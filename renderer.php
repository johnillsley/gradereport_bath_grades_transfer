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
 * Renderer for the grade transfer report
 *
 * @package    grade_report_bath_transfer
 * @uses       plugin_renderer_base
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2017 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/tablelib.php');

class gradereport_transfer_renderer extends plugin_renderer_base
{

    /**
     * @boolean Bulk action user controls required on the report page.
     */
    public $bulkactions = false;

    /**
     * @boolean valid mapping is set to false if a mapping is selected but has expired.
     */
    public $validmapping = true;

    /**
     * Output of the summary details for the selected mapping
     * @param transfer report object $transferreport
     * @return string
     */
    public function selected_mapping_overview($transferreport) {
        global $CFG, $DB, $OUTPUT;
        $editpageurl = $CFG->wwwroot . '/course/modedit.php?update=' . $transferreport->selected->coursemoduleid;
        $gradespageurl = $CFG->wwwroot . '/mod/' .
            $transferreport->selected->moodle_activity_type .
            '/view.php?id=' . $transferreport->selected->coursemoduleid .
            '&action=grading';
        $dotransfersurl = $CFG->wwwroot .
            '/grade/report/transfer/index.php?id=' .
            $transferreport->selected->course .
            '&dotransfer=all&mappingid=' . $transferreport->id;

        $usermodifier = $DB->get_record('user', array('id' => $transferreport->selected->modifierid));
        $useraction = ($transferreport->selected->timecreated == $transferreport->selected->timemodified) ? get_string('createdby', 'question') . ' ' : get_string('lastmodifiedby', 'question') . ' ';
        $activityprogress = $transferreport->get_progress();

        $warning = (!empty($transferreport->selected->locked)) ? ' <span class="label label-warning">' . get_string('locked', 'grades') . '</span>' : '';

        // Current status indicator
        if ($this->validmapping === false) { // TODO - USE CLASS IN LOCAL PLUGIN TO CHECK IF MAPPING IS VALID
            // Transfer mapping no longer valid.
            $status = '<span class="label label-danger">' . get_string('mappingnotvalid', 'gradereport_transfer') . '</span> ';
            $status .= '<strong><a href="' . $editpageurl . '">' .
                get_string('reconfiguremapping', 'gradereport_transfer') . '</a></strong>';
            $warning .= ' <span class="label label-danger">' . get_string('thisnolongerexists', 'gradereport_transfer') . '</span>';
        } else if (empty($transferreport->selected->samis_assessment_end_date)) {
            // Transfer time has not been specified.
            $status = '<span class="label label-warning">' . get_string('transfernotscheduled', 'gradereport_transfer') . '</span>';
            $status .= '<br/>' . get_string('youcaneither', 'gradereport_transfer');
            $status .= ' <strong><a href="' . $editpageurl . '">' .
                get_string('scheduletransfer', 'gradereport_transfer') . '</a></strong>';
            $status .= get_string('triggermanually', 'gradereport_transfer');
            $status .= '<br/><a class="btn btn-default" href="' . $dotransfersurl . '">' .
                get_string('transferall', 'gradereport_transfer') . '</a>';
        } else if ($transferreport->selected->samis_assessment_end_date > time()) {
            // Transfer will occur in the future.
            $status = get_string('transferscheduled', 'gradereport_transfer') .
                ' <strong>' . userdate($transferreport->selected->samis_assessment_end_date) . '</strong>';
        } else {
            // Transfer has already occurred.
            $status = get_string('transfercompleted', 'gradereport_transfer') .
                ' <strong>' . userdate($transferreport->selected->samis_assessment_end_date) . '</strong>';
        }

        // Build table.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable';

        $table->data[] = array(
            get_string('mappingitem', 'gradereport_transfer') . $OUTPUT->help_icon('samis_assessment_name', 'gradereport_transfer'),
            '<strong>' . $transferreport->selected->samis_assessment_name . '<strong>' . $warning
        );
        $table->data[] = array(
            get_string('mappingreference', 'gradereport_transfer') . $OUTPUT->help_icon('samis_code', 'gradereport_transfer'),
            $transferreport->selected->samisassessmentid
        );
        $table->data[] = array(
            get_string('academicyear', 'gradereport_transfer'),
            $transferreport->selected->academicyear
        );
        $table->data[] = array(
            get_string('mappingcategory', 'gradereport_transfer'),
            $transferreport->selected->periodslotcode
        );
        $table->data[] = array(
            get_string('moodleactivitytype', 'gradereport_transfer') .
            $OUTPUT->help_icon('moodle_activity_type', 'gradereport_transfer'),
            $transferreport->selected->moodle_activity_type
        );
        $table->data[] = array(
            get_string('moodleactivityname', 'gradereport_transfer') .
            $OUTPUT->help_icon('moodle_activity_name', 'gradereport_transfer'),
            '<strong>' . $transferreport->selected->moodle_activity_name .
            '</strong> (<a href="' . $editpageurl . '">' . get_string('editsettings') . '</a>)'
        );
        $table->data[] = array(
            get_string('moodleactivitycompletion', 'gradereport_transfer') .
            $OUTPUT->help_icon('moodle_activity_completion', 'gradereport_transfer'),
            'Currently ' . $activityprogress->graded . ' out of
                ' . $activityprogress->total . ' have been graded,
                ' . $activityprogress->transferred . ' have been transferred to SAMIS.
                (<strong><a href="' . $gradespageurl . '">click here to see current grades</a></strong>)'
        );
        $table->data[] = array(
            get_string('transferstatus', 'gradereport_transfer') . $OUTPUT->help_icon('transfer_status', 'gradereport_transfer'),
            $status
        );
        $table->data[] = array(
            get_string('mappingdetails', 'gradereport_transfer') .
            $OUTPUT->help_icon('transfer_mapping_details', 'gradereport_transfer'),
            $useraction . fullname($usermodifier) . " on " . userdate($transferreport->selected->timemodified)
        );

        return html_writer::table($table);
    }

