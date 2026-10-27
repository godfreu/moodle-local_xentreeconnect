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
 * The "Connect with Xentree" button, or an admin-only notice when not configured.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect\output;

use local_xentreeconnect\button_policy;
use local_xentreeconnect\launcher;

/**
 * The "Connect with Xentree" button, or an admin-only notice when the plugin is not configured.
 *
 * @package    local_xentreeconnect
 */
class connect_button implements \renderable, \templatable {
    /** @var string One of the button_policy state constants (never HIDDEN). */
    private string $state;

    /** @var string */
    private string $label;

    private function __construct(string $state, string $label) {
        $this->state = $state;
        $this->label = $label;
    }

    /**
     * Builds the button for the current user, or null when nothing should be shown to them.
     */
    public static function for_current_user(): ?self {
        $state = button_policy::decide(launcher::from_site_config()->is_configured(), is_siteadmin());
        if ($state === button_policy::HIDDEN) {
            return null;
        }
        $label = button_policy::label(
            get_config('local_xentreeconnect', 'buttontext'),
            get_string('launchbutton', 'local_xentreeconnect')
        );
        return new self($state, $label);
    }

    public function export_for_template(\renderer_base $output): \stdClass {
        $data = new \stdClass();
        $data->showbutton = $this->state === button_policy::BUTTON;
        $data->adminnotice = $this->state === button_policy::ADMIN_NOTICE;
        $data->label = $this->label;
        $data->href = (new \moodle_url('/local/xentreeconnect/launch.php'))->out(false);
        $data->message = get_string('buttonnotconfigured', 'local_xentreeconnect');
        $data->statusurl = (new \moodle_url('/local/xentreeconnect/index.php'))->out(false);
        $data->statuslabel = get_string('statuspage', 'local_xentreeconnect');
        return $data;
    }
}
