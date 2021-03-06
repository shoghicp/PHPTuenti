<?php

/*
 * PHPTuenti by @shoghicp
 * https://github.com/shoghicp/PHPTuenti
 * Under LGPL LICENSE
*/


$path = dirname(__FILE__)."/";
include_once($path."../PHPTuenti.php");
$path .= "dump/";
$tuenti = new PHPTuenti(false,true);

@mkdir($path);
@chmod($path,0755);
if($argv[1] == "cookie" and $argc>=2){
	if(!$tuenti->login_cookie($argv[2])){
		die("[-] bad cookie".PHP_EOL);
	}
	$userId = ($argv[3]!="") ? $argv[3]:$tuenti->getUserId();
}elseif($argv[1] == "password" and $argc>=3){
	if(!$tuenti->login($argv[2], $argv[3])){
		die("[-] bad credentials".PHP_EOL);
	}
	$userId = isset($argv[4]) ? $argv[4]:$tuenti->getUserId();
}else{
	die("usage: php ".basename(__FILE__)." <auth_mode> <user/cookie> <password> [userid]".PHP_EOL."\tauth_mode: cookie, password");
}
$userinfo = $tuenti->getUserInfo($userId);

echo "[*] starting to dump ".$userinfo["userFirstName"]." ".$userinfo["userLastName"]." account...".PHP_EOL;
$path .= $userId."/";

@mkdir($path);
@chmod($path,0755);
@mkdir($path."images/");

echo "[*] Writing index page...",PHP_EOL;
file_put_contents($path."style.css",getCSS());
@file_put_contents($path."images/".$userId,file_get_contents($tuenti->getProfileImage("medium",$userId)));
@file_put_contents($path."images/".$userId."_big",file_get_contents($tuenti->getProfileImage("big",$userId)));
$states = $tuenti->getUserStates(200,$userId);
$index = "
<html>
<head>
<link rel='stylesheet' type='text/css' href='style.css' />
<title>".$userinfo["userFirstName"]." ".$userinfo["userLastName"]." &bull; Tuenti Dump</title>
</head>
<body>";
$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
$index .= '<div class="userHeader"><a href="images/'.$userId.'_big" target="_blank"><img src="images/'.$userId.'"/></a><h1 class="userName">'.$userinfo["userFirstName"]." ".$userinfo["userLastName"].'</h1><span class="state">'.$states[0].'</span></div><br/>';

$index .= '<span style="font-size:20px;font-weight:bold;">Informacion</span><br/>';
if($tuenti->getUserId() == $userId){
	$index .= 'Visitas: '.$tuenti->getViews().'<br/>';
	$index .= 'Invitaciones: '.$tuenti->getRestInvites().'<br/>';
}
$index .= 'Amigos: '.$tuenti->getFriendsCount($userId).'<br/>';
foreach($userinfo["personalInfo"] as $name => $val){
	$index .= $name.': '.$val.'<br/>';
}
foreach($userinfo["userInterests"] as $name => $val){
	$index .= '<h3>'.$name.'</h3><p>'.$val.'</p>';
}
$index .= '<br/>';
$posts = $tuenti->getPosts(50,$userId);
$index .= '<div class="posts"><span style="font-size:20px;font-weight:bold;">Espacio personal</span><br/>';
foreach($posts as $post){
	$index .= '<div class="post"><b>'.$post["title"].'</b><br>'.stylize(nl2br($post["text"])).'</div>';
}
$index .= '</div><br/><br/>';
$index .= '<div class="states"><span style="font-size:20px;font-weight:bold;">Estados</span><br/>';
foreach($states as $state){
	$index .= '<div class="state">'.stylize(nl2br($state)).'</div>';
}
$index .= "</div>";
$index .= "
</body>
</html>";
file_put_contents($path."index.html",$index);
unset($states);


