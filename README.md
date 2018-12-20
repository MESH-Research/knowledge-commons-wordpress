# dev-scripts

A place for one-off or sensitive scripts used in data migration. Please organize scripts in subfolders corresponding package, plugin, or function. Add to the inventory in the README.

## CloudFormation Install

`$ pip install sceptre`

`$ git clone git@github.com:mlaa/dev-scripts.git`

`$ cd dev-scripts/aws/cloudformation`

pick your project folder: 
`$ cd hc`

`$ sceptre create-stack dev ec2`


*note:* Cloudformnation scripts are setup to work with (Sceptre)[https://sceptre.cloudreach.com/latest/docs/get_started.html]

## Inventory

 - commons/friends-to-followers.php: convert friends in `wp_bp_friends` to followers. edit `HTTP_HOST` in the code before use.

 - humcore/fix_content_mime_type.php: Fix incorrect mimeType on a fedora resource object.
 - humcore/fix_deposit_counts.php: One time data migration to rename and merge deposit download counters.
 - humcore/fix_fileloc1.php: One time data migration to rename deposit upload file locations.
 - humcore/fix_fileloc_and_size.php: Part of a 4 step process to replace a file for a given file in Fedora.
 - humcore/fix_filetype.php: Fix incorrect filetype (mimetype) in deposit post meta.
 - humcore/fix_no_doi.php: Fix DOI creation failure for an otherwise complete deposit.
 - humcore/fix_pub_types.php: One time data migration to change publication types in deposit post meta.
 - humcore/fix_tax_ids.php: One time data migration to add group, subject and keyword ID values to deposits post meta.
 - humcore/load_test_deposits.php: Add deposits from a commons production copy to a solr/fedora test system.

 - humcore/DUPLICATES.md: Notes on duplicate deposit cleanup.

 - mla-academic-interests/insert_academic_interests.php: Create sample values for the Academic Interests taxonomy.
