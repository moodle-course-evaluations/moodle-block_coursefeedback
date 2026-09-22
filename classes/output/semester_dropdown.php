<?php

namespace block_coursefeedback\output;

use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization_semester;
use Closure;
use core\output\named_templatable;
use core\output\renderable;
use core\output\renderer_base;
use moodle_url;

class semester_dropdown implements renderable, named_templatable {

    public function __construct(
        /** @var array{0: ?evaluation_semester, 1: ?organization_semester}[] $semesters */
        private readonly array $semesters,
        private readonly Closure $link_generator,
        private readonly ?string $button_text = null,
        private readonly ?int $selected_semester_id = null,
        private readonly ?int $selected_orgsem_id = null,
    ) {
    }

    public function get_template_name(renderer_base $renderer): string {
        return 'block_coursefeedback/semester_dropdown';
    }

    public function export_for_template(renderer_base $output): array {
        $context = [
            'button_text' => $this->button_text,
        ];

        foreach ($this->semesters as [$eva_semester, $org_semester]) {
            $context['semesters'][] = [
                'name' => $eva_semester?->name ?? $org_semester->get('semestername'),
                'is_current' => $eva_semester?->is_current ?? false,
                'is_selected' => $org_semester
                    ? $org_semester->get('id') === $this->selected_orgsem_id
                    : $eva_semester->id === $this->selected_semester_id,
                'url' => ($this->link_generator)($eva_semester, $org_semester),
            ];
        }

        return $context;
    }
}