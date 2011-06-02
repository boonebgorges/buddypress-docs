=== BuddyPress Docs  ===
Contributors: boonebgorges, cuny-academic-commons
Donate link: http://teleogistic.net/donate
Tags: buddypress, docs, wiki, documents, collaboration
Requires at least: WordPress 3.1, BuddyPress 1.3
Tested up to: WordPress 3.2 beta, BuddyPress 1.3
Stable tag: 1.1.1
 
Adds collaborative Docs to BuddyPress.

== Description ==

BuddyPress Docs adds collaborative work spaces to your BuddyPress community. Part wiki, part document editing, think of these Docs as a BuddyPress version of the Docs service offered by the Big G *ifyouknowwhatimean*

Features include:

* Group-specific Docs
* Doc taxonomy, using tags
* Fully sortable and filterable doc lists
* TinyMCE front-end doc editing
* One-editor-at-a-time prevention against overwrites, plus idle detection/autosave
* Dashboard access and management of Docs for the site admin

<strong>NOTE</strong> This plugin <em>REQUIRES</em> WordPress 3.1 or higher. Lower versions of WP may appear to work, but <em>will</em> compromise the privacy of your Docs.

This plugin is in active development. For feature requests and bug reports, visit http://github.com/boonebgorges/buddypress-docs. If you have translated the plugin and would like to provide your translation for distribution with BuddyPress Docs, please contact the plugin author.

== Installation ==

1. Install
1. Activate
1. For each group where you want Docs activated, visit Group Admin > Docs and check the checkbox
1. Sit back and watch the jack roll in

== Changelog ==

= 1.1.2 =
* Fixes markup error that prevented h2 tag from being closed on New Doc screen

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
