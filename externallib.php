<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
//require_once(dirname(__FILE__).'/classes/lticontainer_webservice_handler.php');


//
// see https://docs.moodle.org/dev/Adding_a_web_service_to_a_plugin#Deprecation
//

class mod_lticontainer_external extends external_api 
{
    /**
     * Get list of courses with active sessions for today.
     * @param int $userid
     * @return array
     */

    public static function write_nblogs($data)
    {
        global $DB;

        /*
        ob_start();
        print_r($data);
        $buffer = ob_get_contents();
        ob_end_clean();
        $fp = fopen("/xtmp/Data","w");
        fputs($fp,$buffer);
        fclose($fp);
        */

        //file_put_contents('/xtmp/ZZ', "------------------------------\n", FILE_APPEND);
        //$param = self::validate_parameters(self::write_nbdata_parameters(), array($data));
        //$nb_logs = (object)$param[0];
        $param = self::validate_parameters(self::write_nblogs_parameters(), array('nb_logs' => $data));
        $nb_logs = (object)$param['nb_logs'][0];
        $nb_logs->updatetm = time();
        //file_put_contents('/xtmp/ZZ', 'host = '. $nb_logs->host."\n", FILE_APPEND);

        // Server
        if ($nb_logs->host=='server') {
            if (!empty($nb_logs->date)) $nb_logs->updatetm = strtotime($nb_logs->date);
            $condition = array('session'=>$nb_logs->session, 'message'=>$nb_logs->message);
            $recs = $DB->get_records('lticontainer_client_data', $condition);
            if ($recs) {
                $DB->insert_record('lticontainer_server_data', $nb_logs);
            }
            else {
                $nb_logs->status .= '/nc';    // no pair client data
                $DB->insert_record('lticontainer_server_data', $nb_logs);
                //$DB->insert_record('lticontainer_client_data', $nb_logs);
            }
        }

        // Clinent
        else if ($nb_logs->host=='client') {
            if (!empty($nb_logs->date)) $nb_logs->updatetm = strtotime($nb_logs->date);
            //$DB->insert_record('lticontainer_client_data', $nb_logs);
            //
            if ($nb_logs->tags!='') {
                $properties = 'filename|codenum';
                //$patterns   = "/\"(${properties})\s*:\s*([^\s\"]+)\"/u";
                $patterns   = "/\"(${properties}):\s*([^\"]+)\"/u";
                preg_match_all($patterns, $nb_logs->tags, $matches, PREG_SET_ORDER);
                foreach($matches as $match) {
                    $nb_logs->{$match[1]} = $match[2];
                } 
                //
                $rec = $DB->get_record('lticontainer_tags', array('cell_id'=>$nb_logs->cell_id, 'filename'=>$nb_logs->filename));
                if (!$rec) {
                    $DB->insert_record('lticontainer_tags', $nb_logs);
                }
                else {
                    //if ($nb_logs->filename!=$rec->filename || $nb_logs->codenum!=$rec->codenum) {
                    if ($nb_logs->codenum!=$rec->codenum) {
                        $nb_logs->id = $rec->id;
                        $DB->update_record('lticontainer_tags', $nb_logs);
                    }
                }
            }
            //
            $DB->insert_record('lticontainer_client_data', $nb_logs);
        }
        else {  // ltictr: cookie
            if ($nb_logs->lti_id!='') {
                $rec = $DB->get_record('lticontainer_session', array('session'=>$nb_logs->session)); 
                if (!$rec) {
                    $rec = $DB->get_record('lti', array('id'=>$nb_logs->lti_id), 'course'); 
                    $nb_logs->course = $rec->course;
                    $DB->insert_record('lticontainer_session', $nb_logs);
                }
            }
        }

        return $nb_logs;
    }


/*
    // for XML-RPC
    public static function write_nblogs_parameters()
    {
        return new external_function_parameters(
            array (
                new external_single_structure(
                    array(
                        'host'     => new external_value(PARAM_TEXT, 'server or client'),
                        'inst_id'  => new external_value(PARAM_TEXT, 'id of mod_lticontainer instance'),
                        'lti_id'   => new external_value(PARAM_TEXT, 'id of LTI module instance'),
                        'session'  => new external_value(PARAM_TEXT, 'id of session'),
                        'message'  => new external_value(PARAM_TEXT, 'id of message'),
                        'status'   => new external_value(PARAM_TEXT, 'status of jupyter'),
                        'username' => new external_value(PARAM_TEXT, 'user name'),
                        'cell_id'  => new external_value(PARAM_TEXT, 'id of cell'),
                        'tags'     => new external_value(PARAM_TEXT, 'tags of cell'),
                        'date'     => new external_value(PARAM_TEXT, 'date'),
                    )
                )
            )
        );
    }
*/


    public static function write_nblogs_parameters()
    {
        return new external_function_parameters(
            array (
                'nb_logs' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'host'     => new external_value(PARAM_TEXT, 'server or client', VALUE_DEFAULT, 'ltictr'),
                            'inst_id'  => new external_value(PARAM_TEXT, 'id of mod_lticontainer instance', VALUE_OPTIONAL),
                            'lti_id'   => new external_value(PARAM_TEXT, 'id of LTI module instance', VALUE_OPTIONAL),
                            'session'  => new external_value(PARAM_TEXT, 'id of session', VALUE_OPTIONAL),
                            'message'  => new external_value(PARAM_TEXT, 'id of message', VALUE_OPTIONAL),
                            'status'   => new external_value(PARAM_TEXT, 'status of jupyter', VALUE_OPTIONAL),
                            'username' => new external_value(PARAM_TEXT, 'user name', VALUE_OPTIONAL),
                            'cell_id'  => new external_value(PARAM_TEXT, 'id of cell', VALUE_OPTIONAL),
                            'tags'     => new external_value(PARAM_TEXT, 'tags of cell', VALUE_OPTIONAL),
                            'date'     => new external_value(PARAM_TEXT, 'date', VALUE_OPTIONAL),
                        )
                    )
                )
            )
        );
    }


    //
    public static function write_nblogs_returns()
    {
        return new external_single_structure(
            array (
                //'session' => new external_value(PARAM_TEXT, 'session id'),
            )
        );
    }

}

