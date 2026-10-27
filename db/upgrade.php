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
 * Upgrade steps for local_xentreeconnect.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_xentreeconnect.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_xentreeconnect_upgrade($oldversion) {
    if ($oldversion < 2026101200) {
        // 0.1.1: the JWKS is now derived from the private key, and placeholder defaults are gone.
        unset_config('jwksjson', 'local_xentreeconnect');
        if (get_config('local_xentreeconnect', 'issuer') === 'https://moodle.example.com') {
            unset_config('issuer', 'local_xentreeconnect');
        }
        $pem = get_config('local_xentreeconnect', 'privatekeypem');
        if (is_string($pem) && preg_replace('/\s+/', '', $pem) === '-----BEGINPRIVATEKEY----------ENDPRIVATEKEY-----') {
            unset_config('privatekeypem', 'local_xentreeconnect');
        }
        upgrade_plugin_savepoint(true, 2026101200, 'local', 'xentreeconnect');
    }

    if ($oldversion < 2026101201) {
        // 0.1.1 build 2: button placement hooks and launch.php. Nothing to migrate; the bump registers the hooks.
        upgrade_plugin_savepoint(true, 2026101201, 'local', 'xentreeconnect');
    }

    if ($oldversion < 2026101202) {
        // 0.1.1 build 3: privacy provider added. Nothing to migrate; the bump refreshes Moodle's class cache.
        upgrade_plugin_savepoint(true, 2026101202, 'local', 'xentreeconnect');
    }

    if ($oldversion < 2026101400) {
        // 0.2.0: tables for the activity received from Xentree and the receiver's hourly counters.
        global $DB;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('local_xentreeconnect_event');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('deliveryid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('eventid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('eventtype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('occurredat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('receivedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sessionseconds', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('sessionstatus', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('deliveryid', XMLDB_INDEX_UNIQUE, ['deliveryid']);
            $table->add_index('eventid', XMLDB_INDEX_UNIQUE, ['eventid']);
            $table->add_index('userid_occurredat', XMLDB_INDEX_NOTUNIQUE, ['userid', 'occurredat']);
            $table->add_index('receivedat', XMLDB_INDEX_NOTUNIQUE, ['receivedat']);
            $dbman->create_table($table);
        }

        $stat = new xmldb_table('local_xentreeconnect_stat');
        if (!$dbman->table_exists($stat)) {
            $stat->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $stat->add_field('hourstart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $stat->add_field('outcome', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $stat->add_field('cnt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $stat->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $stat->add_index('hourstart_outcome', XMLDB_INDEX_UNIQUE, ['hourstart', 'outcome']);
            $dbman->create_table($stat);
        }

        upgrade_plugin_savepoint(true, 2026101400, 'local', 'xentreeconnect');
    }

    return true;
}
