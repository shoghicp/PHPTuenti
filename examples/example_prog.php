<?php

/*
 * PHPTuenti by @shoghicp
 * https://github.com/shoghicp/PHPTuenti
 * Under LGPL LICENSE
*/

$path = dirname(__FILE__)."/";
require($path."../PHPTuenti.php");
if($argc<=3){
	die("usage: ".basename(__FILE__)." \"<cookie>\" \"<timestamp_send>\" \"<message>\"");
}

$tuenti = new PHPTuenti();	
if(!$tuenti->login_cookie($argv[1])){
	die("[-] bad cookie");
}

/*
//Simpler way
$wait = min(1,$argv[2]-time());
sleep($wait);
*/
$end = $argv[2];

function pretty_time ($seconds) {
	$years = (int)($seconds / (24 * 3600 * 365));
	$day = (int)($seconds / 86400 % 365);
	$hs = (int)($seconds / 3600 % 24);
	$ms = (int)($seconds / 60 % 60);
	$sr = (int)($seconds / 1 % 60);

	if ($hs < 10) { $hh = "0" . $hs; } else { $hh = $hs; }
	if ($ms < 10) { $mm = "0" . $ms; } else { $mm = $ms; }
	if ($sr < 10) {  $ss = $sr; } else { $ss = $sr; }
	if($seconds < 1){$ss = rtrim( (string) number_format($seconds,8), '0');}

	$time = '';
	if ($years != 0) { $time .= $years . 'a '; }
	if ($day != 0) { $time .= $day . 'd '; }
	if ($hs  != 0) { $time .= $hh . 'h ';  }
	if ($ms  != 0) { $time .= $mm . 'm ';  }
	$time .= $ss . 's';

	return $time;
}

while($end-time()>0){
	echo "\r".pretty_time($end-time())."   ";
	sleep(1);
}
$tuenti->postStatus($argv[3]);
echo $argv[3],PHP_EOL,PHP_EOL,"Hecho!!";

?>