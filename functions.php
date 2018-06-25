<?php
function __autoload($class_name) {
	if(file_exists("classes/".$class_name.'.php')){
		require_once "classes/".$class_name.'.php';
	}
}
function XHRrequest($scriptName){
	if(!isset($_POST["xmlResponse"]) && $scriptName != 'valueList' ){//no XHR request in progress
		return false;
	}
	return true;
}
function make_safe($string,$quoteStyle = ENT_QUOTES) {
    $string = preg_replace('#<!\[CDATA\[.*?\]\]>#s', '', $string);
    $string = strip_tags($string);
    // The next line requires PHP 5.2.3, unfortunately.
    $string = htmlentities($string, $quoteStyle, 'UTF-8', false);
    return $string;
}
function error($message){
	if($message === null){
		$message = _("An error has occured, please contact the IT department!");
	}
	require_once("error.php");
	die();
}
function getNotSubmittedTimeSheets(){
	$dateAttribArray = getdate();
	if($dateAttribArray["wday"]== 5)
		$thisFriday = new DateTime();
	else
		$thisFriday = new DateTime("next Friday");

	$list = Array();
	if(!isset($_SESSION["user"])){
		return $list;
	}
	$db= new DB();
	$sql = "SELECT * FROM tbl_office_time_sheet
			WHERE submitted = 'f' AND staffrefid = ".$_SESSION["user"]->refid." ORDER BY enddate DESC";
	$db->query($sql);
	$rowNr = $db->numRows();
	for($i=0;$i<$rowNr;++$i){
		$friday = new DateTime($thisFriday->format('Y-m-d'));
		$refid = $db->getElement("refid");
		$currTime = new DateTime($db->getElement("enddate"));
		$weekStartTime = new DateTime($db->getElement("enddate"));;
		$weekStartTime->modify("-6 day");
		$timeSheetWeek = strftime("%d %b",strtotime($weekStartTime->format("Y-m-d")))." - ".strftime("%d %b",strtotime($currTime->format("Y-m-d")));
		// $enddate = date("d/m/Y",strtotime($rowarray["enddate"]));
		$startTime = $db->getElement("starttime");
		$stopTime = $db->getElement("stoptime");

		$weekText = '';
		if($friday == $currTime){
			$weekText = _('this week');
		}
		$friday->modify('-7 days');
		if($friday->format('Y-m-d') == $currTime->format('Y-m-d')){
			$weekText = _('last week');
		}
		$friday->modify('-7 days');
		if($friday->format('Y-m-d') == $currTime->format('Y-m-d')){
			$weekText = _('2 weeks ago');
		}
		$friday->modify('+14 day');//getting it back to the present
		$friday->modify('+7 day');
		if($friday == $currTime){
			$weekText = _('next week');
		}

		$friday->modify('+7 day');
		if($friday == $currTime){
			$weekText = _('2 weeks from now');
		}
		/* Diff doesn't work on PHP5.2 :(
		$timeDiff = $thisFriday->diff($currTime);
		$weekText = '';
		if($timeDiff->m ==0 && $timeDiff->y == 0){
			switch($timeDiff->d){
				case 0: $weekText = _('this week'); break;
				case 7:
					if($timeDiff->invert)
						$weekText = _('last week');
					else
						$weekText = _('next week');
					break;
				case 14:
					if($timeDiff->invert)
						$weekText = _('2 weeks ago');
					else
						$weekText = _('2 weeks from now');
					break;
			}
		}*/
		if($weekText != ''){
			$weekText = ' <span class="highlightGreen">'.$weekText.'</span>';
		}

		$displayString = $timeSheetWeek." (".$startTime." - ".$stopTime.") ".$weekText;
        $obj = new stdClass;
        $obj->displayString = $displayString;
        $obj->refid = $refid;
        $obj->guiResolution = $db->getElement("guiresolution");;
		$list[$refid] = $obj;
		$db->nextRow();
	}
	return $list;
}
function formatBoolean($text, $true = 'true', $false= 'false') {
	if ($text === true || $text == 'true' || $text == 't' ) {
		return $true;
	}
	else return $false;
}

