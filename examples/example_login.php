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
echo "Email: ";
$email = cli_read();
echo "Clave: ";
if(!$tuenti->login($email,cli_read())){
	die("[-] bad combination");
}
echo PHP_EOL;
$info = $tuenti->getUserInfo();
echo "Nombre: ",$info["userFirstName"],PHP_EOL;
echo "Apellidos: ",$info["userLastName"],PHP_EOL;
echo "Email: ",$info["userMail"],PHP_EOL;
echo "Estado: ",$tuenti->getUserState(),PHP_EOL;
echo "ID: ",$info["userId"],PHP_EOL;
echo "Amigos: ",$tuenti->getFriendsCount(),PHP_EOL;
echo "Visitas: ",$tuenti->getViews(),PHP_EOL;
echo "Invitaciones: ",$tuenti->getRestInvites(),PHP_EOL;

?>