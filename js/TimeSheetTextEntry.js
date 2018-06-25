function TimeSheetTextEntry(){

	this.totalHours = 0;
	this.normalHours = 0;
	this.chargedOTHours = 0;
	this.lieuOTHours = 0;
	this.identityEntry = null;
	this.identityDisplay = null;
	this.invoiceCodeAutoComplete = null;
	this.taskCodeAutoComplete = null;
	this.ntTdId = "ntTd"; //normal time hours
	this.mahTdId = "mahTd"; //minimum accountable hours
	this.totalTdId = "totalTd";
	this.chargedTdId = "chargedTd"; //charged Overtime
	this.liueTdId = "lieuTd";//lieu Overtime
	this.stopEvents = false;//made false to stop events to be handled
	this.taskName = ""; //this will store the task name when a new one is entered in the database
	this.keyTable = null;
	this.clientXIntermediate = null;
	this.cycleTimeInterval = 30 *1000; //every 30 seconds
	this.cycleSaveCount = 0;
	this.cyclesBeforeRedirect = 120; //1 hour of inactivity


	this.initialize = function(){

		this.identityEntry = TimeSheet.activeInstance.textEntryId;
		this.identityDisplay = TimeSheet.activeInstance.keyEntryId;
		this.prepareTextShellEntry();
		this.cycleSaveCount = 0;
	};

	this.prepareTextShellEntry = function(){

		var input=document.getElementById("enterNormal");
		input.addEventListener("mousedown",function(evt){ TimeSheet.activeInstance.textEntry.changeSelectedRateType(evt,Rates.RATE_NORMAL);},false);
		input=document.getElementById("enterLieu");
		input.addEventListener("mousedown",function(evt){ TimeSheet.activeInstance.textEntry.changeSelectedRateType(evt,Rates.RATE_OT_LIEU);},false);
		input=document.getElementById("enterCharged");
		input.addEventListener("mousedown",function(evt){ TimeSheet.activeInstance.textEntry.changeSelectedRateType(evt,Rates.RATE_OT_CHARGED);},false);

		input=document.getElementById("entryButton");
		input.addEventListener("mousedown",TimeSheet.activeInstance.textEntry.collectReferenceCodes,false);
		input=document.getElementById("undoButton");
		input.addEventListener("mousedown",TimeSheet.activeInstance.undoLastAction,false);
		input=document.getElementById("saveButton");
		input.addEventListener("mousedown",this.saveEntry,false);
		input=document.getElementById("proceedButton");
		input.addEventListener("mousedown",this.finishEntry,false);

		this.keyTable = document.getElementById("keyEntryTable");
		var wrapperTable = document.getElementById(TimeSheet.activeInstance.identity);
		if(wrapperTable){
			wrapperTable.addEventListener("mouseup",function(evt){TimeSheet.activeInstance.textEntry.keyTableColumnHeaderMouseUp(evt);},false);
			wrapperTable.addEventListener("mousemove",function(evt){TimeSheet.activeInstance.textEntry.keyTableColumnHeaderMouseMove(evt);},false);
		}
		if(this.keyTable){
			var ths = this.keyTable.getElementsByTagName("th");
			for(var i=0;i<ths.length;++i){
				ths[i].addEventListener("mousedown",function(evt){TimeSheet.activeInstance.textEntry.keyTableColumnHeaderMouseDown(evt);},false);
			}
		}
	};

	this.keyTableColumnHeaderMouseDown = function(evt){
		TimeSheet.activeInstance.textEntry.column = evt.target;
		TimeSheet.activeInstance.textEntry.clientXIntermediate = evt.clientX;
		if(!TimeSheet.activeInstance.textEntry.column.style.width){
			TimeSheet.activeInstance.textEntry.column.style.width = TimeSheet.activeInstance.textEntry.column.clientWidth+'px';
		}
		evt.preventDefault();
		evt.stopPropagation();

	};
	this.keyTableColumnHeaderMouseUp = function(evt){
		if(TimeSheet.activeInstance.textEntry.clientXIntermediate && evt){
			TimeSheet.activeInstance.textEntry.clientXIntermediate = null;
			evt.preventDefault();
   			evt.stopPropagation();
		}
	};
	this.keyTableColumnHeaderMouseMove = function(evt){
		if(TimeSheet.activeInstance.textEntry.clientXIntermediate && evt){
			var diff = evt.clientX - TimeSheet.activeInstance.textEntry.clientXIntermediate;
			diff *= 1.2;
			diff = parseInt(diff,10);

			TimeSheet.activeInstance.textEntry.clientXIntermediate = evt.clientX;
			//update widths in the current column
			var newWidth = parseInt(TimeSheet.activeInstance.textEntry.column.style.width,10)+ diff;
			var newGlobalWidth = parseInt(TimeSheet.activeInstance.textEntry.keyTable.style.width,10) + diff;
			if(newGlobalWidth >= 500 && Math.abs(diff) > 0){
				TimeSheet.activeInstance.textEntry.column.style.width = newWidth+'px';
				TimeSheet.activeInstance.textEntry.keyTable.style.width = newGlobalWidth+'px';
			}
   			evt.preventDefault();
   			evt.stopPropagation();
		}
	};

//this function changes the selected rate type for the global object
	this.changeSelectedRateType = function(evt,rateType)//event listener
	{
		if(evt.button != 0)//only for left button events
			return;
		TimeSheet.activeInstance.selectedRateType = rateType;
		var nButton = document.getElementById("enterNormal");
		var lButton = document.getElementById("enterLieu");
		var cButton = document.getElementById("enterCharged");
		switch(rateType)
		{
			case Rates.RATE_NORMAL:
				nButton.disabled = "disabled";
				lButton.disabled = null;
				cButton.disabled = null;
				break;
			case Rates.RATE_OT_LIEU:
				nButton.disabled = null;
				lButton.disabled = "disabled";
				cButton.disabled = null;
				break;
			case Rates.RATE_OT_CHARGED:
				nButton.disabled = null;
				lButton.disabled = null;
				cButton.disabled = "disabled";
				break;
		}
	};

//this function renders the invoice and task codes form for data input
	this.collectReferenceCodes = function(evt){//event listener
		if(evt.button != 0)//only for left button events
			return

		TimeSheet.activeInstance.textEntry.stopEvents = true;
		document.getElementById("entryButton").disabled = "disabled";
		document.getElementById("saveButton").disabled = "disabled";
		document.getElementById("proceedButton").disabled = "disabled";
		TimeSheet.activeInstance.tableEntry.clearClicked();
		TimeSheet.activeInstance.tableEntry.removeEvents();
		//clearing the div
		var parentNode = document.getElementById(TimeSheet.activeInstance.formEntryId);
		while(parentNode.hasChildNodes())
		{
			parentNode.removeChild(parentNode.firstChild);
		}

		var div1=document.createElement("div");
		var div2=document.createElement("div");
		var div3=document.createElement("div");
		var div4=document.createElement("div");
		var div5=document.createElement("div");
		div1.setAttribute("class","formBlock");
		div2.setAttribute("class","formBlock");
		div3.setAttribute("class","formBlock");
		div4.setAttribute("class","formBlock");
		div5.setAttribute("class","notes");
		var p1=document.createElement("p");
		p1.setAttribute("class","formText");
		p1.textContent=_("Invoice code");
		var p2=document.createElement("p");
 		p2.setAttribute("class","formText");
 		p2.textContent=_("Task code");
		// notes
		var n1 = document.createElement("img");
		n1.setAttribute("class","notesImg");
		n1.setAttribute("id", "modalOpen");
		n1.src = "images/notes.png";
		n1.addEventListener("click", openModal);
		function openModal(){
			document.getElementById("modal").style.display = "block";
		}
		// modal
		var modal = document.createElement("div");
		modal.setAttribute("id", "modal");
		modal.setAttribute("class", "modal");
		var modalContent =  document.createElement("div");
		modalContent.setAttribute("class", "modal_content");
		var modalContainer = document.createElement("div");
		modalContainer.setAttribute("class", "modal_container");
		var modalHeader = document.createElement("header");
		var span = document.createElement("span");
		span.setAttribute("class", "closeModal");
		span.textContent = _("X");
		span.addEventListener("click", closeModal);
		function closeModal(){
			document.getElementById("modal").style.display = "none";
		}
		var modalTitle = document.createElement("h3");
		modalTitle.setAttribute("class", "formText");
		modalTitle.textContent = _("Notes");
		var modalP = document.createElement("p");
		modalP.setAttribute("class", "formText");
		modalP.textContent = _("Please insert the notes");
		var modalInput = document.createElement("input");
		modalInput.setAttribute("id", "notesInput");
		modalInput.setAttribute("name","notes");
		modalInput.type = "text";
		modalInput.tabIndex = 3;
		var modalFooter = document.createElement("footer");
		var modalSave = document.createElement("button");
		modalSave.setAttribute("class", "modalSave bootstrap-btn-style bootstrap-green");
		modalSave.textContent = _("Add Note");
		modalSave.addEventListener("click", closeModal);
		function closeModal(){
			document.getElementById("modal").style.display = "none";
		}
		modalHeader.appendChild(span);
		modalHeader.appendChild(modalTitle);
		modalFooter.appendChild(modalSave);
		modalContainer.appendChild(modalP);
		modalContainer.appendChild(modalInput);
		modalContent.appendChild(modalHeader);
		modalContent.appendChild(modalContainer);
		modalContent.appendChild(modalFooter);
		modal.appendChild(modalContent);
		n1.appendChild(modal);

		var p3=document.createElement("p");
		p3.setAttribute("class","formText");
		p3.textContent=_("Submit");
		var p4=document.createElement("p");
		p4.setAttribute("class","formText");
		p4.textContent=_("Cancel");
		var input1=document.createElement("input");
		input1.id= "invoiceCodeInput";
		input1.type="text";
		input1.tabIndex = 1;
		input1.title = _("type here the invoice code");
		input1.setAttribute("name","invoicecode");
		input1.addEventListener("focus",TimeSheet.activeInstance.textEntry.onInvoiceFocus,false);
// 		input1.addEventListener("change",TimeSheet.activeInstance.textEntry.onInvoiceChange,false);
		var input2=document.createElement("input");
		input2.id= "taskCodeInput";
		input2.type="text";
		input2.tabIndex = 2;
		input2.title = _("type here the task code");
		input2.setAttribute("name","taskcode");
		input2.addEventListener("focus",TimeSheet.activeInstance.textEntry.onTaskFocus,false);
		var input3=document.createElement("input");
		input3.id= "normalEntrySubmit";
		input3.type="button";
		input3.tabIndex = 4;
    input3.className = 'bootstrap-btn-style bootstrap-green';
		input3.setAttribute("value",_("Submit details"));
    input3.addEventListener("click",TimeSheet.activeInstance.textEntry.submitDetails,false);

		var input5=document.createElement("input");
		input5.id= "normalEntryCancel";
		input5.type="button";
		input5.tabIndex = 5;
    input5.className = 'bootstrap-btn-style bootstrap-red';
		input5.setAttribute("value",_("Cancel"));
    input5.addEventListener("click",TimeSheet.activeInstance.textEntry.cancelSubmitDetails,false);
		var input4=document.createElement("input");
		input4.id= "projectNameInput";
		input4.type="hidden";
		input4.setAttribute("name","projectname");

		div1.appendChild(p1);
		div1.appendChild(input1);
		div2.appendChild(p2);
		div2.appendChild(input2);
		div5.appendChild(n1);
		div5.appendChild(modal);
		div3.appendChild(p3);
		div3.appendChild(input3);
		div3.appendChild(input4);
		div4.appendChild(p4);
		div4.appendChild(input5);

		parentNode.appendChild(div1);
 		parentNode.appendChild(div2);
		parentNode.appendChild(div5);
		parentNode.appendChild(div3);
		parentNode.appendChild(div4);

		input1.focus();
	};


//this is called when clicked on cancel while entering details for a project
	this.cancelSubmitDetails = function(evt){//event listener
		if(evt.button != 0)
			return;
		var parentNode = document.getElementById(TimeSheet.activeInstance.formEntryId);
		while(parentNode.hasChildNodes())
		{
			parentNode.removeChild(parentNode.firstChild);
		}
		TimeSheet.activeInstance.tableEntry.addEvents();
		TimeSheet.activeInstance.textEntry.stopEvents = false;
		document.getElementById("entryButton").removeAttribute("disabled");
		if(TimeSheet.activeInstance.textEntry.totalHours >= TimeSheet.activeInstance.minHours){
			document.getElementById("proceedButton").removeAttribute("disabled");
		}
	};

//this is called when clicked on submit details while entering details for a project
	this.submitDetails = function(evt){//event listener
		if(evt.button != 0)
			return;

        this.disabled = true;
		var invoiceCode = document.getElementById('invoiceCodeInput');
		invoiceCode.disabled = true;
		if(invoiceCode.value == "")
		{
			TimeSheet.activeInstance.errorDisplay.showError(_("Please enter an invoice code"),null,ErrorDisplay.WARNING);
			document.getElementById('normalEntrySubmit').disabled = false;
			return;
		}
		var xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		xmlData += "<VALIDATE TYPE=\"invoicecode\">\n";
		xmlData +="\t\t<CODE>" + escapeHTML(invoiceCode.value) +"</CODE>\n";
		xmlData += "</VALIDATE>";
		ajaxSendXMLtoPHP("validation.php",xmlData,TimeSheet.activeInstance.textEntry.addInvoiceCheck);
	};

//this is called when all the validation tests have passed and we're adding a new key to the list
	this.addKey = function() {
		var invoiceCode = document.getElementById('invoiceCodeInput').value;
		var taskCode = document.getElementById('taskCodeInput').value;
		var taskName = TimeSheet.activeInstance.textEntry.taskName;
		var note = document.getElementById('notesInput').value;
		TimeSheet.activeInstance.textEntry.taskName = "";
		var projectName = document.getElementById('projectNameInput').value;
		var parentNode = document.getElementById(TimeSheet.activeInstance.formEntryId);
		while(parentNode.hasChildNodes())
		{
			parentNode.removeChild(parentNode.firstChild);
		}
		var currentEntryId = TimeSheet.activeInstance.entryLastId++;
		var key = new Key(currentEntryId,invoiceCode,projectName,taskCode,taskName,TimeSheet.activeInstance.colourObject.currentColour(), note);
		//ActiveArray will hold objects of type Entry
		key.entries = TimeSheet.activeInstance.tableEntry.activeArray;
		TimeSheet.activeInstance.tableEntry.activeArray = new Array();
		//setting the right attributes to table cells
		for(var i=0;i< key.entries.length; ++i)
		{
			var cell = document.getElementById(key.entries[i].id);
			cell.setAttribute("title",escapeHTML(invoiceCode+" - "+taskCode));
			cell.setAttribute("alt",escapeHTML(invoiceCode+" - "+taskCode));
			cell.setAttribute("entryId",key.entryId);
		}
		TimeSheet.activeInstance.tableEntry.keyArray.push(key);
		TimeSheet.activeInstance.textEntry.updateTotals();
		TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(0);//resets the number of selected hours
		TimeSheet.activeInstance.textEntry.addToKeys(key);
	  TimeSheet.activeInstance.colourObject.nextColour();
	  TimeSheet.activeInstance.textEntry.stopEvents = false;
		TimeSheet.activeInstance.tableEntry.addEvents();
		TimeSheet.activeInstance.textEntry.cyclicSave(); //save data after validations
	};

	this.approve = function approve(){//event listener
		if(!confirm("Are you sure you want to approve this time sheet?"))
			return;
		var entryId = parseInt(this.getAttribute("entryId"));
		var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		request += "<REQUEST OBJECT=\"approval\">\n";
		request += "\t<REFID>" + TimeSheet.activeInstance.refid+ "</REFID>\n";
		request += "\t<INVOICECODE>" + escapeHTML(document.getElementById("invoiceCode"+entryId).textContent) + "</INVOICECODE>\n";
		request += "\t<TASKCODE>" + escapeHTML(document.getElementById("taskCode"+entryId).textContent) + "</TASKCODE>\n";
		request += "\t<STATUS>approved</STATUS>\n";
		request += "</REQUEST>\n";
		ajaxSendXMLtoPHP("timesheetApproval.php",request,TimeSheet.activeInstance.textEntry.approvalCallback);
	};
	this.disapprove = function disapprove(){//event listener
		var reason = prompt("Note that disapproving with one entry will set the whole time sheet to unauthorized.\nPlease specify a reason for disapproval which will be sent to the owner of this time sheet.\n","There's something fishy with this time sheet");
		if(reason == null)
			return;
		var entryId = parseInt(this.getAttribute("entryId"));
		var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		request += "<REQUEST OBJECT=\"approval\">\n";
		request += "\t<REFID>" + TimeSheet.activeInstance.refid+ "</REFID>\n";
		request += "\t<INVOICECODE>" + escapeHTML(document.getElementById("invoiceCode"+entryId).textContent) + "</INVOICECODE>\n";
		request += "\t<TASKCODE>" + escapeHTML(document.getElementById("taskCode"+entryId).textContent) + "</TASKCODE>\n";
		request += "\t<STATUS>disapproved</STATUS>\n";
		request += "\t<REASON>"+ reason +"</REASON>\n";
		request += "</REQUEST>\n";
		ajaxSendXMLtoPHP("timesheetApproval.php",request,TimeSheet.activeInstance.textEntry.approvalCallback);
	};
	this.getFirstMatchingEntryId = function(invoiceCode,taskCode)
	{
		var entryId = 0;
		var invoice = document.getElementById("invoiceCode"+entryId);
		var task = document.getElementById("taskCode"+entryId);
		while(invoice != null && task !=null)
		{
			if(invoice.textContent == invoiceCode && task.textContent == taskCode)
				return entryId;
			++entryId;
			invoice = document.getElementById("invoiceCode"+entryId);
			task = document.getElementById("taskCode"+entryId);
		}
		return -1;
	};
	this.approvalCallback = function(XML){//callback
		if(XML == null)
			return;
		var statuses = XML.getElementsByTagName("STATUS");
		if(statuses!=null && statuses.length!=0){
			if(statuses[0].textContent == "disapproved")
			{
				TimeSheet.activeInstance.errorDisplay.showError(_("This time sheet will be edited by the owner and will need your attention again later"),null,ErrorDisplay.NOTICE);
				window.location = "timesheetList.php";
				return;
			}
			if(statuses[0].textContent == "approved" || statuses[0].textContent == "disapproved")
			{
				var invoiceCode = XML.getElementsByTagName("INVOICECODE")[0].textContent;
				var taskCode = XML.getElementsByTagName("TASKCODE")[0].textContent;
				var entryId = TimeSheet.activeInstance.textEntry.getFirstMatchingEntryId(invoiceCode,taskCode);
				var td = document.getElementById("authorizeButtons"+entryId);
				if(td!=null)
				{
					while(td.hasChildNodes())
						td.removeChild(td.firstChild);
					td.textContent = statuses[0].textContent;
				}
				//this represents the number of entries that await approval
				var number = parseInt(document.getElementById("thAuthorize").getAttribute("awaitingApproval"));
				if(number > 1)
					document.getElementById("thAuthorize").setAttribute("awaitingApproval",(number-1));
				else//this is the last one, so we'll redirect the browser to the menu page
					window.location = "timesheetList.php";
				return;
			}
			TimeSheet.activeInstance.errorDisplay.showError(_("An error has been reported: ")+statuses[0].textContent,"x032",ErrorDisplay.FATAL);
		}
	};

//this function adds the data of the last struct added, to the visual table
	this.addToKeys = function(key){
		var table = document.getElementById(TimeSheet.activeInstance.keyTableId);
		var rowNode = document.createElement("tr");
		var tdColour= document.createElement("td");
		tdColour.id="colourCode"+key.entryId;
		tdColour.title = _("the colour associated in the table with this entry");
		tdColour.setAttribute("class","colourTd");
		tdColour.setAttribute("entryId",key.entryId);
		tdColour.style.backgroundColor = key.colour;
		tdColour.addEventListener("mouseover",TimeSheet.activeInstance.textEntry.onColourOver,false);
		tdColour.addEventListener("mouseout",TimeSheet.activeInstance.textEntry.onColourOut,false);
		var tdHours= document.createElement("td");
		tdHours.id="hours"+key.entryId;
		tdHours.title = _("the number of hours worked for this project");
		tdHours.textContent = TimeSheet.activeInstance.displayTime(key.entries.length);
		tdHours.setAttribute("entryId",key.entryId);
		tdHours.addEventListener("mouseover",TimeSheet.activeInstance.textEntry.onColourOver,false);
		tdHours.addEventListener("mouseout",TimeSheet.activeInstance.textEntry.onColourOut,false);
		var tdInvoice= document.createElement("td");
		tdInvoice.textContent = key.invoiceCode;
		tdInvoice.id="invoiceCode"+key.entryId;
		tdInvoice.title = _("invoice code of this entry");
		tdInvoice.setAttribute("entryId",key.entryId);
		tdInvoice.addEventListener("mouseover",TimeSheet.activeInstance.textEntry.onColourOver,false);
		tdInvoice.addEventListener("mouseout",TimeSheet.activeInstance.textEntry.onColourOut,false);
		var tdProject= document.createElement("td");
		projectName = key.projectName;
		tdProject.appendChild(document.createTextNode(projectName));
		tdProject.id="projectName"+key.entryId;
		tdProject.title = _("project name for this entry");
		tdProject.setAttribute("entryId",key.entryId);
		tdProject.addEventListener("mouseover",TimeSheet.activeInstance.textEntry.onColourOver,false);
		tdProject.addEventListener("mouseout",TimeSheet.activeInstance.textEntry.onColourOut,false);
		var tdTask= document.createElement("td");
		tdTask.textContent = key.taskCode;
		tdTask.id="taskCode"+key.entryId;
		tdTask.title = _("task code of this entry");
		tdTask.setAttribute("entryId",key.entryId);
		tdTask.addEventListener("mouseover",TimeSheet.activeInstance.textEntry.onColourOver,false);
		tdTask.addEventListener("mouseout",TimeSheet.activeInstance.textEntry.onColourOut,false);

		var tdDiv = document.createElement("div");
		tdDiv.setAttribute("class","tdContainer");
		var tdNote = document.createElement("td");
		var tdImage =document.createElement("img");
		tdImage.id="image"+key.entryId;
		tdImage.setAttribute("class", "tdImage");
		if (key.note != "") {
			tdImage.src = "images/icon-blue.png";
			tdImage.setAttribute("title",key.note);
		}
		else {
			tdImage.src = "images/notes.png";
			tdImage.setAttribute("title","no note for this entry");
		}
		tdNote.setAttribute("class", "tdNote");
		tdNote.textContent = key.note;
		tdNote.setAttribute("entryId",key.entryId);
		tdNote.id="note"+key.entryId;
		tdNote.setAttribute("title",key.note);
		tdNote.setAttribute("entryId",key.entryId);
		tdDiv.addEventListener("mouseover",TimeSheet.activeInstance.textEntry.onColourOver,false);
		tdDiv.addEventListener("mouseout",TimeSheet.activeInstance.textEntry.onColourOut,false);
		tdDiv.appendChild(tdNote);
		tdDiv.appendChild(tdImage);
		console.log(tdDiv);
		var tdButtons= document.createElement("td");
		if(!TimeSheet.activeInstance.viewing)
		{
			var input1 = document.createElement("input");
			input1.id="editButton"+key.entryId;
			input1.type="button";
			input1.value=_("modify");
			input1.title = _("Press this button to modify this entry");
			input1.setAttribute("entryId",key.entryId);
			input1.setAttribute("class", "bootstrap-btn-style bootstrap-blue");
			input1.addEventListener("click",this.onKeyEdit,false);
			var input2 = document.createElement("input");
			input2.id="removeButton"+key.entryId;
			input2.type="button";
			input2.value=_("remove");
			input2.title = _("Press this button to remove this entry");
			input2.setAttribute("entryId",key.entryId);
			input2.setAttribute("class", "bootstrap-btn-style bootstrap-red");
			input2.addEventListener("click",this.onKeyRemove,false);
			tdButtons.appendChild(input1);
			tdButtons.appendChild(input2);
        }
		else if(TimeSheet.activeInstance.viewingMode != "userview" && TimeSheet.activeInstance.viewingMode != "adminview")
		{
			var a1 = document.createElement("a");
			a1.id="approveButton"+key.entryId;
			a1.title = _("Press to approve this entry");
			a1.setAttribute("entryId",key.entryId);
			a1.setAttribute("class","keyLink");
			a1.addEventListener("click",this.approve,false);
			a1.textContent = _("yes");
			var a2 = document.createElement("a");
			a2.id="disapproveButton"+key.entryId;
			a2.title = _("Press to disapprove this entry");
			a2.setAttribute("entryId",key.entryId);
			a2.setAttribute("class","keyLink");
			a2.addEventListener("click",this.disapprove,false);
			a2.textContent = _("no");
			tdButtons.appendChild(a1);
			tdButtons.appendChild(document.createTextNode(" | "));
			tdButtons.appendChild(a2);
			tdButtons.id = "authorizeButtons"+key.entryId;
			tdButtons.setAttribute("style","text-align : center");
			var auth = document.getElementById("thAuthorize");
			if(auth){
				var number = parseInt(auth.getAttribute("awaitingApproval"));
				auth.setAttribute("awaitingApproval",(number+1));
			}
		}
		rowNode.appendChild(tdColour);
		rowNode.appendChild(tdHours);
		rowNode.appendChild(tdInvoice);
		rowNode.appendChild(tdProject);
		rowNode.appendChild(tdTask);
		rowNode.appendChild(tdDiv);
		if(TimeSheet.activeInstance.viewingMode != "userview")
			rowNode.appendChild(tdButtons);
		table.appendChild(rowNode);
		//making the keyTableDiv as high as the detail table
		TimeSheet.activeInstance.textEntry.resizeKeyTable();

		TimeSheet.activeInstance.backgroundColourPointer++;
	};
	//this function resizes the key table to match the height of the Entry table
	this.resizeKeyTable = function()
	{
		var keyTableDiv = document.getElementById("keyEntry");
		var height = document.getElementById(TimeSheet.activeInstance.tableEntry.identity).offsetHeight - document.getElementById(TimeSheet.activeInstance.formEntryId).offsetHeight - document.getElementById(TimeSheet.activeInstance.buttonEntryId).offsetHeight - document.getElementById(TimeSheet.activeInstance.buttonEntry2Id).offsetHeight - document.getElementById(TimeSheet.activeInstance.textEntryId).offsetHeight - 2;
		keyTableDiv.style.height = height+"px";
	};

	//this highlights the cells corresponding to the key over which the mouse is located
	this.onColourOver = function(){

		var entryId = this.getAttribute("entryId");
        for(pos=0;pos<TimeSheet.activeInstance.tableEntry.keyArray.length;++pos)
		{
			if(TimeSheet.activeInstance.tableEntry.keyArray[pos].entryId == entryId )
			{
				key = TimeSheet.activeInstance.tableEntry.keyArray[pos];
				for(var i=0;i<key.entries.length;++i)
				{
					var td=document.getElementById(key.entries[i].id);
					if(td!=null)
						td.style.backgroundImage = "url('images/hash.gif')";
				}
				break;
			}
		}
	};
	this.onColourOut = function(){

		var entryId = this.getAttribute("entryId");
		for(pos=0;pos<TimeSheet.activeInstance.tableEntry.keyArray.length;++pos)
		{
			if(TimeSheet.activeInstance.tableEntry.keyArray[pos].entryId == entryId )
			{
				key = TimeSheet.activeInstance.tableEntry.keyArray[pos];
				for(var i=0;i<key.entries.length;++i)
				{
					var td=document.getElementById(key.entries[i].id);
					if(td!=null)
						td.style.backgroundImage = "";
				}
			}
		}
	};
	this.onInvoiceFocus = function(){
		TimeSheet.activeInstance.textEntry.addAutoComplete(this,"invoicecode");
    };
	this.onTaskFocus = function(){
		var inInvoiceValue = document.getElementById("invoiceCodeInput").value;
		TimeSheet.activeInstance.textEntry.addAutoComplete(this,"taskcode",inInvoiceValue);
    };
	this.addAutoComplete = function(eInput, type, othervar){
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
		}

		//this prevents adding more AutoComplete objects for the same input box at every focus event
		if(oAutoComplete != undefined) {
			//reinitialize properties that might have changed since the last call
			oAutoComplete.dataSource.scriptQueryAppend = scriptQueryAppend;
			return oAutoComplete;
		}

		//initializing the DataSource object
		XHRDataSource = new YAHOO.widget.DS_XHR(myServer, schema);
		XHRDataSource.connTimeout = 6000;
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
    oAutoComplete.scrollIntoView = true;
    oAutoComplete.activateFirstItem = true;
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
		switch(type){
			case "invoicecode":
				oAutoComplete.itemSelectEvent.subscribe(TimeSheet.activeInstance.textEntry.onInvoiceChange,null,false);
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
				break;
		}
		return oAutoComplete;
	};
	this.onInvoiceChange = function(){

		var invoiceCode = document.getElementById("invoiceCodeInput").value;
		if(invoiceCode == "")
		{
			TimeSheet.activeInstance.errorDisplay.showError(_("Please enter an invoice code"),null,ErrorDisplay.WARNING);
			return;
		}
		var xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		xmlData += "<VALIDATE TYPE=\"invoicecode\">\n";
		xmlData +="\t\t<CODE>" + escapeHTML(invoiceCode) +"</CODE>\n";
		xmlData += "</VALIDATE>";
		ajaxSendXMLtoPHP("validation.php",xmlData,TimeSheet.activeInstance.textEntry.addPriorInvoiceCheck);
	};

	this.editInvoiceCheck = function(XML) {
		if(XML==null)
			return;
		var statusText = XML.getElementsByTagName("STATUSTEXT")[0];
		var statusS = XML.getElementsByTagName("STATUS")[0];
		var taskNeeded = XML.getElementsByTagName("TASKNEEDED")[0];
		var entryId = XML.getElementsByTagName("ID")[0];
		var inInvoice = document.getElementById("invoiceCodeInput");
		if(statusS.textContent != "valid" && statusS.textContent != "completed")
		{
			TimeSheet.activeInstance.errorDisplay.showError(statusText.textContent,null,ErrorDisplay.FATAL);
			document.getElementById("editButton"+entryId.textContent).disabled = false;
			inInvoice.disabled = false;
			TimeSheet.activeInstance.textEntry.stopEvents = false;
			return;
		}
        var projectName = XML.getElementsByTagName("PROJECTNAME")[0];
		document.getElementById("projectNameInput").value = projectName.textContent;

		var inTask = document.getElementById("taskCodeInput");
		if(	(taskNeeded == null || taskNeeded.textContent == "false") && inTask.value == "")
		{
			TimeSheet.activeInstance.textEntry.updateKey(entryId.textContent);
			return;
		}
		if(inTask.value == "")
		{
			TimeSheet.activeInstance.errorDisplay.showError(_("Please enter a task code."),null,ErrorDisplay.WARNING);
			document.getElementById("editButton"+entryId.textContent).disabled = false;
			inInvoice.disabled = false;
			TimeSheet.activeInstance.textEntry.stopEvents = false;
			return;
		}
		inTask.disabled = true;
		var xmlData = "<\?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		xmlData += "<VALIDATE TYPE=\"taskcode\">\n";
		xmlData += "\t\t<ICODE>" + escapeHTML(inInvoice.value) +"</ICODE>\n";
		xmlData += "\t\t<CODE>" + escapeHTML(inTask.value) +"</CODE>\n";
		xmlData += "\t\t<ID>" + escapeHTML(entryId.textContent) +"</ID>\n";
		xmlData += "</VALIDATE>";
		ajaxSendXMLtoPHP("validation.php",xmlData,TimeSheet.activeInstance.textEntry.editTaskCheck);
	};
	this.addPriorInvoiceCheck = function(XML){
		if(XML === null)
			return;
		var statusText = XML.getElementsByTagName("STATUSTEXT")[0];
		var statusS = XML.getElementsByTagName("STATUS")[0];
		var entryId = XML.getElementsByTagName("ID")[0];
		if(statusS.textContent != "valid" && statusS.textContent != "completed")
		{
			TimeSheet.activeInstance.errorDisplay.showError(statusText.textContent,"x021a",ErrorDisplay.FATAL);
			return;
		}
		if(statusS.textContent == "completed")
		{
			if(!window.confirm(_("This invoice code is complete")+"\n"+_("Are you sure you want to use it?")+"\n"+_("Is there a more recent code that is currently in use?")))
				return;
		}
		var inInvoiceValue = document.getElementById("invoiceCodeInput").value;
		var projectName = XML.getElementsByTagName("PROJECTNAME")[0];
		document.getElementById("projectNameInput").value = projectName.textContent;
		TimeSheet.activeInstance.textEntry.addAutoComplete(document.getElementById("taskCodeInput"),"taskcode",inInvoiceValue);
	};
	this.addInvoiceCheck = function(XML) {
		if(XML==null)
			return;
		var statusText = XML.getElementsByTagName("STATUSTEXT")[0];
		var statusS = XML.getElementsByTagName("STATUS")[0];
		var taskNeeded = XML.getElementsByTagName("TASKNEEDED")[0];
		var entryId = XML.getElementsByTagName("ID")[0];
		var inInvoice = document.getElementById("invoiceCodeInput");
		if(statusS.textContent != "valid" && statusS.textContent != "completed")
		{
			TimeSheet.activeInstance.errorDisplay.showError(statusText.textContent,"x021b",ErrorDisplay.FATAL);
			document.getElementById('normalEntrySubmit').disabled = false;
			inInvoice.disabled = false;
			return;
		}
		var projectName = XML.getElementsByTagName("PROJECTNAME")[0];
		document.getElementById("projectNameInput").value = projectName.textContent;
		var inTask = document.getElementById("taskCodeInput");
		if(	(taskNeeded == null || taskNeeded.textContent == "false") && inTask.value == "")
		{
			TimeSheet.activeInstance.textEntry.addKey();
			return;
		}
		if(inTask.value == "")
		{
			TimeSheet.activeInstance.errorDisplay.showError(_("Please enter a task code!"),null,ErrorDisplay.WARNING);
			document.getElementById('normalEntrySubmit').disabled = false;
			inInvoice.disabled = false;
			return;
		}
		inTask.disabled = true;
		var xmlData = "<\?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		xmlData += "<VALIDATE TYPE=\"taskcode\">\n";
		xmlData += "\t\t<ICODE>" + escapeHTML(inInvoice.value) +"</ICODE>\n";
		xmlData += "\t\t<CODE>" + escapeHTML(inTask.value) +"</CODE>\n";
		xmlData += "</VALIDATE>";
		ajaxSendXMLtoPHP("validation.php",xmlData,TimeSheet.activeInstance.textEntry.addTaskCheck);
	};
	this.addTaskCheck = function(XML) {
		if(XML==null)
			return;
		var statusText = XML.getElementsByTagName("STATUSTEXT")[0];
		var statusS = XML.getElementsByTagName("STATUS")[0];
		if(statusS.textContent != "valid" && statusS.textContent != "allowednew")
		{
			TimeSheet.activeInstance.errorDisplay.showError(statusText.textContent,null,ErrorDisplay.FATAL);
			document.getElementById('normalEntrySubmit').disabled = false;
			document.getElementById("invoiceCodeInput").disabled = false;
			document.getElementById("taskCodeInput").disabled = false;
			return;
		}
		var taskCodeInp = document.getElementById('taskCodeInput');
		if(statusS.textContent == "allowednew")
		{
			if(!confirm(_("The task code you entered has not been found in the database. Are you sure you want to add it?")))
			{
				document.getElementById('normalEntrySubmit').disabled = false;
				document.getElementById("invoiceCodeInput").disabled = false;
				taskCodeInp.disabled = false;
				return;
			}
			TimeSheet.activeInstance.textEntry.taskName = prompt("Please specify a description for the new task code '"+taskCodeInp.value+"'",taskCodeInp.value);
			if(TimeSheet.activeInstance.textEntry.taskName == null)
			{
				document.getElementById('normalEntrySubmit').disabled = false;
				document.getElementById("invoiceCodeInput").disabled = false;
				taskCodeInp.disabled = false;
				return;
			}
		}
		TimeSheet.activeInstance.textEntry.addKey();
	};
	this.editTaskCheck = function(XML) {
		if(XML==null)
			return;
		var statusText = XML.getElementsByTagName("STATUSTEXT")[0];
		var statusS = XML.getElementsByTagName("STATUS")[0];
		var entryId = XML.getElementsByTagName("ID")[0];
		var inInvoice = document.getElementById("invoiceCodeInput");
		var inTask = document.getElementById("taskCodeInput");
		if(statusS.textContent != "valid" && statusS.textContent != "allowednew")
		{
			TimeSheet.activeInstance.errorDisplay.showError(statusText.textContent,null,ErrorDisplay.FATAL);
			document.getElementById("editButton"+entryId.textContent).disabled = false;
			inInvoice.disabled = false;
			inTask.disabled = false;
			TimeSheet.activeInstance.textEntry.stopEvents = false;
			return;
		}
		if(statusS.textContent == "allowednew")
		{
			if(!confirm(_("The task code you entered has not been found in the database. Are you sure you want to add it?")))
			{
				document.getElementById("editButton"+entryId.textContent).disabled = false;
				inInvoice.disabled = false;
				inTask.disabled = false;
				TimeSheet.activeInstance.textEntry.stopEvents = false;
				return;
			}
			var taskCode = document.getElementById('taskCodeInput').value;
			TimeSheet.activeInstance.textEntry.taskName = prompt(_("Please specify a description for the new task code '")+taskCode+"'",taskCode);
			if(TimeSheet.activeInstance.textEntry.taskName == null)
			{
				document.getElementById("editButton"+entryId.textContent).disabled = false;
				inInvoice.disabled = false;
				inTask.disabled = false;
				TimeSheet.activeInstance.textEntry.stopEvents = false;
				return;
			}
		}
		TimeSheet.activeInstance.textEntry.updateKey(entryId.textContent);
	};

	//this is used after a key's details have been edited
	this.updateKey = function(entryId){

		TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(0);
		var tdHours = document.getElementById("hours"+entryId);
		var tdInvoice = document.getElementById("invoiceCode"+entryId);
		var tdTask = document.getElementById("taskCode"+entryId);
		var tdProjectName = document.getElementById("projectName"+entryId);
		var tdNote = document.getElementById("note"+entryId);
		var inProjectNameValue = document.getElementById("projectNameInput").value;
		var inTaskValue = document.getElementById("taskCodeInput").value;
		var inInvoiceValue = document.getElementById("invoiceCodeInput").value;
		var inNoteValue = document.getElementById("notesInput").value;

		//updating the number of hours
		var addedEntries = TimeSheet.activeInstance.tableEntry.activeArray.length;
		tdHours.textContent = TimeSheet.activeInstance.displayTime(TimeSheet.activeInstance.getEntriesFromDisplay(tdHours.textContent) + addedEntries);

		TimeSheet.activeInstance.undoObject = null;

		document.getElementById("editButton"+entryId).value = _("modify");
		document.getElementById("editButton"+entryId).disabled = false;
		document.getElementById("removeButton"+entryId).value = _("remove");
		tdInvoice.textContent = inInvoiceValue;
		tdTask.textContent = inTaskValue;
		tdNote.textContent = inNoteValue;
		tdProjectName.textContent = "";
		tdProjectName.appendChild(document.createTextNode(inProjectNameValue));
		//adding the new entries (if any)
		//saving the invoice and task codes to all affected structures
		var i=0;
		for(;i<TimeSheet.activeInstance.tableEntry.keyArray.length;++i)
			if(TimeSheet.activeInstance.tableEntry.keyArray[i].entryId == entryId)
			{
				TimeSheet.activeInstance.tableEntry.keyArray[i].invoiceCode = inInvoiceValue;
				TimeSheet.activeInstance.tableEntry.keyArray[i].taskCode = inTaskValue;
				TimeSheet.activeInstance.tableEntry.keyArray[i].projectName = inProjectNameValue;
				TimeSheet.activeInstance.tableEntry.keyArray[i].taskName = TimeSheet.activeInstance.textEntry.taskName;
				TimeSheet.activeInstance.tableEntry.keyArray[i].note = inNoteValue;
				for(var j = 0;j < TimeSheet.activeInstance.tableEntry.activeArray.length;++j)
				{
					var cell = document.getElementById(TimeSheet.activeInstance.tableEntry.activeArray[j].id);
					cell.setAttribute("title",escapeHTML(inInvoiceValue+" - "+inTaskValue));
					cell.setAttribute("alt",escapeHTML(inInvoiceValue+" - "+inTaskValue));
					cell.setAttribute("entryId",entryId);
				}
				TimeSheet.activeInstance.tableEntry.keyArray[i].entries = TimeSheet.activeInstance.tableEntry.keyArray[i].entries.concat(TimeSheet.activeInstance.tableEntry.activeArray);
				TimeSheet.activeInstance.tableEntry.activeArray = new Array();
				break;
			}
		//the key could have been deleted by deslecting cells
		if(i == TimeSheet.activeInstance.tableEntry.keyArray.length)
		{
			var key = new Key(entryId,inInvoiceValue,inProjectNameValue,inTaskValue,TimeSheet.activeInstance.textEntry.taskName,TimeSheet.activeInstance.editColour, inNoteValue);
			key.entries = TimeSheet.activeInstance.tableEntry.activeArray;
			for(var j = 0;j < TimeSheet.activeInstance.tableEntry.activeArray.length;++j)
			{
				var cell = document.getElementById(TimeSheet.activeInstance.tableEntry.activeArray[j].id);
				cell.setAttribute("title",escapeHTML(inInvoiceValue+" - "+inTaskValue));
				cell.setAttribute("alt",escapeHTML(inInvoiceValue+" - "+inTaskValue));
				cell.setAttribute("entryId",entryId);
			}
			TimeSheet.activeInstance.tableEntry.keyArray.push(key);
			TimeSheet.activeInstance.tableEntry.activeArray = new Array();
		}
		TimeSheet.activeInstance.editId = -1;
		TimeSheet.activeInstance.editColour = "";
		TimeSheet.activeInstance.textEntry.updateTotals();
		document.getElementById("undoButton").removeAttribute("disabled");
		TimeSheet.activeInstance.textEntry.stopEvents = false;
	};

