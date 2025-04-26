 
jQuery(document).ready(function($) {
    // Populate states based on country
    function populateStates($countrySelect, $stateSelect) {
        $stateSelect.prop('disabled', true).html('<option value="">Loading...</option>');
        
        $.ajax({
            url: gf_da_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gf_da_get_states',
                nonce: gf_da_ajax.nonce,
                country_id: $countrySelect.val()
            },
            success: function(response) {
                $stateSelect.html('<option value="">Select State</option>');
                if (response.success && response.data.length) {
                    $.each(response.data, function(index, state) {
                        $stateSelect.append(`<option value="${state.id}">${state.name}</option>`);
                    });
                    $stateSelect.prop('disabled', false);
                } else {
                    $stateSelect.html('<option value="">No states available</option>');
                }
            }
        });
    }

    // Populate cities based on state
    function populateCities($stateSelect, $citySelect) {
        $citySelect.prop('disabled', true).html('<option value="">Loading...</option>');
        
        $.ajax({
            url: gf_da_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gf_da_get_cities',
                nonce: gf_da_ajax.nonce,
                state_id: $stateSelect.val()
            },
            success: function(response) {
                $citySelect.html('<option value="">Select City</option>');
                if (response.success && response.data.length) {
                    $.each(response.data, function(index, city) {
                        $citySelect.append(`<option value="${city.id}">${city.name}</option>`);
                    });
                    $citySelect.prop('disabled', false);
                } else {
                    $citySelect.html('<option value="">No cities available</option>');
                }
            }
        });
    }

    // Handle country change
    $(document).on('change', '.gf_da_country', function() {
        var $countrySelect = $(this);
        var $container = $countrySelect.closest('.gf_da_container');
        var $stateSelect = $container.find('.gf_da_state');
        var $citySelect = $container.find('.gf_da_city');

        $stateSelect.html('<option value="">Select State</option>');
        $citySelect.html('<option value="">Select City</option>').prop('disabled', true);

        if ($countrySelect.val()) {
            populateStates($countrySelect, $stateSelect);
        } else {
            $stateSelect.prop('disabled', true);
        }
    });

    // Handle state change
    $(document).on('change', '.gf_da_state', function() {
        var $stateSelect = $(this);
        var $container = $stateSelect.closest('.gf_da_container');
        var $citySelect = $container.find('.gf_da_city');

        $citySelect.html('<option value="">Select City</option>');

        if ($stateSelect.val()) {
            populateCities($stateSelect, $citySelect);
        } else {
            $citySelect.prop('disabled', true);
        }
    });

    // Repeater functionality
    $(document).on('click', '.gf_da_add_row', function() {
        var $repeater = $(this).prev('.gf_da_repeater');
        var maxRows = parseInt($repeater.data('max-rows'));
        var $items = $repeater.find('.gf_da_repeater_item');

        if ($items.length >= maxRows) {
            alert('Maximum number of addresses reached.');
            return;
        }

        var index = $items.length;
        var $clone = $items.first().clone();
        $clone.find('select').val('');
        $clone.find('.gf_da_state, .gf_da_city').prop('disabled', true);
        $clone.find('.gf_da_container').attr('data-repeater-index', index);
        $clone.find('select').each(function() {
            var name = $(this).attr('name').replace(/\[\d+\]/, `[${index}]`);
            $(this).attr('name', name);
        });
        $repeater.append($clone);
    });

    $(document).on('click', '.gf_da_remove_row', function() {
        var $repeater = $(this).closest('.gf_da_repeater');
        var minRows = parseInt($repeater.data('min-rows'));
        var $items = $repeater.find('.gf_da_repeater_item');

        if ($items.length <= minRows) {
            alert('Minimum number of addresses required.');
            return;
        }

        $(this).closest('.gf_da_repeater_item').remove();
    });
});