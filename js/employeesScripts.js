if(window.loadDependencies)
	loadDependencies(
		Array('yui/build/yahoo-dom-event/yahoo-dom-event.js',
			  'yui/build/calendar/calendar-min.js'
			))
else
	alert("Some dependencies could not be loaded!");
	
var selectedRefIds = new Array(); //list of selected refids 
var populateLock = false;//this is used as a lock by the populateEmployeeList function
var overrideLock = false;
var valueListXML = null;//this will store the XML with all the results
var positionInList = 0;//this is the position in the list
var GDatePicker = null;//the date picker used for each field that needs a date

window.addEventListener('load',function() {
	search(null);
},true);
//selectEmployee - called when an employee is selected in the list, it calls for the employee's details
function selectEmployee(evt) {
	if(evt.button != 0 || evt.target.hasAttribute("class"))
		return;
	var refid = parseInt(evt.target.getAttribute("refid"));
	var fname = document.getElementById("fname"+refid);
	var lname = document.getElementById("lname"+refid);
	var xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	xmlData += "<REQUEST OBJECT=\"employee\">\n";
	xmlData +="\t\t<REFID>" + refid +"</REFID>\n";
	xmlData += "</REQUEST>";
// 	alert(xmlData);
	ajaxSendXMLtoPHP("parseResponse.php",xmlData,setDisplayedEmployee);
	clearSelected();
	if(fname)
		fname.setAttribute("class","selected");
	if(lname)
		lname.setAttribute("class","selected");
	selectedRefIds.push(refid);
}
//clearSelected - visually deselects all the selected employees in the list and deletes the selectedRefIds list
function clearSelected() {
	if(selectedRefIds != null && selectedRefIds.length > 0)
	{
		for(var i=0; i< selectedRefIds.length; ++i)
		{
			selFname = document.getElementById("fname"+selectedRefIds[i]);
			selLname = document.getElementById("lname"+selectedRefIds[i]);
			if(selFname)
				selFname.removeAttribute("class");
			if(selLname)
				selLname.removeAttribute("class");
		}
		delete selectedRefIds;
		selectedRefIds = new Array();
	}
}
//search - called at each key press in the list and changes the employees displayed according to the new search filter
function search(evt) {
	if(evt && evt.keyIdentifier && evt.keyIdentifier.indexOf("U") == -1)
		return;//this will narrow the keys that trigger this event 
	
	var showunE = "false";
	if(document.getElementById("showunemployed") && document.getElementById("showunemployed").checked)
		showunE = "true";
	var input = document.getElementById("searchBox");
	var cost_centre = document.getElementById("cost_centre");
	var sortBy = document.getElementById("sortby");
	var sortOrder = document.getElementById("sortorder");
	ajaxSendXMLtoPHP('valueList.php?field=users&by='+encodeURIComponent(sortBy.value)+'&dir='+encodeURIComponent(sortOrder.value)+'&showunemployed='+encodeURIComponent(showunE)+'&query='+encodeURIComponent(input.value)+'&cost_centre='+encodeURIComponent(cost_centre.value),"",startPopulateEmployeeList);
    clearSelected();
	detailDisplay(null);
}
// called from the init function and does some needed actions before calling the recursive function populateEmployeeList()
function startPopulateEmployeeList(XML) {
	if(XML == null)
		return;
	valueListXML = XML;
	positionInList = 0;
	//if this function is already running
	if(window.populateLock)
		window.populateLock = false;
	else
		populateEmployeeList();
}
// called from startPopulateEmployeeList(), it puts in the list all the employees given by the server
function populateEmployeeList() {
	if(valueListXML == null || (populateLock && !window.overrideLock))
		return;
	window.populateLock = true;
	var table = document.getElementById("StaffListTable");
	if(positionInList == 0)
	{
		while(table.hasChildNodes())
			table.removeChild(table.firstChild);
	}
		
	var results = valueListXML.getElementsByTagName("RESULT");
	if(results!=null && results.length!=0)
	{
		var counter = 0;
		for(var i=positionInList;i<results.length;++i,++counter)
		{
			//making sure we don't cause starvation
			if(counter >= 10 )
			{
				window.overrideLock = true;
				positionInList = i;
				setTimeout(populateEmployeeList,100);
				return;
			}
			//check if other controls ask for repopulating the list
			if(!window.populateLock)
			{
				//starting all over
				populateEmployeeList();
				return;
			}
			var refid = parseInt(results[i].getElementsByTagName("REFID")[0].textContent);
			var fname = escapeHTML(results[i].getElementsByTagName("FNAME")[0].textContent);
			var lname = escapeHTML(results[i].getElementsByTagName("LNAME")[0].textContent);
			var tr = document.createElement("TR");
			var td1 = document.createElement("td");
			td1.textContent = fname;
			td1.setAttribute("refid",refid);
			td1.id="fname"+refid;
			td1.addEventListener("click",selectEmployee,false);
			var td2 = document.createElement("td");
			td2.textContent = lname;
			td2.setAttribute("refid",refid);
			td2.id="lname"+refid;
			td2.addEventListener("click",selectEmployee,false);
			tr.appendChild(td1);
			tr.appendChild(td2);
			table.appendChild(tr);
		}
	}
	window.overrideLock = false;
	window.populateLock = false;
}
// callback function when the server returns the details of the selected employee. It will create an Employee object according to the XML information received and send it to detailDisplay()
function setDisplayedEmployee(XML) {
	if(XML == null)
		return;
	
	var emp = new Employee();
	emp.fromXML(XML);
	detailDisplay(emp);
}
// this function displays the given employee's information on the page
function detailDisplay(employee) {
	var tableDiv = document.getElementById("StaffDetailsContainer");
	var blankDiv = document.getElementById("StaffDetailsBlank");
	if(employee == null)
	{
		tableDiv.setAttribute("class","hide");
		blankDiv.removeAttribute("class");
		return;
	}
	else
	{
		blankDiv.setAttribute("class","hide");
		tableDiv.removeAttribute("class");
		var hiddenRefid = document.getElementById("hiddenRefid");
		hiddenRefid.value = employee.refid;
		//obtaining the input boxes I need to set values for
		var fName = document.getElementById("fname");
		var lName = document.getElementById("lname");
		var uName = document.getElementById("username");
		var email = document.getElementById("email");
		var lineManager = document.getElementById("lineManager");
		var userType = document.getElementById("userType");
		var minHours = document.getElementById("minHours");
		var toilAdjustment = document.getElementById("toilAdjustment");
		var toilAdjustmentDate = document.getElementById("toilAdjustmentDate");
		var toilAdjustmentComment = document.getElementById("toilAdjustmentComment");
		var holsAdjustment = document.getElementById("holsAdjustment");
		var holsAdjustmentComment = document.getElementById("holsAdjustmentComment");
		var employed = document.getElementById("employed");
		var variable = document.getElementById("variable");
		var authorizes_subordinates = document.getElementById("authorizes_subordinates");
		var authorizes_invoice_codes = document.getElementById("authorizes_invoice_codes");
		var enrolled = document.getElementById("enrolled");
		fName.value = employee.firstName;
		lName.value = employee.lastName;
		uName.value = employee.userName;
		toilAdjustment.value = employee.toilAdjustment;
		toilAdjustmentDate.value = employee.toilAdjustmentDate;
		toilAdjustmentComment.value = employee.toilAdjustmentComment;
		holsAdjustment.value = employee.holsAdjustment;
		holsAdjustmentComment.value = employee.holsAdjustmentComment;
		email.value = employee.email;
		lineManager.value = employee.lineManager;
		userType.value = employee.userType.toString();
		minHours.value = employee.minHours;
		employed.checked = employee.employed;
		variable.checked = employee.variable;
		authorizes_subordinates.checked = employee.authorizes_subordinates;
		authorizes_invoice_codes.checked = employee.authorizes_invoice_codes;
		enrolled.checked = employee.enrolled;
		document.getElementById("statusText").value = "";
	}
}
// called when the save button is clicked, takes the data in the form and puts it into an Employee object then calls the save method for it.
function saveEmployee() {
	if(!document.getElementById("hiddenRefid"))
		return;
	if(!confirm(_("Are you sure you want to update this employee's information?")))
		return;
	
	var inpToilAdjustment = document.getElementById("toilAdjustment"); 	
	var inpHolsAdjustment= document.getElementById("holsAdjustment");
		
	var employee = new Employee();
	employee.refid = parseInt(document.getElementById("hiddenRefid").value);
	employee.userType = parseInt(document.getElementById("userType").value);
	employee.minHours = parseFloat(document.getElementById("minHours").value);
	employee.variable = document.getElementById("variable").checked;
	employee.toilAdjustment = parseFloat(inpToilAdjustment.value);
	employee.toilAdjustmentDate = trim(document.getElementById("toilAdjustmentDate").value);
	employee.toilAdjustmentComment = trim(document.getElementById("toilAdjustmentComment").value);
	employee.holsAdjustment = parseFloat(inpHolsAdjustment.value);
	employee.holsAdjustmentComment = trim(document.getElementById("holsAdjustmentComment").value);
	employee.authorizes_subordinates = document.getElementById("authorizes_subordinates").checked;
	employee.authorizes_invoice_codes = document.getElementById("authorizes_invoice_codes").checked;
	employee.enrolled = document.getElementById("enrolled").checked;
	employee.save();
}
//function called by Employee.save() and updates the status box with the status the server returned
function saveEmployeeStatusCallback(XML) {
	if(XML == null)
		return;
	//NOTE: the list refresh is not needed anymore as the names can no longer be edited from TRS
	var statTxts = XML.getElementsByTagName("STATUS");
	if(statTxts!=null && statTxts.length!=0){
		document.getElementById("statusText").value = statTxts[0].textContent;
	}
}