if($tuenti->getUserId() == $userId){
echo "[*] getting Inbox messages...",PHP_EOL;
$messages = $tuenti->getMessages("inbox");
echo "[*] writing Inbox messages page...",PHP_EOL;
$count = count($messages);
$count2 = 0;
	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>Mensajes Recibidos &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
	$index .= '<div class="messages"><span style="font-size:20px;font-weight:bold;">Mensajes Recibidos</span><br/>';

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
file_put_contents($path."messagesInbox.html",$index);



echo "[*] getting Sent messages...",PHP_EOL;
$mn = $tuenti->getMessages("sent");
$messages = array_merge($messages, $mn);
echo "[*] writing Sent messages page...",PHP_EOL;
$count = count($mn);
$count2 = 0;
	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>Mensajes Enviados &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
	$index .= '<div class="messages"><span style="font-size:20px;font-weight:bold;">Mensajes Enviados</span><br/>';

foreach($mn as $threadId => $thread){
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
file_put_contents($path."messagesSent.html",$index);


echo "[*] getting Spam messages...",PHP_EOL;
$mn = $tuenti->getMessages("spam");
$messages = array_merge($messages, $mn);
echo "[*] writing Spam messages page...",PHP_EOL;
$count = count($mn);
$count2 = 0;
	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>Mensajes Desconocidos &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
	$index .= '<div class="messages"><span style="font-size:20px;font-weight:bold;">Mensajes Desconocidos</span><br/>';

foreach($mn as $threadId => $thread){
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
file_put_contents($path."messagesSpam.html",$index);

	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>Mensajes Desconocidos &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
$index .= '<div class="messages"><a href="messagesInbox.html"><span style="font-size:20px;font-weight:bold;font-decoration:none;">Mensajes Recibidos</span></a><br/><a href="messagesSent.html"><span style="font-size:20px;font-weight:bold;font-decoration:none;">Mensajes Enviados</span></a><br/><a href="messagesSpam.html"><span style="font-size:20px;font-weight:bold;font-decoration:none;">Mensajes Desconocidos</span></a><br/>';
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
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
	$index .= '<div class="thread">';
	foreach($thread as $mess){
		++$c;
		$index .= '<div class="message">';
		$index .= '<span class="body">'.$mess["messageBody"].'</span><a href="'.(($mess["senderId"]==$userId) ? "index":$mess["senderId"]).'.html">';
		$index .= '<span class="sender"><img src="images/'.$mess["senderId"].'"/><span class="text">'.$mess["senderFullName"].'</span></span></a><span class="date">'.date("j \d\e M, H:i",$mess["sentDate"]).'</span>';
		$index .= '</div>';
		if(!file_exists($path."images/".$mess["senderId"])){
			@file_put_contents($path."images/".$mess["senderId"],file_get_contents($tuenti->getProfileImage("medium",$mess["senderId"])));
		}
	}	
	$index .= "</div>";
	$index .= "
	</body>
	</html>";
	file_put_contents($path."thread".$threadId.".html",$index);
	show_status($count2,$count);
}
}else{
	file_put_contents($path."messages.html","You aren't this user");
}

echo "[*] writing photos page...",PHP_EOL;
$photos = array_merge($tuenti->getAlbum("1-".$userId), $tuenti->getAlbum("2-".$userId), $tuenti->getAlbum("17-".$userId));
if($tuenti->getUserId() == $userId){
	array_merge($photos, $tuenti->getAlbum("20-".$userId));
}
$count = count($photos);
$count2 = 0;
$pages = 1;
$index = "
<html>
<head>
<link rel='stylesheet' type='text/css' href='style.css' />
<title>Fotos &bull; Tuenti Dump</title>
</head>
<body>";
$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
$index .= '<div class="photos"><span style="font-size:20px;font-weight:bold;">Fotos '.$pages.'&nbsp;&nbsp;<a href="photos'.($pages+1).'.html">&gt;&gt;</a></span><div style="height:110px;padding:5px;">';
foreach($photos as $ph){	
	if($count2 > 0 and $count2 % 5 == 0){
		$index .= '</div><br/><div style="height:130px;padding:5px;">';
	}
	if($count2 > 0 and $count2 % 20 == 0){
		$index .= "</div>";
		$index .= "
		</body>
		</html>";
		file_put_contents($path."photos".$pages.".html",$index);
		++$pages;
		$index = "
		<html>
		<head>
		<link rel='stylesheet' type='text/css' href='style.css' />
		<title>Fotos &bull; Tuenti Dump</title>
		</head>
		<body>";
		$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
		$index .= '<div class="photos"><span style="font-size:20px;font-weight:bold;">Fotos '.$pages.'&nbsp;&nbsp;<a href="photos'.max(1,($pages-1)).'.html">&lt;&lt;</a>&nbsp;&nbsp;<a href="photos'.($pages+1).'.html">&gt;&gt;</a></span><div style="height:110px;padding:5px;">';
	}
	++$count2;
	$big = $tuenti->getPhoto($ph["id"]);
	@file_put_contents($path."images/".$ph["id"]."_small",file_get_contents($ph["thumb"]));
	@file_put_contents($path."images/".$ph["id"],file_get_contents($big["url"]));
	$index .= '<div style="padding:5px;display:inline;float:left;"><a href="'."images/".$ph["id"].'" target="_blank"><img src="'."images/".$ph["id"]."_small".'" width="120" alt="'.$big["title"].'" title="'.$big["title"].'"/><div style="width:120px;font-size:9px;">'.$big["title"].'</div></a></div>';
	show_status($count2,$count);
}
if(($count2 + 5) % 5 == 0){
	$index .= "</div>";
}
	$index .= "</div>";
	$index .= "
	</body>
	</html>";
	file_put_contents($path."photos".$pages.".html",$index);
unset($photos);

echo "[*] writing friends page...",PHP_EOL;
$friends = $tuenti->getFriends($userId);
$index = "
<html>
<head>
<link rel='stylesheet' type='text/css' href='style.css' />
<title>Amigos &bull; Tuenti Dump</title>
</head>
<body>";
$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
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
echo "\r";
echo "[*] writing friends personal pages...",PHP_EOL;
$count2 = 0;
foreach($friends as $friend){
	++$count2;
	if(!file_exists($path."images/".$friend["userId"])){
		@file_put_contents($path."images/".$friend["userId"],file_get_contents($tuenti->getProfileImage("medium",$friend["userId"])));
	}
	$tuenti->progress=false;
	$states = $tuenti->getUserStates(10,$friend["userId"]);
	$tuenti->progress=true;
	$index = "
	<html>
	<head>
	<link rel='stylesheet' type='text/css' href='style.css' />
	<title>".$friend["userFirstName"]." ".$friend["userLastName"]." &bull; Tuenti Dump</title>
	</head>
	<body>";
	$index .= '<div class="menuHeader"><span style="font-weight:bold;font-size:30px;">Tuenti</span>&nbsp;&nbsp;<a href="index.html">Perfil</a><a href="messages.html">Mensajes</a><a href="friends.html">Amigos</a><a href="photos1.html">Fotos</a></div>';
	$index .= '<div class="userHeader"><a href="images/'.$friend["userId"].'_big" target="_blank"><img src="images/'.$friend["userId"].'"/></a><h1 class="userName">'.$friend["userFirstName"]." ".$friend["userLastName"].'</h1><span class="state">'.$states[0].'</span></div><br/>';
	if(count($states)>0){
		@file_put_contents($path."images/".$friend["userId"]."_big",file_get_contents($tuenti->getProfileImage("big",$friend["userId"])));
		$tuenti->progress=false;
		$posts = $tuenti->getPosts(2,$friend["userId"]);
		$tuenti->progress=true;
		$index .= '<div class="posts"><span style="font-size:20px;font-weight:bold;">Espacio personal</span><br/>';
		foreach($posts as $post){
			$index .= '<div class="post"><b>'.$post["title"].'</b><br>'.stylize(nl2br($post["text"])).'</div>';
		}
		$index .= '</div><br/><br/>';	
		$index .= '<div class="states"><span style="font-size:20px;font-weight:bold;">Estados</span><br/>';
		foreach($states as $state){
			$index .= '<div class="state">'.stylize($state).'</div>';
		}
		$index .= "</div>";
	}else{
		$index .= '<span style="font-size:20px;font-weight:bold;">Perfil privado</span>';
	}
	$index .= "
	</body>
	</html>";
	file_put_contents($path.$friend["userId"].".html",$index);
	show_status($count2,$count);
	unset($states);
}
unset($friends);



die("[+] Done!!".PHP_EOL);

function stylize($text){
	if (preg_match("#(http://www.youtube.com)?/(v/([-|~_0-9A-Za-z]+)|watch\?v\=([-|~_0-9A-Za-z]+)&?.*?)#i", $text)) {
		$vidurl = strstr($text,"?v="); //GRAB VIDEO ID
		$vidarray = explode("v=",$vidurl);
		$stripParameters = explode("&",$vidarray[1]);
		$stripBreaks = explode("<br />",$stripParameters[0]);
		$stripSpaces = explode(" ",$stripBreaks[0]);//NICE, CLEAN VIDEO ID
		$viewVideo = '<iframe class="youtube-player" type="text/html" width="240" height="150" src="http://www.youtube.com/embed/' . $stripSpaces[0] . '" frameborder="0"></iframe>';
		$text = preg_replace("#(http://www.youtube.com)?/(v/([-|~_0-9A-Za-z]+)|watch\?v\=([-|~_0-9A-Za-z]+)&?.*?)#i",$viewVideo, $text);
	}
	$text = preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" target=\"_blank\">$3</a>", $text);  
    $text = preg_replace("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" target=\"_blank\">$3</a>", $text);  
	return $text;
}

function getCSS(){
$css = <<<CSS
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
.posts{
	width: 500px;
}
.posts .post{
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