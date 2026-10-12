<?php

namespace local_xentreeconnect\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy API implementation for local_xentreeconnect.
 *
 * The plugin stores no personal data in Moodle. When a user starts the Connect flow it sends their identifier and
 * e-mail address to Xentree, which is declared below. There is nothing local to export or delete.
 *
 * @package    local_xentreeconnect
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describes the data sent to the external Xentree service.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link('xentree', [
            'userid' => 'privacy:metadata:xentree:userid',
            'email' => 'privacy:metadata:xentree:email',
        ], 'privacy:metadata:xentree');

        return $collection;
    }

    /**
     * Nothing is stored in Moodle, so no context holds data for the user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Nothing is stored in Moodle, so there is nothing to export.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
    }

    /**
     * Nothing is stored in Moodle, so there is nothing to delete.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
    }

    /**
     * Nothing is stored in Moodle, so there is nothing to delete.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }
}
