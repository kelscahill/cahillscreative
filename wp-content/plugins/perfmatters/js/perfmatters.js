//Perfmatters Admin JS
jQuery(document).ready(function($) {

	//tab-content display
	$('.perfmatters-subnav > a').click(function(e) {

		e.preventDefault();//stop browser to take action for clicked anchor
					
		//get displaying tab content jQuery selector
		var active_tab_selector = $('.perfmatters-subnav > a.active').attr('href');		
					
		//find actived navigation and remove 'active' css
		var actived_nav = $('.perfmatters-subnav > a.active');
		actived_nav.removeClass('active');
					
		//add 'active' css into clicked navigation
		$(this).addClass('active');

		var selected_tab_id = $(this).attr('rel');
		$('#perfmatters-options-form').attr('action', "options.php" + "#" + selected_tab_id);
					
		//hide displaying tab content
		$(active_tab_selector).removeClass('active');
		$(active_tab_selector).addClass('hide');
					
		//show target tab content
		var target_tab_selector = $(this).attr('href');
		$(target_tab_selector).removeClass('hide');
		$(target_tab_selector).addClass('active');
	});

	//display correct tab content based on URL anchor
	var hash = $.trim(window.location.hash);
    if(hash) {

    	$('#perfmatters-options-form').attr('action', "options.php" + hash);

    	//get displaying tab content jQuery selector
		var active_tab_selector = $('.perfmatters-subnav > a.active').attr('href');				
					
		//find actived navigation and remove 'active' css
		var active_nav = $('.perfmatters-subnav > a.active');
		active_nav.removeClass('active');
					
		//add 'active' css into clicked navigation
		$(hash + "-section").addClass('active');
					
		//hide displaying tab content
		$(active_tab_selector).removeClass('active');
		$(active_tab_selector).addClass('hide');
					
		//show target tab content
		var target_tab_selector = $(hash + "-section").attr('href');
		$(target_tab_selector).removeClass('hide');
		$(target_tab_selector).addClass('active');
    }

    //tooltip display
	$(".perfmatters-tooltip").hover(function(){
	    $(this).closest("tr").find(".perfmatters-tooltip-text").fadeIn(100);
	},function(){
	    $(this).closest("tr").find(".perfmatters-tooltip-text").fadeOut(100);
	});
	
	//add input row
	$('.perfmatters-add-input-row').on('click', function(ev) {
		ev.preventDefault();

		var rowCount = $(this).prop('rel');

		rowCount++;

		var $container = $(this).closest('.perfmatters-input-row-wrapper').find('.perfmatters-input-row-container');

		var $clonedRow = $container.find('.perfmatters-input-row').last().clone();

		$clonedRow.find(':text, select').val('');
		$clonedRow.find(':checkbox').prop('checked', false);

		perfmattersUpdateRowCount($clonedRow, rowCount);

		$container.append($clonedRow);
		
		$(this).prop('rel', rowCount);
	});

	//delete input row
	$('.perfmatters-input-row-wrapper').on('click', '.perfmatters-delete-input-row', function(ev) {
		ev.preventDefault();

		var siblings = $(this).closest('div').siblings();
		var $addButton = $(this).closest('.perfmatters-input-row-wrapper').find('.perfmatters-add-input-row');

		if($addButton.prop('rel') == 0) {
			$row = $(this).closest('.perfmatters-input-row');
			$row.find(':text, select').val('');
			$row.find(':checkbox').prop("checked", false);
		}
		else {
			$(this).closest('div').remove();
			$addButton.prop('rel', $addButton.prop('rel') - 1);
		}
		
		siblings.each(function(i) {

			perfmattersUpdateRowCount(this, i);
		});
	});

	//input display control
	$('.perfmatters-input-controller input, .perfmatters-input-controller select').change(function() {

		var controller = $(this);

		var inputID = $(this).attr('id');

		var nestedControllers = [];

		$('.' + inputID).each(function() {

			var skipFlag = true;
			var forceHide = false;
			var forceShow = false;

			if($(this).hasClass('perfmatters-input-controller')) {
				nestedControllers.push($(this).find('input').attr('id'));
			}

			var currentInputContainer = this;

			$.each(nestedControllers, function(index, value) {

				var controlChecked = $('#' + value).is(':checked');
				var controlReverse = $('#' + value).closest('.perfmatters-input-controller').hasClass('perfmatters-input-controller-reverse');

	  			if($(currentInputContainer).hasClass(value) && (controlChecked == controlReverse)) {
	  				skipFlag = false;
	  				return false;
	  			}
			});

			if(controller.is('select')) {
				var className = this.className.match(/perfmatters-select-control-([^\s]*)/);

				if(className && className[1] == controller.val()) {
					forceShow = true;
				}
				else {
					forceHide = true;
				}
			}

			if(skipFlag) {
				if(($(this).hasClass('hidden') || forceShow) && !forceHide) {
					$(this).removeClass('hidden');
				}
				else {
					$(this).addClass('hidden');
				}
			}

		});
	});

	//validate Login URL
	$("#perfmatters-admin #login_url").keypress(function(e) {
		var code = e.which;
		var character = String.fromCharCode(code);
		if(!perfmattersValidateInput(character, /^[a-z0-9-]+$/)) {
			e.preventDefault();
		};
	});
});

//update row count for given input row attributes
function perfmattersUpdateRowCount(row, rowCount) {
	jQuery(row).find('input, select, label').each(function() {
		if(jQuery(this).attr('id')) {
			jQuery(this).attr('id', jQuery(this).attr('id').replace(/[0-9]+/g, rowCount));
		}
		if(jQuery(this).attr('name')) {
			jQuery(this).attr('name', jQuery(this).attr('name').replace(/[0-9]+/g, rowCount));
		}
		if(jQuery(this).attr('for')) {
			jQuery(this).attr('for', jQuery(this).attr('for').replace(/[0-9]+/g, rowCount));
		}
	});
}

//validate settings input
function perfmattersValidateInput(input, pattern) {
	if(input.match(pattern)) {
		return true;
	} else {
		return false;
	}
}