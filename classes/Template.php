<?php
class Template
{
	//version should be updated everytime a change is done to a script or css file.
	//The version is added to all JS files and css files and the browser will reload them
	public $version = 2.70;
	public $hasInfoSection;
	public $infoSectionContent;
	public $hasMenu;
	public $printContent;
	public $title;
	public $subTitle;
	public $content;
	public $styleSheets;
	public $scriptFiles;//this is an array of script file paths
	private $vars;

	public function __construct($params = null)
	{
		$title = _("Time Recording System");
		$subTitle = "";
		$content="";
		$styleSheets = Array("timesheets.css");
		$scriptFiles = null;
		$hasInfoSection = false;
		$infoSectionContent = "";
		$hasMenu = true;
		$printContent = false;
		$onLoad = '';
		$this->vars = Array();
		if(isset($params["title"]))
			$title = $params["title"];
		if(isset($params["subTitle"]))
			$subTitle = $params["subTitle"];
		if(isset($params["content"]))
			$content = $params["content"];
		if(isset($params["styleSheets"]))
			$styleSheets = $params["styleSheets"];
		if(isset($params["scriptFiles"]))
			$scriptFiles = $params["scriptFiles"];
		if(isset($params["hasInfoSection"]))
			$hasInfoSection = $params["hasInfoSection"];
		if(isset($params["infoSectionContent"]))
			$infoSectionContent = $params["infoSectionContent"];
		if(isset($params["hasMenu"]))
			$hasMenu = $params["hasMenu"];
		if(isset($params["printContent"]))
			$printContent = $params["printContent"];
		if(isset($params["vars"]))
			$this->vars = $params["vars"];

		$this->printContent = $printContent;
		$this->hasInfoSection = $hasInfoSection;
		$this->infoSectionContent = $infoSectionContent;
		$this->hasMenu = $hasMenu;
		$this->title = $title;
		$this->subTitle = $subTitle;
		if(strcmp($subTitle,"")!=0)
			$this->title .= ": ".$subTitle;
		$this->content = $content;
		$this->styleSheets = $styleSheets;
		$this->scriptFiles = $scriptFiles;
	}

	private function displayMenu(){
		if (!$this->hasMenu){
			return;
		}
		$menu = "";
		?>
		<div id="menuSection">
			<div id="logout"><a class="inlineLink" href="index.php?logout"><?php echo _("Logout");?></a></div>
			<?php require('menu.php') ?>
		</div>
		<?php
		return $menu;
	}
	private function getFooter() {
		$footer = "";
		$footer .= '<div id="ft">';
		$footer .= '<p>'._("Copyright (C)").' '.date('Y').' <a href="http://newro.co/" title="newroco website">newroco</a> ';		
		$footer .= '</p></div>';
		return $footer;
	}
	private function printContent(){
		if ($this->printContent){
			echo $this->content;
		}else{
			//loading the vars
			foreach($this->vars as $var => $value){
				$$var = $value;
			}
			require_once('content/'.$this->content);
		}
	}

	public function display()
	{
        if(!isset($_SESSION['language'])){
            $_SESSION['language'] = 'en';
        }
		if(!defined("TEMPLATELOADED")){
			define("TEMPLATELOADED","true");
		}
		else {
			exit;//template already displayed
		}
		?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $_SESSION['language']?>" lang="<?php echo $_SESSION['language']?>">
			<head>
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<title><?php echo $this->title?></title>
		<?php
			if(isset($_REQUEST['print'])) {
				echo '</head><body>';
				if ($this->printContent){
					echo $this->content;
				}else{
					require $this->content;
				}
				echo '</body></html>';
				exit;
			}

			foreach($this->styleSheets as $sheet) {
				echo '<link href="'.$sheet.'?version='.$this->version.'" rel="stylesheet" type="text/css" />'."\n";
			}
		?>
				<link href="favicon.ico" type="image/x-icon" rel="shortcut icon"/>
				<script src="js/toolbox.js?version=<?php echo $this->version?>" type="text/javascript"></script>

		<?php
            if(isset($_SESSION["user"])) {
                echo "\t\t\t<script src=\"messages.php?version=".$this->version."\" type=\"text/javascript\"></script>\n";
            }
			if($this->scriptFiles !== null){
				foreach($this->scriptFiles as $file){
					echo "\t\t\t<script src=\"".$file."?version=".$this->version."\" type=\"text/javascript\"></script>\n";
				}
			}
		?>

			</head>
			<body>
				<?php if(isset($_SESSION["user"])) echo '<div id="mob-btn"><span> &#9776; </span></div>' ?>
				<div id="hd">
					<h1 <?php if(!isset($_SESSION["user"])) echo 'class="padding-not-margin"' ?>><?php echo $this->title ?></h1>

					<?php if(isset($_SESSION["user"])) echo _("<div id='login'>You are logged in as ".$_SESSION["user"]->displayName ."</div>")." " ?>
				</div>
				<?php $this->displayMenu()?>
				<?php if ($this->hasInfoSection){	?>
				<div id="infoSection">
					<?php echo $this->infoSectionContent?>
				</div>
				<?php }	?>
				<div id="content">
					<?php echo $this->printContent();?>
				</div>
				<?php echo $this->getFooter()?>
				<div id="errors" class="hidden"></div>
				<div id="comment" class="hidden"></div>
				<script src="js/jquery-3.2.1.min.js" type="text/javascript"></script>
				<script src="js/custom.js" type="text/javascript"></script>
			</body>
			</html>
		<?php
 	}
}
?>
