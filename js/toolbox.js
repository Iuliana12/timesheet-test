//version should be updated everytime a change is done to a script or css file.
//The version is added to all JS files and css files and the browser will reload them
var app_version = 2.61;
//this function replaces all occurances of html special chars into html entities
function escapeHTML(string){
	if (!string || string == '') {
		return '';
	}
	if(typeof string != 'string'){
		string = string.toString();
	}
	return string.replace(/&/g,'&amp;').                                        
                replace(/>/g,'&gt;').                                           
                replace(/</g,'&lt;').                                           
                replace(/"/g,'&quot;'); 
}
function unescapeHTML(string){
	if (!string || string == '') {
		return '';
	}
	if(typeof string != 'string'){
		string = string.toString();
	}
	return string.replace(/&amp;/g,'&').                                        
                replace(/&gt;/g,'>').                                           
                replace(/&lt;/g,'<').                                           
                replace(/&quot;/g,'"'); 
}
//this function removes white spaces from both the begining and ending of a string
function trim(string)
{
	var posToDelete = 0;
	while(string[posToDelete] == " ")
		++posToDelete;
	var newString = string.substr(posToDelete);
	posToDelete = newString.length-1;
	while(newString[posToDelete] == " ")
		--posToDelete;
	return newString.substr(0,posToDelete+1);
}

//this function takes a Date object and returns a formated string
//format is DD/MM/YYYY
function DateToString(date)
{
	var day = date.getUTCDate();
	var month = date.getUTCMonth()+1;
	var year = date.getUTCFullYear();
	var ret = day < 10 ? "0"+day : day;
	ret += "/";
	ret += month < 10 ? "0"+month : month;
	ret += "/";
	ret += year;
	return ret;	
}
//this function takes a Date string and returns Date object
//format expected is DD/MM/YYYY
function StringToDate(date)
{
	var arr = date.split("/");
	if(arr.length < 3)
		return null;
	var day = parseInt(arr[0],10);
	var month = parseInt(arr[1],10)-1;
	var year = parseInt(arr[2],10);
	if (isNaN(day) || isNaN(month) || isNaN(year)) {
		return null;
	}
	if (year < 1000 || year > 9999) {
		return null;
	}
	if (month < 0 || month > 11) {
		return null;
	}
	if(day < 1 || day > 31) {
		return null;
	}
	return new Date(Date.UTC(year, month, day));	
}
//this function takes a Date object and returns a formated ISO string
//format is YYYY-MM-DD
function DateToISOString(date)
{
	var day = date.getUTCDate();
	var month = date.getUTCMonth()+1;
	var year = date.getUTCFullYear();
	var ret = year;
	ret += "-";
	ret += month < 10 ? "0"+month : month;
	ret += "-";
	ret += day < 10 ? "0"+day : day;
	return ret;	
}
//this function takes a ISO Date string and returns Date object
//format is YYYY-MM-DD
function ISOStringToDate(date)
{
	var arr = date.split("-");
	if(arr.length < 3){
		return null;
	}
	var year = parseInt(arr[0],10);
	var month = parseInt(arr[1],10)-1;
	var day = parseInt(arr[2],10);
	if (isNaN(day) || isNaN(month) || isNaN(year)) {
		return null;
	}
	if (year < 1000 || year > 9999) {
		return null;
	}
	if (month < 0 || month > 11) {
		return null;
	}
	if(day < 1 || day > 31) {
		return null;
	}
	return new Date(Date.UTC(year, month, day));	
}
//this function takes a Date object and returns the date with the specified number of days added (or subtracted)
function addDays(date,number)
{
	var milisecondsForOneDay = 1000*60*60*24;
	var newDate = new Date(date);
	newDate.setTime(newDate.getTime()+milisecondsForOneDay*number);
	return newDate;
}
function loadDependencies(dependency)
{
	//version should be updated everytime a change is done to a script or css file.
	//The version is added to all JS files and css files and the browser will reload them
	var version = 0;
	if(app_version) {
		version= app_version;
	}
	var head = document.getElementsByTagName('head')[0];
	var scripts = head.getElementsByTagName('script');
	var i, j, newjs;
	if(typeof dependency == 'string')
	{
		dependency += "?version="+version;
		//search if it's loaded already
		for(i=0; i < scripts.length; ++i){
			if(scripts[i].src.toLowerCase() == dependency.toLowerCase()){
				return;
			}
		}
		//if not, load it
		newjs = document.createElement('script');
		newjs.type = 'text/javascript';
		newjs.src = dependency;
		head.appendChild(newjs);
	}
	if(typeof dependency == 'object')//it must be an array
	{
		//search if it's loaded already
		for(i=0; i < dependency.length; ++i)
		{
			tempDependency = dependency[i]+"?version="+version;
			for(j=0; j < scripts.length; ++j){
				if(scripts[j].src.toLowerCase() == tempDependency.toLowerCase()){
					break;
				}
			}
			if(j < scripts.length) {//the script was already found in the list
				continue;
			}
			//if not, load it
			newjs = document.createElement('script');
			newjs.type = 'text/javascript';
			newjs.src = tempDependency;
			head.appendChild(newjs);
		}
	}
}
function getX(obj) {
	var obj = obj;
	var x = 0;
	if (obj.offsetParent) {
		x = obj.offsetLeft;
		while (obj = obj.offsetParent) {
			x += obj.offsetLeft;
		}
	}
	return x;
}
function getY(obj) {
	var obj = obj;
	var y = 0;
	if (obj.offsetParent) {
		y = obj.offsetTop;
		while (obj = obj.offsetParent) {
			y += obj.offsetTop;
		}
	}
	return y;
}
function sprintf(text){
	for(var i=1; i<arguments.length; ++i){
		text = text.replace('%s',arguments[i]);
	}
	return text;
}
