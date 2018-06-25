//NOTE: try ajaxModule V4 for an implicit timeout function
function ajaxSendXMLtoPHP(url, message, callback){

	function ajaxBindCallback(){
		try {
			if (ajaxRequest.readyState == 4) {
				if (ajaxRequest.status && ajaxRequest.status == 200) {
					if (ajaxCallback){
						ajaxCallback(ajaxRequest.responseXML);
					}
				} else {
                    if(trim(ajaxRequest.responseText) == 'nosession'){
                        var locArr = window.location.toString().split("/");
                        var loc = locArr.pop();
                        window.location = locArr.join("/")+"/login.php?ref="+encodeURIComponent(loc);
                        return;
                    }
					alert(_("There was a problem retrieving the xml data:")
					+ "\n" + ajaxRequest.status + ":\t" + ajaxRequest.statusText
					+ "\n" + ajaxRequest.responseText);
				}
			}
			body.removeChild(loadingLabel);
		}
		catch (anything) {}
	}

	// use a local variable to hold our request and callback until the inner function is called...
	var ajaxRequest = null;
	var ajaxCallback = callback;
	var loadingStyle = "position: fixed; right: 2px; top: 2px; color: #fff; background-color: #c06;padding: 1px 6px";

	//adding a loading label on the page
	var loadingLabel = document.createElement("div");
	loadingLabel.setAttribute("style",loadingStyle);
	loadingLabel.appendChild(document.createTextNode("Loading..."));
	loadingLabel.id = "ajaxRequestModuleLoadingLabel";
	var body = document.getElementsByTagName("body")[0];
	body.appendChild(loadingLabel);

	// bind our callback then hit the server...
	if (window.XMLHttpRequest) {
		// moz et al
		ajaxRequest = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		// ie
		ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");

	}
	if (ajaxRequest) {
		ajaxRequest.onreadystatechange = ajaxBindCallback;
		ajaxRequest.open("POST", url, true);
		ajaxRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
		ajaxRequest.setRequestHeader("X-Requested-With", "XMLHttpRequest");
		ajaxRequest.send("xmlResponse="+encodeURIComponent(message));
	}
}
