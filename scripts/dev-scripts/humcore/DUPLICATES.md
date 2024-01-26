Clean up duplicated deposits

Why do entries get duplicated?

 - User hits submit twice or hits refresh
 - Error occurs in one of the related systems ( solr, fedora, ezid, wp ) and user deposits again.
 - User tries to correct metadata or change uploaded file.
 - User does not see deposit where expected and deposits again.


What gets duplicated?

 - file uploads
 - solr entries
 - fedora entries
 - ezid entries
 - custom post types and post meta
 - buddypress activity entries


Where do you go for information?

file upload
/srv/www/commons/current/web/app/uploads/humcore

There is one entry for each time the user uploads a file, so it is possible that there is one entry for two deposits. Most likely there will be two entries.

solr entries
https://macana.cul.columbia.edu:8443/solr-4.3.0/#/hum_core/query

There is one entry for each deposit.

Example query will find the latest entries where the id (pid) starts with mla:14 ( mla:141, mla:143 )
 - q = id:mla\:14*
 - sort = record_creation_date DESC
 - check the edismax query option

fedora entries
https://cdrs-fedora-dev1.cul.columbia.edu:8443/fedora/objects

There are two entries for each deposit. One for the metadata and one for the uploaded file.

Example query to search pid for values starting with mla:14

 - pid~mla:14*

EZID entries
http://ezid.cdlib.org/manage

There is one entry for each deposit.

The Manage IDs lists the latest entries in descending order. Check the ID details, if Status is reserved the ID can be deleted, if the Status is public the status must be changed to unavailable.


WP entries
https://commons.mla.org/wp-admin/

Custom post types

There are two entries for each deposit. One for the metadata and one for the uploaded file. Click the HumCORE Deposits menu item, sort the deposits by date descending.

BuddyPress activity records

There is one New Depost entry for each deposit. There is one New Group Deposit for each group on each deposit



Correcting a double submission

In this case, all metadata is the same in each deposit. The best choice is to keep the later deposit.
We need the two solr ids, the four fedora ids, the two ezid entries, the four WP custom post type entries and the BuddyPress activity records

Example:

solr id
 - mla:141
 - mla:143

fedora pid
 - mla:141
 - mla:142
 - mla:143
 - mla:144

ezid identifier
 - doi:10.17613/M6QC73
 - doi:10.17613/M6V30X

WP slug - ID
 - mla141 - 8424
 - mla142 - 8425
 - mla143 - 8426
 - mla144 - 8427

BuddyPress

Edit each activity to determine which deposit the activity represents, or use the timestamps on the activities list.

Cleanup

Currently this is a mix of command line entries on the server and WP admin transactions.

```sh
wp solr delete --pid=mla:141
```
```sh
wp fedora delete --pid=mla:141
```
```sh
wp fedora delete --pid=mla:142
```

EZID

Log into the ezid site.
Goto manage ids.
Edit the identifier, set the status to unavailable.

WP custom post types

trash 8424 and 8425

BuddyPress activities

delete the activities for mla:141


Example command line output

```sh
admin@gaddis:~/app/public$ wp solr delete --pid=mla:141
***Delete Solr Document***array (
  'responseHeader' =>
  array (
    'status' => 0,
    'QTime' => 357,
  ),
)
Success: Deleted pid : mla:141!
admin@gaddis:~/app/public$ wp fedora delete --pid=mla:141
Success: Deleted pid : mla:141!
admin@gaddis:~/app/public$ wp fedora delete --pid=mla:142
Success: Deleted pid : mla:142!
admin@gaddis:~/app/public$
```