    /**
     * Output of the all previous and future individual grade transfers for the selected mapping
     * @param transfer report object $transferreport
     * @return string
     */
    public function grade_transfer_table($transferreport) {
        global $PAGE, $OUTPUT, $USER, $CFG, $DB;

        $table = new flexible_table('user-grade-transfer-' . $PAGE->course->id);

        $tablecolumns = array();
        $tableheaders = array();

        $tablecolumns[] = 'select';
        $tableheaders[] = get_string('select');

        $tablecolumns[] = 'userpic';
        $tableheaders[] = get_string('userpic');

        $tablecolumns[] = 'fullname';
        $tableheaders[] = get_string('fullnameuser');

        $tablecolumns[] = 'currentgrade';
        $tableheaders[] = get_string('grade');

        $tablecolumns[] = 'timegraded';
        $tableheaders[] = get_string('lastgraded', 'gradereport_transfer');

        $tablecolumns[] = 'transferredgrade';
        $tableheaders[] = get_string('transferredgrade', 'gradereport_transfer');

        $tablecolumns[] = 'timetransferred';
        $tableheaders[] = get_string('transferstatus', 'gradereport_transfer');

        $tablecolumns[] = 'transfernow';
        $tableheaders[] = get_string('dotransfernow', 'gradereport_transfer');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($PAGE->url->out());

        $table->sortable(true, 'lastname', SORT_ASC);
        $table->sortable(true, 'firstname', SORT_ASC);
        $table->sortable(true, 'timegraded', SORT_DESC);
        $table->sortable(true, 'timetransferred', SORT_DESC);

        $table->no_sorting('select');
        $table->no_sorting('currentgrade');
        $table->no_sorting('transferredgrade');
        $table->no_sorting('transfernow');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'gradetransfers');
        $table->set_attribute('class', 'generaltable generalbox');

        $table->set_control_variables(array(
            TABLE_VAR_SORT => 'ssort',
            TABLE_VAR_HIDE => 'shide', // What does this do?
            TABLE_VAR_SHOW => 'sshow', // What does this do?
            TABLE_VAR_IFIRST => 'sifirst',
            TABLE_VAR_ILAST => 'silast',
            TABLE_VAR_PAGE => 'spage'
        ));

