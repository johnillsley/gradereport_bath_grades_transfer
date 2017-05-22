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

require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->dirroot . '/grade/report/transfer/classes/grade_transfer.php');

/**
 * Class providing core functionality for the grade transfer report
 * @uses grade_report
 * @package grade_report_bath_transfer
 */
class grade_report_bath_transfer extends grade_report
{
    /**
     * @integer mappingid for the report - id in table local_bath_grades_mapping
     */
    public $id;
    /**
     * @var FROM part of SQL used by class methods.
     */
    private $sql_from;
    /**
     * @var FROM parameters for all SQL in class.
     */
    private $sql_params=array();
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
    public $moodle_activity = array();

    /**
     * @array the currently mapped external assessments on the course
     */
    public $external_assessment = array();

    /**
     * @object the currently selected mapping to be displayed on the report
     */
    public $selected = null;

    /**
     * @integer the report filter value indicating status of transfers to be displayed
     */
    public $transferstatus;

    /**
     * grade_report_bath_transfer constructor.
     * @param integer $mappingid
     */
    public function __construct($courseid, $gpr, $context, $mappingid)
    {
        parent::__construct($courseid, $gpr, $context);

        $this->id = $mappingid;
        $this->sql_from = "
        /***** get the grade transfer mapping *****/
        FROM {local_bath_grades_mapping} AS gm
        JOIN {local_bath_grades_lookup} AS gl
            ON gl.id = gm.assessment_lookup_id
            AND gl.expired IS NULL
            
        /***** join students that have equivalent sits mapping *****/     
        JOIN {sits_mappings} AS sm
            ON sm.acyear = gl.academic_year
            AND sm.period_code = gl.periodslotcode
            AND sm.sits_code = gl.samis_unit_code
            AND sm.active = 1
            AND sm.default_map = 1
        JOIN {sits_mappings_enrols} AS me ON me.map_id = sm.id
        JOIN {user_enrolments} AS ue ON ue.id = me.u_enrol_id -- PROBLEM WITH user_enrolments BEING REMOVED!!!
        JOIN {user} AS u ON u.id = ue.userid
        
        /***** join moodle activity information relating to mapping including current grade *****/
        JOIN {course_modules} AS cm ON cm.id = gm.coursemodule
        JOIN {modules} AS mo ON mo.id = cm.module
        LEFT JOIN {grade_items} AS gi
            ON gi.itemmodule = mo.name
            AND gi.iteminstance = cm.instance
        LEFT JOIN {grade_grades} AS gg
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
            WHERE outcomeid != ".GRADE_ALREADY_EXISTS."
            GROUP BY userid, gradetransfermappingid
        ) AS last_log
            ON last_log.userid = gg.userid
            AND last_log.gradetransfermappingid = gm.id
            
        /***** join outcome status *****/
        LEFT JOIN {local_bath_grades_log} AS log
            ON log.gradetransfermappingid = last_log.gradetransfermappingid
            AND log.userid = last_log.userid
            AND log.timetransferred = last_log.timetransferred
        LEFT JOIN {local_bath_grades_outcome} AS oc ON log.outcomeid = oc.id
        
