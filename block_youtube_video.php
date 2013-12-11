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
        
        // Test YouTube oEmbed
        $json = file_get_contents('http://www.youtube.com/oembed?url='.$first_video->url.'&format=json');
        $video_info = json_decode($json);
        
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
        
        $select_playlist = '<select id="block_youtube_playlist">';
        $videodesc_arr = '';
        foreach ($videos_pl as $video) {
            $selected = ($first_video->id == $video->id) ? ' selected="selected"':'';
            $share = ($video->shared == 1 && $video->courseid != $pageid ? ' (*)' : '');
            $select_playlist .= '<option title="'.nl2br($video->description).'" value="' . $video->url . '"'.$selected.'>' . $video->title . $share . '</option>';
        }
        $select_playlist .= '</select>';
        
        $video_width = "100%";
        $video_height = "100%";
        $this->content->text = $select_playlist .
                   '<div id="block_youtube_videoobj" style="text-align:center;">' .
                   '<div id="block_youtube_video_thumb"><img src="'.$video_info->thumbnail_url.'" width="'.$video_width.'" height="'.$video_height.'" alt="'.$video_info->title.'" /></div>'.
                   '<div class="block_youtube_play_button"><img src="/blocks/youtube_video/pix/youtube_play.png" /></div>'.
                   '</div>'.
                   '<div id="videodesc">'.format_text($first_video->description, FORMAT_MOODLE, $filteropt).'</div>'.
                   '<script language="JavaScript" type="text/javascript">
                    YUI().use("io-base", "node-base", "json-parse", function (Y) {
                        function showVideo(obj) {
                            var html = obj.html;
                            html = html.replace("480","240");
                            html = html.replace("270","180");
                            html = html.replace("feature=oembed","feature=oembed&autoplay=1");
                            Y.one("#block_youtube_videoobj").setHTML(html);
                        }
                        
                        function updateThumbnail (obj) {
                            var thumbnail = obj.thumbnail_url;
                            var desc = obj.title;
                            var html = "<div id=\"block_youtube_video_thumb\"><img src=\""+thumbnail+"\" width=\"'.$video_width.'\" height=\"'.$video_height.'\" alt=\""+desc+"\" /></div>";
                            html = html + "<div class=\"block_youtube_play_button\"><img src=\"/blocks/youtube_video/pix/youtube_play.png\" /></div>";
                            Y.one("#block_youtube_videoobj").setHTML(html);
                        }
                        
                        Y.one("#block_youtube_videoobj").on("click", function (e) {
                            var target = Y.one("#block_youtube_videoobj");
                            var playlist = Y.one("#block_youtube_playlist");
                            var current_url = playlist.get("value");
                            
                            Y.io("/blocks/youtube_video/oembed.php", {
                                data: "url="+current_url,
                                on: {
                                    complete: function (id, response) {
                                        if (response.status >= 200 && response.status < 300) {
                                            showVideo(Y.JSON.parse(response.responseText));
                                        } else {
                                            showVideo("Failed to load video.");
                                        }
                                    }
                                }
                            });
                        });
                        
                        Y.one("#block_youtube_playlist").on("change", function (e) {
                            var thumb_div = Y.one("#block_youtube_video_thumb");
                            var playlist = Y.one("#block_youtube_playlist");
                            var current_url = this.get("value");
                            Y.io("/blocks/youtube_video/oembed.php", {
                                data: "url="+current_url,
                                on: {
                                    complete: function (id, response) {
                                        if (response.status >= 200 && response.status < 300) {
                                            updateThumbnail(Y.JSON.parse(response.responseText));
                                        } else {
                                            alert("Failed to load video.");
                                        }
                                    }
                                }
                            });
                        });
                    });
                    </script>';
                    
        return $this->content;
    }
}
?>