        $table->setup();
        $table->is_persistent(true);
        $table->initialbars(true);

        $gradelist = $transferreport->user_list($table);
        $table->pagesize($transferreport->perpage, $transferreport->matchcount);

        if ($gradelist->valid()) {

            foreach ($gradelist as $grade) {

                $user = $DB->get_record('user', array('id' => $grade->userid));

                $checkbox = '';
                $transfernow = '';

                if ($grade->outcomeid != 1) {
                    // The grade has not been succesfully transferred yet.
                    if (!empty($grade->outcomeid > 0)) {
                        // Transfer previously failed.
                        $transferstatus = '<span class="label label-danger">' .
                            get_string('transferfailed', 'gradereport_transfer') . '</span>';
                        $transferstatus .= " ($grade->transfer_outcome)";
                    } else {
                        // Transfer not attempted yet.
                        $transferstatus = '<span class="label label-warning">' .
                            get_string('transferpending', 'gradereport_transfer') . '</span>';
                    }
                    if (!empty($grade->finalgrade) && $grade->rawgrademax == MAX_GRADE && $this->validmapping) {
                        // Create transfer button and checkbox if there is a grade to transfer.

                        $buttonurl = $CFG->wwwroot . '/grade/report/transfer/index.php?id=' .
                            $PAGE->course->id .
                            '&mappingid=' .
                            $transferreport->id .
                            '&dotransfer=' .
                            $grade->userid .
                            '&returnto=' . s($PAGE->url->out(false));
                        $transfernow = '<a href="' . $buttonurl . '" class="btn btn-default">' .
                            get_string('transfergrade', 'gradereport_transfer') . '<a/>';
                        $checkbox = '<input type="checkbox" class="usercheckbox" name="user' . $grade->userid . '" />';
                        $this->bulkactions = true;
                    }
                    $gradetransferred = "";
                } else {
                    // The grade transfer has been successful.
                    $transferstatus = get_string('transferredon', 'gradereport_transfer') . ' ' . userdate($grade->timetransferred);
                    $gradetransferred = $grade->gradetransferred;
                }

                $timegraded = (empty($grade->timegraded)) ? get_string('notgraded', 'question') : userdate($grade->timegraded);

                $context = context_course::instance($PAGE->course->id);
                $usercontext = context_user::instance($user->id);

                if ($piclink = ($USER->id == $user->id || has_capability('moodle/user:viewdetails', $context)
                    || has_capability('moodle/user:viewdetails', $usercontext))
                ) {
                    $profilelink = '<strong><a href="' . $CFG->wwwroot . '/user/view.php?id=' .
                        $user->id . '&course=' . $PAGE->course->id . '">' . fullname($user) .
                        '</a></strong>';
                } else {
                    $profilelink = '<strong>' . fullname($user) . '</strong>';
                }

                $data = array();
                $data[] = $checkbox;
                $data[] = $OUTPUT->user_picture($user, array('size' => 35, 'courseid' => $PAGE->course->id));
                $data[] = $profilelink;
                $data[] = $this->display_grade($grade);
                $data[] = $timegraded;
                $data[] = $gradetransferred;
                $data[] = $transferstatus;
                $data[] = $transfernow;
                $table->add_data($data);
            }
        }
        $gradelist->close();

