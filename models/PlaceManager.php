<?php
namespace App\Models;

use PDO;
use Exception;

class PlaceManager {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Save a location for a travel.
     */
    public function savePlace(int $travelId, string $name, float $latitude, float $longitude): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO places (travel_id, nom, latitude, longitude) 
                VALUES (:travel_id, :nom, :latitude, :longitude)
            ");
            return $stmt->execute([
                ':travel_id' => $travelId,
                ':nom' => $name,
                ':latitude' => $latitude,
                ':longitude' => $longitude
            ]);
        } catch (Exception $e) {
            error_log("Error saving place: " . $e->getMessage()); // Admin Logger
            return false;
        }
    }

    /**
     * Retrieve all saved places for a travel.
     */
    public function getPlacesByTravel(int $travelId): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM places WHERE travel_id = :travel_id");
            $stmt->execute([':travel_id' => $travelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching places: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a saved place by its id.
     */
    public function deletePlace(int $placeId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM places WHERE id = :id");
            return $stmt->execute([':id' => $placeId]);
        } catch (Exception $e) {
            error_log("Error deleting place: " . $e->getMessage());
            return false;
        }
    }
}