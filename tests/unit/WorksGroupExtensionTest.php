<?php
/**
 * Behavioural tests for Works_Groups_Extension (hc-custom).
 *
 * Exercises the group subnav filter and the collection-data persistence
 * round-trip with all WordPress/BuddyPress dependencies mocked. No live
 * HTTP: the wp_remote_* stubs return WP_Error unless a mock callback is set.
 */

use PHPUnit\Framework\TestCase;

if ( ! defined( 'WORKS_URL' ) ) {
	define( 'WORKS_URL', 'https://works.test' );
}
if ( ! defined( 'WORKS_API_KEY' ) ) {
	define( 'WORKS_API_KEY', 'test-key' );
}
if ( ! defined( 'WORKS_KNOWLEDGE_COMMONS_INSTANCE' ) ) {
	define( 'WORKS_KNOWLEDGE_COMMONS_INSTANCE', 'hcommons' );
}

if ( ! class_exists( 'BP_Group_Extension' ) ) {
	class BP_Group_Extension {
		public function init( $args = [] ) {}
	}
}
if ( ! class_exists( 'BP_Core_Nav_Item' ) ) {
	class BP_Core_Nav_Item {}
}

require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/class-works-group-extension.php';

class WorksGroupExtensionTest extends TestCase {

	private const NAV_HTML = '<li id="nav-kcworks-groups-li"><a id="nav-kcworks" href="https://example.org/groups/testgroup/kcworks/">KCWorks</a></li>';

	protected function setUp(): void {
		$GLOBALS['_mock_wp_cache']   = [];
		$GLOBALS['_mock_group_meta'] = [];
		unset(
			$GLOBALS['_mock_current_group_id'],
			$GLOBALS['_mock_wp_remote_get_callback'],
			$GLOBALS['_mock_wp_remote_post_callback']
		);
	}

	/**
	 * Run the subnav filter while collecting every PHP error/warning/notice
	 * raised, so tests can assert on behaviour and on log noise.
	 *
	 * @return array{0: string, 1: string[]} Filter return value and error messages.
	 */
	private function run_subnav_filter( Works_Groups_Extension $extension ): array {
		$errors = [];
		set_error_handler(
			function ( $errno, $errstr ) use ( &$errors ) {
				if ( E_USER_NOTICE !== $errno && E_DEPRECATED !== $errno ) {
					$errors[] = $errstr;
				}
				return true;
			}
		);
		try {
			$result = $extension->filter_kcworks_subnav_link( self::NAV_HTML, new BP_Core_Nav_Item(), 'home' );
		} finally {
			restore_error_handler();
		}
		return [ $result, $errors ];
	}

	/**
	 * Collection data already persisted in groupmeta (in the shape that
	 * save_works_collection_data() has historically written) must resolve
	 * the subnav link without any remote API call.
	 */
	public function test_subnav_link_resolves_from_persisted_groupmeta(): void {
		$GLOBALS['_mock_group_meta'][42] = [
			'kcworks-enable'          => 1,
			'kcworks-collection-data' => [
				'kcworks-collection-slug'       => 'my-collection',
				'kcworks-collection-id'         => 'abc123',
				'kcworks-collection-visibility' => 'public',
			],
		];

		$extension = new Works_Groups_Extension( group_id: 42 );

		[ $result, $errors ] = $this->run_subnav_filter( $extension );

		$this->assertStringContainsString(
			'href="https://works.test/collections/my-collection"',
			$result,
			'Subnav link should point at the persisted collection slug.'
		);
		$this->assertSame( [], $errors, 'No PHP warnings should be raised when collection data is persisted.' );
	}

