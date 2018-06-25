function prevWeek() {
	var dateInput = document.getElementById('friday');
	var currentFriday = ISOStringToDate(dateInput.value);
	var selectedFriday = addDays(currentFriday, -7);
	dateInput.value = DateToISOString(selectedFriday);
	updateWeek();
}
function nextWeek() {
	var dateInput = document.getElementById('friday');
	var currentFriday = ISOStringToDate(dateInput.value);
	var selectedFriday = addDays(currentFriday, 7);
	var milisecondsForOneDay = 1000*60*60*24;
	var allowedWeeksAhead = 6;
	if ( (selectedFriday.getTime() - Date.now()) / milisecondsForOneDay / 7  > allowedWeeksAhead) {
		var status = document.getElementById("status");
		if(status) status.textContent= _("Sorry, you can not add time sheets for that week at this time");
		return;
	}
	dateInput.value = DateToISOString(selectedFriday);
	updateWeek();
}
function updateWeek(){
	var monthArray = [_('Jan'),_('Feb'),_('Mar'),_('Apr'),_('May'),_('Jun'),_('Jul'),_('Aug'),_('Sep'),_('Oct'),_('Nov'),_('Dec')];
	var dateInput = document.getElementById('friday');
	var currentFriday = ISOStringToDate(dateInput.value);
	var lastSaturday = addDays(currentFriday,-6);
	var weekStart = document.getElementById('weekStart');
	var weekEnd = document.getElementById('weekEnd');
	weekStart.value = _("Sat")+" "+ ((lastSaturday.getUTCDate() > 9) ? lastSaturday.getUTCDate() : '0'+lastSaturday.getUTCDate()) +" "+monthArray[lastSaturday.getUTCMonth()];
	weekEnd.value = _("Fri")+" "+ ((currentFriday.getUTCDate() > 9) ? currentFriday.getUTCDate() :'0'+currentFriday.getUTCDate()) +" "+monthArray[currentFriday.getUTCMonth()];
	var status = document.getElementById("status");
	if(status) status.textContent= "";
}