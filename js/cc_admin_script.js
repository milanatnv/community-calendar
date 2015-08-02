function add_holiday_validation()
{
    var len = jQuery("table#holidays tr").length - 1;
    for (i = 1; i <= len; i++)
    {
        if (jQuery("input#holiday_name" + i).val() == "")
        {
            jQuery("input#holiday_name" + i).focus();
            jQuery("input#holiday_name" + i).css("border", "1px solid red")
            alert("Please input holiday name");
            return false;
        }
    }
    
    jQuery("input#holiday_count").val(len);
    return true;
}

function add_holiday() {    
    var len = jQuery("table#holidays tr").length;
    var i = len;
    jQuery("table#holidays").append('<tr>'
         + '<td><input class="vm_holiday_name" name="holiday_name' + i + '" type="text" id="holiday_name' + i + '"/></td>'
         + '<td><input class="vm_holiday_date" name="holiday_date' + i + '" type="text" id="holiday_date' + i + '" readonly="readonly"/></td>'
         + '<td>'
         + '<img onclick="add_holiday()" src="' + PLUGIN_URL + 'images/add.png" title="Add a row" alt="Add a row" style="cursor:pointer; margin:0 3px;">'
         + '<img id="remove_holiday' + i + '"onclick="delete_holiday(' + i + ')" src="' + PLUGIN_URL + 'images/remove.png" title="Remove this row" alt="Remove this row" class="delete_list_item" style="cursor: pointer; visibility: visible;">'
         + '</td></tr>');
         
    
    jQuery('.vm_holiday_date').datepicker({
        dateFormat : 'mm-dd-yy',
        showOn: "both",
        buttonImageOnly: true,
        buttonImage: PLUGIN_URL + "images/calendar1.png"
    });
}

function delete_holiday(i) {    
    if (jQuery("#holiday_id" + i).length > 0) {
        jQuery("body").append('<div id="vm-overlay"></div>');
        jQuery("div#vm-pinner").fadeIn(500);
        jQuery.ajax( {
            type: "POST",
            url: ajaxurl,
            data: {"action":"vm_ajax_delete_holiday", "id": jQuery("#holiday_id" + i).val()},
            success: function() {
                remove_holiday_tr(i);
                jQuery("div#vm-pinner").fadeOut(500);                  
                jQuery("#vm-overlay").remove();                
            }
        });
    }
    else {
        remove_holiday_tr(i);
    }
}

function remove_holiday_tr(i) {
    jQuery("#holiday_name" + i).parents("tr").fadeOut('300', function() {
        jQuery("#holiday_name" + i).parents("tr").remove();
        var len = jQuery("table#holidays tr").length;
        for (j=(i+1); j<=(len+1); j++) 
        {
            jQuery("#holiday_name" + j).attr("name", "holiday_name" + (j-1));
            jQuery("#holiday_name" + j).attr("id", "holiday_name" + (j-1));
            
            jQuery("#holiday_date" + j).attr("name", "holiday_date" + (j-1));
            jQuery("#holiday_date" + j).attr("id", "holiday_date" + (j-1));
            
            if (jQuery("#holiday_id" + j).length > 0)
            {
                jQuery("#holiday_id" + j).attr("name", "holiday_id" + (j-1));
                jQuery("#holiday_id" + j).attr("id", "holiday_id" + (j-1));
            }            
            jQuery("#remove_holiday" + j).attr("onclick", "delete_holiday(" + (j-1) + ")");                        
            jQuery("#remove_holiday" + j).attr("id", "remove_holiday" + (j-1));                  
        }                                     
    });    
}