<?php
	require_once 'config.php';
	require_once 'functions.php';
	global $status;
/* This is to block access when things go wooohooo with the DB
if(!XHRrequest('')){
 	die("The TimeRecordingSystem is <b>DOWN :-(</b> for maintenance.<br/>Please try again later.<br/>If you were working on something, just leave the browser open and press the back button in an hour.
 	<br/><b>Sorry for the inconvenience!</b>");
}*/
	
    if(preg_match('/(?i)msie/',$_SERVER['HTTP_USER_AGENT'])){
		require_once("nobrowsersupport.php");
		exit;
	}
//	for debuging, uncomment the next line
	//ini_set('display_errors',1);
	ini_set("session.cookie_httponly",true);
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Tue, 29 Mar 1983 07:20:55 GMT");
	
	//phpCAS login
	include_once('CAS.php');
	//phpCAS::setDebug();
	// initialize phpCAS
	phpCAS::client(CAS_VERSION_2_0,CAS_SERVER,CAS_PORT,'');
	// no SSL validation for the CAS server
	phpCAS::setNoCasServerValidation();
	phpCAS::setCacheTimesForAuthRecheck(0);

    $isAjaxRequest = false;
    if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == strtolower('XMLHttpRequest')){
        $isAjaxRequest = true;
    }
    if(strstr($_SERVER["SCRIPT_NAME"],"parseResponse.php") !== FALSE
        || strstr($_SERVER["SCRIPT_NAME"],"validation.php") !== FALSE
        || strstr($_SERVER["SCRIPT_NAME"],"valueList.php") !== FALSE){
        $isAjaxRequest = true;
    }
	
	if(!isset($_SESSION["user"])){
        if($isAjaxRequest){
            //we have an XHR request
            die('nosession');
        }
		if(!phpCAS::checkAuthentication()){
            if(strpos($_SERVER['PHP_SELF'],'login.php')=== false){
                require_once("login.php");
                exit();
            }
		}else{
			if(updateUserCredentials(phpCAS::getUser()) == 0){
				//logged in via CAS, but no match in the database
				$status = _("You are not set to use this system,<br/> please contact the Administrative Department");
				require_once("index.php");
				exit();
			}
		}
	}
	// logout if desired
	if (isset($_REQUEST['logout'])) {
		phpCAS::logout();
	}
	@session_start();
	setLocales();
?>