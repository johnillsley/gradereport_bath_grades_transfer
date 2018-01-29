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
defined('MOODLE_INTERNAL') || die();
define('AJAX_SCRIPT', true);
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/grade/report/transfer/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/transfer/classes/task/process_grade.php');
$timenow = time();
require_sesskey();

// Get submitted parameters.
$confirmtransfer = required_param('confirmtransfer', PARAM_INT);
$action = optional_param('action', '', PARAM_RAW);
$dotransfer = required_param('dotransfer', PARAM_TEXT);
$courseid = required_param('id', PARAM_INT);
$mappingid = required_param('mappingid', PARAM_INT);
$users = optional_param('users', 0, PARAM_INT);
$context = context_course::instance($courseid);
require_login($courseid);
$gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'transfer', 'courseid' => $courseid));
$transferreport = new \gradereport_transfer\transfer_report($courseid, $gpr, $context, $mappingid);
if (isset($mappingid)) {
    $assessmentmapping = \local_bath_grades_transfer_assessment_mapping::get($mappingid, true);
}
if ($action == 'grade_struct_exists') {
    // Check that the grade structure exists.
    if (isset($mappingid)) {
        $assessmentmapping = \local_bath_grades_transfer_assessment_mapping::get($mappingid, true);
        if (isset($assessmentmapping->lookup) && $objlookup = $assessmentmapping->lookup) {
            $lookup = \local_bath_grades_transfer_assessment_lookup::get_by_id($objlookup->id);
            $assessmentgrades = new local_bath_grades_transfer_assessment_grades();
            $gradestructure = $assessmentgrades->get_grade_strucuture_samis($lookup);
            if (empty($gradestructure)) {
                $transferstatus = new \gradereport_transfer\output\transfer_status
                (null, 'grade_struct_empty', null, "GRADE STRUCTURE IS EMPTY");
                // Trigger an event.
                $event = \local_bath_grades_transfer\event\missing_samis_grade_structure::create(
                    array(
                        'context' => $context,
                        'courseid' => $courseid,
                    )
                );
                $event->trigger();
                echo json_encode($transferstatus);
            } else {
                $transferstatus = new \gradereport_transfer\output\transfer_status
                (null, 'grade_struct_ok', null, "GRADE STRUCTURE PRESENT");
                echo json_encode($transferstatus);
            }
        }
    }
    die;
}
if ($confirmtransfer == 1 && !empty($dotransfer)) {
    // Create a queue event.
    $event = \gradereport_transfer\event\grade_report_queue_grade_transfer::create(
        array(
            'context' => $context,
            'courseid' => $courseid,
            'relateduserid' => $users[0],
            'other' => array(
                'mappingid' => $mappingid,
                'users' => $users
            ),
        )
    );
    $event->trigger();

    // Log it as an outcome.
    $localgradestransferlog = new \local_bath_grades_transfer_log();
    $localgradestransferlog->userid = $users[0];
    $localgradestransferlog->gradetransfermappingid = $mappingid;
    $localgradestransferlog->timetransferred = time();
    $localgradestransferlog->gradetransferred = null;
    $localgradestransferlog->coursemoduleid = $assessmentmapping->coursemodule;
    $localgradestransferlog->outcomeid = GRADE_QUEUED;
    $localgradestransferlog->assessmentlookupid = $assessmentmapping->assessmentlookupid;
    $localgradestransferlog->save();

    // Come back to the user saying , grade is being processed !.
    $transferstatus = new \gradereport_transfer\output\transfer_status(
        $users[0], 'queued', null, get_string('gradequeued', 'gradereport_transfer'));
    echo json_encode($transferstatus);
}






