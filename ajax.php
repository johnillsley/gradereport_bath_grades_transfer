<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../../config.php');
require_once $CFG->dirroot . '/grade/report/transfer/lib.php';
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->dirroot . '/grade/lib.php';
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
    $transfer_outcomes = $transfer_report->do_transfers($users);
    if (!empty($transfer_outcomes)) {
        echo json_encode($transfer_outcomes);
    }

}






