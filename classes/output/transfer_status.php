<?php
namespace gradereport_transfer\output;

use renderable;
use renderer_base;
use templatable;

class transfer_status implements renderable,templatable{
    public $userid;
    public $status;
    public $mark;
    public $reason;

    public function __construct($userid,$status,$mark = null,$reason = null) {
        $this->userid = $userid;
        $this->status = $status;
        $this->mark = $mark;
        $this->reason = $reason;
    }
    public function export_for_template(renderer_base $output){
        $data = new \stdClass();
        $data->userid = $this->userid;
        $data->status = $this->status;
        if(!is_null($this->mark)){
            $data->mark = $this->mark;
        }
        if(!is_null($this->reason)){
            $data->reason = $this->reason;

        }
         return $data;
    }

}