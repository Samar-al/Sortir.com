document.addEventListener("DOMContentLoaded", function () {
    const citySelect = document.getElementById("city");
    const locationSelect = document.getElementById("trip_location");

    // Fetch and populate zipCode when city changes
    citySelect.addEventListener("change", function () {
        const cityId = citySelect.value;

        if (cityId) {
            // Make AJAX request to get city data
            fetch(`/get-city/${cityId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate zipCode field
                    document.querySelector("p.postalCode").innerHTML = `Code postal: ${data.zipCode}`;
                })
                .catch(error => console.error("Error fetching city data:", error));
        }
    });
    
    // Fetch and populate location details when location changes
    locationSelect.addEventListener("change", function () {
        const locationId = locationSelect.value;
        
        if (locationId) {
            // Make AJAX request to get location data
            fetch(`/get-location/${locationId}`)
                .then(response => response.json())
                .then(data => {
                    
                    document.querySelector("p.streetName").innerHTML = `Rue: ${data.streetName}`;

                    // Populate latitude, add placeholder if empty
                    const latitudeField = document.getElementById("latitude");
                    latitudeField.value = data.latitude || '';
                    latitudeField.placeholder = data.latitude ? '' : 'Latitude not available';

                    // Populate longitude, add placeholder if empty
                    const longitudeField = document.getElementById("longitude");
                    longitudeField.value = data.longitude || '';
                    longitudeField.placeholder = data.longitude ? '' : 'Longitude not available';
                })
                .catch(error => console.error("Error fetching location data:", error));
        }
    });
});
