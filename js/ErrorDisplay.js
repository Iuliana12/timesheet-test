//class is the name of the class that should be given to the error pane
function ErrorDisplay(container, className) {

	this.secondsToDisplay = 8;
	this.fadeAlphaStep = 3;
	this.fadeTimeStep = 20;
	this.className = className;
	this.errorArray = Array();
	if(typeof container == 'string') {
		this.container = document.getElementById(container);
	}
	this.showError = function(description,code,type) {
		if(!this.container) {
			return;
		}
		if(description == null || trim(description) == '') {
			description = _("An error has occured, please contact the IT department!");
		}
		var element = document.createElement("div");
		element.className = this.className;
		switch(type) {
			case ErrorDisplay.FATAL:
				element.style.backgroundColor = '#D62A27';
				break;
			case ErrorDisplay.WARNING:
				element.style.backgroundColor = '#FFCA7F';
				break;
			case ErrorDisplay.NOTICE:
				element.style.backgroundColor = '#20AE5F';
				break;
		}
		//setting the text of the container
		if(code == null || code == "") {
			element.appendChild(document.createTextNode(description));
		}
		else {
			element.appendChild(document.createTextNode(code+" : "+description));
		}
		this.container.appendChild(element);
		//unhiding the container
		this.container.style.display = 'block';
		this.initializeHide(element);
		this.errorArray.push(element);
		return element;
	}
	this.initializeHide = function(element) {
		if(!element) {
			return;
		}
		//initiating the fading process
		if (element.style.MozOpacity!=null) {
			/* Mozilla's pre-CSS3 proprietary rule */
			element.style.MozOpacity = 1;
		} else if (element.style.opacity!=null) {
			/* CSS3 compatible */
			element.style.opacity = 1;
		}else if (element.filters[0] && ( typeof fadeInTarget[myNumber].filters[0].opacity=="number")) {
			/* IE 6+'s proprietary filter */
			element.opacity = 100;
		} else if (element.style.filter!=null) {
			/* IE's proprietary filter */
			element.style.filter = "alpha(opacity=100)";
		} else if (element.style.KhtmlOpacity!=null) {
			// khtml compatibility
			element.style.KhtmlOpacity = 1;
		}
		//setting a time out to clear the error message after the setted number of seconds
		var obj = this;
		setTimeout(function(){obj.fade(element,100); delete obj,element;},this.secondsToDisplay * 1000);
	}
	this.fade = function(element, opacity) {
		if(!element) {
			return;
		}
		if(opacity < 20) {
			element.style.display = 'none';
			if (element.style.MozOpacity!=null) {
				/* Mozilla's pre-CSS3 proprietary rule */
				element.style.MozOpacity = 1;
			} else if (element.style.opacity!=null) {
				/* CSS3 compatible */
				element.style.opacity = 1;
			}else if (element.filters[0] && ( typeof fadeInTarget[myNumber].filters[0].opacity=="number")) {
				/* IE 6+'s proprietary filter */
				element.opacity = 100;
			} else if (element.style.filter!=null) {
				/* IE's proprietary filter */
				element.style.filter = "alpha(opacity=100)";
			} else if (element.style.KhtmlOpacity!=null) {
				// khtml compatibility
				element.style.KhtmlOpacity = 1;
			}
			for(var i= 0; i < this.errorArray.length; ++i) {
				if(this.errorArray[i] === element) {
					this.errorArray.splice(i,1);
				}
			}
			if(element.parentNode){
				element.parentNode.removeChild(element);
			}
			return;
		}
		if (element.style.MozOpacity!=null) {
			/* Mozilla's pre-CSS3 proprietary rule */
			element.style.MozOpacity = (opacity/101);
		} else if (element.style.opacity!=null) {
			/* CSS3 compatible */
			element.style.opacity = (opacity/101);
		} else if (element.style.filter!=null) {
			/* IE's proprietary filter */
			element.style.filter = "alpha(opacity="+opacity+")";
		} else if (element.style.KhtmlOpacity!=null) {
			// khtml compatibility
			element.style.KhtmlOpacity = (opacity/101);
		}
		opacity -= this.fadeAlphaStep;
		var obj = this;
		setTimeout(function(){obj.fade(element,opacity); delete obj, element;}, this.fadeTimeStep);
	}
	//this function removes a message even though the time hasn't timed out yet.
	this.clean = function(element) {
		if(!element) {
			return;
		}
		this.fade(element,100);
	}
	//this function removes all the messages even though the time hasn't timed out yet.
	this.cleanAll = function() {
		for(var i= 0; i < this.errorArray.length; ++i) {
			this.fade(this.errorArray[i],100);
		}
	}

}
ErrorDisplay.FATAL = 0;
ErrorDisplay.WARNING = 1;
ErrorDisplay.NOTICE = 2;
