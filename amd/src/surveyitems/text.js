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

import { SurveyItem } from "block_coursefeedback/surveyitem";

/**
 * Implement SurveyItem for Text
 *
 * @module     block_coursefeedback/surveyitems/text
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export class Text extends SurveyItem {

    textarea = this.surveyItemRootElement.querySelector('textarea');

    initialize() {
        if (this.surveyItemData.autoresize) {
            const textArea = this.textarea;
            // To interpret the 'rows' attribute as minimum height, we measure the initial size of the text area.
            const initialHeight = this.textarea.clientHeight;

            const autoResize = () => {
                textArea.style.height = "auto"; // Set to auto so that the browser calculates scrollHeight properly.
                textArea.style.height = Math.max(initialHeight, textArea.scrollHeight) + "px";
            };

            this.textarea.style.resize = "none"; // No manual resizing.
            this.textarea.addEventListener("input", autoResize);
            autoResize();
        }
    }

    async beforeNext({prevent}) {
        // The browser should prevent it, but if the user managed to enter more text than 'maxlength', prevent the page change.
        if (!this.textarea.checkValidity()) {
            this.textarea.reportValidity();
            prevent();
        }
    }

    getValue() {
        return this.textarea.value || null;
    }

    setValue(value) {
        this.textarea.value = value;
    }
}
