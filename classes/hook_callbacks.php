<?php

namespace local_xentreeconnect;

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
                $hook->add_html($OUTPUT->render_from_template('local_xentreeconnect/connect_button',
                    $button->export_for_template($OUTPUT)));
            }
        } catch (\Throwable $e) {
            // Never let the button break a page.
            debugging($e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