Employee = function() {
	this.refid = -1;
	this.lastName = "";
	this.firstName = "";
	this.userName = "";
	this.email = "";
	this.lineManager = -1;
	this.userType = 0;
	this.minHours = "";
	this.authorizes_subordinates = false;
	this.authorizes_invoice_codes = false;
	this.employed = true;
	this.enrolled = true;
	this.variable = false;
	this.toilAdjustment = 0;
	this.toilAdjustmentDate = "";
	this.toilAdjustmentComment = "";
	this.holsAdjustment = 0;
	this.holsAdjustmentComment = "";
	
	this.fromXML = function(XML) 	{
		if (XML ==null)
			return;
		var employees = XML.getElementsByTagName("EMPLOYEE");
		if(employees != null && employees.length > 0) {
			employee = employees[0];
			var prop = employee.getElementsByTagName("REFID"); 
			if(prop && prop.length > 0)
				this.refid = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("LNAME"); 
			if(prop && prop.length > 0)
				this.lastName = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("FNAME"); 
			if(prop && prop.length > 0)
				this.firstName = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("USERNAME"); 
			if(prop && prop.length > 0)
				this.userName = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("EMAIL"); 
			if(prop && prop.length > 0)
				this.email = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("LINEMANAGER"); 
			if(prop && prop.length > 0)
				this.lineManager = unescapeHTML(prop[0].textContent);	
			prop = employee.getElementsByTagName("USERTYPE"); 
			if(prop && prop.length > 0)
				this.userType = unescapeHTML(prop[0].textContent);	
			prop = employee.getElementsByTagName("MINHOURS"); 
			if(prop && prop.length > 0)
				this.minHours = unescapeHTML(prop[0].textContent);	
			prop = employee.getElementsByTagName("TOILADJUSTMENT"); 
			if(prop && prop.length > 0)
				this.toilAdjustment = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("TOILDATE"); 
			if(prop && prop.length > 0)
				this.toilAdjustmentDate = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("TOILCOMMENT"); 
			if(prop && prop.length > 0)
				this.toilAdjustmentComment = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("HOLSADJUSTMENT"); 
			if(prop && prop.length > 0)
				this.holsAdjustment = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("HOLSCOMMENT"); 
			if(prop && prop.length > 0)
				this.holsAdjustmentComment = unescapeHTML(prop[0].textContent);
			prop = employee.getElementsByTagName("AUTHORIZES_SUBORDINATES"); 
			if(prop && prop.length > 0)
				this.authorizes_subordinates = unescapeHTML(prop[0].textContent) == "true" ? true : false;
			prop = employee.getElementsByTagName("AUTHORIZES_INVOICE_CODES"); 
			if(prop && prop.length > 0)
				this.authorizes_invoice_codes = unescapeHTML(prop[0].textContent) == "true" ? true : false;
			prop = employee.getElementsByTagName("EMPLOYED"); 
			if(prop && prop.length > 0)
				this.employed = unescapeHTML(prop[0].textContent) == "true" ? true : false;
			prop = employee.getElementsByTagName("VARIABLE"); 
			if(prop && prop.length > 0)
				this.variable = unescapeHTML(prop[0].textContent) == "true" ? true : false;
			prop = employee.getElementsByTagName("ENROLLED");
			if(prop && prop.length > 0)
				this.enrolled = unescapeHTML(prop[0].textContent) == "true" ? true : false;
		}
	}
	
	this.save = function() {
		var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		request += "<REQUEST OBJECT=\"saveEmployee\">\n";
		request += "\t<EMPLOYEE>\n";
		request += "\t\t<REFID>"+ this.refid +"</REFID>\n";
		request += "\t\t<USERTYPE>"+ this.userType +"</USERTYPE>\n";
		request += "\t\t<MINHOURS>"+ this.minHours +"</MINHOURS>\n";
		request += "\t\t<VARIABLE>"+ this.variable +"</VARIABLE>\n";
		request += "\t\t<TOILADJUSTMENT>"+ this.toilAdjustment +"</TOILADJUSTMENT>\n";
		request += "\t\t<TOILDATE>"+ escapeHTML(this.toilAdjustmentDate) +"</TOILDATE>\n";
		request += "\t\t<TOILCOMMENT>"+ escapeHTML(this.toilAdjustmentComment) +"</TOILCOMMENT>\n";
		request += "\t\t<HOLSADJUSTMENT>"+ this.holsAdjustment +"</HOLSADJUSTMENT>\n";
		request += "\t\t<HOLSCOMMENT>"+ escapeHTML(this.holsAdjustmentComment) +"</HOLSCOMMENT>\n";
		request += "\t\t<AUTHORIZES_SUBORDINATES>"+ this.authorizes_subordinates +"</AUTHORIZES_SUBORDINATES>\n";
		request += "\t\t<AUTHORIZES_INVOICE_CODES>"+ this.authorizes_invoice_codes +"</AUTHORIZES_INVOICE_CODES>\n";
		request += "\t\t<ENROLLED>"+ this.enrolled +"</ENROLLED>\n";
		request += "\t</EMPLOYEE>\n";
		request += "</REQUEST>\n";
// 		alert(request);
		ajaxSendXMLtoPHP('parseResponse.php',request,saveEmployeeStatusCallback);
	}
}
