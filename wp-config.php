<?php



/** Enable W3 Total Cache */


/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_jp0jg' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost:3306' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'b@lZ3CszLwvog9E;SfHa|ZVxH9eq+:#~k-g9MuUS]X#1IskHlM6J503([K%:Ir4O');
define('SECURE_AUTH_KEY', 'T8j~0404X_07j_wHfeI0xZ~cg2K@9RWm!L-Xb+b161k8+EU9h:&~OQ&1&7eTC89e');
define('LOGGED_IN_KEY', '6#J+1uinTZG6fC](l+]Q/E5s2i!1y]37BL&n@*tF0;#9)@NGt2wjA#3XI]428Yni');
define('NONCE_KEY', ':Y6/x7r19s7t8||kvH11JQZm3T7%f%g74g~3HA;ZaB2cX@6IGZ-gT0~:u!Y#t9a]');
define('AUTH_SALT', '48h4+uHh*3S+Bp)l;)0/7NC_5UChkG!;ktbu/H5h05!W9!_34l[5exjCG1xnR-d[');
define('SECURE_AUTH_SALT', ':)~2@r47H9x81-jd#T@xW~i5V0lP%1/-DyZ2w:1w_8e_9ktDh)&R9~A+-58-o*8;');
define('LOGGED_IN_SALT', '2i2!r*1xJgM6[@7ni![kmAaxPo7ft7oT+my|[0hDgT~gd7jsW:;NmfC_t%+*1&FM');
define('NONCE_SALT', '5Fm8fAnRD;3I3pSF@&19s5!~qyn!a8_[)N-*XW;on5Y8g)J#/_x!mb(~I7Q[H@se');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'MLtdM_';


/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);


// Twilio Credentials
define('TWILIO_SID', 'AC18b03a854ee36f8328eaf8257d666347');
define('TWILIO_AUTH_TOKEN', 'c16c04476b55afe07c500da6ab4ad5b7');
define('TWILIO_PHONE_NUMBER', '+447723447201');

// define('TWILIO_SID', 'AC23432de5e0387ae72fe20d96f8eb1edd');
// define('TWILIO_AUTH_TOKEN', 'ed48177204a4cae2dd5ffdb19e4848ed');
// define('TWILIO_PHONE_NUMBER', '+16507062681');


define('WP_MAIL_FROM', 'info@brags.co.uk');
define('WP_MAIL_FROM_NAME', 'Brags');

@ini_set( 'max_input_vars', '5000' );


define('WP_POST_REVISIONS', 3); // Keeps only 3 revisions per post
define('AUTOSAVE_INTERVAL', 160); // Saves every 160 seconds (instead of 60)

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	
	define('WP_DEBUG', false);
	//define('WP_DEBUG_LOG', true);
	// define('WP_DEBUG_DISPLAY', false);
	// @ini_set('display_errors', 0);
}

// Increase execution time
// set_time_limit(300); // 300 seconds = 5 minutes
// ini_set('max_execution_time', '300');
// ini_set('memory_limit', '512M');

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
