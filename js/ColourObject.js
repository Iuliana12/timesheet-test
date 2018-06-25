
function colourObject(red,green,blue){
	this.red = red;
	this.green = green;
	this.blue = blue;
	this.fromString = function(string){
		var tmpArr = string.split("(");
		var tmp = tmpArr[1].substr(0,tmpArr[1].length-1);
		tmpArr = tmp.split(",");
		this.red = parseInt(tmpArr[0]);
		this.green = parseInt(tmpArr[1]);
		this.blue = parseInt(tmpArr[2]); 
	},
	this.toString = function (){
		return "rgb("+this.red+","+this.green+","+this.blue+")";
	},
	this.isEqual = function(otherColour){
		if(this.red == otherColour.red && this.green == otherColour.green && this.blue == otherColour.blue)
			return true;
		return false;
	};
}
function masterColourObject(){
	this.colourArray = new Array(	new colourObject(255,0,51),
									new colourObject(255,255,51),
									new colourObject(51,0,255),
									new colourObject(0,255,204),
									new colourObject(204,204,204),
									new colourObject(204,51,0),
									new colourObject(153,51,102),
									new colourObject(255,153,255),
									new colourObject(51,204,255),
									new colourObject(0,255,0),
									new colourObject(170,170,170),
									new colourObject(153,102,51),
									new colourObject(51,153,204),
									new colourObject(238,204,51));
	this.colourArrayUsage = new Array(0,0,0,0,0,0,0,0,0,0,0,0,0,0);
	this.colourPointer = -1;
	this.arrayFullyUsed = false;
	this.currentColour = function (){
		return this.colourArray[this.colourPointer];
	},
	this.nextColour = function (suggestedColour){
		if(suggestedColour !=null && suggestedColour !="null" && suggestedColour !="" )
		{//if we're given a colour
			var suggested = new colourObject(0,0,0);
			suggested.fromString(suggestedColour);
			for(i=0;i<this.colourArray.length;++i)
			{//search the array for a match
				if(this.colourArray[i].isEqual(suggested))
				{//if found, mark it as used
					this.colourArrayUsage[i] = 1;
					this.colourPointer = i;
					return suggested;
				}
			}
			//if it wasn't found, add it to the array and mark it as used
			this.colourArray.push(suggested);
			this.colourArrayUsage.push(1);
			this.colourPointer = this.colourArray.length-1;
			return suggested;
		}
		//if asking for a colour
		if(!this.arrayFullyUsed)
		{
			// look for the first unused colour
			this.colourPointer = 0; 
			while(this.colourPointer<this.colourArrayUsage.length && this.colourArrayUsage[this.colourPointer]==1)
				this.colourPointer++;
			if(this.colourPointer<this.colourArrayUsage.length)
			{
				this.colourArrayUsage[this.colourPointer] = 1;
				return this.colourArray[this.colourPointer];
			}
		}
		//if we got so far, then all the colours are used
		this.arrayFullyUsed = true;
		//generate a new one
		var r,g,b;
		var newColourObject = null;
		lastColour = this.colourArray[this.colourArray.length-1];
// 		alert(lastColour);
		var rand = Math.round(Math.random() * 6);
// 		alert(lastColour.red);
		switch(rand)
		{
			case 0:
				r = 255-lastColour.red;
				g = 255-lastColour.green;
				b = 255-lastColour.blue;
				break;
			case 1:
				r = lastColour.red;
				g = 255-lastColour.green;
				b = 255-lastColour.blue;
				break;
			case 2:
				r = 255-lastColour.red;
				g = lastColour.green;
				b = 255-lastColour.blue;
				break;
			case 3:
				r = 255-lastColour.red;
				g = 255-lastColour.green;
				b = lastColour.blue;
				break;
			case 4:
				r = lastColour.red;
				g = lastColour.green;
				b = 255-lastColour.blue;
				break;
			case 5:
				r = 255-lastColour.red;
				g = lastColour.green;
				b = lastColour.blue;
				break;
			case 6:
				r = lastColour.red;
				g = 255-lastColour.green;
				b = lastColour.blue;
				break;
		}
//no matter what has happend previously, the maximum will get the opposite value
		if(r>=g && r>=b)//r is max
			r = 255-lastColour.red;
		else if(g>=r && g>=b)//g is max
			g = 255-lastColour.green;
		else if(b>=g && b>=r)//b is max
			b = 255-lastColour.blue;
		newColourObject=new colourObject(r,g,b);
// 		alert(newColourObject);
		var loops = 0;
		while(this.inArray(newColourObject,40) && loops<32)
		{
			if(r<=g && r<=b)//r is min
			{
				r+=20;
				if(r>255)
					r=0;
			}
			if(g<=r && g<=b)//g is min
			{
				g+=20;
				if(g>255)
					g=0;
			}
			if(b<=g && b<=r)//b is min
			{
				b+=20;
				if(b>255)
					b=0;
			}
			newColourObject.red = r;
			newColourObject.green = g;
			newColourObject.blue = b;
			++loops;
		}
		if(loops==32)
		{
//			alert("more than 32 loops");
			while(this.inArray(newColourObject,20) && loops<96)
			{
				r+=10;
				if(r>255)
					r=0;
				g+=10;
				if(g>255)
					g=0;
				b+=10;
				if(b>255)
					b=0;
				newColourObject.red = r;
				newColourObject.green = g;
				newColourObject.blue = b;
				++loops;
			}
 			if(loops==96)
 				alert("Colour second loop breached. Please tell the It department about this.");
		}
// 		alert(loops+"  dist("+newColourObject+","+lastColour+")="+this.getDistance(newColourObject,lastColour));
		this.colourArray.push(newColourObject);
		this.colourArrayUsage.push(1);
		this.colourPointer = this.colourArray.length-1;
		return newColourObject;
	},
	this.inArray = function(newColourObject,distance){
		if(this.getDistance(newColourObject,new colourObject(0,0,0))<20 || this.getDistance(newColourObject,new colourObject(255,255,255))<120)
			return true;
		for(i=0;i<this.colourArray.length;++i)
		{
			if(this.getDistance(newColourObject,this.colourArray[i])<distance)
				return true;
		}
		return false;
	},
	this.getDistance = function(colourA,colourB){
		var rD = Math.abs(colourA.red - colourB.red);
		var gD = Math.abs(colourA.green - colourB.green);
		var bD = Math.abs(colourA.blue - colourB.blue);
		return rD+gD+bD;
	},
	//returns the background colour of an element given as parameter
	this.getColour = function(object)
	{
		if(!object)
			return null;
		if(object.style && object.style.backgroundColor)
			colour = object.style.backgroundColor;
		else
		{
			var style = object.getAttribute("style");
			if(!style)
				return null;
			var colour;
			var properties = style.split(";");
			for(var propI=0;propI < properties.length; ++propI)
				if(properties[propI].indexOf("background-color")!=-1)
				{
					colour = trim(properties[propI].split(":")[1]);
					break;
				}
		}
		//some browsers keep the colour in hexadecimal
		var position = colour.indexOf("#");
		if(position!=-1)
		{
			if(colour.length <7)
			{
				var r = parseInt(colour.substr(position+1,1)+colour.substr(position+1,1));
				var g = parseInt(colour.substr(position+2,1)+colour.substr(position+2,1));
				var b = parseInt(colour.substr(position+3,1)+colour.substr(position+3,1));
				colour = "rgb("+r+","+g+","+b+")";
			}
			else if(colour.length ==7)
			{
				var r = parseInt(colour.substr(position+1,2));
				var g = parseInt(colour.substr(position+3,2));
				var b = parseInt(colour.substr(position+5,2));
				colour = "rgb("+r+","+g+","+b+")";
			}
			else
			{
				alert("There's a problem in the colour object!");
				return null;
			}
		}
		return colour;
	};
}