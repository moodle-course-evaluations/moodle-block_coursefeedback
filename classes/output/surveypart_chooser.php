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

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\surveypart;
use core\output\bootstrap_renderer;
use core\output\named_templatable;
use core\output\renderable;
use core\output\renderer_base;

/**
 * Renders a select over available global and organization-level survey parts.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class surveypart_chooser implements named_templatable, renderable {

    /**
     * Constructor.
     *
     * @param array $surveyparts
     * @param int|null $selectedid
     * @param organization $organization
     */
    public function __construct(
        /** @var surveypart[] */
        private readonly array $surveyparts,
        /** @var int|null */
        private readonly ?int $selectedid,
        /** @var organization */
        private readonly organization $organization
    ) {
    }

    #[\Override]
    public function get_template_name(renderer_base|bootstrap_renderer $renderer): string {
        return "block_coursefeedback/surveypart_chooser";
    }

    #[\Override]
    public function export_for_template(renderer_base|bootstrap_renderer $output): array {
         $result = [
            'org_name' => $this->organization->get('name'),
         ];

         foreach ($this->surveyparts as $surveypart) {
             $sp_context = [
                 'id' => $surveypart->get('id'),
                 'name' => $surveypart->get('name'),
             ];
             if ($this->selectedid && $this->selectedid === $surveypart->get('id')) {
                 $sp_context['selected'] = true;
             }

             if (!$surveypart->get('organizationid')) {
                 $result['global_surveyparts'][] = $sp_context;
             } else if ($surveypart->get('organizationid') === $this->organization->get('id')) {
                 $result['organization_surveyparts'][] = $sp_context;
             }
         }

         if (!empty($result['global_surveyparts'])) {
             $result['has_global_surveyparts'] = true;
         }
         if (!empty($result['organization_surveyparts'])) {
             $result['has_organization_surveyparts'] = true;
         }

         return $result;
    }
}
