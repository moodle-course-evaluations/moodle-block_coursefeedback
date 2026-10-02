<?php

namespace block_coursefeedback\local\semester;

use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;

class semester_state_uninitialized extends semester_state {

    public function __construct(
        organization $organization,
        public readonly evaluation_semester $semester
    ) {
        parent::__construct($organization);
    }
}