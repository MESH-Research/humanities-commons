<?php
header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );
echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>';
?>
<rss version="2.0"
		xmlns:content="http://purl.org/rss/1.0/modules/content/"
		xmlns:wfw="http://wellformedweb.org/CommentAPI/"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:atom="http://www.w3.org/2005/Atom"
		xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
		xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
		<?php do_action( 'rss2_ns' ); ?>>
<channel>
		<title><?php echo 'CORE Deposits'; ?> - Feed</title>
		<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
		<link><?php bloginfo_rss( 'url' ); ?></link>
		<description><?php echo 'The lastest deposits to CORE at hcommons.org'; ?></description>
		<lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>
		<language>en_us</language>
		<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
		<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
		<?php do_action( 'rss2_head' ); ?>
	<?php if ( humcore_has_deposits( '&per_page=250' ) ) : ?>
		<?php
		while ( humcore_deposits() ) :
			humcore_the_deposit();
?>
				<item>
				<?php do_action( 'humcore_deposits_feed_item_content' ); ?>
				<?php do_action( 'rss2_item' ); ?>
				</item>
		<?php endwhile; ?>
		<?php endif; ?>
</channel>
</rss>
<?php
exit();
