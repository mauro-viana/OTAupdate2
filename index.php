<?php

header('Content-type: text/plain; charset=utf8', true);

//	Function to test existance of header value
function check_header($name, $value = false) {
    if(!isset($_SERVER[$name])) {
        return false;
    }
    if($value && $_SERVER[$name] != $value) {
        return false;
    }
    return true;
}

//	Function to send file to ESP8266
function sendFile($path) {
    header($_SERVER["SERVER_PROTOCOL"].' 200 OK', true, 200);
    header('Content-Type: application/octet-stream', true);
    header('Content-Disposition: attachment; filename='.basename($path));
    header('Content-Length: '.filesize($path), true);
    header('x-MD5: '.md5_file($path), true);
    readfile($path);
}

//	Test for correct user agent
if(!check_header('HTTP_USER_AGENT', 'ESP8266-http-Update')) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    echo "Invalid USER AGENT. This is only for ESP8266 updater!\n";
    exit();
}

//	Confirm existance of HTTP headers from ESP8266
if(
    !check_header('HTTP_X_ESP8266_STA_MAC') ||
    !check_header('HTTP_X_ESP8266_AP_MAC') ||
    !check_header('HTTP_X_ESP8266_FREE_SPACE') ||
    !check_header('HTTP_X_ESP8266_SKETCH_SIZE') ||
    !check_header('HTTP_X_ESP8266_SKETCH_MD5') ||
    !check_header('HTTP_X_ESP8266_CHIP_SIZE') ||
    !check_header('HTTP_X_ESP8266_SDK_VERSION')
) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden - Missing header value.', true, 403);
    exit();
}

//	Database associating ESP8266 MAC address with sketch file
$db = array(
    "5C-CF-7F-87-1A-40" => "maurao-0.0.2",
    "18:FE:AA:AA:AA:AA" => "DOOR-7-g14f53a19",
    "5C:CF:7F:B0:F2:14" => "httpUpdateDev.ino.nodemcu",
    "18:FE:AA:AA:AA:BB" => "TEMP-1.0.0"
);

//	Confirm ESP8266 MAC addres in database
if(!isset($db[$_SERVER['HTTP_X_ESP8266_STA_MAC']])) {
    header($_SERVER["SERVER_PROTOCOL"].' 500 ESP MAC not configured for updates', true, 500);
	exit();
}

//	Build filename of version requested from database
$localBinary = "./bin/".$db[$_SERVER['HTTP_X_ESP8266_STA_MAC']].".bin";

// Check if version has been set and does not match, if not, check if
// MD5 hash between local binary and ESP8266 binary do not match if not.
// then no update has been found.

if	(($db[$_SERVER['HTTP_X_ESP8266_STA_MAC']] != $_SERVER['HTTP_X_ESP8266_VERSION']) || 	// if ESP8266 requests different file from database OR
	($_SERVER["HTTP_X_ESP8266_SKETCH_MD5"] != md5_file($localBinary)))						// is there a mismatch between MD5 ... then
	{
		sendFile($localBinary);
	} else {
		header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified - no update required', true, 304);
	}

?>