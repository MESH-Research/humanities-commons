=== Buddypress Messages Spam Blocker=== 
Contributors: Florian Schießl
Donate link: http://ifs-net.de/donate.php
Tags: buddypress, spam, messages
Requires at least: 3.0
Tested up to: 4.5
Stable Tag: trunk
License: GPLv2
License URI: http://www.opensource.org/licenses/GPL-2.0

This plugin will block mass mailing for the buddypress messaging system

== Description ==

If there is a user (or a bot) that signed up at your site this user can start to send messages to every other user.
I had some negative experiences with this and so I wrote a module that should help to block such a spam.

Buddypress Messages Spam Blocker introduces some restrictions to your users:

* New users can send messages only 24h after their registration, so you'll have time if bot registrations have to be removed manually (modify this value using filter 'buddypress_messages_spamblocker_newMembersWaitingPeriod')

Their are also some more restrictions for mass mailings (mails that are sent to "friends" of the contact list are not included in this calculation):

* Users can send 6 messages maximum in 5 minutes (modify this value using filter 'buddypress_messages_spamblocker_5m')
* Users can send 10 messages maximum in 10 minutes (modify this value using filter 'buddypress_messages_spamblocker_10m')
* Users can send 20 messages maximum in 30 minutes (modify this value using filter 'buddypress_messages_spamblocker_30m')
* Users can send 30 messages maximum in 60 minutes (modify this value using filter 'buddypress_messages_spamblocker_60m')
* Users can send 35 messages maximum in 12 hours (modify this value using filter 'buddypress_messages_spamblocker_12h')
* Users can send 45 messages maximum in 24 hours (modify this value using filter 'buddypress_messages_spamblocker_24h')
* Users can send 50 messages maximum in 48 hours (modify this value using filter 'buddypress_messages_spamblocker_48h')

Users with the capability "edit_users" (admins etc.) have no restrictions for outgoing messages

Install, activate, and it will work.

**More about me and my plugins**

Since the year 1999 I do administration, customizing and programming for several forums, communities and social networks. In the year 2013 I switched from another PHP framework to Wordpress.
Because not all plugins I'd like to have exist already I wrote some own plugins and I think I'll continue to do so.

If you have the scope at forums or social networks my other modules might also be interesting for you. [Just take a look at my Wordpress Profile to see all my Plugins.](http://wordpress.org/plugins/search.php?q=quan_flo "ifs-net / quan_flo Wordpress Plugins") Use them and if my work helps you to save time, earn money or just makes you happy feel free to donate - Thanks. The donation link can be found at the right sidebar next to this text.

== Installation ==

1. Upload the files to the `/wp-content/plugins/buddypress-messages-spamblocker/` directory or install through WordPress directly.
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Are there some options for the parameters or are these parameters hard coded? =

At the moment they are hardcoded, you can easily chance them inside the code as you need them.

= New members 

== Changelog ==

= 2.5 =
* Fixing PHP Fatal error:  Call to undefined function friends_get_friend_user_ids() in /wp-content/plugins/buddypress-messages-spam-blocker/buddypress-messages-spamblocker.php on line 42 => https://codex.buddypress.org/plugindev/checking-buddypress-is-active/ applied now

= 2.4 =
* Added filter for redirection url. You can uzse buddypress_messages_spamblocker_blockedURL to specify a page for redirecting a blocked user
* little code cleanup

= 2.3 =
* fixing wrong redirect url if messages slug is not "messages"

= 2.2 =
* New filter for changing waiting period for new buddypress members (buddypress_messages_spamblocker_newMembersWaitingPeriod)

= 2.1 =
* fixed little bug for different messages-module-locations (thanks to reflectgrowth)
* fixed little bug in sql statement (thanks to vizzweb)

= 2.0 =
* added filters for easy customizing of message quantity that should be able to be sent. See plugin description for details.

= 1.1 =
* users that have the capability "edit_users" have no restictions for outgoing messages

= 1.0 = 
* First version.
