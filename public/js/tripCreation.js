document.addEventListener("DOMContentLoaded", function () {
    const citySelect = document.getElementById("city");
    const locationSelect = document.getElementById("trip_location");

    // Fetch initial location data when the page loads
    if (locationSelect.value) {
        const locationId = locationSelect.value;
        fetch(`/get-location/${locationId}`)
            .then(response => response.json())
            .then(data => {
                
                // Populate street name and coordinates
                document.querySelector("p.streetName").innerHTML = `Rue: ${data.streetName}`;
                document.querySelector("p.postalCode").innerHTML = `Code postal: ${data.zipCode}`;
                const latitudeField = document.getElementById("latitude");
                latitudeField.value = data.latitude || '';
                latitudeField.placeholder = data.latitude ? '' : "Latitude not available";

                const longitudeField = document.getElementById("longitude");
                longitudeField.value = data.longitude || '';
                longitudeField.placeholder = data.longitude ? '' : 'Longitude not available';
                 // Update the city name and hidden input with the city ID
                 const cityNameField = document.getElementById("city_name");
                 const cityHiddenField = document.getElementById("city_hidden");
 
                 cityNameField.value = data.cityName || '';  // Update city name
                 cityHiddenField.value = data.cityId || '';  // Update city ID
                
            })
            .catch(error => console.error("Error fetching location data on load:", error));
    }

    // Fetch and populate location details when location changes
    locationSelect.addEventListener("change", function () {
        const locationId = locationSelect.value;

        if (locationId) {
            // Make AJAX request to get location data
            fetch(`/get-location/${locationId}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);

                     // Populate city name and city ID
                     const cityNameField = document.getElementById("city_name");
                     const cityHiddenField = document.getElementById("city_hidden");
 
                     cityNameField.value = data.cityName || '';  // Update city name
                     cityHiddenField.value = data.cityId || '';  // Update hidden field with city ID

                    document.querySelector("p.streetName").innerHTML = `Rue: ${data.streetName}`;
                    document.querySelector("p.postalCode").innerHTML = `Code postal: ${data.zipCode}`;
                    // Populate latitude, add placeholder if empty
                    const latitudeField = document.getElementById("latitude");
                    latitudeField.value = data.latitude || '';
                    latitudeField.placeholder = data.latitude ? '' : "Latitude not available";

                    // Populate longitude, add placeholder if empty
                    const longitudeField = document.getElementById("longitude");
                    longitudeField.value = data.longitude || '';
                    longitudeField.placeholder = data.longitude ? '' : 'Longitude not available';

                
                })
                .catch(error => console.error("Error fetching location data:", error));
        }
    });
});