        $table->print_html();
    }

    /**
     * Output of options for bulk transfer operations available to the user
     * @return string
     */
    public function table_bulk_actions() {
        global $PAGE;

        // Bulk actions at bottom of table.
        $module = array('name' => 'core_user', 'fullpath' => '/user/module.js');
        $PAGE->requires->js_init_call('M.core_user.init_participation', null, false, $module);

        $output = "";
        $output .= '<br /><div class="buttons">';
        $output .= '<input type="button" id="checkall" value="' . get_string('selectall') . '" /> ';
        $output .= '<input type="button" id="checknone" value="' . get_string('deselectall') . '" /> ';

        // Print "Remove ability to do single transfers until after bulk transfer has been attemped?? - maybe ok to do this!";.

        $displaylist = array();
        $displaylist[$PAGE->url->out()] = get_string('transfergrades', 'gradereport_transfer');

        $output .= html_writer::tag('label', get_string("withselectedusers"), array('for' => 'formactionid'));
        $output .= html_writer::select($displaylist, 'formaction', '', array('' => 'choosedots'), array('id' => 'formactionid'));

        $output .= '<input type="hidden" name="id" value="' . $PAGE->course->id . '" />';
        $output .= '<noscript style="display:inline">';
        $output .= '<div><input type="submit" value="' . get_string('ok') . '" /></div>';
        $output .= '</noscript>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Output of search form for finding users by name in the grade transfer table
     * @param transfer report object $transferreport
     * @param moodle_url object $baseurl - additional form parameters that are needed to return to the correct mapping
     * @return string
     */
    public function table_name_search_form($transferreport, $baseurl) {
        global $OUTPUT, $PAGE;

        $totalcount = $transferreport->totalcount;
        $matchcount = $transferreport->matchcount;
        $search = $transferreport->search;
        $perpage = $transferreport->perpage;

        // Show a search box if all participants don't fit on a single screen.
        $output = "";
        if ($matchcount > $perpage || !empty($search)) {
            $output .= '<form action="index.php" class="searchform"><div>';
            $output .= '<input type="hidden" name="id" value="' . $PAGE->course->id . '" />';
            $output .= '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
            $output .= '<input type="hidden" name="mappingid" value="' . $transferreport->id . '" />';
            $output .= '<label for="search">' . get_string('search', 'search') . ' </label>';
            $output .= '<input type="text" id="search" name="search" value="' .
                s($search) . '" />&nbsp;<input type="submit" value="' .
                get_string('search') . '" />';
            $output .= '</div></form>' . "\n";
        }
        $perpageurl = clone($baseurl);
        $perpageurl->remove_params('perpage');

        if ($perpage == SHOW_ALL_PAGE_SIZE) {
            $perpageurl->param('perpage', DEFAULT_PAGE_SIZE);
            $output .= $OUTPUT->container(
                html_writer::link($perpageurl,
                    get_string('showperpage', '', DEFAULT_PAGE_SIZE)), array(), 'showall');

        } else if ($matchcount > 0 && $perpage < $matchcount) {
            $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
            $output .= $OUTPUT->container(
                html_writer::link($perpageurl,
                    get_string('showall', '', $matchcount)), array(), 'showall');
        }
        return $output;
    }

    /**
     * Output of confirmation list of transfers that have been selected for transfer
     * @param transfer report object $transferreport
     * @param array $transferlist
     * @param string $dotransfer
     * @return string
     */
    public function confirm_transfers($transferreport, $transferlist, $dotransfer) {
        global $DB, $PAGE, $OUTPUT;
        $willbetransferredcount = $nogradetotransfercount = 0;
        $confirmlist = $transferreport->confirm_list($transferlist);
        $table = new html_table();
        $table->id = 'confirm_transfer_table';
        $table->attributes['class'] = 'generaltable table-bordered';
        $table->head = array(
            get_string('fullnameuser'),
            get_string('grade'),
            get_string('lastgraded', 'gradereport_transfer'),
            get_string('transferstatus', 'gradereport_transfer') . $OUTPUT->help_icon('transfer_status', 'gradereport_transfer')
        );
        foreach ($confirmlist as $confirmitem) {
            $user = $DB->get_record('user', array('id' => $confirmitem->userid));
            $graded = (empty($confirmitem->timegraded)) ? get_string('notgraded', 'question') : userdate($confirmitem->timegraded);
            if ($confirmitem->outcomeid == 1) {
                $status = '<span class="label label-warning transfer_status">' .
                    get_string('alreadytransferred', 'gradereport_transfer') . '</span>';
            } else if (empty($confirmitem->finalgrade)) {
                $nogradetotransfercount++;
                $status = '<span class="label label-warning transfer_status">' .
                    get_string('nogradetotransfer', 'gradereport_transfer') . '</span>';
            } else if ($confirmitem->rawgrademax != MAX_GRADE) {
                $status = '<span class="label label-danger transfer_status">' .
                    get_string('wrongmaxgrade', 'gradereport_transfer') . '</span>';
            } else {
                $willbetransferredcount++;
                $status = '<span class="label label-success transfer_status">' .
                    get_string('willbetransferred', 'gradereport_transfer') . '</span>';
            }
            $loadingdiv = "<div class='loadingDiv' style='display: none;'>
<img width='32' height='32' src='images/Spinner.gif'/></div>$status";
            // Dont show if grade is already transferred.
            /*if ($confirmitem->outcomeid != 1) {*/
            $row = new html_table_row(array(
                fullname($user),
                $this->display_grade($confirmitem),
                $graded,
                $loadingdiv
            ));
            $row->attributes = array("class" => "", "data-moodle-user-id" => $user->id);
            $table->data[] = $row;
            /*}*/
        }

        $output = "<div class=\"spotlight spotlight-v2\">
<i class=\"fa fa-file-text fa-4x pull-left\" style=\"color:#38b9ec;\"></i>
<h3>" . $transferreport->selected->samis_assessment_name . "</h3>
<p>You have chosen to transfer the grades listed below.</p>
<p>Click the <span style= 'font-weight:bold' class='text-success'>
Proceed with data transfer</span> button to complete the request or
 <span style= 'font-weight:bold' class='text-danger'>Cancel</span> to cancel the request and return to the previous screen.<br></p>
<ul class=\"list-style-1 colored\">
<li>Will be transferred: <span class=\"badge\">$willbetransferredcount</span></li>
<li>No grade to transfer: <span class=\"badge\"> $nogradetotransfercount</span></li>
</ul>
</div>";
        $output .= html_writer::table($table);

        $output .= '<form action="index.php" method="post" id="transferconfirmed">';
        $output .= '<input type="hidden" name="confirmtransfer" value="1" />';
        $output .= '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        $output .= '<input type="hidden" name="dotransfer" value="' . $dotransfer . '" />';
        $output .= '<input type="hidden" name="id" value="' . $PAGE->course->id . '" />';
        $output .= '<input type="hidden" name="mappingid" value="' . $transferreport->id . '" />';
        if ($dotransfer == "selected") {
            foreach ($transferlist as $userid) {
                //$output .= '<input type="hidden" name="user'.$userid.'" value="on" />';
                //$output .= '<input type="hidden" name="user[]" value="' . $userid . '" />';
            }
        }
        // $output .= '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />'; // TODO value.

        $output .= '<button class="btn btn-success" id = "proceed_grade_transfer" type="submit">' .
            get_string('proceedwithtransfer', 'gradereport_transfer') . '</button>';
        $output .= ' <a id = "cancel_grade_transfer" href="javascript:history.back()" class="btn btn-danger">' .
            get_string('canceltransfer', 'gradereport_transfer') . '</a>';
        $output .= '</form>';
        return $output;
    }

    /**
     * Output of formatted grade
     * @param object $grade
     * @return string
     */
    private function display_grade($grade) {

        $maxgrade = round($grade->rawgrademax);
        $maxdisplay = ($maxgrade == MAX_GRADE) ? $maxgrade : '<span class="max_grade_warning">' . $maxgrade . '</span>';
        return (!empty($grade->finalgrade)) ? round($grade->finalgrade) . ' / ' . $maxdisplay : '';
    }

    public function render_transfer_status(\templatable $transferstatus) {
        global $DB;
        $data = $transferstatus->export_for_template($this);
        $data->fullname = fullname($DB->get_record('user', ['id' => $data->userid]));
        if (($data->status == 'failure') && isset($data->reason)) {
            return $this->render_from_template('gradereport_transfer/transfer_failed', $data);
        } else {
            return $this->render_from_template('gradereport_transfer/transfer_success', $data);
        }

    }
}