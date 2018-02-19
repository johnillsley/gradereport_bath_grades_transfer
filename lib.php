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
 * Defines grade_report_bath_transfer class which contains all library functions
 *
 * @package    grade_report_bath_transfer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2017 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transfer;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->dirroot . '/local/bath_grades_transfer/lib.php');
require_once($CFG->dirroot . '/local/bath_grades_transfer/classes/assessment_grades.php');
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Class providing core functionality for the grade transfer report
 * @uses grade_report
 * @package grade_report_bath_transfer
 */
class transfer_report extends \grade_report
{
    /**
     * @integer mappingid for the report - id in table local_bath_grades_mapping
     */
    public $id;
    /**
     * @var FROM part of SQL used by class methods.
     */
    private $sqlfrom;
    private $sqlreadytotransfer;
    /**
     * @var FROM parameters for all SQL in class.
     */
    private $sqlparams = array();
    /**
     * @var the count of number of users on the transfer log report after name search and initials filters are applied.
     */
    public $matchcount = 0;

    /**
     * @var the count of number of users on the transfer log report before name search and initials filters are applied.
     */
    public $totalcount = 0;

    /**
     * @array the currently mapped moodle activities on the course
     */
    public $moodleactivity = array();

    /**
     * @array the currently mapped external assessments on the course
     */
    public $externalassessment = array();

    /**
     * @object the currently selected mapping to be displayed on the report
     */
    public $selected = null;

    /**
     * @integer the report filter value indicating status of transfers to be displayed
     */
    public $transferstatus;


    /**
     * transfer_report constructor.
     * @param int $courseid
     * @param object $gpr
     * @param string $context
     * @param int|null $mappingid
     */
    public function __construct($courseid, $gpr, $context, $mappingid) {
        parent::__construct($courseid, $gpr, $context);

        $this->id = $mappingid;
        $this->courseid = $courseid;
        $this->sqlfrom = "
        /***** get the grade transfer mapping *****/
        FROM {local_bath_grades_mapping} gm
        JOIN {local_bath_grades_lookup} gl
            ON gl.id = gm.assessmentlookupid
            -- AND gl.expired IS NULL  -- need to show transfer history if mapping becomes expired

       /***** join students that have equivalent sits mapping *****/
        JOIN {sits_mappings} sm
            ON sm.acyear = gl.academicyear
            AND sm.period_code = gl.periodslotcode
            AND sm.sits_code = gl.samisunitcode
            AND sm.active = 1
            AND sm.default_map = 1
        JOIN {sits_mappings_enrols} me ON me.map_id = sm.id
        JOIN {user_enrolments} ue ON ue.id = me.u_enrol_id -- PROBLEM WITH user_enrolments BEING REMOVED!!!
        JOIN {user} u ON u.id = ue.userid
        JOIN {role_assignments} ra
            ON ra.userid = u.id
            AND contextid = :contextid
            AND roleid = 5 /* student role */
            AND ra.id = me.ra_id
        /***** join moodle activity information relating to mapping including current grade *****/
        JOIN {course_modules} cm ON cm.id = gm.coursemodule
        JOIN {modules} mo ON mo.id = cm.module
        LEFT JOIN {grade_items} gi
            ON gi.itemmodule = mo.name
            AND gi.iteminstance = cm.instance
        LEFT JOIN {grade_grades} gg
            ON gg.itemid = gi.id
            AND gg.userid = ue.userid

        /***** get time of latest transfer log entry for each student enrolment *****/
        LEFT JOIN
        (
            SELECT
                userid
                , gradetransfermappingid
                , MAX( timetransferred ) AS timetransferred
            FROM {local_bath_grades_log}
            -- WHERE outcomeid != " . GRADE_ALREADY_EXISTS . "
            GROUP BY userid, gradetransfermappingid
        ) AS last_log
            ON last_log.userid = gg.userid
            AND last_log.gradetransfermappingid = gm.id

        /***** join outcome status *****/
        LEFT JOIN {local_bath_grades_log} log
            ON log.gradetransfermappingid = last_log.gradetransfermappingid
            AND log.userid = last_log.userid
            AND log.timetransferred = last_log.timetransferred
        LEFT JOIN {local_bath_grades_outcome} oc ON log.outcomeid = oc.id

        WHERE gm.id = :id
        ";
        $this->sqlparams['id'] = $this->id;
        $this->sqlparams['contextid'] = $context->id;

        $this->sqlreadytotransfer = "
        AND (log.outcomeid NOT IN (" . TRANSFER_SUCCESS . "," . GRADE_QUEUED . "," . GRADE_ALREADY_EXISTS . ")
        OR log.outcomeid IS NULL) -- already transferred or queued
        AND gg.finalgrade IS NOT NULL
        AND CEIL(gg.finalgrade) = gg.finalgrade
        AND gg.rawgrademax=" . MAX_GRADE;
    }

