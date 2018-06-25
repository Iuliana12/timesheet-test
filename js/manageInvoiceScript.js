if(window.loadDependencies)
	loadDependencies(
		Array('js/ajaxRequestModule.js',
				'yui/build/yahoo-dom-event/yahoo-dom-event.js',
				'yui/build/connection/connection.js',
				'yui/build/animation/animation.js',
				'yui/build/autocomplete/autocomplete.js'
			))
else
	alert("Some dependencies could not be loaded!");

var invoiceAutoComp = null;
var editButtonPressed = false;
var previousEditValue = "";
var addButtonPressed = false;
var saveButtonPressed = false;
var ajaxRequestInProgress = false;
var taskSelected = false;


//this function sets the initial values of all input or check boxes
window.onload = function()
{
	var invoiceCode = document.getElementById("invoicecode").value;
	var taskSchema = ["RESULT", "TASKCODE", "TASKNAME"]; 
	var taskXHRDataSource = new YAHOO.widget.DS_XHR("./valueList.php", taskSchema);
	taskXHRDataSource.scriptQueryAppend = "field=taskcode&invoicecode="+encodeURIComponent(invoiceCode)+"&limit=500";
	taskXHRDataSource.connTimeout = 1000;
	taskXHRDataSource.scriptQueryParam = "query"; 
	taskXHRDataSource.maxCacheEntries = 60; 
	taskXHRDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_XML;

	window.taskAutoComp = new YAHOO.widget.AutoComplete("taskcodeEdit","taskCodeEditDojoDiv", taskXHRDataSource); 
	taskAutoComp.animVert = false;
	taskAutoComp.maxResultsDisplayed = 500;
	taskAutoComp.queryDelay = 0;
	taskAutoComp.forceSelection = true;
	taskAutoComp.typeAhead = false;
	taskAutoComp.minQueryLength = 0;
	taskAutoComp.allowBrowserAutocomplete = false;
	taskAutoComp.highlightClassName = "myCustomHighlightClass"; 
	taskAutoComp.prehighlightClassName = "myCustomPrehighlightClass";
	taskAutoComp.itemSelectEvent.subscribe(itemSelected,null,false);
	taskAutoComp.containerExpandEvent.subscribe(function()
		{
			window.taskSelected = false;
			if(window.editButtonPressed)
			{
				window.editButtonPressed = false;
				tasknameEdit = document.getElementById("tasknameEdit");
				tasknameEdit.setAttribute("disabled","disabled");
				tasknameEdit.value = "";
				document.getElementById("editTaskDetails").textContent = "edit";
			}
		},null,false);
	taskAutoComp.formatResult = function(aResultItem, sQuery) { 
		var sKey = aResultItem[0]; // the entire result key 
		var sKeyQuery = sKey.substr(0, sQuery.length); // the query itself 
		var sKeyRemainder = sKey.substr(sQuery.length); // the rest of the result 
		
	// some other piece of data defined by schema 
		var attribute1 = aResultItem[1];  
		// and another piece of data defined by schema
		if(attribute1 != "")
		{
			attribute1 = " ("+attribute1+")";
		}
		var aMarkup = ["<div id='ysearchresult'>", 
			"<span style='font-weight:bold'>", 
			sKeyQuery, 
			"</span>", 
			sKeyRemainder, 
			attribute1, 
			"</div>"]; 
		return (aMarkup.join("")); 
	};
	//positioning the container under the input
	var inTaskDiv = document.getElementById("taskCodeEditDojoDiv");
	var taskcodeEdit =  document.getElementById("taskcodeEdit");
	var obj = taskcodeEdit;
	var x = y = 0;
	if (obj.offsetParent) {
		x = obj.offsetLeft;
		y = obj.offsetTop;
		while (obj = obj.offsetParent) {
			x += obj.offsetLeft;
			y += obj.offsetTop;
		}
	}
	y += taskcodeEdit.offsetHeight;
	inTaskDiv.setAttribute("style","left: "+x+"px;top:"+y+"px");
// 	taskAutoComp._sendQuery("");
}
			
