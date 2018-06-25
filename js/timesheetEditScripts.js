var Gtimesheet;
window.onbeforeunload = function() { 
	if (Gtimesheet) {
		var saveButton = document.getElementById("saveButton");
		if(!saveButton.hasAttribute("disabled")) {
			saveButton.setAttribute("disabled","disabled");
			document.getElementById("proceedButton").setAttribute("disabled","disabled");
			document.getElementById("entryButton").setAttribute("disabled","disabled");
			Gtimesheet.errorDisplay.showError(_("Saving..."),null,ErrorDisplay.NOTICE);
			Gtimesheet.textEntry.serializeAndSend(false);
		}
		delete saveButton;
	}
};
window.onunload = function() {
	delete Gtimesheet;
};
window.onload = function(){
	var resolution = document.getElementById('resolution').value;
	var showWeekendDays = document.getElementById('showWeekendDays').value;
	var startTime = document.getElementById('startTime').value;
	var stopTime = document.getElementById('stopTime').value;
	var weekEndingDate = document.getElementById('weekEndingDate').value;
	var minHours = document.getElementById('minHours').value;
	var refid = document.getElementById('refid').value;
	var viewingMode = document.getElementById('viewingMode').value;
	var variable = document.getElementById('variable').value;
	var submitted = document.getElementById('submitted').value;

	Gtimesheet = new TimeSheet(resolution, showWeekendDays, startTime, stopTime, weekEndingDate, minHours, refid, viewingMode, variable, submitted);
	TimeSheet.activeInstance = Gtimesheet;
	Gtimesheet.initialize();
};