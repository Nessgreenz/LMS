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

namespace core_reportbuilder\event;

use core\event\base;
use core_reportbuilder\local\models\report;
use moodle_url;

/**
 * Report builder custom report updated event class.
 *
 * @package     core_reportbuilder
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string    name:      The name of the report
 *      - string    source:    The report source class
 * }
 */
class report_updated extends base {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = report::TABLE;
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Creates an instance from a report object
     *
     * @param report $report
     * @return self
     */
    public static function create_from_object(report $report): self {
        $eventparams = [
            'context'  => $report->get_context(),
            'objectid' => $report->get('id'),
            'other' => [
                'name'     => $report->get('name'),
                'source'   => $report->get('source'),
            ]
        ];
        $event = self::create($eventparams);
        $event->add_record_snapshot($event->objecttable, $report->to_record());
        return $event;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('reportupdated', 'core_reportbuilder');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated the custom report with id '$this->objectid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/reportbuilder/edit.php', ['id' => $this->objectid]);
    }
}
