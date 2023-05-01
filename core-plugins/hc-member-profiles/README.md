# Academic Member Profiles for BuddyPress

[![Build Status](https://travis-ci.org/mlaa/hc-member-profiles.svg)](https://travis-ci.org/mlaa/hc-member-profiles)

Inspired by (and not compatible with) [CAC Advanced Profiles](https://github.com/cuny-academic-commons/cac-advanced-profiles).

Built for [Humanities Commons](https://hcommons.org).


## Required Dependencies

[BuddyPress](https://buddypress.org) `xprofile` and `members` components must be enabled.

The XProfile fields listed in the `HC_Members_Profile_Component` class must be created manually. See `_hcmp_create_xprofile_fields()`.

## Optional Dependencies

BuddyPress components `activity`, `blogs`, and `groups` enable their respective fields.

[BuddyPress Follow](https://wordpress.org/plugins/buddypress-followers) enables displaying follower count on profiles. (Humanities Commons uses [BuddyBlock](http://www.philopress.com/products/buddyblock) to complement BuddyPress Follow but it changes nothing about how this plugin works.)

[MLA Academic Interests](https://github.com/mlaa/mla-academic-interests) enables the "Academic Interests" field.

[HumCORE](https://github.com/mlaa/humcore) enables the "CORE Deposits" field.
