# ElasticPress BuddyPress

This plugin provides a custom feature for [ElasticPress](https://github.com/10up/ElasticPress) which adds index & query support for BuddyPress groups & members.

Built for [Humanities Commons](https://hcommons.org).

# Initial setup:

Required plugins:

    buddypress
    bbpress
    elasticpress
    elasticpress-buddypress

__BuddyPress content types depend on the mapping for posts provided by ElasticPress, so you must set up that mapping before indexing groups and members.__ You can do that by indexing posts with the `--setup` flag (which will also delete the index first!):

    wp --url=example.com elasticpress index --setup

where `example.com` is your main site/network.

Index buddypress content:

    wp --url=example.com elasticpress-buddypress index

There are no hooks yet to re-index buddypress content. Run it on a regular basis to keep the index up-to-date.
