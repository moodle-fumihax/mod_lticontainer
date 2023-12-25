<?php

defined('MOODLE_INTERNAL') || die();


require_once(__DIR__.'/../locallib.php');



class  VolumeView
{
    var $cmid;
    var $courseid   = 0;
    var $course;
    var $minstance;
    var $mcontext;
    var $host_name  = 'localhost';

    var $isGuest    = true;
    var $edit_cap   = false;
    var $submitted  = false;
    var $confirm    = false;

    var $action_url = '';
    var $error_url  = '';
    var $url_params = array();
    var $deletes    = array();

    var $items      = array();


    function  __construct($cmid, $courseid, $minstance)
    {
        global $CFG, $DB;
        
        $this->cmid      = $cmid;
        $this->courseid  = $courseid;
        $this->course    = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $this->minstance = $minstance;
        $this->host_name = parse_url($CFG->wwwroot, PHP_URL_HOST);

        $this->url_params = array('id'=>$cmid, 'course'=>$courseid);
        $this->action_url = new moodle_url('/mod/lticontainer/actions/volume_view.php', $this->url_params);
        $this->error_url  = new moodle_url('/mod/lticontainer/actions/volume_view.php', $this->url_params);

        // for Guest
        $this->isGuest = isguestuser();
        if ($this->isGuest) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }
        //
        $this->mcontext = context_module::instance($cmid);
        if (!has_capability('mod/lticontainer:volume_view', $this->mcontext)) {
            print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
        }
        if (has_capability('mod/lticontainer:volume_edit', $this->mcontext)) {
            $this->edit_cap = true;
        }
    }


    function  set_condition() 
    {
        return true;
    }


    function  execute()
    {
        $check_course = '_'.$this->courseid.'_'.$this->host_name;
        $len_check = strlen($check_course);

        if ($this->minstance->use_podman==1) {
            if (!file_exists(LTICONTAINER_PODMAN_CMD) and  !file_exists(LTICONTAINER_PODMAN_REMOTE_CMD)) {
                print_error('no_podman_command', 'mod_lticontainer', $this->error_url);
            }
        }
        else {
            if (!file_exists(LTICONTAINER_DOCKER_CMD)) {
                print_error('no_docker_command', 'mod_lticontainer', $this->error_url);
            }
        }

        // POST
        if ($submit_data = data_submitted()) {
            if (!$this->edit_cap) {
                print_error('access_forbidden', 'mod_lticontainer', $this->error_url);
            }
            if (!confirm_sesskey()) {
                print_error('invalid_sesskey', 'mod_lticontainer',  $this->error_url);
            }
            $this->submitted  = true;

            //
            if (property_exists($submit_data, 'delete')) {
                $this->deletes = $submit_data->delete;
                if (!empty($this->deletes)) {
                    //
                    // confirm to delete volumes
                    if (property_exists($submit_data, 'submit_volume_del')) {
                        $this->confirm = true;
                    }
                    // delete volumes
                    else if (property_exists($submit_data, 'submit_volume_delete')) {
                        foreach ($this->deletes as $del=>$value) {
                            if (substr($del, -$len_check)==$check_course) { 
                                $cmd = 'volume rm '.$del;
                                container_exec($cmd, $this->minstance);
                                //
                                $event = lticontainer_get_event($this->cmid, 'volume_delete', $this->url_params, $cmd);
                                $event->add_record_snapshot('course', $this->course);
                                $event->add_record_snapshot('lticontainer',  $this->minstance);
                                $event->trigger();
                            }
                        }
                    } 
                }
            }
        }

        //
        $rslts = container_exec('volume ls', $this->minstance);
        if (isset($rslts['error'])) {
            print_error($rslts['error'], 'mod_lticontainer', $this->error_url);
        }

        $i = 0;
        foreach ($rslts as $rslt) {
            $rslt = preg_replace("/\s+/", ' ', trim($rslt));
            $vol  = explode(' ', $rslt);
            if (isset($vol[1])) {
                $role = '';
                if (!strncmp(LTICONTAINER_LTI_VOLUMES_CMD, $vol[1], strlen(LTICONTAINER_LTI_VOLUMES_CMD))) {
                    $role = 'Task Volume';
                    $len_cmd = strlen(LTICONTAINER_LTI_VOLUMES_CMD);
                }
                else if (!strncmp(LTICONTAINER_LTI_SUBMITS_CMD, $vol[1], strlen(LTICONTAINER_LTI_SUBMITS_CMD))) {
                    $role = 'Submit Volume';
                    $len_cmd = strlen(LTICONTAINER_LTI_SUBMITS_CMD);
                }

                if ($role!='' and substr($vol[1], -$len_check)==$check_course) { 
                    $this->items[$i] = new stdClass();
                    $this->items[$i]->driver   = $vol[0];
                    $this->items[$i]->fullname = $vol[1]; 
                    $this->items[$i]->volname  = substr($vol[1], 0, strlen($vol[1])-$len_check); 
                    $this->items[$i]->shrtname = substr($vol[1], $len_cmd, strlen($vol[1])-$len_check-$len_cmd); 
                    $this->items[$i]->role     = $role; 
                    $i++;
                }
            }
        }
        
        return true;
    }


    function  print_page() 
    {
        global $OUTPUT;

        if ($this->confirm) {
            include(__DIR__.'/../html/volume_delete.html');
        }
        else {
            include(__DIR__.'/../html/volume_view.html');
        }
    }
}
