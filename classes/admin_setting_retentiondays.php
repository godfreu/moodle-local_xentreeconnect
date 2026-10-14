<?php

namespace local_xentreeconnect;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Number of days activity from Xentree is kept; 0 disables automatic deletion.
 *
 * @package    local_xentreeconnect
 */
class admin_setting_retentiondays extends \admin_setting_configtext {
    public function __construct(string $name, string $visiblename, string $description, string $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_INT);
    }

    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
            return $result;
        }
        if (!preg_match('/^\d{1,5}$/', trim((string) $data))) {
            return get_string('retentiondaysinvalid', 'local_xentreeconnect');
        }
        return true;
    }
}
