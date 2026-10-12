<?php

namespace local_xentreeconnect;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * URL setting that only accepts absolute https URLs.
 *
 * @package    local_xentreeconnect
 */
class admin_setting_httpsurl extends \admin_setting_configtext {
    public function __construct(string $name, string $visiblename, string $description, string $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_URL);
    }

    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
            return $result;
        }
        if (!launcher::is_valid_https_url(trim((string) $data))) {
            return get_string('baseurlinvalid', 'local_xentreeconnect');
        }
        return true;
    }
}
