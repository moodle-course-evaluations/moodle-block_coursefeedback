<?php

namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization_semester;
use core\exception\coding_exception;

/**
 * TODO.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class semester_pair {

    /**
     * Constructor.
     *
     * @param evaluation_semester|null $semester
     * @param organization_semester|null $orgsem
     */
    public function __construct(
        /** @var evaluation_semester|null $semester */
        public readonly ?evaluation_semester $semester,
        /** @var organization_semester|null $orgsem */
        public readonly ?organization_semester $orgsem
    ) {
        if (!$this->semester && !$this->orgsem) {
            throw new coding_exception('$semester or $orgsem (or both) must be set.');
        }
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return $this->semester?->name ?? $this->orgsem?->get('semestername');
    }

    /**
     * @return bool
     */
    public function is_current(): bool {
        return $this->semester?->is_current ?? false;
    }
}