//event listener for the edit button on each row of the keyTable
	this.onKeyEdit = function(){

		if (TimeSheet.activeInstance.textEntry.stopEvents) {
			return;
		}

		document.getElementById("undoButton").setAttribute("disabled","disabled");
		var entryId = this.getAttribute("entryId");
		var tdInvoice=document.getElementById("invoiceCode"+entryId);
		var tdTask=document.getElementById("taskCode"+entryId);
		var tdProjectName = document.getElementById("projectName"+entryId);
		var tdNote=document.getElementById("note"+entryId);

        if(this.value === _("modify"))
		{
			//cancel previous action
			TimeSheet.activeInstance.tableEntry.emptyActiveArray();
			//this cleans the form area of the page
			var parentNode = document.getElementById(TimeSheet.activeInstance.formEntryId);
			while(parentNode.hasChildNodes()){
				parentNode.removeChild(parentNode.firstChild);
			}
			TimeSheet.activeInstance.textEntry.cancelEdit();
			//prepare the environment
			document.getElementById("proceedButton").disabled = "disabled";
			document.getElementById("entryButton").disabled="disabled";

			var tdColour=document.getElementById("colourCode"+entryId);
// 			var tempArr=tdColour.getAttribute("style").split(":");
			TimeSheet.activeInstance.editColour=tdColour.style.backgroundColor;
			TimeSheet.activeInstance.editId=entryId;
			this.value=_("save");
			document.getElementById("removeButton"+entryId).value = _("cancel");
			var cancelEdit = document.getElementById("removeButton"+entryId);
			cancelEdit.addEventListener("click", closeEdit);
			function closeEdit (){
				var nt = document.getElementById("note"+entryId);
				var img = document.getElementById("image"+entryId);
				nt.style.display = "none";
				img.style.display = "block";
			}
			var inInvoice=document.createElement("input");
			inInvoice.type="text";
			inInvoice.tabIndex = 1;
			inInvoice.value = unescapeHTML(tdInvoice.textContent);
			inInvoice.id = "invoiceCodeInput";
			inInvoice.addEventListener("focus",TimeSheet.activeInstance.textEntry.onInvoiceFocus,false);
			var inProjectName=document.createElement("input");
			inProjectName.type="hidden";
			inProjectName.value = unescapeHTML(tdProjectName.textContent);
			inProjectName.id = "projectNameInput";
			var inTask=document.createElement("input");
			inTask.type="text";
			inTask.tabIndex = 2;
			inTask.value = unescapeHTML(tdTask.textContent);
			inTask.id = "taskCodeInput";
			inTask.addEventListener("focus",TimeSheet.activeInstance.textEntry.onTaskFocus,false);
			var nt = document.getElementById("note"+entryId);
			var img = document.getElementById("image"+entryId);
			nt.style.display = "block";
			img.style.display = "none";
			var inNote=document.createElement("input");
			inNote.type="text";
			inNote.tabIndex = 3;
			inNote.value = unescapeHTML(tdNote.textContent);
			inNote.id = "notesInput";
			tdInvoice.textContent="";
			tdTask.textContent="";
			tdNote.textContent="";
			tdInvoice.appendChild(inInvoice);
			tdInvoice.appendChild(inProjectName);
			tdTask.appendChild(inTask);
			tdNote.appendChild(inNote);
			//setting the hour amount for the counter
			var amount = 0;
			var i = 0;
			for(;i< TimeSheet.activeInstance.tableEntry.keyArray.length;i++)
			{
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entryId == entryId)
				{
					undoKey = new Key(TimeSheet.activeInstance.tableEntry.keyArray[i].entryId,TimeSheet.activeInstance.tableEntry.keyArray[i].invoiceCode,TimeSheet.activeInstance.tableEntry.keyArray[i].projectName,TimeSheet.activeInstance.tableEntry.keyArray[i].taskCode,TimeSheet.activeInstance.tableEntry.keyArray[i].taskName,TimeSheet.activeInstance.tableEntry.keyArray[i].colour,TimeSheet.activeInstance.tableEntry.keyArray[i].note);
					for(var j=0; j < TimeSheet.activeInstance.tableEntry.keyArray[i].entries.length; ++j)
						undoKey.entries.push(new Entry(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id,TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].date,TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType));
					TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(TimeSheet.activeInstance.tableEntry.keyArray[i].entries.length);
					break;
				}
			}
			//setting the UNDO object
			TimeSheet.activeInstance.undoObject2 = new undoClass("edit",new Array(i,undoKey));
			inInvoice.focus();
		}
		else
		{//the user pressed save
		    this.disabled = true;
		    TimeSheet.activeInstance.textEntry.stopEvents = true;
			var inInvoice = document.getElementById("invoiceCodeInput");
			if(!inInvoice || inInvoice.value == "")
			{
				TimeSheet.activeInstance.errorDisplay.showError(_("Please enter an invoice code"),null,ErrorDisplay.WARNING);
				this.disabled = false;
				return;
			}

			var nt = document.getElementById("note"+entryId);
			var img = document.getElementById("image"+entryId);
			var inNote=document.getElementById("notesInput");
			img.setAttribute("title", inNote.value);
			nt.style.display = "none";
			img.style.display = "block";

			inInvoice.disabled = true;
			var xmlData = "<\?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			xmlData += "<VALIDATE TYPE=\"invoicecode\">\n";
			xmlData +="\t\t<CODE>" + escapeHTML(inInvoice.value) +"</CODE>\n";
			xmlData += "\t\t<ID>" + escapeHTML(entryId) +"</ID>\n";
			xmlData += "</VALIDATE>";
			ajaxSendXMLtoPHP("validation.php",xmlData,TimeSheet.activeInstance.textEntry.editInvoiceCheck);
		}
	};
	this.cancelEdit = function()
	{//this function cancels the edit action in the key table and reverts to the previous state
		if(TimeSheet.activeInstance.editId==-1)
			return;

		var editButton = document.getElementById("editButton"+TimeSheet.activeInstance.editId);
		editButton.value = _("modify");
		editButton.disabled = false;
		var removeButton = document.getElementById("removeButton"+TimeSheet.activeInstance.editId);
		removeButton.value = _("remove");
	  var tdInvoice = document.getElementById("invoiceCode"+TimeSheet.activeInstance.editId);
		var tdTask = document.getElementById("taskCode"+TimeSheet.activeInstance.editId);
		var tdNote = document.getElementById("note"+TimeSheet.activeInstance.editId);
		tdInvoice.textContent = escapeHTML(TimeSheet.activeInstance.undoObject2.key.invoiceCode);
		tdTask.textContent = escapeHTML(TimeSheet.activeInstance.undoObject2.key.taskCode);
		tdNote.textContent = escapeHTML(TimeSheet.activeInstance.undoObject2.key.note);
		TimeSheet.activeInstance.undoLastAction(null);
		TimeSheet.activeInstance.undoObject = null;
		TimeSheet.activeInstance.editId=-1;
		TimeSheet.activeInstance.editColour="";
		TimeSheet.activeInstance.textEntry.stopEvents = false;
	};
