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
 * The gradebook transfer report - table functionality based around course participants report user/index.php
 *
 * @package    grade_report_bath_transfer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2017 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/transfer/lib.php'); // Doesn't autoload.
require_once($CFG->dirroot . '/local/bath_grades_transfer/lib.php');

// Grade report transfer table constants
define('USER_SMALL_CLASS', 20);   // Below this is considered small.
define('USER_LARGE_CLASS', 200);  // Above this is considered large.
define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);
define('MODE_USERDETAILS', 1);

$courseid = required_param('id', PARAM_INT);
$mappingid = optional_param('mappingid', 0, PARAM_INT);
$transferstatus = optional_param('transferstatus', 0, PARAM_INT);
$year = optional_param('year', 0, PARAM_INT);
$dotransfer = optional_param('dotransfer', 0, PARAM_RAW);
$confirmtransfer = optional_param('confirmtransfer', 0, PARAM_INT);

// FOR TABLE DISPLAY - user/index.php
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT); // How many per page.
$search = optional_param('search', '', PARAM_RAW);
$sifirst = optional_param('sifirst', '', PARAM_RAW);
$silast = optional_param('silast', '', PARAM_RAW);
$currentgroup = 0;
$action = optional_param('action', '', PARAM_RAW);
$title = get_string('pluginname', 'gradereport_transfer');
global $PAGE, $DB;
$PAGE->set_url('/grade/report/transfer/index.php'
    , array(
        'id' => $courseid,
        'mappingid' => $mappingid,
        'transferstatus' => $transferstatus,
        'year' => $year,
        'perpage' => $perpage,
        'search' => $search,
        'sifirst' => $sifirst,
        'silast' => $silast,
    ));

// Basic access checks.
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}

$baseurl = new \moodle_url('/grade/report/transfer/index.php', array(
    'id' => $course->id,
    'perpage' => $perpage,
    'mappingid' => $mappingid,
    'transferstatus' => $transferstatus,
    'year' => $year
));

require_login($course);
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$output = $PAGE->get_renderer('gradereport_transfer');

$context = context_course::instance($course->id);

require_capability('gradereport/transfer:view', $context);
$access = false;
$PAGE->requires->js_call_amd('gradereport_transfer/transfer_status', 'init', []);
// AMD call to display log entries.
$PAGE->requires->js_call_amd('gradereport_transfer/logentries', 'init', []);

if (has_capability('moodle/grade:viewall', $context)) {
    // Ok - can view all course grades.
    $access = true;
}

if (!$access) {
    // No access to grades!.
    print_error('nopermissiontoviewgrades', 'error', $CFG->wwwroot . '/course/view.php?id=' . $course->id);
}
$gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'transfer', 'courseid' => $course->id));

$transferreport = new \gradereport_transfer\transfer_report($course->id, $gpr, $context, $mappingid);
if ($action == 'download_log') {
    $transferreport->download_log($mappingid);
    die;
}
$transferreport->get_mapping_options($course->id, $year);
if (!empty($transferreport->selected->expired)) {
    $output->valid_mapping = false; // Mapping has expired so output needs to know.
}

// PROCESS GRADE TRANSFERS.
// TODO - Redundant as ajax.php is taking care of it ?.
if ($confirmtransfer == 1 && !empty($dotransfer)) {
    // Log that transfer button has been clicked.
    $event = \gradereport_transfer\event\grade_report_starttransfer::create(
        array(
            'context' => $context,
            'courseid' => $courseid,
            'other' => array('mappingid' => $mappingid, 'assessment_name' => $transferreport->selected->samis_assessment_name),
        )
    );
    $event->trigger();

    $transferlist = $transferreport->get_transfer_list($dotransfer);
    $transferoutcomes = $transferreport->do_transfers($transferlist);
    // Show me the outcomes.
    foreach ($transferoutcomes as $transferstatus) {
        echo $output->render_transfer_status($transferstatus);
    }
}

$buttons = false;
print_grade_page_head($course->id, 'report', 'transfer', $title, false, $buttons);

