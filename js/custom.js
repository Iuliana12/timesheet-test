jQuery( document ).ready(function() {

  // delete button after the 15 minutes conversion
  $('.convert').each(function() {
      var $this = $(this);
      if($this.html().replace(/\s|&nbsp;/g, '').length == 0)
          $this.remove();
  });

  var isMenuOnPAge = document.getElementById('menuSection');
   if (isMenuOnPAge) {
     if (window.innerWidth < 980) {
       jQuery('#mob-btn').css('display', 'block');
        jQuery('#mob-btn').on('click', function (){
          jQuery('#menuSection').fadeToggle( "600", "linear" );
        });
   	  } else {
      jQuery('#mob-btn').css('display', 'none');
      }
   }
   minifiedTable();
 });

jQuery(window).on('resize', function(){
  minifiedTable();
  if (window.innerWidth < 980) {
    jQuery('#menuSection').css('display', 'none');
    jQuery('#mob-btn').css('display', 'block');
    $("#mob-btn").off("click");
    jQuery('#mob-btn').on('click', function (){
      jQuery('#menuSection').fadeToggle( "600", "linear" );
    });
  } else {
    jQuery('.insideHeading').remove();
    jQuery('#mob-btn').css('display', 'none');
    jQuery('.minifiedTable tbody tr td').show();
    jQuery('#menuSection').css('display', 'block');
  }

});

function minifiedTable(){

    jQuery('.minifiedTable').each(function(){
        const noOfHeadings = jQuery(this).find('thead tr th').length;
        if( noOfHeadings ) {
            var arrHeadings = [];
            var arrHeadingsHtml = [];
            jQuery(this).find('.insideHeading').remove();

            if (window.innerWidth < 980) {
                for (var i = 1; i <= noOfHeadings; i++) {
                    arrHeadings[i] = jQuery(this).find('thead tr th:nth-child(' + i + ')').text();
                    arrHeadingsHtml[i] = jQuery(this).find('thead tr th:nth-child(' + i + ')').html();
                    if(arrHeadingsHtml[i] == '&nbsp;' || !arrHeadings[i] || arrHeadings[i].length<=0) {
                        arrHeadings[i] = '';
                    }
                    var isEmpty = jQuery(this).find('tbody tr td:nth-child(' + i + ')').text();
                    var hasCharacters = jQuery(this).find('tbody tr td:nth-child(' + i + ')').text().length;
                    //if(!isEmpty || isEmpty == '0' || isEmpty == ' ' || isEmpty == '' || hasCharacters <= 0 || isEmpty == '&nbsp;') {
                    //   jQuery('.minifiedTable tbody tr td:nth-child(' + i + ')').hide();
                    // } else {
                    // if ( i == noOfHeadings){
                    //   jQuery('.minifiedTable tbody tr td:nth-child(' + i + ')').prepend('<span class="insideHeading">'+ arrHeadings[i] + ' : </span>');
                    // } else {

                    jQuery(this).find('tbody tr td:nth-child(' + i + ')').prepend('<span class="insideHeading">'+ arrHeadings[i] + '  </span>');
                    //let i2 = i+2;
                    // console.log(i2);

                    jQuery(this).find('tfoot tr#totals td:nth-child(' + (i-1) + ')').prepend('<span class="insideHeading">'+ arrHeadings[i] + '  </span>');
                    // }

                    //}

                }
                var isTableAfterButtons = jQuery('.side-content .alignInputTbl').length;
                if(isTableAfterButtons <= 0) {
                    jQuery('.alignInputTbl').insertAfter('#formEntry');
                }

            } else {// end if query
                var isTableAfterButtons = jQuery('.tbl-row > .alignInputTbl').length;
                if(isTableAfterButtons <= 0) {
                    jQuery('.alignInputTbl').insertBefore('.side-content');
                }
            }
        }
    });
}


// function checkThisTask(){
//   jQuery('.check-this-task').click();
// }
//
// function checkThisInvoice(){
//   jQuery('.check-this-invoice').click();
// }
var GDatePicker = null;//the date picker used for dates
var GDatePickerLastElement = null; //this will store the last element the DatePicker was associtaed with
var currentFriday = null;

