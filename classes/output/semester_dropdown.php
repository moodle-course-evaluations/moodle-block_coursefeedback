<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_coursefeedback\output;

use block_coursefeedback\local\manager\semester_info;
use Closure;
use core\output\named_templatable;
use core\output\renderable;
use core\output\renderer_base;
use moodle_url;

class semester_dropdown implements named_templatable, renderable {

    /**
     * Constructor.
     *
     * @param array $semesters
     * @param Closure(semester_info): moodle_url|string $link_generator
     * @param string|null $button_text
     * @param int|null $selected_semester_id
     * @param int|null $selected_orgsem_id
     */
    public function __construct(
        /** @var semester_info[] $semesters */
        private readonly array $semesters,
        /** @var Closure(semester_info): moodle_url|string $link_generator */
        private readonly Closure $link_generator,
        /** @var string|null $button_text */
        private readonly ?string $button_text = null,
        /** @var int|null $selected_semester_id */
        private readonly ?int $selected_semester_id = null,
        /** @var int|null $selected_orgsem_id */
        private readonly ?int $selected_orgsem_id = null,
    ) {
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'block_coursefeedback/semester_dropdown';
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $context = [
            'button_text' => $this->button_text,
        ];

        foreach ($this->semesters as $semester_info) {
            $is_selected = $semester_info->orgsem && $semester_info->orgsem->get('id') === $this->selected_orgsem_id
                || $semester_info->semester && $semester_info->semester->id === $this->selected_semester_id;

            $semester_context = $context['semesters'][] = [
                'name' => $semester_info->get_name(),
                'is_current' => $semester_info->is_current(),
                'is_selected' => $is_selected,
                'url' => ($this->link_generator)($semester_info),
            ];

            if ($is_selected) {
                $context['selected_semester'] = $semester_context;
            }
        }

        return $context;
    }
}
