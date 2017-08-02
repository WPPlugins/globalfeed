/**
 * Gets a facebook object given a facebook object url.
 */
function get_fb_object( object_id, element_id ) {
    return jQuery.ajax({
        url: 'https://graph.facebook.com/' + object_id + "?metadata=1",
        dataType: 'jsonp',
        data: {},
        statusCode: { 
            200: function (response) {
                // In some browsers response is on object, and in some its text...

                toggleAjaxIndicator( element_id );
                if (response == false || typeof(response.error) != 'undefined'){
                    fb_obj_is_error = true;
                    showMessage( 'Error: The given Facebook ID was not found. Please try again.', 'fb_obj_not_found' , true);
                    setMsgRemove( 'fb_object_id','fb_obj_not_found' );
                    return false;
                } else
                    fb_obj_is_error = false;

                fb_obj_id = response.id;
                fb_obj_type = (response.type ? response.type : response.metadata.type);

                // Get the output and display it
                jQuery( element_id ).css({display:'none'}).empty().append( format_fb_obj( response ) );
                jQuery( element_id ).fadeIn('fast', function () {
                    jQuery( this ).removeClass('hidden-step');
                });
            },
            404: function(){
                showMessage( 'Error: The given Facebook ID was not found. Please try again.', 'fb_obj_not_found', true);
                setMsgRemove( 'fb_object_id','fb_obj_not_found' );
                toggleAjaxIndicator( element_id );
            },
            400: function(){
                showMessage( 'Error: An error occured. Most likely you attempted to access a resource you don\'t have permission for.', 'fb_obj_permission_error', true );
                setMsgRemove( 'fb_object_id','fb_obj_permission_error' );
                toggleAjaxIndicator( element_id );
            }}
    });
}

function format_fb_obj( fb_obj ) {
    output_div = '<div class="fb_obj">';
    console.log(fb_obj);
    output_div += '<img src="' + (typeof fb_obj.metadata.connections.picture !== 'undefined' ? fb_obj.metadata.connections.picture : fb_obj.picture) + '" />';
    if (fb_obj.username == undefined)
        output_div += '<div class="obj_info"><strong>' + fb_obj.name + '</strong> - ' +  fb_obj.id + '</div>';
    else
        output_div += '<div class="obj_info"><strong>' + fb_obj.name + ' / ' + fb_obj.username + '</strong> - ' +  fb_obj.id + '</div>';
    
    switch(fb_obj.metadata.type) {
        case 'application':
            output_div += '<div class="obj_info">Type: <strong>App</strong></div>';
            output_div += '<div class="obj_info description">' + fb_obj.description + '</div>';
            break
        case 'page':
            output_div += '<div class="obj_info">Type: <strong>Page</strong>, ' + fb_obj.category + '</div>';
            if (fb_obj.about != undefined) {
                output_div += '<div class="obj_info description">' + fb_obj.about + '</div>';
            }
            if (fb_obj.description != undefined) {
                output_div += '<div class="obj_info description">' + fb_obj.description + '</div>';
            }
            break                                            
        case 'group':
            output_div += '<div class="obj_info">Type: <strong>Group</strong>, ' + fb_obj.category + '</div>';
            output_div += '<div class="obj_info description">' + fb_obj.description + '</div>';
            break
        case 'event':
            output_div += '<div class="obj_info">Type: <strong>Event</strong></div>';
            output_div += '<div class="obj_info">Location: '  + fb_obj.location + '</div>';
            output_div += '<div class="obj_info description">' + fb_obj.description + '</div>';
            break;
        default:
            output_div += '<div class="obj_info">Type: <strong>User</strong></div>';
            break;
    }
    return jQuery(output_div + '</div>');
}

/* ----------------------------------------------- FB Authorization Interface */
var authWindowChecker = null;
var authWindow = null;
function setAuthWindowChecker( statusdiv, fail, redir_url ) {
    authWindowChecker = setTimeout(function(){
        checkAuthWindow( statusdiv, fail, redir_url );
    }, 1000);
    //authWindowChecker = setTimeout('checkAuthWindow("' + statusdiv + ',' + fail + ',' + '")', 1000);
    return;
}
function checkAuthWindow( statusdiv, fail, redir_url ) {
    clearTimeout(authWindowChecker);
    try {
        // If this is true, then the user is most likely authenticating with Facebook, or we have checked before the opened window was redirected to facebook.
        if (typeof(authWindow) == undefined || Object.keys(authWindow).length == 0) {
            setAuthWindowChecker( statusdiv, fail, redir_url );
            return;
        }
    } catch(exc) {
        // If an error has occured, we've most likely run into a problem accessing the authWindow object because Facebook is on the login page. We need to wait for the user to 
        // authenticate.
        if ( error_count < 40 ) {
            error_count++;
            setAuthWindowChecker( statusdiv, fail, redir_url );
            return;
        } else {
            // We've restarted the script too many times. Assume the user closed the window.
            hideAjaxIndicator( statusdiv );
            eval(fail);
            showMessage('The authorization either took too long, or the window was closed before authentication was completed.\n\nPlease try again.','authWindowError', true, 10000);
            authWindow.close();
            error_count = 0;
            return;
        }
    }

    if (authWindow.closed == true) {
        // The window was closed by the user before auth was completed...
        showMessage('The authorization window was closed before the authorization process was completed.\n\nPlease try again.', 'authWindowClosed', true, 10000);
        hideAjaxIndicator( statusdiv );
        eval(fail);
        return;
    }

    childWinLocation = authWindow.location.href;
    if (childWinLocation.indexOf( redir_url ) != -1) {
        // The user has been redirected back to the site...
        jQuery(authWindow.document).ready(function () {
            authWindow.close();
            if (childWinLocation.indexOf('error') != -1){
                // Auth failed. Likely the user denied access.
                showMessage('The authorization failed. Most likely you did not allow app access.\n\nPlease try again.', 'authNotApproved', true, 10000);
                hideAjaxIndicator( statusdiv );
                eval(fail);
                return;
            }

            // Check with the server to see if we now have an access token
            jQuery.post(ajaxurl, {action:'mbgf_facebook_connect_is_authed',_wpnonce:jQuery('#_wpnonce').val()}, function(response){
                if (jQuery.parseJSON(response) == true){
                    if (in_wizard == true)
                        nextStep();
                    else {
                        hideAjaxIndicator( statusdiv );
                        showMessage('Authorization was successful. The access codes have been received.', 'authSuccessful', false, 10000);
                    }
                } else {
                    hideAjaxIndicator( statusdiv );
                    fail();
                }
            });
        });
    } else {
        setAuthWindowChecker( statusdiv, fail, redir_url );
        return;
    }
}