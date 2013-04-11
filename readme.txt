=== BuddyPress Docs  ===
Contributors: boonebgorges, cuny-academic-commons
Donate link: http://teleogistic.net/donate
Tags: buddypress, docs, wiki, documents, collaboration
Requires at least: WordPress 3.3, BuddyPress 1.5
Tested up to: WordPress 3.5.1, BuddyPress 1.7
Stable tag: 1.3.3
 
Adds collaborative Docs to BuddyPress.

== Description ==

BuddyPress Docs adds collaborative work spaces to your BuddyPress community. Part wiki, part document editing, think of these Docs as a BuddyPress version of the Docs service offered by the Big G *ifyouknowwhatimean*

Features include:

* Docs that can be linked to groups or users, with a variety of privacy levels
* Doc taxonomy, using tags
* Fully sortable and filterable doc lists
* TinyMCE front-end doc editing
* One-editor-at-a-time prevention against overwrites, plus idle detection/autosave
* Full access to revision history
* Dashboard access and management of Docs for the site admin

<strong>NOTE</strong> This plugin <em>REQUIRES</em> WordPress 3.1 or higher. Lower versions of WP may appear to work, but <em>will</em> compromise the privacy of your Docs.

This plugin is in active development. For feature requests and bug reports, visit http://github.com/boonebgorges/buddypress-docs. If you have translated the plugin and would like to provide your translation for distribution with BuddyPress Docs, please contact the plugin author.

== Installation ==

1. Install
1. Activate
1. For each group where you want Docs activated, visit Group Admin > Docs and check the checkbox
1. Sit back and watch the jack roll in

== Changelog ==

= 1.3.3 =
* Fixed bug that incorrectly approved some post comments

= 1.3.2 =
* Fixed bug with tab permalinks on some setups
* Fixed bug in the way parent Doc is pre-selected on Edit screen dropdown

= 1.3.1 =
* Fixed issues with Doc creation when groups are disabled
* Fixed several bugs occurring when group association was changed or deleted
* Updated translations: Danish, Spanish

= 1.3 =
* Adds theme compatibility layer, for better formatting with all themes
* Full compatibility with BuddyPress 1.7
* Don't show permissions snapshot to non-logged-in users
* Adds Docs link to My Account toolbar menu
* Delete Doc activity when deleting Doc
* Delete local Doc tags when deleting Doc from any location
* Improved markup for Create New Docs button
* Don't show History quicklink on directories when revisions are disabled

= 1.2.10 =
* Improved compatibility with BP Group Hierarchy
* Fixes for global directory pagination

= 1.2.9 =
* Improved access protection, for better compatibility with bbPress 2.x and other plugins
* Updated Russian translation

= 1.2.8 =
* Fixes problem with group associations and privacy levels of new docs
* Improves access protection in WP searches and elsewhere
* Sets hide_sitewide more carefully when posting Doc activity items
* Prevents some errors related to wp_check_post_lock()
* Adds Russian translation

= 1.2.7 =
* Updates German translation
* Fixes rewrite problem when using custom BP_DOCS_SLUG
* Fixes fatal error when upgrading BuddyPress

= 1.2.6 =
* Updates Danish translation
* Fixes infinite loop bug in upgrader
* Fixes html entity problem in permalinks

= 1.2.5 =
* Fixes comment posting
* Fixes comment display and posting permissions
* Don't show Tags: label when no tags are present

= 1.2.4 =
* Updates .pot file
* Updates German translation
* l18n improvements
* Ensures that doc links are trailingslashed
* Fixes bug that prevented front-end doc deletion
* Removes temporarily non-functional doc counts from group tabs

= 1.2.3 = 
* Fixes bug with bp-pages

= 1.2.2 =
* Improves group-association auto-settings when creating via the Create New Doc link in a group
* Fixes bug that erroneously required a directory page

= 1.2.1 =
* Fixes bug with overzealous Create New Doc button
* Fixes some PHP warnings

= 1.2 =
* Major plugin rewrite
* Moves Docs out of groups, making URLs cleaner, interface simpler, and making it possible to have Docs not linked to any group
* Adds a sitewide Docs directory

= 1.1.25 =
* Fixes bug in Javascript that may have caused secondary editor rows not to
  show in some cases
* Fixes bug that broke comment moderation in some cases

= 1.1.24 =
* Moves Table buttons to third row of editor, for better fit on all themes
* Adds Danish translation

= 1.1.23 =
* Adds Delete links to doc actions row
* Fixes an invalid markup issue in a template file

= 1.1.22 =
* Added Romanian translation

= 1.1.21 =
* Show the 'author' panel in the Dashboard

= 1.1.20 =
* Fixes idle timeout javascript
* Fixes bug with timezones on History tab
* Improves data passed to filters
* Cleans up references to WP's fullscreen editing mode
* Fixes potential PHP warnings on the Dashboard

= 1.1.19 =
* Improved WP 3.3 support
* Ensure that groups' can-delete setting defaults to 'member' when not present, to account for legacy groups
* Moved to groups_get_group() for greater efficiency under BP 1.6
* Fixed bug that redirected users to wp-admin when comparing a revision to itself

= 1.1.18 =
* Adds filters to allow site admins and plugin authors to force-enable Docs at group creation, or to remove the Docs step from the group creation process

