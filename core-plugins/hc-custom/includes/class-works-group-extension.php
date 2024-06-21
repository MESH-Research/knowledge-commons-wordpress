<?php
/**
 * Integration between BuddyPress groups and Knowledge Commons Works.
 */

class Works_Groups_Extension extends \BP_Group_Extension {
	public function __construct() {
		if ( ! defined( 'WORKS_URL' ) ) {
			trigger_error( 'In hc-custom, WORKS_URL is not defined.', E_USER_WARNING );
			return;
		}

		$this->slug = 'kcworks';
		
		$args = [
			'slug' => $this->slug,
			'name' => 'KCWorks',
			'nav_item_position' => 42,
			'screens' => [
				'create' => [
					'enabled' => true,
					'position' => 15,
				],
				'edit' => [
					'enabled' => true,
					'position' => 42,
				],
				'admin' => [
					'enabled' => false,
				],
			],
		];

		add_action( 'bp_get_options_nav_nav-kcworks', [ $this, 'filter_kcworks_subnav_link' ], 10, 3 );
		parent::init( $args );
	}

	public function filter_kcworks_subnav_link( string $value, \BP_Core_Nav_Item $subnav_item, string $selected_item ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			trigger_error( '$group_id is not set.', E_USER_WARNING );
			return '';
		}
		$collection_enabled = (bool) groups_get_groupmeta( $group_id, 'kcworks-enable' );
		if ( ! $collection_enabled ) {
			return '';
		}
		$collection_slug = groups_get_groupmeta( $group_id, 'kcworks-collection-slug' );
		if ( ! $collection_slug ) {
			trigger_error( '$collection_slug is not set.', E_USER_WARNING );
			return '';
		}
		$collection_url = WORKS_URL . '/collections/' . $collection_slug;
		return preg_replace( '/href="[^"]*"/', 'href="' . $collection_url . '"', $value );
	}

	/**
	 * Screen displayed when creating a new group.
	 */
	public function create_screen( $group_id = null ) {
		$this->create_edit_form();
	}

	public function create_screen_save( $group_id = null ) {
		$this->create_edit_save( $group_id );
	}

	/**
	 * Screen displayed when managing a group.
	 */
	public function edit_screen( $group_id = null ) {
		$this->create_edit_form();
	}

	public function edit_screen_save( $group_id = null ) {
		$this->create_edit_save( $group_id );
	}

	private function create_edit_save( $group_id = null ) {
		check_admin_referer( 'groups_create_save_' . $this->slug );
		if ( ! $group_id ) {
			trigger_error( 'In Works_Groups_Extension::create_screen_save, $group_id is not set.', E_USER_WARNING );
			return;
		}
		
		if ( ! isset( $_POST['kcworks-enable'] ) ) {
			$enable = 0;
		} else {
			$enable = intval( $_POST['kcworks-enable'] );
		}

		groups_update_groupmeta( $group_id, 'kcworks-enable', $enable );
		if ( $enable ) {
			$this->signal_enable_works_collection( $group_id );
		} else {
			$this->signal_disable_works_collection( $group_id );
		}
	}

	private function create_edit_form() : void {
		$collection_enabled = (bool) groups_get_groupmeta( bp_get_current_group_id(), 'kcworks-enable' );
		?>
		<h2><?= esc_html__( 'Create a KCWorks collection', 'hc-custom' ); ?></h2>
		<p>
			<a href='<?= WORKS_URL ?>'><?= esc_html__( 'Knowledge Commons Works', 'hc-custom' ); ?></a>
			<?= esc_html__( ' is the open access repository of the Knowledge Commons network. Knowledge Commons members can upload work for dissemination, preservation, and collaboration with others making it findable by a wider audience.', 'hc-custom' ); ?>
		</p>
		<p>
			<?= esc_html__( 'You can associate a KCWorks collection with this group. This will allow members of this group to deposit works into the collection.', 'hc-custom' ); ?>
		</p>
		<div>
			<label for='kcworks-enable'>
				<input type='checkbox' id='kcworks-enable' name='kcworks-enable' value='1' <?= $collection_enabled ? 'checked' : '' ?>/>
				<?= esc_html__( 'Associate a KCWorks collection with this group.', 'hc-custom' ); ?>
			</label>
		</div>
		<?php
		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}

	/**
	 * Signal to KCWorks that a new collection should be created and associated with this group.
	 *
	 * @see https://github.com/MESH-Research/invenio-group-collections#creating-a-collection-for-a-group-post
	 */
	private function signal_enable_works_collection(int $group_id) {
		$endpoint = WORKS_URL . '/api/group_collections';
		try {
			$response = wp_remote_post( $endpoint, [
				'headers' => [
					'Authorization' => 'Bearer ' . WORKS_API_KEY,
				],
				'body' => [
					'commons_instance'      => 'knowledgeCommons',
					'commons_group_id'      => $group_id,
					'collection_visibility' => 'public',
				],
			] );
		} catch ( Exception $e ) {
			trigger_error( 'In Works_Groups_Extension::signal_create_works_collection, error creating collection: ' . $e->getMessage(), E_USER_WARNING );
			return;
		}
		if ( is_wp_error( $response ) ) {
			trigger_error( 'In Works_Groups_Extension::signal_create_works_collection, error creating collection: ' . $response->get_error_message(), E_USER_WARNING );
			return;
		}
		if ( 201 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = match( wp_remote_retrieve_response_code( $response ) ) {
				400 => '400 Bad request: The request body is missing required fields or contains invalid data.',
				401 => '401 Unauthorized',
				403 => '403 Forbidden: The request is not authorized to modify the collection',
				404 => '404 Not found: The specified group could not be found by the callback to the Commons instance',
				500 => '500 Internal server error',
				default => wp_remote_retrieve_response_code( $response ) . ' Unknown error',
			};
			trigger_error( 'In Works_Groups_Extension::signal_create_works_collection, error creating collection: ' . $message, E_USER_WARNING );
			return;
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $response_body || ! intval( $response_body->commons_group_id ) !== $group_id ) {
			trigger_error( 'In Works_Groups_Extension::signal_create_works_collection, error creating collection: ' . wp_remote_retrieve_body( $response ), E_USER_WARNING );
			return;
		}
		groups_update_groupmeta( $group_id, 'kcworks-collection-slug', $response_body->new_collection_slug );
	}

	/**
	 * Signal to KCWorks that a collection should be hidden or deleted.
	 */
	private function signal_disable_works_collection( int $group_id ) {
		$collection_slug = groups_get_groupmeta( $group_id, 'kcworks-collection-slug' );
		$endpoint = WORKS_URL . "/api/communities/$collection_slug";
		try {
			$response = wp_remote_request( $endpoint, [
				'method' => 'PUT',
				'headers' => [
					'Authorization' => 'Bearer ' . WORKS_API_KEY,
				],
				'body' => json_encode( [
					'access' => [
						'visibility' => 'hidden',
					],
				] ),
			] );
		} catch ( Exception $e ) {
			trigger_error( 'In Works_Groups_Extension::signal_disable_works_collection, error disabling collection: ' . $e->getMessage(), E_USER_WARNING );
			return;
		}
	}
}