
mb_youtube_info = { yt_usr:'' };

function format_yt_usr( yt_usr ) {
    output_div = '<div class="yt_usr">';
    output_div += '<img src="' + yt_usr.media$thumbnail.url + '" />';
    output_div += '<div class="obj_info"><strong>' + yt_usr.title.$t + '</strong></div>';
    
    if ( yt_usr.content.$t != null )
        output_div += '<div class="obj_info description">' + yt_usr.content.$t + '</div>';

    return jQuery(output_div + '</div>');
}

function get_yt_object( yt_username, element_id ){
    // Check input
    var yt_username_usr_match = new RegExp(/^[a-zA-Z0-9_]{1,20}$/); // Check for a user screen name
    var querystring = '';

    // Check that they've entered a valid youtube username
    if ( !yt_username_usr_match.exec( yt_username ) ){
        showMessage('You must provide a valid object id (must be only numbers) or screen name (can be letters, numbers or underscores)', 'yt_username_id', true);
        jQuery('#validate_button').attr("disabled", false);
        return false;
    }

    jQuery('#yt_usr_confirm #confirm_yt_usr_id').children().fadeOut('fast', function () {jQuery(this).empty();});
    hideAllMessages();
    toggleAjaxIndicator( 'body' );
    

    // Check that the object ID is valid, and that we have access to that object.
    return jQuery.ajax({
        type: 'GET',
        url: 'https://gdata.youtube.com/feeds/api/users/' + yt_username + '?alt=json&',
        dataType: 'jsonp',
        data: {},
        timeout: 3000,
        statusCode: { 
            200: function (response) {
                response = response.entry;// Data received from YouTube is an array
                
                // Save the youtube obj id publicly
                mb_youtube_info.yt_usr = response.yt$username.$t;
                
                toggleAjaxIndicator( '.mbgf_wrap' );

                // Get the output and display it
                confirm_div = format_yt_usr( response );

                jQuery( element_id ).show().empty().append(confirm_div);
                jQuery( element_id ).fadeIn('fast', function () {
                    jQuery( element_id ).removeClass('hidden-step');
                });
            }
        }
        
    });
}