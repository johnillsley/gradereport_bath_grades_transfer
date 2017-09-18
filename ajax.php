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
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/grade/report/transfer/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/transfer/classes/task/process_grade.php');
$timenow = time();
require_sesskey();

// Get submitted parameters.
$confirmtransfer = required_param('confirmtransfer', PARAM_INT);
$dotransfer = required_param('dotransfer', PARAM_TEXT);
$courseid = required_param('id', PARAM_INT);
$mappingid = required_param('mappingid', PARAM_INT);
$users = required_param('users', PARAM_INT);
$context = context_course::instance($courseid);
require_login($courseid);
$gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'transfer', 'courseid' => $courseid));
$transferreport = new \gradereport_transfer\transfer_report($courseid, $gpr, $context, $mappingid);
if ($confirmtransfer == 1 && !empty($dotransfer)) {
    // Create a queue event.
    $event = \gradereport_transfer\event\grade_report_queue_grade_transfer::create(
        array(
            'context' => $context,
            'courseid' => $courseid,
            'other' => array(
                'mappingid' => $mappingid,
                'users' => $users
            ),
        )
    );
    $event->trigger();
    // Come back to the user saying , grade is being processed !.
    $transferstatus = new \gradereport_transfer\output\transfer_status($users[0], 'queued', null, "Added to ADHOC QUEUE");
    echo json_encode($transferstatus);
}






