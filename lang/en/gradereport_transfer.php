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
 * Strings for 'grade transfer report', language 'en'
 *
 * @package    grade_report_bath_transfer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2017 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Grade transfer';
$string['mappingitem'] = 'SAMIS assessment Name';
$string['mappingreference'] = 'SAMIS Assessment Code';
$string['periodslotcode'] = 'Period Slot Code';
$string['mabseq'] = 'MAB Sequence';
$string['astcode'] = 'AST Code';
$string['mabperc'] = 'MAB Percentage';
$string['datefrom'] = 'Submission date after';
$string['dateto'] = 'Submission date before';
$string['moodleactivitytype'] = 'Moodle Activity Type';
$string['moodleactivityname'] = 'Moodle Activity Name';
$string['transfer_status0'] = 'show all';
$string['transfer_status1'] = 'only completed transfers';
$string['transfer_status2'] = 'previous failed transfers';
$string['transfer_status3'] = 'not transferred';
$string['transfer_status4'] = 'ready to transfer';
$string['transfer_status5'] = 'in transfer queue';
$string['selectassessment'] = 'Select assessment';
$string['transferredgrade'] = 'Transferred grade';
$string['lastsubmission'] = 'Last modified (submission)';
$string['notsubmitted'] = 'Not submitted';
$string['lastgraded'] = 'Last graded';
$string['transferredon'] = 'Transferred on';
$string['transferpending'] = 'Not transferred yet';
$string['transferfailed'] = 'Transfer previously failed';
$string['transfergrade'] = 'Transfer grade';
$string['transfergrades'] = 'Transfer grades';
$string['academicyear'] = 'Academic Year';
$string['transferoverview'] = 'Transfer overview';
$string['transferlog'] = 'Transfer log';
$string['gotoactivitysettings'] = 'Go to activity settings';
$string['dotransfernow'] = 'Transfer';
$string['transferall'] = 'Transfer All';
$string['transferscheduled'] = 'The automatic grade transfer is scheduled for';
$string['transfercompleted'] = 'The automatic grade transfer was completed at';
$string['moodleactivitycompletion'] = 'Grade Status';
$string['transferconfirmheading'] = 'You are about to transfer the following grades to SAMIS for ';
$string['proceedwithtransfer'] = 'Proceed with data transfer';
$string['alreadytransferred'] = 'Grade already transferred to SAMIS';
$string['nogradetotransfer'] = 'No grade to transfer into SAMIS';
$string['wrongmaxgrade'] = 'Only grades out of 100 will be transferred';
$string['gradenotinteger'] = 'Only grades that are whole numbers can be transferred';
$string['willbetransferred'] = 'Will be transferred into SAMIS';
$string['novalidgrade'] = 'No valid grade to transfer';
$string['gradequeued'] = 'The grade has been queued for transfer';
$string['thisnolongerexists'] = 'This assessment no longer exists in SAMIS with the parameters below';
$string['mappingnotvalid'] = 'The destination assessment in SAMIS that was mapped to this Moodle activity no longer exists';
$string['reconfiguremapping'] = 'You must reconfigure the grade transfer in the Moodle activity settings to transfer these grades into SAMIS.';
$string['transfernotscheduled'] = 'Automated grade transfer is currently not scheduled for this assessment.';
$string['youcaneither'] = 'You can either';
$string['scheduletransfer'] = 'schedule a transfer in the moodle activity settings';
$string['triggermanually'] = ' or trigger the transfer manually once the Moodle activity has been graded.';
$string['courseisnotmapped'] = 'This course does not have a valid SAMIS mapping therefore no activity grades can be transferred.';
$string['eventgradereportviewed'] = 'Grade transfer report viewed';
$string['eventgradereporttransfer'] = 'Grade transfer triggered';
$string['onlymappedassessments'] = 'Only mapped assessments are listed';
$string['canceltransfer'] = 'Cancel';
$string['nocapabilitytotransfer'] = 'Your role does not have the capaiblity to transfer grades';
$string['youhavechosen'] = 'You have chosen to transfer the grades listed below.';
$string['blind_marking_warning'] = 'Blind marking is currently enabled for this assignment.  Anonymity will need to be lifted before grades can be transferred to SAMIS.';
$string['clicktocomplete'] = 'Click the <span style= "font-weight:bold" class="text-success">Proceed with data transfer</span> button to complete the request or
<span style= "font-weight:bold" class="text-danger">Cancel</span> to cancel the request and return to the previous screen.';
$string['transfer_progress_label'] = 'Currently {$a->graded} out of {$a->total} have been graded, {$a->transferred} have been transferred to SAMIS.';
// HELP ICONS.

$string['samis_code_help'] = 'Internal use only';
$string['samis_code'] = 'SAMIS Assessment Code';
$string['samis_assessment_name_help'] = 'SAMIS Assessment Name from MAB table in SAMIS';
$string['samis_assessment_name'] = 'SAMIS Assessment Name';
$string['moodle_activity_type_help'] = 'Type of Moodle Activity to send grades for';
$string['moodle_activity_type'] = 'Moodle Activity Type';
$string['moodle_activity_completion_help'] = 'Grades that have been successfully transferred to SAMIS will not be overwritten. If Moodle grades are updated after the transfer has taken place, the relevant SAMIS records will need be updated manually.';
$string['moodle_activity_completion'] = 'Grade Status';
$string['moodle_activity_name_help'] = 'Name of the Moodle Activity against which the SAMIS assessment is mapped';
$string['moodle_activity_name'] = 'Moodle Activity Name';
$string['transfer_mapping_details_help'] = 'Transfer Mapping details set on the Activity Settings page';
$string['transfer_mapping_details'] = 'Transfer Mapping Details';
$string['transfer_status_help'] = 'Status of your transfer';
$string['transfer_status'] = 'Transfer Status';
$string['samis_anon'] = 'Print Name in SAMIS';
$string['samis_anon_help'] = 'Value of MABPNAM field in MAB table .<br/>
  <strong> N </strong> = Anonymous in SAMIS <br/>
  <strong> Y </strong> = Not anonymous in SAMIS';
// EVENTS.
$string['gradereport_transfer_queue'] = 'Grade Transfer Queue';
$string['processqueuedgrades'] = 'Process Queued Grades to Transfer';
// Capability.
$string['transfer:transfer'] = 'Transfer Grades to SAMIS';
$string['transfer:view'] = 'View Grade Transfer Report';