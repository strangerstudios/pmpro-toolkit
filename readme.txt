=== Paid Memberships Pro - Developer's Toolkit Add On ===
Contributors: strangerstudios, jessica o
Tags: paid memberships pro, pmpro, debug, developer, toolkit
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1

Adds various tools and settings to aid in the development of Paid Memberships Pro enabled websites.

== Description ==

Features:

* Redirect all PMPro emails to a specific email address.
* Define payment gateway debug constants easily in one place.
* Enable a Checkout Debug Email every time the Checkout Page is hit.
* Disable PMPro's scheduled cron jobs.
* Enable a "View as" feature allowing admins to view any page as a specific membership level or levels.

== Installation ==

1. Upload the `pmpro-toolkit` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-toolkit/issues

== Changelog ==
= 1.1 - 2025-09-22 =
* FEATURE: Enable performance testing endpoints to test Paid Memberships Pro key functionality. #57 (@dalemugford)
* FEATURE: Enable scripts as CLI commands: Run Toolkit scripts in the WP CLI. #57 (@dalemugford)
* ENHANCEMENT: Use batch processing to improve performance of member data scrubbing. #52 (@dalemugford)
* ENHANCEMENT: Remove test subscriptions when clearing out test orders. #53 (@andrewlimaza)
* ENHANCEMENT: Adds conditional support for Action Scheduler handling #52 (@dalemugford)
* ENHANCEMENT: Improved deletion of test orders to include order meta and subscription meta. #62 (@andrewlimaza)
* ENHANCEMENT: Added support for generated checkout data for Mailing Address Add On and the Member Directory Maps Fields and other general improvements. #54,#55 (@dparker1005,@kimwhite)
* BUG FIX: Tweak wording for delete member script to include subscription tables. #46 (@MaximilianoRicoTabo)
* REFACTOR: Renamed "PMPro Toolkit" to "Toolkit" in the admin menu bar option. #60 (@andrewlimaza)

= 1.0 - 2024-10-23 =
* SECURITY: Added nonce checks to the admin forms. #36 (@MaximilianoRicoTabo)
* FEATURE: Added an option to allow automatically generating test user data at checkout. #44 (@MaximilianoRicoTabo)
* FEATURE: Added support for importing and exporting user fields. #30 (@andrewlimaza)
* FEATURE: Added a script to delete old token, pending, and review orders. #33 (@dparker1005)
* FEATURE: Added a script to delete test orders. #40 (@MaximilianoRicoTabo)
* FEATURE: Added a script to clear cached report data. #39 (@MaximilianoRicoTabo)
* ENHANCEMENT: Moved the Toolkit admin pages to the Memberships menu and updated the UI. #36 (@MaximilianoRicoTabo)
* BUG FIX/ENHANCEMENT: Updated clean up scripts to account for tables added in PMPro v3.0. #41 (@MaximilianoRicoTabo)
* BUG FIX: Fixed a PHP warning when sending the chekcout debug email if the `password2` field is not collected at checkout. #45 (@MaximilianoRicoTabo) 
* DEPRECATED: Removed the "View As" feature as this is now included in the core Paid Memberships Pro plugin. #37 (@MaximilianoRicoTabo)

= 0.8.1 - 2023-04-08 =
* BUG FIX: Fixed issue where the checkout debug feature interfered with redirects.

= 0.8 - 2023-03-30 =
* SECURITY: Now stripping password data from the checkout debug email. Even though this is a prerelease plugin and typically run on dev/staging sites, if you were running the debug email feature on a live site, we strongly encourage you to find those emails and delete them from your email.
* FEATURE: Added a "when" option for the checkout debug emails. You can now set these emails to happen on all checkout page loads, only submissions, or only when an error is thrown.
* ENHANCEMENT: Now running the checkout debug email code later so we can include any success/warning/error message that was shown in the email.
* ENHANCEMENT: Adjusted the layout and wording of the checkout debug email.
* BUG FIX: Fixed issue where the "was submitted" section of the checkout debug email was showing incorrect values.

= 0.7 - 2022-06-02 =
* SECURITY: Improved data sanitization and escaping in various areas of the plugin.
* ENHANCEMENT: Added Import and Export functionality. Data to import or export between sites are as follows: membership levels, Email Templates, Payment & SSL Settings and Advanced Settings.

= 0.6.1 - 2021-12-27
* BUG FIX/ENHANCEMENT: Added third $cancel_level_id parameter to the pmpro_after_change_membership_level action.

= 0.6 - 2020-08-06 =
* FEATURE: Added script to clear Visits, Views, and Logins report.
* ENHANCEMENT: Added menu to navigate between settings and scripts pages.
* BUG FIX/ENHANCEMENT: "Cancel all users with level" script now works with MMPU.
* BUG FIX: Fixed issue where start date could not be entered for "Give all non-members level" script.
* BUG FIX: Fixed issue where checkout debug email may not be sent to correct recipient.

= 0.5.2 =
* BUG FIX: Fixed the Cancel All Members script. (Thanks, Jessica Thomas)
* BUG FIX: Fixed issue on the scripts page where clicking on text inputs would check/uncheck the cooresponding checkbox. (Thanks, Jessica Thomas)
* ENHANCEMENT: Bit of code cleanup.

= 0.5.1 =
* ENHANCEMENT: Ready for translation.
* ENHANCEMENT: Added Spanish Translation.
* ENHANCEMENT: WordPress Coding Standards.
* ENHANCEMENT: Updated plugin name and links for consistency.

= 0.5 =
* FEATURE: Added a script to give non-members a level.
* BUG FIX: No longer trying to delete the memberships_users table twice. (Thanks, bhdd on GitHub)
* BUG FIX: Fixed issue where only one entry on the usermeta table was deleted for each user. (Thanks, bhdd on GitHub)

= 0.4 =
* Added script to change members from one level to another.
* Added script to cancel all members of one level.
* Added script to copy page restrictions from one level to another.

= 0.3 =
* Added scripts to delete data from the database. (Use at your own risk!)

= 0.2.1 =
* Fixed issue where pmprodev_view_as_has_membership_level() was making members seem like they don't have a membership level.

= 0.2 =
* Added "Scheduled Cron Job Debugging" section to disable cron jobs.
* Now only have one Gateway Callback Email setting "ipn_debug" that is used for all gateways.
* Moved sections around a bit.

= 0.1.2 =
* Removed some warnings/notices.
* Added settings page.
* "View as" feature now filtering pmpro_hasMembershipLevel() function as well.

= 0.1.1 =
* Added "View as" access filter. Lets admins view any page as a specific membership level. Add "?pmprodev_view_as=3-2-1" to the query string.

= 0.1 =
* This is the initial version of the plugin.