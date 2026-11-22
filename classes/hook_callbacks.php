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
 * Hook callbacks that place the Connect button on the pages the admin enabled.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect;

use core\hook\navigation\primary_extend;
use core\hook\output\before_standard_top_of_body_html_generation;
use local_xentreeconnect\output\connect_button;

/**
 * Hook callbacks that place the Connect button on the pages the admin enabled.
 *
 * @package    local_xentreeconnect
 */
class hook_callbacks {
    /**
     * Adds the button at the top of the body on enabled pages (dashboard, course, login).
     *
     * @param before_standard_top_of_body_html_generation $hook The hook.
     */
    public static function before_standard_top_of_body_html_generation(before_standard_top_of_body_html_generation $hook): void {
        global $PAGE, $OUTPUT;

        $enabled = button_policy::enabled_placements(get_config('local_xentreeconnect', 'placements'));
        if (!$enabled || !button_policy::should_render((string) $PAGE->pagetype, $enabled)) {
            return;
        }
        try {
            $button = connect_button::for_current_user();
            if ($button !== null) {
                $hook->add_html($OUTPUT->render_from_template(
                    'local_xentreeconnect/connect_button',
                    $button->export_for_template($OUTPUT)
                ));
            }
        } catch (\Throwable $e) {
            // Never let the button break a page.
            debugging($e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Adds the "Learn with AI" item to the primary navigation when the admin enabled the header placement.
     *
     * @param primary_extend $hook The hook.
     */
    public static function primary_extend(primary_extend $hook): void {
        $enabled = button_policy::enabled_placements(get_config('local_xentreeconnect', 'placements'));
        if (!in_array(button_policy::HEADER, $enabled, true)) {
            return;
        }
        try {
            $visible = button_policy::header_item_visible(
                $enabled,
                launcher::from_site_config()->is_configured(),
                isloggedin(),
                isguestuser()
            );
            if (!$visible) {
                return;
            }
            // The primary navigation prints the text without escaping it, so it has to go through format_string().
            $label = format_string(
                button_policy::label(
                    get_config('local_xentreeconnect', 'menulabel'),
                    get_string('launchmenu', 'local_xentreeconnect')
                ),
                true,
                ['context' => \context_system::instance()]
            );
            $hook->get_primaryview()->add(
                $label,
                new \moodle_url('/local/xentreeconnect/launch.php'),
                \navigation_node::TYPE_CUSTOM,
                null,
                'local_xentreeconnect'
            );
        } catch (\Throwable $e) {
            // Never let the menu item break a page.
            debugging($e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
