<?php
/**
 * Unit tests for the `wp cilogon resync-all` command orchestration.
 *
 * Exercises ResyncCommand in isolation by injecting test doubles for the
 * sync caller, user fetcher, cursor store, sleeper and logger — no DB,
 * no HTTP, no real WP-CLI involvement.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\ResyncCommand;
use PHPUnit\Framework\TestCase;

class ResyncCommandTest extends TestCase
{
    private function makeUser(int $id, ?string $login = null): \WP_User
    {
        $u = new \WP_User($id);
        $u->ID = $id;
        $u->user_login = $login === null ? 'user_' . $id : $login;
        return $u;
    }

    private function newCommand(): ResyncCommand
    {
        $cmd = new ResyncCommand();
        // No-op sleeper so tests don't actually wait.
        $cmd->set_sleeper(static function ($us) { /* no-op */ });
        // Capture logs into a global instead of stdout.
        $cmd->set_logger(static function ($level, $message) {
            $GLOBALS['_resync_log'][] = ['level' => $level, 'message' => $message];
        });
        return $cmd;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_resync_log'] = [];
        $GLOBALS['_resync_cursor_store'] = 0;
        $GLOBALS['_resync_cursor_deleted'] = false;
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['_resync_log'],
            $GLOBALS['_resync_cursor_store'],
            $GLOBALS['_resync_cursor_deleted']
        );
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // parse_options
    // ------------------------------------------------------------------

    /** @test */
    public function parse_options_returns_defaults_when_no_args(): void
    {
        $opts = ResyncCommand::parse_options([]);

        $this->assertSame(300, $opts['sleep_ms']);
        $this->assertNull($opts['start_at']);
        $this->assertSame(0, $opts['limit']);
        $this->assertSame(200, $opts['batch_size']);
        $this->assertFalse($opts['reset']);
        $this->assertFalse($opts['dry_run']);
    }

    /** @test */
    public function parse_options_reads_overrides(): void
    {
        $opts = ResyncCommand::parse_options([
            'sleep'        => '500',
            'start-at'     => '1234',
            'limit'        => '10',
            'batch-size'   => '50',
            'reset-cursor' => true,
            'dry-run'      => true,
        ]);

        $this->assertSame(500, $opts['sleep_ms']);
        $this->assertSame(1234, $opts['start_at']);
        $this->assertSame(10, $opts['limit']);
        $this->assertSame(50, $opts['batch_size']);
        $this->assertTrue($opts['reset']);
        $this->assertTrue($opts['dry_run']);
    }

    /** @test */
    public function parse_options_clamps_negative_values(): void
    {
        $opts = ResyncCommand::parse_options([
            'sleep'      => '-1',
            'start-at'   => '-5',
            'limit'      => '-10',
            'batch-size' => '0',
        ]);

        $this->assertSame(0, $opts['sleep_ms']);
        $this->assertSame(0, $opts['start_at']);
        $this->assertSame(0, $opts['limit']);
        $this->assertSame(1, $opts['batch_size'], 'batch-size must be at least 1');
    }

    // ------------------------------------------------------------------
    // run loop — sync caller invocation
    // ------------------------------------------------------------------

    /** @test */
    public function run_invokes_sync_caller_for_each_user(): void
    {
        $called_with = [];

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) use (&$called_with) {
            $called_with[] = $login;
        });
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1, 'alice'), $this->makeUser(2, 'bob')],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) { /* no-op */ });
        $cmd->set_cursor_resetter(static function () { /* no-op */ });

        $result = $cmd->run([]);

        $this->assertSame(['alice', 'bob'], $called_with);
        $this->assertSame(2, $result['synced']);
        $this->assertSame(0, $result['errors']);
        $this->assertSame(2, $result['last_user_id']);
    }

    /** @test */
    public function run_skips_users_with_empty_user_login(): void
    {
        $called_with = [];

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) use (&$called_with) {
            $called_with[] = $login;
        });
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1, ''), $this->makeUser(2, 'bob')],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $result = $cmd->run([]);

        $this->assertSame(['bob'], $called_with);
        $this->assertSame(1, $result['synced']);
        $this->assertSame(1, $result['skipped']);
    }

    /** @test */
    public function run_continues_past_sync_caller_errors(): void
    {
        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) {
            if ($login === 'bob') {
                throw new \RuntimeException('boom');
            }
        });
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1, 'alice'), $this->makeUser(2, 'bob'), $this->makeUser(3, 'carol')],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $result = $cmd->run([]);

        $this->assertSame(2, $result['synced']);
        $this->assertSame(1, $result['errors']);
        $this->assertSame(3, $result['last_user_id']);
    }

    // ------------------------------------------------------------------
    // cursor handling
    // ------------------------------------------------------------------

    /** @test */
    public function run_advances_cursor_after_each_user(): void
    {
        $cursor_writes = [];

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) {});
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1), $this->makeUser(2), $this->makeUser(3)],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) use (&$cursor_writes) {
            $cursor_writes[] = $id;
        });
        $cmd->set_cursor_resetter(static function () {});

        $cmd->run([]);

        $this->assertSame([1, 2, 3], $cursor_writes);
    }

    /** @test */
    public function run_resumes_from_stored_cursor_when_no_start_at_arg(): void
    {
        $fetched_after = null;

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) {});
        $cmd->set_user_fetcher(function ($after_id, $batch_size) use (&$fetched_after) {
            if ($fetched_after === null) {
                $fetched_after = $after_id;
            }
            return [];
        });
        $cmd->set_cursor_getter(static fn() => 9999);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $cmd->run([]);

        $this->assertSame(9999, $fetched_after);
    }

    /** @test */
    public function run_uses_start_at_arg_in_preference_to_stored_cursor(): void
    {
        $fetched_after = null;

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) {});
        $cmd->set_user_fetcher(function ($after_id, $batch_size) use (&$fetched_after) {
            if ($fetched_after === null) {
                $fetched_after = $after_id;
            }
            return [];
        });
        $cmd->set_cursor_getter(static fn() => 9999);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $cmd->run(['start-at' => '500']);

        $this->assertSame(500, $fetched_after);
    }

    /** @test */
    public function run_clears_cursor_when_reset_flag_passed(): void
    {
        $reset_called = false;

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) {});
        $cmd->set_user_fetcher($this->staticBatcher([[]]));
        $cmd->set_cursor_getter(static fn() => 9999);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () use (&$reset_called) {
            $reset_called = true;
        });

        $cmd->run(['reset-cursor' => true]);

        $this->assertTrue($reset_called);
    }

    // ------------------------------------------------------------------
    // limit, sleep, dry-run, termination
    // ------------------------------------------------------------------

    /** @test */
    public function run_stops_after_limit_users_synced(): void
    {
        $called_with = [];

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) use (&$called_with) {
            $called_with[] = $login;
        });
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1), $this->makeUser(2), $this->makeUser(3), $this->makeUser(4)],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $result = $cmd->run(['limit' => '2']);

        $this->assertCount(2, $called_with);
        $this->assertSame(2, $result['synced']);
        $this->assertSame(2, $result['last_user_id']);
    }

    /** @test */
    public function run_sleeps_between_users_in_microseconds(): void
    {
        $sleep_calls = [];

        $cmd = new ResyncCommand();
        $cmd->set_logger(static function ($l, $m) {});
        $cmd->set_sleeper(static function ($us) use (&$sleep_calls) {
            $sleep_calls[] = $us;
        });
        $cmd->set_sync_caller(static function ($login) {});
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1), $this->makeUser(2)],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $cmd->run(['sleep' => '250']);

        $this->assertSame([250000, 250000], $sleep_calls);
    }

    /** @test */
    public function run_does_not_sleep_when_sleep_is_zero(): void
    {
        $sleep_calls = [];

        $cmd = new ResyncCommand();
        $cmd->set_logger(static function ($l, $m) {});
        $cmd->set_sleeper(static function ($us) use (&$sleep_calls) {
            $sleep_calls[] = $us;
        });
        $cmd->set_sync_caller(static function ($login) {});
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1)],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $cmd->run(['sleep' => '0']);

        $this->assertSame([], $sleep_calls);
    }

    /** @test */
    public function run_dry_run_does_not_invoke_sync_caller(): void
    {
        $called = false;

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) use (&$called) {
            $called = true;
        });
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1), $this->makeUser(2)],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $result = $cmd->run(['dry-run' => true]);

        $this->assertFalse($called);
        $this->assertSame(2, $result['synced']);
    }

    /** @test */
    public function run_stops_when_user_fetcher_returns_empty_batch(): void
    {
        $batches_requested = 0;

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) {});
        $cmd->set_user_fetcher(function ($after_id, $batch_size) use (&$batches_requested) {
            $batches_requested++;
            if ($batches_requested === 1) {
                return [$this->makeUser(1)];
            }
            return [];
        });
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $cmd->run([]);

        $this->assertSame(2, $batches_requested);
    }

    /** @test */
    public function run_pages_across_multiple_batches(): void
    {
        $called_with = [];

        $cmd = $this->newCommand();
        $cmd->set_sync_caller(static function ($login) use (&$called_with) {
            $called_with[] = $login;
        });
        $cmd->set_user_fetcher($this->staticBatcher([
            [$this->makeUser(1, 'a'), $this->makeUser(2, 'b')],
            [$this->makeUser(3, 'c'), $this->makeUser(4, 'd')],
            [],
        ]));
        $cmd->set_cursor_getter(static fn() => 0);
        $cmd->set_cursor_setter(static function ($id) {});
        $cmd->set_cursor_resetter(static function () {});

        $cmd->run([]);

        $this->assertSame(['a', 'b', 'c', 'd'], $called_with);
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    /**
     * Returns a fetcher that hands out the supplied batches in order, then
     * an empty batch forever after.
     *
     * @param array<array<\WP_User>> $batches
     */
    private function staticBatcher(array $batches): callable
    {
        $i = 0;
        return static function ($after_id, $batch_size) use (&$i, $batches) {
            if ($i >= count($batches)) {
                return [];
            }
            return $batches[$i++];
        };
    }
}
