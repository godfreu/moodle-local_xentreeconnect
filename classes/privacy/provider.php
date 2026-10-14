<?php

namespace local_xentreeconnect\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy API implementation for local_xentreeconnect.
 *
 * Two flows of personal data:
 *  - Sent to Xentree when a user starts the Connect flow: Moodle user ID (as moodle-user-<id>) and e-mail address.
 *  - Received from Xentree and stored locally: learning activity of members who agreed to share it (one row per
 *    completed session: when, how long, status). Stored only in local_xentreeconnect_event, system context.
 *
 * @package    local_xentreeconnect
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /** The table holding activity received from Xentree. */
    private const TABLE = 'local_xentreeconnect_event';

    /**
     * Describes the data stored here and the data sent to the external Xentree service.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(self::TABLE, [
            'userid' => 'privacy:metadata:event:userid',
            'eventtype' => 'privacy:metadata:event:eventtype',
            'occurredat' => 'privacy:metadata:event:occurredat',
            'receivedat' => 'privacy:metadata:event:receivedat',
            'sessionseconds' => 'privacy:metadata:event:sessionseconds',
            'sessionstatus' => 'privacy:metadata:event:sessionstatus',
        ], 'privacy:metadata:event');

        $collection->add_external_location_link('xentree', [
            'userid' => 'privacy:metadata:xentree:userid',
            'email' => 'privacy:metadata:xentree:email',
        ], 'privacy:metadata:xentree');

        return $collection;
    }

    /**
     * The system context holds the data of every user who has rows.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND EXISTS (SELECT 1 FROM {" . self::TABLE . "} e WHERE e.userid = :userid)";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_SYSTEM, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Lists the users with data in the system context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT DISTINCT userid FROM {' . self::TABLE . '}', []);
    }

    /**
     * Exports the activity stored for the user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $system = \context_system::instance();
        if (!in_array($system->id, $contextlist->get_contextids())) {
            return;
        }
        $rows = $DB->get_records(self::TABLE, ['userid' => $contextlist->get_user()->id], 'occurredat ASC, id ASC');
        if (!$rows) {
            return;
        }
        $sessions = [];
        foreach ($rows as $row) {
            $sessions[] = (object) [
                'eventtype' => $row->eventtype,
                'occurredat' => transform::datetime($row->occurredat),
                'receivedat' => transform::datetime($row->receivedat),
                'sessionseconds' => $row->sessionseconds === null ? null : (int) $row->sessionseconds,
                'sessionstatus' => $row->sessionstatus,
            ];
        }
        writer::with_context($system)->export_data(
            [get_string('pluginname', 'local_xentreeconnect'), get_string('privacy:path:activity', 'local_xentreeconnect')],
            (object) ['activity' => $sessions]
        );
    }

    /**
     * Deletes all activity of every user in the context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_system) {
            $DB->delete_records(self::TABLE);
        }
    }

    /**
     * Deletes the activity of one user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (!in_array(\context_system::instance()->id, $contextlist->get_contextids())) {
            return;
        }
        $DB->delete_records(self::TABLE, ['userid' => $contextlist->get_user()->id]);
    }

    /**
     * Deletes the activity of several users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(self::TABLE, "userid $insql", $params);
    }
}
