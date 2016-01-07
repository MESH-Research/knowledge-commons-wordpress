# dev-scripts

A place for one-off or sensitive scripts used in data migration. Please organize scripts in subfolders corresponding package, plugin, or function. Add to the inventory in the README.

## Inventory

commons/friends-to-followers.php: convert friends in `wp_bp_friends` to followers. edit `HTTP_HOST` in the code before use.

humcore/fix_content_mime_type.php: Fix incorrect mimeType on a fedora resource object.
humcore/fix_deposit_counts.php: One time data migration to rename and merge deposit download counters.
humcore/fix_fileloc1.php: One time data migration to rename deposit upload file locations.
humcore/fix_filetype.php: Fix incorrect filetype (mimetype) in deposit post meta.
humcore/fix_no_doi.php: Fix DOI creation failure for an otherwise complete deposit.
humcore/fix_tax_ids.php: One time data migration to add group, subject and keyword ID values to deposits post meta.
humcore/load_test_deposits.php: Add deposits from a commons production copy to a solr/fedora test system.

