function prevWeek() {
	var dateInput = document.getElementById('friday');
	var currentFriday = ISOStringToDate(dateInput.value);
	var selectedFriday = addDays(currentFriday, -7);
	dateInput.value = DateToISOString(selectedFriday);
	document.forms.parameters.submit();
}
function nextWeek() {
	var dateInput = document.getElementById('friday');
	var currentFriday = ISOStringToDate(dateInput.value);
	var selectedFriday = addDays(currentFriday, 7);
	dateInput.value = DateToISOString(selectedFriday);
	document.forms.parameters.submit();
}
function remindEmployee(refid) {
	var request = "<\?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
	request += "<LIST>\n";
	request += "\t<ITEM>"+refid+"</ITEM>\n";
	request += "</LIST>\n";
	ajaxSendXMLtoPHP('sendReminder.php',request,statusCallback);
}
function remindAll() {
	var inputs = document.getElementsByTagName("input");
	var request = "<\?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
	request += "<LIST>\n";
	var count =0;
	var errorDisplay = new ErrorDisplay('errors','errorDiv');
	for(var i=0; i < inputs.length; ++i)
	{
		if(inputs[i].name != "remind")
			continue;
		if(inputs[i].checked)
		{
			request += "\t<ITEM>"+inputs[i].value+"</ITEM>\n";
			count++;
		}
	}
	if(count > 0)
	{
		request += "</LIST>\n";
		ajaxSendXMLtoPHP('sendReminder.php',request,statusCallback);
	}
	else
		errorDisplay.showError(_("Select at least one recipient"),null,ErrorDisplay.WARNING);
}
function statusCallback(XML) {
	if(XML == null)
		return;
	var statuses = XML.getElementsByTagName("STATUS");
	var errorDisplay = new ErrorDisplay('errors','errorDiv');
	var successNo = XML.getElementsByTagName("SUCCESS");
	if (successNo != null) {
		successNo = successNo[0].textContent;
	}
	else {
		successNo = 0;
	}
	var failNo = XML.getElementsByTagName("FAIL");
	if (failNo != null) {
		failNo = failNo[0].textContent;
	}
	else {
		failNo = 0;
	}
	var messageType = ErrorDisplay.NOTICE;
	var message = "";
	if (successNo > 0 && failNo == 0) {
		message = sprintf(_("Reminder sent successfully to %s recipient(s)"),successNo);
	}
	if (successNo > 0 && failNo > 0) {
		message = sprintf(_("Reminder sent successfully to %s recipient(s), but failed on %s recipient(s)"),successNo,failNo);
		messageType = ErrorDisplay.WARNING;
	}
	if (successNo == 0 && failNo > 0) {
		message = sprintf(_("Reminder failed for all %s recipient(s)"),failNo);
		messageType = ErrorDisplay.FATAL;
	}
	if( successNo == 0 && failNo == 0) {
		message = _("No recipients, please contact IT department!");
		messageType = ErrorDisplay.FATAL;
	}
	errorDisplay.showError(message,null,messageType);
	if (failNo != 0) {
		errorDisplay.showError(statuses[0].textContent,null,ErrorDisplay.FATAL);
	}
}
