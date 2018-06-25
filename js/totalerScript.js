errorDisplay = null;
isAdmin = true;

window.onload = function(){
	errorDisplay = new ErrorDisplay('errors','errorDiv');
	var employeeInput = document.getElementById("employee");
	if(employeeInput == null){
		isAdmin = false;
	}
	total();
};
function total(){
	var totalInput = document.getElementById("totaltime");
	if(!totalInput){
		return;
	}
	totalInput.value = "-";
	var invoiceCode = document.getElementById("invoicecode");
	var invoiceCheck = document.getElementById("specificinvoice");
	var taskCode = document.getElementById("taskcode");
	var taskCheck = document.getElementById("specifictask");
	var dateStart = document.getElementById("datestart");
	var dateEnd = document.getElementById("dateend");
	var employeeRefid = "";
	var employeeChecked = false;
	var selftimeChecked = false;
	if(isAdmin){
		var employee = document.getElementById("employee");
		var employeeRefid = document.getElementById("employeerefid").value;
		var employeeCheck = document.getElementById("selftime");
		var selftime = document.getElementById("selftime");
		selftimeChecked = selftime.checked;
		//employeeChecked = employeeCheck.checked;
		if(selftime.checked){
			employee.disabled = true;
			//employeeCheck.disabled = true;
			employeeChecked = false;
		} else {
			employee.disabled = false;
			employeeChecked = true;
			//employeeCheck.disabled = false;
		}
	}
	var invoiceCheckchecked = true;
	var taskCheckchecked  = true;
	if(taskCode.value) {
		taskCheckchecked = true;
	} else {
		taskCheckchecked = false;
	}

	var xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	xmlData += "<REQUEST OBJECT=\"timetotal\">\n";
	xmlData +="\t\t<INVOICECODE>" + escapeHTML(invoiceCode.value) +"</INVOICECODE>\n";
	xmlData +="\t\t<INVOICECHECK>" + escapeHTML(invoiceCheckchecked) +"</INVOICECHECK>\n"; //invoiceCheck.checked invoiceCheckchecked
	xmlData +="\t\t<TASKCODE>" + escapeHTML(taskCode.value) +"</TASKCODE>\n";
	xmlData +="\t\t<EMPLOYEE>" + escapeHTML(employeeRefid) +"</EMPLOYEE>\n";
	xmlData +="\t\t<TASKCHECK>" + escapeHTML(taskCheckchecked) +"</TASKCHECK>\n"; //taskCheck.checked taskCheckchecked
	xmlData +="\t\t<EMPLOYEECHECK>" + escapeHTML(employeeChecked) +"</EMPLOYEECHECK>\n";
	xmlData +="\t\t<SELFTIME>" + escapeHTML(selftimeChecked) +"</SELFTIME>\n";
	xmlData +="\t\t<DATESTART>" + escapeHTML(dateStart.value) +"</DATESTART>\n";
	xmlData +="\t\t<DATEEND>" + escapeHTML(dateEnd.value) +"</DATEEND>\n";
	xmlData += "</REQUEST>";
	// alert(xmlData);
	ajaxSendXMLtoPHP("parseResponse.php",xmlData,totalCallBack);
}
function totalCallBack(XML)
{
	if(XML == null)
	{
		errorDisplay.showError("AJAX request returned null",'x001',ErrorDisplay.FATAL);
		return;
	}
	var statusText = XML.getElementsByTagName("STATUSTEXT")[0];
	var statusS = XML.getElementsByTagName("STATUS")[0];
	if(statusS.textContent == "valid"){
		var total = parseFloat(XML.getElementsByTagName("TOTAL")[0].textContent);
		if(isNaN(total))
			total = 0;
		var totalInput = document.getElementById("totaltime");
		totalInput.value = total;
	} else{
		errorDisplay.showError(statusText.textContent,'x005',ErrorDisplay.FATAL);
	}
}
function onInvoiceChange() {
	var invoiceCode = document.getElementById("invoicecode").value;
	if(invoiceCode == "") {
		errorDisplay.showError(_("Please enter an invoice code!"),null,ErrorDisplay.WARNING);
		return;
	}
	var xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	xmlData += "<VALIDATE TYPE=\"invoicecode\">\n";
	xmlData +="\t\t<CODE>" + escapeHTML(invoiceCode) +"</CODE>\n";
	xmlData += "</VALIDATE>";
	ajaxSendXMLtoPHP("validation.php",xmlData,addPriorInvoiceCheck);
}
function addPriorInvoiceCheck(XML) {
	if(XML==null) {
		errorDisplay.showError("AJAX request returned null",'x000',ErrorDisplay.FATAL);
		return;
	}
	var statusText = XML.getElementsByTagName("STATUSTEXT")[0];
	var statusS = XML.getElementsByTagName("STATUS")[0];
	var entryId = XML.getElementsByTagName("ID")[0];
	if(statusS.textContent != "valid" && statusS.textContent != "completed" && statusS.textContent != "closed") {
		errorDisplay.showError(statusText.textContent,"x021a",ErrorDisplay.FATAL);
		return;
	}
	var inInvoice = document.getElementById("invoicecode");
	total(null,inInvoice);
	addAutoComplete("taskcode",inInvoice.value);
}
function onTaskChange() {
	var inTask = document.getElementById("taskcode");
	var taskCheck = document.getElementById("specifictask");
	//if(true)
	total(null,inTask);
}
function onEmployeeChange(oSelf, elItem, oData) {
	var refid = elItem[2][0];
	var fname = elItem[2][1];
	var lname = elItem[2][2];
	var inEmployee = document.getElementById("employee");
	inEmployee.value = fname+ ' ' + lname;
	var inEmployeeRefid = document.getElementById("employeerefid");
	inEmployeeRefid.value = refid;
	//var employeeCheck = document.getElementById("specificemployee");
	var employeeCheck = document.getElementById("sleftime");
	//if(!employeeCheck.checked)
	total(null,inEmployee);
}
function addAutoComplete(eInput, type, othervar){
	var myServer = "./valueList.php";
	var schema = ["RESULT"];
	var scriptQueryAppend = "field="+type;
	var eContainer = eInput.eAutoCompleteResultContainer;
	var XHRDataSource;
	var oAutoComplete = eInput.oAutoComplete;

	if (eContainer == undefined) {
		eContainer = document.createElement("div");
		eContainer.className = "AutoCompleteResultContainer";
		document.getElementsByTagName('body')[0].appendChild(eContainer);
		eInput.eAutoCompleteResultContainer = eContainer;
	}
	//positioning the container under the focused element
	var pos = YAHOO.util.Dom.getXY(eInput);
    pos[1] += YAHOO.util.Dom.get(eInput).offsetHeight;
    YAHOO.util.Dom.setXY(eContainer,pos);
	//eContainer.style.minWidth = YAHOO.util.Dom.get(eInput).offsetWidth + 'px';

	//type specific stuff about XHR goes here
	switch(type)
	{
		case "invoicecode":
			schema = ["RESULT", "INVOICECODE", "PROJECTNAME", "COMPLETED"];
			break;
		case "taskcode":
			schema =  ["RESULT", "TASKCODE", "TASKNAME"];
			scriptQueryAppend += "&invoicecode="+encodeURIComponent(othervar);
			break;
		case "employee":
			schema = ["RESULT","REFID","LNAME","FNAME"];
			break;
	}

	//this prevents adding more AutoComplete objects for the same input box at every focus event
	if(oAutoComplete != undefined) {
		//reinitialize properties that might have changed since the last call
		oAutoComplete.dataSource.scriptQueryAppend = scriptQueryAppend;
		return oAutoComplete;
	}

	//initializing the DataSource object
	XHRDataSource = new YAHOO.widget.DS_XHR(myServer, schema);
	XHRDataSource.connTimeout = 3000;
	XHRDataSource.maxCacheEntries = 60;
	XHRDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_XML;
	XHRDataSource.scriptQueryParam = "query";
	XHRDataSource.scriptQueryAppend = scriptQueryAppend;
	//
	//initializing the AutoComplete object
	oAutoComplete = new YAHOO.widget.AutoComplete(eInput, eContainer, XHRDataSource);
	oAutoComplete.type = type;
	oAutoComplete.alwaysShowContainer = false;
	oAutoComplete.animVert = false;
	oAutoComplete.maxResultsDisplayed = 30;
	oAutoComplete.queryDelay = 0;
	oAutoComplete.forceSelection = false;
	oAutoComplete.typeAhead = false;
	oAutoComplete.minQueryLength = 0;
	oAutoComplete.allowBrowserAutocomplete = false;
	oAutoComplete.highlightClassName = "myCustomHighlightClass";
	oAutoComplete.prehighlightClassName = "myCustomPrehighlightClass";
	oAutoComplete.formatResult = function(aResultItem, sQuery) {
		sQuery = trim(sQuery);
		var sKey = aResultItem[0];
		var sKeyQuery,sKeyReminder,aMarkup,tMarkup,i,j;
		var keyParts = sKey.split(' ');
		var parts = sQuery.split(' ');

		aMarkup = ["<div id='ysearchresult'>"];
		//search each word of the result for a match in the words of the query. Highlight the matching parts.
		for(i=0;i < keyParts.length; ++i)
		{
			var found = false;
			for(j=0;j < parts.length; ++j)
				if(keyParts[i].toLowerCase().indexOf(parts[j].toLowerCase()) == 0)
				{
					sKeyQuery = keyParts[i].substr(0, parts[j].length);
					sKeyRemainder = keyParts[i].substr(parts[j].length);
					aMarkup.push(["<span class='highlightresult'>",
								sKeyQuery,
								"</span>",
								sKeyRemainder].join(''));
					aMarkup.push(" ");
					found = true;
				}
			if(!found)
			{
				aMarkup.push(keyParts[i]);
				aMarkup.push(" ");
			}
		}
		aMarkup.push("</div>");
		return (aMarkup.join(''));
	};
	eInput.oAutoComplete = oAutoComplete;

	//type specific stuff about AutoComplete goes here
	switch(type)
	{
		case "invoicecode":
			oAutoComplete.itemSelectEvent.subscribe(onInvoiceChange,null,false);
			oAutoComplete.formatResult = function(aResultItem, sQuery) {
				var sKey = aResultItem[0]; // the entire result key
				var sKeyQuery = sKey.substr(0, sQuery.length); // the query itself
				var sKeyRemainder = sKey.substr(sQuery.length); // the rest of the result

				// some other piece of data defined by schema
				var attribute1 = aResultItem[1];
				// and another piece of data defined by schema
				var attribute2 = "";
				if(aResultItem[2] == "true")
					attribute2 = " - <span style=\"color:red\">completed</span>";

				var aMarkup = ["<div id='ysearchresult'>",
					"<span style='font-weight:bold'>",
					sKeyQuery,
					"</span>",
					sKeyRemainder,
					" - ",
					attribute1,
					attribute2,
					"</div>"];
				return (aMarkup.join(""));
			};
			break;
		case "taskcode":
			oAutoComplete.itemSelectEvent.subscribe(onTaskChange,null,false);
			oAutoComplete.formatResult = function(aResultItem, sQuery) {
				var sKey = aResultItem[0]; // the entire result key
				var sKeyQuery = sKey.substr(0, sQuery.length); // the query itself
				var sKeyRemainder = sKey.substr(sQuery.length); // the rest of the result

				// some other piece of data defined by schema
				var attribute1 = aResultItem[1];
				if(attribute1 != ""){
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
			break;
		case "employee":
			oAutoComplete.itemSelectEvent.subscribe(onEmployeeChange,null,false);
			oAutoComplete.formatResult = function(aResultItem, sQuery) {
				var lname = aResultItem[2];
				var fname = aResultItem[1];
				var sKeyQuery,sKeyReminder,aMarkup;
				//if the qeuery was found in the first name
				if(fname.toLowerCase().indexOf(sQuery.toLowerCase()) != -1)
				{
					sKeyQuery = fname.substr(0, sQuery.length);
					sKeyRemainder = fname.substr(sQuery.length);
					aMarkup = ["<div id='ysearchresult'>",
						"<span style='font-weight:bold'>",
						sKeyQuery,
						"</span>",
						sKeyRemainder,
						" - ",
						lname,
						"</div>"];
				}//if not, it must be in the last name
				else if(lname.toLowerCase().indexOf(sQuery.toLowerCase()) != -1)
				{
					sKeyQuery = lname.substr(0, sQuery.length);
					sKeyRemainder = lname.substr(sQuery.length);
					aMarkup = ["<div id='ysearchresult'>",
						fname,
						" - ",
						"<span style='font-weight:bold'>",
						sKeyQuery,
						"</span>",
						sKeyRemainder,
						"</div>"];
				}
				else
				{
					aMarkup = ["<div id='ysearchresult'>",
						fname,
						" - ",
						lname,
						"</div>"];
				}
				return (aMarkup.join(""));
			};
			break;
	}
	return oAutoComplete;
}