    /**
     * Handles form data sent by this report for this report. Abstract method to implement in all children.
     * @abstract
     * @param array $data
     * @return void
     */
    public function process_data($data) {

    }

    /**
     * Processes a single action against a category, grade_item or grade.
     * @param string $target Sortorder
     * @param string $action Which action to take (edit, delete etc...)
     * @return void
     */
    public function process_action($target, $action) {

    }

    // TODO - do we need academic year?

    /**
     * Gets all academic year options used within external assessments.
     * @return array $years - all distinct academic years that exist in
     */
    public function get_academic_year_options() {
        global $DB;

        $years = array();
        $options = $DB->get_records_sql(
            "SELECT DISTINCT academicyear FROM {local_bath_grades_lookup} ORDER BY academicyear DESC");
        foreach ($options as $option) {
            $years[substr($option->academicyear, 0, 4)] = $option->academicyear;
        }
        return $years;
    }

    /**
     * Gets all mapping options that have been created for the course
     * @param integer $courseid
     * @param string $year (optional)
     * @return void
     */
    public function get_mapping_options($courseid, $year = null) {
        global $DB;

        // Mapping lookups refreshed from cron on local plugin - so need to to refresh here!

        $params = array();
        $params[] = $courseid;

        if (!empty($year)) {
            $yearsql = 'AND SUBSTRING(gl.academic_year,1,4) = ?';
            $params[] = $year;
        } else {
            $yearsql = "";
        }

        $mappings = $DB->get_records_sql("
              SELECT
              gm.id
            , gm.timecreated
            , gm.timemodified
            , gm.modifierid
            , gm.locked
            , gm.samisassessmentenddate
            , gm.lasttransfertime
            , gl.id AS 'assessmentlookupid'
            , gl.samisassessmentid
            , gl.mabname AS 'samis_assessment_name'
            , gl.academicyear
            , gl.occurrence
            , gl.mabseq
            ,gl.astcode
            ,gl.mabperc
            ,gl.mabpnam
            , gl.periodslotcode
            , gl.expired
            , cm.course
            , cm.id AS 'coursemoduleid'
            , cm.instance
            , gm.activitytype AS 'moodle_activity_type'
            FROM {local_bath_grades_mapping} gm
            JOIN {local_bath_grades_lookup} gl ON gl.id = gm.assessmentlookupid
            JOIN {course_modules} cm ON cm.id = gm.coursemodule
            JOIN {sits_mappings} sm
              ON sm.sits_code = gl.samisunitcode
              AND sm.courseid = cm.course
              AND sm.default_map = 1
              AND sm.active = 1
              AND sm.acyear = gl.academicyear
              AND sm.period_code = gl.periodslotcode
            WHERE ( gl.expired IS NULL OR gl.expired = 0 OR
             EXISTS ( SELECT 1 FROM {local_bath_grades_log} l WHERE l.gradetransfermappingid = gl.id ) )
            /* grade lookup is current or if expired some tranfers happened that might be of interest to user */
            AND gm.expired = 0 -- to show non-expired mappings only
            AND cm.course = ? " . $yearsql,
            $params
        );

        // Create both SAMIS assessment and Moodle activity select options
        // TODO - only need one of these options - either external assessment list or moodle.
        $selected = null;
        $optionsexternal = array();
        $optionsexternal[0] = get_string('selectassessment', 'gradereport_transfer');

        foreach ($mappings as $mapping) {

            // Drop down menu options for mapped external assessments.
            $optionexternalstr = $mapping->samis_assessment_name .
                ' (' . $mapping->academicyear . ' - ' . $mapping->periodslotcode . ') ' . $mapping->mabseq;
            $optionsexternal[$mapping->id] = $optionexternalstr;
            $moodlemodule = $DB->get_record($mapping->moodle_activity_type, array('id' => $mapping->instance));
            $mapping->moodle_activity_name = $moodlemodule->name;
            // Condition for blind marking.
            $mapping->is_blind_marking_turned_on = 0;
            if (isset($moodlemodule->blindmarking)) {
                $mapping->is_blind_marking_turned_on = $moodlemodule->blindmarking;
                $mapping->revealidentities = $moodlemodule->revealidentities;
            }

            // Drop down menu options for mapped moodle activities.

            if ($mapping->id == $this->id) {
                $selected = $mapping;
            }
        }
        $this->externalassessment = $optionsexternal;
        $this->selected = $selected;
    }

