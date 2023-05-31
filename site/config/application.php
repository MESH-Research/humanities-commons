<?php
/**
 * Your base production configuration goes in this file. Environment-specific
 * overrides go in their respective config/environments/{{WP_ENV}}.php file.
 *
 * A good default policy is to deviate from the production config as little as
 * possible. Try to define as much of your configuration in this file as you
 * can.
 */

use Roots\WPConfig\Config;
use function Env\env;

/**
 * Directory containing all of the site's files
 *
 * @var string
 */
$root_dir = dirname(__DIR__);

/**
 * Document Root
 *
 * @var string
 */
$webroot_dir = $root_dir . '/web';

/**
 * Use Dotenv to set required environment variables and load .env file in root
 * .env.local will override .env if it exists
 */
if (file_exists($root_dir . '/.env')) {
    $env_files = file_exists($root_dir . '/.env.local')
        ? ['.env', '.env.local']
        : ['.env'];

    $dotenv = Dotenv\Dotenv::createUnsafeImmutable($root_dir, $env_files, false);

    $dotenv->load();

    $dotenv->required(['WP_HOME', 'WP_SITEURL']);
    if (!env('DATABASE_URL')) {
        $dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD']);
    }
}

/**
 * Set up our global environment constant and load its config first
 * Default: production
 */
define('WP_ENV', env('WP_ENV') ?: 'production');

/**
 * Infer WP_ENVIRONMENT_TYPE based on WP_ENV
 */
if (!env('WP_ENVIRONMENT_TYPE') && in_array(WP_ENV, ['production', 'staging', 'development'])) {
    Config::define('WP_ENVIRONMENT_TYPE', WP_ENV);
}

/**
 * URLs
 */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/**
 * Custom Content Directory
 */
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

/**
 * DB settings
 */
if (env('DB_SSL')) {
    Config::define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
}

