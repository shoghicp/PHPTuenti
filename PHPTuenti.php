<?php

/*
 * PHPTuenti by @shoghicp
 * Under LGPL LICENSE
*/

class PHPTuenti{
	protected $cookie, $csrf_token, $user;
	
	/*
	-------------------------------------------------
	*/
	
	public function logout(){
		$this->cookie['tempHash'] = "m=Logout&func=log_out";
		$ch = curl_init ("http://www.tuenti.com/?m=Logout&func=log_out&ajax=1&store=0&ajax_target=canvas");
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array(
			'user_id' => $this->user["userId"],
			'csfr' => $this->csrf_token,
		));
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);	
	}
	
	public function getViews(){
		$page = $this->get("?".$this->page("home")."&ajax=1&store=1&ajax_target=canvas");
		return strstr(str_replace('<div class="views"><strong>','',strstr($page,'<div class="views"><strong>')),'</strong>',true);
	}

	public function getFriendsCount(){
		$page = $this->get("?".$this->page("profile")."&ajax=1&store=1&ajax_target=canvas");
		return strstr(str_replace('return false;">Ver todos</a><span class="counter">(','',strstr($page,'return false;">Ver todos</a><span class="counter">(')),')</span>',true);
	}	

	public function postBlogEntry($title,$text){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init ("http://www.tuenti.com/?m=Profile&func=process_add_blog_entry&ajax=1&store=0&ajax_target=blog");
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array(
			'user_id' => $this->user["userId"],
			'csfr' => $this->csrf_token,
			'blog_entry_title' => $title,
			'blog_entry_body' => $text,
		));
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);
	}
	
	public function postStatus($status,$twitter=false){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init ("http://www.tuenti.com/index.php?control-mode=1");
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array(
			'req' => '[{"csfr":"'.$this->csrf_token.'"},{"Status":{"updateStatus":{"statusRaw":"'.addslashes($status).'","postToTwitter":'.(($twitter==true) ? "true":"false").'}}}]',
		));
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);
	}
	
	public function postToUserWall($status,$user){
		$user = intval($user);
		$this->cookie['tempHash'] = $this->page("index")."&user_id=".$user;
		$ch = curl_init ("http://www.tuenti.com/?m=Wall&func=process_create_wall_post&wall_id=1%2C".$user."&ajax=1&store=0&ajax_target=wall");
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'wall_post_body' => $status,
		));
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);		
	}
	
	public function changePassword($old,$new){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init ("http://www.tuenti.com/?m=Settings&func=process_change_password_settings&ajax=1&store=0&ajax_target=canvas");
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'old_password' => $old,
			'new_password' => $new,
			'new_password_copy' => $new,
		));
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);	
	}
	
	public function changePrivacy($profile,$wall,$photos,$messages,$number){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init ("http://www.tuenti.com/?m=Settings&func=process_privacy_settings&ajax=1&store=0&ajax_target=canvas");
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array(
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
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);	
	}

	public function changeAbout($hobbies,$music,$quotes,$books,$movies,$about_me){
		$this->cookie['tempHash'] = $this->page("home");
		$ch = curl_init ("http://www.tuenti.com/?m=Settings&func=process_settings_details&ajax=1&store=0&ajax_target=canvas");
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array(
			'csfr' => $this->csrf_token,
			'hobbies' => $hobbies,
			'music' => $music,
			'quotes' => $quotes,
			'books' => $books,
			'movies' => $movies,	
			'about_me' => $about_me,
		));
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);	
	}
	
	public function getUserInfo(){
		return $this->user;	
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
		$this->csrf_token = substr(strstr($page,',"csrf":"'),9,8);
		$this->user = array();
		$this->user["userFirstName"] = strstr(str_replace(',"userFirstName":"','',strstr($page,',"userFirstName":')),'"',true);
		$this->user["userLastName"] = strstr(str_replace(',"userLastName":"','',strstr($page,',"userLastName":')),'"',true);
		$this->user["userMail"] = strstr(str_replace(',"userMail":"','',strstr($page,',"userMail":')),'"',true);
		$this->user["userId"] = strstr(str_replace('"requestHandler":{"username":','',strstr($page,'"requestHandler":{"username":')),',',true);
		return true;
	}
	
	public static function page($page){
		$pages = array(
			"index" => "m=Home&func=index",
			"home" => "m=Home&func=view_home",
			"profile" => "m=Profile&func=index",
		);
		return $pages[$page];
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
	
	public function load($page){
		$this->cookie['tempHash'] = $page;
		$ch = curl_init ("http://www.tuenti.com/");
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);
	}

	public function get($page){
		$ch = curl_init ("http://www.tuenti.com/".$page);
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
			'Referer' => 'http://www.tuenti.com/',
		));
		curl_setopt ($ch, CURLOPT_COOKIE, $this->get_cookies());
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec ($ch);
	}	
	
}


?>