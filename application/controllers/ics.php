<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * This file is part of Jorani.
 *
 * Jorani is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jorani is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jorani.  If not, see <http://www.gnu.org/licenses/>.
 */

use Sabre\VObject;

class Ics extends CI_Controller {
    
    /**
     * Default constructor
     * Initializing of Sabre VObjets library
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function __construct() {
        parent::__construct();
        $this->load->helper('language');
        $this->lang->load('global', $this->config->item('language'));
        require_once(APPPATH . 'third_party/VObjects/vendor/autoload.php');
    }
    
    /**
     * Get the list of dayoffs for a given contract identifier
     * @param int $id identifier of a contract
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function dayoffs($id) {
        expires_now();
        if ($this->config->item('ics_enabled') == FALSE) {
            $this->output->set_header("HTTP/1.0 403 Forbidden");
        } else {
            $this->load->model('dayoffs_model');
            $result = $this->dayoffs_model->get_all_dayoffs($id);
            if (empty($result)) {
                echo "";
            } else {
                $vcalendar = new VObject\Component\VCalendar();
                foreach ($result as $event) {
                    $startdate = new \DateTime($event->date);
                    $enddate = new \DateTime($event->date);
                    switch ($event->type) {
                        case 1: 
                            $startdate->setTime(0, 0);
                            $enddate->setTime(23, 59);
                            break;
                        case 2:
                            $startdate->setTime(0, 0);
                            $enddate->setTime(12, 0);
                            break;
                        case 3:
                            $startdate->setTime(12, 0);
                            $enddate->setTime(23, 59);
                            break;
                    }                    
                    $vcalendar->add('VEVENT', [
                        'SUMMARY' => $event->title,
                        'CATEGORIES' => lang('day off'),
                        'DTSTART' => $startdate,
                        'DTEND' => $enddate
                    ]);    
                }
                echo $vcalendar->serialize();
            }
        }
    }
    
    /**
     * Get the list of leaves for a given employee identifier
     * @param int $id identifier of an employee
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function individual($id) {
        expires_now();
        if ($this->config->item('ics_enabled') == FALSE) {
            $this->output->set_header("HTTP/1.0 403 Forbidden");
        } else {
            $this->load->model('leaves_model');
            $result = $this->leaves_model->get_user_leaves($id);
            if (empty($result)) {
                echo "";
            } else {
                $vcalendar = new VObject\Component\VCalendar();
                foreach ($result as $event) {
                    $startdate = new \DateTime($event['startdate']);
                    $enddate = new \DateTime($event['enddate']);
                    if ($event['startdatetype'] == 'Morning') $startdate->setTime(0, 0);
                    if ($event['startdatetype'] == 'Afternoon') $startdate->setTime(12, 0);
                    if ($event['enddatetype'] == 'Morning') $enddate->setTime(12, 0);
                    if ($event['enddatetype'] == 'Afternoon') $enddate->setTime(23, 59);
                    
                    $vcalendar->add('VEVENT', [
                        'SUMMARY' => lang('leave'),
                        'CATEGORIES' => lang('leave'),
                        'DTSTART' => $startdate,
                        'DTEND' => $enddate,
                        'DESCRIPTION' => $event['cause'],
                        'URL' => base_url() . "leaves/" . $event['id'],
                    ]);    
                }
                echo $vcalendar->serialize();
            }
        }
    }
    
    /**
     * Action : download an iCal event corresponding to a leave request
     * @param int leave request id
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function ical($id) {
        expires_now();
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=leave.ics');
        $this->load->model('leaves_model');
        $leave = $this->leaves_model->get_leaves($id);
        $vcalendar = new VObject\Component\VCalendar();

        $vcalendar->add('VEVENT', [
            'SUMMARY' => lang('leave'),
            'CATEGORIES' => lang('leave'),
            'DESCRIPTION' => $leave['cause'],
            'DTSTART' => new \DateTime($leave['startdate']),
            'DTEND' => new \DateTime($leave['enddate']),
            'URL' => base_url() . "leaves/" . $id,
        ]);
        echo $vcalendar->serialize();
    }
}
