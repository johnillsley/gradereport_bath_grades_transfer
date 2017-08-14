<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

class gradereport_transfer_observer {
    public static function transfer_grade_queue(\gradereport_transfer\event\grade_report_queue_grade_transfer $event){
       global $CFG;
        require_once $CFG->dirroot . '/grade/report/transfer/lib.php';
        require_once($CFG->dirroot . '/grade/report/transfer/classes/task/process_grade.php');

        $task = new \gradereport_transfer\task\process_grade();
        $task->set_custom_data(array(
            'courseid' => $event->courseid,
            'mappingid' => $event->other['mappingid'],
            'user'=> $event->other['users'],
            'contextid' => $event->contextid,
            'eventtype' => 'grade_transfer'
        ));
         \core\task\manager::queue_adhoc_task($task);

    }
}