// Show outcomes if any.
if (!empty($outcomeoutput)) {
    echo $outcomeoutput;
}
if ($confirmtransfer == 0 && !empty($dotransfer)) {
    if (!$transferreport->is_blind_marking_enabled()) {
        $transferlist = $transferreport->get_transfer_list($dotransfer);
        echo $output->confirm_transfers($transferreport, $transferlist, $dotransfer);
    } else {
        echo "<div id='report_bath_transfer_blind' class=\"alert alert-danger\" role=\"alert\">
<i class=\"fa fa-3x fa-eye-slash\" aria-hidden=\"true\"></i><span> " . get_string('blind_marking_warning', 'gradereport_transfer') .
            "</span></div> ";
    }

}
// END PROCESS GRADE TRANSFER.

$gradetransfer = new \local_bath_grades_transfer();
$coursehassamiscode = $gradetransfer->samis_mapping_exists($course->id);
if (empty($dotransfer)) {

    if ($coursehassamiscode) {
        echo "<p class='alert alert-info'>" . get_string('onlymappedassessments', 'gradereport_transfer') . "</p>";
        if (!has_capability('gradereport/transfer:transfer', $context)) {
            echo "<p class='alert alert-warning'>" . get_string('nocapabilitytotransfer', 'gradereport_transfer') . "</p>";

        }
        // FILTER FORM.
        $params['course'] = $course;
        $params['years'] = $transferreport->get_academic_year_options(); // TODO - NEED TO MOVE THIS ABOVE THIS FORM
        $params['mappingids'] = $transferreport->externalassessment; // Use $transferreport->moodle_activity for moodle list.
        $params['selected_mapping'] = $mappingid;
        $params['transferstatus'] = $transferreport->get_status_options();
        $params['selected_status'] = $transferstatus;
        $mform = new \gradereport_transfer\filter_form(null, $params, 'get');
        $mform->display();
        // END OF FILTER FORM.

        if ($mappingid > 0) {

            // BEGINNING OF TRANSFER MAPPING OVERVIEW.
            echo "<h5>" . get_string('transferoverview', 'gradereport_transfer') . "</h5>";
            echo $output->selected_mapping_overview($transferreport);
            // END OF TRANSFER MAPPING OVERVIEW.

            // BEGINNING OF INDIVIDUAL GRADE TRANSFER TABLE.
            $transferreport->perpage = $perpage;
            $transferreport->search = $search;
            $transferreport->sifirst = $sifirst;
            $transferreport->silast = $silast;
            $transferreport->transferstatus = $transferstatus;

            if (!$transferreport->selected->is_blind_marking_turned_on || ($transferreport->selected->is_blind_marking_turned_on
                    && $transferreport->selected->revealidentities)
            ) {
                $module = array('name' => 'core_user', 'fullpath' => '/user/module.js');
                $PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);

                echo '<form action="index.php" method="post" id="participantsform">';
                echo '<div>';
                echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
                echo '<input type="hidden" name="mappingid" value="' . $transferreport->id . '" />';
                echo '<input type="hidden" name="dotransfer" value="selected" />';
                echo '<input type="hidden" name="returnto" value="' . s($PAGE->url->out(false)) . '" />';
                echo "<h5>" . get_string('transferlog', 'gradereport_transfer') .
                    " (" . get_string('transfer_status' . $transferstatus, 'gradereport_transfer') . ")</h5>";
                $output->grade_transfer_table($transferreport);
                if ($output->bulkactions && $output->validmapping) {
                    // ADD CAPABILITY HERE.
                    $context = context_course::instance($course->id);
                    if (has_capability('gradereport/transfer:transfer', $context)) {
                        echo $output->table_bulk_actions();
                    }

                }
                echo '</form>';

                echo $output->table_name_search_form($transferreport, $baseurl);
            } else {
                echo "<div id='report_bath_transfer_blind' class=\"alert alert-danger\" role=\"alert\">
<i class=\"fa fa-3x fa-eye-slash\" aria-hidden=\"true\"></i><span> " . get_string('blind_marking_warning', 'gradereport_transfer') .
                    "</span></div> ";
            }


            // END OF INDIVIDUAL GRADE TRANSFER TABLE.

            // Log that transfer mapping has been viewed.
            $event = \gradereport_transfer\event\grade_report_viewed::create(
                array(
                    'context' => $context,
                    'courseid' => $courseid,
                    'other' => array('mappingid' => $mappingid,
                        'assessment_name' => $transferreport->selected->samis_assessment_name),
                )
            );
            $event->trigger();
        }
    } else {
        echo get_string('courseisnotmapped', 'gradereport_transfer');
    }
}
echo $output->footer();