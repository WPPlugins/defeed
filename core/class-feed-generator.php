<?php
namespace Defeed;

/**
 * Class Feed_Generator
 * Do the feeds generation.
 * @package Defeed
 * @since 0.9.0
 */
class Feed_Generator {

	/**
	 * @var \stdClass null
	 */
	private $feed = null;

	function __construct() {
		add_action( 'template_redirect', array( $this, 'generate' ) );
		add_action( 'wp_loaded', array( '\Defeed\Feed_Generator', 'routes_setup' ) );
	}

	/**
	 * Setup rewrite rules for feeds
	 */
	static function routes_setup() {
		add_rewrite_rule(
			'^defeed/([^/]*)/?$',
			'index.php?defeed_feed_name=$matches[1]',
			'top'
		);
		add_rewrite_tag( '%defeed_feed_name%', '([^/]*)');

		static::maybe_flush_rewrite();
	}

	/**
	 * Flush rewrite rules on installs that was done prior, proper placing add_rewrite_rule.
	 * Previously it was done only on plugin activation, and if other plugin or anything else call flush_rewrite_rules,
	 * defeed's rewrite were removed.
	 */
	static function maybe_flush_rewrite() {
		$defeed_options = get_option('defeed_additional_options');
		if ( ! $defeed_options || ( is_array( $defeed_options ) && ! $defeed_options['is_done_forced_rules_flush'] ) ) {
			if ( ! $defeed_options ) {
				flush_rewrite_rules();
				$defeed_options = array(
					'is_done_forced_rules_flush' => true,
				);
				add_option( 'defeed_additional_options', $defeed_options );
			} elseif ( is_array( $defeed_options ) && ! $defeed_options['is_done_forced_rules_flush'] ) {
				flush_rewrite_rules();
				$defeed_options['is_done_forced_rules_flush'] = true;
			}
		}
	}

	static function flush_rewrite() {
		flush_rewrite_rules();
	}

	function generate() {
		if ( get_query_var('defeed_feed_name') ) {
			$this->load_dependencies();
			$this->prepare_info();
			$this->generate_xml_header();
			$this->generate_xml_body();
			$this->generate_xml_footer();
			die();
		}
	}

	private function load_dependencies() {
		require_once Main::get_path() . '/libs/core-nav-menus/nav-menu-from-wp-include.php';
	}

	private function prepare_info() {
		// Get feed object
		$feed_slug = get_query_var( 'defeed_feed_name' );
		$feed_raw = defeed_get_object( $feed_slug );
		if ( empty( $feed_raw ) || "stdClass" != get_class( $feed_raw ) ) {
			return;
		}

		// Get feed's items
		$feed_raw->items = defeed_get_feed_items( $feed_raw->term_id, array( 'update_post_term_cache' => false ) );
		if ( empty( $feed_raw->items ) ) {
			return;
		}

		// Store feed's info in class property
		$this->feed = new \stdClass();
		$this->feed->title = $feed_raw->name;
		$this->feed->link = home_url() . '/defeed/' . $feed_raw->slug;
		$this->feed->items = array();
		$this->feed->post_type = null;
		foreach( $feed_raw->items as $key => $item ) {
			$item_info = get_post_meta( $item->ID, '_defeed_item_info', true );
			if ( 'main' === $item_info['type'] && 'post-container' === $item_info['id'] ) {
				$this->feed->post_type = $item->object;
			} else {
				$this->feed->items[] = $item_info;
			}
		}

		// Throw exception if no main container found
		if ( ! $this->feed->post_type ) {
			throw new \Exception( 'Defeed: no container found during feed generation.' );
		}
	}

	private function generate_xml_header() {
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
		echo "<rss version=\"2.0\">\n";
		echo "	<channel>\n";
		echo "		<title>{$this->feed->title}</title>\n";
		echo "		<link>{$this->feed->link}</link>\n";
	}

	private function generate_xml_footer() {
		echo "	</channel>\n";
		echo "</rss>\n";
	}

	private function generate_xml_body() {
		$base_element = $this->feed->post_type;
		echo "\t\t<{$base_element}s>\n";
		$this->output_xml_items();
		echo "\t\t</{$base_element}s>\n";
	}

	private function output_xml_items() {
		$entities = $this->get_items();
		foreach ( $entities as $entity ) {
			$this->output_item( $entity );
		}
	}

	private function output_item( $entity ) {
		$base_element = $this->feed->post_type;
		echo "\t\t\t<$base_element>\n";
		foreach( $this->feed->items as $item ) {
			switch( $item['type'] ) {
				case 'main':
					$this->output_main_info_types( $item, $entity );
					break;
				case 'metas':
					$this->output_metas( $item, $entity );
					break;
				case 'taxonomies':
					$this->output_tax( $item, $entity );
					break;
			}
		}
		echo "\t\t\t</$base_element>\n";
	}

	private function output_tax( $item, $entity ) {
		$taxonomy = $item['id'];
		$value = implode( ', ', wp_get_post_terms( $entity->ID, $taxonomy, array( 'fields' => 'names' ) ) );
		echo "\t\t\t\t<$taxonomy>";
		echo $value;
		echo "</$taxonomy>\n";
	}

	private function output_metas( $item, $entity ) {
		$value = get_post_meta( $entity->ID, $item['id'], true );
		$name = trim( $item['id'], '_' );
		echo "\t\t\t\t<$name>";
		echo $value;
		echo "</$name>\n";
	}

	private function output_main_info_types( $item, $entity ) {
		switch( $item['id'] ) {
			case 'post-title' :
				$this->output_title( $entity );
				break;
			case 'post-content' :
				$this->output_content( $entity );
				break;
			case 'featured-image' :
				$this->output_featured_image( $entity );
				break;
			case 'post-link' :
				$this->output_link( $entity );
				break;
			case 'post-date' :
				$this->output_date( $entity );
				break;
		}
	}

	private function output_featured_image( $entity ) {
		$image_id = get_post_thumbnail_id( $entity->ID );
		if ( $image_id ) {
			$image_src       = wp_get_attachment_image_src( $image_id, 'full' );
			$image_mime_type = get_post_mime_type( $image_id );
			echo "\t\t\t\t" . '<media:content url="' . $image_src[0] . '" height="' . $image_src[2] . '" width="' . $image_src[1] . '" type="' . $image_mime_type . '"/>' . "\n";
		}
	}

	private function output_title( $entity ) {
		$title = apply_filters( 'post_title', $entity->post_title );
		echo "\t\t\t\t<title>";
		echo $title;
		echo "</title>\n";
	}

	private function output_link( $entity ) {
		$link = get_post_permalink( $entity );
		$link = apply_filters('the_permalink_rss', $link );
		echo "\t\t\t\t<link>";
		echo $link;
		echo "</link>\n";
	}

	private function output_date( $entity ) {
		$date = get_post_time( 'D, d M Y H:i:s', true, $entity );
		echo "\t\t\t\t<pubDate>";
		echo $date . ' GMT';
		echo "</pubDate>\n";
	}

	private function output_content( $entity ) {
		setup_postdata( $entity );
		$content = get_the_excerpt();
		wp_reset_postdata();
		echo "\t\t\t\t<description><![CDATA[";
		echo $content;
		echo "]]></description>\n";
	}

	private function get_items() {
		$args = array(
			'post_type' => $this->feed->post_type,
			'post_status' => 'publish',
			'posts_per_page' => -1,
		);
		$entities = get_posts( $args );

		return $entities;
	}

}