Config::define('DB_NAME', env('DB_NAME'));
Config::define('DB_USER', env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', '');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

if (env('DATABASE_URL')) {
    $dsn = (object) parse_url(env('DATABASE_URL'));

    Config::define('DB_NAME', substr($dsn->path, 1));
    Config::define('DB_USER', $dsn->user);
    Config::define('DB_PASSWORD', isset($dsn->pass) ? $dsn->pass : null);
    Config::define('DB_HOST', isset($dsn->port) ? "{$dsn->host}:{$dsn->port}" : $dsn->host);
}

/**
 * Authentication Unique Keys and Salts
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/**
 * Custom Settings
 */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('DISABLE_WP_CRON', env('DISABLE_WP_CRON') ?: false);

// Disable the plugin and theme file editor in the admin
Config::define('DISALLOW_FILE_EDIT', true);

// Disable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', true);

// Limit the number of post revisions
Config::define('WP_POST_REVISIONS', env('WP_POST_REVISIONS') ?? true);

/**
 * Debugging Settings
 */
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('WP_DEBUG_LOG', false);
Config::define('SCRIPT_DEBUG', false);
ini_set('display_errors', '0');

/**
 * Allow WordPress to detect HTTPS when used behind a reverse proxy or a load balancer
 * See https://codex.wordpress.org/Function_Reference/is_ssl#Notes
 */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

/**
 * Multisite
 */
Config::define('WP_ALLOW_MULTISITE', true);
Config::define('MULTISITE', true);
Config::define('SUBDOMAIN_INSTALL', true);


Config::define('SUNRISE', 'on');
Config::define('PLUGINDIR', 'app/plugins');

// all paths should be on the root to avoid cookies which are duplicates aside from path
Config::define( 'COOKIEPATH', '/' );
Config::define( 'ADMIN_COOKIE_PATH', '/' );
Config::define( 'SITECOOKIEPATH',    '/' );

Config::define('PATH_CURRENT_SITE', '/');

Config::define('PRIMARY_NETWORK_ID', 1);

/**
 * Redirect nonexistent blogs
 */
Config::define('NOBLOGREDIRECT', getenv('WP_HOME'));

/**
 * Akismet
 */
Config::define('WPCOM_API_KEY', getenv('WPCOM_API_KEY'));

/**
 * Logging
 */
Config::define('WP_LOGS_DIR', getenv('WP_LOGS_DIR'));

// W3 Total Cache
Config::define( 'WP_CACHE', getenv( 'WP_CACHE' ) );

/**
 * Redis
 */
Config::define('WP_CACHE_KEY_SALT', getenv('WP_CACHE_KEY_SALT'));

/**
 * Redis cache
 */
Config::define('REDIS_HOST', getenv('REDIS_HOST'));       // wp-redis
Config::define('WP_REDIS_HOST', getenv('WP_REDIS_HOST')); // redis-cache

/**
 * ElasticPress Elasticsearch
 */
Config::define('EP_HOST', getenv('EP_HOST'));

/**
 * Humanities Commons
 */
Config::define('HC_SITE_ID', getenv('HC_SITE_ID'));
Config::define('HC_SITE_URL', getenv('HC_SITE_URL'));
Config::define('AJS_SITE_URL', getenv('AJS_SITE_URL'));
Config::define('ARLISNA_SITE_URL', getenv('ARLISNA_SITE_URL'));
Config::define('ASEEES_SITE_URL', getenv('ASEEES_SITE_URL'));
Config::define('CAA_SITE_URL', getenv('CAA_SITE_URL'));
Config::define('MLA_SITE_URL', getenv('MLA_SITE_URL'));
Config::define('MSU_SITE_URL', getenv('MSU_SITE_URL'));
Config::define('SAH_SITE_URL', getenv('SAH_SITE_URL'));
Config::define('UP_SITE_URL', getenv('UP_SITE_URL'));
Config::define('REGISTRY_SERVER_URL', getenv('REGISTRY_SERVER_URL'));
Config::define('SATOSA_SERVER_URL', getenv('SATOSA_SERVER_URL'));
Config::define('HC_ENROLLMENT_URL', getenv('HC_ENROLLMENT_URL'));
Config::define('AJS_ENROLLMENT_URL', getenv('AJS_ENROLLMENT_URL'));
Config::define('ARLISNA_ENROLLMENT_URL', getenv('ARLISNA_ENROLLMENT_URL'));
Config::define('ASEEES_ENROLLMENT_URL', getenv('ASEEES_ENROLLMENT_URL'));
Config::define('CAA_ENROLLMENT_URL', getenv('CAA_ENROLLMENT_URL'));
Config::define('MLA_ENROLLMENT_URL', getenv('MLA_ENROLLMENT_URL'));
Config::define('MSU_ENROLLMENT_URL', getenv('MSU_ENROLLMENT_URL'));
Config::define('SAH_ENROLLMENT_URL', getenv('SAH_ENROLLMENT_URL'));
Config::define('UP_ENROLLMENT_URL', getenv('UP_ENROLLMENT_URL'));
Config::define('HC_ACCOUNT_LINK_URL', getenv('HC_ACCOUNT_LINK_URL'));
Config::define('AJS_ACCOUNT_LINK_URL', getenv('AJS_ACCOUNT_LINK_URL'));
Config::define('ARLISNA_ACCOUNT_LINK_URL', getenv('ARLISNA_ACCOUNT_LINK_URL'));
Config::define('ASEEES_ACCOUNT_LINK_URL', getenv('ASEEES_ACCOUNT_LINK_URL'));
Config::define('CAA_ACCOUNT_LINK_URL', getenv('CAA_ACCOUNT_LINK_URL'));
Config::define('MLA_ACCOUNT_LINK_URL', getenv('MLA_ACCOUNT_LINK_URL'));
Config::define('MSU_ACCOUNT_LINK_URL', getenv('MSU_ACCOUNT_LINK_URL'));
Config::define('SAH_ACCOUNT_LINK_URL', getenv('SAH_ACCOUNT_LINK_URL'));
Config::define('UP_ACCOUNT_LINK_URL', getenv('UP_ACCOUNT_LINK_URL'));
Config::define('HC_ORCID_USER_ACCOUNT_LINK_URL', getenv('HC_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('AJS_ORCID_USER_ACCOUNT_LINK_URL', getenv('AJS_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('ARLISNA_ORCID_USER_ACCOUNT_LINK_URL', getenv('ARLISNA_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('ASEEES_ORCID_USER_ACCOUNT_LINK_URL', getenv('ASEEES_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('CAA_ORCID_USER_ACCOUNT_LINK_URL', getenv('CAA_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('MLA_ORCID_USER_ACCOUNT_LINK_URL', getenv('MLA_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('MSU_ORCID_USER_ACCOUNT_LINK_URL', getenv('MSU_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('SAH_ORCID_USER_ACCOUNT_LINK_URL', getenv('SAH_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('UP_ORCID_USER_ACCOUNT_LINK_URL', getenv('UP_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('GOOGLE_IDENTITY_PROVIDER', getenv('GOOGLE_IDENTITY_PROVIDER'));
Config::define('TWITTER_IDENTITY_PROVIDER', getenv('TWITTER_IDENTITY_PROVIDER'));
Config::define('HC_IDENTITY_PROVIDER', getenv('HC_IDENTITY_PROVIDER'));
Config::define('MLA_IDENTITY_PROVIDER', getenv('MLA_IDENTITY_PROVIDER'));
Config::define('GOOGLE_LOGIN_METHOD_SCOPE', getenv('GOOGLE_LOGIN_METHOD_SCOPE'));
Config::define('TWITTER_LOGIN_METHOD_SCOPE', getenv('TWITTER_LOGIN_METHOD_SCOPE'));
Config::define('HC_LOGIN_METHOD_SCOPE', getenv('HC_LOGIN_METHOD_SCOPE'));
Config::define('MLA_LOGIN_METHOD_SCOPE', getenv('MLA_LOGIN_METHOD_SCOPE'));
Config::define('HC_ROOT_BLOG_ID', getenv('HC_ROOT_BLOG_ID'));
Config::define('AJS_ROOT_BLOG_ID', getenv('AJS_ROOT_BLOG_ID'));
Config::define('ARLISNA_ROOT_BLOG_ID', getenv('ARLISNA_ROOT_BLOG_ID'));
Config::define('ASEEES_ROOT_BLOG_ID', getenv('ASEEES_ROOT_BLOG_ID'));
Config::define('CAA_ROOT_BLOG_ID', getenv('CAA_ROOT_BLOG_ID'));
Config::define('MLA_ROOT_BLOG_ID', getenv('MLA_ROOT_BLOG_ID'));
Config::define('MSU_ROOT_BLOG_ID', getenv('MSU_ROOT_BLOG_ID'));
Config::define('SAH_ROOT_BLOG_ID', getenv('SAH_ROOT_BLOG_ID'));
Config::define('UP_ROOT_BLOG_ID', getenv('UP_ROOT_BLOG_ID'));
Config::define('GLOBAL_SUPER_ADMINS', getenv('GLOBAL_SUPER_ADMINS'));
Config::define('GOOGLE_IDP_URL', getenv('GOOGLE_IDP_URL'));
Config::define('TWITTER_IDP_URL', getenv('TWITTER_IDP_URL'));
Config::define('MLA_IDP_URL', getenv('MLA_IDP_URL'));
Config::define('HC_IDP_URL', getenv('HC_IDP_URL'));
Config::define('REGISTRY_SP_URL', getenv('REGISTRY_SP_URL'));
Config::define('HASTAC_SITE_URL', getenv('HASTAC_SITE_URL'));
Config::define('HASTAC_ENROLLMENT_URL', getenv('HASTAC_ENROLLMENT_URL'));
Config::define('HASTAC_ACCOUNT_LINK_URL', getenv('HASTAC_ACCOUNT_LINK_URL'));
Config::define('HASTAC_ORCID_USER_ACCOUNT_LINK_URL', getenv('HASTAC_ORCID_USER_ACCOUNT_LINK_URL'));
Config::define('HASTAC_ROOT_BLOG_ID', getenv('HASTAC_ROOT_BLOG_ID'));

/**
 * COMANAGE API
 */
Config::define('COMANAGE_API_URL', getenv( 'COMANAGE_API_URL' ));
Config::define('COMANAGE_API_USERNAME', getenv( 'COMANAGE_API_USERNAME' ));
Config::define('COMANAGE_API_PASSWORD', getenv( 'COMANAGE_API_PASSWORD' ));

/**
 * MLA Member API
 */
Config::define('CBOX_AUTH_API_URL', getenv('CBOX_AUTH_API_URL'));
Config::define('CBOX_AUTH_API_KEY', getenv('CBOX_AUTH_API_KEY'));
Config::define('CBOX_AUTH_API_SECRET', getenv('CBOX_AUTH_API_SECRET'));

/**
 * SMTP settings
 */
Config::define('GLOBAL_SMTP_FROM', getenv('GLOBAL_SMTP_FROM'));

/**
 * CBOX plugin management
 */
Config::define('CBOX_OVERRIDE_PLUGINS', true); // help debug setup

/**
 * Plugin Monitor
 */
Config::define( 'PLUGIN_MONITOR_ALERT_EMAILS', getenv( 'PLUGIN_MONITOR_ALERT_EMAILS' ) );

/**
 * BuddyPress
 */
Config::define( 'BP_DEFAULT_COMPONENT', 'profile' ); // make "profile" default rather than "activity" for bp members component

/**
 * BuddyPress Reply By Email
 */
Config::define( 'BP_RBE_SPARKPOST_WEBHOOK_TOKEN', getenv( 'BP_RBE_SPARKPOST_WEBHOOK_TOKEN' ) );

/**
 * Social Accounts
 */
Config::define( 'TWITTER_USERNAME', getenv( 'TWITTER_USERNAME' ) );
Config::define( 'FACEBOOK_APP_ID', getenv( 'FACEBOOK_APP_ID' ) );

/**
 * Humanities CORE
 */
Config::define('CORE_HTTP_DEBUG', getenv('CORE_HTTP_DEBUG'));
Config::define('CORE_ERROR_LOG', getenv('CORE_ERROR_LOG'));
Config::define('CORE_HUMCORE_NAMESPACE', getenv('CORE_HUMCORE_NAMESPACE'));
Config::define('CORE_HUMCORE_TEMP_DIR', getenv('CORE_HUMCORE_TEMP_DIR'));
Config::define('CORE_HUMCORE_COLLECTION_PID', getenv('CORE_HUMCORE_COLLECTION_PID'));
Config::define('CORE_FEDORA_PROTOCOL', getenv('CORE_FEDORA_PROTOCOL'));
Config::define('CORE_FEDORA_HOST', getenv('CORE_FEDORA_HOST'));
Config::define('CORE_FEDORA_PORT', getenv('CORE_FEDORA_PORT'));
Config::define('CORE_FEDORA_PATH', getenv('CORE_FEDORA_PATH'));
Config::define('CORE_FEDORA_LOGIN', getenv('CORE_FEDORA_LOGIN'));
Config::define('CORE_FEDORA_PASSWORD', getenv('CORE_FEDORA_PASSWORD'));
Config::define('CORE_SOLR_PROTOCOL', getenv('CORE_SOLR_PROTOCOL'));
Config::define('CORE_SOLR_HOST', getenv('CORE_SOLR_HOST'));
Config::define('CORE_SOLR_PORT', getenv('CORE_SOLR_PORT'));
Config::define('CORE_SOLR_PATH', getenv('CORE_SOLR_PATH'));
Config::define('CORE_SOLR_CORE', getenv('CORE_SOLR_CORE'));
Config::define('CORE_EZID_PROTOCOL', getenv('CORE_EZID_PROTOCOL'));
Config::define('CORE_EZID_HOST', getenv('CORE_EZID_HOST'));
Config::define('CORE_EZID_PORT', getenv('CORE_EZID_PORT'));
Config::define('CORE_EZID_PATH', getenv('CORE_EZID_PATH'));
Config::define('CORE_EZID_LOGIN', getenv('CORE_EZID_LOGIN'));
Config::define('CORE_EZID_PASSWORD', getenv('CORE_EZID_PASSWORD'));
Config::define('CORE_EZID_PREFIX', getenv('CORE_EZID_PREFIX'));
Config::define('CORE_DATACITE_PROTOCOL', getenv('CORE_DATACITE_PROTOCOL'));
Config::define('CORE_DATACITE_HOST', getenv('CORE_DATACITE_HOST'));
Config::define('CORE_DATACITE_PORT', getenv('CORE_DATACITE_PORT'));
Config::define('CORE_DATACITE_PATH', getenv('CORE_DATACITE_PATH'));
Config::define('CORE_DATACITE_LOGIN', getenv('CORE_DATACITE_LOGIN'));
Config::define('CORE_DATACITE_PASSWORD', getenv('CORE_DATACITE_PASSWORD'));
Config::define('CORE_DATACITE_PROXY', getenv('CORE_DATACITE_PROXY'));
Config::define('CORE_DATACITE_PREFIX', getenv('CORE_DATACITE_PREFIX'));

$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if (file_exists($env_config)) {
    require_once $env_config;
}

Config::apply();

/**
 * Bootstrap WordPress
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}
