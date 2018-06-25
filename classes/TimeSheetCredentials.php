<?php
/** 
 * @author Lucian
 * 
 * 
 */
class TimeSheetCredentials {
	public $refid;
	public $owner;
	public $submitted;
	public $enddate;
	public $ownerLineManager;
	public $reasonForCanEditFalse="";
	private $isProjectManager;
	private $canEdit;
	
	/**
	 * In case of a project manager, this list holds the invoice codes the logged in person has access to.
	 * @var String
	 */
	private $invoiceCodeList;
	
	function __construct($timesheetRefid) {
		$db= new DB();
		//getting all the data about the owner of this timesheet
		$sql = "SELECT a.refid,a.linemanager,b.submitted,b.enddate FROM tbl_staff_lookup AS a, tbl_office_time_sheet AS b 
				WHERE a.refid = b.staffrefid AND b.refid = ".$timesheetRefid;
		$db->query($sql,Array('integer', 'integer','text','text'));
		if($db->numRows() == 0){
			$this->refid = null;
			return null;
		}
		$this->refid = $timesheetRefid;
		$this->submitted = formatBoolean($db->getElement("submitted"),true,false);
		$this->enddate = $db->getElement("enddate");
		$this->owner = $db->getElement("refid");
		$this->ownerLineManager = $db->getElement("linemanager");
		$this->invoiceCodeList = "";
		$this->isProjectManager = false;
		$this->canEdit = false;
		if(!isset($_SESSION["user"])){
			return null;
		}
		if(!$this->isOwner() && !$this->isLineManager() && !$_SESSION["user"]->isAdmin()){
			//let's check if the user has access only to some entries of this time sheet
			$sql = "SELECT invoicecode FROM tbl_invoice_code_lookup WHERE invoicecode 
					IN (SELECT invoicecoderefid FROM tbl_office_time_sheet_entry WHERE officetimesheetrefid = ".$this->refid.") 
					AND projectmanager = ".$_SESSION["user"]->refid;
			$db->query($sql);
			$this->isProjectManager = ($db->numRows() != 0);
			for($i=0; $i < $db->numRows();++$i) {
				$this->invoiceCodeList .= $db->quote($db->getElement("invoicecode")).",";
                $db->nextRow();
			}
			//removing the last comma
			$this->invoiceCodeList = substr($this->invoiceCodeList,0,strlen($this->invoiceCodeList)-1);
			$this->reasonForCanEditFalse = _("access denied");
		}
		if($this->isOwner() || $_SESSION["user"]->isAdmin()){
			$this->canEdit = true;
		}
		if($this->submitted && $this->canEdit){
			$this->canEdit = false;
			$this->reasonForCanEditFalse = _("this time sheet has been submitted");
			//checking if it's the last submitted time sheet for this owner, otherwise it can not be edited!
			if($_SESSION["user"]->isAdmin()){
				$sql = "SELECT * FROM tbl_office_time_sheet WHERE submitted = true 
						AND enddate > ".$db->quote($this->enddate)."
						AND staffrefid = ".$this->owner;
				$db->query($sql);
				if($db->numRows() == 0){
					$this->canEdit = true;
				}else{
					$this->reasonForCanEditFalse = _("newer submitted time sheet(s) prevent you from editing this time sheet");
				}
			}
		}
	}
	function isNull(){
		return ($this->refid === null);
	}
	/**
	 * If true then the logged in user is the owner
	 */
	function isOwner(){
		return ($this->owner == $_SESSION["user"]->refid);
	}
	/**
	 * If true then the logged in user is the line manager of the owner of this time sheet
	 */
	function isLineManager(){
		return ($this->ownerLineManager == $_SESSION["user"]->refid);
	}
	function isProjectManager(){
		return $this->isProjectManager;
	}
	function getInvoiceCodeList(){
		return $this->invoiceCodeList;
	}
	function canView(){
		return ($_SESSION["user"]->isAdmin() || $this->isOwner() || $this->isLineManager() || $this->isProjectManager());
	}
	function canEdit(){
		return $this->canEdit;
	}
	function canDelete(){
		return $this->canEdit();
	}
}

?>