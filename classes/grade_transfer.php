<?php

namespace gradereport_transfer;

require_once($CFG->dirroot . '/grade/report/transfer/classes/transfer_log.php');
require_once($CFG->libdir . '/grade/grade_grade.php'); // TODO - direct to final class in local plugin

class grade_report_transfer_grade_transfer
{
    public function __construct() {

        $this->outcomes = array();
        $this->transfer_log = new transfer_log;
    }

    public function prepare_grade_transfer( $mappingid, $userids=array() ) {

        //$m = new local_bath_grades_transfer_assessment_mapping($mappingid);
        //$this->mapping = $m->get( $mappingid );

        // Check mapping is still valid
        //if (( $mapping->exists_by_id )) {

        if(1==1) { // TODO - replace with mapping is valid check - see above

            // TODO - check if get_grades_structure will have SPR code as key - if not add it
            //$assessment_data = new assessment_data();
            //$grades_structure = $assessment_data->get_grades_structure($mapping);

            $grades_structure = array(
                "n7" => (object) array( "mark"=>"90" ),
                "n2" => (object) array( "mark"=>"" ),
                "n4" => (object) array( "mark"=>"" ),
                "n5" => (object) array( "mark"=>"" ),
                "n6" => (object) array( "mark"=>"" ),
                "n9" => (object) array( "mark"=>"" ),
            ); // TODO - replace with actual grade structure - two lines above

            if ( count($userids) > 0 ) {

                $checked_grades = $this->pre_transfer_checks( $grades_structure, $userids );

                //$transfer_outomes = $assessment_data->export_grades( $checked_grades ); // uses moodle.user.id in key for transfer_outcome return
                // TODO - REMOVE THIS FAKE TRANSFER SUCCESS
                foreach( $checked_grades as $k=>$v ) {
                    $transfer_outcomes[$k] = $v;
                    $transfer_outcomes[$k]->status = TRANSFER_SUCCESS;
                }
                // END OF FAKE TRANSFER SUCCESS

                if( count( $transfer_outcomes ) >0 ) {
                    $this->log_outcomes( $transfer_outcomes );
                }
            }
        } else {
            // Mapping not valid
            // throw exception
        }
        return $this->outcomes;
    }

    private function pre_transfer_checks( $grades_structure, $userids=array() ) {

        $checked_grades = array();

        foreach ( $userids as $userid ) {

            $student_spr = $this->get_external_reference($userid);
            $moodle_grade = $this->get_moodle_grade($userid);
            $outcomeid = 0;

            if ( empty( $moodle_grade->finalgrade )) {
                $outcomeid = TRANSFER_NO_GRADE; // No grade to transfer

            } elseif ( $moodle_grade->rawgrademax != MAX_GRADE ) {
                $outcomeid = GRADE_NOT_OUT_OF_100; // Assessment not graded out of 100

            } elseif ( empty($grades_structure[$student_spr] )) {
                $outcomeid = NOT_IN_SITS_STRUCTURE; // Grade not expected by SITS - not in structure

            } elseif ( !empty($grades_structure[$student_spr]->mark )) {
                $outcomeid = GRADE_ALREADY_EXISTS; // Grade already exists in SITS

            } else {
                // Export OK to go
                $grades_structure[$student_spr]->mark = $moodle_grade->finalgrade; // add grade ready for export
                $checked_grades[$userid] = $grades_structure[$student_spr]; // Use moodle.user.id for export
            }

            if ( $outcomeid != 0 ) {
                // Log why transfer is not being attempted
                $this->outcomes[$userid] = $outcomeid;
                $this->transfer_log->add($this->mapping, $userid, $outcomeid, $moodle_grade->finalgrade);
            }
        }
        return $checked_grades;
    }

    private function log_outcomes( $transfer_outcomes ) {
        global $DB;

        $grade_item = $DB->get_record( "grade_items", array( 'itemmodule'=> $this->mapping->moodle_activity_type, 'iteminstance' => $this->mapping->instance ) ); // needed to lock grades

        foreach( $transfer_outcomes as $k=>$transfer_outcome ) {
            $userid = $k;
            if( $transfer_outcome->status==TRANSFER_SUCCESS ) {
                $this->transfer_log->add( $this->mapping, $userid, TRANSFER_SUCCESS, $transfer_outcome->mark ); // Does this go here or in assessment_data class?
                $this->outcomes[$userid] = TRANSFER_SUCCESS;

                // Lock moodle activity grade for user
                $grade_grade = new \grade_grade(array('userid'=>$userid, 'itemid'=>$grade_item->id), true);
                if( $grade_grade->locked==0 ) $grade_grade->set_locked(1);

            } else {
                $this->transfer_log->add( $this->mapping, $userid, TRANSFER_ERROR, $transfer_outcome->mark ); // Does this go here or in assessment_data class?
                $this->outcomes[$userid] = TRANSFER_ERROR;
            }
        }
    }

    private function get_moodle_grade( $userid ) {
        global $DB;

        $params = array();
        $params["userid"] = $userid;
        $params["cm"] = $this->mapping->coursemoduleid;

        $grade = $DB->get_record_sql("
        SELECT 
          ROUND(gg.finalgrade) AS 'finalgrade'
        , ROUND(gg.rawgrademax) AS 'rawgrademax'
        FROM {course_modules} AS cm
        JOIN {modules} AS mo ON mo.id = cm.module
        LEFT JOIN {grade_items} AS gi
            ON gi.itemmodule = mo.name
        AND gi.iteminstance = cm.instance
        LEFT JOIN {grade_grades} AS gg
            ON gg.itemid = gi.id
        AND gg.userid = :userid
        WHERE cm.id = :cm
        ", $params);

        return $grade;
    }

    private function get_external_reference( $userid ) {
        // TODO - this is temp
        return "n".$userid;
    }
}
/*
<records>
<assessments>
    <assessment>
        <module>ED00000</module>
        <occurrence>A</occurrence>
        <year>2016/7</year>
        <period>AY</period>
        <assess_item>01</assess_item>
        <student>029005235/1</student>
        <mark>80</mark>
    </assessment>
</assessments>
</records>
 */