if(window.loadDependencies){
	loadDependencies(
		Array('js/ajaxRequestModule.js',
				'js/ErrorDisplay.js',
				'yui/build/yahoo-dom-event/yahoo-dom-event.js',
				'yui/build/connection/connection.js',
				'yui/build/animation/animation.js',
				'yui/build/autocomplete/autocomplete.js'
			));
}
else
	alert("Some dependencies could not be loaded!");

errorDisplay = null;

function trOfElement(element){
	var thisTr = element;
	while(thisTr.tagName != 'TR' && thisTr.parentNode) {
		thisTr = thisTr.parentNode;
	}
	return thisTr;
}

function setError(element,error,type){
	if(type == null){
		type = ErrorDisplay.WARNING;
	}
	if(errorDisplay===null){
		errorDisplay = new ErrorDisplay('errors','errorDiv');
	}
	errorDisplay.showError(error,null,type);
	return;
}
function confirmSubmit(event){
	var conf = confirm(_('This will start the batch creation of time sheets. Are you sure?'));
	if(!conf){
		event.preventDefault();
		event.stopPropagation();
	}
}
function byDay(element){
	var container = document.getElementById('projectTable');
	var allInputs = container.getElementsByTagName('INPUT');
	for(var i=0; i < allInputs.length; ++i){
		if(allInputs[i].name.indexOf('dayHours') == 0){
			allInputs[i].disabled = !element.checked;
			if(!element.checked){
				allInputs[i].value = 0;
			}
		}
		if(allInputs[i].name.indexOf('hours') == 0){
			allInputs[i].disabled = element.checked;
		}
	}
	updateTotalHours();
}
/*
//duplicates the calling table row and sets all input values to false;
function duplicateTR(element){
	var thisTr = trOfElement(element);
	var newTr = thisTr.cloneNode(true);
	var allInputs = newTr.getElementsByTagName('INPUT');
	for(var i=0; i < allInputs.length; ++i){
		if(allInputs[i].type!= 'button'){
			allInputs[i].value = '';
		}
	}
	allInputs = newTr.getElementsByTagName('SPAN');
	for(var i=0; i < allInputs.length; ++i){
		allInputs[i].textContent = '';
	}
	thisTr.parentNode.insertBefore(newTr,thisTr.nextSibling);
}*/
//removes the current TR
function removeTR(element){
	var thisTr = trOfElement(element);
	var conf = confirm(_('Are you sure?'));
	if(conf){
		thisTr.parentNode.removeChild(thisTr);
	}
}
function addEmployeeTR(){
	var container = document.getElementById('employeeTable');
	var newTr = document.createElement('TR');
	var newTd = document.createElement('TD');
	newTd.innerHTML = '<input type="button" class="bootstrap-btn-style bootstrap-red" name="remove" value="-" onclick="removeTR(this)" />';
	newTr.appendChild(newTd);
	newTd = document.createElement('TD');
	newTd.innerHTML = '<input name="employee[]" value="" type="text" title="" style="text-transform: capitalize"  onfocus="addAutoComplete(this,\'employee\')"/>';
	newTd.innerHTML += '<input name="employeerefid[]" value="" type="hidden" />';
	newTr.appendChild(newTd);
	newTd = document.createElement('TD');
	newTr.appendChild(newTd);
	container.appendChild(newTr);
}
function addProjectTR(){
	var container = document.getElementById('projectTable');
	var enterByDay = document.getElementById('enterByDay');
	//finding out how many rows are already added
	var trs = container.getElementsByTagName('TR');
	var currNo = trs.length;
	var newTr = document.createElement('TR');
	var newTd = document.createElement('TD');
	var newInput;
	newTd.innerHTML = '<input type="button" class="bootstrap-btn-style bootstrap-red" name="remove" value="-" onclick="removeTR(this);updateTotalHours()" />';
	newTr.appendChild(newTd);
	newTd = document.createElement('TD');
	newInput = document.createElement('INPUT');
	newInput.type = "text";
	newInput.name = "invoicecode[]";
	newInput.onfocus = function(){addAutoComplete(this,'invoicecode');};
	newTd.appendChild(newInput);
	newTr.appendChild(newTd);
	newTd = document.createElement('TD');
	newInput = document.createElement('INPUT');
	newInput.type = "text";
	newInput.name = "taskcode[]";
	newInput.onfocus = function(){bulkAddTaskCodeAutoComplete(this);};
	newTd.appendChild(newInput);
	newTr.appendChild(newTd);
	newTd = document.createElement('TD');
	newInput = document.createElement('INPUT');
	newInput.type = "text";
	newInput.value = 0;
	newInput.name = "hours[]";
	newInput.className = "small";
	newInput.setAttribute("autocomplete","off");
	newInput.disabled = enterByDay.checked;
	newInput.onkeypress = function(){onDigitInputsChange(event);};
	newInput.onkeyup = function(){updateTotalHours();};
	newTd.appendChild(newInput);
	newTr.appendChild(newTd);
	for(j=0; j < 7; ++j) {
		newTd = document.createElement('TD');
		newInput = document.createElement('INPUT');
		newInput.type = "text";
		newInput.value = 0;
		newInput.name = "dayHours["+currNo+"][]";
		newInput.className = "small";
		newInput.setAttribute("autocomplete","off");
		newInput.disabled = !enterByDay.checked;
		newInput.onkeypress = function(event){onDigitInputsChange(event);};
		newInput.onkeyup = function(){updateTotalHours();};
		newTd.appendChild(newInput);
		newTr.appendChild(newTd);
	}
	newTd = document.createElement('TD');
	newInput = document.createElement('INPUT');
	newInput.type = "text";
	newInput.name = "ot[]";
	newInput.className = "small";
	newInput.setAttribute("autocomplete","off");
	newInput.value = 0;
	newTd.appendChild(newInput);
	newTr.appendChild(newTd);
	newTd = document.createElement('TD');
	newTr.appendChild(newTd);
	container.appendChild(newTr);
}
function updateTotalHours(){
	var i,j,allInputs;
	var totalDayArr = new Array(0,0,0,0,0,0,0);
	var grandTotal = 0;
	var myReg = /dayHours\[([0-9]+)\]/ig;
	var myArray = null;
	var hoursInp = null;
	var total = 0,val=0,name,totalOT = 0,idx;
	var totalsTR = document.getElementById('totals');
	var projectTable = document.getElementById('projectTable');
	var enterByDay = document.getElementById('enterByDay');
	var bTotalsByHour = enterByDay.checked;
	if(projectTable){
		var allTRs = projectTable.getElementsByTagName('TR');
		for(i=0; i < allTRs.length; ++i){
			allInputs = allTRs[i].getElementsByTagName('INPUT');
			hoursInp = null;
			total = 0;
			idx = 0;
			for(j=0; j < allInputs.length; ++j){
				name = allInputs[j].name;
				if(name == 'ot[]'){
					val = parseFloat(allInputs[j].value);
					if(isNaN(val)){
						continue;
					}
					totalOT += val;
					continue;
				}
				if(name == 'hours[]'){
					hoursInp = allInputs[j];
					idx = j;
					if(bTotalsByHour){
						continue;//keep going over the inputs
					}else{
						break;//found what we were looking for
					}
				}
				if(bTotalsByHour && hoursInp != null){
					val = parseFloat(allInputs[j].value);
					if(isNaN(val)){
						continue;
					}
					myReg.lastIndex = 0;
					if(myReg.test(name)){
						total += val;
						totalDayArr[j-idx-1] += val;
					}
				}
			}
			if(hoursInp && bTotalsByHour){
				hoursInp.value = total.toFixed(2);
				grandTotal += total;
			}else if(hoursInp){
				grandTotal += parseFloat(hoursInp.value);
			}
		}
	}
	//setting the day total
	myReg = /dayTotalHours\[([0-9]*)\]/ig;
	allInputs = totalsTR.getElementsByTagName('INPUT');
	hoursInp = null;
	for(j=0; j < allInputs.length; ++j){
		name = allInputs[j].name;
		if(name == 'totalot'){
			allInputs[j].value = totalOT.toFixed(2);
			continue;
		}
		if(name == 'totalhours'){
			hoursInp = allInputs[j];
			hoursInp.value = grandTotal.toFixed(2);
			if(bTotalsByHour){
				continue;//keep going over the inputs
			}else{
				break;//found what we were looking for
			}
		}
		myReg.lastIndex = 0;
		myArray = myReg.exec(name);
		if(myArray !== null){
			allInputs[j].value = totalDayArr[myArray[1]].toFixed(2);
		}
	}
}
function bulkAddTaskCodeAutoComplete(element){
	var thisTr = trOfElement(element);
	var invoiceValue = '';
	var allInputs = thisTr.getElementsByTagName('INPUT');
	for(var i=0; i < allInputs.length; ++i){
		if(allInputs[i].name == 'invoicecode[]'){
			invoiceValue = allInputs[i].value;
		}
	}
	if(invoiceValue == ''){
		setError(thisTr,_('no invoice code selected'));
		return;
	}
	addAutoComplete(element,'taskcode',invoiceValue);
}
//this function makes sure that the value of the input consists only of digits
function onDigitInputsChange(event)
{
	if(event.ctrlKey){
		return false;
	}
	//event.charCode != 0 means the key has a character result that would show in the input
	if(event.charCode != 0 && (event.which < 48 || event.which > 57) && event.charCode != 46)
	{
		event.preventDefault();
		return false;
	}
	return true;
}
function onEmployeeChange(oSelf, elItem, oData)
{
	var refid = elItem[2][0];
	var fname = elItem[2][1];
	var lname = elItem[2][2];
	oData.value = fname+ ' ' + lname;
	var thisTr = trOfElement(oData);
	var allInputs = thisTr.getElementsByTagName('INPUT');
	for(var i=0; i < allInputs.length; ++i){
		if(allInputs[i].name == 'employeerefid[]'){
			allInputs[i].value = refid;
		}
	}
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
	eContainer.style.minWidth = YAHOO.util.Dom.get(eInput).offsetWidth + 'px';

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
			oAutoComplete.itemSelectEvent.subscribe(onEmployeeChange,eInput,false);
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
