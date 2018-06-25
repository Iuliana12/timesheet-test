function TimeSheetTableEntry(){

	//TimeArray is used to hold the times used in the entry table, This allows the time resolution not to be hardcoded
	this.timeArray = new Array();
	//DateArray is used to hold the dates of the days involved in the entry table
	this.dateArray = new Array();
	//ActiveArray holds the identities of the table entries that are currently being selected but haven't been assigned to an invoice/task code
	this.activeArray = new Array();
	//keyArray holds the key objects corresponding to each table entry/invoice code group that has been entered
	this.keyArray = new Array();
	//This bool value is used when selecting entries so that the mouse over event only fires when this is set to true
	this.entryActive = false;
	this.selecting = true;//if it's false then the current action is deselecting
	this.identity = "inputTable";
	this.selectedEntries = 0;//this is the number of selected cells(entries) before entering details about them
	this.daysArray = new Array("Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday");
	this.daysArrayName = new Array(_("Saturday"),_("Sunday"),_("Monday"),_("Tuesday"),_("Wednesday"),_("Thursday"),_("Friday"));

	//This function creates the node to hold the entry table,
	this.initialize = function(){
		this.populateDateArray();
		this.createTableHTML();
	};
   //This function creates the html code for the entry table, using the array created from the fillTimeArray function
	this.createTableHTML = function(){

		var daysArrayStartPos = 0;
		if(TimeSheet.activeInstance.weekend != "true"){
			daysArrayStartPos = 2;
		}

		var table=document.getElementById(this.identity);
		if(!TimeSheet.activeInstance.viewing){
			table.addEventListener("mousemove",this.onMouseMove,false);
			table.addEventListener("mouseout",this.onMouseOut,false);
		}
		var tr=document.createElement("tr");
		var th=document.createElement("th");
		th.textContent="";
		th.setAttribute("class","firstTh");
		th.id="noevent-corner";
		var span = document.createElement("span");
		span.setAttribute("class","sidenote");
		span.textContent = _("select all remaining");
		th.appendChild(span);
		th.title = _("press here to select/deselect the entire table");
		tr.appendChild(th);
		if(TimeSheet.activeInstance.weekend != "true" && !TimeSheet.activeInstance.viewing)
		{
			th=document.createElement("th");
			th.id="noevent-addWE";
			th.title = _("press here to add weekend days to this timesheet");
			th.textContent = "add";
			var p=document.createElement("p");
			p.textContent = "WE";
			p.setAttribute("class","thDate");
			th.appendChild(p);
			tr.appendChild(th);
		}
		for(var i=daysArrayStartPos;i<TimeSheet.activeInstance.tableEntry.daysArray.length;++i)
		{
			th=document.createElement("th");
			th.id="noevent-col"+i;
			th.title = _("press here to select/deselect all the cells of this column");
			th.textContent = TimeSheet.activeInstance.tableEntry.daysArrayName[i];
			var p=document.createElement("p");
			var dateLength = TimeSheet.activeInstance.dateArray[i].length;
            var shortYear = TimeSheet.activeInstance.dateArray[i].substring(dateLength-4, dateLength).substr(2,2);
            var shortDate = TimeSheet.activeInstance.dateArray[i].substring(0, dateLength-4) + '' +shortYear;
			//p.textContent = TimeSheet.activeInstance.dateArray[i];
			p.textContent = shortDate;
			p.setAttribute("class","thDate");
			th.appendChild(p);
			tr.appendChild(th);
		}
		table.appendChild(tr);

		//adding the top extension row
		if(!TimeSheet.activeInstance.viewing && (this.timeArray[0].hours > 0 || (this.timeArray[0].hours == 0 && this.timeArray[0].minutes > 0)))
		{
			tr=document.createElement("tr");
			var td=document.createElement("td");
			td.id="noevent-topextension";
			td.title = _("press here to add another row on top of the others");
			td.setAttribute("class","firstTd");
			td.textContent= "+";
			td.addEventListener("click",TimeSheet.activeInstance.tableEntry.onTopExtension,false);

			tr.appendChild(td);
			if(TimeSheet.activeInstance.weekend != "true" && !TimeSheet.activeInstance.viewing)
			{
				td=document.createElement("td");
				td.id = "noevent-addWE";
				td.setAttribute("class","noevent");
				tr.appendChild(td);
			}
			for(var j = daysArrayStartPos;j<TimeSheet.activeInstance.tableEntry.daysArray.length;++j)
			{
				td=document.createElement("td");
				td.id = "noevent-top"+j;
				td.setAttribute("class","noevent");
				tr.appendChild(td);
			}
			table.appendChild(tr);
		}

		for(var i =0;i<this.timeArray.length-1;++i)
		{
			tr=document.createElement("tr");
			var td=document.createElement("td");
			td.id="noevent-row"+i;
			td.title = _("press here to select/deselect all the cells of this row");
			td.setAttribute("class","firstTd");
			td.textContent= this.timeArray[i];
			td.textContent += ' '+_("to")+' ';
			td.textContent += this.timeArray[i+1];

			tr.appendChild(td);

           	if(TimeSheet.activeInstance.weekend != "true" && !TimeSheet.activeInstance.viewing)
			{
				td=document.createElement("td");
				td.id = "noevent-addWE";
				td.setAttribute("class","noevent");
				tr.appendChild(td);
			}
			for(var j = daysArrayStartPos;j<TimeSheet.activeInstance.tableEntry.daysArray.length;++j)
			{
				td=document.createElement("td");
				td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.onCellOver,false);
				td.addEventListener("mouseout",TimeSheet.activeInstance.tableEntry.onCellOut,false);
				td.id=TimeSheet.activeInstance.tableEntry.daysArray[j]+"-"+this.timeArray[i].getHours()+"-"+this.timeArray[i].getMinutes();
				tr.appendChild(td);
			}
			table.appendChild(tr);
		}

		//adding the bottom extension row
		tr=document.createElement("tr");
		var td=document.createElement("td");
		td.id="noevent-bottomextension";
		if(this.timeArray[this.timeArray.length-1].hours <= 23 && this.timeArray[this.timeArray.length-1].hours !=0){
			if(!TimeSheet.activeInstance.viewing)
			{
				td.setAttribute("class","firstTd");
				td.title = _("press here to add another row at the bottom of the others");
				td.textContent= "+";
				td.addEventListener("click",TimeSheet.activeInstance.tableEntry.onBottomExtension,false);
			}
			else{
				td.setAttribute("class","noevent");
			}
		}
		else{
			td.appendChild(document.createTextNode(" "));
			td.setAttribute("class","noevent");
		}
		tr.appendChild(td);
		if(TimeSheet.activeInstance.weekend != "true" && !TimeSheet.activeInstance.viewing)
		{
			td=document.createElement("td");
			td.id = "noevent-addWE";
			td.setAttribute("class","noevent");
			tr.appendChild(td);
		}
		for(var j = daysArrayStartPos;j<TimeSheet.activeInstance.tableEntry.daysArray.length;++j)
		{
			td=document.createElement("td");
			td.id = "noevent-bottom"+j;
			td.setAttribute("class","noevent");
			td.appendChild(document.createTextNode("0"));
			tr.appendChild(td);
		}
		table.appendChild(tr);
		return 1;
	};

	//this positions the floater (which displays the selected time) next to the cursor
	this.onMouseMove = function(evt){
		var floater = document.getElementById("selectCounterFloater");
		if(floater ==null)
			return;
		floater.style.top = (evt.pageY+22)+"px";
		floater.style.left = evt.pageX+"px";
		if(TimeSheet.activeInstance.tableEntry.selectedEntries != 0){
			floater.style.display = "";
		}
	};
	//hides the floater when the mouse leaves the table area
	this.onMouseOut = function(){
		var floater = document.getElementById("selectCounterFloater");
		floater.style.display = "none";
	};
	//adjust the number of selected hours
	this.increaseSelectedEntries = function(step)	{
		var displayLocation = document.getElementById("selectCounterFloater");
		while(displayLocation.hasChildNodes()){
			displayLocation.removeChild(displayLocation.firstChild);
		}
		if(step == 0){
			TimeSheet.activeInstance.tableEntry.selectedEntries = 0;
			displayLocation.style.display = "none";
			return;
		}
		else{
			TimeSheet.activeInstance.tableEntry.selectedEntries += step;
		}
		if(TimeSheet.activeInstance.tableEntry.selectedEntries != 0)
//			displayLocation.appendChild(document.createTextNode(TimeSheet.activeInstance.tableEntry.selectedEntries+" h"));
			displayLocation.textContent = TimeSheet.activeInstance.displayTime(TimeSheet.activeInstance.tableEntry.selectedEntries);
		else
			displayLocation.style.display = "none";
	};
	this.onCellOver = function() {
		if(this.hasAttribute("entryId") && !TimeSheet.activeInstance.tableEntry.entryActive)
		{
			var entryId = this.getAttribute("entryId");
			var tdColour = document.getElementById("colourCode"+entryId);
			var tdHours = document.getElementById("hours"+entryId);
			var tdInvoice = document.getElementById("invoiceCode"+entryId);
			var tdProject = document.getElementById("projectName"+entryId);
			var tdTask = document.getElementById("taskCode"+entryId);
			var tdNote = document.getElementById("note"+entryId);

			if(tdColour!=null) tdColour.style.backgroundImage = "url('images/hash.gif')";
			if(tdHours!=null) tdHours.style.backgroundImage = "url('images/hash.gif')";
			if(tdInvoice!=null) tdInvoice.style.backgroundImage = "url('images/hash.gif')";
			if(tdProject!=null) tdProject.style.backgroundImage = "url('images/hash.gif')";
			if(tdTask!=null) tdTask.style.backgroundImage = "url('images/hash.gif')";
			if(tdNote!=null) tdTask.style.backgroundImage = "url('images/hash.gif')";
		}
		TimeSheet.activeInstance.tableEntry.highlightPosition(this);
	};
	this.onCellOut = function() {
		if(this.hasAttribute("entryId") && !TimeSheet.activeInstance.tableEntry.entryActive)
		{
			var entryId = this.getAttribute("entryId");
			var tdColour = document.getElementById("colourCode"+entryId);
			var tdHours = document.getElementById("hours"+entryId);
			var tdInvoice = document.getElementById("invoiceCode"+entryId);
			var tdProject = document.getElementById("projectName"+entryId);
			var tdTask = document.getElementById("taskCode"+entryId);
			var tdNote = document.getElementById("note"+entryId);

			if(tdColour!=null) tdColour.style.backgroundImage = "";
			if(tdHours!=null) tdHours.style.backgroundImage = "";
			if(tdInvoice!=null) tdInvoice.style.backgroundImage = "";
			if(tdProject!=null) tdProject.style.backgroundImage = "";
			if(tdTask!=null) tdTask.style.backgroundImage = "";
			if(tdNote!=null) tdTask.style.backgroundImage = "";
		}
		TimeSheet.activeInstance.tableEntry.unHighlightPosition(this);
	};
	this.highlightPosition = function(cell){
		cell.parentNode.childNodes[0].style.outline = '2px solid #17A';
	};
	this.unHighlightPosition = function(cell){
		cell.parentNode.childNodes[0].style.outline = null;
	};
	this.onTopExtension = function(){
		//verify that we still have hours left before the first one in the array
		if(TimeSheet.activeInstance.tableEntry.timeArray[0].hours == 0 && TimeSheet.activeInstance.tableEntry.timeArray[0].minutes == 0 || TimeSheet.activeInstance.textEntry.stopEvents)
			return;
		//determine the previous hour
		var minutesToSubstract = TimeSheet.activeInstance.resolution;
		var newTimeStruct = new timeStruct();
		var diff = parseInt(TimeSheet.activeInstance.tableEntry.timeArray[0].minutes,10) - minutesToSubstract;
		if(diff >= 0)
		{
			newTimeStruct.hours = TimeSheet.activeInstance.tableEntry.timeArray[0].hours;
			if(diff == 0)
				newTimeStruct.minutes = "00";
			else
				newTimeStruct.minutes = diff.toString();
		}
		else
		{
			var hDiff = parseInt(TimeSheet.activeInstance.tableEntry.timeArray[0].hours,10) - 1;
			newTimeStruct.hours = hDiff.toString();
			newTimeStruct.minutes = (60 - TimeSheet.activeInstance.resolution).toString();
		}
		TimeSheet.activeInstance.startTime = newTimeStruct.toString();
		TimeSheet.activeInstance.tableEntry.timeArray = ([newTimeStruct]).concat(TimeSheet.activeInstance.tableEntry.timeArray);
		//adding the new row to the table
		var daysArrayStartPos = 0;
		if(TimeSheet.activeInstance.weekend != "true")
			daysArrayStartPos = 2;
		tr = document.createElement("tr");
		var td = document.createElement("td");
		td.id="noevent-row";
		td.title = _("press here to select/deselect all the cells of this row");
		td.setAttribute("class","firstTd");
		td.textContent= TimeSheet.activeInstance.tableEntry.timeArray[0];
		td.textContent += ' '+_("to")+' ';
		td.textContent += TimeSheet.activeInstance.tableEntry.timeArray[1];
		td.addEventListener("mousedown",TimeSheet.activeInstance.tableEntry.RowEventDown,false);

		tr.appendChild(td);

		if(TimeSheet.activeInstance.weekend != "true")
		{
			td=document.createElement("td");
			td.id = "noevent-addWE";
			td.setAttribute("class","noevent");
			tr.appendChild(td);
		}

		for(var j = daysArrayStartPos;j<TimeSheet.activeInstance.tableEntry.daysArray.length;++j)
		{
			td = document.createElement("td");
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.onCellOver,false);
			td.addEventListener("mouseout",TimeSheet.activeInstance.tableEntry.onCellOut,false);
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.EventOver,false);
			td.addEventListener("mousedown",TimeSheet.activeInstance.tableEntry.EventDown,false);
			td.addEventListener("mouseup",TimeSheet.activeInstance.tableEntry.EventUp,false);
			td.id = TimeSheet.activeInstance.tableEntry.daysArray[j]+"-"+TimeSheet.activeInstance.tableEntry.timeArray[0].getHours()+"-"+TimeSheet.activeInstance.tableEntry.timeArray[0].getMinutes();
			tr.appendChild(td);
		}
		var table = document.getElementById("inputTable");
		var row = this.parentNode;
		while(row!=null && row.tagName!="TR")
		{
			row = row.parentNode;
		}
		var nextRow = row.nextSibling;
		while(nextRow!=null && nextRow.tagName!="TR")
		{
			nextRow = nextRow.nextSibling;
		}
		table.insertBefore(tr,nextRow);
	};
	this.onBottomExtension = function(){
		//verify that we still have hours left before the first one
		if(TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-1].hours == 0 ||TimeSheet.activeInstance.textEntry.stopEvents)
			return;
		//determine the next hour
		var minutesToAdd = TimeSheet.activeInstance.resolution;
		var newTimeStruct = new timeStruct;
		var result = parseInt(TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-1].minutes,10) + minutesToAdd;
		if(result < 60){
			newTimeStruct.hours = TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-1].hours;
			newTimeStruct.minutes = result.toString();
		}
		else{
			var hResult = parseInt(TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-1].hours,10) + 1;
			if(hResult > 23)
				hResult = 0;
			newTimeStruct.hours = hResult.toString();
			newTimeStruct.minutes = "00";
		}

		TimeSheet.activeInstance.stopTime = newTimeStruct.toString();
		TimeSheet.activeInstance.tableEntry.timeArray.push(newTimeStruct);
		//adding the new row to the table
		var daysArrayStartPos = 0;
		if(TimeSheet.activeInstance.weekend != "true")
			daysArrayStartPos = 2;
		tr = document.createElement("tr");
		var td = document.createElement("td");
		td.id="noevent-row";
		td.title = _("press here to select/deselect all the cells of this row");
		td.setAttribute("class","firstTd");
		td.textContent= TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-2];
		td.textContent += ' ' + _("to") + ' ';
		td.textContent += TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-1];
		td.addEventListener("mousedown",TimeSheet.activeInstance.tableEntry.RowEventDown,false);

		tr.appendChild(td);

		if(TimeSheet.activeInstance.weekend != "true")
		{
			td=document.createElement("td");
			td.id = "noevent-addWE";
			td.setAttribute("class","noevent");
			tr.appendChild(td);
		}

		for(var j = daysArrayStartPos;j<TimeSheet.activeInstance.tableEntry.daysArray.length;++j)
		{
			td = document.createElement("td");
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.onCellOver,false);
			td.addEventListener("mouseout",TimeSheet.activeInstance.tableEntry.onCellOut,false);
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.EventOver,false);
			td.addEventListener("mousedown",TimeSheet.activeInstance.tableEntry.EventDown,false);
			td.addEventListener("mouseup",TimeSheet.activeInstance.tableEntry.EventUp,false);
			td.id = TimeSheet.activeInstance.tableEntry.daysArray[j]+"-"+TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-2].getHours()+"-"+TimeSheet.activeInstance.tableEntry.timeArray[TimeSheet.activeInstance.tableEntry.timeArray.length-2].getMinutes();
			tr.appendChild(td);
		}
		var table = document.getElementById("inputTable");
		var row = this.parentNode;
		while(row!=null && row.tagName!="TR")
		{
			row = row.parentNode;
		}
		table.insertBefore(tr,row);
	};
