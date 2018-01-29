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
namespace gradereport_transfer;
defined('MOODLE_INTERNAL') || die();


/**
 * Class transfer_log
 * @package gradereport_transfer
 */
class transfer_log
{
    /**
     * transfer_log constructor.
     */
    public function __construct() {

    }

    /** Add log entry to the database table
     * @param $mapping
     * @param $userid
     * @param $outcomeid
     * @param $gradetransferred
     * @param null $error
     */
    public function add($mapping, $userid, $outcomeid, $gradetransferred, $error = null) {
        global $DB;
        if (!empty($error)) {
            // Insert error message.
            $error = new \stdClass();
            $error->error_message = $error;
            $errorid = $DB->insert_record("local_bath_grades_error", $error, true);
        } else {
            $errorid = null;
        }

        $insert = new \stdClass();
        $insert->coursemoduleid = $mapping->coursemoduleid;
        $insert->userid = $userid;
        $insert->gradetransfermappingid = $mapping->id;
        $insert->timetransferred = time();
        $insert->outcomeid = $outcomeid;
        $insert->grade_transfer_error_id = $errorid;
        $insert->gradetransferred = $gradetransferred;
        $DB->insert_record("local_bath_grades_log", $insert);

    }
}