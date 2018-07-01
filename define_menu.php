<?php
require 'WebRoot/get_access_token.php';
require 'config.php';
$jsonmenu = '{
    "button": [
        {
            "type": "view", 
            "name": "问卷调查", 
            "url": ""
        }
    ]
}';

$url = "";
$access_token = get_access_token(APPID, APPSECRET, $url);

$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
$result = http_post_data ( $url, $jsonmenu );

function http_post_data($url, $data = null) {
	$curl = curl_init ();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	if(!empty($data)){
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($curl);
	curl_close($curl);
	return $output;
}
?>