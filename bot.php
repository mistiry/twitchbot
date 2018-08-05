<?php
// Prevent PHP from stopping the script after 30 sec
// and hide notice messages
set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set("America/Chicago");
system("clear");

echo "Starting bot...\n";

//Bot Settings from command line options
$settings = "s:";	//server to connect to
$settings.= "p:";	//port to use
$settings.= "c:";	//channel to manage
$settings.= "n:";	//nickname
$settings.= "o:";   //oauth token
$setting = getopt($settings);
$errmsg = "";
empty($setting['s']) ? $errmsg.= "No server provided!\n" : true ;
empty($setting['p']) ? $errmsg.= "No port provided!\n" : true ;
empty($setting['c']) ? $errmsg.= "No channel provided!\n" : true ;
empty($setting['n']) ? $errmsg.= "No nickname provided!\n" : true ;
empty($setting['o']) ? $errmsg.= "No OAuth token provided!\n" : true ;
if($errmsg != "") {
	die($errmsg);
}

// Tread lightly.
$socket = fsockopen($setting['s'], $setting['p']);
fputs($socket,"USER ".$setting['n']." ".$setting['n']." ".$setting['n']." ".$setting['n']." :".$setting['n']."\n");
fputs($socket,"PASS ".$setting['o']."\n");
fputs($socket,"NICK ".$setting['n']."\n");
fputs($socket,"JOIN ".$setting['c']."\n");

$ignore = array('353','366');

while(1) {
    while($data = fgets($socket)) {
		$timestamp = date("Y-m-d H:i:s T");
		$ircdata = processIRCdata($data);
		if(!in_array($ircdata['messagetype'], $ignore)) {
			echo "[$timestamp]  $data";
		}
		
		if($ircdata['command'] == "PING") {
			echo "[$timestamp]  PONG ".$ircdata['messagetype']."";
            fputs($socket, "PONG ".$ircdata['messagetype']."\n");
		}

		//Reading the chat data
		if($ircdata['messagetype'] == "PRIVMSG" && $ircdata['location'] == $setting['c']) {
			$messagearray = $ircdata['messagearray'];
			$firstword = trim($messagearray[1]);
			switch($firstword) {
				case "!say":
					sendPRIVMSG($setting['c'], $ircdata['commandargs']);
					break;
			}						
		}
		// * END COMMAND PROCESSING * \\
	}
}


function sendPRIVMSG($location,$message) {
	global $socket;
	fputs($socket, "PRIVMSG ".$location." :".$message."\n");
	return;
}
function processIRCdata($data) {
	$pieces = explode(' ', $data);
	$messagearray = explode(':', $pieces[3]);
	$command = $pieces[0];
	$messagetype = $pieces[1];
	$location = $pieces[2];
	$userpieces1 = explode('@', $pieces[0]);
	$userpieces2 = explode('!', $userpieces1[0]);
	$userpieces3 = explode(':', $userpieces2[0]);
	$userhostname = $userpieces1[1];
	$usernickname = $userpieces3[1];
	$fullmessage = NULL; for ($i = 3; $i < count($pieces); $i++) { $fullmessage .= $pieces[$i] . ' '; }
	$fullmessage = substr($fullmessage, 1);
	$fullmessage = trim($fullmessage);
	$commandargs = NULL; for ($i = 4; $i < count($pieces); $i++) { $commandargs .= $pieces[$i] . ' '; }
	$commandargs = trim($commandargs);
	$return = array(
		'messagearray'	=>	$messagearray,
		'messagetype'	=>	$messagetype,
		'command'       =>  $command,
		'location'      =>  $location,
		'userhostname'	=>	$userhostname,
		'usernickname'	=>	$usernickname,
		'commandargs'	=>	$commandargs,
		'fullmessage'	=>	$fullmessage
	);
	return $return;
}
?>