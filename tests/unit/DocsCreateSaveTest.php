<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/docs-create-save-loader.php';

/**
 * Saving a brand-new doc from /docs/create must not be treated as an edit of
 * some unrelated existing doc.
 *
 * On the create screen the main query is the bp_doc post-type archive, so at
 * save time buddypress-docs' bp_docs_get_current_doc() falls through to the
 * global $post — the first doc of the archive listing — and then demands a
 * per-doc edit nonce that the create form never contained, killing the save
 * with "The link you followed has expired." The hc-custom filters on
 * 'bp_docs_get_current_doc' must therefore resolve to no current doc for a
 * create save, while leaving renders and genuine edit saves untouched.
 *
 * These tests exercise the full filter chain hc-custom registers on
 * 'bp_docs_get_current_doc', exactly as bp_docs_get_current_doc() applies it.
 */
class DocsCreateSaveTest extends TestCase {

	/**
	 * Stand-in for the accidental "current doc": the first doc of the
	 * archive query that ends up in the global $post at save time.
	 *
	 * @var object
	 */
	private $accidental_doc;

	protected function setUp(): void {
		hc_test_reset_state();
		$_POST = array();

		$this->accidental_doc = (object) array(
			'ID'        => 42,
			'post_type' => 'bp_doc',
		);
	}

	protected function tearDown(): void {
		$_POST = array();

		// hc_test_reset_state() sets _mock_current_group_id, which shadows
		// the _hc_mock store other suites drive bp_get_current_group_id()
		// with; drop it (and this suite's stores) so no state leaks.
		unset( $GLOBALS['_mock_current_group_id'] );
		$GLOBALS['_hc_mock'] = array();
		$GLOBALS['hc_test']  = array();
	}

	/**
	 * Run a doc through the hc-custom 'bp_docs_get_current_doc' filter
	 * chain, as bp_docs_get_current_doc() would.
	 *
	 * @param object|null $doc Doc detected by buddypress-docs.
	 * @return object|null The doc after filtering.
	 */
	private function currentDocAfterFilters( $doc ) {
		return _test_apply_captured_filters( 'bp_docs_get_current_doc', $doc );
	}

	/**
	 * A "Save" POST for a new doc (the create form posts doc_id="0") must
	 * resolve to no current doc, even though the archive query has left an
	 * unrelated doc in the globals.
	 */
	public function testCreateSaveResolvesToNoCurrentDoc(): void {
		$_POST['doc-edit-submit'] = 'Save';
		$_POST['doc_id']          = '0';

		$this->assertNull( $this->currentDocAfterFilters( $this->accidental_doc ) );
	}

	/**
	 * The same holds for "Save and Continue Editing", including when the
	 * doc_id field is missing entirely.
	 */
	public function testCreateSaveAndContinueResolvesToNoCurrentDoc(): void {
		$_POST['doc-edit-submit-continue'] = 'Save and Continue Editing';

		$this->assertNull( $this->currentDocAfterFilters( $this->accidental_doc ) );
	}

	/**
	 * A create save that already resolved to no current doc stays that way.
	 */
	public function testCreateSaveWithNoDetectedDocStaysEmpty(): void {
		$_POST['doc-edit-submit'] = 'Save';
		$_POST['doc_id']          = '';

		$this->assertNull( $this->currentDocAfterFilters( null ) );
	}

	/**
	 * Ordinary page renders (no save POST) must keep whatever doc
	 * buddypress-docs detected — single doc views depend on it.
	 */
	public function testRenderRequestKeepsDetectedDoc(): void {
		$this->assertSame(
			$this->accidental_doc,
			$this->currentDocAfterFilters( $this->accidental_doc )
		);
	}

	/**
	 * A genuine edit save (non-empty doc_id) must resolve to the posted
	 * doc, not the accidental one from the globals.
	 */
	public function testEditSaveResolvesToThePostedDoc(): void {
		$edited_doc = (object) array(
			'ID'        => 123,
			'post_type' => 'bp_doc',
		);

		$GLOBALS['_hc_mock']['posts']           = array( 123 => $edited_doc );
		$GLOBALS['hc_test']['doc_groups'][123]  = 7;

		$_POST['doc-edit-submit'] = 'Save';
		$_POST['doc_id']          = '123';

		$this->assertSame(
			$edited_doc,
			$this->currentDocAfterFilters( $this->accidental_doc )
		);
	}

	/**
	 * An edit save of a doc with no associated group still resolves to no
	 * current doc (pre-existing hc-custom behaviour, must be preserved).
	 */
	public function testEditSaveOfGroupUnassociatedDocResolvesToNoCurrentDoc(): void {
		$edited_doc = (object) array(
			'ID'        => 123,
			'post_type' => 'bp_doc',
		);

		$GLOBALS['_hc_mock']['posts']           = array( 123 => $edited_doc );
		$GLOBALS['hc_test']['doc_groups'][123]  = 0;

		$_POST['doc-edit-submit'] = 'Save';
		$_POST['doc_id']          = '123';

		$this->assertNull( $this->currentDocAfterFilters( $this->accidental_doc ) );
	}
}
