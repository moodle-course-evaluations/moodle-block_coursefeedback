<?php
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

/**
 * Breadcrumbs manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use context_system;
use moodle_url;
use navigation_node;

/**
 * Breadcrumbs manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class breadcrumbs_manager {

    /**
     * Create two navigation_nodes, because the first two navigation nodes are never shown?
     * @return navigation_node
     */
    public static function create_root() {
        global $PAGE;
        return $PAGE->navbar;
    }

    /**
     * Setup evaluation administration overview.
     * @return navigation_node
     */
    public static function setup_evaluation_admin_overview(): navigation_node {
        $node = self::create_root()->add(
            get_string('evaluationadministration', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/overview.php'),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Setup list of organizations breadcrumbs.
     * @return navigation_node
     */
    public static function setup_organizations(): navigation_node {
        global $CFG, $PAGE;
        if (has_capability('moodle/site:config', context_system::instance())) {
            require_once($CFG->libdir . '/adminlib.php');
            admin_externalpage_setup('block_coursefeedback_category_organization');
            return $PAGE->settingsnav->find_active_node();
        } else {
            if (permission_manager::can_do_any_evaluation_administration()) {
                $parent = self::setup_evaluation_admin_overview();
            } else {
                $parent = self::create_root();
            }
            $node = $parent->add(
                get_string('organizations', 'block_coursefeedback'),
                new moodle_url('/blocks/coursefeedback/organizations.php'),
            );
            $node->make_active();
            return $node;
        }
    }

    /**
     * Setup organization breadcrumbs.
     * @param organization $organization
     * @return navigation_node
     */
    public static function setup_organization(organization $organization): navigation_node {
        $parent = self::setup_organizations();
        $node = $parent->add(
            $organization->get('name'),
            new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $organization->get('id')]),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Setup organization settings breadcrumbs.
     * @param organization $organization
     * @return navigation_node
     */
    public static function setup_organization_settings(organization $organization): navigation_node {
        $parent = self::setup_organization($organization);
        $node = $parent->add(
            get_string('general_settings_and_permissions', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/organization_settings.php', ['id' => $organization->get('id')]),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Setup edit organization breadcrumbs.
     * @param organization|null $organization
     * @return navigation_node
     */
    public static function setup_edit_organization(?organization $organization): navigation_node {
        $parent = self::setup_organizations();
        $params = [];
        if ($organization) {
            $params['id'] = $organization->get('id');
        }
        $node = $parent->add(
            get_string($organization ? 'edit_organization' : 'new_organization', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/organization_edit.php', $params),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Setup edit default survey period for organization breadcrumbs.
     * @param organization $organization
     * @return navigation_node
     */
    public static function setup_organization_default_survey_period(organization $organization): navigation_node {
        $parent = self::setup_organization($organization);
        $node = $parent->add(
            get_string('edit_default_survey_period', 'block_coursefeedback'),
            new moodle_url(
                '/blocks/coursefeedback/organization_edit_default_survey_period.php',
                ['id' => $organization->get('id')],
            ),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Setup organization courses without evaluation breadcrumbs.
     * @param organization $organization
     * @return navigation_node
     */
    public static function setup_organization_courses_without_evaluation(organization $organization): navigation_node {
        $parent = self::setup_organization($organization);
        $node = $parent->add(
            get_string('list_of_courses_without_evaluation', 'block_coursefeedback'),
            new moodle_url(
                '/blocks/coursefeedback/organization_courses_without_evaluation.php',
                ['id' => $organization->get('id')],
            ),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Setup organization evaluations breadcrumbs.
     * @param organization $organization
     * @return navigation_node
     */
    public static function setup_organization_evaluations(organization $organization): navigation_node {
        $parent = self::setup_organization($organization);
        $node = $parent->add(
            get_string('list_of_evaluations', 'block_coursefeedback'),
            new moodle_url(
                '/blocks/coursefeedback/organization_evaluations.php',
                ['id' => $organization->get('id')],
            ),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Setup organization default surveypart breadcrumbs.
     * @param organization $organization
     * @return navigation_node
     */
    public static function setup_organization_default_surveypart(organization $organization): navigation_node {
        $parent = self::setup_organization($organization);
        $node = $parent->add(
            get_string('define_default_surveyparts', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/organization_default_surveypart.php', ['id' => $organization->get('id')]),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the questionnaires (survey parts) node, either for global questionnaires or org-scoped ones.
     *
     * @param ?organization $organization
     * @return navigation_node
     */
    public static function setup_questionnaires(?organization $organization = null): navigation_node {
        global $CFG, $PAGE;
        if ($organization) {
            $parent = self::setup_organization($organization);
            $params = ['organizationid' => $organization->get('id')];
        } else {
            if (has_capability('moodle/site:config', context_system::instance())) {
                require_once($CFG->libdir . '/adminlib.php');
                admin_externalpage_setup('block_coursefeedback_category_survey');
                return $PAGE->settingsnav->find_active_node();
            } else {
                if (permission_manager::can_do_any_evaluation_administration()) {
                    $parent = self::setup_evaluation_admin_overview();
                } else {
                    $parent = self::create_root();
                }
            }
            $params = [];
        }

        $node = $parent->add(
            get_string('questionnaires', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/surveyparts.php', $params),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for a specific survey.
     *
     * @param surveypart $surveypart
     * @param organization|null $organization
     * @return navigation_node
     */
    public static function setup_questionnaire(surveypart $surveypart, ?organization $organization): navigation_node {
        $parent = self::setup_questionnaires($organization);
        $node = $parent->add(
            $surveypart->get('name'),
            new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $surveypart->get('id')]),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for editing a survey.
     *
     * @param surveypart|null $surveypart
     * @param organization|null $organization
     * @return navigation_node
     */
    public static function setup_edit_questionnaire(?surveypart $surveypart, ?organization $organization): navigation_node {
        $parent = self::setup_questionnaires($organization);
        $params = $surveypart ? ['id' => $surveypart->get('id')] : [];
        $node = $parent->add(
            get_string($surveypart ? 'edit_surveypart' : 'new_surveypart', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/surveypart_edit.php', $params),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for editing a surveyitem.
     *
     * @param surveypart $surveypart
     * @param surveyitem|null $surveyitem
     * @param organization|null $organization
     * @return navigation_node
     */
    public static function setup_edit_surveyitem(
        surveypart $surveypart,
        ?surveyitem $surveyitem,
        ?organization $organization
    ): navigation_node {
        $parent = self::setup_questionnaire($surveypart, $organization);
        $params = ['surveypartid' => $surveypart->get('id')];
        if ($surveyitem) {
            $params['id'] = $surveyitem->get('id');
        }
        $node = $parent->add(
            get_string($surveyitem ? 'edit_surveyitem' : 'new_surveyitem', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/surveyitem_edit.php', $params),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the navigation node for the scales overview.
     *
     * @param surveypart $surveypart
     * @param organization|null $organization
     * @return navigation_node
     */
    public static function setup_survey_scales(surveypart $surveypart, ?organization $organization = null): navigation_node {
        $parent = self::setup_questionnaire($surveypart, $organization);
        $node = $parent->add(
            get_string('scales', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/scales.php', ['surveypartid' => $surveypart->get('id')]),
        );
        $node->make_active();
        return $node;
    }

    /**
     * Sets up the edit scale navigation node.
     *
     * @param surveypart $surveypart
     * @param int|null $scaleid
     * @param organization|null $organization
     * @return navigation_node
     */
    public static function setup_edit_survey_scale(
        surveypart $surveypart,
        ?int $scaleid,
        ?organization $organization
    ): navigation_node {
        $parent = self::setup_questionnaire($surveypart, $organization);
        $params = ['surveyitemid' => $surveypart->get('id')];
        if ($scaleid) {
            $params['id'] = $scaleid;
        }
        $node = $parent->add(
            get_string($scaleid ? 'edit_scale' : 'new_scale', 'block_coursefeedback'),
            new moodle_url('/blocks/coursefeedback/scale_edit.php', $params),
        );
        $node->make_active();
        return $node;
    }
}
