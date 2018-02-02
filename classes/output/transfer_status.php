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

/**
 * Class transfer_status
 * @package gradereport_transfer\output
 */
class transfer_status implements renderable, templatable
{
    /**
     * @var
     */
    public $userid;
    /**
     * @var
     */
    public $status;
    /**
     * @var null
     */
    public $mark;
    /**
     * @var null
     */
    public $reason;

    /**
     * transfer_status constructor.
     * @param $userid
     * @param $status
     * @param null $mark
     * @param null $reason
     */
    public function __construct($userid, $status, $mark = null, $reason = null) {
        $this->userid = $userid;
        $this->status = $status;
        $this->mark = $mark;
        $this->reason = $reason;
    }

    /**
     * @param renderer_base $output
     * @return \stdClass
     */
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