<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PEM private key setting that rejects unreadable or non-RSA keys when saved.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


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