    /**
     * Get the report filter options
     * @return array $options
     */
    public function get_status_options() {
        $options = array();
        for ($statusid = 0; $statusid < 6; $statusid++) {
            $options[] = get_string('transfer_status' . $statusid, 'gradereport_transfer');
        }
        return $options;
    }

    /**
     * Get the list of students who's grade is to be transferred
     * @param string $dotransfer determines the how the list is derived - all/selected/single
     * @return array $transferlist - userids
     */
    public function get_transfer_list($dotransfer) {

        switch ($dotransfer) {
            case 'all':
                $transferlist = $this->get_all_users();
                break;
            case 'selected':
                $transferlist = $this->get_selected_users();
                break;
            default:
                $transferlist = $this->get_individual_user($dotransfer);
                break;
        }
        return $transferlist;
    }

    /**
     * Get all students that should have completed the assessment -
     * all SAMIS enrolled students with same mapping parameters as assessment
     * @return array $transferlist - userids
     */
    // TODO - this should be replaced by local plugin method?
    private function get_all_users() {
        $userids = array();

        $users = $this->confirm_list();
        foreach ($users as $user) {
            $userids[] = $user->userid;
        }
        return $userids;
    }

    /**
     * Get selected students from form POST data
     * @return array $userids - userids
     */
    private function get_selected_users() {
        $userids = array();

        foreach ($_POST as $k => $v) {
            if (substr($k, 0, 4) == 'user') {
                // User is start of checkbox name.
                // Get userid for selected checkbox.
                $userid = substr($k, 4);
                if ($userid > 0) {
                    $userids[] = $userid;
                }
            }
        }
        return $userids;
    }

    /**
     * Put single student in an array so compatible with all & selected transfers
     * @return array $userid Single user id
     */
    private function get_individual_user($userid) {
        if ($userid > 0) {
            return array($userid);
        } else {
            return array();
        }
    }

    /**
     * Pass student list to grade transfer method in local plugin
     * @param array $transferlist - userids
     */
    public function do_transfers($transferlist = array()) {
        // Require local plugin class.
        $gradetransfers = new \local_bath_grades_transfer();
        $assessmentgrades = new \local_bath_grades_transfer_assessment_grades();
        try {
            $responses = $gradetransfers->transfer_mapping2($this->id, $transferlist, $assessmentgrades);
            return $responses;
        } catch (\Exception $e) {
            // Get mapping details by id.
            $mapping = $gradetransfers->assessmentmapping->get($this->id);
            foreach ($transferlist as $userid) {
                $gradetransfers->local_grades_transfer_log->outcomeid = TRANSFER_FAILURE;
                $gradetransfers->local_grades_transfer_log->userid = $userid;
                $gradetransfers->local_grades_transfer_log->timetransferred = time();
                $gradetransfers->local_grades_transfer_log->assessmentlookupid = $mapping->assessmentlookupid;
                $gradetransfers->local_grades_transfer_log->errormessage = $e->getMessage();
                $gradetransfers->local_grades_transfer_log->coursemoduleid = $mapping->coursemodule;
                $gradetransfers->local_grades_transfer_log->gradetransfermappingid = $this->id;
                $gradetransfers->local_grades_transfer_log->save();
            }
        }
    }

