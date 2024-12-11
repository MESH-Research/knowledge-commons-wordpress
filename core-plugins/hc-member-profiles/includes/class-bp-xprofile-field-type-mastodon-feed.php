<?php
/**
 * Mastodon Feed field type.
 * 
 * Displays items from a user's Mastodon feed if their Mastodon username is set.
 *
 * @package Hc_Member_Profiles
 */

/**
 * Mastodon Feed field type.
 */
class BP_XProfile_Field_Type_Mastodon_Feed extends BP_XProfile_Field_Type {

	public $name = 'Mastodon Feed';
	public $accepts_null_value = true;

	public function __construct() {
		parent::__construct();
	}

	public static function display_filter( $field_value, $field_id = '' ) {
		$url = hcmp_get_normalized_mastodon_url();
		$cache_key = 'hcmp_mastodon_feed_' . $url;
		$rss_feed_items_html = wp_cache_get( $cache_key );
		if ( ! empty( $rss_feed_items_html ) ) {
			return $rss_feed_items_html;
		}
		if ( empty( $url ) ) {
			return '';
		}
		$url = trim( $url, '/' );
		$rss_url = "$url.rss";
		$rss_feed = fetch_feed( $rss_url );
		if ( is_wp_error( $rss_feed ) ) {
			return '';
		}
		$rss_feed_items = $rss_feed->get_items(0, 5);
		$rss_feed_items_html = '';
		foreach ( $rss_feed_items as $rss_feed_item ) {
			$item_html = '<p>';
			$item_html .= wp_strip_all_tags(
				str_replace(
					['</p>', '<br>', '<br />'],
					' ',
					$rss_feed_item->get_description()
				)
			);
			$item_html .= ' (<a href="' . esc_url( $rss_feed_item->get_permalink() ) . '">' . $rss_feed_item->get_date('Y-m-d') . ' &nearr;</a>)';
			$item_html .= '</p><hr>';
			$rss_feed_items_html .= $item_html;
		}
		wp_cache_set( $cache_key, $rss_feed_items_html, '', 60 * 10 );
		return $rss_feed_items_html;
	}

	public function edit_field_html( array $raw_properties = [] ) {
		echo 'This field lists your Mastodon feed.';
	}

	public function admin_field_html( array $raw_properties = [] ) {
		$this->edit_field_html();
	}
}
	

