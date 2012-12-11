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
 * Youtube Video Playlist Block
 *
 * @package     block
 * @subpackage  youtube_video
 * @author      Paul Holden, Greenhead College, 31st July 2007 (http://gcmoodle.greenhead.ac.uk/external/youtube/)
 * @copyright   2012 Andy Chan, CITE, HKU <ctchan.andy@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_youtube_video extends block_base {

    var $video;

    function init() {
        $this->title = get_string('formaltitle', 'block_youtube_video');
    }
    
    function instance_allow_multiple() {
        return true;
    }
    
    function instance_allow_config() {
        return true;
    }
    
    function applicable_formats() {
        return array('all' => true, 'my' => false, 'tag' => false);
    }
    
    function specialization() {
        $this->title = get_string('formaltitle', 'block_youtube_video');
    }

    function get_videos() {
        global $DB;
        if (!empty($this->config->youtubevideoid)) {
            list($video_ids_sql, $params) = $DB->get_in_or_equal($this->config->youtubevideoid);
            $youtube_videos = $DB->get_records_select('block_youtube_video', "id $video_ids_sql", $params);
            return $youtube_videos;
        }
        return false;
    }
    
    function preferred_width() {
        return 200;
    }
    
    function get_content() {
        global $DB;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->videos = $this->get_videos();

        if (!$this->videos) {
            $this->content->text = get_string('warning_no_playlist', 'block_youtube_video');
            $this->content->footer = '';
            return $this->content;
        }

        $this->specialization();
        
        $first_video = current($this->videos);
        
        $movieurl = str_replace(array('/watch/', '/watch?v='), '/v/' , $first_video->url);

        $filteropt = new stdClass;
        $filteropt->noclean = true;
        
        $videos_pl = array();
        $videos_av = array();
        $form_pl   = (!$this->config->youtubevideoid ? array() : $this->config->youtubevideoid);
        
        foreach ($this->videos as $video) {
            $array = 'videos_' . (in_array($video->id, $form_pl) ? 'pl' : 'av');
            array_push($$array, $video);
        }
        
        $pageid = $DB->get_field("context", "instanceid", array("id"=>$this->instance->parentcontextid));
        
        $select_playlist = '<select name="playlist" id="playlist" onchange="changeYouTubeVideo(this.options[this.selectedIndex].value, this.options[this.selectedIndex].title)">';
        $videodesc_arr = '';
        foreach ($videos_pl as $video) {
            $selected = ($first_video->id == $video->id) ? ' selected="selected"':'';
            $share = ($video->shared == 1 && $video->courseid != $pageid ? ' (*)' : '');
            $select_playlist .= '<option title="'.nl2br($video->description).'" value="' . $video->url . '"'.$selected.'>' . $video->title . $share . '</option>';
        }
        $select_playlist .= '</select>';
        
        $video_width = 200;
        $video_height = 150;
        $this->content->text = '<script language="JavaScript" type="text/javascript">' .
                     'function changeYouTubeVideo(url, desc) {' .
                        'url = url.replace("/watch/", "/v/");' .
                        'url = url.replace("/watch?v=", "/v/");' .
                        'document.getElementById("videoobj").innerHTML = "' .
                        '<object width=\''.$video_width.'\' height=\''.$video_height.'\'>' .
                        '<param name=\'movie\' value=\'" + url + "\'></param>' .
                        '<param name=\'wmode\' value=\'transparent\'></param>' .
                        '<embed src=\'" + url + "\' type=\'application/x-shockwave-flash\' wmode=\'transparent\' width=\''.$video_width.'\' height=\''.$video_height.'\'></embed>' .
                        '</object>";' .
                        'document.getElementById("videodesc").innerHTML = desc;'.
                     '}' .
                     '</script>' .
                     $select_playlist .
                     '<div id="videoobj" align="center">' .
                     '<object width="'.$video_width.'" height="'.$video_height.'">' .
                     '<param name="movie" value="' . $movieurl . '"></param>' .
                     '<param name="wmode" value="transparent"></param>' .
                     '<embed src="' . $movieurl . '" type="application/x-shockwave-flash" wmode="transparent" width="'.$video_width.'" height="'.$video_height.'"></embed>' .
                     '</object>'.
                     '</div>'.
                     '<div id="videodesc">'.format_text($first_video->description, FORMAT_MOODLE, $filteropt).'</div>';
        
        return $this->content;
    }
}
?>