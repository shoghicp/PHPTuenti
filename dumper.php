<?php

/*
 * PHPTuenti by @shoghicp
 * https://github.com/shoghicp/PHPTuenti
 * Under LGPL LICENSE
*/

include_once("PHPTuenti.php");
$tuenti = new PHPTuenti(false,true);
$path = dirname(__FILE__)."/dump/";
@mkdir($path);
if($argv[1] == "cookie" and $argc>=2){
	if(!$tuenti->login_cookie($argv[2])){
		die("[-] bad cookie".PHP_EOL);
	}
}elseif($argv[1] == "password" and $argc>=3){
	if(!$tuenti->login($argv[2], $argv[3])){
		die("[-] bad credentials".PHP_EOL);
	}	
}else{
	die("usage: php ".basename(__FILE__)." <auth_mode> <user/cookie> <password>".PHP_EOL."\tauth_mode: cookie, password");
}
$userinfo = $tuenti->getUserInfo();
print_r($userinfo);
echo "[*] starting to dump ".$userinfo["userFirstName"]." ".$userinfo["userLastName"]." account...".PHP_EOL;
$path .= $tuenti->getUserId()."/";
@mkdir($path);
@mkdir($path."images/");

$states = $tuenti->getUserStates(200);

echo "[*] Writing index page...",PHP_EOL;
file_put_contents($path."style.css",getCSS());
@file_put_contents($path."images/".$tuenti->getUserId(),file_get_contents($tuenti->getProfileImage()));
$index = "
<html>
<head>
<link rel='stylesheet' type='text/css' href='style.css' />
<title>".$userinfo["userFirstName"]." ".$userinfo["userLastName"]." &bull; Tuenti Dump</title>
</head>
<body>";
$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a></div>';
$index .= '<div class="userHeader"><img src="images/'.$tuenti->getUserid().'"/><h1 class="userName">'.$userinfo["userFirstName"]." ".$userinfo["userLastName"].'</h1><span class="state">'.$states[0].'</span></div><br/>';

$index .= '<span style="font-size:20px;font-weight:bold;">Informacion</span><br/>';
$index .= 'Visitas: '.$tuenti->getViews().'<br/>';
$index .= 'Invitaciones: '.$tuenti->getRestInvites().'<br/>';
$index .= 'Amigos: '.$tuenti->getFriendsCount().'<br/><br/>';

$index .= '<div class="states"><span style="font-size:20px;font-weight:bold;">Estados</span><br/>';
foreach($states as $state){
	$index .= '<div class="state">'.$state.'</div>';
}
$index .= "</div>";
$index .= "
</body>
</html>";
file_put_contents($path."index.html",$index);
unset($states);

echo "[*] writing friends page...",PHP_EOL;
$friends = $tuenti->getFriends();
$index = "
<html>
<head>
<link rel='stylesheet' type='text/css' href='style.css' />
<title>Amigos &bull; Tuenti Dump</title>
</head>
<body>";
$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a></div>';
$index .= '<div class="friends"><span style="font-size:20px;font-weight:bold;">Amigos</span><br/>';
$count = count($friends);
foreach($friends as $friend){
	$index .= '<a href="'.$friend["userId"].'.html"><div class="friend"><img src="images/'.$friend["userId"].'"/><h2>'.$friend["userFirstName"]." ".$friend["userLastName"].'</h2><span class="ubication">'.$friend["userUbication"].'</span></div></a>';
}
$index .= "</div>";
$index .= "
</body>
</html>";
file_put_contents($path."friends.html",$index);
echo "[*] writing friends personal pages...",PHP_EOL;
$count2 = 0;
foreach($friends as $friend){
	++$count2;
	@file_put_contents($path."images/".$friend["userId"],file_get_contents($tuenti->getProfileImage("medium",$friend["userId"])));
	$states = $tuenti->getUserStates(20,$friend["userId"]);
	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>".$friend["userFirstName"]." ".$friend["userLastName"]." &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a></div>';
	$index .= '<div class="userHeader"><img src="images/'.$friend["userId"].'"/><h1 class="userName">'.$friend["userFirstName"]." ".$friend["userLastName"].'</h1><span class="state">'.$states[0].'</span></div><br/>';
	$index .= '<div class="states"><span style="font-size:20px;font-weight:bold;">Estados</span><br/>';
	foreach($states as $state){
		$index .= '<div class="state">'.$state.'</div>';
	}
	$index .= "</div>";
	$index .= "
	</body>
	</html>";
	file_put_contents($path.$friend["userId"].".html",$index);
	show_status($count2,$count);
	unset($states);
}
unset($friends);

echo "[*] getting messages...",PHP_EOL;
$messages = $tuenti->getMessages("inbox");
echo "[*] writing messages page...",PHP_EOL;
$count = count($messages);
$count2 = 0;
	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>Mensajes &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a></div>';
	$index .= '<div class="messages"><span style="font-size:20px;font-weight:bold;">Mensajes</span><br/>';

