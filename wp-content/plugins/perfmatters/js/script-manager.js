//Dynamic Form Selection
jQuery(document).ready(function($) {

	var $loader = $('#pmsm-loading-wrapper');
	if($loader) {
		$loader.hide();
	}

	//group status select
	$('.pmsm-group-heading .perfmatters-status-select').on('change', function(ev) {

		var $group = $(this).closest('.perfmatters-script-manager-group');

		if($(this).children(':selected').val() == 'enabled') {
			$(this).removeClass('disabled');
			$group.find('.perfmatters-script-manager-section .perfmatters-script-manager-assets-disabled').hide();
			$group.find('.perfmatters-script-manager-section table').show();
		 	$group.find('.pmsm-mu-mode-badge').hide();
		}
		else {
			$(this).addClass('disabled');
			$group.find('.perfmatters-script-manager-section table').hide();
			$group.find('.perfmatters-script-manager-section .perfmatters-script-manager-assets-disabled').show();
			$group.find('.pmsm-mu-mode-badge').show();
		}
	});

	//group status toggle
	$('.pmsm-group-heading .perfmatters-status-toggle').on('change', function(ev) {

		var $group = $(this).closest('.perfmatters-script-manager-group');

		if($(this).is(':checked')) {
			$group.find('.perfmatters-script-manager-section table').hide();
			$group.find('.perfmatters-script-manager-section .perfmatters-script-manager-assets-disabled').show();
			$group.find('.pmsm-mu-mode-badge').show();
		}
		else {
			$group.find('.perfmatters-script-manager-section .perfmatters-script-manager-assets-disabled').hide();
			$group.find('.perfmatters-script-manager-section table').show();
			$group.find('.pmsm-mu-mode-badge').hide();
		}
	});

	//script status select
	$('.perfmatters-script-manager-status .perfmatters-status-select').on('change', function(ev) {

		var $controls = $(this).closest('tr').find('.perfmatters-script-manager-controls');

		if($(this).children(':selected').val() == 'enabled') {
			$(this).removeClass('disabled');
			$controls.hide();
		}
		else {
			$(this).addClass('disabled');
			$controls.show();
		}
	});

	//script status toggle
	$('.perfmatters-script-manager-status .perfmatters-status-toggle').on('change', function(ev) {

		var $controls = $(this).closest('tr').find('.perfmatters-script-manager-controls');

		if($(this).is(':checked')) {
			$controls.show();
		}
		else {
			$controls.hide();
		}
	});

	//disable radio
	$('.perfmatters-disable-select').on('change', function(ev) {

		var $controls = $(this).closest('.perfmatters-script-manager-controls');

		if($(this).val() == 'everywhere') {
			$controls.find('.perfmatters-script-manager-enable').show();
		}
		else {
			$controls.find('.perfmatters-script-manager-enable').hide();
		}
		if($(this).val() == 'regex') {
			$controls.find('.pmsm-disable-regex').show();
		}
		else {
			$controls.find('.pmsm-disable-regex').hide();
		}
	});

	//set changed status of selected inputs
	$('#pmsm-main-form input').on('change', function() {
		$(this).addClass('pmsm-changed');

		if($(this).is(':checkbox')) {

			var $checkboxes = $(this).closest('.pmsm-checkbox-container').find('input');

			$checkboxes.addClass('pmsm-changed');
		}
	});
	  
	//submit main script manager form
	$('#pmsm-main-form').on('submit', function(e) {

		//prevent server side submission
		e.preventDefault();

		//disable any inputs that weren't touched
	    $('#pmsm-main-form input:not(.pmsm-changed)').prop('disabled', true);

	    //save button feedback
	    var $saveButton = $('#pmsm-save').find('input');
	    var $saveSpinner = $('#pmsm-save').find('.pmsm-spinner');
	    $saveButton.val(pmsm.messages.buttonSaving);
	    $saveSpinner.css('display', 'inline-block');

	    //serialize form data
	    var formData = $('#pmsm-main-form').serialize();

	    // set ajax data
	    var data = {
	        'action' : 'pmsm_save',
	        'current_id' : pmsm.currentID,
	        'pmsm_data' : formData
	    };

	    $.post(pmsm.ajaxURL, data, function(response) {

	    	//setup message variables
	    	var message;

	    	//set message from response
	    	if(response == 'update_success') {
	    		message = pmsm.messages.updateSuccess;

	    		//if status was toggled back on, clear child input values
	    		$('.perfmatters-status-toggle.pmsm-changed').each(function() {
	    			if(!$(this).is(':checked')) {
	    				var $toggleRow = $(this).closest('tr');
	    				$toggleRow.find('.perfmatters-script-manager-enable').hide();
	    				$toggleRow.find('input:checkbox, input:radio').prop('checked', false);
	    				$toggleRow.find('input:text').val('');
	    			}
	    		});
	    	}
	    	else if(response == 'update_failure') {
	    		message = pmsm.messages.updateFailure;
	    	}
	    	else if(response == 'update_nochange') {
	    		message = pmsm.messages.updateNoChange;
	    	}

	    	//display message
	    	if(message) {
	    		pmsmPopupMessage(message);
	    	}

	        //successful response, reset form
	        $('#pmsm-main-form input:not(.pmsm-changed)').prop('disabled', false);
	        $saveButton.val(pmsm.messages.buttonSave);
	        $saveSpinner.hide();
	        $(".pmsm-changed").removeClass("pmsm-changed");
		    
	    });
	});

	//reset button
	$('.pmsm-reset').click(function(ev) {
		ev.preventDefault();
		$('#pmsm-reset-form').submit();
	});
});

//popup message after submit
function pmsmPopupMessage(message) {

	if(message) {
		var $messageContainer = jQuery('.pmsm-message');

		$messageContainer.text(message).stop(true, true).show().animate({'opacity': 1, 'bottom': '80px'}, 500).delay(2000).animate({'opacity': 0}, 500, function() {
			jQuery(this).hide().css({'opacity': 0, 'bottom': '0px'});
		});
	}
}