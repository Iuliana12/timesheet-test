//this is a static variable that the event listeners will use without requiring a fixed instance name
TimeSheet.activeInstance;
function TimeSheet(resolution,showWeekendDays,startTime,stopTime,weekEndingDate,minHours,refid,viewingMode,variable, submitted){

	//These two variables are used to give each selection of entries their own background colour
	this.colourObject = new masterColourObject();
	this.tablemanip = "";
	//this will store the last action's data for restauration
	this.undoObject = null;
	//this is used to restore a previous state of a key after edit
	//we need it because while editing the first one is being reinitialized
	this.undoObject2 = null;
	//this is the id of the timesheet that needs editing. If it's -1 then we're adding a new timesheet
	this.refid = refid;
	//this is true if the object is created just for viewing in the approving mode the time sheet contents
	this.viewingMode = viewingMode;
	//this is true if the object is created just for viewing in the approving mode the time sheet contents
	this.viewing = ((viewingMode == "approve") || (viewingMode == "userview") || (viewingMode == "adminview")) ;
	//This holds the resolution of the table, which is the number of minutes an entry represents
	this.resolution = parseFloat(resolution,10);
	//old time sheets have the resolution represented as a fraction of an hour
	if(this.resolution < 1){
		this.resolution = this.resolution * 60;
	}
	//This holds whether to create the weekend in the entry table
	this.weekend = showWeekendDays;
	//This holds the earliest time @which the user started, and therefore the earliest to create the table
	this.startTime = startTime;
	//This holds the latest time @which the user finished, and therefore the latest to create the table
	this.stopTime = stopTime;
	this.minHours = parseFloat(minHours);
	//This holds the date of the friday the week finished
	this.weekEndingDate = weekEndingDate;
	//This is true for employees that work variable number of hours
	this.variable = (variable == "true");
	//This holds the identity of the module
	this.identity = "entryConsole";
	//This creates the new table class that makes up the entryConsole
	this.tableEntry = new TimeSheetTableEntry();
	//This creates the new text class that makes up the entryConsole
	this.textEntry = new TimeSheetTextEntry();
	//a list of preseted id names for certain page zones
	this.textEntryId = "textEntry";
	this.buttonEntryId = "buttonEntry";
	this.buttonEntry2Id = "buttonEntry2";
	this.formEntryId = "formEntry";
  this.keyEntryId = "keyEntry";
	this.keyTableId = "keyTable";
	this.entryLastId = 0;
	//this is made true only after the normal Hour limit is exceeded
	this.exceededNormalHours = false;
	//this tells different functions if the user is currently editing one entry and which entry it edits
	this.editId=-1;
	//this is the colour we'll use when editing a particular entry
	this.editColour = "";
	//this tells what kind of time is being selected at the moment
	this.selectedRateType = null;
	//this will be used by the timeoutSave and afterSave functions to see which one was called first
	this.saving = false;
	this.submitted = (submitted == "true");
	this.errorDisplay = new ErrorDisplay('errors','errorDiv');

	this.displayTime = function(entries){
		var hours = parseInt(this.resolution * entries / 60, 10);
		var minutes = this.resolution * entries % 60;
		var part1 = hours + _('h');
		var part2 = minutes + _('m');
		if(hours == 0) {
			part1 = '';
		}
		if(minutes == 0) {
			part2 = '';
		}
		if(hours ==0 && minutes == 0) {
			part1 = '0';
		}
		return part1 + part2;
	};
	this.getNumberOfHours = function(entries){
		var hours = parseInt(this.resolution * entries / 60, 10);
		var minutes = this.resolution * entries % 60;
		return (hours + minutes /60).toFixed(2);
	};
	this.getEntriesFromDisplay = function(display){
		if(display == '' || display == '0'){
			return 0;
		}
		var hIdx = display.indexOf(_('h'));
		var mIdx = display.indexOf(_('m'));
		var temp;
		var entries = 0;
		if(hIdx != -1){
			temp = parseInt(display.substr(0,hIdx),10);
			display = display.substr(hIdx+1);
			entries += parseInt(temp * 60 / this.resolution,10);
		}
		if(mIdx != -1){
			temp = parseInt(display.substr(0,mIdx),10);
			entries += temp / this.resolution;
		}
		return entries;
	};
	//When initialized this function attaches the new entryConsole node to the page, and then initializes the table and text classes which attach themselves within the entryConsole.
	this.initialize = function(){

		this.weekEndingDate = ISOStringToDate(this.weekEndingDate);
		this.tableEntry.fillTimeArray();
		this.tableEntry.initialize();
		this.textEntry.initialize();
		this.selectedRateType = Rates.RATE_NORMAL;

		if(this.refid != -1)
		{
			//preparing data to send to database in order to receive saved data
			var request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			request += "<REQUEST OBJECT=\"timesheet\">\n";
			request += "\t<REFID>" + this.refid+ "</REFID>\n";
			request += "</REQUEST>\n";
			ajaxSendXMLtoPHP("parseResponse.php",request,this.updateContents);
		}
		else
		{
			if(!this.viewing)
				this.tableEntry.addEvents();
			window.setInterval(this.textEntry.cyclicSave,TimeSheet.activeInstance.textEntry.cycleTimeInterval);//every half a minute
	        //requesting colour change
			this.colourObject.nextColour();
		}
		window.addEventListener("resize",TimeSheet.activeInstance.textEntry.resizeKeyTable,false);
	};

	//this function takes data received from the database and updates the screen accordingly
	this.updateContents = function(XML)
	{
		if(XML == null)
			return;
		var structs = XML.getElementsByTagName("STRUCT");
		for(var i=0;i<structs.length;++i)
		{
			var invoiceCode = structs[i].getAttribute("INVOICECODE");
			var taskCode = structs[i].getAttribute("TASKCODE");
			var note = structs[i].getAttribute("NOTES");
			var colour = structs[i].getAttribute("COLOUR");
			var projectName = structs[i].getAttribute("PROJECTNAME");
			var childnodes = structs[i].getElementsByTagName("CHILDNODE");
	        //requesting colour change
			TimeSheet.activeInstance.colourObject.nextColour(colour);

			var key;
			var idx1=0;
			for(; idx1 < TimeSheet.activeInstance.tableEntry.keyArray.length; ++idx1)
				if(TimeSheet.activeInstance.tableEntry.keyArray[idx1].invoiceCode == invoiceCode &&
					TimeSheet.activeInstance.tableEntry.keyArray[idx1].taskCode == taskCode &&
					TimeSheet.activeInstance.tableEntry.keyArray[idx1].note == note)
				{
					key = TimeSheet.activeInstance.tableEntry.keyArray[idx1];
					break;
				}
			if(idx1 == TimeSheet.activeInstance.tableEntry.keyArray.length)
			{
				key = new Key(i,invoiceCode,projectName,taskCode,taskCode,colour,note);
				TimeSheet.activeInstance.entryLastId = i+1;
				TimeSheet.activeInstance.tableEntry.keyArray.push(key);
			}
			for(var j=0;j<childnodes.length;++j)
			{
				var rateType = childnodes[j].getAttribute("RATETYPE");
				var date = childnodes[j].getAttribute("DATE");
				if(TimeSheet.activeInstance.viewingMode == "sameaslastweek")
				{
					date = DateToString(addDays(StringToDate(date),7));
				}
				var entries = childnodes[j].getElementsByTagName("ENTRY");
				var entry,cell;
				for(var k=0;k<entries.length;++k)
				{
					entry = new Entry(entries[k].getAttribute("ID"),date,rateType);
					key.entries.push(entry);
					cell=document.getElementById(entry.id);
					if(cell==null)
						TimeSheet.activeInstance.errorDisplay.showError(_("Problem at: ")+entry.id,'x012',ErrorDisplay.FATAL);
					cell.style.backgroundColor = TimeSheet.activeInstance.colourObject.currentColour();
					cell.setAttribute("title",escapeHTML(invoiceCode+" - "+taskCode));
					cell.setAttribute("alt",escapeHTML(invoiceCode+" - "+taskCode));
					cell.setAttribute("entryId",key.entryId);
                    cell.setAttribute("data-cell-type", cell.textContent);
					cell.textContent = Rates.getRateText(entry.rateType);
				}
			}
		}
		for(i=0 ; i < TimeSheet.activeInstance.tableEntry.keyArray.length; ++i)
			TimeSheet.activeInstance.textEntry.addToKeys(TimeSheet.activeInstance.tableEntry.keyArray[i]);

		TimeSheet.activeInstance.textEntry.updateTotals();
		//requesting colour change
		TimeSheet.activeInstance.colourObject.nextColour(null);

		if(!TimeSheet.activeInstance.viewing)
			TimeSheet.activeInstance.tableEntry.addEvents();
		if(TimeSheet.activeInstance.viewingMode != "sameaslastweek")
			document.getElementById("saveButton").setAttribute("disabled", "disabled");
		window.setInterval(TimeSheet.activeInstance.textEntry.cyclicSave,30 * 1000);//every half a minute

		//checking if the time sheet is submitted
		if(TimeSheet.activeInstance.submitted){
			document.getElementById("proceedButton").setAttribute("disabled", "disabled");
		}
	};

	this.undoLastAction = function(evt) {//event listener
		if(evt !=null && evt.button != 0)
				return;

		if(TimeSheet.activeInstance.undoObject == null && TimeSheet.activeInstance.undoObject2 == null)
		{
			if(evt !=null) this.setAttribute("disabled","disabled");
			TimeSheet.activeInstance.errorDisplay.showError("both undoOs are null",'x011',ErrorDisplay.FATAL);
			return;
		}
		if(TimeSheet.activeInstance.undoObject != null)
		{
			switch(TimeSheet.activeInstance.undoObject.type)
			{
				case "removeNT":
					if(TimeSheet.activeInstance.undoObject.key != null)
					{
						TimeSheet.activeInstance.undoObject.key.entries.push(TimeSheet.activeInstance.undoObject.entry);
						var part1 = TimeSheet.activeInstance.tableEntry.keyArray.slice(0,TimeSheet.activeInstance.undoObject.keyIndex);
						var part2 = TimeSheet.activeInstance.tableEntry.keyArray.slice(TimeSheet.activeInstance.undoObject.keyIndex);
						part1.push(TimeSheet.activeInstance.undoObject.key);
						TimeSheet.activeInstance.tableEntry.keyArray = part1.concat(part2);
						entryId = TimeSheet.activeInstance.undoObject.key.entryId;
						invoiceCode = TimeSheet.activeInstance.undoObject.key.invoiceCode;
						taskCode = TimeSheet.activeInstance.undoObject.key.taskCode;
						colour = TimeSheet.activeInstance.undoObject.key.colour;
					}
					else
					{
						TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject.keyIndex].entries.push(TimeSheet.activeInstance.undoObject.entry);
						entryId = TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject.keyIndex].entryId;
						invoiceCode = TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject.keyIndex].invoiceCode;
						taskCode = TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject.keyIndex].taskCode;
						colour = TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject.keyIndex].colour;
					}
					var td = document.getElementById(TimeSheet.activeInstance.undoObject.entry.id);
					td.style.backgroundColor = colour;
					td.setAttribute("entryId",entryId);
					td.setAttribute("title",escapeHTML(invoiceCode+" - "+taskCode));
					td.setAttribute("alt",escapeHTML(invoiceCode+" - "+taskCode));
					td.textContent = Rates.getRateText(TimeSheet.activeInstance.undoObject.entry.rateType);
					td.setAttribute("data-class", td.textContent);
					//if deleting TimeSheet.activeInstance cell resulted in deleting a key
					if(TimeSheet.activeInstance.undoObject.keyRow != null)
					{
						var nextLine = document.getElementById("colourCode"+(parseInt(entryId,10)+1));
						if(nextLine!=null)
						{
							//nextLine is a td. Getting the row...
							var row = nextLine.parentNode;
							while(row!=null && row.tagName!="TR")
							{
								row = row.parentNode;
							}
							document.getElementById(TimeSheet.activeInstance.keyTableId).insertBefore(TimeSheet.activeInstance.undoObject.keyRow,row);
						}
						else
							document.getElementById(TimeSheet.activeInstance.keyTableId).appendChild(TimeSheet.activeInstance.undoObject.keyRow);
					}
					else
					{
						//updating the hours on the project's key
						var hoursTd = document.getElementById("hours"+entryId);
						var updatedHours = TimeSheet.activeInstance.displayTime(TimeSheet.activeInstance.getEntriesFromDisplay(hoursTd.textContent) + 1);
						hoursTd.textContent = updatedHours;
					}
					TimeSheet.activeInstance.textEntry.updateTotals();
					break;
				case "edit":
					//TimeSheet.activeInstance object will never be of type edit!
					break;
				case "remove":
					//putting the row back in the key table
					var nextLine = document.getElementById("colourCode"+(parseInt(TimeSheet.activeInstance.undoObject.key.entryId)+1));
					if(nextLine!=null)
					{
						//nextLine is a td. Getting the row...
						var row=nextLine.parentNode;
						while(row!=null && row.tagName!="TR")
						{
							row=row.parentNode;
						}
						document.getElementById(TimeSheet.activeInstance.keyTableId).insertBefore(TimeSheet.activeInstance.undoObject.keyRow,row);
					}
					else
						document.getElementById(TimeSheet.activeInstance.keyTableId).appendChild(TimeSheet.activeInstance.undoObject.keyRow);

					var editColour = TimeSheet.activeInstance.undoObject.key.colour;
					//putting the entries back
					var part1 = TimeSheet.activeInstance.tableEntry.keyArray.slice(0,TimeSheet.activeInstance.undoObject.keyIndex);
					var part2 = TimeSheet.activeInstance.tableEntry.keyArray.slice(TimeSheet.activeInstance.undoObject.keyIndex);
					part1.push(TimeSheet.activeInstance.undoObject.key);
					TimeSheet.activeInstance.tableEntry.keyArray = part1.concat(part2);
					for(var i=0;i<TimeSheet.activeInstance.undoObject.key.entries.length;++i)
					{
						var td=document.getElementById(TimeSheet.activeInstance.undoObject.key.entries[i].id);
						td.style.backgroundColor = editColour;
						td.setAttribute("title",escapeHTML(TimeSheet.activeInstance.undoObject.key.invoiceCode+" - "+TimeSheet.activeInstance.undoObject.key.taskCode));
						td.setAttribute("alt",escapeHTML(TimeSheet.activeInstance.undoObject.key.invoiceCode+" - "+TimeSheet.activeInstance.undoObject.key.taskCode));
						td.setAttribute("entryId",TimeSheet.activeInstance.undoObject.key.entryId);
						td.textContent = Rates.getRateText(TimeSheet.activeInstance.undoObject.key.entries[i].rateType);
						td.setAttribute("data-cell-type", td.textContent);
					}
					break;
			}
			TimeSheet.activeInstance.undoObject = null;
			TimeSheet.activeInstance.textEntry.updateTotals();
			if(evt !=null) this.setAttribute("disabled","disabled");
			return;
		}
		if(TimeSheet.activeInstance.undoObject2 != null)
		{
			//TimeSheet.activeInstance is always of type edit!
			//removing graphics elements from all the entries of the current key
			for(var i=0; i < TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject2.keyIndex].entries.length;++i)
			{
				var cell = document.getElementById(TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject2.keyIndex].entries[i].id);
				cell.style.backgroundColor = "";
				cell.removeAttribute("entryId");
				cell.removeAttribute("title");
				cell.removeAttribute("alt");
        cell.removeAttribute("data-cell-type");
				cell.textContent = "";
			}
			//restoring the key
			TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject2.keyIndex] = TimeSheet.activeInstance.undoObject2.key;
			//restoring the row in the key table
			var tdHours = document.getElementById("hours"+TimeSheet.activeInstance.undoObject2.key.entryId);
			var tdInvoice = document.getElementById("invoiceCode"+TimeSheet.activeInstance.undoObject2.key.entryId);
			var tdTask = document.getElementById("taskCode"+TimeSheet.activeInstance.undoObject2.key.entryId);
			var tdProjectName = document.getElementById("projectName"+TimeSheet.activeInstance.undoObject2.key.entryId);
			tdHours.textContent = TimeSheet.activeInstance.displayTime(TimeSheet.activeInstance.undoObject2.key.entries.length);
			tdInvoice.textContent = TimeSheet.activeInstance.undoObject2.key.invoiceCode;
			tdTask.textContent = TimeSheet.activeInstance.undoObject2.key.taskCode;
			tdProjectName.textContent = "";
			//squeezing the project name in a small width box
			inProjectNameValue = TimeSheet.activeInstance.undoObject2.key.projectName;
			tdProjectName.appendChild(document.createTextNode(inProjectNameValue));
			//restoring graphics elements to the entries that belong to the previous key
			for(var i=0; i < TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject2.keyIndex].entries.length;++i)
			{
				var cell = document.getElementById(TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject2.keyIndex].entries[i].id);
				cell.style.backgroundColor = TimeSheet.activeInstance.undoObject2.key.colour;
				cell.setAttribute("entryId",TimeSheet.activeInstance.undoObject2.key.entryId);
				cell.setAttribute("title",escapeHTML(TimeSheet.activeInstance.undoObject2.key.invoiceCode+" - "+TimeSheet.activeInstance.undoObject2.key.taskCode));
				cell.setAttribute("alt",escapeHTML(TimeSheet.activeInstance.undoObject2.key.invoiceCode+" - "+TimeSheet.activeInstance.undoObject2.key.taskCode));
        cell.setAttribute("data-cell-type", cell.textContent);
				cell.textContent = Rates.getRateText(TimeSheet.activeInstance.tableEntry.keyArray[TimeSheet.activeInstance.undoObject2.keyIndex].entries[i].rateType);

			}
		}
		TimeSheet.activeInstance.undoObject = null;
		TimeSheet.activeInstance.textEntry.updateTotals();
		if(evt!=null)
			this.setAttribute("disabled","disabled");
	};

	//This function removes the entryConsole from the document
	this.remove = function(){
		document.getElementById(this.attachPoint).removeChild(document.getElementById(this.identity));
	};
}
