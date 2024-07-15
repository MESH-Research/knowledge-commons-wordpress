<?php
/**
 * Integration between BuddyPress groups and Knowledge Commons Works.
 */

class Works_Groups_Extension extends \BP_Group_Extension {
	public function __construct(
		public bool   $enabled                     = true,
		public string $works_collection_slug       = '',
		public string $works_collection_id         = '',
		public string $works_collection_visibility = '',
		public $slug                               = 'kcworks',
		public $group_id                           = 0,
	) {
		if ( ! defined( 'WORKS_URL' ) ) {
			trigger_error( 'In hc-custom, WORKS_URL is not defined.', E_USER_WARNING );
			$this->enabled = false;
		}

		if ( ! defined( 'WORKS_API_KEY' ) ) {
			trigger_error( 'In hc-custom, WORKS_API_KEY is not defined.', E_USER_WARNING );
			$this->enabled = false;
		}

		if ( ! defined( 'WORKS_KNOWLEDGE_COMMONS_INSTANCE' ) ) {
			trigger_error( 'In hc-custom, WORKS_KNOWLEDGE_COMMONS_INSTANCE is not defined.', E_USER_WARNING );
			$this->enabled = false;
		}

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
		if ( ! $this->enabled ) {
			return $value;
		}
		if ( ! $this->group_id ) {
			$this->group_id = bp_get_current_group_id() ?? 0;
		}
		if ( ! $this->group_id ) {
			trigger_error( '$group_id is not set.', E_USER_WARNING );
			return $value;
		}
		$collection_enabled = (bool) groups_get_groupmeta( $this->group_id, 'kcworks-enable' );
		if ( ! $collection_enabled ) {
			return $value;
		}
		$this->update_works_collection_data();
		if ( ! $this->works_collection_slug ) {
			trigger_error( 'collection_slug is not set.', E_USER_WARNING );
			return $value;
		}
		$collection_url = WORKS_URL . '/collections/' . $this->works_collection_slug;
		return preg_replace( '/href="[^"]*"/', 'href="' . $collection_url . '"', $value );
	}

	/**
	 * Screen displayed when creating a new group.
	 */
	public function create_screen( $group_id = null ) {
		if ( $group_id ) {
			$this->group_id = $group_id;
		}
		$this->create_edit_form();
	}

	public function create_screen_save( $group_id = null ) {
		if ( $group_id ) {
			$this->group_id = $group_id;
		}
		$this->create_edit_save( $group_id );
	}

	/**
	 * Screen displayed when managing a group.
	 */
	public function edit_screen( $group_id = null ) {
		if ( $group_id ) {
			$this->group_id = $group_id;
		}
		$this->create_edit_form();
	}

	public function edit_screen_save( $group_id = null ) {
		if ( $group_id ) {
			$this->group_id = $group_id;
		}
		$this->create_edit_save( $group_id );
	}

