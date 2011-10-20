<?php

/*
 * PHPTuenti by @shoghicp
 * https://github.com/shoghicp/PHPTuenti
 * Under LGPL LICENSE
*/

$PHPTuentiPath = dirname(__FILE__)."/";

class PHPTuenti{
	protected $cookie, $csrf_token, $user, $cache, $DOMcache, $chat, $progress, $useCache;
	
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
	
	public function getProfileImage($size="medium",$user=""){
		if($user==""){$user = $this->getUserId();}
		$page = $this->get("?".$this->page("profile")."&ajax=1&store=1&ajax_target=canvas&user_id=".$user,true);
		if(is_object($page->find("div#multiitemsearch",0))){
			return false;
		}
		switch($size){
			case "big":
				$photoId = str_replace(array("&amp;s=0","#m=Photo&amp;func=view_photo&amp;collection_key="),"",$page->find("div#avatar",0)->find("a",0)->href);
				$ch = curl_init("http://pdta.tuenti.com/");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(
					'req' => '[{"csfr":"'.$this->csrf_token.'"},{"Photo":{"preloadPhotos":{"itemKey":"'.$photoId.'","backgrounded":false,"prefetchDirection":10,"offset":0,"source":22,"pc":{"wt":2}}}}]',
				));
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Referer' => 'http://www.tuenti.com/',
				));
				curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$ret = json_decode(curl_exec($ch),true);
				$ret = $ret["output"][0][$photoId]["getPhoto"]["url"];
				break;
				
			case "medium":
			default:
				$ret = $page->find("div#avatar",0);
				if(is_object($ret)){$ret = $ret->find("img",0)->src;}else{return false;}
				break;
		}
		unset($page);
		return $ret;		
	}

	public function getFriendsCount($user=""){
		if($user!=""){$user = "&user_id=".$user;}
		$page = $this->get("?".$this->page("profile")."&ajax=1&store=1&ajax_target=canvas".$user,true);
		return str_replace(array("(",")","."),"",$page->find("div#friends",0)->find("span.counter",0)->innertext);
	}	
	
	public function getFriends($user=""){
		if($user != ""){
			$count2 = $this->getFriendsCount($user);
			$count = ceil($count2/10);
			$page2 = "?".$this->page("search")."&category=people&filters=".urlencode('{"user_scope":4,"other_user":'.$user.'}')."&ajax=1&store=1&ajax_target=canvas";
		}else{
			$user = $this->getUserId();
			$count2 = $this->getFriendsCount();
			$count = ceil($count2/10);
			$page2 = "?".$this->page("search")."&category=people&filters=".urlencode('{"user_scope":1}')."&ajax=1&store=1&ajax_target=canvas";
		}
		$count3=0;
		$friends = array();
		for($i=0;$i<$count;++$i){
			$page = $this->get($page2."&page_no=".$i,true,false);
			foreach($page->find("ul.searchResults",0)->find("li") as $friendO){
				++$count3;
				$id = substr($friendO->first_child()->id,10);
				$friend = $friendO->find("div.itemInfoSearch",0);
				$friends[$id] = array();
				$name = ($this->getUserId()==$user) ? explode(" ",$friend->first_child()->first_child()->innertext." "):explode(" ",(!is_object($friend->first_child()->first_child()) ? $friend->first_child()->innertext:$friend->first_child()->first_child()->innertext)." ");
				$friends[$id]["userId"] = $id;
				$friends[$id]["userFirstName"] = $name[0];
				unset($name[0]);
				$friends[$id]["userLastName"] = trim(implode(" ",$name));
				$network = explode("<br/>",$friend->find("p.networks",0)->innertext);
				$friends[$id]["userUbication"] = strstr(str_replace('</span>','',strstr($network[0],'</span>')),"<a ",true).str_get_html($network[0])->find("a",0)->innertext;
				$friends[$id]["userStudies"] = str_replace('</span>','',strstr($network[1],'</span>'));
				if($this->progress==true){
					$this->show_status($count3,$count2);
				}
			}
			unset($page,$network);
		}
		return $friends;
	}
	
	public function getMessages($box="inbox"){
		/*
			ESTA FUNCION REALIZA MUCHAS PETICIONES!!!!
			advertido estas		
		*/
		$this->cookie['tempHash'] = "m=Message&func=index&boxName=".$box;
		switch($box){		
			case "inbox":
			default:
				$box="getInbox";
			break;
		}
		$count=1;
		$count2=1;
		$count3=0;
		$messages = array();
		for($i=0;$i<$count;++$i){		
			$ch = curl_init("http://www.tuenti.com/index.control.php");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(
				'req' => '[{"csfr":"'.$this->csrf_token .'"},{"Messages":{"'.$box.'":{"page":'.$i.'}}},{"Messages":"getUnreadSpamCount"},{"Messages":"getUnreadThreads"},{"Messages":"getUnreadVoicemailCount"}]',
			));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Referer' => 'http://www.tuenti.com/',
			));
			curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$ret = json_decode(curl_exec($ch),true);
			$count = ceil($ret["output"][0]["pager"]["totalItems"]/25);
			$count2 = $ret["output"][0]["pager"]["totalItems"];
			foreach($ret["output"][0]["threadBox"]["threads"] as $mess){
				++$count3;
				$ch = curl_init("http://www.tuenti.com/index.control.php");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(
					'req' => '[{"csfr":"'.$this->csrf_token .'"},{"Messages":{"getThreadContents":{"threadId":"'.$mess["threadId"].'","boxName":"inbox"}}}]',
				));
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Referer' => 'http://www.tuenti.com/',
				));
				curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$thread = json_decode(curl_exec($ch),true);
				if($this->progress==true){
					$this->show_status($count3,$count2);
				}
				if(count($thread["output"][0]["messages"]) == 0){
					continue;
				}
				$messages[$mess["threadId"]] = array();
				foreach($thread["output"][0]["messages"] as $id => $tMess){
					$messages[$mess["threadId"]][$id] = array();
					$messages[$mess["threadId"]][$id]["isUnread"] = ($tMess["isUnread"]==1) ? true:false;
					$messages[$mess["threadId"]][$id]["senderId"] = $tMess["senderId"];
					$messages[$mess["threadId"]][$id]["senderFullName"] = utf8_decode($tMess["senderFullName"]);
					$messages[$mess["threadId"]][$id]["senderIsMe"] = ($this->getUserId==$tMess["senderId"]) ? true:false;
					$messages[$mess["threadId"]][$id]["sentDate"] = $tMess["sentDate"];
					$messages[$mess["threadId"]][$id]["messageBody"] = "";
					foreach($tMess["richMedia"] as $line){
						$messages[$mess["threadId"]][$id]["messageBody"] .= "\r\n".utf8_decode($line["lines"][0]["string"]);
					}
					$messages[$mess["threadId"]][$id]["messageBody"] = trim(strip_tags($messages[$mess["threadId"]][$id]["messageBody"]));
				}
			}
		}
		return $messages;
	}

	public function getUserInfo($useri=""){
		if($useri==""){$useri=$this->getUserId();}
			$useri = intval($useri);
			
			if($useri!=$this->getUserId()){
				$page = $this->get("?".$this->page("profile")."&ajax=1&store=1&ajax_target=canvas&user_id=".$useri,true);
				$name = explode(" ",strstr($page->find("h1#profile_status_title",0)->innertext,"<",true));
			}else{
				$page = $this->get("?".$this->page("index")."&ajax=1&store=1&ajax_target=canvas",true);
				$name = explode(" ",$page->find("a#home_user_name",0)->innertext);
			}
			$user = array();
			$user["userFirstName"] = $name[0];
			unset($name[0]);
			$user["userLastName"] = trim(implode(" ",$name));
			$user["userMail"] = ""; //No tengo tiempo :p
			$user["userId"] = $useri;
			return $user;
	}
	
	public function getUserState($user=""){
		if($user==""){
			$user = $this->getUserId();
		}
		$page = $this->get("?m=Wall&func=view_wall_posts&filter=1&filter_author=0&wall_page=0&wall_id=1%2C".$user."&ajax=1&store=0&ajax_target=wall_posts_content",true);
		if(is_object($page->find("div#multiitemsearch",0))){
			return false;
		}
		return $page->find("p.status",0)->plaintext;
	}
	
	public function getUserStates($limit=20,$user=""){
		if($user==""){
			$user = $this->getUserId();
		}
		$arr = array();
		$limit=ceil($limit/10);
		for($i=0;$i<$limit;++$i){
			$page = $this->get("?m=Wall&func=view_wall_posts&filter=1&filter_author=0&wall_page=".$i."&wall_id=1%2C".$user."&ajax=1&store=0&ajax_target=wall_posts_content",true);
			if(is_object($page->find("div#multiitemsearch",0))){
				return array();
			}
			foreach($page->find("p.status") as $status){
				$arr[] = $status->plaintext;
			}
		}
		return $arr;
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
	
	public function changeUserName($first,$last){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init("http://www.tuenti.com/?m=Settings&func=process_change_name&ajax=1&store=0&ajax_target=canvas");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'first_name' => $first,
			'surname' => $last,
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
		$this->user["userId"] = strstr(str_replace('"requestHandler":{"username":','',strstr($page,'"requestHandler":{"username":')),',',true);
		$this->user = $this->getUserInfo();
		$this->user["userMail"] = strstr(str_replace(',"userMail":"','',strstr($page,',"userMail":')),'"',true);
		
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
		if($cache == true and $this->useCache == true and $this->getCache($page,true)){
			$str = $this->getCache($page);
		}elseif($HtmlDOM == true and $cache == true and $this->useCache == true and $this->getDOMCache($page,true)){
			return $this->getDOMCache($page);
		}else{
			$this->cookie['tempHash'] = $page;
			$ch = curl_init("http://www.tuenti.com/");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Referer' => 'http://www.tuenti.com/',
			));
			curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$str = utf8_decode(str_replace(array('"href','"onclick','"title','"src'),array('" href','" onclick','" title','" src'),curl_exec($ch)));
			if($cache == true and $this->useCache == true){
				$this->setCache($page,$str);
			}
		}
		if($HtmlDOM == true){
			$str = str_get_html($str);
			if($cache == true and $this->useCache == true){
				$this->setDOMCache($page,$str);
			}
		}
		return $str;		
	}

	public function get($page, $HtmlDOM=false, $cache=true){
		if($cache == true and $this->useCache == true and $this->getCache($page,true)){
			$str = $this->getCache($page);
		}elseif($HtmlDOM == true and $cache == true and $this->useCache == true and $this->getDOMCache($page,true)){
			return $this->getDOMCache($page);
		}else{
			$ch = curl_init("http://www.tuenti.com/".$page);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Referer' => 'http://www.tuenti.com/',
			));
			curl_setopt($ch, CURLOPT_COOKIE, $this->get_cookies());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$str = utf8_decode(str_replace(array('"href','"onclick','"title','"src'),array('" href','" onclick','" title','" src'),curl_exec($ch)));
			if($cache == true and $this->useCache == true){
				$this->setCache($page,$str);
			}
		}
		if($HtmlDOM == true){
			$str = str_get_html($str);
			if($cache == true and $this->useCache == true){
				$this->setDOMCache($page,$str);
			}
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
	
	protected function show_status($done, $total, $size=30) {
		static $start_time;
		if($done > $total){ return false;}

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
	
	
	function __construct($cache=true,$progress=false){
		$this->cache = array();
		$this->DOMCache = array();
		$this->useCache = $cache;
		$this->progress = $progress;
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