//event listener for the remove button on each row of the keyTable
	this.onKeyRemove = function(){

		if (TimeSheet.activeInstance.textEntry.stopEvents) {
			return;
		}

		//cancel previous action
		document.getElementById("undoButton").setAttribute("disabled","disabled");
		document.getElementById("entryButton").setAttribute("disabled","disabled");
		TimeSheet.activeInstance.undoObject = null;
		TimeSheet.activeInstance.tableEntry.emptyActiveArray();
		var parentNode = document.getElementById(TimeSheet.activeInstance.formEntryId);
		while(parentNode.hasChildNodes()){
			parentNode.removeChild(parentNode.firstChild);
		}
		TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(0);
		if(this.value == _("cancel"))
		{
			TimeSheet.activeInstance.textEntry.cancelEdit();
			return;
		}
		TimeSheet.activeInstance.textEntry.cancelEdit();
		var i=0;
		for(;i < TimeSheet.activeInstance.tableEntry.keyArray.length;++i)
			if(TimeSheet.activeInstance.tableEntry.keyArray[i].entryId == this.getAttribute("entryId"))
			{
				key = TimeSheet.activeInstance.tableEntry.keyArray[i];
				for(var j=0;j<key.entries.length;++j)
				{
					//removing graphical interface elements
					var td = document.getElementById(key.entries[j].id);
					if(td!=null)
					{
						td.style.backgroundColor = "";//remove normal time graphics
						td.removeAttribute("alt");//remove invoice and task code info
						td.removeAttribute("title");
						td.removeAttribute("entryId");
						td.textContent = "";//remove ot time graphics
					}
				}
				break;
			}
		TimeSheet.activeInstance.tableEntry.keyArray.splice(i,1);

		//obtaining the row that needs to be removed from the keyTable
		var row=this.parentNode;
		while(row!=null && row.tagName!="TR")
		{
			row=row.parentNode;
		}
		TimeSheet.activeInstance.textEntry.updateTotals();

		//prepare the undoObject
		TimeSheet.activeInstance.undoObject = new undoClass("remove",new Array(i,key,row));
		document.getElementById(TimeSheet.activeInstance.keyTableId).removeChild(row);
		//make the undo button visible
		document.getElementById("undoButton").removeAttribute("disabled");
	};

	this.finishEntry = function(evt){
		if(evt.button != 0)
			return;

		if(TimeSheet.activeInstance.viewingMode == "adminview" || TimeSheet.activeInstance.viewingMode == "approve")
		{//this happens when an admin or a line manager clicks the submit button on any timesheet that was not submitted
			var confirmed = confirm(_("Are you sure you want to submit this time sheet?"));
			if(confirmed)
			{
				var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
				request += "<REQUEST OBJECT=\"submitTimesheet\">\n";
				request += "\t<REFID>" + TimeSheet.activeInstance.refid + "</REFID>\n";
				request += "</REQUEST>\n";
				TimeSheet.activeInstance.saving = true;
				ajaxSendXMLtoPHP('parseResponse.php',request,TimeSheet.activeInstance.textEntry.afterSave);
			}
			return;
		}
		var message = _("Once you submit the timesheet details you can NOT modify them any further.\nAre you sure you want to continue?");

		if(TimeSheet.activeInstance.textEntry.totalHours <TimeSheet.activeInstance.minHours)
		{
			if(TimeSheet.activeInstance.variable)
				message =  _("You didn't declare the minimum hours.\nAre you sure you want to continue?");
			else if(TimeSheet.activeInstance.viewingMode === "adminedit")
				message =  _("This time sheet doesn't have the minimum hours declared.\nAre you sure you want to continue?");
			else
		    {
				TimeSheet.activeInstance.errorDisplay.showError(_("You didn't declare the minimum hours yet.\n You are not allowed to submit this time sheet!"),null,ErrorDisplay.WARNING);
				this.setAttribute("disabled","disabled");
				return;
			}
		}else{
			var undeclaredHours = TimeSheet.activeInstance.textEntry.totalHours - TimeSheet.activeInstance.minHours -TimeSheet.activeInstance.textEntry.chargedOTHours - TimeSheet.activeInstance.textEntry.lieuOTHours;
			//this is for fuzziness
			//if the undeclared hours of overtime is less than sixProcents no error will be raised.
			var sixProcents = Math.abs(TimeSheet.activeInstance.minHours * 0.06);
			if(undeclaredHours < 0)
			{
				if(TimeSheet.activeInstance.viewingMode === "adminedit")
					message =  _("This time sheet has more overtime hour(s) than it should:")+Math.abs(undeclaredHours)+"\n"+_("Are you sure you want to continue?");
				else
				{
					TimeSheet.activeInstance.errorDisplay.showError(_("ERROR: You declared more overtime hour(s) than you worked: "+Math.abs(undeclaredHours)),null,ErrorDisplay.WARNING);
					return;
				}
			}
			if(undeclaredHours >= sixProcents)
			{
				if(TimeSheet.activeInstance.viewingMode === "adminedit")
					message =  sprintf(_("This time sheet has %s undeclared hour(s) of overtime!"),undeclaredHours)+"\n"+_("Are you sure you want to continue?");
				else if(!TimeSheet.activeInstance.variable){
					TimeSheet.activeInstance.errorDisplay.showError(_("ERROR")+": "+_("You have undeclared hour(s) of overtime: !"+undeclaredHours),null,ErrorDisplay.WARNING);
					message = _("You have undeclared hour(s) of overtime: !")+undeclaredHours+"\n" +_("Once you submit the timesheet details you can NOT modify them any further.\n Are you sure you want to continue?");
				}
			}
		}

		var response = window.confirm(message);
		if(!response)
			return;

		this.setAttribute("disabled","disabled");
		TimeSheet.activeInstance.errorDisplay.showError(_("Submitting..."),null,ErrorDisplay.NOTICE);
		document.getElementById("saveButton").setAttribute("disabled","disabled");
		TimeSheet.activeInstance.textEntry.serializeAndSend(true);
	};
	this.saveEntry = function(evt){
		if(evt.button != 0)
			return;

		this.setAttribute("disabled","disabled");
		TimeSheet.activeInstance.errorDisplay.showError(_("Saving..."),null,ErrorDisplay.NOTICE);
		document.getElementById("proceedButton").setAttribute("disabled","disabled");
		TimeSheet.activeInstance.textEntry.serializeAndSend(false);
	};
	this.cyclicSave = function(){
		var saveButton = document.getElementById("saveButton");
		if(!saveButton.hasAttribute("disabled"))
		{
			saveButton.setAttribute("disabled","disabled");
			document.getElementById("proceedButton").setAttribute("disabled","disabled");
			document.getElementById("entryButton").setAttribute("disabled","disabled");
			TimeSheet.activeInstance.errorDisplay.showError(_("Saving..."),null,ErrorDisplay.NOTICE);
			TimeSheet.activeInstance.textEntry.serializeAndSend(false);
		}else{
			TimeSheet.activeInstance.textEntry.cycleSaveCount++;
			if(TimeSheet.activeInstance.textEntry.cycleSaveCount > TimeSheet.activeInstance.textEntry.cyclesBeforeRedirect){
				window.location.assign('timesheetList.php');//reloads after some time without activity in case the session expired
			}
		}
	};

	this.serializeAndSend = function(isFinal){
//isFinal tells us if the user submitted the form for good or he's just saving it for later

		//translating from keyArray to serializeParentStruct and serializeChildStruct
		//all entries of the same invoice code, task code will be joined in one serializeParentStruct
		//all entries of the same date and rate will be joined under one serializeChildStruct
		//one serializeParentStruct has many serializeChildStruct
		//one serializeChildStruct has many entries
       	var serializeStructArray = new Array();
		for(var i=0;i<TimeSheet.activeInstance.tableEntry.keyArray.length;++i)
		{
			for(var j=0;j < TimeSheet.activeInstance.tableEntry.keyArray[i].entries.length;++j)
			{
				var parentStruct;
				var childStruct;
				var idx1=0,idx2=0;
				for(;idx1 < serializeStructArray.length;++idx1)
					if(serializeStructArray[idx1].invoiceCode == TimeSheet.activeInstance.tableEntry.keyArray[i].invoiceCode &&
						serializeStructArray[idx1].taskCode == TimeSheet.activeInstance.tableEntry.keyArray[i].taskCode &&
						serializeStructArray[idx1].note == TimeSheet.activeInstance.tableEntry.keyArray[i].note )
					{
						parentStruct = serializeStructArray[idx1];
						break;
					}
				//no match on parentStruct
				if(idx1 == serializeStructArray.length)
				{
					parentStruct = new serializableParentStruct();
					parentStruct.invoiceCode = TimeSheet.activeInstance.tableEntry.keyArray[i].invoiceCode;
					parentStruct.taskCode = TimeSheet.activeInstance.tableEntry.keyArray[i].taskCode;
					parentStruct.taskName = TimeSheet.activeInstance.tableEntry.keyArray[i].taskName;
					parentStruct.note = TimeSheet.activeInstance.tableEntry.keyArray[i].note;
					parentStruct.colour = TimeSheet.activeInstance.tableEntry.keyArray[i].colour;

					childStruct = new serializableChildStruct();
					childStruct.date = TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].date;
					childStruct.rateType = Rates.getRateType(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType,childStruct.date);
					childStruct.entries.push(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id);
					parentStruct.childNodes.push(childStruct);
					serializeStructArray.push(parentStruct);
					continue;
				}
				//one matching parentStruct has been found
				for(;idx2 < parentStruct.childNodes.length;++idx2)
					if(parentStruct.childNodes[idx2].date == TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].date &&
						parentStruct.childNodes[idx2].rateType == Rates.getRateType(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType,TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].date))
					{
						childStruct = parentStruct.childNodes[idx2];
						break;
					}
				//no match on childStruct
				if(idx2 == parentStruct.childNodes.length)
				{
					childStruct = new serializableChildStruct();
					childStruct.date = TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].date;
					childStruct.rateType = Rates.getRateType(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType,childStruct.date);
					childStruct.entries.push(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id);
					parentStruct.childNodes.push(childStruct);
					continue;
				}
				//one matching childStruct has been found
				childStruct.entries.push(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id);
 			}
		}
		var serializedString = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		var currRefid = TimeSheet.activeInstance.refid;
		if(TimeSheet.activeInstance.viewingMode == "sameaslastweek")
			currRefid = -1;
		var	date = DateToString(TimeSheet.activeInstance.weekEndingDate);
		serializedString += "<FORM FINAL=\""+isFinal+"\" WEEKENDINGDATE=\""+date+"\" RESOLUTION=\""+TimeSheet.activeInstance.resolution+"\" STARTTIME=\""+TimeSheet.activeInstance.startTime+"\" STOPTIME=\""+TimeSheet.activeInstance.stopTime+"\" SHOWWEEKEND=\""+TimeSheet.activeInstance.weekend+"\" REFID=\""+currRefid+"\">\n";

		for(var i = 0;i < serializeStructArray.length;++i)
		{
			serializedString +="\t<STRUCT INVOICECODE=\"" + escapeHTML( serializeStructArray[i].invoiceCode) + "\" TASKCODE=\""+ escapeHTML(serializeStructArray[i].taskCode) +"\" TASKNAME=\""+ escapeHTML(serializeStructArray[i].taskName) +"\" COLOUR=\""+serializeStructArray[i].colour+"\" NOTES=\""+serializeStructArray[i].note+"\">\n";
			for(var j =0; j < serializeStructArray[i].childNodes.length;j++)
			{
				serializedString +="\t\t<CHILDNODE ";

				serializedString +=" DATE=\""+ serializeStructArray[i].childNodes[j].date +"\"";
				serializedString +=" RATETYPE=\""+ serializeStructArray[i].childNodes[j].rateType +"\"";
				serializedString +=" HOURS=\""+ TimeSheet.activeInstance.getNumberOfHours(serializeStructArray[i].childNodes[j].entries.length)+"\"";
				serializedString +=">\n";

				for(var k=0;k < serializeStructArray[i].childNodes[j].entries.length;k++)
					serializedString +="\t\t\t<ENTRY ID=\""+serializeStructArray[i].childNodes[j].entries[k]+"\"/>\n";
				serializedString +="\t\t</CHILDNODE>\n";
			}
			serializedString +="\t</STRUCT>\n";
		}
		serializedString += "</FORM>\n";
		TimeSheet.activeInstance.saving = true;
		//this will timeout the save action after 10 seconds
		window.setTimeout(TimeSheet.activeInstance.textEntry.timeoutSave,10000);
		//debugging stuff