//this function populates TimeSheet.activeInstance.dateArray with the exact dates for each day of the week
	this.populateDateArray = function(){

		var monthArray = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Nov','Dec'];
		var prevDay = new Date(Date.UTC(TimeSheet.activeInstance.weekEndingDate.getUTCFullYear(),TimeSheet.activeInstance.weekEndingDate.getUTCMonth(),TimeSheet.activeInstance.weekEndingDate.getUTCDate()));
		TimeSheet.activeInstance.dateArray = new Array(7);
		for(i=6;i>=0;--i) {
			TimeSheet.activeInstance.dateArray[i] = DateToString(prevDay);
			prevDay = addDays(prevDay,-1);
		}

	};
	//This function removes all the normal(standard time,not OT)eventlisteners from the table
	this.removeEvents = function(){

		var parentNode = document.getElementById(this.identity);
		var childNodes = parentNode.getElementsByTagName("td");
		for(var i = 0;i<childNodes.length;i++)
		{
			if(childNodes[i].id.indexOf("noevent") == -1)
			{
				childNodes[i].removeEventListener("mouseover",this.EventOver,false);
				childNodes[i].removeEventListener("mousedown",this.EventDown,false);
				childNodes[i].removeEventListener("mouseup",this.EventUp,false);
			}
			if(childNodes[i].id.indexOf("noevent-row") == 0)
				childNodes[i].removeEventListener("mousedown",this.RowEventDown,false);
		}
		childNodes = parentNode.getElementsByTagName("th");
		for(var i = 0;i<childNodes.length;i++)
		{
			if(childNodes[i].id.indexOf("noevent-addWE") != -1)
			{
				childNodes[i].removeEventListener("mouseup",this.addWE,false);
				continue;
			}
			if(childNodes[i].id.indexOf("noevent-col") == 0)
			{
				childNodes[i].removeEventListener("mousedown",this.ColEventDown,false);
			}
			if(childNodes[i].id.indexOf("noevent-corner") == 0)
				childNodes[i].removeEventListener("mousedown",this.CornerEventDown,false);
		}
	};

	//This function add's all the normal(standard time,not OT)eventlisteners to the table
	this.addEvents = function(){
 		document.addEventListener("mouseup",this.EventUp,false);
		var parentNode = document.getElementById(this.identity);
		var childNodes = parentNode.getElementsByTagName("td");
		for(var i = 0;i<childNodes.length;i++)
		{
			if(childNodes[i].id.indexOf("noevent") == -1)
			{
				childNodes[i].addEventListener("mouseover",this.EventOver,false);
				childNodes[i].addEventListener("mousedown",this.EventDown,false);
				childNodes[i].addEventListener("mouseup",this.EventUp,false);
			}
			if(childNodes[i].id.indexOf("noevent-row") == 0)
				childNodes[i].addEventListener("mousedown",this.RowEventDown,false);
		}
		childNodes = parentNode.getElementsByTagName("th");
		for(var i = 0;i<childNodes.length;++i)
		{
			if(childNodes[i].id.indexOf("noevent-addWE") != -1)
			{
				childNodes[i].addEventListener("mouseup",this.addWE,false);
				continue;
			}
			if(childNodes[i].id.indexOf("noevent-col") == 0)
			{
				childNodes[i].addEventListener("mousedown",this.ColEventDown,false);
				continue;
			}
			if(childNodes[i].id.indexOf("noevent-corner") == 0)
				childNodes[i].addEventListener("mousedown",this.CornerEventDown,false);
		}
	};

	//This function fills the time array with the times to be used in the table class using the resolution variable
	this.fillTimeArray = function(){
		var firstPos =0;
		var lastPos = 0;
		var hoursArray = new Array();
		for(i=0;i<24;++i)
		{
			if(i<10)
				var curTime="0"+i;
			else
				var curTime=i;
			hoursArray.push(curTime);
		}
		hoursArray.push("00");
		var startHourArr = TimeSheet.activeInstance.startTime.split(":");
		var startHour = startHourArr[0];
		var stopHourArr = TimeSheet.activeInstance.stopTime.split(":");
		var stopHour = stopHourArr[0];
		//searching for the position of the selected hours in the timeArray
		var found = false;
		while(!found && firstPos<hoursArray.length)
		{
			if(startHour == hoursArray[firstPos])
				found = true;
			else
				++firstPos;
		}
		lastPos = firstPos+1;
		found = false;
		while(!found && lastPos<hoursArray.length)
		{
			if(stopHour ==hoursArray[lastPos])
				found = true;
			else
				++lastPos;
		}
		var minutesArray = new Array();
		var bucket = 0;
		while (bucket < 60 ){
			if (bucket < 10) {
				minutesArray.push("0"+bucket);
			}
			else {
				minutesArray.push(bucket);
			}
			bucket += TimeSheet.activeInstance.resolution;
		}
		var go = false;
		for(var j = 0; j<minutesArray.length;++j)
		{
			if(startHourArr[1] == minutesArray[j])
				go = true;
			if(go)
				this.timeArray.push(new timeStruct(hoursArray[firstPos],minutesArray[j]));
		}
		for(var i = firstPos+1;i<lastPos;++i)
		{
			for(var j = 0; j<minutesArray.length;++j)
				this.timeArray.push(new timeStruct(hoursArray[i],minutesArray[j]));
		}
		go = true;
		for(var j = 0; j<minutesArray.length;++j)
		{
			if(go)
				this.timeArray.push(new timeStruct(hoursArray[lastPos],minutesArray[j]));
			if(stopHourArr[1] == minutesArray[j])
				go = false;
		}
	};
	//this function deletes the cells before Monday and inserts columns for Saturday and Sunday
	this.addWE = function(evt)//event listener
	{
	    if(evt.button != 0)
			return;

		//obtaining the table
		var table = this.parentNode;
		while(table!=null && table.tagName!="TABLE")
		{
			table = table.parentNode;
		}
		var rows = table.getElementsByTagName("tr");
		var ths = rows[0].getElementsByTagName("th");
		var posInRow = 1;
		for(var i=0;i<ths.length;++i)
			if(ths[i].id != null && ths[i].id == this.id)
				posInRow = i;

		//the TH
		thParent = rows[0];
		var ths = thParent.getElementsByTagName("th");
		nextChild = ths[posInRow+1];
		thParent.removeChild(ths[posInRow]);

		//Saturday
		th=document.createElement("th");
		th.id="noevent-col0";
		th.title = _("press here to select/deselect all the cells of this column");
		th.textContent = _("Saturday");
		var p=document.createElement("p");
		p.textContent = TimeSheet.activeInstance.dateArray[0];
		p.setAttribute("class","thDate");
		th.appendChild(p);
		thParent.insertBefore(th,nextChild);
		//Sunday
		th=document.createElement("th");
		th.id="noevent-col1";
		th.title = _("press here to select/deselect all the cells of this column");
		th.textContent = _("Sunday");
		var p=document.createElement("p");
		p.textContent = TimeSheet.activeInstance.dateArray[1];
		p.setAttribute("class","thDate");
		th.appendChild(p);
		thParent.insertBefore(th,nextChild);

		var lastPos = rows.length,firstPos = 1;
		if(document.getElementById("noevent-topextension")!=null)
		{
			firstPos++;
			var tds = rows[1].getElementsByTagName("td");
			nextChild = tds[posInRow+1];
			rows[1].removeChild(tds[posInRow]);

			td=document.createElement("td");
			td.id = "noevent-top0";
			td.setAttribute("class","noevent");
			rows[1].insertBefore(td,nextChild);

			td=document.createElement("td");
			td.id = "noevent-top1";
			td.setAttribute("class","noevent");
			rows[1].insertBefore(td,nextChild);
		}
		if(document.getElementById("noevent-bottomextension")!=null)
		{
			lastPos--;
			var tds = rows[lastPos].getElementsByTagName("td");
			nextChild = tds[posInRow+1];
			rows[lastPos].removeChild(tds[posInRow]);

			td=document.createElement("td");
			td.id = "noevent-bottom0";
			td.setAttribute("class","noevent");
			td.appendChild(document.createTextNode("0"));
			rows[lastPos].insertBefore(td,nextChild);

			td=document.createElement("td");
			td.id = "noevent-bottom1";
			td.setAttribute("class","noevent");
			td.appendChild(document.createTextNode("0"));
			rows[lastPos].insertBefore(td,nextChild);
		}

		for(var i=firstPos;i<lastPos;++i)
		{
			var tds = rows[i].getElementsByTagName("td");
			tdParent = rows[i];
			nextChild = tds[posInRow+1];
			tdParent.removeChild(tds[posInRow]);

			td=document.createElement("td");
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.onCellOver,false);
			td.addEventListener("mouseout",TimeSheet.activeInstance.tableEntry.onCellOut,false);
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.EventOver,false);
			td.addEventListener("mousedown",TimeSheet.activeInstance.tableEntry.EventDown,false);
			td.addEventListener("mouseup",TimeSheet.activeInstance.tableEntry.EventUp,false);
			td.id="Saturday-"+TimeSheet.activeInstance.tableEntry.timeArray[i-firstPos].getHours()+"-"+TimeSheet.activeInstance.tableEntry.timeArray[i-firstPos].getMinutes();
			tdParent.insertBefore(td,nextChild);

			td=document.createElement("td");
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.onCellOver,false);
			td.addEventListener("mouseout",TimeSheet.activeInstance.tableEntry.onCellOut,false);
			td.addEventListener("mouseover",TimeSheet.activeInstance.tableEntry.EventOver,false);
			td.addEventListener("mousedown",TimeSheet.activeInstance.tableEntry.EventDown,false);
			td.addEventListener("mouseup",TimeSheet.activeInstance.tableEntry.EventUp,false);
			td.id="Sunday-"+TimeSheet.activeInstance.tableEntry.timeArray[i-firstPos].getHours()+"-"+TimeSheet.activeInstance.tableEntry.timeArray[i-firstPos].getMinutes();
			tdParent.insertBefore(td,nextChild);
		}

		TimeSheet.activeInstance.weekend = "true";
		TimeSheet.activeInstance.tableEntry.addEvents();
	};

	//here's what happens when the user presses the left mouse button
	this.EventDown = function(evt){//event listener

		if(evt.button != 0)
			return;

		document.getElementById("undoButton").setAttribute("disabled","disabled");
		TimeSheet.activeInstance.undoObject = null;
		TimeSheet.activeInstance.tableEntry.entryActive = true;//this initializes the drag procedure
		//if it's not already selected and not part of a key
		if(!TimeSheet.activeInstance.tableEntry.isActive(this.id) && TimeSheet.activeInstance.tableEntry.getEntry(this.id)==null)
		{
			TimeSheet.activeInstance.tableEntry.selecting = true;
			var date = TimeSheet.activeInstance.tableEntry.getObjectDate(this.id);
			var tempEntry = new Entry(this.id,date,TimeSheet.activeInstance.selectedRateType);
			TimeSheet.activeInstance.tableEntry.activeArray.push(tempEntry);
			document.getElementById("entryButton").disabled = "disabled";
			TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(1);
			this.textContent = Rates.getRateText(TimeSheet.activeInstance.selectedRateType);
			this.setAttribute("data-cell-type", this.textContent);
			if(TimeSheet.activeInstance.editId!=-1)
			{
				this.style.backgroundColor = TimeSheet.activeInstance.editColour;
			}
			else
			{
				this.style.backgroundColor = TimeSheet.activeInstance.colourObject.currentColour();
				document.getElementById("entryButton").removeAttribute("disabled");
			}
		}
		else//if it has already been selected
		{
			TimeSheet.activeInstance.tableEntry.selecting = false;
			var entryId = this.getAttribute("entryId");
			var entry = TimeSheet.activeInstance.tableEntry.getEntry(this.id);
			//if in edit mode but not in active array and the selected time type is different than the type of the current entry
			if(entry!=null && TimeSheet.activeInstance.editId != -1 && TimeSheet.activeInstance.editId == parseInt(entryId) && !TimeSheet.activeInstance.tableEntry.isActive(this.id) && entry.rateType != TimeSheet.activeInstance.selectedRateType)
			{
				var date = TimeSheet.activeInstance.tableEntry.getObjectDate(this.id);
				entry.rateType = TimeSheet.activeInstance.selectedRateType;
				this.textContent = Rates.getRateText(TimeSheet.activeInstance.selectedRateType);
				this.setAttribute("data-cell-type", this.textContent);
				TimeSheet.activeInstance.textEntry.updateTotals();
				return;
			}
			//if we're not in edit mode, ignore this command
			if(!(TimeSheet.activeInstance.editId != -1 && TimeSheet.activeInstance.editId == parseInt(entryId)) && !TimeSheet.activeInstance.tableEntry.isActive(this.id) )
				return;
			else
			//in this case a key was selected and it needs deselecting because this cell doesn't belong to it anymore
			{
				var tdColour = document.getElementById("colourCode"+entryId);
				var tdHours = document.getElementById("hours"+entryId);
				var tdProject = document.getElementById("projectName"+entryId);
				var tdInvoice = document.getElementById("invoiceCode"+entryId);
				var tdTask = document.getElementById("taskCode"+entryId);
				var tdNote = document.getElementById("note"+entryId);
				if(tdColour!=null) tdColour.style.backgroundImage = "";
				if(tdHours!=null) tdHours.style.backgroundImage = "";
				if(tdProject!=null) tdProject.style.backgroundImage = "";
				if(tdInvoice!=null) tdInvoice.style.backgroundImage = "";
				if(tdTask!=null) tdTask.style.backgroundImage = "";
				if(tdNote!=null) tdTask.style.backgroundImage = "";
			}
			//if changes to the array were made
			if(TimeSheet.activeInstance.tableEntry.removeEntry(this.id))
				TimeSheet.activeInstance.textEntry.updateTotals();
			if(TimeSheet.activeInstance.tableEntry.activeArray.length <1)
				document.getElementById("entryButton").disabled = "disabled";

			this.style.backgroundColor = "";
			this.removeAttribute("alt");
			this.removeAttribute("title");
			this.removeAttribute("entryId");
            this.removeAttribute("data-cell-type");
			this.textContent = "";
		}
	};
	this.EventUp = function(evt){//event listener

		if(evt.button != 0)
			return;
		TimeSheet.activeInstance.tableEntry.entryActive = false;
		TimeSheet.activeInstance.tableEntry.selecting = true;
	};

	this.EventOver = function(){//event listener

		if(!TimeSheet.activeInstance.tableEntry.entryActive)
			return;
		if(TimeSheet.activeInstance.tableEntry.selecting && !TimeSheet.activeInstance.tableEntry.isActive(this.id) && TimeSheet.activeInstance.tableEntry.getEntry(this.id) == null)
		{
			if(TimeSheet.activeInstance.editId!=-1)
				this.style.backgroundColor = TimeSheet.activeInstance.editColour;
			else
				this.style.backgroundColor = TimeSheet.activeInstance.colourObject.currentColour();
			this.textContent = Rates.getRateText(TimeSheet.activeInstance.selectedRateType);
			this.setAttribute("data-cell-type", this.textContent);
			var date = TimeSheet.activeInstance.tableEntry.getObjectDate(this.id);
			var tempEntry = new Entry(this.id,date,TimeSheet.activeInstance.selectedRateType);
			TimeSheet.activeInstance.tableEntry.activeArray.push(tempEntry);
			TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(1);
		}
		else if(!TimeSheet.activeInstance.tableEntry.selecting)
		{
			var isActive = TimeSheet.activeInstance.tableEntry.isActive(this.id);
			var entry = TimeSheet.activeInstance.tableEntry.getEntry(this.id);
			var isInKeyArray = (entry != null);
			var entryId = this.getAttribute("entryId");
			//if in edit mode but not in active array and the selected time type is different than the type of the current entry
			if(entry!=null && TimeSheet.activeInstance.editId != -1 && TimeSheet.activeInstance.editId == parseInt(entryId) && !TimeSheet.activeInstance.tableEntry.isActive(this.id) && entry.rateType != TimeSheet.activeInstance.selectedRateType)
			{
				var date = TimeSheet.activeInstance.tableEntry.getObjectDate(this.id);
				entry.rateType = TimeSheet.activeInstance.selectedRateType;
				this.textContent = Rates.getRateText(TimeSheet.activeInstance.selectedRateType);
				this.setAttribute("data-cell-type", this.textContent);
				TimeSheet.activeInstance.textEntry.updateTotals();
				return;
			}
			//ignore this command if we're not in edit mode and this entry is not active
			//or if we are in edit mode, but this entry does not belong to the key being edited
			if( (TimeSheet.activeInstance.editId == -1 && !isActive) || (TimeSheet.activeInstance.editId != -1 && TimeSheet.activeInstance.editId != parseInt(entryId) && !isActive))
				return;
			if(!isActive)
			//in this case a key was selected and it needs deselecting because this cell doesn't belong to it anymore
			{
				var tdColour = document.getElementById("colourCode"+entryId);
				var tdHours = document.getElementById("hours"+entryId);
				var tdProject = document.getElementById("projectName"+entryId);
				var tdInvoice = document.getElementById("invoiceCode"+entryId);
				var tdTask = document.getElementById("taskCode"+entryId);
				var tdNote = document.getElementById("note"+entryId);
				if(tdColour!=null) tdColour.style.backgroundImage = "";
				if(tdHours!=null) tdHours.style.backgroundImage = "";
				if(tdProject!=null) tdProject.style.backgroundImage = "";
				if(tdInvoice!=null) tdInvoice.style.backgroundImage = "";
				if(tdTask!=null) tdTask.style.backgroundImage = "";
				if(tdNote!=null) tdTask.style.backgroundImage = "";
			}
			//if changes to the array were made
			if(TimeSheet.activeInstance.tableEntry.removeEntry(this.id))
				TimeSheet.activeInstance.textEntry.updateTotals();
			if(TimeSheet.activeInstance.tableEntry.activeArray.length <1)
				document.getElementById("entryButton").disabled = "disabled";

			this.style.backgroundColor = "";
			this.removeAttribute("alt");
			this.removeAttribute("title");
			this.removeAttribute("entryId");
            this.removeAttribute("data-cell-type");
			this.textContent = "";
		}
	};
	this.RowEventDown = function (evt) {//event listener

		if(evt.button != 0)
			return;

		document.getElementById("undoButton").setAttribute("disabled","disabled");
		TimeSheet.activeInstance.undoObject = null;
		//obtaining the row
		var row=this.parentNode;
		while(row!=null && row.tagName!="TR")
		{
			row=row.parentNode;
		}
		var tds = row.getElementsByTagName("td");

		//click number based decision
		if(this.clicked == "clicked")
		{
			//gathering the tds in range
			for(var i=0;i<tds.length;++i)
			{
				for(var j=0;j<TimeSheet.activeInstance.tableEntry.activeArray.length;++j)
				{
					if(TimeSheet.activeInstance.tableEntry.activeArray[j].id == tds[i].id)
					{
						var td=document.getElementById(TimeSheet.activeInstance.tableEntry.activeArray[j].id);
						td.style.backgroundColor = "";
						td.textContent = "";
						TimeSheet.activeInstance.tableEntry.activeArray.splice(j,1);
						TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(-1);
						--j;
					}
				}
			}

			this.clicked = "";
			this.style.backgroundColor = "";
		}
		else
		{
			this.clicked = "clicked";
			this.style.backgroundColor = "rgb(210,210,210)";

			TimeSheet.activeInstance.tableEntry.entryActive = true;
			var evt = document.createEvent("MouseEvents");
			for(var i=0;i<tds.length;++i){
				if(tds[i].id.lastIndexOf('noevent') != -1) {
					continue;
				}
				evt.initMouseEvent("mouseover", false, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, null);
				tds[i].dispatchEvent(evt);
				evt.initMouseEvent("mouseout", false, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, null);
				tds[i].dispatchEvent(evt);
			}
		}
		if(TimeSheet.activeInstance.tableEntry.activeArray.length==0)
			document.getElementById("entryButton").setAttribute("disabled","disabled");
		else
			document.getElementById("entryButton").removeAttribute("disabled");
		TimeSheet.activeInstance.tableEntry.entryActive = false;
	};
	//this function clears the clicked rows or columns after clicking on the corner cell
	this.clearClicked = function() {

		var th = document.getElementById("noevent-corner");
		if(th.hasAttribute("clicked"))
		{
			th.removeAttribute("clicked");
			th.style.backgroundColor = "";
		}
		var counter = 0;
		var th = document.getElementById("noevent-col"+counter);
		while( th !=null)
		{
			if(th.hasAttribute("clicked"))
			{
				th.removeAttribute("clicked");
				th.style.backgroundColor = "";
			}
			++counter;
			th = document.getElementById("noevent-col"+counter);
		}
		counter = 0;
		var td = document.getElementById("noevent-row"+counter);
		while( td !=null)
		{
			if(td.hasAttribute("clicked"))
			{
				td.removeAttribute("clicked");
				td.style.backgroundColor = "";
			}
			++counter;
			td = document.getElementById("noevent-row"+counter);
		}
	};

	this.ColEventDown = function (evt) {//event listener

		if(evt.button != 0)
			return;
		document.getElementById("undoButton").setAttribute("disabled","disabled");
		TimeSheet.activeInstance.undoObject = null;
		//obtaining the table
		var table=this.parentNode;
		while(table!=null && table.tagName!="TABLE")
		{
			table=table.parentNode;
		}
		var rows = table.getElementsByTagName("tr");
		var ths = rows[0].getElementsByTagName("th");
		var posInRow = 1;
		for(var i=0;i<ths.length;++i)
			if(ths[i].id != null && ths[i].id == this.id)
				posInRow = i;

		//click number based decision
		if(this.clicked == "clicked")
		{
			for(var i=1;i<rows.length;++i)
			{
				var tds = rows[i].getElementsByTagName("td");
				for(var j=0;j<TimeSheet.activeInstance.tableEntry.activeArray.length;++j)
				{
					if(TimeSheet.activeInstance.tableEntry.activeArray[j].id == tds[posInRow].id)
					{
						var td=document.getElementById(TimeSheet.activeInstance.tableEntry.activeArray[j].id);
						td.style.backgroundColor = "";
						td.textContent="";
						TimeSheet.activeInstance.tableEntry.activeArray.splice(j,1);
						TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(-1);
						--j;
					}
				}
			}
			this.clicked = "";
			this.style.backgroundColor = "";
			if(TimeSheet.activeInstance.tableEntry.activeArray.length==0)
				document.getElementById("entryButton").setAttribute("disabled","disabled");
		}
		else
		{
			this.clicked = "clicked";
			this.style.backgroundColor = "rgb(210,210,210)";

			TimeSheet.activeInstance.tableEntry.entryActive = true;
			var evt = document.createEvent("MouseEvents");
			for(var i=0;i<rows.length;++i){
				var tds = rows[i].getElementsByTagName("td");
				if(!tds[posInRow] || tds[posInRow].id.lastIndexOf('noevent') != -1) {
					continue;
				}
				evt.initMouseEvent("mouseover", false, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, null);
				tds[posInRow].dispatchEvent(evt);
				evt.initMouseEvent("mouseout", false, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, null);
				tds[posInRow].dispatchEvent(evt);
			}
		}

		TimeSheet.activeInstance.tableEntry.entryActive = false;
		if(TimeSheet.activeInstance.tableEntry.activeArray.length!=0)
			document.getElementById("entryButton").removeAttribute("disabled");
	};

	this.CornerEventDown = function (evt) {//event listener

			if(evt.button != 0)
				return;
		document.getElementById("undoButton").setAttribute("disabled","disabled");
		TimeSheet.activeInstance.undoObject = null;
		//obtaining the table
		var table=this.parentNode;
		while(table!=null && table.tagName!="TABLE")
		{
			table=table.parentNode;
		}
		var rows = table.getElementsByTagName("tr");
		//click number based decision
		if(this.hasAttribute("clicked") && this.getAttribute("clicked") == "clicked")
		{
			TimeSheet.activeInstance.tableEntry.emptyActiveArray();
			TimeSheet.activeInstance.tableEntry.clearClicked();

			this.removeAttribute("clicked");
			var span = document.createElement("span");
			span.setAttribute("class","sidenote");
			span.textContent = _("select all remaining");
			this.removeChild(this.firstChild);
			this.appendChild(span);
			this.style.backgroundColor = "";
		}
		else
		{
			this.setAttribute("clicked","clicked");
			var span = document.createElement("span");
			span.setAttribute("class","sidenote");
			span.textContent = _("deselect");
			this.removeChild(this.firstChild);
			this.appendChild(span);
			this.style.backgroundColor = "rgb(210,210,210)";

			TimeSheet.activeInstance.tableEntry.entryActive = true;
			var evt = document.createEvent("MouseEvents");
			evt.initMouseEvent("mouseover", false, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, null);
			for(var i=1;i<rows.length;++i)
			{
				var tds = rows[i].getElementsByTagName("td");
				for(var j=1;j<tds.length;++j)
				{
					tds[j].dispatchEvent(evt);
					evt.initMouseEvent("mouseout", false, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, tds[j-1]);
					tds[j].dispatchEvent(evt);
					evt.initMouseEvent("mouseover", false, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, tds[j-1]);
				}
			}
			//setting the status to clicked for every th and every first TD
			var counter = 0;
			var th = document.getElementById("noevent-col"+counter);
			while( th !=null)
			{
				th.setAttribute("clicked","clicked");
				++counter;
				th = document.getElementById("noevent-col"+counter);
			}
			counter = 0;
			var td = document.getElementById("noevent-row"+counter);
			while( td !=null)
			{
				td.setAttribute("clicked","clicked");
				++counter;
				td = document.getElementById("noevent-row"+counter);
			}
		}

		TimeSheet.activeInstance.tableEntry.entryActive = false;
		if(TimeSheet.activeInstance.tableEntry.activeArray.length!=0)
			document.getElementById("entryButton").removeAttribute("disabled");
	};

	//delete the active array and restores any graphics accordingly
	this.emptyActiveArray = function() {

		while(this.activeArray.length >0)
		{
			TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(-1);
			var entry=this.activeArray.pop();
		    var td=document.getElementById(entry.id);
			td.style.backgroundColor = "";
			td.textContent="";
		}
		this.activeArray = new Array();
		document.getElementById("entryButton").setAttribute("disabled","disabled");
	};

