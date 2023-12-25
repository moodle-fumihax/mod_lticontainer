<?php
namespace mod_lticontainer\output;
 
defined('MOODLE_INTERNAL') || die();
 
use context_module;
 
/**
 * Mobile output class for autoattend
 *
 * @package    mod_lticontainer
 * @copyright  2021 Fumi Iseki
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile
{
    public static function mobile_course_view($args)
    {
        global $OUTPUT, $USER, $DB;
 
        $args = (object) $args;
        $cm = get_coursemodule_from_id('lticontainer', $args->cmid);
 
        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);
 
        $context = context_module::instance($cm->id);
 
        require_capability ('mod/lticontainer:view', $context);
        if ($args->userid != $USER->id) {
            require_capability('mod/lticontainer:manage', $context);
        }
        $lticontainer = $DB->get_record('lticontainer', array('id' => $cm->instance));
 

        $data = array(
            'lticontainer' => $lticontainer,
            'cmid'     => $cm->id,
            'courseid' => $args->courseid
        );
 
        return [
            'templates' => [
                [
                    'id'   => 'main',
                    'html' => $OUTPUT->render_from_template('mod_lticontainer/mobile_course_view', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
        ];
    }

}
