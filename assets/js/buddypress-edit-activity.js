function buddypress_edit_activity_initiate( link ){
    if( jQuery(link).hasClass( 'action-save' ) ){
	buddypress_edit_activity_save( link );
    } else {
	buddypress_edit_activity_get( link );
    }
    return false;
}

function buddypress_edit_activity_get( link ){
    $link = jQuery(link);
    $form = jQuery('#frm_buddypress-edit-activity');
    $form_wrapper = $form.parent();
    
    $link.addClass('loading');
    
    var data = {
	'action'			    : $form.find('input[name="action_get"]').val(),
	'buddypress_edit_activity_nonce'    : $form.find('input[name="buddypress_edit_activity_nonce"]').val(),
	'activity_id'			    : $link.data('activity_id'),
    };
    
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	data: data,
	success: function (response) {
	    response = jQuery.parseJSON(response);
	    if( response.status ){
		$link.removeClass('loading').addClass('action-save').html(B_E_A_.button_text.save);
		
		if( $link.hasClass( 'buddyboss_edit_activity_comment' ) ){
		    //editing comment
		    $link.closest('[id^=acomment]').find(' > .acomment-content').html('').hide().after( $form_wrapper );
		} else {
		    //editing activity
		    $link.closest('.activity_update').find('.activity-inner').html('').hide().after( $form_wrapper );
		}
		
		$form_wrapper.show();
		
		$form.find('input[name="activity_id"]').val( data.activity_id );
		$form.find('textarea').val(response.content);
	    }
	},
    });
}

function buddypress_edit_activity_save( link ){
    $link = jQuery(link);
    $form = jQuery('#frm_buddypress-edit-activity');
    $form_wrapper = $form.parent();
    
    $link.addClass('loading');
    
    var data = {
	'action'			    : $form.find('input[name="action_save"]').val(),
	'buddypress_edit_activity_nonce'    : $form.find('input[name="buddypress_edit_activity_nonce"]').val(),
	'activity_id'			    : $link.data('activity_id'),
	'content'			    : $form.find('textarea').val()
    };
    
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	data: data,
	success: function (response) {
	    response = jQuery.parseJSON(response);
	    if( response.status ){
		$link.removeClass('loading').removeClass('action-save').html(B_E_A_.button_text.edit);
		
		if( $link.hasClass( 'buddyboss_edit_activity_comment' ) ){
		    //editing comment
		    $link.closest('[id^=acomment]').find(' > .acomment-content').html(response.content).show();
		} else {
		    //editing activity
		    $link.closest('.activity_update').find('.activity-inner').html(response.content).show();
		}
		
		$form_wrapper.hide();
		jQuery('body').append( $form_wrapper );
	    }
	},
    });
}