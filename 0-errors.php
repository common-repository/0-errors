<?php
/**
 * Plugin Name: 0-errors
 * Plugin URI: http://example.org/
 * Author: Ayebare Mucunguzi
 * Author URI: http://zanto.org/
 * Version: 0.2
 * Text Domain: 0e
 * License: GPL2
 */
define('ZEP_VERSION', '0.2');
define('ZEP_PATH', dirname(__FILE__));
define('ZEP_PATH_INCLUDES', dirname(__FILE__) . '/inc');
define('ZEP_PATH_CLASS', dirname(__FILE__) . '/classes');
define('ZEP_FOLDER', basename(ZEP_PATH));
define('ZEP_URL', plugins_url() . '/' . ZEP_FOLDER);
define('ZEP_URL_INCLUDES', ZEP_URL . '/inc');

/**
 * @defgroup logging_severity_levels Logging severity levels
 * @{* Logging severity levels as defined in RFC 3164.
 *
 
 * The ZEROERROR_* constant definitions correspond to the logging severity levels
 * defined in RFC 3164, section 4.1.1. PHP supplies predefined LOG_* constants
 * for use in the syslog() function, but their values on Windows builds do not
 * correspond to RFC 3164. The associated PHP bug report was closed with the
 * comment, "And it's also not a bug, as Windows just have less log levels,"
 * and "So the behavior you're seeing is perfectly normal."
 *
 * @see http://www.faqs.org/rfcs/rfc3164.html
 * @see http://bugs.php.net/bug.php?id=18090
 * @see http://php.net/manual/function.syslog.php
 * @see http://php.net/manual/network.constants.php
 * @see format_err()
 * @see ZEROERROR_severity_levels()
 */

/**
 * Log message severity -- Emergency: system is unusable.
 */
define('ZEROERROR_EMERGENCY', 0);

/**
 * Log message severity -- Alert: action must be taken immediately.
 */
define('ZEROERROR_ALERT', 1);

/**
 * Log message severity -- Critical conditions.
 */
define('ZEROERROR_CRITICAL', 2);

/**
 * Log message severity -- Error conditions.
 */
define('ZEROERROR_ERROR', 3);

/**
 * Log message severity -- Warning conditions.
 */
define('ZEROERROR_WARNING', 4);

/**
 * Log message severity -- Normal but significant conditions.
 */
define('ZEROERROR_NOTICE', 5);

/**
 * Log message severity -- Informational messages.
 */
define('ZEROERROR_INFO', 6);

/**
 * Log message severity -- Debug-level messages.
 */
define('ZEROERROR_DEBUG', 7);

/**
 * @} End of "defgroup logging_severity_levels".
 */
if (!WP_DEBUG)
error_reporting( E_ALL );


/**
 * Register activation hook
 *
 */
function ze_on_activate_callback() {
    // do something on activation
}

/**
 * Register deactivation hook
 *
 */
function ze_on_deactivate_callback() {
    // do something when deactivated
}

require_once ZEP_PATH_CLASS . '/ze-base.class.php';
require_once ZEP_PATH_CLASS . '/ze-error-handler.class.php';

$ze_error_handler = new ZERO_ERROR_handler();

// Initialize everything
$Zero_Error_Base = new Zero_Error_Base();
