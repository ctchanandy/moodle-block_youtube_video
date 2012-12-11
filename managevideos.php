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
 * Script to let a user manage their YouTube videos.
 *
 * @package     block
 * @subpackage  youtube_video
 * @author      Paul Holden, Greenhead College, 31st July 2007 (http://gcmoodle.greenhead.ac.uk/external/youtube/)
 * @copyright   2012 Andy Chan, CITE, HKU <ctchan.andy@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$deletevideoid = optional_param('deletevideoid', 0, PARAM_INTEGER);
$configid = optional_param('configid', 0, PARAM_INTEGER);

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

$urlparams = array();
$extraparams = '';
if ($courseid) {
    $urlparams['courseid'] = $courseid;
    $extraparams = '&courseid=' . $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
    $extraparams = '&returnurl=' . $returnurl;
}
$PAGE->set_url('/blocks/youtube_video/managevideos.php', $urlparams);

// Process any actions
if ($deletevideoid && confirm_sesskey()) {
    $DB->delete_records('block_youtube_video', array('id'=>$deletevideoid));

    redirect($PAGE->url, get_string('video_deleted', 'block_youtube_video'));
}

// Display the list of videos.
if ($managesharedvideos) {
    $select = '(courseid = ' . $courseid . ' OR shared = 1)';
} else {
    $select = 'courseid = ' . $courseid;
}
$videos = $DB->get_records_select('block_youtube_video', $select, null, $DB->sql_order_by_text('title'));

$strmanage = get_string('tab_managevids', 'block_youtube_video');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

$settingsurl = new moodle_url('/course/view.php?id='.$courseid.'&sesskey='.$USER->sesskey.'&bui_editid='.$configid);
$managevideos = new moodle_url('/blocks/youtube_video/managevideos.php', $urlparams);
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('formaltitle', 'block_youtube_video'));
$PAGE->navbar->add(get_string('configuration'), $settingsurl);
$PAGE->navbar->add(get_string('tab_managevids', 'block_youtube_video'), $managevideos);
echo $OUTPUT->header();

echo html_writer::start_tag('div', array('style'=>'text-align:right;width:100%'));
echo html_writer::tag('a', get_string('backtoconfig', 'block_youtube_video') , array('href' => $settingsurl));
echo html_writer::end_tag('div');

$table = new flexible_table('youtube-display-videos');

$table->define_baseurl($CFG->wwwroot.'/blocks/youtube_video/managevideos.php?courseid='.$courseid);
$table->define_columns(array('video', 'actions'));
$table->define_headers(array(get_string('formaltitle', 'block_youtube_video'), get_string('actions', 'moodle')));

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'youtubevideos');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_class('video', 'video');
$table->column_class('actions', 'actions');

$table->setup();

foreach($videos as $video) {
    $videotitle =  s($video->title);

    $viewlink = html_writer::link($video->url, $videotitle, array('target'=>'_blank'));

    $videoinfo = '<div class="title">' . $viewlink . '</div>' .
        '<div class="url">' . html_writer::link($video->url, $video->url, array('target'=>'_blank')) .'</div>' .
        '<div class="description">' . $video->description . '</div>';

    $editurl = new moodle_url('/blocks/youtube_video/editvideo.php?videoid=' . $video->id . $extraparams);
    $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));

    $deleteurl = new moodle_url('/blocks/youtube_video/managevideos.php?deletevideoid=' . $video->id . '&sesskey=' . sesskey() . $extraparams);
    $deleteicon = new pix_icon('t/delete', get_string('delete'));
    $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('delete_video_confirm', 'block_youtube_video')));

    $videoicons = $editaction . ' ' . $deleteaction;

    $table->add_data(array($videoinfo, $videoicons));
}

$table->print_html();

$url = $CFG->wwwroot . '/blocks/youtube_video/editvideo.php?' . substr($extraparams, 1);
echo '<div class="actionbuttons">' . $OUTPUT->single_button($url, get_string('add_new_video', 'block_youtube_video'), 'get') . '</div>';


if ($returnurl) {
    echo '<div class="backlink">' . html_writer::link($returnurl, get_string('back')) . '</div>';
}

echo $OUTPUT->footer();
