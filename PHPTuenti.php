<?php

/*
 * PHPTuenti by @shoghicp
 * https://github.com/shoghicp/PHPTuenti
 * Under LGPL LICENSE
*/

$PHPTuentiPath = dirname(__FILE__)."/";

class PHPTuenti{
	protected $cookie, $csrf_token, $user, $cache, $DOMcache, $chat;
	
	public function logout(){
		$this->cookie['tempHash'] = "m=Logout&func=log_out";
		$ch = curl_init("http://www.tuenti.com/?m=Logout&func=log_out&ajax=1&store=0&ajax_target=canvas");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'user_id' => $this->user["userId"],
			'csfr' => $this->csrf_token,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);	
	}
	
	public function getViews(){
		$page = $this->get("?".$this->page("home")."&ajax=1&store=1&ajax_target=canvas",true);
		return $page->find("div.views",0)->first_child()->innertext;
	}
	
	public function getRestInvites(){
		$page = $this->get("?".$this->page("home")."&ajax=1&store=1&ajax_target=canvas",true);
		return $page->find("div#invitations",0)->find("span.tip",0)->first_child()->innertext;
	}

	public function getFriendsCount($user=""){
		if($user!=""){$user = "&user_id=".$user;}
		$page = $this->get("?".$this->page("profile")."&ajax=1&store=1&ajax_target=canvas".$user,true);
		return str_replace(array("(",")","."),"",$page->find("div#friends",0)->find("span.counter",0)->innertext);
	}
	
	public function getFriends($user=""){
		if($user != ""){
			$count = ceil($this->getFriendsCount($user)/10);
			$page2 = "?".$this->page("search")."&category=people&filters=".urlencode('{"user_scope":4,"other_user":'.$user.'}')."&ajax=1&store=1&ajax_target=canvas";
		}else{
			$user = $this->getUserId();
			$count = ceil($this->getFriendsCount()/10);
			$page2 = "?".$this->page("search")."&category=people&filters=".urlencode('{"user_scope":1}')."&ajax=1&store=1&ajax_target=canvas";
		}
		$friends = array();
		for($i=0;$i<$count;++$i){
			$page = $this->get($page2."&page_no=".$i,true,false);
			foreach($page->find("ul.searchResults",0)->find("li") as $friendO){
				$id = substr($friendO->first_child()->id,10);
				$friend = $friendO->find("div.itemInfoSearch",0);
				$friends[$id] = array();
				$name = ($this->getUserId()==$user) ? explode(" ",$friend->first_child()->first_child()->innertext):explode(" ",$friend->first_child()->innertext);
				$friends[$id]["userId"] = $id;
				$friends[$id]["userFirstName"] = $name[0];
				unset($name[0]);
				$friends[$id]["userLastName"] = trim(implode(" ",$name));
				$network = explode("<br/>",$friend->find("p.networks",0)->innertext);
				$friends[$id]["userUbication"] = strstr(str_replace('</span>','',strstr($network[0],'</span>')),"<a ",true).str_get_html($network[0])->find("a",0)->innertext;
				$friends[$id]["userStudies"] = str_replace('</span>','',strstr($network[1],'</span>'));
			}
		}
		return $friends;
	}

	public function getUserInfo($useri=""){
		if($useri!=""){
			$useri = intval($useri);
			$page = $this->get("?".$this->page("profile")."&ajax=1&store=1&ajax_target=canvas&user_id=".$useri);
			$user = array();
			$name = explode(" ",$page->find("h1#profile_status_title",0)->innertext);
			$user["userFirstName"] = $name[0];
			unset($name[0]);
			$user["userLastName"] = trim(implode(" ",$name));
			$user["userMail"] = ""; //No tengo tiempo :p
			$user["userId"] = $useri;
			return $user;
		}else{
			return $this->user;	
		}
	}
	
	public function getUserState($user=""){
		if($user==""){
			$user = $this->getUserId();
		}
		$page = $this->get("?".$this->page("profile")."&ajax=1&store=1&ajax_target=canvas&user_id=".$user,true);
		return (($user==$this->getUserId()) ? $page->find("p.status",0)->plaintext:strip_tags(strstr(str_replace(array('corner"></span>',"&nbsp;"),'',strstr($page->find("div.statusBox",0)->innertext,'corner"></span>')),"<span",true)));
	}
	
	public function getUserId(){
		return $this->user["userId"];	
	}	
	
	public function uploadPhoto($path){
		$ch = curl_init("http://fotos.tuenti.com/?m=upload&iframe=1");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'func' => 'addq',
			'wf' => "",
			'rotate' => 0,
			basename($path) => "@".$path,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$page = curl_exec($ch);
		$ch = curl_init("http://fotos.tuenti.com/?m=upload&iframe=1");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'func' => 'checkq',
			'wf' => "",
			'qid' => 1,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$page = curl_exec($ch);
		$ch = curl_init("http://www.tuenti.com/?m=Uploadphoto&func=log_uploaded_photos&ajax=1&store=0&ajax_target=canvas");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'usi' => 1,
			'csfr' => $this->csrf_token,
			'uup' => 4,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$page = curl_exec($ch);
	}
	
	public function sendInvite($email){
		$ch = curl_init("http://www.tuenti.com/?m=Home&func=process_invitation&ajax=1&store=0&ajax_target=canvas");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'migration' => 'false',
			'csfr' => $this->csrf_token,
			'invitation_address' => $email,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);
	}

	public function postBlogEntry($title,$text){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init("http://www.tuenti.com/?m=Profile&func=process_add_blog_entry&ajax=1&store=0&ajax_target=blog");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'user_id' => $this->user["userId"],
			'csfr' => $this->csrf_token,
			'blog_entry_title' => $title,
			'blog_entry_body' => $text,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);
	}
	
	public function postStatus($status,$twitter=false){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init("http://www.tuenti.com/index.php?control-mode=1");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'req' => '[{"csfr":"'.$this->csrf_token.'"},{"Status":{"updateStatus":{"statusRaw":"'.addslashes($status).'","postToTwitter":'.(($twitter==true) ? "true":"false").'}}}]',
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);
	}
	
	public function sendMessage($text,$user){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init("http://www.tuenti.com/index.php?control-mode=1");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'req' => '[{"csfr":"'.$this->csrf_token.'"},{"Messages":{"newThread":{"toUserId":"'.$user.'","messageBody":"'.$text.'"}}}]',
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);
	}
	
	public function postToUserWall($status,$user){
		$user = intval($user);
		$this->cookie['tempHash'] = $this->page("index")."&user_id=".$user;
		$ch = curl_init("http://www.tuenti.com/?m=Wall&func=process_create_wall_post&wall_id=1%2C".$user."&ajax=1&store=0&ajax_target=wall");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'wall_post_body' => $status,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);		
	}
	
	public function changePassword($old,$new){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init("http://www.tuenti.com/?m=Settings&func=process_change_password_settings&ajax=1&store=0&ajax_target=canvas");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'old_password' => $old,
			'new_password' => $new,
			'new_password_copy' => $new,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);	
	}
	
	public function changePrivacy($profile,$wall,$photos,$messages,$number){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init("http://www.tuenti.com/?m=Settings&func=process_privacy_settings&ajax=1&store=0&ajax_target=canvas");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'profile' => $profile,
			'wall' => $wall,
			'download_photos' => $photos,
			'messages' => $messages,
			'phone_numbers' => $number,			
		));
		/*
		No one: 0
		Friends: 10
		Friends of friends: 20
		All Tuenti: 50		
		*/
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);	
	}

	public function changeAbout($hobbies,$music,$quotes,$books,$movies,$about_me){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init("http://www.tuenti.com/?m=Settings&func=process_settings_details&ajax=1&store=0&ajax_target=canvas");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'hobbies' => $hobbies,
			'music' => $music,
			'quotes' => $quotes,
			'books' => $books,
			'movies' => $movies,	
			'about_me' => $about_me,
		));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);	
	}
	
	
	
	/*
	-------------------------------------------------
	*/
	
	public function login_cookie($cookie){
		$this->cookie = array();
		$cookie = explode(";",$cookie);
		foreach($cookie as $c){
			$c = trim($c);
			if($c!=""){
				$b = explode("=",$c);
				$this->cookie[$b[0]]=urldecode($b[1]);
			}
		}
		$page=$this->page("index");
		$this->cookie['redirect_url'] = $page;
		$page = $this->load($page);
		unset($this->cookie['redirect_url']);
		if(strpos($page,"self.location.href='http://www.tuenti.com/?m=login';")!==false){
			return false;
		}
		$this->setConf($page);
		return true;
	}
	
	public function login($email,$password){
		$ch = curl_init("https://secure.tuenti.com/?m=Login&func=do_login");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'email' => $email,
			'remember' => 'on',
			'input_password' => $password,
			'timezone' => '1',
			'timestamp' => '1',
		));
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$page = curl_exec($ch);
		if(strpos($page,"Location: http://www.tuenti.com/?m=login")!==false){
			return false;
		}
		$headers = explode("\n",$page);
		foreach($headers as $head){
			$head = explode(":",$head);
			if(trim($head[0])=='Set-Cookie'){
				$cookie = explode(";",trim($head[1]));
				$b = explode("=",trim($cookie[0]));
				$this->cookie[$b[0]]=urldecode($b[1]);				
			}
		}
		$this->setConf();
		return true;
	}
	
	public static function page($page){
		$pages = array(
			"index" => "m=Home&func=index",
			"home" => "m=Home&func=view_home",
			"profile" => "m=Profile&func=index",
			"search" => "m=Multiitemsearch&func=index",
		);
		return $pages[$page];
	}
	
	protected function setConf($page=false){
		if($page===false){
			$page=$this->page("index");
			$this->cookie['redirect_url'] = $page;
			$page = $this->load($page);
			unset($this->cookie['redirect_url']);		
		}
		$this->csrf_token = substr(strstr($page,',"csrf":"'),9,8);
		$this->user = array();
		$this->user["userFirstName"] = strstr(str_replace(',"userFirstName":"','',strstr($page,',"userFirstName":')),'"',true);
		$this->user["userLastName"] = strstr(str_replace(',"userLastName":"','',strstr($page,',"userLastName":')),'"',true);
		$this->user["userMail"] = strstr(str_replace(',"userMail":"','',strstr($page,',"userMail":')),'"',true);
		$this->user["userId"] = strstr(str_replace('"requestHandler":{"username":','',strstr($page,'"requestHandler":{"username":')),',',true);
	}
	
	protected function get_cookies(){
		$str = "";
		foreach($this->cookie as $ord => $val){
			if($ord=="tempHash" or $ord=="redirect_url"){
				$value = $val;
			}else{
				$value = urlencode($val);
			}
			$str .= $ord."=".$value."; ";
		}	
		return $str;
	}
	
	public function load($page, $HtmlDOM=false, $cache=true){
		if($cache == true and $this->getCache($page,true)){
			$str = $this->getCache($page);
		}elseif($HtmlDOM == true and $cache == true and $this->getDOMCache($page,true)){
			return $this->getDOMCache($page);
		}else{
			$this->cookie['tempHash'] = $page;
			$ch = curl_init("http://www.tuenti.com/");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Referer' => 'http://www.tuenti.com/',
			));
			curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$str = curl_exec($ch);
			$this->setCache($page,$str);
		}
		if($HtmlDOM == true){
			$str = str_get_html($str);
			$this->setDOMCache($page,$str);
		}
		return $str;		
	}

	public function get($page, $HtmlDOM=false, $cache=true){
		if($cache == true and $this->getCache($page,true)){
			$str = $this->getCache($page);
		}elseif($HtmlDOM == true and $cache == true and $this->getDOMCache($page,true)){
			return $this->getDOMCache($page);
		}else{
			$ch = curl_init("http://www.tuenti.com/".$page);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Referer' => 'http://www.tuenti.com/',
			));
			curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$str = curl_exec($ch);
			$this->setCache($page,$str);
		}
		if($HtmlDOM == true){
			$str = str_get_html($str);
			$this->setDOMCache($page,$str);
		}
		return $str;
	}
	
	protected function setCache($name,$value){
		$name = md5($name);
		$this->cache[$name] = gzdeflate($value,9);
		return true;
	}
	
	protected function getCache($name,$exist=false){
		$name = md5($name);
		if(isset($this->cache[$name])){
			if($exist==false){
				return gzinflate($this->cache[$name]);
			}
			return true;
		}
		return false;
	}
	
	protected function setDOMCache($name,$html){
		$name = md5($name);
		$this->DOMCache[$name] = str_get_html($html);
		return true;
	}
	
	protected function getDOMCache($name){
		$name = md5($name);
		if(isset($this->DOMCache[$name])){
			if($exist==false){
				return $this->DOMCache[$name];
			}
			return true;
		}
		return false;
	}
	
	
	/*
	Chat Handling (doesn't work yet!)
	*/
	
	public function connectChat(){
		if(is_object($this->chat)){
			$this->disconnectChat();
		}
		$host = "xmpp3.tuenti.com";
		$this->chat = new XMPPHP_XMPP($host,5222,$this->getUserId()."@".$host,  $this->cookie["sid"],"xmpphp");
		$this->chat->connect();
		$this->chat->processUntil('session_start');
	}
	
	
	
	
	function __construct(){
		$this->cache = array();
		$this->DOMCache = array();
	}
	
}


if(!function_exists('gzdeflate')){
	function gzdeflate($str,$val){
		return $str;
	}
	function gzinflate($str){
		return $str;
	}
}

if(!function_exists('file_get_html')){
	require_once($PHPTuentiPath."simple_html_dom.php"); //PHP Simple HTML DOM Parser
}
if(!class_exists('XMPPHP_XMPP')){
	require_once($PHPTuentiPath."XMPPHP/XMPP.php"); //XMPPHP: The PHP XMPP Library
}

?>