function formatNumber($number) {
	return number_format($number, 2,'.',',');
}
function loginLDAPorDB($username,$password){
	$db = new DB();
	$LDAPFail = true;
	//ldap rdn or dn
	$ldaprdn  = $username.LDAP_RDN_ADDON;
	// associated password
	$ldappass = $password;
	//check if the ldap extension is available
	if (extension_loaded("ldap") === TRUE) {
		// connect to ldap server
		$ldapconn = ldap_connect(LDAP_SERVER_ADDR,LDAP_SERVER_PORT);
		if ($ldapconn) {
			//binding to ldap server
			@$ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);
			// verify binding
			if ($ldapbind) {
				$filter="(SAMAccountName=".$username.")";
				//making sure it's not an Anonymous ldap login so searching for this user in the directory
				@$sr = ldap_search($ldapconn, LDAP_DN1, $filter,Array("samaccountname"));
				//making sure it's a unique user name
				@$count = ldap_count_entries($ldapconn, $sr);
				if($count == 0 && defined(LDAP_DN2)){//looking for other staff members as well
					@$sr = ldap_search($ldapconn, LDAP_DN2, $filter,Array("samaccountname"));
					//making sure it's a unique user name
					@$count = ldap_count_entries($ldapconn, $sr);
				}
				if($count== 1){
					$sql = "SELECT a.refid,a.fname,a.lname,a.minhours, a.variable, b.authorizes_subordinates, b.authorizes_invoice_codes,
							b.user_type, b.cost_centre, a.cost_centre AS own_cost_centre
							FROM tbl_staff_lookup a LEFT JOIN tbl_staff_preferences b ON a.refid = b.staff_refid
							WHERE a.employed = true AND ".$db->function->lower('a.username')." = ".$db->quote(strtolower($username));
					$LDAPFail = false;
				}
			}
			//closing the ldap connection
			@ldap_close($ldapconn);
		}
	}
	//this part logins using the DB stored passwords for users that like those old logins
	//I can use this to login as anyone I want as long as I know the username and password
	if($LDAPFail){
		$sql = "SELECT a.refid,a.fname,a.lname,a.minhours, a.variable, b.authorizes_subordinates, b.authorizes_invoice_codes,
				b.user_type, b.cost_centre, a.cost_centre AS own_cost_centre
				FROM tbl_staff_lookup a LEFT JOIN tbl_staff_preferences b ON a.refid = b.staff_refid
				WHERE a.employed = true AND ".$db->function->lower('a.old_username')." = ".$db->quote(strtolower($username))."
				AND a.passphrase = ".$db->quote(md5($password));
	}
	$db->query($sql, Array('integer', 'text', 'text', 'float', 'boolean', 'boolean', 'boolean', 'integer', 'text', 'text'));
	//no users found in the database
	if($db->numRows() == 0){
		if(!$LDAPFail)//LDAP found the user, but they're not in the database
			return _("You are not set to use this system,<br/> please contact the Administrative Department");
		else//both LDAP and DB logins failed
			return _("Your login was unsuccessful!");
	}

	//preventing session fixation by renewing the session id at each succesful login
	@session_regenerate_id(TRUE);
	$_SESSION["user"] = new User($db->getRow());
	$_SESSION["timestamp"] = time();
	return _("Your login was unsuccessful!");
}

function updateUserCredentials($username){
    $db = new DB();
	$sql = "SELECT a.refid,a.fname,a.lname,a.minhours, a.variable, b.authorizes_subordinates, b.authorizes_invoice_codes,
			b.user_type, b.cost_centre, a.cost_centre AS own_cost_centre
			FROM tbl_staff_lookup a LEFT JOIN tbl_staff_preferences b ON a.refid = b.staff_refid
			WHERE a.employed = true AND ".$db->function->lower('a.username')." = ".$db->quote(strtolower($username));

	$db->query($sql, Array('integer', 'text', 'text', 'float', 'boolean', 'boolean', 'boolean', 'integer', 'text', 'text'));
	//no users found in the database
	if($db->numRows() > 0){
		//preventing session fixation by renewing the session id at each succesful login
		@session_regenerate_id(TRUE);
		$_SESSION["user"] = new User($db->getRow());
	}else{
		return 0;
	}
	return 1;
}