//this function returns true if it finds the given entry id in the keyArray
	this.getEntry = function(idString){

		for(var i=0;i < TimeSheet.activeInstance.tableEntry.keyArray.length; ++i)
			for(var j=0;j < TimeSheet.activeInstance.tableEntry.keyArray[i].entries.length; ++j)
			{
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id == idString )
					return TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j];
			}
		return null;
	};
//this function returns true if it finds the struct with the given id in TimeSheet.activeInstance.tableEntry.activeArray
	this.isActive = function(idString)
	{
		for(var i=0;i<TimeSheet.activeInstance.tableEntry.activeArray.length;++i)
		{
			if(TimeSheet.activeInstance.tableEntry.activeArray[i].id == idString)
				return true;
		}
		return false;
	};

//this function returns the date according to the given id and the dateArray array.
	this.getObjectDate = function(idString){

		var returnVal = null;
		var idArray = idString.split("-",3);;
		switch(idArray[0]){

			case'Saturday'	:	returnVal = TimeSheet.activeInstance.dateArray[0];break;
			case'Sunday'	:	returnVal = TimeSheet.activeInstance.dateArray[1];break;
			case'Monday'	:	returnVal = TimeSheet.activeInstance.dateArray[2];break;
			case'Tuesday'	:	returnVal = TimeSheet.activeInstance.dateArray[3];break;
			case'Wednesday'	:	returnVal = TimeSheet.activeInstance.dateArray[4];break;
			case'Thursday'	:	returnVal = TimeSheet.activeInstance.dateArray[5];break;
			case'Friday'	:	returnVal = TimeSheet.activeInstance.dateArray[6];break;
			default:break;
		}
		return returnVal;
	};

