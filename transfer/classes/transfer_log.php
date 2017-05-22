<?php

namespace gradereport_transfer;

class transfer_log
{
    public function __construct() {

    }

    public function add($mapping, $userid, $outcomeid, $gradetransferred, $error=null) {
        global $DB;
/*
        echo "<br/>";
        echo "<br/>Mapping=".$mapping->id;
        echo "<br/>Userid=".$userid;
        echo "<br/>Outcomeid=".$outcomeid;
*/
        if( !empty($error) ) {
            // Insert error message
            $error = new \stdClass();
            $error->error_message = $error;
            $error_id = $DB->insert_record( "local_bath_grades_error", $error, true );
        } else {
            $error_id = null;
        }

        $insert = new \stdClass();
        $insert->coursemoduleid             = $mapping->coursemoduleid;
        $insert->userid                     = $userid;
        $insert->gradetransfermappingid     = $mapping->id;
        $insert->timetransferred            = time();
        $insert->outcomeid                  = $outcomeid;
        $insert->grade_transfer_error_id    = $error_id;
        $insert->gradetransferred           = $gradetransferred;
        $DB->insert_record( "local_bath_grades_log", $insert );

    }
}
/*

coursemoduleid - mapping
userid
gradetransfermappingid - mapping
assessmentlookupid - mapping
timetransferred
outcomeid
grade_transfer_error_id
gradetransferred

 */