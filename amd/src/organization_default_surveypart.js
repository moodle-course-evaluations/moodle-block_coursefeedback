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

import Templates from "core/templates";

/**
 *
 * @param {object} new_surveypart_chooser_context
 * @returns {Promise<void>}
 */
export async function init(new_surveypart_chooser_context) {
    let additionalCount = 0;

    void Templates.prefetchTemplates(['block_coursefeedback/organization_default_surveypart_row']);

    const formEl = document.getElementById('block_coursefeedback-default_surveypart_form');
    const addEventtypeRowEl = formEl.querySelector('.add_eventtype_row');
    let removed = new Set();
    let added = new Set();

    const addedEl = formEl.querySelector('input[name="added"]');
    const removedEl = formEl.querySelector('input[name="removed"]');

    const updateAdded = () => {
        addedEl.value = JSON.stringify([...added]);
    };
    const updateRemoved = () => {
        removedEl.value = JSON.stringify([...removed]);
    };

    formEl.addEventListener('click', (e) => {
        const deleteEl = e.target.closest('[data-delete-eventtype]');
        if (deleteEl) {
            const deleteId = deleteEl.dataset.deleteEventtype;
            if (deleteId.startsWith('new-')) {
                added.delete(parseInt(deleteId.substring(4)));
                updateAdded();
            } else {
                removed.add(parseInt(deleteId));
                updateRemoved();
            }
            deleteEl.closest('tr').remove();
        }
    });

    addEventtypeRowEl.querySelector('.add_eventtype_btn').addEventListener('click', async() => {
        const newId = ++additionalCount;
        const {html} = await Templates.renderForPromise('block_coursefeedback/organization_default_surveypart_row', {
            id: 'new-' + newId,
            name: '',
            surveypart_chooser_context: new_surveypart_chooser_context,
        });
        const row = document.createElement('tr');
        row.innerHTML = html;

        added.add(newId);
        updateAdded();

        addEventtypeRowEl.parentNode.insertBefore(row, addEventtypeRowEl);
    });
}