//this function removes the entry with the given id from whereever it's found.
//returns true if it's found in the keyArray
	this.removeEntry = function(idString){

		var i = TimeSheet.activeInstance.tableEntry.activeArray.length-1;
		//look for it first in the activeArray
		while(i>=0)
		{
			if(TimeSheet.activeInstance.tableEntry.activeArray[i].id == idString)
			{
				TimeSheet.activeInstance.tableEntry.activeArray.splice(i,1);
				TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(-1);
				//if this action just deleted the last entry in the active array while in edit mode
				if(TimeSheet.activeInstance.editId !=-1 && TimeSheet.activeInstance.tableEntry.activeArray == 0)
				{
					//looking for the key with this entryId
					var k=0;
					for(; k<TimeSheet.activeInstance.tableEntry.keyArray.length; ++k)
						if(TimeSheet.activeInstance.tableEntry.keyArray[k].entryId == TimeSheet.activeInstance.editId)
							break;//found => no need to remove the key
					//if there were no other structs found with the same entryId
					if(k == TimeSheet.activeInstance.tableEntry.keyArray.length)
					{
						//obtaining the row that needs to be removed from the keyTable
						var row = document.getElementById("invoiceCode"+TimeSheet.activeInstance.editId);
						while(row!=null && row.tagName!="TR")
						{
							row=row.parentNode;
						}
						document.getElementById(TimeSheet.activeInstance.keyTableId).removeChild(row);
						TimeSheet.activeInstance.editId = -1;
						TimeSheet.activeInstance.editColour = "";
					}
				}
				return false;
			}
			i--;
		}
        //look for this entry in the keyArray
		i = TimeSheet.activeInstance.tableEntry.keyArray.length-1;
		var j = 0;
		while(i>=0)
		{
			j = TimeSheet.activeInstance.tableEntry.keyArray[i].entries.length-1;
			while(j>=0)
			{
				if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j].id == idString)
				{
					TimeSheet.activeInstance.undoObject = new undoClass("removeNT",new Array(i,TimeSheet.activeInstance.tableEntry.keyArray[i].entries[j]));
					//make the undo button visible
					document.getElementById("undoButton").removeAttribute("disabled");

					TimeSheet.activeInstance.tableEntry.keyArray[i].entries.splice(j,1);
					TimeSheet.activeInstance.tableEntry.increaseSelectedEntries(-1);
					var entryId = TimeSheet.activeInstance.tableEntry.keyArray[i].entryId;

					if(TimeSheet.activeInstance.tableEntry.keyArray[i].entries.length ==0)
					{
						//there is no need to remove the key if there are entries in the active array ready to be added
						if(TimeSheet.activeInstance.tableEntry.activeArray.length == 0)
						{
							TimeSheet.activeInstance.textEntry.cancelEdit();
							//obtaining the row that needs to be removed from the keyTable
							var row=document.getElementById("invoiceCode"+entryId);
							while(row!=null && row.tagName!="TR")
							{
								row = row.parentNode;
							}
							if(TimeSheet.activeInstance.undoObject !=null)
								TimeSheet.activeInstance.undoObject.keyRow = row;
							document.getElementById(TimeSheet.activeInstance.keyTableId).removeChild(row);
							TimeSheet.activeInstance.editId=-1;
							TimeSheet.activeInstance.editColour="";
						}
						if(TimeSheet.activeInstance.undoObject !=null)
							TimeSheet.activeInstance.undoObject.key = TimeSheet.activeInstance.tableEntry.keyArray[i];
						TimeSheet.activeInstance.tableEntry.keyArray.splice(i,1);
					}
					else
					{
						//updating the hours on the project's key
						var hoursTd = document.getElementById("hours"+entryId);
						var updatedEntries = TimeSheet.activeInstance.getEntriesFromDisplay(hoursTd.textContent) - 1;
						hoursTd.textContent = TimeSheet.activeInstance.displayTime(updatedEntries);
					}
					return true;
				}
				j--;
			}
			i--;
		}
	};
}
