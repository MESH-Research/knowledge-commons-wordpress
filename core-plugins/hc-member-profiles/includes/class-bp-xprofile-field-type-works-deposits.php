<?php
/**
 * CORE Deposits field type.
 *
 * @package Hc_Member_Profiles
 */

/**
 * CORE Deposits field type.
 */
class BP_XProfile_Field_Type_Works_Deposits extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'Works Deposits';

	/**
	 * If allowed to store null/empty values.
	 *
	 * @var bool If this is set, allow BP to store null/empty values for this field type.
	 */
	public $accepts_null_value = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Front-end display of user's blog posts, ordered by date.
	 * 
	 * @uses DOMDocument
	 *
	 * @param mixed      $field_value Field value.
	 * @param string|int $field_id    ID of the field.
	 * @return mixed
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		$username = bp_get_displayed_user_username();

		$cache_key = "hc-member-profiles-xprofile-works-deposits-{$username}";
		$html = wp_cache_get( $cache_key );
		if ( $html ) {
			return $html;
		}

		if ( ! defined('WORKS_URL') || WORKS_URL === '' ) {
			trigger_error( 'In hc-member-profiles, WORKS_URL is not defined.', E_USER_WARNING );
			return '';
		}

		$api_endpoint = WORKS_URL . '/api/records';
		$query_string = $api_endpoint . '?q=metadata.creators.person_or_org.identifiers.identifier:' . $username;
		$response = wp_remote_get( $query_string );
		if ( is_wp_error( $response ) ) {
			trigger_error( 'In hc-member-profiles, error fetching works: ' . $response->get_error_message(), E_USER_WARNING );
			return '';
		}
		
		$works = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $works || ! $works->hits || ! $works->hits->hits ) {
			return '';
		}

		$works_links = [];
		foreach ( $works->hits->hits as $work ) {
			$work_link = "<li><a href='" . $work->links->latest_html . "'>" . $work->metadata->title . "</a>";
			$work_link .= $work->metadata->publication_date ? " (" . $work->metadata->publication_date . ")" : "";
			$work_link .= '</li>';

			$work_type = $work->metadata->resource_type->title->en ?? 'Other';
			if ( ! array_key_exists( $work_type, $works_links ) ) {
				$works_links[ $work_type ] = [];
			}
			$works_links[ $work_type ][] = $work_link;
		}

		$html = "<ul>";
		foreach ( $works_links as $work_type => $work_links ) {
			$html .= "<li><strong>$work_type</strong><ul>";
			foreach ( $work_links as $work_link ) {
				$html .= $work_link;
			}
			$html .= "</ul></li>";
		}
		$html .= "</ul>";

		wp_cache_add( $cache_key, $html, '', 1 );
		return $html;
	}

	/**
	 * Placeholder HTML for the widget on the user side (not editable).
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function edit_field_html( array $raw_properties = [] ) {
		echo 'This field lists your Commons Works deposits.';
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function admin_field_html( array $raw_properties = [] ) {
		$this->edit_field_html();
	}

}
