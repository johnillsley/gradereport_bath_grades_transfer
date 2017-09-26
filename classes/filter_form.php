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
 * Form for the grade transfer report
 *
 * @package    grade_report_bath_transfer
 * @uses       moodleform
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2017 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transfer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class filter_form extends \moodleform
{

    /**
     * Definition of the Mform for filters used in the report.
     */
    public function definition() {

        $mform = $this->_form;
        $course = $this->_customdata['course'];
        // $years            = $this->_customdata['years'];.
        $mappingids = $this->_customdata['mappingids'];
        $selectedmapping = $this->_customdata['selected_mapping'];
        $transferstatus = $this->_customdata['transferstatus'];
        $selectedstatus = $this->_customdata['selected_status'];

        // IS ACADEMIC YEAR REQUIRED IF MOODLE IS ARCHIVED EVERY YEAR?
        // $mform->addElement('select', 'year', get_string('academicyear', 'gradereport_transfer'), $years);.
        // $mform->setType('year', PARAM_INT);.

        $selectmapping = $mform->addElement('select', 'mappingid', get_string('mappingitem', 'gradereport_transfer'), $mappingids);
        $mform->setType('mappingid', PARAM_INT);
        $selectmapping->setSelected($selectedmapping);

        $selectstatus = $mform->addElement(
            'select', 'transferstatus',
            get_string('transferstatus',
                'gradereport_transfer'),
            $transferstatus
        );
        $mform->setType('transferstatus', PARAM_INT);
        $selectstatus->setSelected($selectedstatus);

        $mform->addElement('hidden', 'id', $course->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'sifirst', ""); // Resets firstname initial filter when selecting new mapping.
        $mform->setType('sifirst', PARAM_RAW);

        $mform->addElement('hidden', 'silast', ""); // Resets lastname initial filter when selecting new mapping.
        $mform->setType('silast', PARAM_RAW);

        // Add a submit button.
        $mform->addElement('submit', 'submitbutton', get_string('view'));
    }
}
