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
 * The form that hands the browser to Xentree with the signed assertion.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect\output;

/**
 * The auto-submitted launch form. It is a plain POST form, so it also works without JavaScript: the visible button
 * submits it when the browser does not run the AMD module that submits it automatically.
 *
 * @package    local_xentreeconnect
 */
class launch_form implements \renderable, \templatable {
    /** @var string Id of the form element, used by the AMD module. */
    public const FORM_ID = 'xentreeconnect-launch-form';

    /** @var string Xentree launch endpoint. */
    private string $action;

    /** @var string Signed assertion (JWT). */
    private string $assertion;

    /** @var string Text shown while the browser is being redirected. */
    private string $redirecting;

    /** @var string Label of the fallback submit button. */
    private string $continuelabel;

    /**
     * Creates the launch form.
     *
     * @param string $action Xentree launch endpoint the form is posted to.
     * @param string $assertion Signed launch assertion.
     * @param string $redirecting Text shown while redirecting.
     * @param string $continuelabel Label of the submit button.
     */
    public function __construct(string $action, string $assertion, string $redirecting, string $continuelabel) {
        $this->action = $action;
        $this->assertion = $assertion;
        $this->redirecting = $redirecting;
        $this->continuelabel = $continuelabel;
    }

    /**
     * Builds the form for the signed-in user from the current configuration strings.
     *
     * @param string $action Xentree launch endpoint.
     * @param string $assertion Signed launch assertion.
     * @return self
     */
    public static function create(string $action, string $assertion): self {
        return new self(
            $action,
            $assertion,
            get_string('redirecting', 'local_xentreeconnect'),
            get_string('continuetoxentree', 'local_xentreeconnect')
        );
    }

    /**
     * Data for the templates/launch_form mustache template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $data = new \stdClass();
        $data->formid = self::FORM_ID;
        $data->action = $this->action;
        $data->assertion = $this->assertion;
        $data->redirecting = $this->redirecting;
        $data->continuelabel = $this->continuelabel;
        return $data;
    }
}
