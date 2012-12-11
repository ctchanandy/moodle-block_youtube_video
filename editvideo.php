<?php
// This file is part of YouTube Video Playlist block for Moodle - http://moodle.org/
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

/**
 * Script to let a user edit the properties of a particular YouTube video.
 *
 * @package     block
 * @subpackage  youtube_video
 * @author      Paul Holden, Greenhead College, 31st July 2007 (http://gcmoodle.greenhead.ac.uk/external/youtube/)
 * @copyright   2012 Andy Chan, CITE, HKU <ctchan.andy@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir .'/simplepie/moodle_simplepie.php');

class video_edit_form extends moodleform {
    protected $isadding;
    protected $caneditshared;
    protected $title = '';
    protected $description = '';
    protected $url = '';

    function __construct($actionurl, $isadding, $caneditshared) {
        $this->isadding = $isadding;
        $this->caneditshared = $caneditshared;
        parent::moodleform($actionurl);
    }

    function definition() {
        $mform =& $this->_form;

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'youtubeeditvideoheader', get_string('formaltitle', 'block_youtube_video'));

        $mform->addElement('text', 'url', get_string('video_url', 'block_youtube_video'), array('size' => 60));
        $mform->setType('url', PARAM_URL);
        $mform->addRule('url', null, 'required');

        $mform->addElement('text', 'title', get_string('edit_title', 'block_youtube_video'), array('size' => 60));
        $mform->setType('title', PARAM_NOTAGS);
        $mform->addRule('title', null, 'required');
        
        $editoroptions = array();
        $mform->addElement('editor', 'description', get_string('description'), null, $editoroptions);
        $mform->setType('description', PARAM_CLEANHTML);
        
        if ($this->caneditshared) {
            $mform->addElement('selectyesno', 'shared', get_string('edit_shared', 'block_youtube_video'));
            $mform->setDefault('shared', 0);
        }

        $submitlabal = null; // Default
        if ($this->isadding) {
            $submitlabal = get_string('add_new_video', 'block_youtube_video');
        }
        $this->add_action_buttons(true, $submitlabal);
    }

    function definition_after_data(){
        
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        return $errors;
    }

    function get_data() {
        $data = parent::get_data();
        if ($data) {          
            if($this->title){
                $data->title = $this->title;
            }

            if($this->description){
                $data->description = $this->description;
            }
            
            // Process URL
            if ($this->url) {
                $data->url = $this->url;
            }
            
            if (strpos($data->url,'&rel=0') === FALSE) {
                if (strpos($data->url,'&rel=1') === FALSE) {
                    $data->url .= '&rel=0';
                } else {
                    $data->url = str_replace('&rel=1','&rel=0',$data->url);
                }
            }
            $data->url = str_replace('http://youtu.be/', 'http://www.youtube.com/watch?v=', $data->url);
        }

        return $data;
    }
}

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$videoid = optional_param('videoid', 0, PARAM_INTEGER); // 0 mean create new.

if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
}

$managesharedvideos = has_capability('block/youtube_video:manageanyvideos', $context);
if (!$managesharedvideos) {
    require_capability('block/youtube_video:manageownvideos', $context);
}

$urlparams = array('videoid' => $videoid);
if ($courseid) {
    $urlparams['courseid'] = $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
$managevideos = new moodle_url('/blocks/youtube_video/managevideos.php', $urlparams);

$PAGE->set_url('/blocks/youtube_video/editvideo.php', $urlparams);
$PAGE->set_pagelayout('base');

if ($videoid) {
    $isadding = false;
    $videorecord = $DB->get_record('block_youtube_video', array('id' => $videoid), '*', MUST_EXIST);
} else {
    $isadding = true;
    $videorecord = new stdClass;
}

$mform = new video_edit_form($PAGE->url, $isadding, $managesharedvideos);
$mform->set_data($videorecord);

if ($mform->is_cancelled()) {
    redirect($managevideos);

} else if ($data = $mform->get_data()) {
    $data->courseid = $courseid;
    $data->description = $data->description['text'];
    if (!$managesharedvideos) {
        $data->shared = 0;
    }

    if ($isadding) {
        $DB->insert_record('block_youtube_video', $data);
    } else {
        $data->id = $videoid;
        $DB->update_record('block_youtube_video', $data);
    }
    
    redirect($managevideos);

} else {
    if ($isadding) {
        $strtitle = get_string('add_new_video', 'block_youtube_video');
    } else {
        $strtitle = get_string('edit_video', 'block_youtube_video');
    }
    
    $PAGE->set_title($strtitle);
    $PAGE->set_heading($strtitle);
    
    $settingsurl = new moodle_url('/admin/settings.php?section=blocksettingyoutube_video');
    $PAGE->navbar->add(get_string('blocks'));
    $PAGE->navbar->add(get_string('formaltitle', 'block_youtube_video'), $settingsurl);
    $PAGE->navbar->add(get_string('tab_managevids', 'block_youtube_video'));
    $PAGE->navbar->add($strtitle);
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strtitle, 2);
    
    $mform->display();
    
    echo $OUTPUT->footer();
}