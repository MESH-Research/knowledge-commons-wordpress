# Incident Report: Group Pages Crashing for Logged-In Users

**Date:** 2026-08-27
**Affected page(s):** https://stemedplus.org/stemed-groups/dber-alliance-primary-discussion-listserv/ (and potentially any group activity page meeting the trigger conditions below)
**Impact:** Fatal PHP error (white screen / 500) for logged-in users viewing the group page. Logged-out visitors were unaffected.
**Status:** Fixed. Regression tests added.

---

## Plain-English Summary (for non-technical readers)

Logged-in members visiting the DBER Alliance group page were getting a crashed, broken page, while visitors who weren't logged in saw the page normally. That asymmetry made it look like a login or permissions problem — it wasn't.

The real story: when someone creates a calendar event in a group, our events plugin quietly stores *two* copies of the "so-and-so created an event" story — one general copy and one private copy that belongs to the group. Members of the group are allowed to see both copies; the general public sees only one. To avoid showing members the same story twice, the plugin runs a de-duplication routine — but *only* for people who can see the duplicates, i.e. logged-in members. Buried in that routine was a typo, roughly ten years old, from the plugin's original authors: a closing bracket placed one word too early in a single line of code. In older versions of the programming language PHP, that typo was silently tolerated. The newer version we now run treats it as a fatal error.

So three things had to line up to crash the page: (1) someone recently posted or edited an event in that group, putting a duplicate pair of stories near the top of the group's activity feed; (2) the viewer had to be logged in, so the de-duplication routine actually ran; and (3) our modern PHP version, which turns the old typo into a hard stop. That's why nothing we changed this month caused it — including the Elementor forms change, which is unrelated — and why it appeared "out of nowhere": it was a landmine armed years ago, and a routine bit of member activity finally stepped on it.

The fix was ultimately moving that one bracket to where it was always meant to be. Finding which bracket, out of a codebase of millions of lines, meant tracing the crash backwards through seven layers of WordPress and BuddyPress plumbing, reproducing the failure in an isolated test harness, and proving the repair with automated tests that now guard against it ever coming back. While in there, we also silenced a separate long-standing fault in our own customization code that was spraying warnings into the logs on every group page view, muddying diagnosis.

---

## Technical Report

### Symptoms

- Logged-in requests to the group page fatally errored:

  ```
  PHP Fatal error: Uncaught TypeError: count(): Argument #1 ($value) must be
  of type Countable|array, bool given in
  /app/plugins/bp-event-organiser/includes/activity.php:332
  ```

- Preceded in the logs by an unrelated-looking warning:

  ```
  PHP Warning: Attempt to read property "id" on null in
  /app/plugins/hc-custom/includes/buddypress/bp-activity.php on line 25
  ```

- Logged-out requests rendered normally.
- No code deployments in the preceding month other than disabling Elementor Pro Forms (confirmed unrelated — nothing in either affected code path touches Elementor or Ninja Forms).

### Root cause

`plugins/bp-event-organiser/includes/activity.php:332`, inside
`bpeo_remove_duplicates_from_activity_stream()` (hooked on `bp_activity_get`):

```php
// Buggy — counts the boolean result of (array > int), not the array:
if ( count( $activity['activities'] > $original_activity_count ) ) {

// Fixed:
if ( count( $activity['activities'] ) > $original_activity_count ) {
```

A misplaced closing parenthesis passes the *comparison result* (a boolean) to
`count()`. On PHP 7.x, `count(bool)` emitted a warning and returned 1, so the
line silently "worked" (the subsequent `array_slice` to the original page size
is harmless). On PHP 8+, `count()` on a non-countable throws a `TypeError`,
which is fatal. The bug is upstream in origin — this line dates back to the
plugin's original CAC development (present since the plugin was vendored into
this repo) — and lay dormant until the runtime and the data aligned.

### Why only logged-in users, and why now

`bp-event-organiser` records event activity twice: a canonical item
(`component = 'events'`, `activity.php:90-100`) and a per-group copy created
with `hide_sitewide = true` (`group.php:389`). BuddyPress excludes
`hide_sitewide` items from queries unless the viewer can see hidden items —
which group members can. Therefore:

