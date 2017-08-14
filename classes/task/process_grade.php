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
 * Process grade adhoc task - Task to deal with processing grades to SAMIS
 *
 * @package    grade_report_bath_transfer
 * @author     Hittesh Ahuja <ha386@bath.ac.uk>
 * @copyright  2017 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace gradereport_transfer\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Class process_grade
 * @package gradereport_transfer\task
 */
class process_grade extends \core\task\adhoc_task{
    /**
     * @return string
     */
    public function get_name() {
        return get_string('processqueuedgrades', 'gradereport_transfer');
    }

    /**
     * @return string
     */
    public function get_component() {
        return 'gradereport_transfer';
    }

    /**
     *
     */
    public function execute() {
        global $CFG;
        echo "execute grade transfer adhoc task";
        require_once $CFG->dirroot . '/grade/report/transfer/lib.php';
        require_once $CFG->dirroot . '/local/bath_grades_transfer/lib.php';
        //Transfer grade
        $eventdata = (array) $this->get_custom_data();
        $courseid = $eventdata['courseid'];
        $mappingid = $eventdata['mappingid'];
        $context = \context_course::instance($courseid);
        $gpr = new \grade_plugin_return(array('type' => 'report', 'plugin' => 'transfer', 'courseid' => $eventdata['courseid']));
        $transfer_report = new \gradereport_transfer\transfer_report($courseid, $gpr, $context, $mappingid);
        $transfer_outcomes = $transfer_report->do_transfers($eventdata['user']);
        //die();
        //if (!empty($transfer_outcomes)) {
        //    echo json_encode($transfer_outcomes);
        //
    }
}