//this function sets the contents of the task Name Input 
function itemSelected( oSelf , elItem , oData )
{
	window.taskSelected = true;
	tasknameEdit = document.getElementById("tasknameEdit");
	tasknameEdit.value = elItem[2][1];
// 	alert(elItem[2][0]+ " - "+ elItem[2][1]);
}
//this is called to add a new task code to the list of this invoice code			
function addTask(event){
	taskcode = document.getElementById("taskcode");
	taskname = document.getElementById("taskname");
	if(event.button != 0 || window.ajaxRequestInProgress)
		return;
	if(!window.addButtonPressed)
	{
		window.addButtonPressed = true;
		taskcode.removeAttribute("disabled");
		taskname.removeAttribute("disabled");
		event.target.textContent = "save";
	}
	else//saving the data of the new task
	{
		var flag = true;
		if(trim(taskcode.value) == "")
		{
			alert(_("Sorry, we can not add empty name task codes."));
			flag = false;
		} 
		flag = (flag && confirm(_("Are you sure you want to add this task code?")));
		window.addButtonPressed = false;
		taskcode.setAttribute("disabled","disabled");
		taskname.setAttribute("disabled","disabled");
		event.target.textContent = _("add new");
		if(!flag)
		{
			taskcode.value = "";
			taskname.value = "";
		    return;
		}
		var invoiceCodeInput = document.getElementById("invoicecode");
		//actual saving
		var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		request += "<REQUEST OBJECT=\"addTask\">\n";
		request += "\t<INVOICECODE>" + escapeHTML(invoiceCodeInput.value) + "</INVOICECODE>\n";
		request += "\t<TASKCODE>" + escapeHTML(trim(taskcode.value)) + "</TASKCODE>\n";
		request += "\t<TASKNAME>" + escapeHTML(trim(taskname.value)) + "</TASKNAME>\n";
		request += "</REQUEST>\n";
		
		taskcode.value = "";
		taskname.value = "";
		
// 		alert(request);
		window.ajaxRequestInProgress = true;
		ajaxSendXMLtoPHP('parseResponse.php',request,statusCallback);
	}
}
function cancelAddTask(event){
	if(event.button != 0 || window.ajaxRequestInProgress || window.addButtonPressed === false )
		return;
	taskcode = document.getElementById("taskcode");
	taskname = document.getElementById("taskname");
	window.addButtonPressed = false;
	taskcode.setAttribute("disabled","disabled");
	taskname.setAttribute("disabled","disabled");
	document.getElementById("add_new_task_button").textContent = _("add new");
}
//this is called when editing a selected task code
function editTask(event){
	taskcodeEdit = document.getElementById("taskcodeEdit");
	if(event.button!=0 || window.ajaxRequestInProgress || !window.taskSelected || trim(taskcodeEdit.value) == "")
		return;
	tasknameEdit = document.getElementById("tasknameEdit");
	if(!window.editButtonPressed)
	{
		window.editButtonPressed = true;
		tasknameEdit.removeAttribute("disabled");
		event.target.textContent = _("save");
		window.previousEditValue = tasknameEdit.value;
	}
	else//saving the data of the edited task
	{
		var flag = confirm(_("Are you sure you want to update this task code?"));
		window.editButtonPressed = false;
		tasknameEdit.setAttribute("disabled","disabled");
		event.target.textContent = _("edit");
		if(!flag){
			tasknameEdit.value = window.previousEditValue;
		    return;
		}
		//actual saving
		var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		request += "<REQUEST OBJECT=\"saveTask\">\n";
		request += "\t<TASKCODE>" + escapeHTML(trim(taskcodeEdit.value)) + "</TASKCODE>\n";
		request += "\t<TASKNAME>" + escapeHTML(trim(tasknameEdit.value)) + "</TASKNAME>\n";
		request += "</REQUEST>\n";
		
		window.ajaxRequestInProgress = true;
		ajaxSendXMLtoPHP('parseResponse.php',request,statusCallback);
	}
}
function cancelEditTask(){
	if(window.ajaxRequestInProgress || window.editButtonPressed === false )
		return;
	window.editButtonPressed = false;
	tasknameEdit = document.getElementById("tasknameEdit");
	tasknameEdit.setAttribute("disabled","disabled");
	document.getElementById("editTaskDetails").textContent = _("edit");
	window.previousEditValue = tasknameEdit.value;
}
//this is called to save the details of the current invoice code
function saveDetails(){
	if( window.ajaxRequestInProgress)
		return;
	if(!window.editButtonPressed)
	{
		window.saveButtonPressed = true;
		var statusDisplay = document.getElementById("statusDisplay");
		statusDisplay.textContent = _("saving details");
		
		var invoiceCodeInput = document.getElementById("invoicecode");
		var projectNameInput = document.getElementById("projectname");
		var departmentSelect = document.getElementById("department");
		var reqAuth = document.getElementById("reqauth");
		var taskNeeded = document.getElementById("taskneeded");
		
		var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		request += "<REQUEST OBJECT=\"saveInvoice\">\n";
		request += "\t<INVOICECODE>" + escapeHTML(invoiceCodeInput.value) + "</INVOICECODE>\n";
		request += "\t<PROJECTNAME>" + escapeHTML(trim(projectNameInput.value)) + "</PROJECTNAME>\n";
		request += "\t<DEPARTMENT>" + escapeHTML(departmentSelect.value) + "</DEPARTMENT>\n";
		request += "\t<TASKNEEDED>" + taskNeeded.checked + "</TASKNEEDED>\n";
		request += "\t<REQAUTH>" + reqAuth.checked + "</REQAUTH>\n";
		request += "</REQUEST>\n";
		
		window.ajaxRequestInProgress = true;
		ajaxSendXMLtoPHP('parseResponse.php',request,statusCallback);
	}
}

//this function is a callback to indicate the status of a previous AJAX action
function statusCallback(XML)
{
	if(XML == null)
		return;
	if(window.saveButtonPressed)
		window.saveButtonPressed = false;
	window.ajaxRequestInProgress = false;
	var statTxts = XML.getElementsByTagName("STATUS");
	if(statTxts!=null && statTxts.length!=0){
		document.getElementById("statusDisplay").textContent = statTxts[0].textContent;
		if(statTxts[0].textContent.indexOf("Error")!=-1)
			document.getElementById("statusDisplay").setAttribute("class","highlightRed");
		else if(statTxts[0].textContent.indexOf("Warning")!=-1)
			document.getElementById("statusDisplay").setAttribute("class","highlightYellow");
		else
			document.getElementById("statusDisplay").setAttribute("class","highlightGreen");
	}
}
