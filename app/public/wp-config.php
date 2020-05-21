<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'uQcaS3Bq4tlU5SZpTTl9qKALH07VdujlW8eptyEdqxadPv2rQFjsERJs5J98JmylfQM4LeJk7kdZVImSqZVZUg==');
define('SECURE_AUTH_KEY',  'YrzoEvD930VnxT3FImivBJtvS9j64JNsmvjq9AGldR2FhJ0BIDPHOCerro7oPXNcgVYSGVG9poOmjkQn39p2Hw==');
define('LOGGED_IN_KEY',    'DPuxI6P88zaVtd+8KPorMJooLifENsHotf4/3l3YfMAr4Hy0EB/hZH1WpoU36jid6pKisu6oXyie9MghtWG4nw==');
define('NONCE_KEY',        '4w77lBGAYBoRnvo0rnF2ut8kN88d3NwmB41RlltXTGNj12sXSgFNyAiVBvZAU2Fydc9xnxfI1+QoGhRQclPGow==');
define('AUTH_SALT',        'Bcp8D1fT2bQakRAdyn9CnlOEeVTRaupBtSfNH17kDoTJVb6kHi+2V2VbFKfOJYBCkC9vgm10CBnx4P/zMyezGw==');
define('SECURE_AUTH_SALT', 'FSGgswkJK3myGtXdkNyD1GkilmOIIsFFR0qHfuduZPM7fyf7cK547ZuBKKOGcGuN0SfdpFAYN5GEsF2pbGIgNA==');
define('LOGGED_IN_SALT',   'kGhykfT0MBNYYPlPZ71Dxjbs5QfVYc7sol+Zw8274RX/IP07QnkcQ9iKan0EcyYgxTkQj+AHlUipYHO8dZjf3A==');
define('NONCE_SALT',       'KbC+Gc7hKXcJoHbFf0JhOmggECbwiokQDBtFFlmXrUe2iiH73h2KxrhS0LPLU9ZM1WnNGUN14rnI6DqhCgnZxQ==');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';




/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
