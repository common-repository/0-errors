<?php

class ZERO_ERROR_handler {

    private static $ajax_errs;

    /**
     * Construct me
     */
    public function __construct() {
        // set to the user defined error handler
        $GLOBALS['_ze_errors'] = array();
        set_error_handler(array($this, "_zerror_handler"));
    }

    /** error handler function */
    function _zerror_handler($error_level, $message, $filename, $line, $context) {
        require_once( ABSPATH . WPINC . '/pluggable.php' );
        $this->_error_handler_real($error_level, $message, $filename, $line, $context);
    }

    /**
     * Provides custom PHP error handling.
     *
     * @param $error_level
     *   The level of the error raised.
     * @param $message
     *   The error message.
     * @param $filename
     *   The filename that the error was raised in.
     * @param $line
     *   The line number the error was raised at.
     * @param $context
     *   An array that points to the active symbol table at the point the error
     *   occurred.
     */
    function _error_handler_real($error_level, $message, $filename, $line, $context) {
        if ($error_level) {
            $types = $this->_error_levels();
            list($severity_msg, $severity_level) = $types[$error_level];
            $caller = $this->_get_last_caller(debug_backtrace());

            // We treat recoverable errors as fatal.
            $this->_log_error(array(
                '%type' => isset($types[$error_level]) ? $severity_msg : 'Unknown error',
                // The standard PHP error handler considers that the error messages
                // are HTML. We mimick this behavior here.
                '!message' => esc_html($message),
                '%function' => $caller['function'],
                '%file' => $caller['file'],
                '%line' => $caller['line'],
                'severity_level' => $severity_level,
                    ), $error_level == E_RECOVERABLE_ERROR);
        }
    }

    /**
     * Maps PHP error constants to format_err severity levels.
     *
     * The error constants are documented at
     * http://php.net/manual/errorfunc.constants.php
     *
     * @ingroup logging_severity_levels
     */
    function _error_levels() {
        $types = array(
            E_ERROR => array('Error', ZEROERROR_ERROR),
            E_WARNING => array('Warning', ZEROERROR_WARNING),
            E_PARSE => array('Parse error', ZEROERROR_ERROR),
            E_NOTICE => array('Notice', ZEROERROR_NOTICE),
            E_CORE_ERROR => array('Core error', ZEROERROR_ERROR),
            E_CORE_WARNING => array('Core warning', ZEROERROR_WARNING),
            E_COMPILE_ERROR => array('Compile error', ZEROERROR_ERROR),
            E_COMPILE_WARNING => array('Compile warning', ZEROERROR_WARNING),
            E_USER_ERROR => array('User error', ZEROERROR_ERROR),
            E_USER_WARNING => array('User warning', ZEROERROR_WARNING),
            E_USER_NOTICE => array('User notice', ZEROERROR_NOTICE),
            E_STRICT => array('Strict warning', ZEROERROR_DEBUG),
            E_RECOVERABLE_ERROR => array('Recoverable fatal error', ZEROERROR_ERROR),
        );
        // E_DEPRECATED and E_USER_DEPRECATED were added in PHP 5.3.0.
        if (defined('E_DEPRECATED')) {
            $types[E_DEPRECATED] = array('Deprecated function', ZEROERROR_DEBUG);
            $types[E_USER_DEPRECATED] = array('User deprecated function', ZEROERROR_DEBUG);
        }
        return $types;
    }

    /**
     * Gets the last caller from a backtrace.
     *
     * @param $backtrace
     *   A standard PHP backtrace.
     *
     * @return
     *   An associative array with keys 'file', 'line' and 'function'.
     */
    function _get_last_caller($backtrace) {
        // Errors that occur inside PHP internal functions do not generate
        // information about file and line. Ignore black listed functions.
        $blacklist = array('_zerror_handler', '_ze_error_handler', '_ze_exception_handler');
        while (($backtrace && !isset($backtrace[0]['line'])) ||
        (isset($backtrace[1]['function']) && in_array($backtrace[1]['function'], $blacklist))) {
            array_shift($backtrace);
        }

        // The first trace is the call itself.
        // It gives us the line and the file of the last call.
        $call = $backtrace[0];

        // The second call give us the function where the call originated.
        if (isset($backtrace[1])) {
            if (isset($backtrace[1]['class'])) {
                $call['function'] = $backtrace[1]['class'] . $backtrace[1]['type'] . $backtrace[1]['function'] . '()';
            } else {
                $call['function'] = $backtrace[1]['function'] . '()';
            }
        } else {
            $call['function'] = 'main()';
        }
        return $call;
    }

