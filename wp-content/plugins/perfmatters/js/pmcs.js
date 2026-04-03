jQuery(function($) {

	//settings link trigger
    $('#pmcs-show-settings-link').on('click', function() {
    	$(this).toggleClass('active');
        $('#show-settings-link').trigger('click');
    });

    //code type change when creating new snippet
	$("#pmcs-code-type input[name='type']").on('change', function() {

		//set code type attribute
		var type = $(this).val();
		$("#pmcs-snippet").attr("data-code-type", type);

		//update snippet codemirror editor
		var editor = document.querySelector("#pmcs-snippet .CodeMirror").CodeMirror;
		editor.setOption("mode", PMCS.code_types[type].mime);
		editor.setOption("lint", PMCS.code_types[type].lint);

		//populate locations for code type
		pmcsGetLocationOptions(type);
		pmcsUpdateLoadBehavior(type);
	});

	//update name in ui when input changes
	$("#pmcs-name").on('keyup', function(e) {
		$("#pmcs-snippet-name").text($(this).val());
	});

	//active toggle
	$('input[type="checkbox"].pmcs-active').on('change', function() {

		var action = $(this).is(':checked') ? 'activate' : 'deactivate';
		var rowClass = $(this).is(':checked') ? 'pmcs-active-snippet' : 'pmcs-inactive-snippet';
		var row = $(this).closest('tr');

		$.ajax({
	        url: PERFMATTERS.ajaxurl,
	        data: {
	        	'action' : 'perfmatters_' + action + '_snippet',
	        	'nonce' : PERFMATTERS.nonce,
	        	'file_name' : $(this).data("pmcs-file-name")
	        }
	    })
	    .done(function(r) {
	    	row.attr("class", rowClass);
	    });
  	});

  	//load method change
	$("#pmcs-method").on('change', function() {

		//populate behaviors for method
		pmcsUpdateLoadBehavior();
	});

  	//delete snippet confirmation
    $('a.pmcs-delete').on('click', function(e) {
    	e.preventDefault();

	    if(confirm(PMCS.strings.delete_snippet)) {
	    	window.location.href = $(this).attr('href');
	    }
	    else {
	    	$(this).blur();
	    }
    });

  	function pmcsGetLocationOptions(pmcsCodeType = null) {

		$("#pmcs-location").empty();

		if(!pmcsCodeType) {
			pmcsCodeType = $("#pmcs-snippet").attr("data-code-type");
		}

		$("#pmcs-location-options option").each(function(i) {
	       
			var optionCodeType = $(this).attr("data-code-type");
			var optionCodeTypeArray = optionCodeType.split(",");

			if($.inArray(pmcsCodeType, optionCodeTypeArray) == -1) {
				return;
			}

	        $(this).clone().appendTo("#pmcs-location");
	    });

	    $("#pmcs-snippet div[data-code-type]").each(function() {
			if($(this).attr('data-code-type').includes(pmcsCodeType)) {
				$(this).removeClass('hidden');
				$(this).find(":input[name]").prop("disabled", false);
				
			}
			else {
				$(this).addClass('hidden');
				$(this).find(":input[name]").prop("disabled", true);
			}
		});
	}

	//update load behavior options for selected code type
	function pmcsUpdateLoadBehavior(pmcsCodeType = null) {

		if(!pmcsCodeType) {
			pmcsCodeType = $("#pmcs-snippet").attr("data-code-type");
		}

		var method = $('#pmcs-method').val();
		var $select = $('#pmcs-behavior-' + pmcsCodeType);

		$select.find('option').each(function() {
	       
			var optionMethod = $(this).attr("data-method");

			if(optionMethod) {
				if(optionMethod !== method) {
					$(this).prop({
						'selected': false,
						'disabled': true
					});
				}
				else {
					$(this).prop({
						'disabled': false
					});
				}
			}
	    });

		//set disabled status for select
		$select.prop('disabled', $select.find('option:not(:disabled)').length < 2);
	}

	//document ready
	$(document).ready(function() {
		
		//update options once on load
	    pmcsGetLocationOptions();
	    pmcsUpdateLoadBehavior();

	    //hide loader
		$("#pmcs-snippet-loader").addClass('loaded');
	});
    
    //store timer
    let copyTimeout; 

    //recovery url copy
    $('#pmcs-recovery-url').on('click', function(e) {
        
        e.preventDefault(); 
        
        //clear existing timeout
        clearTimeout(copyTimeout); 

        const value = $(this).find('input').val();
        
        if(!value) {
            return;
        }

        const $copySpan = $(this).find('span');

        //copy value to clipboard
        navigator.clipboard.writeText(value).then(() => {

            //feedback
            $copySpan.text(PMCS.strings.copied);
            
            //timeout to restore span text
            copyTimeout = setTimeout(() => {
                $copySpan.text(PMCS.strings.copy);
            }, 1200);
        });
    });
});