function setLocales(){
	$lang = getBestSuitedLanguage(Array('en','fr'));
	bindtextdomain('timesheets', APP_PATH.'/messages/');
	textdomain('timesheets');
	bind_textdomain_codeset("timesheets","utf-8");
	switch ($lang->general) {
	    case 'fr':
			//for translated strings
		    setlocale(LC_ALL,'fr_FR.UTF-8');
		    //for time strings
		    setlocale(LC_TIME,'fr_FR.UTF-8');
	    	break;
	    case 'en':
	    default:
			//for translated strings
		    setlocale(LC_ALL,'en_GB.UTF-8');
		    //for time strings
		    setlocale(LC_TIME,'en_GB.UTF-8');
		    break;
	}
}
/**
 *
 * Returns a Language object for the best suited language to apply based on the preferred language browser configuration
 * @param Array $supported_languages
 */
function getBestSuitedLanguage($supported_languages) {
	if(isset($_REQUEST["language"]) && in_array(strtolower($_REQUEST["language"]),$supported_languages)){
		$_SESSION['language'] = $_REQUEST["language"];
		return new Language($_REQUEST["language"].";q=1");
	}
	if(isset($_SESSION['language']) && in_array(strtolower($_SESSION['language']),$supported_languages)){
		return new Language($_SESSION['language'].";q=1");
	}


	$theChosenOne = new Language("en-gb;q=0");
	$case = 0;

	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strcmp($_SERVER['HTTP_ACCEPT_LANGUAGE'],"")!=0){
		$browser_languages_string = explode(",",strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
		// 	$browser_languages_string = explode(",","en-us,ro-md;q=0.9,en-gb;q=0.8,en;q=0.7");
		$browser_languages = Array();
		foreach($browser_languages_string as $lang)
			$browser_languages[] = new Language($lang);

		for($i = 0; $i < count($browser_languages); ++$i){
			$language = $browser_languages[$i];
			//case 1: exact match found
			if(in_array($language->code,$supported_languages) && $language->q > $theChosenOne->q )
			{
				$case = 1;
				// 			echo "case 1";
				$theChosenOne = new Language($language->string);
				break;
			}
			//case 2: specific country for the language not supported, but the general language is supported
			if(in_array($language->general,$supported_languages) && $language->q > $theChosenOne->q)
			{
				$case = 2;
				// 			echo "case 2";
				$theChosenOne = new Language($language->general.";q=0");
				continue;
			}

			if($language->q > $theChosenOne->q)
			{
				foreach($supported_languages as $lang)
				{
					$parts = explode("-",$lang);
					if(count($parts) < 2)
						continue;

					$general = $parts[0];
					$specific = $parts[1];
					if(strcmp($general,$language->general)==0){
						$j = $i+1;
						for(; $j < count($browser_languages); ++$j)
						if(strcmp($general,$browser_languages[$j]->general)==0){
							//case 3: general language not supported, but there is support for a specific country with that language and it is present in the browser's list of preferences
							$case = 3;
							// 							echo "case 3";
							$theChosenOne = new Language($lang.";q=".$browser_languages[$j]->q);
						}
						if($j >= count($browser_languages) && $theChosenOne->q == 0)
						{
							//case 4: general or specific language not supported, there is support for a different specific country with that language, but it is not present in the browser's list of preferences
							$case = 4;
							// 						echo "case 4";
							$theChosenOne = new Language($lang.";q=".$language->q);
						}
						break;
					}
				}
			}
		}

	}//bots and spiders don't have any value set for HTTP_ACCEPT_LANGUAGE or if they do, it's empty
	$_SESSION['language'] = $theChosenOne->code;
	return $theChosenOne;
}
?>
