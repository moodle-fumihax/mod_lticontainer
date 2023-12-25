<?php
/**
 * dashboard_view.class.php
 *
 * @package     mod_lticontainer
 * @copyright   2021 Urano Masanori <j18081mu@edu.tuis.ac.jp>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


require_once(__DIR__.'/../locallib.php');
require_once(__DIR__.'/../locallib_db.php');
require_once(__DIR__.'/../locallib_chart.php');



class  DashboardView
{
    var $cmid;
    var $courseid     = 0;
    var $course;
    var $minstance;
    var $mcontext;

    var $isGuest      = true;

    var $action_url   = '';
    var $error_url    = '';
    var $url_params   = array();

    var $start_date_r = '';
    var $start_date_a = '';
    var $start_date   = '';
    var $end_date     = '';
    var $lti_ids      = array();

    var $sql_r;         // SQL for Real Time
    var $sql_a;         // SQL for Any Period Time
    var $charts_data  = array();


    function  __construct($cmid, $courseid, $minstance)
    {
        global $DB;

        $this->cmid      = $cmid;
        $this->courseid  = $courseid;
        $this->course    = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $this->minstance = $minstance;

        $this->url_params = array('id'=>$cmid);
        $this->action_url = new moodle_url('/mod/lticontainer/actions/dashboard_view.php', $this->url_params);
        $this->error_url  = new moodle_url('/mod/lticontainer/actions/view.php', $this->url_params);

        // for Guest
        $this->isGuest = isguestuser();
        if ($this->isGuest) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }
        //
        $this->mcontext = context_module::instance($cmid);
        if (!has_capability('mod/lticontainer:dashboard_view', $this->mcontext)) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }

        ////////////////////////////////////////////////////////////////////////////
        $startdiff_r = $this->minstance->during_realtime;
        $startdiff_a = $this->minstance->during_anytime;
        if ($startdiff_r <= 0) $startdiff_r = CHART_DURING_REALTIME;
        if ($startdiff_a <= 0) $startdiff_a = CHART_DURING_ANYTIME;

        ////////////////////////////////////////////////////////////////
        $obj_datetime = new DateTime();
        //$obj_datetime = new DateTime('2022-01-29 12:00');

        //$this->end_date = $obj_datetime->format('Y-m-d H:i');
        $this->end_date = $obj_datetime->format(get_string('datetime_format','mod_lticontainer'));

        $startdiff  = $startdiff_r;
        $obj_datetime->sub(new DateInterval('PT'.$startdiff.'S'));
        //$this->start_date_r = $obj_datetime->format('Y-m-d H:i');
        $this->start_date_r = $obj_datetime->format(get_string('datetime_format','mod_lticontainer'));

        $startdiff = $startdiff_a - $startdiff_r;
        $obj_datetime->sub(new DateInterval('PT'.$startdiff.'S'));
        //$this->start_date_a = $obj_datetime->format('Y-m-d H:i');
        $this->start_date_a = $obj_datetime->format(get_string('datetime_format','mod_lticontainer'));

        $this->lti_info = db_get_disp_ltis($this->courseid, $this->minstance);
        foreach ($this->lti_info as $lti) {
            $this->lti_ids[] = $lti->id;
        }
    }


    function  set_condition()
    {
        $this->sql_r  = get_base_sql($this->courseid, $this->start_date_r, $this->end_date);
        $this->sql_r .= get_lti_sql_condition($this->lti_ids);
        //
        $this->sql_a  = get_base_sql($this->courseid, $this->start_date_a, $this->end_date);
        $this->sql_a .= get_lti_sql_condition($this->lti_ids);

        return true;
    }


    function  execute()
    {
        global $DB;

        $recs_r = $DB->get_records_sql($this->sql_r);
        $recs_a = $DB->get_records_sql($this->sql_a);
        $this->charts_data = chart_dashboard($recs_r, $recs_a, $this->minstance);

        return true;
    }


    function  print_page()
    {
        global $OUTPUT;

        include(__DIR__.'/../html/dashboard_view.html');
    }

}
