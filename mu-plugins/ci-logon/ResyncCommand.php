<?php
/**
 * `wp cilogon resync-all` command.
 *
 * Iterates the WordPress users table in ID order and calls
 * Plugin::sync_user() against the remote Profiles API for each user,
 * with configurable pacing and a persisted cursor for resume.
 *
 * @package MeshResearch\CILogon
 */

namespace MeshResearch\CILogon;

class ResyncCommand
{
    public const CURSOR_OPTION       = 'cilogon_resync_cursor';
    public const DEFAULT_SLEEP_MS    = 300;
    public const DEFAULT_BATCH_SIZE  = 200;

    /** @var callable */
    private $sync_caller;
    /** @var callable */
    private $user_fetcher;
    /** @var callable */
    private $cursor_getter;
    /** @var callable */
    private $cursor_setter;
    /** @var callable */
    private $cursor_resetter;
    /** @var callable */
    private $logger;
    /** @var callable */
    private $sleeper;

    public function __construct()
    {
        $this->sync_caller     = [Plugin::class, 'sync_user'];
        $this->user_fetcher    = [self::class, 'default_user_fetcher'];
        $this->cursor_getter   = [self::class, 'default_cursor_getter'];
        $this->cursor_setter   = [self::class, 'default_cursor_setter'];
        $this->cursor_resetter = [self::class, 'default_cursor_resetter'];
        $this->logger          = [self::class, 'default_logger'];
        $this->sleeper         = 'usleep';
    }

    public function set_sync_caller(callable $c): void     { $this->sync_caller = $c; }
    public function set_user_fetcher(callable $c): void    { $this->user_fetcher = $c; }
    public function set_cursor_getter(callable $c): void   { $this->cursor_getter = $c; }
    public function set_cursor_setter(callable $c): void   { $this->cursor_setter = $c; }
    public function set_cursor_resetter(callable $c): void { $this->cursor_resetter = $c; }
    public function set_logger(callable $c): void          { $this->logger = $c; }
    public function set_sleeper(callable $c): void         { $this->sleeper = $c; }

    public static function parse_options(array $assoc_args): array
    {
        return [
            'sleep_ms'   => isset($assoc_args['sleep'])
                ? max(0, (int) $assoc_args['sleep'])
                : self::DEFAULT_SLEEP_MS,
            'start_at'   => isset($assoc_args['start-at'])
                ? max(0, (int) $assoc_args['start-at'])
                : null,
            'limit'      => isset($assoc_args['limit'])
                ? max(0, (int) $assoc_args['limit'])
                : 0,
            'batch_size' => isset($assoc_args['batch-size'])
                ? max(1, (int) $assoc_args['batch-size'])
                : self::DEFAULT_BATCH_SIZE,
            'reset'      => ! empty($assoc_args['reset-cursor']),
            'dry_run'    => ! empty($assoc_args['dry-run']),
        ];
    }

    /**
     * @return array{synced:int,skipped:int,errors:int,last_user_id:int}
     */
    public function run(array $assoc_args): array
    {
        $opts = self::parse_options($assoc_args);

        if ($opts['reset']) {
            call_user_func($this->cursor_resetter);
            $this->log('info', 'Resync cursor cleared.');
        }

        $cursor = $opts['start_at'] !== null
            ? $opts['start_at']
            : (int) call_user_func($this->cursor_getter);

        $this->log('info', sprintf(
            'Starting resync from user_id > %d (sleep=%dms, batch=%d, limit=%s, dry-run=%s)',
            $cursor,
            $opts['sleep_ms'],
            $opts['batch_size'],
            $opts['limit'] ?: 'none',
            $opts['dry_run'] ? 'yes' : 'no'
        ));

        $synced       = 0;
        $skipped      = 0;
        $errors       = 0;
        $last_user_id = $cursor;

        while (true) {
            $batch = call_user_func($this->user_fetcher, $cursor, $opts['batch_size']);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $user) {
                $cursor       = (int) $user->ID;
                $last_user_id = $cursor;

                if (empty($user->user_login)) {
                    $skipped++;
                    $this->log('warning', sprintf('Skipped user_id=%d (no user_login)', $user->ID));
                    call_user_func($this->cursor_setter, $cursor);
                    continue;
                }

                if ($opts['dry_run']) {
                    $this->log('info', sprintf(
                        '[dry-run] would sync user_id=%d user_login=%s',
                        $user->ID,
                        $user->user_login
                    ));
                    $synced++;
                } else {
                    try {
                        call_user_func($this->sync_caller, $user->user_login);
                        $synced++;
                        $this->log('info', sprintf(
                            'Synced user_id=%d user_login=%s',
                            $user->ID,
                            $user->user_login
                        ));
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->log('warning', sprintf(
                            'Sync FAILED user_id=%d user_login=%s: %s',
                            $user->ID,
                            $user->user_login,
                            $e->getMessage()
                        ));
                    }
                }

                call_user_func($this->cursor_setter, $cursor);

                if ($opts['limit'] > 0 && $synced >= $opts['limit']) {
                    $this->log('info', sprintf('Reached limit of %d, stopping.', $opts['limit']));
                    break 2;
                }

                if ($opts['sleep_ms'] > 0) {
                    call_user_func($this->sleeper, $opts['sleep_ms'] * 1000);
                }
            }
        }

        $this->log('info', sprintf(
            'Done. synced=%d skipped=%d errors=%d last_user_id=%d',
            $synced,
            $skipped,
            $errors,
            $last_user_id
        ));

        return [
            'synced'       => $synced,
            'skipped'      => $skipped,
            'errors'       => $errors,
            'last_user_id' => $last_user_id,
        ];
    }

    private function log(string $level, string $message): void
    {
        call_user_func($this->logger, $level, $message);
    }

    /**
     * Default user fetcher — keyset-paginated query over wp_users by ID.
     *
     * @return \WP_User[]
     */
    public static function default_user_fetcher(int $after_id, int $batch_size): array
    {
        global $wpdb;

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE ID > %d ORDER BY ID ASC LIMIT %d",
            $after_id,
            $batch_size
        ));

        $users = [];
        foreach ($ids as $id) {
            $user = get_user_by('id', (int) $id);
            if ($user) {
                $users[] = $user;
            }
        }
        return $users;
    }

    public static function default_cursor_getter(): int
    {
        return (int) get_option(self::CURSOR_OPTION, 0);
    }

    public static function default_cursor_setter(int $id): void
    {
        update_option(self::CURSOR_OPTION, $id, false);
    }

    public static function default_cursor_resetter(): void
    {
        delete_option(self::CURSOR_OPTION);
    }

    public static function default_logger(string $level, string $message): void
    {
        if (class_exists('\WP_CLI')) {
            if ($level === 'warning') {
                \WP_CLI::warning($message);
            } else {
                \WP_CLI::log($message);
            }
            return;
        }
        error_log('CILogon resync [' . $level . ']: ' . $message);
    }
}
