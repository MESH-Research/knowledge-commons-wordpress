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
			],
		];
		parent::init( $args );
	}

	public function settings_screen( $group_id = null ) {
		echo 'KCWorks settings screen';
	}

	public function settings_screen_save( $group_id = null ) {
		echo 'KCWorks settings screen save';
	}

	public function create_screen( $group_id = null ) {
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
				<input type='checkbox' id='kcworks-enable' name='kcworks-enable' value='1' />
				<?= esc_html__( 'Associate a KCWorks collection with this group.', 'hc-custom' ); ?>
			</label>
		</div>
		<?php
		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}

	public function create_screen_save( $group_id = null ) {
		check_admin_referer( 'groups_create_save_' . $this->slug );
		if ( ! $group_id ) {
			trigger_error( 'In Works_Groups_Extension::create_screen_save, $group_id is not set.', E_USER_WARNING );
			return;
		}
		if ( ! isset( $_POST['kcworks-enable'] ) ) {
			trigger_error( 'In Works_Groups_Extension::create_screen_save, $_POST[\'kcworks-enable\'] is not set.', E_USER_WARNING );
			return;
		}
		
		$enable = intval( $_POST['kcworks-enable'] );
		groups_update_groupmeta( $group_id, 'kcworks-enable', $enable );
		$this->signal_create_works_collection( $group_id );
	}

	public function edit_screen( $group_id = null ) {
		echo 'KCWorks edit screen';
	}

	public function edit_screen_save( $group_id = null ) {
		echo 'KCWorks edit screen save';
	}

	/**
	 * Signal to KCWorks that a new collection should be created and associated with this group.
	 */
	private function signal_create_works_collection(int $group_id) {
		$endpoint = WORKS_URL . '/api/group_collections';
		$response = wp_remote_post( $endpoint, [
			'body' => [
				'commons_instance'      => 'knowledgeCommons',
				'commons_group_id'      => $group_id,
				'collection_visibility' => 'public',
			],
		] );
		if ( is_wp_error( $response ) ) {
			trigger_warning( 'In Works_Groups_Extension::signal_create_works_collection, error creating collection: ' . $response->get_error_message(), E_USER_WARNING );
			return;
		}
	}
}