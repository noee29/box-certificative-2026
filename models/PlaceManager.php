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
     * Save a location into the user's list.
     */
    public function savePlace(int $userId, string $name, float $latitude, float $longitude): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO places (user_id, name, latitude, longitude) 
                VALUES (:user_id, :name, :latitude, :longitude)
            ");
            return $stmt->execute([
                ':user_id' => $userId,
                ':name' => $name,
                ':latitude' => $latitude,
                ':longitude' => $longitude
            ]);
        } catch (Exception $e) {
            error_log("Error saving place: " . $e->getMessage()); // Admin Logger
            return false;
        }
    }

    /**
     * Retrieve all saved places for a specific user.
     */
    public function getPlacesByUser(int $userId): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM places WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching places: " . $e->getMessage());
            return [];
        }
    }
}