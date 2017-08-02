
mb_twitter_info = { tw_obj_id:0 };

function format_tw_obj( tw_obj ) {
    output_div = '<div class="tw_obj">';
    output_div += '<img src="' + tw_obj.profile_image_url + '" />';
    output_div += '<div class="obj_info"><strong>' + tw_obj.name + '</strong> - ' +  tw_obj.id + '</div>';
    
    if ( tw_obj.description == null )
        output_div += '<div class="obj_info description">' + tw_obj.status.text + '</div>';
    else
        output_div += '<div class="obj_info description">' + tw_obj.description + '</div>';

    return jQuery(output_div + '</div>');
}

function get_tw_object( tw_object_id, element_id ){
    // Check input
    var tw_object_id_match = new RegExp(/^[0-9]+$/); // Check for a user id
    var tw_object_sn_match = new RegExp(/^(\?|@)??([A-Za-z0-9_]+)$/); // Check for a user screen name
    var querystring = ''; 

    // Check which object type we have...
    if (tw_object_id_match.exec( tw_object_id )){
        querystring = '?user_id=' + tw_object_id + '&callback=?';
    } else if (tw_object_sn_match.exec( tw_object_id )){
        tw_object_id = tw_object_sn_match.exec( tw_object_id );// Replace question marks
        querystring = '?screen_name=' + tw_object_id[tw_object_id.length - 1] + '&callback=?';
    } else {
        showMessage('You must provide a valid object id (must be only numbers) or screen name (can be letters, numbers or underscores)', 'tw_object_id', true);
        jQuery('#validate_button').attr("disabled", false);
        return false;
    }

    jQuery('#tw_obj_confirm #confirm_tw_obj_id').children().fadeOut('fast', function () {jQuery(this).empty();});
    hideAllMessages();
    toggleAjaxIndicator( 'body' );
    

    // Check that the object ID is valid, and that we have access to that object.
    return jQuery.ajax({
        type: 'GET',
        url: 'https://api.twitter.com/1/users/lookup.json' + querystring,
        dataType: 'jsonp',
        data: {},
        timeout: 3000,
        statusCode: { 
            200: function (response) {
                response = response[0];// Data received from Twitter is an array
                
                // Save the twitter obj id publicly
                mb_twitter_info.tw_obj_id = response.id;
                toggleAjaxIndicator( '.mbgf_wrap' );

                // Get the output and display it
                confirm_div = format_tw_obj( response );

                jQuery( element_id ).show().empty().append(confirm_div);
                jQuery( element_id ).fadeIn('fast', function () {
                    jQuery( element_id ).removeClass('hidden-step');
                });
            }}
        
    });
}