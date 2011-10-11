<?php

/*
 * PHPTuenti by @shoghicp
 * Under LGPL LICENSE
*/

$path = dirname(__FILE__)."/";
require($path."PHPTuenti.php");
if($argc<=2){
	die("usage: ".basename(__FILE__)." \"<cookie>\" \"<status>\"");
}

$tuenti = new PHPTuenti();	
if(!$tuenti->login_cookie($argv[1])){
	die("[-] bad cookie");
}

$tuenti->postStatus($argv[2]);
echo $argv[2],PHP_EOL,PHP_EOL,"Hecho!!";

?>