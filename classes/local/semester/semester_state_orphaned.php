<?php

namespace block_coursefeedback\local\semester;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;

class semester_state_orphaned extends semester_state {

    public function __construct(
        organization $organization,
        public readonly organization_semester $orgsem
    ) {
        parent::__construct($organization);
    }
}