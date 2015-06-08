=== GoogleDrive folder list ===
Contributors: sh4d0w28
Donate link: http://goo.gl/MTVgNL
Tags: google, google docs, folders, docs, googledrive, folders, share
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

GoogleDrive folder list allows you to insert a document list from any shared folder in Google Drive without Google authentication.

== Description ==

GoogleDrive folder list allows you to insert a document list from any shared folder in google drive.

* Works only with full-shared folders in GoogleDrive
* Login to Google services is not required.
* Customized title
* 'thumbnail' mode:
 * displays thumbnail or the first page of uploaded document.
* 'list' mode:
 * displays download links for each document;
 * can go through nested folders
 * has options to adjust CSS styles from administration panel
 * easy integration in the post or page (just a shortcode)
 
== Installation ==

1. Install plugin by finding it in plugin repository from the administration panel.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Place `[gdocs fid="<sharedFolderId>" title="<titleName>"] in your post or page to print a simple list of links for documents with settings by default (root only, no thumbnails)
1. Add an option `preview=<true|false>` to print a links with / without document thumbnails.
1. Add an option `maxdepth=<depth>` to show a nested folders. This **WORKS ONLY WITH LIST MODE**. 1 means show only root folder, 2 means show root + nested folder content, 3 means second variant + second level nested and so on.

Full option example: [gdocs fid="<sharedFolderId>" title="<titleName>" preview=true maxdepth=2]

If you miss parameter, it will be used from default values or values from administration panel.

Administration panel is now under the "PLUGIN" menu.

How to setup shared folder: view "screenshots" tab.

== Screenshots ==

1. After install you`ll need to share gdocs folders. First, open your Google Drive folder.
2. Then choose folder you want to share and select "share folder"
3. You must select the "anybody-have-access" mode and copy the FID of the folder.

== Changelog ==

= 2.2.2 = 
* fix header error

= 2.2.1 = 
* Fix error for new google-drive url format

= 2.2.0 =
* add css selectors (as admin options)
* added administration panel
* add an ability to view nested folders

= 2.1.0 = 
* Fix error with incorrect function naming

= 2.0.3 = 
* Optimize CURL requests
* Fix error when using different views on one page

= 2.0 =
* Restyle shortcode output

= 1.0 =
* First version. Project started.

== Upgrade Notice ==

= 2.2.1 =
* IMPORTANT : Fix problem "Catalog is unavailable"

= 2.2.0 =
* Complex update. Update if you want more options to adjust

= 2.1.0 =
Fixed bug with incorrect function naming. Upgrade immediately! 