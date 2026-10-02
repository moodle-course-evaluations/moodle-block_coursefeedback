<?php

namespace block_coursefeedback\local\semester;

use block_coursefeedback\local\persistent\organization;

abstract class semester_state {

    public function __construct(
        /** @var organization $organization */
        public readonly organization $organization,
    ) {
    }
}