    /**
     * Provides access to a single instance of a module using the singleton pattern
     *
     * @mvc Controller
     *
     * @return object
     */
    public static function get_ajax_errs() {

        if (!isset(self::$ajax_errs)) {
            self::$ajax_errs = get_option('ze_hxr_errors', array());
        }

        return self::$ajax_errs;
    }

    /**
     * Logs a PHP error or exception and displays an error page in fatal cases.
     *
     * @param $error
     *   An array with the following keys: %type, !message, %function, %file, %line
     *   and severity_level. All the parameters are plain-text, with the exception
     *   of !message, which needs to be a safe HTML string.
     * @param $fatal
     *   TRUE if the error is fatal.
     */
    function _log_error($error, $fatal = FALSE) {

        if ($fatal) {
            wp_die(__('Hey, We are working on somethig but we\'ll be back soon!'), 500);
        }


//Detects whether the current script is running in a command-line environment.
        if (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0))) {
            if ($fatal) {
                // When called from CLI, simply output a plain text message.
                print html_entity_decode(strip_tags(format_string('%type: !message in %function (line %line of %file).', $error))) . "\n";
                exit;
            }
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            $errmsg = $this->format_string('%type: !message in %function (line %line of %file).', $error);

            if (defined('DOING_AJAX') && DOING_AJAX) {
                self::get_ajax_errs();
                if (!in_array($errmsg, self::get_ajax_errs())) {
                    $GLOBALS['_ze_errors'][] = $errmsg;
                    if (count(self::$ajax_errs) == 10) {
                        $ajax_errs = self::$ajax_errs;
                        reset($ajax_errs); // move to the first member of the array
                        $key = key($ajax_errs); // retrieve the key in the first array position
                        unset($ajax_errs[$key]);
                        self::$ajax_errs = $ajax_errs;
                    }
                    self::$ajax_errs[time()] = $errmsg;
                }
            }

            if ($fatal) {
                // When called from JavaScript, simply output the error message.
                print $errmsg;
                exit;
            }
        } else {
            $this->format_err('php', '%type: !message in %function (line %line of %file).', $error, $error['severity_level']);
        }
    }

    /**
     * Logs a system message.
     *
     * @param $type
     *   The category to which this message belongs. Can be any string, but the
     * @param $message
     *   The message to store in the log. 
     * @param $variables
     *   Array of variables to replace in the message on display or
     *   NULL if message is already translated or not possible to
     *   translate.
     * @param $severity
     *   The severity of the message; one of the following values as defined in
     *   @link http://www.faqs.org/rfcs/rfc3164.html RFC 3164: @endlink
     *   - ZEROERROR_EMERGENCY: Emergency, system is unusable.
     *   - ZEROERROR_ALERT: Alert, action must be taken immediately.
     *   - ZEROERROR_CRITICAL: Critical conditions.
     *   - ZEROERROR_ERROR: Error conditions.
     *   - ZEROERROR_WARNING: Warning conditions.
     *   - ZEROERROR_NOTICE: (default) Normal but significant conditions.
     *   - ZEROERROR_INFO: Informational messages.
     *   - ZEROERROR_DEBUG: Debug-level messages.
     * @param $link
     *   A link to associate with the message.
     *
     * @see ZEROERROR_severity_levels()
     */
    function format_err($type, $message, $variables = array(), $severity = ZEROERROR_NOTICE, $link = NULL) {

        static $in_error_state = FALSE;

        // It is possible that the error handling will itself trigger an error. In that case, we could
        // end up in an infinite loop. To avoid that, we implement a simple static semaphore.
        if (!$in_error_state) {
            $in_error_state = TRUE;

            // The userid may not exist in all conditions, so 0 is may not exactly mean user was not logged in
            $user_uid = get_current_user_id();
            // Prepare the fields to be logged
            $log_entry = array(
                'type' => $type,
                'message' => $message,
                'variables' => $variables,
                'severity' => $severity,
                'link' => $link,
                'uid' => $user_uid,
                'request_uri' => $this->request_uri(),
                'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
                // Request time isn't accurate for long processes, use time() instead.
                'timestamp' => time(),
            );

            $message = strtr('!base_url|!timestamp|!type|!request_uri|!referer|!uid|!link|!message', array(
                '!base_url' => site_url(),
                '!timestamp' => $log_entry['timestamp'],
                '!type' => $log_entry['type'],
                '!request_uri' => $log_entry['request_uri'],
                '!referer' => $log_entry['referer'],
                '!uid' => $log_entry['uid'],
                '!link' => strip_tags($log_entry['link']),
                '!message' => strip_tags(!isset($log_entry['variables']) ? $log_entry['message'] : strtr($log_entry['message'], $log_entry['variables'])),
                    ));
            syslog($log_entry['severity'], $message);
            $log_vars = $log_entry['variables'];
            $unq_index = wp_create_nonce($log_vars['%type'] . $log_vars['!message'] . $log_vars['%function'] . $log_vars['%file'] . $log_vars['%line']);
            $GLOBALS['_ze_errors'][$unq_index] = array('errtype' => $log_vars['%type'], 'errmsg' => $message);
            // It is critical that the semaphore is only cleared here, in the parent
            // format_err() call (not outside the loop), to prevent recursive execution.
            $in_error_state = FALSE;
            return $message;
        }
    }

    /**
     * Returns the equivalent of Apache's $_SERVER['REQUEST_URI'] variable.
     *
     * Because $_SERVER['REQUEST_URI'] is only available on Apache, we generate an
     * equivalent using other environment variables.
     */
    function request_uri() {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            if (isset($_SERVER['argv'])) {
                $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
            } elseif (isset($_SERVER['QUERY_STRING'])) {
                $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
            } else {
                $uri = $_SERVER['SCRIPT_NAME'];
            }
        }
        // Prevent multiple slashes to avoid cross site requests via the Form API.
        $uri = '/' . ltrim($uri, '/');

        return $uri;
    }

    /**
     * Formats a string for HTML display by replacing variable placeholders.
     *
     * This function replaces variable placeholders in a string with the requested
     * values and escapes the values so they can be safely displayed as HTML. It
     * should be used on any unknown text that is intended to be printed to an HTML
     * page (especially text that may have come from untrusted users, since in that
     * case it prevents cross-site scripting and other security problems).
     *
     *
     * @param $string
     *   A string containing placeholders.
     * @param $args
     *   An associative array of replacements to make. Occurrences in $string of
     *   any key in $args are replaced with the corresponding value, after optional
     *   sanitization and formatting. The type of sanitization and formatting
     *   depends on the first character of the key:
     *   - @variable: Escaped to HTML using esc_html(). Use this as the default
     *     choice for anything displayed on a page on the site.
     *   - %variable: Escaped to HTML and formatted using placeholder(),
     *     which makes it display as <em>emphasized</em> text.
     *   - !variable: Inserted as is, with no sanitization or formatting. Only use
     *     this for text that has already been prepared for HTML display (for
     *     example, user-supplied text that has already been run through
     *     esc_html() previously, or is expected to contain some limited HTML
     *
     * @ingroup sanitization
     */
    function format_string($string, array $args = array()) {
        // Transform arguments before inserting them.
        foreach ($args as $key => $value) {
            switch ($key[0]) {
                case '@':
                    // Escaped only.
                    $args[$key] = esc_html($value);
                    break;

                case '%':
                default:
                    // Escaped and placeholder.
                    $args[$key] = $this->placeholder($value);
                    break;

                case '!':
                // Pass-through.
            }
        }
        return strtr($string, $args);
    }

    /**
     * Formats text for emphasized display in a placeholder inside a sentence.
     *
     * Used automatically by format_string().
     *
     * @param $text
     *   The text to format (plain-text).
     *
     * @return
     *   The formatted text (html).
     */
    function placeholder($text) {
        return '<em class="placeholder">' . esc_html($text) . '</em>';
    }

}