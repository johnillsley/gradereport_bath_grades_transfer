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
namespace gradereport_transfer\output;

use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

class transfer_status implements renderable, templatable
{
    public $userid;
    public $status;
    public $mark;
    public $reason;

    public function __construct($userid, $status, $mark = null, $reason = null) {
        $this->userid = $userid;
        $this->status = $status;
        $this->mark = $mark;
        $this->reason = $reason;
    }

    public function export_for_template(renderer_base $output) {
        $data = new \stdClass();
        $data->userid = $this->userid;
        $data->status = $this->status;
        if (!is_null($this->mark)) {
            $data->mark = $this->mark;
        }
        if (!is_null($this->reason)) {
            $data->reason = $this->reason;

        }
        return $data;
    }

}