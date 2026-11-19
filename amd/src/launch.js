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
 * Submits the Xentree Connect launch form automatically.
 *
 * Without JavaScript the form stays on the page and its button submits it.
 *
 * @module     local_xentreeconnect/launch
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Submits the launch form once.
 *
 * @param {String} formId Id of the launch form element.
 */
export const init = (formId) => {
    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
};
