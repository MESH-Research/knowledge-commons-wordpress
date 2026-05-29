<?php
/**
 * Unit tests for BuddyPress member-type synchronisation.
 *
 * Exercises Plugin::kc_sync_bp_member_types_for_username() against the
 * uppercase-keyed membership payload the Profiles API actually returns.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;

class MemberTypeSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_bp_set_calls']     = [];
        $GLOBALS['_bp_remove_calls']  = [];
        $GLOBALS['_bp_current_types'] = false;
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['_bp_set_calls'],
            $GLOBALS['_bp_remove_calls'],
            $GLOBALS['_bp_current_types']
        );
        parent::tearDown();
    }

    private function user(int $id = 42): \WP_User
    {
        $u = new \WP_User($id);
        $u->ID = $id;
        return $u;
    }

    /**
     * Extract the 'type' field from a list of captured calls.
     */
    private function callTypes(array $calls): array
    {
        return array_map(static fn($c) => $c['type'], $calls);
    }

    /** @test */
    public function truthy_membership_sets_corresponding_type(): void
    {
        Plugin::kc_sync_bp_member_types_for_username($this->user(), [
            'MSU' => 1,
        ]);

        $this->assertContains('msu', $this->callTypes($GLOBALS['_bp_set_calls']));
    }

    /** @test */
    public function empty_string_membership_removes_corresponding_type(): void
    {
        Plugin::kc_sync_bp_member_types_for_username($this->user(), [
            'MLA' => '',
        ]);

        $this->assertContains(
            'mla',
            $this->callTypes($GLOBALS['_bp_remove_calls']),
            'Empty string membership value must remove the BuddyPress member type'
        );
    }

    /** @test */
    public function zero_membership_removes_corresponding_type(): void
    {
        Plugin::kc_sync_bp_member_types_for_username($this->user(), [
            'MLA' => 0,
        ]);

        $this->assertContains(
            'mla',
            $this->callTypes($GLOBALS['_bp_remove_calls']),
            'Zero membership value must remove the BuddyPress member type'
        );
    }

    /** @test */
    public function hc_is_always_set(): void
    {
        Plugin::kc_sync_bp_member_types_for_username($this->user(), []);

        $this->assertContains(
            'hc',
            $this->callTypes($GLOBALS['_bp_set_calls']),
            'hc member type must always be set regardless of payload'
        );
    }

    /** @test */
    public function hc_is_never_removed(): void
    {
        Plugin::kc_sync_bp_member_types_for_username($this->user(), [
            'HC' => 0,
        ]);

        $this->assertNotContains(
            'hc',
            $this->callTypes($GLOBALS['_bp_remove_calls']),
            'hc must never be removed'
        );
    }

    /** @test */
    public function unknown_keys_in_payload_are_ignored(): void
    {
        Plugin::kc_sync_bp_member_types_for_username($this->user(), [
            'NOTREAL' => 1,
            'STEMED+' => 1,
        ]);

        $set_types = $this->callTypes($GLOBALS['_bp_set_calls']);

        $this->assertNotContains('notreal', $set_types);
        $this->assertNotContains('stemed+', $set_types);
    }

    /** @test */
    public function realistic_profiles_response_sets_and_removes_correctly(): void
    {
        $memberships = [
            'MLA'     => '',
            'MSU'     => 1,
            'ARLISNA' => 1,
            'UP'      => '',
            'STEMED+' => '',
            'HASTAC'  => '',
        ];

        Plugin::kc_sync_bp_member_types_for_username($this->user(), $memberships);

        $set_types = $this->callTypes($GLOBALS['_bp_set_calls']);
        $removed   = $this->callTypes($GLOBALS['_bp_remove_calls']);

        // Truthy entries become member types.
        $this->assertContains('msu', $set_types);
        $this->assertContains('arlisna', $set_types);

        // Empty entries get removed.
        $this->assertContains('mla', $removed);
        $this->assertContains('up', $removed);
        $this->assertContains('hastac', $removed);

        // hc is always set, never removed.
        $this->assertContains('hc', $set_types);
        $this->assertNotContains('hc', $removed);

        // Truthy entries are not also removed.
        $this->assertNotContains('msu', $removed);
        $this->assertNotContains('arlisna', $removed);
    }

    /** @test */
    public function types_absent_from_payload_are_removed(): void
    {
        // Payload mentions only MSU; everything else (aseees, hub, sah, etc.)
        // should be removed.
        Plugin::kc_sync_bp_member_types_for_username($this->user(), [
            'MSU' => 1,
        ]);

        $removed = $this->callTypes($GLOBALS['_bp_remove_calls']);

        foreach (['arlisna', 'aseees', 'hub', 'mla', 'sah', 'socsci', 'stem', 'up', 'hastac', 'dhri'] as $type) {
            $this->assertContains(
                $type,
                $removed,
                sprintf('%s should be removed when absent from payload', $type)
            );
        }
    }
}
