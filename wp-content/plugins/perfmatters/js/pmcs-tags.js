jQuery(document).ready(function($) {
    
    var tagInput = $('#pmcs-tag-input');
    var tagPillsContainer = $('#pmcs-tags-container');
    var resultsContainer = $('#pmcs-tag-results');
    var selectedTagsInput = $('#pmcs-selected-tags-input');
    var selectedTagIds = [];
    var searchTimeout;

    //populate existing selected tags
    const inputValue = selectedTagsInput.val(); 
    if(inputValue.length > 0) {
        selectedTagIds = inputValue.split(',').map(item => $.trim(item));
    }

    //debounce function to delay the search request
    function debounce(func, delay) {
        return function(...args) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    //prevent enter key from submitting main form
    tagInput.on('keydown', function(event) {
        if(event.keyCode === 13) {
            event.preventDefault();
        }
    });

    //keyup events for searching and creating.
    tagInput.on('keyup', function(e) {
        var term = $(this).val().trim();
        
        //enter key pressed
        if(e.which === 13) {
            e.preventDefault();
            if(term.length > 1) {
                if(!selectedTagIds.includes(term)) {
                    addTagToSelected(term);
                    tagInput.val('').focus();
                    resultsContainer.html('');
                }
            }
        } 

        //search tags
        else if(term.length > 1) {
            debounce(function() {
                searchForTags(term);
            }, 300)();
        } 
        else {
            resultsContainer.html(''); //clear search results if search term is too short
        }
    });

    //search for tags
    function searchForTags(term) {

        //available tags
        var availableTags = PMCS.tags.filter(element => !selectedTagIds.includes(element));

        //tags that match search term
        const results = availableTags.filter(str => str.includes(term));

        //clear previous results
        resultsContainer.html('');

        //show search results
        if(results.length > 0) {
            $.each(results, function(index, tag) {
                var pill = $('<div class="pmcs-tag">').text(tag);
                pill.attr('data-tag-name', tag);
                resultsContainer.append(pill);
            });
        }

        //create new tag
        else if(term.length > 1) {
            if(!selectedTagIds.includes(term)) {
                var pill = $('<div class="pmcs-tag">').html('Create new tag: <strong>' + term + '</strong>');
                pill.attr('data-tag-name', term);
                resultsContainer.append(pill);
            }
        }
    }

    //handle clicks on tags loaded from results results
    resultsContainer.on('click', '.pmcs-tag', function() {

        var tagName = $(this).data('tag-name');

        if(tagName) {
            addTagToSelected(tagName);
            tagInput.val('').focus();
            resultsContainer.html('');
        }
    });

    //add tag to selected container
    function addTagToSelected(tagName) {

        selectedTagIds.push(tagName);

        var tag = $('<div class="pmcs-tag"></div>');
        tag.text(tagName);
        tag.attr('data-tag-name', tagName);

        //add remove button
        var closeBtn = $('<span class="pmcs-tag-close">&times;</span>');
        tag.append(closeBtn);

        //add tag to selected
        tagPillsContainer.append(tag);
        updateHiddenInput();
    }

    //handle clicks to remove tags
    $('#pmcs-tags').on('click', '.pmcs-tag-close', function(e) {

        e.stopPropagation();

        var tagName = $(this).parent().data('tag-name');

        //remove tag
        $(this).parent().remove();

        //remove tag from selected array
        selectedTagIds = $.grep(selectedTagIds, function(value) {
            return value != tagName;
        });
        updateHiddenInput();
    } );

    //update hidden input with string of selected tags
    function updateHiddenInput() {
        $('#pmcs-selected-tags-input').val(selectedTagIds.join(','));
    }
});