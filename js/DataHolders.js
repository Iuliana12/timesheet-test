//This holds the times created by the entryConsole class and used by the tableEntryClass
function timeStruct(hours,minutes){

	this.hours = hours;
	this.minutes = minutes;
	this.toString = function()
	{
		var ret= this.hours.length<2 ? "0"+this.hours : this.hours;
		ret += ":";
		ret += this.minutes.length<2 ? "0"+this.minutes : this.minutes;
		return ret;
	}
	this.getHours = function()
	{
		var ret= this.hours.length<2 ? "0"+this.hours : this.hours;
		return ret;
	}
	this.getMinutes = function()
	{
		var ret = this.minutes.length<2 ? "0"+this.minutes : this.minutes;
		return ret;
	}
}

//This holds data about the last action to be undone
function undoClass(type,data){
	this.type = type;//type of action: remove ot entry, remove nt entry, edit key, remove key
	this.data = data;//an array with data to be interpreted depending on type of action
	switch(type)
	{
		case "removeNT":
			this.keyIndex = data[0];//the index of the key from which the entry was removed
			this.entry = data[1];//the entry removed with all information -- no cloning
			this.keyRow = null;//the row in the key table in case a key was removed
			this.key = null; //if an entire key is being removed -- no cloning
			break;
		case "edit":
	  		this.keyIndex = data[0];//location of the key
			this.key = data[1];//the key with all the previous information -- needs cloning
			break;
		case "remove":
	  		this.keyIndex = data[0];//location of the key before removal
			this.key = data[1];//the key with all its information -- no cloning
			this.keyRow = data[2];//the row in the key table

			break;
	}
}

function serializableParentStruct(){

	this.invoiceCode = null;
	this.taskCode = null;
	this.taskName = null;
	this.colour = null;
	this.note = null;
	this.childNodes = new Array();
}

function serializableChildStruct(){

	this.date = null;
	this.rateType = null;
	this.entries = new Array();
}

function Key(entryId,invoiceCode,projectName,taskCode,taskName,colour, note)
{
	this.entryId = entryId;
	this.invoiceCode = invoiceCode;
	this.projectName = projectName;
	this.taskCode = taskCode;
	this.taskName = taskName;
	this.colour = colour;
	this.note = note;
	this.entries = new Array();

}
function Entry(id,date,rateType)
{
	this.id = id;
	this.rateType = parseInt(rateType,10);
	this.date = date;
}
Rates = {};
//this function returns the same rateType recieved as parameter except when it is Rates.RATE_OT_CHARGED and the date falls on a Sunday
Rates.getRateType = function(rateType,date) {
	if (rateType != Rates.RATE_OT_CHARGED) {
		return rateType;
	}

	var date = StringToDate(date);
	if(date.getUTCDay() == 0 ) {
		return Rates.RATE_OT_CHARGED_SUNDAY;
	}
	else {
		return rateType;
	}
}
Rates.getRateText = function(rateType) {
	switch (rateType) {
		case Rates.RATE_NORMAL:
			return "";
		case Rates.RATE_OT_LIEU:
			return "lieu";
		case Rates.RATE_OT_CHARGED:
			return "charged";
		case Rates.RATE_OT_CHARGED_SUNDAY:
			return "charged";
		default:
   			return "";
	}
}
Rates.RATE_NORMAL = 0;
Rates.RATE_OT_LIEU = 1;
Rates.RATE_OT_CHARGED = 2;
Rates.RATE_OT_CHARGED_SUNDAY = 3;
//NOTE: don't forget to change the submissionStatus, archive, toil_adjust and parseResponse when the rate type digit is changed
