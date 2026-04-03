jQuery(function($) {

	var getLocationObjects = function(select, onload = false, data = '') {

		var location = select.val();
		var parent = select.parent();

		if(location === '') {
			parent.removeClass('pmcs-condition-objects-loaded');
		} 
		else {

			var locationID = location.split(':').pop();
			var locationType = location.includes(':taxonomy:') ? 'taxonomy' : location.split(':')[0];
			var objectSelect = parent.find('.condition-object-select');

			//check if location type needs objects loaded
			if(locationType === 'post' || locationType === 'taxonomy') {
					
				//set loading class
				parent.removeClass('pmcs-condition-objects-loaded').addClass('pmcs-condition-load-objects');

				//fill objects with response
				var fillObjects = function(response) {

					var objects = response[locationID].objects;
					const blankName = location.includes(':taxonomy:') ? 'Select an option' : 'All ' + response[locationID].label;

					//empty first
					objectSelect.empty();

					//add blank option
					objectSelect.append($('<option>', {
						value: '',
						label: blankName,
						text: blankName,
					}));

					//add objects
					$.each(objects, function(key, value) {
						objectSelect.append($('<option>', {
							value: value.id,
							text: value.name + ' (' + value.id + ')'
						}));
					});

					parent.removeClass('pmcs-condition-load-objects').addClass('pmcs-condition-objects-loaded');

					//preselect saved value
					if(onload) {
						objectSelect.val(objectSelect.attr('data-saved-value'));
					}
				};

				if(data && onload) {
					fillObjects(data);
				} 
				else {

					const actionType = (locationType === 'post') ? 'posts' : 'terms';

					$.post(ajaxurl, {
						action: 'pmcs_get_location_' + actionType,
						id: locationID,
						nonce : PERFMATTERS.nonce
					},
					function(response) {
						response = JSON.parse(response);
						fillObjects(response);
					});
				}
			} 
			else {

				parent.removeClass('pmcs-condition-objects-loaded');
				objectSelect.empty();
			}
		}
	};

	//load location objects on condition change
	$('.perfmatters-input-row-wrapper').on('change', '.condition select.condition-select', function() {
		getLocationObjects($(this));
	});

	//saved object id arrays
	var postObjects = [];
	var termObjects = [];

	//populate saved object ids
	$('.pmcs-condition-load-objects').each(function() {

		var location = $(this).find('select.condition-select').val();
		var locationID = location.split(':').pop();
		var locationType = location.includes(':taxonomy:') ? 'taxonomy' : location.split(':')[0];

		if(locationType === 'post' && !postObjects.includes(locationID)) {
			postObjects.push(locationID);
		}
		else if(locationType === 'taxonomy' && !termObjects.includes(locationID)) {
			termObjects.push(locationID);
		}
	});

	//load object data for ids
	if(postObjects.length > 0 || termObjects.length > 0) {
		$.post(ajaxurl, {
			action: 'pmcs_get_location_objects',
			posts: postObjects,
			terms: termObjects,
			nonce : PERFMATTERS.nonce
		},
		function(response) {

			response = JSON.parse(response);

			$('.pmcs-condition-load-objects').each(function() {

				var select = $(this).find('select.condition-select');

				$(this).addClass('pmcs-condition-load-objects');

				getLocationObjects(select, true, response);
			});
		});
	}
});