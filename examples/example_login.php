<?php

/*
 * PHPTuenti by @shoghicp
 * https://github.com/shoghicp/PHPTuenti
 * Under LGPL LICENSE
*/

function cli_read(){
	$handle = fopen ("php://stdin","r");
	$line = trim(fgets($handle));
	fclose($handle);
	return $line;
}


$path = dirname(__FILE__)."/";
require($path."../PHPTuenti.php");

$tuenti = new PHPTuenti();
if(!$tuenti->login($argv[1],$argv[2])){
	die("[-] bad combination");
}
echo PHP_EOL;
$info = $tuenti->getUserInfo();
print_r($info);

?>