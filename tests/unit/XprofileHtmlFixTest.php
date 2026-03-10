<?php

use PHPUnit\Framework\TestCase;

class XprofileHtmlFixTest extends TestCase {

	/**
	 * Clean HTML passes through unchanged.
	 */
	public function testCleanHtmlUnchanged(): void {
		$input = '<a href="https://example.org" target="_blank" rel="nofollow">link</a>';
		$this->assertSame( $input, hcommons_fix_corrupted_xprofile_html( $input ) );
	}

	/**
	 * Empty/non-HTML values pass through unchanged.
	 */
	public function testEmptyValuePassesThrough(): void {
		$this->assertSame( '', hcommons_fix_corrupted_xprofile_html( '' ) );
		$this->assertNull( hcommons_fix_corrupted_xprofile_html( null ) );
		$this->assertSame( 'plain text', hcommons_fix_corrupted_xprofile_html( 'plain text' ) );
	}

	/**
	 * Curly double quotes inside HTML tags are straightened to ASCII quotes.
	 */
	public function testCurlyDoubleQuotesInTagsStraightened(): void {
		$input  = '<a href=' . "\u{201C}" . 'https://example.org' . "\u{201D}" . '>link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringNotContainsString( "\u{201C}", $result );
		$this->assertStringNotContainsString( "\u{201D}", $result );
		$this->assertStringContainsString( 'href="https://example.org"', $result );
	}

	/**
	 * Curly single quotes inside HTML tags are straightened.
	 */
	public function testCurlySingleQuotesInTagsStraightened(): void {
		$input  = "<a data-x=\u{2018}hello\u{2019}>text</a>";
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringNotContainsString( "\u{2018}", $result );
		$this->assertStringNotContainsString( "\u{2019}", $result );
	}

	/**
	 * Curly quotes in prose text (outside HTML tags) are preserved.
	 */
	public function testCurlyQuotesInProsePreserved(): void {
		$input  = "She said \u{201C}hello\u{201D} and <a href=\"https://example.org\">clicked</a>.";
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( "\u{201C}hello\u{201D}", $result );
	}

	/**
	 * Backslash-quotes from wp_slash are stripped.
	 */
	public function testBackslashQuotesStripped(): void {
		$input  = '<a href=\"https://example.org\" target=\"_blank\">link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringNotContainsString( '\"', $result );
		$this->assertStringContainsString( 'href="https://example.org"', $result );
	}

	/**
	 * Symmetric doubled quotes: href=""url"" → href="url"
	 */
	public function testDoubledStraightQuotesFixed(): void {
		$input  = '<a href=""https://example.org"">link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( 'href="https://example.org"', $result );
	}

	/**
	 * Asymmetric extra trailing quote: href="url"" → href="url"
	 * This pattern comes from wp_kses_hair misparse of backslash-quoted data.
	 */
	public function testExtraTrailingQuoteFixed(): void {
		$input  = '<a href="https://example.org"" target="_blank" rel="nofollow">link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( 'href="https://example.org"', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
	}

	/**
	 * Asymmetric extra leading quote: rel=""noreferrer nofollow" → rel="noreferrer nofollow"
	 */
	public function testExtraLeadingQuoteFixed(): void {
		$input  = '<a href="https://example.org" rel=""noreferrer nofollow">link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( 'rel="noreferrer nofollow"', $result );
	}

	/**
	 * Protocol-relative hrefs get https: restored.
	 */
	public function testProtocolRelativeHrefsFixed(): void {
		$input  = '<a href="//example.org/path">link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( 'href="https://example.org/path"', $result );
	}

	/**
	 * Duplicate rel attribute values are deduplicated.
	 */
	public function testRelValuesDeduplicated(): void {
		$input  = '<a href="https://example.org" rel="nofollow noopener nofollow">link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( 'rel="nofollow noopener"', $result );
	}

	/**
	 * Mixed curly+straight doubled quotes are fixed (curly straightened first,
	 * then wp_kses_hair parses the resulting malformed attributes).
	 */
	public function testDoubledMixedCurlyStraightQuotesFixed(): void {
		$input  = '<a href="' . "\u{201C}" . 'https://example.org' . "\u{201D}" . '">link</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( 'href="https://example.org"', $result );
	}

	/**
	 * Full corruption pattern from the actual bug report.
	 */
	public function testRealWorldCorruptionPattern(): void {
		$url = 'https://textshopexperiments.org/textshop06/looking-back-to-sound-in-as-memory-and-place';

		// The actual corruption pattern the user reported:
		// extra trailing " on href, extra leading " on rel
		$input = '<a title="' . $url . '" href="' . $url . '"" target="_blank" rel=""noreferrer nofollow">' . $url . '</a>';

		$result = hcommons_fix_corrupted_xprofile_html( $input );

		$this->assertStringContainsString( 'href="' . $url . '"', $result );
		$this->assertStringContainsString( 'title="' . $url . '"', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
		$this->assertStringContainsString( 'rel="noreferrer nofollow"', $result );

		// No doubled quotes anywhere in the result
		$this->assertStringNotContainsString( '""', $result );
	}

	/**
	 * Full corruption pattern with curly quotes and protocol stripping.
	 */
	public function testFullCurlyQuoteCorruption(): void {
		$input = '<a title="' . "\u{201C}\u{201D}" . 'https://example.org/path' . "\u{201D}\u{201D}" . '"'
			   . ' href="' . "\u{201C}\u{201C}" . '//example.org/path' . "\u{201D}\u{201D}" . '"'
			   . ' rel="nofollow noopener nofollow">link</a>';

		$result = hcommons_fix_corrupted_xprofile_html( $input );

		$this->assertStringContainsString( 'href="https://example.org/path"', $result );
		$this->assertStringNotContainsString( "\u{201C}", $result );
		$this->assertStringNotContainsString( "\u{201D}", $result );
		$this->assertStringContainsString( 'rel="nofollow noopener"', $result );
	}

	/**
	 * Non-<a> tags are not reconstructed (only curly-quote and backslash fixes apply).
	 */
	public function testNonAnchorTagsPreserved(): void {
		$input  = '<p>Hello</p><strong>world</strong>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertSame( $input, $result );
	}

	/**
	 * Multiple <a> tags in the same value are all fixed.
	 */
	public function testMultipleAnchorTagsFixed(): void {
		$input  = '<a href=""https://a.example.org"">A</a> and <a href=""https://b.example.org"">B</a>';
		$result = hcommons_fix_corrupted_xprofile_html( $input );
		$this->assertStringContainsString( 'href="https://a.example.org"', $result );
		$this->assertStringContainsString( 'href="https://b.example.org"', $result );
	}
}
