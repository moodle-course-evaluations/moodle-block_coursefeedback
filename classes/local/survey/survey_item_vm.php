<?php

namespace block_coursefeedback\local\survey;

abstract class survey_item_vm {


    public function __construct(
        public readonly string $type,
        public readonly ?string $if = null,
    ) {
    }
}