- **Logged out:** the stream contains at most one copy per event → no
  duplicates → `$removed === 0` → the backfill branch containing line 332
  never executes → page renders.
- **Logged in (member):** both copies of the same event appear in the group
  stream → the dedup pass removes one → `$removed > 0` → the backfill branch
  runs → line 332 executes → fatal on PHP 8.

The trigger was therefore **data, not code**: recent event activity in the
DBER Alliance group placed a duplicate pair within the first page of the
group's activity stream. Any group in this state will crash for its members;
the same latent bug exists on every site running this plugin under PHP 8.

### Secondary fault (log noise, not the crash)

`plugins/hc-custom/includes/buddypress/bp-activity.php:25` referenced `$group`,
a variable that was never defined in `hc_custom_template_part_filter()`:

```php
$forum_ids = bbp_get_group_forum_ids( $group->id );
```

This ran on every group page for logged-in users (the activity post form is
only located for viewers who can post), emitting two warnings per view. It
was accidentally functional: bbPress's `bbp_get_group_forum_ids()` falls back
to `bp_get_current_group_id()` when passed an empty value, so the intended
behaviour (hide the activity post form in groups that have a forum) still
occurred. Fixed by passing `bp_get_current_group_id()` explicitly and guarding
the call with `function_exists()` for installs where bbPress group forums are
unavailable. Behaviour is unchanged; the warnings are gone.

### Diagnosis path

1. Read the stack trace bottom-up: group page → activity template loop →
   `bp_activity_get()` → `bp_activity_get` filter →
   `bpeo_remove_duplicates_from_activity_stream()`.
2. Inspected line 332 and spotted the parenthesis placement; confirmed PHP 8
   `count()` semantics account for the exact `TypeError` in the trace.
3. Established the logged-in/logged-out asymmetry via the `hide_sitewide`
   duplicate-creation logic in `group.php` — explaining why the dedup branch
   (and thus the bug) is unreachable for anonymous traffic.
4. Confirmed via git history that neither file had changed since the plugins
   were vendored — ruling out recent deploys (including the Elementor change)
   and pointing to a data-side trigger.
5. Reproduced both faults in the isolated PHPUnit harness (red), applied the
   fixes, re-ran to green.

### Fix

| File | Change |
| --- | --- |
| `plugins/bp-event-organiser/includes/activity.php:332` | Moved the misplaced closing parenthesis so `count()` receives the activities array. |
| `plugins/hc-custom/includes/buddypress/bp-activity.php:21-27` | Replaced undefined `$group->id` with `bp_get_current_group_id()`; added a `function_exists()` guard around `bbp_get_group_forum_ids()`; removed an unused `buddypress()` assignment. |

### Regression guards

New self-contained unit tests (no WordPress load; stub-based, matching the
existing `tests/unit` harness), written red-first against the buggy code —
they reproduced the exact production fatal and warnings — and passing after
the fix:

- `tests/unit/BpeoActivityDedupTest.php` — exercises the dedup path end to
  end: untouched streams pass through; duplicate event items are removed
  keeping the canonical `events`-component copy; backfilled streams are
  trimmed to the originally requested page size (the code path that fataled).
- `tests/unit/HcCustomActivityFormTest.php` — the activity post form is
  hidden in groups with a forum, shown otherwise, with warnings promoted to
  test failures.
- Loaders: `tests/unit/bpeo-activity-loader.php`,
  `tests/unit/hc-bp-activity-loader.php`; shared stubs added to
  `tests/unit/bootstrap.php`.

Full suite: 34 tests, 58 assertions, green under PHP 8.4.

### Follow-ups (recommended, not done in this hotfix)

- Audit vendored legacy plugins for other PHP 8 incompatibilities of the same
  class (`count()` on possibly-non-countable, undefined variables) — a static
  pass with PHPStan/Psalm at a low level would surface these cheaply.
- Consider upstreaming the `bp-event-organiser` fix if the plugin is still
  maintained anywhere.
