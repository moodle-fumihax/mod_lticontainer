<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../locallib_db.php');


class  LTIConnect
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

    var $items;


    function  __construct($cmid, $courseid, $minstance)
    {
        global $DB;
        
        $this->cmid      = $cmid;
        $this->courseid  = $courseid;
        $this->course    = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $this->minstance = $minstance;

        //$this->url_params = array('id'=>$cmid, 'course'=>$courseid);
        $this->url_params = array('id'=>$cmid);
        $this->action_url = new moodle_url('/mod/lticontainer/actions/lti_view.php', $this->url_params);
        $this->error_url  = new moodle_url('/mod/lticontainer/actions/lti_view.php', $this->url_params);

        // for Guest
        $this->isGuest = isguestuser();
        if ($this->isGuest) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }
        //
        $this->mcontext = context_module::instance($cmid);
        if (!has_capability('mod/lticontainer:lti_setting', $this->mcontext)) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }
    }


    function  set_condition() 
    {
        $this->order = optional_param('order', '', PARAM_TEXT);

        return true;
    }


    function  execute()
    {
        global $DB;

        //$disp = explode(',', $this->minstance->display_lti);
        $nodisp = explode(',', $this->minstance->no_display_lti);
        $fields = 'id,name,instructorcustomparameters,typeid';
        $this->items = db_get_valid_ltis($this->courseid, $fields);

        $hname = parse_url($this->minstance->jupyterhub_url, PHP_URL_HOST);
        $namel = strlen($hname);
        foreach ($this->items as $key => &$item) {
            $type = $DB->get_record('lti_types', array('id' => $item->typeid), 'tooldomain');
            if (!strncasecmp($type->tooldomain, $hname, $namel)) {  // 同じドメインのみ取り扱う
                $item->disp = 1;
                //if (!in_array($item->id, $disp, true)) $item->disp = 0;
                if (in_array($item->id, $nodisp, true)) $item->disp = 0;
            }
            else {
                unset($this->items[$key]);  // 違うドメイン(FQDN)の場合はリストから削除
            }
        }
        return true;
    }


    function  print_page() 
    {
        global $OUTPUT;

        include(__DIR__.'/../html/lti_setting.html');
    }
}
