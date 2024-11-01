=== Simple User Locking ===
Plugin Name: Simple User Locking
Contributors: blackbam
Tags: authentication, user, locking, control, security, moderation, administrator, network, multisite
Stable tag: 1.0.1
Requires at least: 5.0
Tested up to: 5.2.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Prevent users (like e.g. ex-employees, rule breakers or spamers) from logging into your WordPress installation for a certain timeframe or permanently without deleting them. With multisite support.


== Description ==

Prevent users from logging into your WordPress installation for a certain timeframe or permanently. Works also great with the multisite user management area.

The locked users are easily manageable within the users overview page. The settings are within the user edit pages.

No user can lock himself. No user with a lower role can lock a higher user and administrators in a network can not lock super administrators.

If a user is locked, he is instantly logged out of any session until the lock expires or is removed.

No useless overhead, no ads. Just a tiny, but very effective plugin to keep your website secure.

Use cases:

*   you do not want to delete a user, but you want to make sure he can not access the site (at least for a certain timeframe)
*   an employee leaves your company and access should be removed, but you want to keep his user as an author in the system
*   you want to punish a certain user which did bad things for a certain timeframe
*   you want only few persons to have access to your WordPress site in order to minimize risk of incidents
*   ... or maybe some other use case

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to a users profile you want to lock (also possible in network admin) and adjust the settings accordingly.

== Frequently Asked Questions ==

Currently none.

== Screenshots ==

1. Lock settings in the user edit screen
2. User overview
3. Trying to login with a locked account

== Changelog ==

= 1.0 =
* Initial version

== Feature requests ==

Currently none.