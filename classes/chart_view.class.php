<?php
/**
 * chart_view.class.php
 *
 * @package     mod_lticontainer
 * @copyright   2021 Urano Masanori <j18081mu@edu.tuis.ac.jp>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


require_once(__DIR__.'/../locallib.php');
require_once(__DIR__.'/../locallib_db.php');
require_once(__DIR__.'/../locallib_chart.php');



class  ChartView
{
    var $cmid;
    var $courseid   = 0;
    var $course;
    var $minstance;
    var $mcontext;

    var $isGuest    = true;

    var $action_url = '';
    var $error_url  = '';
    var $url_params = array();

    var $start_date = '';
    var $end_date   = '';
    var $lti_ids    = array();

    var $usernames  = array();
    var $filenames  = array();
    var $lti_info;

    var $username   = '*';
    var $filename   = '*';
    var $lti_id     = '*';

    var $sql;
    var $charts     = array();
    var $chart_kind;
    var $chart_title;

    var $args;


    function  __construct($cmid, $courseid, $minstance)
    {
        global $DB;

        $this->cmid      = $cmid;
        $this->courseid  = $courseid;
        $this->course    = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $this->minstance = $minstance;

        $this->url_params = array('id'=>$cmid);
        $this->action_url = new moodle_url('/mod/lticontainer/actions/chart_view.php', $this->url_params);
        $this->error_url  = new moodle_url('/mod/lticontainer/actions/view.php', $this->url_params);

        // for Guest
        $this->isGuest = isguestuser();
        if ($this->isGuest) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }
        //
        $this->mcontext = context_module::instance($cmid);
        if (!has_capability('mod/lticontainer:chart_view', $this->mcontext)) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }

        //
        // Parameters を受け取る
        $this->chart_kind  = optional_param('chart_kind',  'total_pie', PARAM_TEXT);
        $this->time_period = optional_param('time_period', 'real',      PARAM_TEXT);
        $this->username    = optional_param('user_select_box', '*',     PARAM_TEXT);
        $this->filename    = optional_param('file_select_box', '*',     PARAM_TEXT);
        $this->lti_id      = optional_param('lti_select_box',  '*',     PARAM_TEXT);

        ////////////////////////////////////////////////////////////////////////////
        // Date
        $startdiff_r = $this->minstance->during_realtime;
        $startdiff_a = $this->minstance->during_anytime;
        if ($startdiff_r <= 0) $startdiff_r = CHART_DURING_REALTIME;
        if ($startdiff_a <= 0) $startdiff_a = CHART_DURING_ANYTIME;

        //////////////////////////////////////////////////////////////////
        $obj_datetime = new DateTime();
        //$obj_datetime = new DateTime('2022-01-29 12:00');

        // Real Time
        if ($this->time_period == 'real') {
            $this->end_date   = $obj_datetime->format(get_string('datetime_format','mod_lticontainer'));
            $obj_datetime->sub(new DateInterval('PT'.$startdiff_r.'S'));
            $this->start_date = $obj_datetime->format(get_string('datetime_format','mod_lticontainer'));
        }
        // Any Period Time
        else {
            $start_date = optional_param('start_date_input', '*', PARAM_TEXT);
            $end_date   = optional_param('end_date_input',   '*', PARAM_TEXT);
            $start_date = str_replace('/', '-', $start_date);
            $end_date   = str_replace('/', '-', $end_date);

            if ($end_date == '*') {
                $this->end_date = $obj_datetime->format(get_string('datetime_format','mod_lticontainer'));
            }
            else {
                $this->end_date = (new DateTime($end_date))->format(get_string('datetime_format','mod_lticontainer'));
            }
            if ($start_date == '*') {
                $obj_datetime->sub(new DateInterval('PT'.$startdiff_a.'S'));
                $this->start_date = $obj_datetime->format(get_string('datetime_format','mod_lticontainer'));
            }
            else {
                $this->start_date = (new DateTime($start_date))->format(get_string('datetime_format','mod_lticontainer'));
            }
        }

        ////////////////////////////////////////////////////////////////////////////
        // LTI
        $this->lti_info = db_get_disp_ltis($this->courseid, $this->minstance);
        foreach ($this->lti_info as $key=>$lti) {
            $this->lti_info[$key]->valid = 0;
            $this->lti_ids[] = $lti->id;
        }
    }


    function  set_condition()
    {
        if ($this->lti_id == '*') $lti_id = $this->lti_ids;
        else                      $lti_id = $this->lti_id;

        $this->sql  = get_base_sql($this->courseid, $this->start_date, $this->end_date);
        $this->sql .= get_lti_sql_condition($lti_id);

        return true;
    }


    function  execute()
    {
        global $DB;

        $recs = $DB->get_records_sql($this->sql);
        foreach ($recs as $rec) {
            if (empty($rec->username)) $rec->username = CHART_NULL_USERNAME;
            if (empty($rec->filename)) $rec->filename = CHART_NULL_FILENAME;
            $this->usernames[$rec->username] = $rec->username;
            $this->filenames[$rec->filename] = $rec->filename;
            // valid : 表示制御用
            if (array_key_exists($rec->lti_id, $this->lti_info)) {
                $this->lti_info[$rec->lti_id]->valid = 1;
            }
        }
        if ($this->username!='*') $this->usernames[$this->username] = $this->username;
        if ($this->filename!='*') $this->filenames[$this->filename] = $this->filename;
        ksort($this->usernames);
        ksort($this->filenames);

        $add_title = '';
        if ($this->time_period == 'real') $add_title = 'Real Time ';

        //
        // call chart function
        if ($this->chart_kind === 'users_bar') {
            $this->chart_title = $add_title.'Activity per User';
            $this->charts = chart_users_bar($recs, $this->username, $this->filename, $this->minstance);
        }
        else if ($this->chart_kind === 'codecell_bar') {
            $this->chart_title = $add_title.'Activity per Code Cell';
            $this->charts = chart_codecell_bar($recs, $this->username, $this->filename, $this->minstance);
        }
        else if ($this->chart_kind === 'codecell_line') {
            $this->chart_title = $add_title.'User Progress on the Task';
            $this->charts = chart_codecell_line($recs, $this->username, $this->filename, $this->minstance);
        }
        else {
            $this->chart_title = $add_title.'Total Activities';
            $this->charts = chart_total_pie($recs, $this->username, $this->filename, $this->minstance);
        }

        return true;
    }


    function  print_page()
    {
        global $OUTPUT;

        $this->args = new StdClass();
        $this->args->usernames   = $this->usernames;
        $this->args->username    = $this->username;
        $this->args->filenames   = $this->filenames;
        $this->args->filename    = $this->filename;
        $this->args->lti_info    = $this->lti_info;
        $this->args->lti_id      = $this->lti_id;
        $this->args->start_date  = $this->start_date;
        $this->args->end_date    = $this->end_date;
        $this->args->chart_title = $this->chart_title;
        $this->args->chart_kind  = $this->chart_kind;
        $this->args->time_period = $this->time_period;
        $this->args->charts      = $this->charts;

        include(__DIR__.'/../html/chart_view.html');
    }

}
