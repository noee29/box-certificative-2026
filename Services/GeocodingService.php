<?php
namespace App\Services;

use Exception;

class GeocodingService {
    /**
     * Fetch coordinates (Lat, Long) from a textual location name using OpenStreetMap.
     */
    public function searchCoordinates(string $locationName): array|bool {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($locationName) . "&format=json&limit=1";
        
        $options = [
            "http" => [
                "header" => "User-Agent: TravelPlannerApp/1.0 (amirkolawole17@gmail.com)\r\n"
            ]
        ];
        
        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception("Failed to contact the Geocoding API.");&
            }

            $data = json_decode($response, true);
            if (empty($data)) {
                return false; // Location not found
            }

            return [
                'name' => $data[0]['display_name'],
                'lat' => (float)$data[0]['lat'],
                'lon' => (float)$data[0]['lon']
            ];
        } catch (Exception $e) {
            error_log("Geocoding Service Error: " . $e->getMessage());
            return false;
        }
    }
}