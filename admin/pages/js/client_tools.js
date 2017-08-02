// Set true if we're in a stepping wizard.
var in_wizard = false;
var _currentStep = null;

// Show any queued notices
jQuery('#notices .notice').fadeIn('slow');

/* ----------------------------------------------------------- Ajax Indicator */
function toggleAjaxIndicator( obj ) {
    if ( typeof obj === 'object' )
        obj.find('.ajax-loading-icon').toggle();
    else
        jQuery( obj ).find('.ajax-loading-icon').toggle();
}

function hideAjaxIndicator( obj ) {
    if ( typeof obj === 'object' )
        obj.find('.ajax-loading-icon').hide();
    else
        jQuery( obj ).find('.ajax-loading-icon').hide();
}

function hideMessage( noticeID ) {
    jQuery( '.' + noticeID + '.notice' ).fadeOut('slow', function(){
        jQuery(this).remove();
    });
}

function hideAllMessages(){
    jQuery('#notices').children('.notice:not(.no-hide)').fadeOut('slow',function(){
        jQuery(this).remove();
    });
}

/* ------------------------------------------------------- Messages Interface */
function showMessage( notice, noticeID, error, timeout, animate ) {
    hideMessage( noticeID );
    if ( error == null )
        error = false;
    if ( animate == null )
        animate = true;

    thisNotice = jQuery('<div class="notice ' + noticeID + '"></div>')
    thisNotice.html(notice);
    jQuery('#notices').append(thisNotice);

    if ( error == true)
        thisNotice.removeClass( 'updated' ).addClass( 'error fade' );
    else
        thisNotice.addClass( 'updated' ).removeClass( 'error fade' );

    if ( animate == true )
        thisNotice.fadeIn( 'slow' );
    else
        thisNotice.show();

    if ( timeout != null ) {
        setTimeout("hideMessage('" + noticeID + "');", timeout);
    }
}

/* This function will remove a message when a fields value changes. Very useful for validation. */
function setMsgRemove( objID, msgID ){
    jQuery( '#' + objID ).change( function(){
        hideMessage( msgID );
    });
}