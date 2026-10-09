<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/hc-styles-group-member-count-loader.php';

/**
 * The groups directory member count must render for every group, including
 * those whose members-page URL contains percent-encoded characters (non-Latin
 * slugs). Previously the URL was spliced into the sprintf() format string, so
 * each "%xx" became a conversion specifier and the directory page fatalled.
 */
class HcStylesGroupMemberCountTest extends TestCase {

	public function testPercentEncodedUrlDoesNotThrowAndIsPreservedVerbatim(): void {
		$url = 'https://hcommons.org/groups/%e4%b8%ad%e6%96%87%e5%b0%8f%e7%bb%84/members/';

		$html = hc_styles_group_member_count_html( 5, $url );

		$this->assertStringContainsString( 'href="' . $url . '"', $html );
		$this->assertStringContainsString( '>5<', $html );
	}

	public function testSingleMemberHasNoLink(): void {
		$html = hc_styles_group_member_count_html( 1, 'https://hcommons.org/groups/solo/members/' );

		$this->assertStringNotContainsString( '<a ', $html );
		$this->assertStringContainsString( '>1<', $html );
		$this->assertStringContainsString( 'member', $html );
		$this->assertStringNotContainsString( 'members', $html );
	}

	public function testMultipleMembersLinkToMembersPage(): void {
		$url  = 'https://hcommons.org/groups/some-group/members/';
		$html = hc_styles_group_member_count_html( 12, $url );

		$this->assertMatchesRegularExpression( '#<a href="' . preg_quote( $url, '#' ) . '"[\s>]#', $html );
		$this->assertStringContainsString( '>12<', $html );
		$this->assertStringContainsString( 'members', $html );
		$this->assertStringContainsString( '</a>', $html );
	}

	public function testZeroMembersUsesPluralForm(): void {
		$html = hc_styles_group_member_count_html( 0, 'https://hcommons.org/groups/empty/members/' );

		$this->assertStringContainsString( '>0<', $html );
		$this->assertStringContainsString( 'members', $html );
	}

	public function testCountIsCastToInteger(): void {
		$html = hc_styles_group_member_count_html( '7', 'https://hcommons.org/groups/g/members/' );

		$this->assertStringContainsString( '>7<', $html );
	}

	public function testLinkOpeningTagIsWellFormed(): void {
		$html = hc_styles_group_member_count_html( 3, 'https://hcommons.org/groups/g/members/' );

		// The <a> must be closed with ">" before the span starts: <a href="..."><span
		$this->assertStringContainsString( '"><span', $html );
	}
}
