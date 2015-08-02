jQuery(document).ready(function($) {
    if( $('#wpfc-calendar-search').length ) {
        ical_link = "http://intranetsbga.com/events.ics";
        $('#wpfc-calendar-search').append("<span class='add-to-ical' style='font-size: 16px !important;'>Add Events/Activities To \
            <a style='font-weight: bold; font-size: 16px !important;' href='" + ical_link + "'>iCal</a></span>");
    }
    
    if( $('.fc-header-title h2').length ) {
        $('.fc-header-title h2').attr("style", "font-size: 22px !important");
    }
    
    if( $('#locations-filter tfoot').length ) {
        $('#locations-filter tfoot').hide();
    }
    
    $(".cancel-pto").click(function() {
        if (!confirm("Are you sure you want to cancel?")) {
            return false;
        }
        return true;
    });
});
    
function printdiv(printpage)
{
    var headstr = "<html><head><title></title></head><body>";
    var footstr = "</body>";
    var newstr = document.all.item(printpage).innerHTML;
    var oldstr = document.body.innerHTML;
    document.body.innerHTML = headstr+newstr+footstr;
    window.print(); 
    document.body.innerHTML = oldstr;
    return false;
}
function PrintElem(elem)
{
    Popup($(elem).html());
}

function Popup(data) 
{
    var mywindow = window.open('', 'my div', 'height=400,width=600');
    mywindow.document.write('<html><head><title>my div</title>');
    
    mywindow.document.write('<link rel="stylesheet" href="main.css" type="text/css" />');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');

    mywindow.print();
    mywindow.close();

    return true;
}  
