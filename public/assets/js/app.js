document.addEventListener("DOMContentLoaded", () => {
    const searchBtn    = document.getElementById("search-btn");
    const generateBtn  = document.getElementById("generate-btn");
    const optimizeBtn  = document.getElementById("optimize-btn");
    const locationInput = document.getElementById("location-input");
    const maxHotelsInput = document.getElementById("max-hotels-input");
    const placesList   = document.getElementById("places-list");
    const errorDiv     = document.getElementById("error-message");
    const resultDiv    = document.getElementById("result");
    const optimizeResult = document.getElementById("optimize-result");

    let selectedPlaces = [];

    // ── 1. Recherche de lieu via géocodage ──────────────────────────────────
    searchBtn.addEventListener("click", async () => {
        const query = locationInput.value.trim();
        errorDiv.textContent = "";

        if (!query) return;

        try {
            const response = await fetch(`/api/geocode.php?q=${encodeURIComponent(query)}`);
            const result   = await response.json();

            if (response.ok && result.status === "success") {
                const place = result.data;
                selectedPlaces.push(place);

                const li = document.createElement("li");
                li.textContent = `${place.name} (Lat: ${place.lat}, Lon: ${place.lon})`;
                placesList.appendChild(li);
                locationInput.value = "";
            } else {
                errorDiv.textContent = result.message || "Location not found.";
            }
        } catch (err) {
            errorDiv.textContent = "A network error occurred. Please try again.";
            console.error("Client Error:", err);
        }
    });

    // ── 2. Circuit TSP simple ───────────────────────────────────────────────
    generateBtn.addEventListener("click", async () => {
        errorDiv.textContent = "";

        if (selectedPlaces.length < 2) {
            errorDiv.textContent = "Please add at least 2 locations to compute a tour.";
            return;
        }

        try {
            const response = await fetch("/api/generate_trip.php", {
                method:  "POST",
                headers: { "Content-Type": "application/json" },
                body:    JSON.stringify({ places: selectedPlaces })
            });

            const result = await response.json();

            if (response.ok && result.ordered_places) {
                renderSimpleResult(result);
            } else {
                errorDiv.textContent = result.message || "Failed to generate optimized trip.";
            }
        } catch (err) {
            errorDiv.textContent = "An error occurred while connecting to the optimization engine.";
            console.error("Generation Error:", err);
        }
    });

    // ── 3. Optimisation avec hôtels ─────────────────────────────────────────
    if (optimizeBtn) {
        optimizeBtn.addEventListener("click", async () => {
            errorDiv.textContent = "";

            if (selectedPlaces.length < 2) {
                errorDiv.textContent = "Please add at least 2 locations.";
                return;
            }

            const body = { places: selectedPlaces };

            if (maxHotelsInput && maxHotelsInput.value) {
                const n = parseInt(maxHotelsInput.value, 10);
                if (n > 0) body.max_hotels = n;
            }

            try {
                const response = await fetch("/api/optimize_trip.php", {
                    method:  "POST",
                    headers: { "Content-Type": "application/json" },
                    body:    JSON.stringify(body)
                });

                const result = await response.json();

                if (response.ok && result.clusters) {
                    renderOptimizeResult(result);
                } else {
                    errorDiv.textContent = result.message || "Failed to optimize trip.";
                }
            } catch (err) {
                errorDiv.textContent = "An error occurred while connecting to the optimization engine.";
                console.error("Optimize Error:", err);
            }
        });
    }

    // ── Affichage : circuit TSP simple ──────────────────────────────────────
    function renderSimpleResult(data) {
        if (!resultDiv) return;
        optimizeResult && (optimizeResult.innerHTML = "");

        let html = `<h3>Circuit optimisé — ${data.total_distance_km} km</h3><ol>`;
        data.ordered_places.forEach(p => {
            html += `<li>${p.name}</li>`;
        });
        html += `</ol>`;
        resultDiv.innerHTML = html;
    }

    // ── Affichage : plan multi-hôtels ────────────────────────────────────────
    function renderOptimizeResult(data) {
        if (!optimizeResult) return;
        resultDiv && (resultDiv.innerHTML = "");

        let html = `
            <h3>Plan optimisé — ${data.optimal_k} hôtel(s) — ${data.total_distance_km} km</h3>
            <p>Circuit entre hôtels : ${data.circuit_distance_km} km &nbsp;|&nbsp;
               Aller-retours : ${data.day_trips_distance_km} km</p>
            <h4>Ordre des hôtels</h4>
            <ol>`;

        data.hotels_circuit.forEach(h => {
            html += `<li>${h.name}</li>`;
        });
        html += `</ol><h4>Détail par hôtel</h4>`;

        data.clusters.forEach(cluster => {
            html += `<div class="cluster">
                <strong>🏨 Hôtel : ${cluster.hotel.name}</strong>`;

            if (cluster.day_trips.length > 0) {
                html += `<ul>`;
                cluster.day_trips.forEach(city => {
                    html += `<li>📍 ${city.name}</li>`;
                });
                html += `</ul>`;
            } else {
                html += `<p><em>Pas d'excursions depuis cet hôtel.</em></p>`;
            }
            html += `</div>`;
        });

        html += `<h4>Coût par nombre d'hôtels</h4>
            <table>
                <thead><tr><th>Hôtels (k)</th><th>Distance totale (km)</th><th>Score</th></tr></thead>
                <tbody>`;

        data.cost_by_k.forEach(row => {
            const recommended = row.k === data.optimal_k ? " ← recommandé" : "";
            html += `<tr>
                <td>${row.k}</td>
                <td>${row.total_distance_km}</td>
                <td>${row.score}${recommended}</td>
            </tr>`;
        });

        html += `</tbody></table>`;
        optimizeResult.innerHTML = html;
    }
});