// 		var textarea = document.createElement("textarea");
// 		textarea.cols = "20";
// 		textarea.rows = "5";
// 		textarea.textContent = serializedString;
// 		document.getElementsByTagName("body")[0].appendChild(textarea);
		ajaxSendXMLtoPHP("parseResponse.php",serializedString,TimeSheet.activeInstance.textEntry.afterSave);
	};
    this.timeoutSave = function()
	{
		if(TimeSheet.activeInstance.saving)
		{
			TimeSheet.activeInstance.errorDisplay.cleanAll();
			TimeSheet.activeInstance.errorDisplay.showError(_("Saving failed. Please check your internet connection and try again later."),'x001',ErrorDisplay.FATAL);
			if(TimeSheet.activeInstance.textEntry.totalHours >= TimeSheet.activeInstance.minHours)
				document.getElementById("proceedButton").removeAttribute("disabled");
			if(TimeSheet.activeInstance.tableEntry.activeArray.length==0)
				document.getElementById("entryButton").setAttribute("disabled","disabled");
			else
				document.getElementById("entryButton").removeAttribute("disabled");
			document.getElementById("saveButton").removeAttribute("disabled");
			TimeSheet.activeInstance.saving = false;
		}
	};
	this.afterSave = function(XML)
	{
		if(!TimeSheet.activeInstance.saving)
			return;
		TimeSheet.activeInstance.saving = false;
		TimeSheet.activeInstance.errorDisplay.cleanAll();
		if(XML == null)
		{
			TimeSheet.activeInstance.errorDisplay.showError(_("Saving failed.\n Please try again later."),'x002',ErrorDisplay.FATAL);
			if(TimeSheet.activeInstance.textEntry.totalHours >= TimeSheet.activeInstance.minHours || TimeSheet.activeInstance.viewingMode == "adminview" || TimeSheet.activeInstance.viewingMode == "adminedit" || TimeSheet.activeInstance.viewingMode == "approve")
				document.getElementById("proceedButton").removeAttribute("disabled");
			if(TimeSheet.activeInstance.tableEntry.activeArray.length == 0)
				document.getElementById("entryButton").setAttribute("disabled","disabled");
			else
				document.getElementById("entryButton").removeAttribute("disabled");
				document.getElementById("saveButton").removeAttribute("disabled");
			return;
		}
		var statusText = "";
		var status = XML.getElementsByTagName("STATUS")[0];
		var statusText2 = XML.getElementsByTagName("STATUSTEXT")[0];
		if(statusText2 != null){
			statusText2 = statusText2.textContent;
		}
		var refid = parseInt(XML.getElementsByTagName("REFID")[0].textContent);

		//if another time sheet had been submitted for this week before the current one got saved, this save attempt will fail
		if(status.textContent == "duplicateTimeSheet")
		{
			var locArr = window.location.toString().split("/");
			var loc = locArr.pop();
			window.location = locArr.join("/")+"/timesheetNew.php?status=duplicateTimeSheet";
			return;
		}

		if(TimeSheet.activeInstance.viewingMode == "adminview" || TimeSheet.activeInstance.viewingMode == "approve")
		{
			if(status.textContent == _("submitted"))
			{
				document.getElementById("proceedButton").setAttribute("disabled","disabled");
				TimeSheet.activeInstance.errorDisplay.showError(_("Status change successful!"),null,ErrorDisplay.NOTICE);
				//changing the status displayed in bold
				var infoSection = document.getElementById("infoSection");
				if(!infoSection) return;
				var spans = infoSection.getElementsByTagName("span");
				if(!spans || spans.length == 0) return;
				for(var j=0;j<spans.length;++j)
					if(spans[j].id == "notsubmitted")
						infoSection.removeChild(spans[j]);
			}
			else
			{
				document.getElementById("proceedButton").removeAttribute("disabled");
				TimeSheet.activeInstance.errorDisplay.showError(_("Status change unsuccessful!"),null,ErrorDisplay.FATAL);
			}
			return;
		}
		if(TimeSheet.activeInstance.textEntry.totalHours >= TimeSheet.activeInstance.minHours || TimeSheet.activeInstance.viewingMode == "adminedit"){
			document.getElementById("proceedButton").removeAttribute("disabled");
		}
		if(TimeSheet.activeInstance.tableEntry.activeArray.length==0)
			document.getElementById("entryButton").setAttribute("disabled","disabled");
		else
			document.getElementById("entryButton").removeAttribute("disabled");

		var statusType = ErrorDisplay.NOTICE;
		var statusCode = null;
		if(status != null && status.textContent != "") {
			switch(status.textContent)
			{
				case "saved":
					statusText = _("Your entered details have been saved.\n You can safely logout now");
					break;
				case "toolittlehol":
					offendingFigure = XML.getElementsByTagName("OFFENDING")[0];
					if (offendingFigure != null) {
						offendingFigure = Math.abs(offendingFigure.textContent);
					}
					else offendingFigure = "?";
					statusText = sprintf(_("You need to put at least %s hours to HOL. You're trying to declare only %s hours"),3.5,offendingFigure);
					statusType = ErrorDisplay.FATAL;
				case "toolittletoil":
					offendingFigure = XML.getElementsByTagName("OFFENDING")[0];
					if (offendingFigure != null) {
						offendingFigure = Math.abs(offendingFigure.textContent);
					}
					else offendingFigure = "?";
					statusText = sprintf(_("You need to put at least %s hours to TOIL. You're trying to declare only %s hours"),3.5,offendingFigure);
					statusType = ErrorDisplay.FATAL;
					break;
				case "received":
					statusText = _("Timesheet details have been received but not saved. Please try again.");
					statusCode = "x033";
					statusType = ErrorDisplay.FATAL;
					break;
				case "invoicecodeerror":
					statusText = _("One invoice code was not found.\n Data could not be saved");
					statusCode = "x034";
					statusType = ErrorDisplay.FATAL;
					break;
				case "closedproject":
					statusText = statusText2;
					statusCode = "x034b";
					statusType = ErrorDisplay.FATAL;
					break;
				case "taskcodeerror":
					statusText = _("One task code was not found.\n Your data could not be saved");
					statusCode = "x035";
					statusType = ErrorDisplay.FATAL;
					break;
				case "dberror":
					statusText = _("There was a database error,\n please try saving again later");
					statusCode = "x036";
					statusType = ErrorDisplay.FATAL;
					break;
				case "parseerror":
					statusText = _("There has been reported a parsing error,\n please contact the web developer and report the screen contents");
					statusCode = "x037";
					statusType = ErrorDisplay.FATAL;
					break;
				default:
					statusText = _("Status set to\n ")+status.textContent;
					break;
			}
			if (status.textContent != "saved" && status.textContent != "toomuchtoil") {
				document.getElementById("saveButton").removeAttribute("disabled");
			}
		}
		else
			statusText = _("No response from the database...");
		TimeSheet.activeInstance.errorDisplay.showError(statusText,statusCode,statusType);
		if (status.textContent == "toomuchtoil") {
			offendingFigure = XML.getElementsByTagName("OFFENDING")[0];
			if (offendingFigure != null) {
				offendingFigure = Math.abs(offendingFigure.textContent);
			}
			else offendingFigure = "?";

			TimeSheet.activeInstance.errorDisplay.showError(_('You are trying to take more TOIL than you have available'),'x038',ErrorDisplay.FATAL);
			alert(sprintf(_("You declared %s hours more TOIL on your time sheet than the total amount you have available.\n "),offendingFigure) +
				  _("Please adjust the amount of TOIL or talk to the Administration Department."));
			if(refid != null && refid != -1)
			{
				TimeSheet.activeInstance.refid = refid;
			}
			return;
		}
		var isFinal = XML.getElementsByTagName("FINAL")[0];
		if(isFinal !=null && isFinal.textContent == "true")
		{ //blocking interaction and redirecting
			if(TimeSheet.activeInstance.viewingMode == "adminedit")
				window.location = "submissionStatus.php";
			else
				window.location = "timesheetList.php";
			return;
		}
		if(refid != null && refid != -1)
		{
			TimeSheet.activeInstance.refid = refid;
			//if(TimeSheet.activeInstance.viewingMode == "sameaslastweek"){
				window.location.search = "?refid="+refid;
			//}
		}
	};

	this.updateTotals = function(){

		var totalEntries = 0;
		var normalT = 0;
		var chargedOT = 0;
		var lieuOT = 0;
		//updating the hours for each day.
		var daysArray = [0,0,0,0,0,0,0];//from Saturday to Friday
		for(var i = 0;i< TimeSheet.activeInstance.tableEntry.keyArray.length;++i)
		{
		    for(var j = 0; j<TimeSheet.activeInstance.tableEntry.keyArray[i].entries.length; ++j)
			{
				totalEntries++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType == Rates.RATE_NORMAL)
					normalT++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType == Rates.RATE_OT_LIEU)
					lieuOT++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType == Rates.RATE_OT_CHARGED || TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].rateType == Rates.RATE_OT_CHARGED_SUNDAY)
					chargedOT++;
				//counting hours per day
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id.indexOf("Saturday")!=-1)
					daysArray[0]++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id.indexOf("Sunday")!=-1)
					daysArray[1]++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id.indexOf("Monday")!=-1)
					daysArray[2]++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id.indexOf("Tuesday")!=-1)
					daysArray[3]++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id.indexOf("Wednesday")!=-1)
					daysArray[4]++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id.indexOf("Thursday")!=-1)
					daysArray[5]++;
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id.indexOf("Friday")!=-1)
					daysArray[6]++;
			}
		}
		//writing the hours per day at the bottom of each column
		var start = 0;
		if(TimeSheet.activeInstance.weekend == "false")
			start = 2;
		for(var j=start;j<daysArray.length;++j)
			document.getElementById("noevent-bottom"+j).textContent =  TimeSheet.activeInstance.displayTime(daysArray[j]);

		TimeSheet.activeInstance.textEntry.totalHours = TimeSheet.activeInstance.getNumberOfHours(totalEntries);
		TimeSheet.activeInstance.textEntry.normalHours = TimeSheet.activeInstance.getNumberOfHours(normalT);
		TimeSheet.activeInstance.textEntry.chargedOTHours = TimeSheet.activeInstance.getNumberOfHours(chargedOT);
		TimeSheet.activeInstance.textEntry.lieuOTHours = TimeSheet.activeInstance.getNumberOfHours(lieuOT);

		document.getElementById(TimeSheet.activeInstance.textEntry.mahTdId).textContent = TimeSheet.activeInstance.minHours;
		document.getElementById(TimeSheet.activeInstance.textEntry.totalTdId).textContent = TimeSheet.activeInstance.displayTime(totalEntries);
		document.getElementById(TimeSheet.activeInstance.textEntry.ntTdId).textContent = TimeSheet.activeInstance.displayTime(normalT);
		document.getElementById(TimeSheet.activeInstance.textEntry.chargedTdId).textContent = TimeSheet.activeInstance.displayTime(chargedOT);
		document.getElementById(TimeSheet.activeInstance.textEntry.lieuTdId).textContent = TimeSheet.activeInstance.displayTime(lieuOT);

		//changing colours
		if(normalT > 0)
			document.getElementById(TimeSheet.activeInstance.textEntry.ntTdId).setAttribute("class","OutputTextHighlight");
		else
			document.getElementById(TimeSheet.activeInstance.textEntry.ntTdId).setAttribute("class","OutputText");
		if(lieuOT > 0)
			document.getElementById(TimeSheet.activeInstance.textEntry.lieuTdId).setAttribute("class","OutputTextHighlight");
		else
			document.getElementById(TimeSheet.activeInstance.textEntry.lieuTdId).setAttribute("class","OutputText");
		if(chargedOT > 0)
			document.getElementById(TimeSheet.activeInstance.textEntry.chargedTdId).setAttribute("class","OutputTextHighlight");
		else
			document.getElementById(TimeSheet.activeInstance.textEntry.chargedTdId).setAttribute("class","OutputText");
		if(TimeSheet.activeInstance.editId!=-1 || TimeSheet.activeInstance.viewing)
		{
			if(TimeSheet.activeInstance.textEntry.totalHours <TimeSheet.activeInstance.minHours)
				document.getElementById(TimeSheet.activeInstance.textEntry.totalTdId).setAttribute("class","OutputTextRed");
			else
				document.getElementById(TimeSheet.activeInstance.textEntry.totalTdId).setAttribute("class","OutputTextGreen");
			if((TimeSheet.activeInstance.viewingMode == "adminview" || TimeSheet.activeInstance.viewingMode == "approve") && !TimeSheet.activeInstance.submitted)
				document.getElementById("proceedButton").removeAttribute("disabled");
			return;
		}
		if(TimeSheet.activeInstance.textEntry.totalHours > 0)
	     	document.getElementById("saveButton").removeAttribute("disabled");
		else
			document.getElementById("saveButton").disabled = "disabled";
		if(TimeSheet.activeInstance.textEntry.totalHours < TimeSheet.activeInstance.minHours)
		{
			TimeSheet.activeInstance.exceededNormalHours = false;
			document.getElementById("proceedButton").disabled = "disabled";
			document.getElementById(TimeSheet.activeInstance.textEntry.totalTdId).setAttribute("class","OutputTextRed");
		}
		else
		{
			TimeSheet.activeInstance.exceededNormalHours = true;
			document.getElementById("proceedButton").removeAttribute("disabled");
			document.getElementById(TimeSheet.activeInstance.textEntry.totalTdId).setAttribute("class","OutputTextGreen");
		}
		if(TimeSheet.activeInstance.viewingMode == "adminedit")
			document.getElementById("proceedButton").removeAttribute("disabled");
	};
};
