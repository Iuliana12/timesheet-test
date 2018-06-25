function deleteTimesheet(refid)
{
	if(confirm(_("Are you sure you want to discard all the data related to this timesheet?")))
	{
		var request = "<\?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		request += "<REQUEST OBJECT=\"deleteTimesheet\">\n";
		request += "\t<REFID>" + refid + "</REFID>\n";
		request += "</REQUEST>\n";
		ajaxSendXMLtoPHP("parseResponse.php",request,function(neimportant){
            window.location.reload(true);
        });
	}
}

function convertTimesheet(refid)
{
    if(confirm(_("Are you sure you want to convert this time sheet to 15minute resolution?\n This action can not be reverted!")))
    {
        var request = "<\?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        request += "<REQUEST OBJECT=\"convertTimesheet\">\n";
        request += "\t<REFID>" + refid + "</REFID>\n";
        request += "</REQUEST>\n";
        ajaxSendXMLtoPHP("parseResponse.php",request,function(neimportant){window.location.reload(true);});
    }
}

(function() {

 } )();
