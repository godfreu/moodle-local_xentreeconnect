<?php

namespace local_xentreeconnect;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * PEM private key setting that rejects unreadable or non-RSA keys when saved.
 *
 * @package    local_xentreeconnect
 */
class admin_setting_privatekey extends \admin_setting_configtextarea {
    public function __construct(string $name, string $visiblename, string $description) {
        parent::__construct($name, $visiblename, $description, '', PARAM_RAW, '60', '12');
    }

    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
            return $result;
        }
        if (trim((string) $data) === '') {
            return true;
        }
        $key = openssl_pkey_get_private(trim($data));
        if ($key === false) {
            return get_string('privatekeyinvalid', 'local_xentreeconnect');
        }
        $details = openssl_pkey_get_details($key);
        if ($details === false || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            return get_string('privatekeynotrsa', 'local_xentreeconnect');
        }
        return true;
    }
}
