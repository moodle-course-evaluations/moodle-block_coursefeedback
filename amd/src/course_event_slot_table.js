/*
 * This file is part of Moodle - https://questionpy.org
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 */

import Alpine from './alpinejs-lazy';
import ModalEvents from 'core/modal_events';
import ModalDeleteCancel from 'core/modal_delete_cancel';
import { get_string as getString } from 'core/str';
import { ajaxAndHandleError } from "./util";

document.addEventListener('alpine:init', () => {
    Alpine.data('table', (courseId) => ({
        courseId,

        addingNewEvent: false,

        async submitEvent(e) {
            const form = e.target;
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const args = Object.fromEntries(new FormData(form).entries());

            const result = await ajaxAndHandleError({
                methodname: 'block_coursefeedback_upsert_event',
                args
            });
            this.$refs.courseMappingTable.outerHTML = result.new_table_html;
        }
    }));

    Alpine.data('event', (eventId) => ({
        eventId,
        eventName: null,

        addingNewSlot: false,
        editingEvent: false,

        init() {
            this.eventName = this.$root.dataset.eventname;
        },

        async submitSlot(e) {
            const form = e.target;
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const args = Object.fromEntries(new FormData(form).entries());

            const result = await ajaxAndHandleError({
                methodname: 'block_coursefeedback_upsert_slot',
                args
            });
            this.$refs.courseMappingTable.outerHTML = result.new_table_html;
        },

        async deleteEvent() {
            const modal = await ModalDeleteCancel.create({
                title: getString('confirm'),
                body: getString('confirm_event_deletion', 'block_coursefeedback', this.eventName),
                show: true,
                removeOnClose: true,
            });

            modal.getRoot().on(ModalEvents.delete, async () => {
                const result = await ajaxAndHandleError({
                    methodname: 'block_coursefeedback_delete_event',
                    args: {
                        courseid: this.courseId,
                        eventid: this.eventId,
                    }
                });
                this.$refs.courseMappingTable.outerHTML = result.new_table_html;
            });
        },
    }));

    Alpine.data('slot', (slotId) => ({
        slotId,
        slotName: null,

        editingSlot: false,
        addingUser: false,

        init() {
            this.slotName = this.$root.dataset.slotname;
        },

        startEditingSlotName() {
            this.editingSlot = true;

            this.$nextTick(() => {
                this.$refs.slotNameInput.focus();
                // Place the cursor at the end of the current input.
                this.$refs.slotNameInput.selectionStart
                    = this.$refs.slotNameInput.selectionEnd
                    = this.$refs.slotNameInput.value.length;
            });
        },

        async deleteSlot() {
            const modal = await ModalDeleteCancel.create({
                title: getString('confirm'),
                body: getString('confirm_slot_deletion', 'block_coursefeedback', this.slotName),
                show: true,
                removeOnClose: true,
            });

            modal.getRoot().on(ModalEvents.delete, async () => {
                const result = await ajaxAndHandleError({
                    methodname: 'block_coursefeedback_delete_slot',
                    args: {
                        courseid: this.courseId,
                        slotid: slotId,
                    }
                });
                this.$refs.courseMappingTable.outerHTML = result.new_table_html;
            });
        },
    }));
});
