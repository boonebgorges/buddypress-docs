=== BuddyPress Docs  ===
Contributors: boonebgorges, cuny-academic-commons
Donate link: http://teleogistic.net/donate
Tags: buddypress, docs, wiki, documents, collaboration
Requires at least: WordPress 3.1, BuddyPress 1.5
Tested up to: WordPress 3.3-bleeding, BuddyPress 1.5
Stable tag: 1.1.19
 
Adds collaborative Docs to BuddyPress.

== Description ==

BuddyPress Docs adds collaborative work spaces to your BuddyPress community. Part wiki, part document editing, think of these Docs as a BuddyPress version of the Docs service offered by the Big G *ifyouknowwhatimean*

Features include:

* Group-specific Docs
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
