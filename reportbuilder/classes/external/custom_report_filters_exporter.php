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

declare(strict_types=1);

namespace core_reportbuilder\external;

use renderer_base;
use core\external\exporter;
use core_reportbuilder\local\report\base;
use core_reportbuilder\local\models\filter;
use core_reportbuilder\output\filter_heading_editable;

/**
 * Custom report filters exporter class
 *
 * @package     core_reportbuilder
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_report_filters_exporter extends exporter {

    /**
     * Return a list of objects that are related to the exporter
     *
     * @return array
     */
    protected static function define_related(): array {
        return [
            'report' => base::class,
        ];
    }

    /**
     * Return the list of additional properties for read structure and export
     *
     * @return array[]
     */
    protected static function define_other_properties(): array {
        return [
            'hasavailablefilters' => [
                'type' => PARAM_BOOL,
            ],
            'availablefilters' => [
                'type' => [
                    'optiongroup' => [
                        'type' => [
                            'text' => ['type' => PARAM_TEXT],
                            'values' => [
                                'type' => [
                                    'value' => ['type' => PARAM_TEXT],
                                    'visiblename' => ['type' => PARAM_TEXT],
                                ],
                                'multiple' => true,
                            ],
                        ],
                    ],
                ],
                'multiple' => true,
                'optional' => true
            ],
            'hasactivefilters' => [
                'type' => PARAM_BOOL,
            ],
            'activefilters' => [
                'type' => [
                    'id' => ['type' => PARAM_INT],
                    'heading' => ['type' => PARAM_TEXT],
                    'headingeditable' => ['type' => PARAM_RAW],
                    'sortorder' => ['type' => PARAM_INT],
                    'movetitle' => ['type' => PARAM_TEXT],
                    'entityname' => ['type' => PARAM_TEXT],
                ],
                'multiple' => true,
                'optional' => true
            ],
            'helpicon' => [
                'type' => PARAM_RAW,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output): array {
        /** @var base $report */
        $report = $this->related['report'];

        // Current filters added to the report.
        $filters = filter::get_filter_records($report->get_report_persistent()->get('id'), 'filterorder');
        $filteridentifiers = array_map(static function(filter $filter): string {
            return $filter->get('uniqueidentifier');
        }, $filters);

        $availablefilters = $activefilters = [];

        // Populate available filters.
        foreach ($report->get_filters() as $filter) {
            if (in_array($filter->get_unique_identifier(), $filteridentifiers)) {
                continue;
            }

            $entityname = $filter->get_entity_name();
            if (!array_key_exists($entityname, $availablefilters)) {
                $availablefilters[$entityname] = [
                    'optiongroup' => [
                        'text' => $report->get_entity_title($entityname)->out(),
                        'values' => [],
                    ],
                ];
            }

            $availablefilters[$entityname]['optiongroup']['values'][] = [
                'value' => $filter->get_unique_identifier(),
                'visiblename' => $filter->get_header(),
            ];
        }

        // Populate active filters.
        $filterinstances = $report->get_filter_instances();
        foreach ($filters as $filter) {
            $filterinstance = $filterinstances[$filter->get('uniqueidentifier')] ?? null;
            if ($filterinstance === null) {
                continue;
            }

            $entityname = $filterinstance->get_entity_name();
            $displayvalue = $filterinstance->get_header();
            $editable = new filter_heading_editable($filter->get('id'));

            $activefilters[] = [
                'id' => $filter->get('id'),
                'entityname' => $report->get_entity_title($entityname)->out(),
                'heading' => $displayvalue,
                'headingeditable' => $editable->render($output),
                'sortorder' => $filter->get('filterorder'),
                'movetitle' => get_string('movefilter', 'core_reportbuilder', $displayvalue),
            ];
        }

        return [
            'hasavailablefilters' => !empty($availablefilters),
            'availablefilters' => array_values($availablefilters),
            'hasactivefilters' => !empty($activefilters),
            'activefilters' => $activefilters,
            'helpicon' => $output->help_icon('filters', 'core_reportbuilder'),
        ];
    }
}