    /**
     * Returns summary data regarding the mapped transfer - total expected grades, graded in moodle & actual transferred so far
     * @return object $rs single database record
     */
    public function get_progress() {
        global $DB;

        $rs = $DB->get_record_sql("
                SELECT
                  COUNT(*) AS total
                , IFNULL( SUM( IF( gg.finalgrade IS NOT NULL, 1, 0 ) ), 0) as graded
                , IFNULL( SUM( IF( log.outcomeid = 1, 1, 0 ) ), 0) as transferred
                " . $this->sqlfrom, $this->sqlparams);

        return ($rs);
    }

    /**
     * Returns user data with grades and transfer status required to populate the report table
     * @param object $table - required for paging information
     * @return \moodle_recordset $rs
     */
    public function user_list($table = null) {
        global $DB;

        $limitfrom = (isset($table) ? $table->get_page_start() : '0');
        $limitnum = (isset($table) ? $table->get_page_size() : '0');

        $ordersql = "";
        if (isset($table)) {
            if ($orderby = $table->get_sql_sort()) {
                $ordersql .= ' ORDER BY ' . $orderby . ' ';
            } else {
                $ordersql .= ' ORDER BY lastname, firstname';
            }
        }

        $this->totalcount = $DB->count_records_sql("SELECT COUNT(ue.userid) " . $this->sqlfrom, $this->sqlparams);

        if (!empty($this->search)) {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $this->sqlfrom .= " AND " . $DB->sql_like($fullname, ':search', false, false);
            $this->sqlparams['search'] = "%$this->search%";
        }

        if (!empty($this->sifirst)) {
            $this->sqlfrom .= " AND " . $DB->sql_like('firstname', ':sifirst', false, false);
            $this->sqlparams['sifirst'] = "$this->sifirst%";
        }

        if (!empty($this->silast)) {
            $this->sqlfrom .= " AND " . $DB->sql_like('lastname', ':silast', false, false);
            $this->sqlparams['silast'] = "$this->silast%";
        }

        switch ($this->transferstatus) {
            case 1: // Completed transfers.
                $this->sqlfrom .= " AND log.outcomeid = " . TRANSFER_SUCCESS;
                break;
            case 2: // Failed transfers.
                $this->sqlfrom .= " AND log.outcomeid IN (2,3,4,5,6,7,9,10)";
                break;
            case 3: // Not transferred yet.
                $this->sqlfrom .= " AND log.outcomeid IS NULL";
                break;
            case 4: // Ready to transfer.
                $this->sqlfrom .= $this->sqlreadytotransfer;
                break;
            case 5: // In transfer queue.
                $this->sqlfrom .= " AND log.outcomeid = " . GRADE_QUEUED;
                break;
        }
        $this->matchcount = $DB->count_records_sql("SELECT COUNT(ue.userid) " . $this->sqlfrom, $this->sqlparams);
        $rs = $DB->get_recordset_sql("
            SELECT
              u.lastname
            , u.firstname
            , ue.userid
            , gg.finalgrade
            , gg.rawgrademax
            , gi.itemname
            , gg.timemodified AS 'timegraded'
            , gi.itemmodule
            , gi.iteminstance
            , log.timetransferred
            , log.outcomeid
            , oc.outcome AS 'transfer_outcome'
            , log.gradetransferred
            " . $this->sqlfrom . $ordersql, $this->sqlparams, $limitfrom, $limitnum
        );

        return $rs;
    }


    /**
     * Get final list of users to be transferred
     * @param array $transferlist
     * @return array
     */
    public function confirm_list($transferlist = array()) {
        global $DB;
        if (count($transferlist) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($transferlist, SQL_PARAMS_NAMED);
            $this->sqlparams = array_merge($this->sqlparams, $inparams);
            $subsetsql = " AND ue.userid $insql";
        } else {
            $subsetsql = "";
        }

        // Only show grades that are allowed to be transferred now.
        $subsetsql .= $this->sqlreadytotransfer;

        $orderby = ' ORDER BY u.lastname ASC,u.firstname ASC';
        $rs = $DB->get_records_sql("
            SELECT
              ue.userid
            , gg.finalgrade
            , gg.rawgrademax
            , gg.timemodified AS 'timegraded'
            , log.outcomeid
            " . $this->sqlfrom . $subsetsql . $orderby, $this->sqlparams
        );
        return $rs;
    }

    /** Download log as a CSV file.
     * @param $mappingid
     */
    public function download_log($mappingid) {
        $this->get_mapping_options($this->courseid);
        $filename = "transfer-log-" . $this->selected->samisassessmentid;
        $logdata = array();
        $fields = array(
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'finalgrade' => 'Grade',
            'timegraded' => 'Last Graded',
            'itemname' => 'Moodle Activity',
            'itemmodule' => 'Activity Type',
            'timetransferred' => 'Time Transferred',
            'gradetransferred' => 'Transferred Grade',
            'transfer_outcome' => 'Transfer Status'
        );
        $this->id = $mappingid;

        $csvexport = new \csv_export_writer();
        $csvexport->set_filename($filename);
        $csvexport->add_data($fields);
        $gradelist = $this->user_list();
        foreach ($gradelist as $grade) {
            foreach ($fields as $key => $field) {
                $data = $grade->$key;
                if ($key == 'rawgrademax') {
                    continue;
                }
                if (($key == 'timegraded' || $key == 'timetransferred') && !is_null($data)) {
                    $data = userdate($data);
                }
                $logdata[$key] = $data;
            }
            $csvexport->add_data($logdata);
        }
        $csvexport->download_file();
        die;
        // Download all log for the current mapping ID.

    }

    /**
     * Determine whether an assignment is blind marked or not
     * @return bool
     */
    public function is_blind_marking_enabled() {
        $isblindmarked = true;
        if (!$this->selected->is_blind_marking_turned_on ||
            ($this->selected->is_blind_marking_turned_on && $this->selected->revealidentities)
        ) {
            $isblindmarked = false;
        }
        return $isblindmarked;
    }
}