	private function create_edit_save( $group_id = null ) {
		if ( ! $group_id ) {
			$group_id = $this->group_id;
		}
		if ( ! $this->enabled ) {
			trigger_error( 'In Works_Groups_Extension::create_edit_save, extension is not enabled.', E_USER_WARNING );
			return;
		}
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
			$this->signal_enable_works_collection();
		} else {
			$this->signal_disable_works_collection();
		}
	}

	private function create_edit_form() : void {
		if ( ! $this->enabled ) {
			trigger_error( 'In Works_Groups_Extension::create_edit_form, extension is not enabled.', E_USER_WARNING );
			return;
		}
		$collection_enabled = (bool) groups_get_groupmeta( $this->group_id, 'kcworks-enable' );
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
	private function signal_enable_works_collection() : void {
		if ( ! $this->group_id ) {
			trigger_error( 'In Works_Groups_Extension::signal_enable_works_collection, $group_id is not set.', E_USER_WARNING );
			return;
		}
		if ( ! $this->enabled ) {
			trigger_error( 'In Works_Groups_Extension::signal_enable_works_collection, extension is not enabled.', E_USER_WARNING );
			return;
		}
		$this->update_works_collection_data();
		if ( $this->works_collection_id ) {
			$success = $this->change_collection_visibility( 'public' );
		} else {
			$success = $this->create_collection();
		}
		if ( ! $success ) {
			trigger_error( "Works_Groups_Extension::signal_enable_works_collection, failed to create collection or make collection public for group: {$this->group_id}", E_USER_WARNING );
		} else {
			trigger_error( "Works_Groups_Extension::signal_enable_works_collection, created or made collection public for group: $this->group_id", E_USER_NOTICE );
		}
	}

	/**
	 * Signal to KCWorks that a collection should be hidden or deleted.
	 */
	private function signal_disable_works_collection() : void {
		if ( ! $this->enabled ) {
			trigger_error( 'In Works_Groups_Extension::signal_disable_works_collection, extension is not enabled.', E_USER_WARNING );
			return;
		}
		if ( ! $this->group_id ) {
			trigger_error( 'In Works_Groups_Extension::signal_disable_works_collection, $group_id is not set.', E_USER_WARNING );
			return;
		}
		$this->update_works_collection_data();
		$success = $this->change_collection_visibility( 'restricted' );
		if ( ! $success ) {
			trigger_error( "Works_Groups_Extension::signal_disable_works_collection, failed to hide collection for group: $this->group_id", E_USER_WARNING );
		} else {
			trigger_error( "Works_Groups_Extension::signal_disable_works_collection, made collection hidden for group: $this->group_id", E_USER_NOTICE );
		}
	}

	private function create_collection() : bool {
		if ( ! $this->enabled ) {
			trigger_error( 'In Works_Groups_Extension::create_collection, extension is not enabled.', E_USER_WARNING );
			return false;
		}
		if ( ! $this->group_id ) {
			trigger_error( 'In Works_Groups_Extension::create_collection, $group_id is not set.', E_USER_WARNING );
			return false;
		}
		$endpoint = WORKS_URL . '/api/group_collections';
		try {
			$response = wp_remote_post( $endpoint, [
				'headers' => [
					'Authorization' => 'Bearer ' . WORKS_API_KEY,
					'Content-Type' => 'application/json',
				],
				'body' => json_encode([
					'commons_instance'      => WORKS_KNOWLEDGE_COMMONS_INSTANCE,
					'commons_group_id'      => (string) $this->group_id,
					'collection_visibility' => 'public',
				]),
			] );
		} catch ( Exception $e ) {
			trigger_error( 'Works_Groups_Extension::signal_enable_works_collection, exception raised creating collection: ' . $e->getMessage(), E_USER_WARNING );
			return false;
		}
		if ( is_wp_error( $response ) ) {
			trigger_error( 'Works_Groups_Extension::signal_enable_works_collection, wp_error creating collection: ' . $response->get_error_code() . ': ' . $response->get_error_message(), E_USER_WARNING );
			return false;
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
			trigger_error( 'Works_Groups_Extension::signal_enable_works_collection, error creating collection: ' . $message, E_USER_WARNING );
			return false;
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $response_body || intval( $response_body->commons_group_id ) !== $this->group_id ) {
			trigger_error( "Works_Groups_Extension::signal_enable_works_collection group_id {$this->group_id} != commons_group_id {$response_body->commons_group_id}", E_USER_WARNING );
			return false;
		}
		$this->works_collection_slug = $response_body->new_collection_slug ?? $this->works_collection_slug;
		$this->works_collection_id = $response_body->new_collection_id ?? $this->works_collection_id;
		$this->save_works_collection_data();
		return true;
	}

	private function change_collection_visibility( string $visibility ) : bool {
		if ( ! $this->enabled ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, extension is not enabled.', E_USER_WARNING );
			return false;
		}
		if ( ! $this->works_collection_id ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, works_collection_id is not set.', E_USER_WARNING );
			return false;
		}
		$endpoint = WORKS_URL . "/api/communities/{$this->works_collection_id}";
		try {
			$response = wp_remote_get( $endpoint, [
				'headers' => [
					'Authorization' => 'Bearer ' . WORKS_API_KEY,
				],
			] );
		} catch ( Exception $e ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, error getting collection: ' . $e->getMessage(), E_USER_WARNING );
			return false;
		}

		if ( is_wp_error( $response ) ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, error getting collection: ' . $response->get_error_message(), E_USER_WARNING );
			return false;
		}

		$collection_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $collection_data ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, error decoding collection data: ' . wp_remote_retrieve_body( $response ), E_USER_WARNING );
			return false;
		}
		$collection_data['access']['visibility'] = $visibility;

		try {
			$response = wp_remote_request( $endpoint, [
				'method' => 'PUT',
				'headers' => [
					'Authorization' => 'Bearer ' . WORKS_API_KEY,
					'Content-Type' => 'application/json',
				],
				'body' => json_encode( $collection_data ),
			] );
		} catch ( Exception $e ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, exception thrown changing collection visibility: ' . $e->getMessage(), E_USER_WARNING );
			return false;
		}

		if ( is_wp_error( $response ) ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, wp_error changing collection visibility: ' . $response->get_error_message(), E_USER_WARNING );
			return false;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			trigger_error( 'In Works_Groups_Extension::change_collection_visibility, non-200 response code changing collection visibility: ' . wp_remote_retrieve_body( $response ), E_USER_WARNING );
			return false;
		}

		return true;
	}

	private function update_works_collection_data() : void {
		if ( ! $this->enabled ) {
			trigger_error( 'In Works_Groups_Extension::get_works_collection_slug, extension is not enabled.', E_USER_WARNING );
			return;
		}
		if ( ! $this->group_id ) {
			trigger_error( 'In Works_Groups_Extension::get_works_collection_slug, $group_id is not set.', E_USER_WARNING );
			return;
		}
		
		$collection_data = wp_cache_get( 'kcworks-collection-data-' . $this->group_id );
		if ( is_array( $collection_data ) ) {
			$this->works_collection_slug       = $collection_data['slug'] ?? '';
			$this->works_collection_id         = $collection_data['id'] ?? '';
			$this->works_collection_visibility = $collection_data['visibility'] ?? '';
		}
		
		if ( ! $this->works_collection_slug || ! $this->works_collection_id ) {
			$collection_data = groups_get_groupmeta( $this->group_id, 'kcworks-collection-data' );
			if ( is_array( $collection_data ) ) {
				wp_cache_add( 'kcworks-collection-data-' . $this->group_id, $collection_data, '', 60 * 10 );
				$this->works_collection_slug       = $collection_data['slug'] ?? '';
				$this->works_collection_id         = $collection_data['id'] ?? '';
				$this->works_collection_visibility = $collection_data['visibility'] ?? '';
			}
		}
		
		if ( ! $this->works_collection_slug || ! $this->works_collection_id ) {
			$endpoint = WORKS_URL . '/api/group_collections?commons_instance=' . WORKS_KNOWLEDGE_COMMONS_INSTANCE . "&commons_group_id={$this->group_id}";
			try {
				$response = wp_remote_get( $endpoint, [
					'headers' => [
						'Authorization' => 'Bearer ' . WORKS_API_KEY,
					],
				] );
			} catch ( Exception $e ) {
				trigger_error( 'In Works_Groups_Extension::get_works_collection_slug, exception thrown getting collection: ' . $e->getMessage(), E_USER_WARNING );
				return;
			}
	
			if ( is_wp_error( $response ) ) {
				trigger_error( 'In Works_Groups_Extension::get_works_collection_slug, wp_error getting collection: ' . $response->get_error_message(), E_USER_WARNING );
				return ;
			}
	
			$response_body = wp_remote_retrieve_body( $response );
			trigger_error( 'In Works_Groups_Extension::get_works_collection_slug, response_body: ' . $response_body, E_USER_NOTICE );
			$collection_data = json_decode( $response_body, true );
			if ( ! $collection_data ) {
				trigger_error( 'In Works_Groups_Extension::get_works_collection_slug, error getting collection: ' . wp_remote_retrieve_body( $response ), E_USER_WARNING );
				return;
			}
			
			$this->works_collection_slug       = $collection_data['hits']['hits'][0]['slug'] ?? '';
			$this->works_collection_id         = $collection_data['hits']['hits'][0]['id'] ?? '';
			$this->works_collection_visibility = $collection_data['hits']['hits'][0]['access']['visibility'] ?? '';
			
			$this->save_works_collection_data();
		}

		wp_cache_add( 'kcworks-collection-data-' . $group_id, $collection_data, '', 60 * 10 );
		return;
	}

	private function save_works_collection_data(): void {
		if ( ! $this->group_id ) {
			trigger_error( 'In Works_Groups_Extension::set_works_collection_data, $group_id is not set.', E_USER_WARNING );
			return;
		}
		groups_update_groupmeta( 
			$this->group_id, 
			'kcworks-collection-data',
			[
				'kcworks-collection-slug'       => $this->works_collection_slug,
				'kcworks-collection-id'         => $this->works_collection_id,
				'kcworks-collection-visibility' => $this->works_collection_visibility,
			]
		);
	}
}