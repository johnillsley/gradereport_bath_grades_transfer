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
defined('MOODLE_INTERNAL') || die;

class gradereport_transfer_observer
{
    public static function transfer_grade_queue(\gradereport_transfer\event\grade_report_queue_grade_transfer $event) {
        global $CFG;
        require_once($CFG->dirroot . '/grade/report/transfer/lib.php');
        require_once($CFG->dirroot . '/grade/report/transfer/classes/task/process_grade.php');

        $task = new \gradereport_transfer\task\process_grade();
        $task->set_custom_data(array(
            'courseid' => $event->courseid,
            'mappingid' => $event->other['mappingid'],
            'user' => $event->other['users'],
            'contextid' => $event->contextid,
            'eventtype' => 'grade_transfer'
        ));
        \core\task\manager::queue_adhoc_task($task);

    }
}