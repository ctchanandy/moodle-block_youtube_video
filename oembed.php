<?php
// Get the remote oEmbed JSON from YouTube and serve it

require_once('../../config.php');
require_login();

$url = required_param('url', PARAM_TEXT);

$json = file_get_contents('http://www.youtube.com/oembed?url='.$url.'&format=json');

header('Content-Type: application/json');
echo $json;