	/**
	 * Collection data resolved from the KCWorks API must survive a
	 * save/read round-trip: a later request (fresh cache, API unavailable)
	 * must resolve the link from the groupmeta written by the first request.
	 */
	public function test_collection_data_round_trips_through_groupmeta(): void {
		$GLOBALS['_mock_group_meta'][7] = [ 'kcworks-enable' => 1 ];

		$GLOBALS['_mock_wp_remote_get_callback'] = function ( $url, $args ) {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'hits' => [
						'hits' => [
							[
								'slug'   => 'fresh-collection',
								'id'     => 'def456',
								'access' => [ 'visibility' => 'public' ],
							],
						],
					],
				] ),
			];
		};

		$first = new Works_Groups_Extension( group_id: 7 );
		[ $first_result, $first_errors ] = $this->run_subnav_filter( $first );
		$this->assertStringContainsString(
			'href="https://works.test/collections/fresh-collection"',
			$first_result,
			'Subnav link should resolve from the API on first lookup.'
		);
		$this->assertSame( [], $first_errors );

		// Simulate a later request: object cache empty, API unreachable.
		$GLOBALS['_mock_wp_cache'] = [];
		unset( $GLOBALS['_mock_wp_remote_get_callback'] );

		$second = new Works_Groups_Extension( group_id: 7 );
		[ $second_result, $second_errors ] = $this->run_subnav_filter( $second );

		$this->assertStringContainsString(
			'href="https://works.test/collections/fresh-collection"',
			$second_result,
			'Subnav link should resolve from groupmeta saved by the earlier request.'
		);
		$this->assertSame( [], $second_errors );
	}

	/**
	 * When the API reports no collection for the group, previously stored
	 * groupmeta must not be overwritten, and no undefined-variable
	 * warnings may be raised on the failure path.
	 */
	public function test_empty_api_response_does_not_clobber_groupmeta(): void {
		$GLOBALS['_mock_group_meta'][9] = [ 'kcworks-enable' => 1 ];

		$GLOBALS['_mock_wp_remote_get_callback'] = function ( $url, $args ) {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'hits' => [ 'hits' => [] ] ] ),
			];
		};

		$extension = new Works_Groups_Extension( group_id: 9 );
		[ $result, $errors ] = $this->run_subnav_filter( $extension );

		$this->assertSame( '', $result, 'Subnav item should be hidden when no collection exists.' );
		$this->assertSame(
			'',
			$GLOBALS['_mock_group_meta'][9]['kcworks-collection-data'] ?? '',
			'Empty API responses must not write empty collection data over groupmeta.'
		);
		foreach ( $errors as $error ) {
			$this->assertStringNotContainsString(
				'Undefined variable',
				$error,
				'No undefined-variable warnings may be raised on the lookup-failure path.'
			);
		}
	}

	/**
	 * Enabling KCWorks for a group with no existing collection must create
	 * one via the API and persist the slug/id from the creation response.
	 *
	 * The mock reproduces the live KCWorks server: the collection route is
	 * /api/group_collections/ and a request to the slashless URL gets a 308
	 * redirect, across which the WordPress HTTP stack loses the
	 * Authorization header, so the server answers 400 (CSRF token missing).
	 * A successful creation returns commons_group_id, collection and
	 * collection_id.
	 */
	public function test_enabling_kcworks_creates_collection_and_persists_slug(): void {
		$GLOBALS['_mock_group_meta'][21] = [];
		$_POST['kcworks-enable']         = '1';

		$GLOBALS['_mock_wp_remote_get_callback'] = function ( $url, $args ) {
			return [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'hits' => [ 'hits' => [] ] ] ),
			];
		};

		$GLOBALS['_mock_wp_remote_post_callback'] = function ( $url, $args ) {
			if ( '/api/group_collections/' !== parse_url( $url, PHP_URL_PATH ) ) {
				return [
					'response' => [ 'code' => 400 ],
					'body'     => json_encode( [
						'message' => '400 Bad Request: CSRF token missing or incorrect.',
						'status'  => 400,
					] ),
				];
			}
			$sent = json_decode( $args['body'], true );
			return [
				'response' => [ 'code' => 201 ],
				'body'     => json_encode( [
					'commons_group_id' => $sent['commons_group_id'],
					'collection'       => 'new-collection',
					'collection_id'    => 'xyz789',
				] ),
			];
		};

		$extension = new Works_Groups_Extension( group_id: 21 );

		$errors = [];
		set_error_handler(
			function ( $errno, $errstr ) use ( &$errors ) {
				if ( E_USER_NOTICE !== $errno && E_DEPRECATED !== $errno ) {
					$errors[] = $errstr;
				}
				return true;
			}
		);
		try {
			$extension->edit_screen_save( 21 );
		} finally {
			restore_error_handler();
			unset( $_POST['kcworks-enable'] );
		}

		$this->assertSame( [], $errors, 'Creating the collection should raise no PHP warnings.' );
		$meta = $GLOBALS['_mock_group_meta'][21]['kcworks-collection-data'] ?? null;
		$this->assertIsArray( $meta, 'Collection data should be persisted to groupmeta after creation.' );
		$this->assertSame( 'new-collection', $meta['slug'] );
		$this->assertSame( 'xyz789', $meta['id'] );
	}

	/**
	 * Groups that have not enabled KCWorks should render no subnav item
	 * and produce no log noise.
	 */
	public function test_subnav_hidden_when_kcworks_not_enabled(): void {
		$GLOBALS['_mock_group_meta'][13] = [ 'kcworks-enable' => 0 ];

		$extension = new Works_Groups_Extension( group_id: 13 );
		[ $result, $errors ] = $this->run_subnav_filter( $extension );

		$this->assertSame( '', $result );
		$this->assertSame( [], $errors );
	}
}