= 1.1.17 =
* Forced BP Docs activity items to respect bp-disable-blogforum-comments in BP 1.5+
* Added Portuguese translation (pt_PT)

= 1.1.16 =
* Fixed bug that caused comments to be posted to the incorrect blog when using parent and child Docs

= 1.1.15 =
* Fixed bug that allowed doc content to be loaded by slug in the incorrect group
* Limit wikitext linking to docs in the same group
* Fixed bug that prevented group admins from creating a Doc when minimum role was set to Moderators
* Disables buggy fullscreen word count for the moment

= 1.1.14 =
* Fixed bug that prevented users from editing docs when no default settings were provided

= 1.1.13 =
* Switches default setting during group creation so that Docs are enabled
* Adds a filter to default group settings so that plugin authors can modify

= 1.1.12 =
* Adds wiki-like bracket linking
* Improves distraction-free editing JS
* Updates tabindent plugin for better browser support

= 1.1.11 =
* Replaces deprecated function calls
* Internationalizes some missing gettext calls
* Adds an error message when a non-existent Doc is requested

= 1.1.10 =
* Fixes bug that made BP Docs break WP commenting on some setups

= 1.1.9 =
* Closes code tag on Edit page.

= 1.1.8 =
* Filters get_post_permalink() so that Doc permalinks in the Admin point to the proper place
* Ensures that a group's last activity is updated when a Doc is created, edited, or deleted
* Modifies Recent Comments dashboard widget in order to prevent non-allowed people from seeing certain Doc comments
* Adds Print button to TinyMCE
* Adds Brazilian Portuguese localization.

= 1.1.7 =
* Fixes Tab name bug in 1.1.6 that may cause tab to disappear

= 1.1.6 =
* Rolls back group-specific Tab names and puts it in Dashboard > BuddyPress > Settings

= 1.1.5 =
* Better redirect handling using bp_core_no_access(), when available
* Added TinyMCE table plugin
* Added admin field for customizing group tab name
* Added UI for changing the slug of an existing Doc
* Security enhancement regarding comment posting in hidden/private groups
* Fixed issue that may have prevented some users from viewing History tab on some setups
* Clarified force-cancel edit lock interface
* Introduces bp_docs_is_docs_enabled_for_group() for easy checks
* French translation added
* Swedish translation added

= 1.1.4 =
* Make the page title prettier and more descriptive
* Don't show History section if WP_POST_REVISIONS are disabled
* Fixes activity throttle for private and hidden groups
* Fixes PHP warning related to read_comments permissions
* Adds German translation

= 1.1.3 =
* Fixes potential PHP notices related to hide_sitewide activity posting

= 1.1.2 =
* Fixes bug related to group privacy settings and doc comments
* Enables WP 3.2 distraction-free editing. Props Stas
* Fixes markup error that prevented h2 tag from being closed on New Doc screen
* Fixes problems with directory separators on some setups

= 1.1.1 =
* Updated textdomains and pot file for new strings

= 1.1 =
* 'History' tab added, giving full access to a Doc's revision history
* UI improvements to make tabs more wiki-like (Read, Edit, History)
* Fixed bug that caused an error message to appear when saving unchanged settings in the group admin

= 1.0.8 =
* Limited access to custom post type on the Dashboard to admins
* Added group Doc count to group tab
* Added Italian translation - Props Luca Camellini

= 1.0.7 =
* Fixes bug that prevented blog comments from being posted to the activity stream
* Fixes incorrect textdomain in some strings

= 1.0.6 =
* Fixes bug from previous release that prevented certain templates from loading correctly

= 1.0.5 =
* Abstracts out the comment format callback for use with non-bp-default themes
* Fixes bug that prevented some templates from being overridden by child themes
* Fixes bug that limited the number of docs visible in the Parent dropdown

= 1.0.4 =
* Adds controls to allow group admins to limit Doc creation based on group role
* Better performance on MS (plugin is not loaded on non-root-blogs by default)
* Fixes TinyMCE link button in WP 3.1.x by removing wplink internal linking plugin in Docs context

= 1.0.3 =
* Switches Delete to trash action rather than a true delete
* Removes More button from TinyMCE in Docs context
* Fixes bug that allowed doc comments to show up in activity streams incorrectly
* Adds Spanish translation

= 1.0.2 =
* Adds logic for loading textdomain and translations

= 1.0.1 =
* Fixes bug that prevented Doc delete button from working
* Adds POT file for translators
* Re-fixes problem with JS editor that might cause error message on save in some setups

= 1.0 =
* UI improvements on doc meta sliders
* Doc children are now listed in single doc view
* Improved support for TinyMCE loading on custom themes
* More consistent tab highlighting in group subnav
* Fixed bug that prevented reverting to the "no parent" setting
* Improvements in the logic of doc comment display
* Improvements in the way that activity posts respect privacy settings of groups

= 1.0-beta-2 =
* Added pagination for doc list view
* Improvements to the simultaneous edit lock mechanism
* Streamlining of Doc Edit CSS to fit better with custom themes
* Improvements to the way that docs tags are handled on the back end

= 1.0-beta =
* Initial public release

== Upgrade Notice ==

= 1.2 =
* Major plugin rewrite. See http://dev.commons.gc.cuny.edu/2012/11/15/buddypress-docs-1-2/ for more details.


