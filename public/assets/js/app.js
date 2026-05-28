document.addEventListener("DOMContentLoaded", () => {
    const searchBtn = document.getElementById("search-btn");
    const generateBtn = document.getElementById("generate-btn");
    const locationInput = document.getElementById("location-input");
    const placesList = document.getElementById("places-list");
    const errorDiv = document.getElementById("error-message");

    let selectedPlaces = [];

    // 1. Fetch Geocoding API from text input
    searchBtn.addEventListener("click", async () => {
        const query = locationInput.value.trim();
        errorDiv.textContent = ""; // Clear errors
        
        if (!query) return;

        try {
            const response = await fetch(`/api/geocode.php?q=${encodeURIComponent(query)}`);
            const result = await response.json();

            if (response.ok && result.status === "success") {
                const place = result.data;
                selectedPlaces.push(place);

                // Update UI list
                const li = document.createElement("li");
                li.textContent = `${place.name} (Lat: ${place.lat}, Lon: ${place.lon})`;
                placesList.appendChild(li);
                locationInput.value = "";
            } else {
                // Friendly error message UI (BCC3.5)
                errorDiv.textContent = result.message || "Location not found.";
            }
        } catch (err) {
            errorDiv.textContent = "A network error occurred. Please try again.";
            console.error("Client Error:", err);
        }
    });

    // 2. Post array to PHP -> Python Bridge for route optimization
    generateBtn.addEventListener("click", async () => {
        errorDiv.textContent = "";
        if (selectedPlaces.length < 2) {
            errorDiv.textContent = "Please add at least 2 locations to compute a tour.";
            return;
        }

        try {
            const response = await fetch("/api/generate_trip.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ places: selectedPlaces })
            });

            const result = await response.json();
            
            if (response.ok && result.status === "success") {
                alert(`Tour optimized successfully! Total optimized distance: ${result.distance} km`);
                console.log("Optimized Path Order:", result.optimized_route);
                // Redirect or render route map here...
            } else {
                errorDiv.textContent = result.message || "Failed to generate optimized trip.";
            }
        } catch (err) {
            errorDiv.textContent = "An error occurred while connecting to the optimization engine.";
            console.error("Generation Error:", err);
        }
    });
});