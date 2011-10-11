<?php

/*
 * PHPTuenti by @shoghicp
 * Under LGPL LICENSE
*/

	function cli_read(){
		$handle = fopen ("php://stdin","r");
		$line = trim(fgets($handle));
		fclose($handle);
		return $line;
	}
	function menu(){
		echo PHP_EOL,PHP_EOL;
		echo "Elige una opcion:",PHP_EOL;
		echo "\t[1] Postear Estado",PHP_EOL;
		echo "\t[2] Postear en Muro de usuario",PHP_EOL;
		echo "\t[3] Cambiar Privacidad",PHP_EOL;
		echo "\t[4] Crear post en blog",PHP_EOL;
		echo "\t[5] Cambiar Acerca De",PHP_EOL;
		echo "\t[6] Salir de Tuenti",PHP_EOL;
		echo "\t[7] Salir del programa",PHP_EOL;
		echo "Elige: ";
		return cli_read();
	}
	
	echo PHP_EOL,"0101010101010101010101010101",PHP_EOL;
	echo "Tuenti CLI tool @shoghicp",PHP_EOL;
	echo "0101010101010101010101010101",PHP_EOL,PHP_EOL;
	$path = dirname(__FILE__)."/";
	require($path."PHPTuenti.php");
	if($argc<=1){
		die("usage: ".basename(__FILE__)." \"<cookie>\"");
	}
	$cookie = $argv[1];
	
	$tuenti = new PHPTuenti();
	
	if(!$tuenti->login_cookie($cookie)){
		die("[-] bad cookie");
	}
	$info = $tuenti->getUserInfo();
	echo "Nombre: ",$info["userFirstName"],PHP_EOL;
	echo "Apellidos: ",$info["userLastName"],PHP_EOL;
	echo "Email: ",$info["userMail"],PHP_EOL;
	echo "ID: ",$info["userId"],PHP_EOL;
	echo "Amigos: ",$tuenti->getFriendsCount(),PHP_EOL;
	echo "Visitas: ",$tuenti->getViews(),PHP_EOL;
	while(1){
		switch(menu()){
			case 1:
				echo "Escribe el estado: ";
				$tuenti->postStatus(cli_read());
				echo "Estado posteado!";
				break;
			case 2:
				echo "ID usuario para postear: ";
				$id = cli_read();
				echo "Mensaje: ";
				$tuenti->postToUserWall(cli_read(),$id);
				echo "Mensaje posteado!!";
				break;
				
			case 3:
				echo "Cambia la privacidad (valor para todo, 0/10/20/50): ";
				$v = cli_read();
				$tuenti->changePrivacy($v,$v,$v,$v,$v);
				echo "Privacidad cambiada!!";
				break;
				
			case 4:
				echo "Titulo del post: ";
				$title = cli_read();
				echo "Texto: ";
				$tuenti->postBlogEntry($title,cli_read());
				echo "Mensaje posteado!!";
				break;
				
			case 6:
				echo "Saliendo...",PHP_EOL;
				$tuenti->logout();
				echo "Listo!",PHP_EOL;
				die();
				break;
			case 7:
				die("Adios!!");
				break;
								
		}
	
	}

?>