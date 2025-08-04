$(document).ready(function() {
    // Handle state selection change
    $('select[name="default_state_for_city_filter"]').on('change', function() {
        const stateId = $(this).val();
        const $cityContainer = $('.selected-cities-settings');
        const $cityCheckboxes = $('.cities-checkboxes');
        
        if (stateId) {
            // Show city selection container
            $cityContainer.show();
            
            // Load cities for the selected state
            loadCitiesForState(stateId, $cityCheckboxes);
        } else {
            // Hide city selection container
            $cityContainer.hide();
            $cityCheckboxes.empty();
        }
    });
    
    function loadCitiesForState(stateId, $container) {
        // Show loading indicator
        $container.html('<div class="loading">Loading cities...</div>');
        
        // Make AJAX request to get cities
        $.ajax({
            url: '/ajax/cities-by-state',
            method: 'GET',
            data: {
                state_id: stateId
            },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    let html = '<div class="row">';
                    
                    response.data.forEach(function(city, index) {
                        if (city.id && city.id !== 0) { // Skip the "Select city..." option
                            const checked = isCurrentlySelected(city.id) ? 'checked' : '';
                            html += `
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-check">
                                        <input type="checkbox" 
                                               name="selected_cities_for_checkout[]" 
                                               value="${city.id}" 
                                               class="form-check-input" 
                                               ${checked}>
                                        <span class="form-check-label">${city.name}</span>
                                    </label>
                                </div>
                            `;
                        }
                    });
                    
                    html += '</div>';
                    $container.html(html);
                } else {
                    $container.html('<p class="text-muted">No cities found for this state.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading cities:', error);
                $container.html('<p class="text-danger">Error loading cities. Please try again.</p>');
            }
        });
    }
    
    function isCurrentlySelected(cityId) {
        // Check if this city is currently selected
        const currentValues = window.selectedCities || [];
        return currentValues.includes(cityId.toString());
    }
    
    // Initialize on page load if state is already selected
    const initialStateId = $('select[name="default_state_for_city_filter"]').val();
    if (initialStateId) {
        const $cityCheckboxes = $('.cities-checkboxes');
        loadCitiesForState(initialStateId, $cityCheckboxes);
    }
});