        WHERE gm.id = :id
        ";
        $this->sql_params['id'] = $this->id;
    }

    /**
     * Handles form data sent by this report for this report. Abstract method to implement in all children.
     * @abstract
     * @param array $data
     * @return void
     */
    public function process_data($data)
    {

    }

    /**
     * Processes a single action against a category, grade_item or grade.
     * @param string $target Sortorder
     * @param string $action Which action to take (edit, delete etc...)
     * @return void
     */
    public function process_action($target, $action)
    {

    }

    // TODO - do we need academic year?
    /**
     * Gets all academic year options used within external assessments.
     * @return array $years - all distinct academic years that exist in
     */
    public function get_academic_year_options() {
        global $DB;

        $years = array();
        $options = $DB->get_records_sql("SELECT DISTINCT academic_year FROM {local_bath_grades_lookup} ORDER BY academic_year DESC");
        foreach ($options as $option) {
            $years[substr($option->academic_year, 0, 4)] = $option->academic_year;
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

        $params = array();
        $params[] = $courseid;

        if (!empty($year)) {
            $year_sql = 'AND SUBSTRING(gl.academic_year,1,4) = ?';
            $params[] = $year;
        } else {
            $year_sql = "";
        }

        $mappings = $DB->get_records_sql("
              SELECT
              gm.id
            , gm.timecreated
            , gm.timemodified
            , gm.modifierid
            , gm.locked
            , gm.samis_assessment_end_date
            , gl.id AS 'assessmentlookupid'
            , gl.samis_assessment_id
            , gl.mab_name AS 'samis_assessment_name'
            , gl.academic_year
            , gl.occurrence
            , gl.mab_sequence
            , gl.periodslotcode
            , cm.course
            , cm.id AS 'coursemoduleid'
            , cm.instance
            , gm.activity_type AS 'moodle_activity_type'
            FROM {local_bath_grades_mapping} AS gm
            JOIN {local_bath_grades_lookup} AS gl ON gl.id = gm.assessment_lookup_id
            JOIN {course_modules} AS cm ON cm.id = gm.coursemodule
            WHERE cm.course = ? " . $year_sql,
            $params
        );

        // Create both SAMIS assessment and Moodle activity select options
        // TODO - only need one of these options - either external assessment list or moodle
        $selected = null;
        //$options_moodle = array();
        $options_external = array();
        $options_external[0] = get_string('selectassessment', 'gradereport_transfer');
        //$options_moodle[0] = get_string('selectassessment', 'gradereport_transfer');

        foreach( $mappings as $mapping ) {

            // Drop down menu options for mapped external assessments
            $option_external_str = $mapping->samis_assessment_name . ' (' . $mapping->academic_year . ' - ' . $mapping->periodslotcode . ') ' . $mapping->mab_sequence;
            $options_external[$mapping->id] = $option_external_str;
            $moodle_module = $DB->get_record($mapping->moodle_activity_type, array('id' => $mapping->instance));
            $mapping->moodle_activity_name = $moodle_module->name;

            // Drop down menu options for mapped moodle activities
            //$options_moodle[$mapping->id] = $mapping->moodle_activity_name;

            if($mapping->id == $this->id) {
                $selected = $mapping;
            }
        }
        //$this->moodle_activity = $options_moodle;
        $this->external_assessment = $options_external;
        $this->selected = $selected;
    }

    /**
     * Get the report filter options
     * @return array $options
     */
    function get_status_options()
    {
        $options = array();
        for( $statusid = 0 ; $statusid<4 ; $statusid++ ) {
            $options[] = get_string('transferstatus'.$statusid, 'gradereport_transfer');
        }
        return $options;
    }

    /**
     * Get the list of students who's grade is to be transferred
     * @param string $dotransfer determines the how the list is derived - all/selected/single
     * @return array $transfer_list - userids
     */
    public function get_transfer_list($dotransfer) {

        switch( $dotransfer )
        {
            case 'all':
                $transfer_list = $this->get_all_users();
                break;
            case 'selected':
                $transfer_list = $this->get_selected_users();
                break;
            default:
                $transfer_list = $this->get_individual_user($dotransfer);
                break;
        }
        return $transfer_list;
    }

    /**
     * Get all students that should have completed the assessment - all SAMIS enrolled students with same mapping parameters as assessment
     * @return array $transfer_list - userids
     */
    // TODO - this should be replaced by local plugin method?
    private function get_all_users() {
        $userids = array();

        $users = $this->confirm_list();
        foreach( $users as $user ) {
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

        foreach( $_POST as $k=>$v ) {
            if( substr($k, 0, 4 )=='user' ) { // user is start of checkbox name
                // get userid for selected checkbox
                $userid = substr($k, 4 );
                if($userid>0) {
                    $userids[] = $userid;
                }
            }
        }
        return $userids;
    }

    /**
     * Put single student in an array so compatible with all & selected transfers
     * @return array containing single userid
     */
    private function get_individual_user($userid) {
        if( $userid>0 ) {
            return array($userid);
        } else {
            return array();
        }
    }

    /**
     * Pass student list to grade transfer method in local plugin
     * @param array $transfer_list - userids
     * @return array containing single userid
     */
    public function do_transfers($transfer_list=array()) {
        // Require local plugin class
        // $do_transfer = new grade_transfer;
        $grade_transfers = new \gradereport_transfer\grade_report_transfer_grade_transfer( $this->selected );
        $grade_transfers->prepare_grade_transfer($transfer_list);
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
                , SUM( IF( gg.finalgrade IS NOT NULL, 1, 0 ) ) as graded
                , SUM( IF( log.outcomeid = 1, 1, 0 ) ) as transferred
                " . $this->sql_from, $this->sql_params );

        return($rs);
    }

    /**
     * Returns user data with grades and transfer status required to populate the report table
     * @param object $table - required for paging information
     * @return moodle_recordset $rs
     */
    public function user_list( $table ) {
        global $DB;

        $limitfrom = $table->get_page_start();
        $limitnum  = $table->get_page_size();

        $order_sql = "";
        if ($orderby = $table->get_sql_sort()) {
            $order_sql .= ' ORDER BY ' . $orderby . ' ';
        } else {
            $order_sql .= ' ORDER BY lastname, firstname';
        }

        $this->totalcount = $DB->count_records_sql("SELECT COUNT(ue.userid) ". $this->sql_from, $this->sql_params );

        if( !empty($this->search) ) {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $this->sql_from .= " AND ".$DB->sql_like($fullname, ':search', false, false);
            $this->sql_params['search'] = "%$this->search%";
        }

        if( !empty($this->sifirst) ) {
            $this->sql_from .= " AND " . $DB->sql_like('firstname', ':sifirst', false, false);
            $this->sql_params['sifirst'] = "$this->sifirst%";
        }

        if( !empty($this->silast) ) {
            $this->sql_from .= " AND " . $DB->sql_like('lastname', ':silast', false, false);
            $this->sql_params['silast'] = "$this->silast%";
        }

        switch( $this->transferstatus ) {
            case 1: // Completed transfers
                $this->sql_from .= " AND log.outcomeid = 1";
                break;
            case 2: // Failed transfers
                $this->sql_from .= " AND log.outcomeid > 1";
                break;
            case 3: // Not transferred yet
                $this->sql_from .= " AND log.outcomeid IS NULL";
                break;
        }
        $this->matchcount = $DB->count_records_sql("SELECT COUNT(ue.userid) ". $this->sql_from, $this->sql_params );

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
            " . $this->sql_from . $order_sql, $this->sql_params, $limitfrom, $limitnum
        );

        return $rs;
    }

    /**
     * Returns user data with grades and transfer status required to populate the confirmation list prior to transferring grades
     * @param array $tansfer_list - userids
     * @return array $rs of objects
     */
    public function confirm_list( $transfer_list=array()) {
        global $DB;

        if( count($transfer_list)>0 ) {
            list($insql, $inparams) = $DB->get_in_or_equal($transfer_list, SQL_PARAMS_NAMED);
            $this->sql_params = array_merge($this->sql_params, $inparams);
            $subset_sql = " AND ue.userid $insql";
        } else {
            $subset_sql = "";
        }

        $rs = $DB->get_records_sql("
            SELECT
              ue.userid
            , gg.finalgrade
            , gg.rawgrademax
            , gg.timemodified AS 'timegraded'
            , log.outcomeid
            ". $this->sql_from.$subset_sql , $this->sql_params
        );

        return $rs;
    }
}