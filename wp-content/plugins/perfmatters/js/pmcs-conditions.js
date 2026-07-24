jQuery(function($) {

	var objectSpinnerHtml = '<svg class="perfmatters-button-spinner" viewBox="0 0 100 100" role="presentation" focusable="false" style="background: rgba(0,0,0,.1); border-radius: 100%; width: 16px; height: 28px; margin: 0px 2px; overflow: visible; opacity: 1; background-color: transparent;"><circle cx="50" cy="50" r="50" vector-effect="non-scaling-stroke" style="fill: transparent; stroke-width: 1.5px; stroke: #fff;"></circle><path d="m 50 0 a 50 50 0 0 1 50 50" vector-effect="non-scaling-stroke" style="fill: transparent; stroke-width: 1.5px; stroke: #4A89DD; stroke-linecap: round; transform-origin: 50% 50%; animation: 1.4s linear 0s infinite normal both running perfmatters-spinner;"></path></svg>';

	var getObjectFieldName = function($row) {
		return $row.find('.condition-select').attr('name').replace('[rule]', '[object]');
	};

	var getSelectLocationMeta = function(location) {
		return {
			locationID: location.split(':').pop(),
			locationType: location.includes(':taxonomy:') ? 'taxonomy' : location.split(':')[0]
		};
	};

	var fillObjectSelect = function($row, location, objectSelect, onload, response) {
		var meta = getSelectLocationMeta(location);
		var objects = response[meta.locationID].objects;
		var blankName = location.includes(':taxonomy:') ? 'Select an option' : 'All ' + response[meta.locationID].label;

		objectSelect.empty();

		objectSelect.append($('<option>', {
			value: '',
			label: blankName,
			text: blankName,
		}));

		$.each(objects, function(key, value) {
			objectSelect.append($('<option>', {
				value: value.id,
				text: value.name + ' (' + value.id + ')'
			}));
		});

		$row.removeClass('pmcs-condition-load-objects').addClass('pmcs-condition-objects-loaded');

		if(onload) {
			objectSelect.val(objectSelect.attr('data-saved-value') || $row.attr('data-saved-object') || '');
		}
	};

	var loadSelectObjects = function($row, location, objectSelect, onload, data) {
		var meta = getSelectLocationMeta(location);

		$row.removeClass('pmcs-condition-objects-loaded').addClass('pmcs-condition-load-objects');

		if(data && onload) {
			fillObjectSelect($row, location, objectSelect, onload, data);
			return;
		}

		var actionType = (meta.locationType === 'post') ? 'posts' : 'terms';

		$.post(ajaxurl, {
			action: 'pmcs_get_location_' + actionType,
			id: meta.locationID,
			nonce : PERFMATTERS.nonce
		},
		function(response) {
			fillObjectSelect($row, location, objectSelect, onload, JSON.parse(response));
		});
	};

	var updateConditionObject = function(select, onload, data) {
		var location = select.val();
		var $row = select.closest('.perfmatters-input-row');
		var $wrap = $row.find('.condition-object-wrap');
		var objectType = location === '' ? '' : (select.find(':selected').data('object-type') || '');
		var fieldName = getObjectFieldName($row);
		var savedObject = onload ? ($row.attr('data-saved-object') || '') : '';

		$row.attr('data-object-type', objectType);
		$row.removeClass('pmcs-condition-load-objects pmcs-condition-objects-loaded');
		$wrap.empty();

		if(!objectType || location === '') {
			return;
		}

		if(objectType === 'text') {
			$wrap.append($('<input>', {
				type: 'text',
				class: 'condition-object-input',
				name: fieldName,
				value: savedObject,
				placeholder: select.find(':selected').data('object-placeholder') || ''
			}));
			return;
		}

		if(objectType === 'select') {
			var objectSelect = $('<select>', {
				class: 'condition-object-select',
				name: fieldName,
				'data-saved-value': savedObject
			});

			$wrap.append(objectSelect);
			$wrap.append($(objectSpinnerHtml));

			loadSelectObjects($row, location, objectSelect, onload, data);
		}
	};

	//load location objects on condition change
	$('.perfmatters-input-row-wrapper').on('change', '.condition select.condition-select', function() {
		var $row = $(this).closest('.perfmatters-input-row');
		$row.attr('data-saved-object', '');
		updateConditionObject($(this));
	});

	//saved object id arrays
	var postObjects = [];
	var termObjects = [];

	//populate saved object ids
	$('.pmcs-condition-load-objects').each(function() {
		var location = $(this).find('select.condition-select').val();
		var meta = getSelectLocationMeta(location);

		if(meta.locationType === 'post' && !postObjects.includes(meta.locationID)) {
			postObjects.push(meta.locationID);
		}
		else if(meta.locationType === 'taxonomy' && !termObjects.includes(meta.locationID)) {
			termObjects.push(meta.locationID);
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
				updateConditionObject(select, true, response);
			});
		});
	}
});
