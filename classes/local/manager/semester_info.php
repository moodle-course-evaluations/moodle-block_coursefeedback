<?php

namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization_semester;

class semester_info {

    public readonly bool $is_current;

    public function __construct(
        public readonly ?evaluation_semester $mapping_semester,
        public readonly ?organization_semester $semester_settings
    ) {
        $this->is_current = $this->mapping_semester?->is_current ?? false;
    }
}