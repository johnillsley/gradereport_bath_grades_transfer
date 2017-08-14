<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../../config.php');
require_once $CFG->dirroot . '/grade/report/transfer/lib.php';
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->dirroot . '/grade/lib.php';
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
$transfer_report = new \gradereport_transfer\transfer_report($courseid, $gpr, $context, $mappingid);
if ($confirmtransfer == 1 && !empty($dotransfer)) {
    //$transfer_list = $transfer_report->get_transfer_list($dotransfer);
    //Create a queue event
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
    //come back to the user saying , grade is being processed !
    $transfer_status = new \gradereport_transfer\output\transfer_status($users[0], 'queued', null, "Added to ADHOC QUEUE");
    echo json_encode($transfer_status);
    //$transfer_outcomes = $transfer_report->do_transfers($users);
    //if (!empty($transfer_outcomes)) {
    //    echo json_encode($transfer_outcomes);
    //

}






