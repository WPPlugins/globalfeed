
function add_feed( feed_name ) {
    return jQuery.post(ajaxurl, {
            action:'mbgf_rss_add_rss_feed',
            rss_feed:escape(feed_name),
            _wpnonce:jQuery('#_wpnonce').val()},
        function(response){
        if (response.responseText != undefined) 
            response = jQuery.parseJSON(response.responseText);
        else
            response = jQuery.parseJSON(response);

        if ( response != null ) {
            // Setup is complete, go to main configuration.
            return response;
            //window.location.reload();
        } else {
            // Setup failed somewhere along the way.
            showMessage('Error: An error was encountered saving the feed. ' + response, 'saveError', true);
            return false;
        }
    });
}

function remove_feed( feed_name, link_obj ) {
    jQuery.ajax({
            url:ajaxurl,
            type: 'POST',
            data: {
                action:'mbgf_rss_remove_rss_feed',
                rss_feed:escape(feed_name),
                _wpnonce:jQuery('#_wpnonce').val()
            },
            success:function(response){
                if (response.responseText != undefined) 
                    response = jQuery.parseJSON(response.responseText);
                else
                    response = jQuery.parseJSON(response);
                
                if (response == true) {
                    // Setup is complete, go to main configuration.
                    showMessage('The feed was removed successfully.', 'removeSuccess_' + Math.round(Math.random()*1000), false, 10000);
                    link_obj.parent().parent().remove();
                    return true;
                } else {
                    // failed somewhere along the way.
                    showMessage('Error: An error was encountered removing the feed. ' + response, 'saveError', true);
                    return false;
                }
            },
            error:function(response){
                // Setup failed somewhere along the way.
                showMessage('Error: An error was encountered removing the feed. ' + response, 'saveError', true);
                return false;
            }
    });
}