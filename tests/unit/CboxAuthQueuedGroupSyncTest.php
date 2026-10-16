<?php

use PHPUnit\Framework\TestCase;
use MLA\Commons\Plugin\CustomAuth\GroupBehavior;
use MLA\Commons\Plugin\CustomAuth\Logger;
use MLA\Commons\Plugin\CustomAuth\MLAAPI;
use MLA\Commons\Plugin\CustomAuth\MLAGroup;

require_once __DIR__ . '/cbox-auth-group-sync-loader.php';

/**
 * Viewing an MLA group must no longer call the MLA API inside the visitor's
 * request. The page view queues a WP-Cron event when the group is due; the
 * cron container performs the sync.
 */
class CboxAuthQueuedGroupSyncTest extends TestCase {

	private const GROUP = 417;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_mock_cron']       = array();
		$GLOBALS['_mock_group_meta'] = array();
		$GLOBALS['_hc_mock']         = array( 'current_group_id' => self::GROUP );
	}

	private function logger(): Logger {
		$logger = new Logger();
		$logger->createLog( null ); // dev-null
		return $logger;
	}

	private function behavior( array $overrides = array() ): GroupBehavior {
		$api = $this->createMock( MLAAPI::class );
		return new GroupBehavior( $api, $this->logger() );
	}

	private function group_meta( array $meta ): void {
		$GLOBALS['_mock_group_meta'][ self::GROUP ] = $meta;
	}

	// ------------------------------------------------------------- is_due

	public function test_group_without_an_mla_api_id_is_never_due(): void {
		$this->group_meta( array( 'last_updated' => 0 ) );

		$this->assertFalse( MLAGroup::is_due( self::GROUP ) );
	}

	public function test_group_synced_within_the_hour_is_not_due(): void {
		$this->group_meta( array( 'mla_api_id' => 'D123', 'last_updated' => time() - 600 ) );

		$this->assertFalse( MLAGroup::is_due( self::GROUP ) );
	}

	public function test_group_synced_over_an_hour_ago_is_due(): void {
		$this->group_meta( array( 'mla_api_id' => 'D123', 'last_updated' => time() - 3601 ) );

		$this->assertTrue( MLAGroup::is_due( self::GROUP ) );
	}

	public function test_group_never_synced_is_due(): void {
		$this->group_meta( array( 'mla_api_id' => 'D123' ) );

		$this->assertTrue( MLAGroup::is_due( self::GROUP ) );
	}

	// ------------------------------------------------------------ queueing

	public function test_viewing_a_due_group_queues_one_sync_event_for_it(): void {
		$this->group_meta( array( 'mla_api_id' => 'D123', 'last_updated' => 0 ) );

		$queued = $this->behavior()->queue_membership_sync();

		$this->assertTrue( $queued );
		$events = $GLOBALS['_mock_cron'][ GroupBehavior::SYNC_EVENT ] ?? array();
		$this->assertCount( 1, $events );
		$this->assertSame( array( self::GROUP ), $events[0]['args'] );
		$this->assertLessThanOrEqual( time(), $events[0]['timestamp'], 'runs at the next cron pass, not later' );
	}

	public function test_viewing_a_recently_synced_group_queues_nothing(): void {
		$this->group_meta( array( 'mla_api_id' => 'D123', 'last_updated' => time() - 60 ) );

		$this->assertFalse( $this->behavior()->queue_membership_sync() );
		$this->assertArrayNotHasKey( GroupBehavior::SYNC_EVENT, $GLOBALS['_mock_cron'] );
	}

	public function test_viewing_a_non_mla_group_queues_nothing(): void {
		$this->group_meta( array() );

		$this->assertFalse( $this->behavior()->queue_membership_sync() );
		$this->assertArrayNotHasKey( GroupBehavior::SYNC_EVENT, $GLOBALS['_mock_cron'] );
	}

	public function test_a_group_already_queued_is_not_queued_twice(): void {
		$this->group_meta( array( 'mla_api_id' => 'D123', 'last_updated' => 0 ) );
		$behavior = $this->behavior();

		$this->assertTrue( $behavior->queue_membership_sync() );
		$this->assertFalse( $behavior->queue_membership_sync() );
		$this->assertCount( 1, $GLOBALS['_mock_cron'][ GroupBehavior::SYNC_EVENT ] );
	}

	public function test_outside_a_group_nothing_is_queued(): void {
		$GLOBALS['_hc_mock']['current_group_id'] = 0;

		$this->assertFalse( $this->behavior()->queue_membership_sync() );
		$this->assertSame( array(), $GLOBALS['_mock_cron'] );
	}

	// ----------------------------------------------------------- the sync

	public function test_cron_callback_syncs_the_group_it_was_queued_for(): void {
		$synced   = array();
		$behavior = new class( $this->createMock( MLAAPI::class ), $this->logger(), $synced ) extends GroupBehavior {
			private $synced;
			public function __construct( $api, $logger, &$synced ) {
				parent::__construct( $api, $logger );
				$this->synced = &$synced;
			}
			protected function make_group( $group_id ) {
				$synced = &$this->synced;
				return new class( $group_id, $synced ) {
					private $id; private $synced;
					public function __construct( $id, &$synced ) { $this->id = $id; $this->synced = &$synced; }
					public function sync() { $this->synced[] = $this->id; return true; }
				};
			}
		};

		$this->assertTrue( $behavior->sync_membership_from_api( 905 ) );
		$this->assertSame( array( 905 ), $synced );
	}

	public function test_cron_callback_reports_failure_when_the_sync_throws(): void {
		$behavior = new class( $this->createMock( MLAAPI::class ), $this->logger() ) extends GroupBehavior {
			protected function make_group( $group_id ) {
				return new class { public function sync() { throw new \Exception( 'API down' ); } };
			}
		};

		$this->assertFalse( $behavior->sync_membership_from_api( 905 ) );
	}

	// ------------------------------------------------------------- wiring

	public function test_page_view_hook_queues_and_cron_hook_syncs(): void {
		$behavior = new class( $this->createMock( MLAAPI::class ), $this->logger() ) extends GroupBehavior {
			public function hooks(): array {
				$out = array();
				foreach ( $this->actions as $a ) {
					$out[ $a['hook'] ][] = $a['callback'];
				}
				return $out;
			}
		};
		$hooks = $behavior->hooks();

		$this->assertContains( 'queue_membership_sync', $hooks['bp_before_group_body'] );
		$this->assertNotContains( 'sync_membership_from_api', $hooks['bp_before_group_body'], 'the API must not be called in the request' );
		$this->assertContains( 'sync_membership_from_api', $hooks[ GroupBehavior::SYNC_EVENT ] );
	}
}