function onDateInput(event){
    if(GDatePicker === null){
        //initializing the date picker
        var PickerDiv = document.createElement("div");
        PickerDiv.id = 'datepicker';
        PickerDiv.style.position = 'absolute';
        var body = document.getElementsByTagName("body")[0];
        body.appendChild(PickerDiv);
        var navConfig = {
            monthFormat: YAHOO.widget.Calendar.LONG
        };
        GDatePicker = new YAHOO.widget.Calendar( 'datepickerid', 'datepicker',{navigator: navConfig, close: true, start_weekday: 6, show_week_header: false});
        //setting the event handlers accordingly
        GDatePicker.selectEvent.subscribe(function (eventType, dateArr) {
            if (GDatePickerLastElement !== null) {
                var strDate = dateArr[0][0];
                var year = parseInt(strDate[0],10);
                var date = new Date(Date.UTC(year,(parseInt(strDate[1],10)-1),strDate[2]));
                //GDatePickerLastElement.value = DateToString(date);
                jQuery(GDatePickerLastElement).val(DateToString(date)).change();
                GDatePicker.hide();
                GDatePickerLastElement = null;
            }
        });

    }
    //finding the associated element
    var dateInput = null;
    for (i = 0; i < event.target.parentNode.childNodes.length; ++i ) {
        if (event.target.parentNode.childNodes[i].name && event.target.parentNode.childNodes[i].className == 'dateInput') {
            dateInput = event.target.parentNode.childNodes[i];
            break;
        }
    }
    if (dateInput === null) {
        lastError = err.showError('','0x024',ErrorDisplayClass.WARNING);
        return;
    }
    GDatePickerLastElement = null;
    //if the same dateInput was nominated, then hide the calendar
    if (GDatePickerLastElement === dateInput && GDatePicker.oDomContainer.style.display == "block") {
        GDatePicker.hide();
        return;
    }
    //setting the date to the date picker
    var date = StringToDate(dateInput.value);
    if (date == null) {
        date = new Date();
    }
//	GDatePicker.setMonth(date.getMonth());
//	GDatePicker.setYear(date.getFullYear());

    GDatePicker.select(date);
    GDatePicker.render();
    GDatePickerLastElement = dateInput;

    //positioning the date picker under the input box
    var pos = YAHOO.util.Dom.getXY(dateInput);
    pos[1] += YAHOO.util.Dom.get(dateInput).offsetHeight;
    YAHOO.util.Dom.setXY(GDatePicker.oDomContainer,pos);

    //making the picker visible
    GDatePicker.show();
    event.preventDefault();
}


function onFridayDateInput(event,modifier){

    if(GDatePicker === null){
        //initializing the date picker
        var PickerDiv = document.createElement("div");
        PickerDiv.id = 'datepicker';
        PickerDiv.style.position = 'absolute';
        var body = document.getElementsByTagName("body")[0];
        body.appendChild(PickerDiv);
        var navConfig = {
            monthFormat: YAHOO.widget.Calendar.LONG
        };
        GDatePicker = new YAHOO.widget.Calendar( 'datepickerid', 'datepicker',{navigator: navConfig, close: true, start_weekday: 6, show_week_header: false});
        GDatePicker.modifier = modifier;
        //setting the event handlers accordingly
        GDatePicker.selectEvent.subscribe(function (eventType, dateArr) {
            var dateInput = document.getElementById('friday');
            if ( currentFriday == null) {
                currentFriday = ISOStringToDate(dateInput.value);
                if (currentFriday == null) {
                    currentFriday = new Date();
                }
            }
            if (dateInput !== null) {
                var strDate = dateArr[0][0];
                var year = parseInt(strDate[0],10);
                var selectedFriday = new Date(Date.UTC(year,(parseInt(strDate[1],10)-1),strDate[2]));
                var diff = 5 - selectedFriday.getDay();
                if (selectedFriday.getDay() == 6) {
                    diff = 6;
                }
                selectedFriday = addDays(selectedFriday, diff);
                if (currentFriday.getTime() != selectedFriday.getTime()) {
                    dateInput.value =  DateToISOString(selectedFriday);
                    if(GDatePicker.modifier == 'newtimesheet'){
                        currentFriday = selectedFriday;
                        updateWeek();
                    }else {
                        document.forms.parameters.submit();
                    }
                }
                GDatePicker.hide();
            }
        });

    }
    if (GDatePicker.oDomContainer.style.display == 'block') {
        GDatePicker.hide();
        return;
    }
    var dateInput = document.getElementById('friday');
    var arr = dateInput.value.split("-");
    if(arr.length == 3) {
        var year = parseInt(arr[0],10);
        var month = parseInt(arr[1],10)-1;
        var day = parseInt(arr[2],10)-1;
        GDatePicker.setMonth(month);
        GDatePicker.setYear(year);
        GDatePicker.render();
        for (var i= 0; i < GDatePicker.cells.length; ++i) {
            if ( GDatePicker.cells[i].textContent == day && GDatePicker.cells[i].className.indexOf('selectable') != -1){
                GDatePicker.cells[i].parentNode.style.outline = '1px solid red'; //highlighting the week
                break;
            }
        }
    }

    //positioning the date picker under the input box
    var weekStart = document.getElementById('weekStart');
    var offsetX = getX(weekStart);
    var offsetY = getY(weekStart);
    GDatePicker.oDomContainer.style.left = (offsetX-10)+"px";
    GDatePicker.oDomContainer.style.top = (offsetY+ weekStart.offsetHeight)+"px";

    //making the picker visible
    GDatePicker.show();
    event.preventDefault();
}
