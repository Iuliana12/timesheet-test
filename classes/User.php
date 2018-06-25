<?php
class User{
	public $refid, $fname, $lname,$displayName, $usertype, $variable,$minHours,$authorizesSubordinates,$authorizesInvoiceCodes;
	public $costCentre;
	private $bIsProjectManager;
	private $bIsAdmin;
	function User($row = null) {
		$db = new DB();
		$this->bIsProjectManager = false;
		$this->bIsAdmin = false;
		if ($row && $row['refid'] !== null) {
			$this->refid = intval($row['refid']);
			 // Is the user PM of at least one project? [can see Tools->Report then]
	        $sql = "SELECT COUNT(*) FROM tbl_invoice_code_lookup WHERE projectmanager=".$this->refid;
			$db->query($sql);
	        $pmProjects = intval($db->getElement("count"));
	        $this->bIsProjectManager = ($pmProjects > 0);
		}
		if ($row && $row['fname'] !== null) {
			$this->fname = $row['fname'];
			$this->displayName = ucwords($this->fname);
		}
		if ($row && $row['lname'] !== null) {
			$this->lname = $row['lname'];
			$this->displayName .= ' '.ucwords($this->lname);
		}
		if ($row && $row['user_type'] !== null) {
			$this->usertype = $row['user_type'];
			if($this->usertype == 1){
				$this->bIsAdmin = true;
			}
			
		}
		if ($row && $row['variable'] !== null) {
			$this->variable = formatBoolean($row['variable'],true,false);;
		}
		if ($row && $row['minhours'] !== null) {
			$this->minHours = $row['minhours'];
		}
		if ($row && $row['authorizes_subordinates'] !== null) {
			$this->authorizesSubordinates = formatBoolean($row['authorizes_subordinates'],true,false);
		}
		if ($row && $row['authorizes_invoice_codes'] !== null) {
			$this->authorizesInvoiceCodes = formatBoolean($row['authorizes_invoice_codes'],true,false);
		}
		if ($row && $row['cost_centre'] !== null) {
			$this->costCentre = $row['cost_centre'];
			if ($this->costCentre == '-' && $row["own_cost_centre"] != '') {
				$this->costCentre = $row["own_cost_centre"];
			}
		}
	}
	function canAccessBulkTS(){
		if($this->isAdmin()){
			return true;
		}
		return false;
	}
	function isAdmin(){
		return $this->bIsAdmin;
	}
	function isProjectManager(){
		return $this->bIsProjectManager;
	}
	function canAccessReports(){
		if($this->usertype == 2 || $this->isAdmin() || $this->isProjectManager()){
			return true;
		}
		return false;
	}
}
?>