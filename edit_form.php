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
 * Form for editing Youtube playlist block instances.
 *
 * @package     block
 * @subpackage  youtube_video
 * @author      Paul Holden, Greenhead College, 31st July 2007 (http://gcmoodle.greenhead.ac.uk/external/youtube/)
 * @copyright   2012 Andy Chan, CITE, HKU <ctchan.andy@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_youtube_video_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG, $DB, $USER;

        // Fields for editing block contents.
        $youtube_videos = $DB->get_records_sql_menu('
                SELECT id, title
                FROM {block_youtube_video}
                WHERE courseid = ? OR shared = 1
                ORDER BY id ASC',
                array($this->page->course->id));
        if ($youtube_videos) {
            $select = $mform->addElement('select', 'config_youtubevideoid', get_string('select_playlist_videos', 'block_youtube_video'), $youtube_videos);
            $select->setMultiple(true);

        } else {
            $mform->addElement('static', 'config_youtubevideoid', get_string('select_playlist_videos', 'block_youtube_video'),
                    get_string('warning_no_videos', 'block_youtube_video'));
        }
        
        if (has_any_capability(array('block/youtube_video:manageanyvideos', 'block/youtube_video:manageownvideos'), $this->block->context)) {
            $mform->addElement('static', 'nofeedmessage', '',
                    '<a target="_blank" href="' . $CFG->wwwroot . '/blocks/youtube_video/managevideos.php?courseid='.$this->page->course->id.'&configid='.$this->block->instance->id.'">' .
                    get_string('tab_editvid', 'block_youtube_video') . '</a>');
        }
    }
}