foreach($messages as $threadId => $thread){
	++$count2;
	$index .= '<a href="thread'.$threadId.'.html">';
	$index .= '<div class="thread">';
	$c=0;
	foreach($thread as $mess){
		++$c;
		if($c=1){
			$index .= '<span class="last">'.substr($mess["messageBody"],0,38).'...</span><span class="date">'.date("j \d\e M, H:i",$mess["sentDate"]).'</span>';
		}
		if($mess["senderIsMe"]==false){
			$index .= '<a href="'.$mess["senderId"].'.html"><span class="other">'.$mess["senderFullName"].'</span></a>';
			break;
		}
	}
	$index .= '</div></a>';
	show_status($count2,$count);
}
$index .= "</div>";
$index .= "
</body>
</html>";
file_put_contents($path."messages.html",$index);

echo "[*] writing threads pages...",PHP_EOL;
$count2 = 0;
foreach($messages as $threadId => $thread){
	++$count2;
	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>Mensajes &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a></div>';
	$index .= '<div class="thread">';
	foreach($thread as $mess){
		++$c;
		$index .= '<div class="message">';
		$index .= '<span class="body">'.$mess["messageBody"].'</span><a href="'.(($mess["senderId"]==$tuenti->getUserId()) ? "index":$mess["senderId"]).'.html">';
		$index .= '<span class="sender"><img src="images/'.$mess["senderId"].'"/><span class="text">'.$mess["senderFullName"].'</span></span></a><span class="date">'.date("j \d\e M, H:i",$mess["sentDate"]).'</span>';
		$index .= '</div>';
	}	
	$index .= "</div>";
	$index .= "
	</body>
	</html>";
	file_put_contents($path."thread".$threadId.".html",$index);	
	show_status($count2,$count);
}

die("[+] Done!!".PHP_EOL);

function getCSS(){
$css = <<<'CSS'
body{
	font-family: Arial,Helvetica,sans-serif;
	font-size: 12px;
	margin-top:35px;
}
a{
	text-decoration:none;
	color:black;
}
img{
	border: 0px;
}
.states{
	width: 500px;
}
.states .state{
	padding-bottom:10px;
	padding-top:5px;
	position:relative;
	left:15px;
	border-bottom:1px dashed gray;
	width:500px;
	font-size:15px;
}
.menuHeader{
	position:fixed;
	top:0px;
	left:0px;
	height:30px;
	width:99%;
	background:skyblue;
	border:1px solid black;
	z-index:9999;
}
.menuHeader a{
	border-left:1px solid gray;
	border-right:1px solid gray;
	font-weight:bold;
	font-size:20px;
	bottom:3px;
	padding-right:10px;
	position:relative;
	height:30px;
	color:blue;
}
.userHeader{
	height:160px;
	width:600px;
	position:relative;
}
.userHeader h1{
	position:absolute;
	font-size:30px;
	left:160px;
	top:0px;
}
.userHeader img{
	position:absolute;
	left:5px;
	top:5px;
	width:150px;
	max-height:150px;
}
.userHeader .state{
	position:absolute;
	width:360px;
	left:160px;
	top:60px;
	height:60px;
}
.friends{
	width:600px;
}
.friends .friend{
	position:relative;
	left:10px;
	height:85px;
	padding:5px;
}
.friends .friend h2{
	position:absolute;
	font-size:20px;
	left:85px;
	top:0px;
}
.friends .friend img{
	position:absolute;
	left:2px;
	top:2px;
	width:80px;
	height:80px;
}
.friends .friend .ubication{
	position:absolute;
	width:360px;
	left:85px;
	top:38px;
	height:60px;
}

.messages{
	width:550px;
}
.messages .thread{
	position:relative;
	left:10px;
	height:20px;
	padding:2px;
}
.messages .thread .other{
	position:absolute;
	left:0px;
	top:0px;
	width:180px;
	overflow:hidden;
}
.messages .thread .last{
	position:absolute;
	left:190px;
}
.messages .thread .date{
	position:absolute;
	right:0px;
}

.thread{
	width:550px;
}
.thread .message{
	position:relative;
	left:10px;
	height:80px;
	padding:2px;
	height:
}
.thread img{
	position:relative;
	left:0px;
	height:30px;
	width:30px;
}
.thread .message .sender{
	position:absolute;
	left:0px;
	top:0px;
	width:200px;
	height:30px;
	font-weight:bold;
}
.thread .message .sender .text{
	position:absolute;
	left:40px;
	top:7px;	
}
.thread .message .body{
	position:absolute;
	top:35px;
	left:10px;
}
.thread .message .date{
	position:absolute;
	left:210px;
	top:0px;
	color:grey;
}

CSS;

return $css;
}
function show_status($done, $total, $size=30) {
    static $start_time;
    if($done > $total) return;

    if(empty($start_time)) $start_time=time();
    $now = time();

    $perc=(double)($done/$total);

    $bar=floor($perc*$size);

    $status_bar="\r[";
    $status_bar.=str_repeat("=", $bar);
    if($bar<$size){
        $status_bar.=">";
        $status_bar.=str_repeat(" ", $size-$bar);
    } else {
        $status_bar.="=";
    }

    $disp=number_format($perc*100, 0);

    $status_bar.="] ".$disp."%  ".$done."/".$total;

    $rate = ($now-$start_time)/$done;
    $left = $total - $done;
    $eta = round($rate * $left, 2);

    $elapsed = $now - $start_time;

    $status_bar.= " remaining: ".number_format($eta)." sec.  elapsed: ".number_format($elapsed)." sec.";

    echo $status_bar."  ";
    if($done == $total) {
        echo "\r[+] done".str_repeat(" ",strlen($status_bar)-8)."  \n";
		unset($start_time);
    }
}

?>