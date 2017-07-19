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

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/transfer/lib.php'; // Doesn't autoload
//require_once $CFG->dirroot.'/grade/report/transfer/classes/event/grade_report_viewed.php';
//require_once $CFG->dirroot.'/grade/report/transfer/classes/event/grade_report_starttransfer.php';
//require_once $CFG->dirroot.'/grade/report/transfer/classes/filter_form.php'; // SHOULD THIS AUTO LOAD??
require_once $CFG->dirroot.'/local/bath_grades_transfer/lib.php';

// Grade report transfer table constants
define('USER_SMALL_CLASS', 20);   // Below this is considered small.
define('USER_LARGE_CLASS', 200);  // Above this is considered large.
define('DEFAULT_PAGE_SIZE', 15);
define('SHOW_ALL_PAGE_SIZE', 5000);
define('MODE_USERDETAILS', 1);

// Grade report transfer outcome constants
// TODO - Probably need to remove the following constants as these are dealt with in the local plugin
/*
define('TRANSFER_SUCCESS', 1);
define('TRANSFER_NO_GRADE', 2);
define('TRANSFER_ERROR', 3);
define('GRADE_ALREADY_EXISTS', 4);
define('NOT_IN_MOODLE_COURSE', 5);
define('NOT_IN_SITS_STRUCTURE', 6);
define('GRADE_NOT_OUT_OF_100', 7);
*/
//define('MAX_GRADE', 100); #Already defined in local grades transfer plugin

$courseid        = required_param('id', PARAM_INT);
$mappingid       = optional_param('mappingid', 0, PARAM_INT);
$transferstatus  = optional_param('transferstatus', 0, PARAM_INT);
$year            = optional_param('year', 0, PARAM_INT);
$dotransfer      = optional_param('dotransfer', 0, PARAM_RAW);
$confirmtransfer = optional_param('confirmtransfer', 0, PARAM_INT);

// FOR TABLE DISPLAY - user/index.php
$perpage         = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT); // How many per page.
$search          = optional_param('search', '', PARAM_RAW);
$sifirst         = optional_param('sifirst', '', PARAM_RAW);
$silast          = optional_param('silast', '', PARAM_RAW);
$currentgroup    = 0;

$title = get_string('pluginname', 'gradereport_transfer');
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

/// basic access checks
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
if (has_capability('moodle/grade:viewall', $context)) {
    //ok - can view all course grades
    $access = true;
}
if (!$access) {
    // no access to grades!
    print_error('nopermissiontoviewgrades', 'error',  $CFG->wwwroot.'/course/view.php?id='.$course->id);
}
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'transfer', 'courseid'=>$course->id ));
//$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'overview', 'courseid'=>$course->id, 'userid'=>2));

$transfer_report = new \gradereport_transfer\transfer_report($course->id, $gpr, $context, $mappingid);
$transfer_report->get_mapping_options($course->id, $year);
if( !empty( $transfer_report->selected->expired ) ) $output->valid_mapping = false; // mapping has expired so output needs to know

// PROCESS GRADE TRANSFERS
if( $confirmtransfer == 1 && !empty($dotransfer) ) {
    // Log that transfer button has been clicked
    $event = \gradereport_transfer\event\grade_report_starttransfer::create(
        array(
            'context' => $context,
            'courseid' => $courseid,
            'other' => array('mappingid' => $mappingid, 'assessment_name' =>  $transfer_report->selected->samis_assessment_name ),
        )
    );
    $event->trigger();

    $transfer_list = $transfer_report->get_transfer_list($dotransfer);
    $transfer_outcomes = $transfer_report->do_transfers($transfer_list);
    //Show me the outcomes
    $outcome_output =  $output->render_transfer_status($transfer_outcomes);
     // do_transfer_mapping($mappingid,$transfer_list ); // TODO - in lib.php new \local_bath_grades_transfer();
   // redirect("index.php?id=".$course->id."&mappingid=".$transfer_report->id, "Transfers processed");
}

$buttons=false;
print_grade_page_head($course->id, 'report', 'transfer', $title, false, $buttons);

/// Show outcomes if any
if(!empty($outcome_output)){
    echo $outcome_output;
}
if( $confirmtransfer == 0 && !empty($dotransfer) ) {
    $transfer_list = $transfer_report->get_transfer_list($dotransfer);
    echo  get_string('transferconfirmheading', 'gradereport_transfer')."<h5>".$transfer_report->selected->samis_assessment_name."</h5>";
    echo $output->confirm_transfers($transfer_report,  $transfer_list, $dotransfer);
}
// END PROCESS GRADE TRANSFER

$grade_transfer = new \local_bath_grades_transfer();
$course_has_samis_code = $grade_transfer->samis_mapping_exists($course->id);
$course_has_samis_code = true; //TODO - remove this

if( empty($dotransfer) ) {

    if ($course_has_samis_code) {

        echo "<p class='alert alert-info'>" . get_string('onlymappedassessments', 'gradereport_transfer') . "</p>";

        // FILTER FORM
        // $transfer_report->get_mapping_options($course, $year);
        $params['course'] = $course;
        $params['years'] = $transfer_report->get_academic_year_options(); // TODO - NEED TO MOVE THIS ABOVE THIS FORM
        $params['mappingids'] = $transfer_report->external_assessment; // Use $transfer_report->moodle_activity for moodle list
        $params['selected_mapping'] = $mappingid;
        $params['transferstatus'] = $transfer_report->get_status_options();
        $params['selected_status'] = $transferstatus;
        $mform = new \gradereport_transfer\filter_form(null, $params, 'get');
        $mform->display();
        // END OF FILTER FORM

        if ($mappingid > 0) {

            // BEGINNING OF TRANSFER MAPPING OVERVIEW
            echo "<h5>" . get_string('transferoverview', 'gradereport_transfer') . "</h5>";
            echo $output->selected_mapping_overview($transfer_report);
            // END OF TRANSFER MAPPING OVERVIEW

            // BEGINNING OF INDIVIDUAL GRADE TRANSFER TABLE
            $transfer_report->perpage = $perpage;
            $transfer_report->search = $search;
            $transfer_report->sifirst = $sifirst;
            $transfer_report->silast = $silast;
            $transfer_report->transferstatus = $transferstatus;

            $module = array('name' => 'core_user', 'fullpath' => '/user/module.js');
            $PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);

            echo '<form action="index.php" method="post" id="participantsform">';
            echo '<div>';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="mappingid" value="'.$transfer_report->id.'" />';
            echo '<input type="hidden" name="dotransfer" value="selected" />';
            echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';

            echo "<h5>" . get_string('transferlog', 'gradereport_transfer') . " (" . get_string('transferstatus' . $transferstatus, 'gradereport_transfer') . ")</h5>";

            $output->grade_transfer_table($transfer_report);

            if ($output->bulk_actions && $output->valid_mapping) echo $output->table_bulk_actions();

            //echo '</div>';
            echo '</form>';

            echo $output->table_name_search_form($transfer_report, $baseurl);

            //echo '</div>';  // Userlist.
            // END OF INDIVIDUAL GRADE TRANSFER TABLE

            // Log that transfer mapping has been viewed
            $event = \gradereport_transfer\event\grade_report_viewed::create(
                array(
                    'context' => $context,
                    'courseid' => $courseid,
                    'other' => array('mappingid' => $mappingid, 'assessment_name' => $transfer_report->selected->samis_assessment_name),
                )
            );
            $event->trigger();
        }
    } else {
        echo get_string('courseisnotmapped', 'gradereport_transfer');
    }
}

echo $output->footer();