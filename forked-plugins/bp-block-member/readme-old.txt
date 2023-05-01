=== BuddyBlock ===
Contributors: philopress.com
Author URI: http://philopress.com/contact/
Plugin URI: http://philopress.com/products/
Requires at least: WP 3.5, BP 1.7
Tested up to: WP 4.2, BP 2.2.3.1
Stable tag: 1.4
License: GPLv2 
Copyright (C) 2013-2015  shanebp, PhiloPress  

== Description == 
BuddyBlock is a BuddyPress plugin. 

See admin page under Settings -> BuddyBlock

Creates a 'Block' / 'UnBlock' button on profile pages of other members, next to the 'Private Message' button.
Creates a 'Block' / 'UnBlock' button on member loops and group member loops.
Create a profile screen for each member, under Settings > Blocked Members, showing all the members they have blocked.

If you Block another member, you will not appear to that member on, so far: 
1. Members Page
2. Activity Page
3. Group Members Page

Member Count is NOT adjusted on #1 or #3

If a blocked member tries to access your profile page, they will be redirected to your site home url. 

If a blocked member tries to send you a private message or reply, they will see a custom error.




== Installation ==

1. Unzip and then upload the 'bp-block-member' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Note:
Activating this plugin will create a new table. 
Deactivation will _not_ remove the table.
Deleting this plugin will remove the table. 


CSS:
On themes where the Block button requires more space, you can copy the example
css ruleset from the folder /css/buddy-block.css to your themes stylesheet to add vertical spacing.


== Changelog ==

= 1.4 =
* close the recent XSS vulnerability found in add_query_arg and remove_query_arg

== Upgrade Notice ==

= 1.4 =
* close the recent XSS vulnerability found in add_query_arg and remove_query_arg
