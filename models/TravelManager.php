<?php
namespace App\Models;

use PDO;
use Exception;

class TravelManager {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Persist the optimised tour result for a given travel.
     * Stores the full JSON plan (hotels, clusters, distances) in ordered_route.
     */
    public function saveResult(int $travelId, array $result): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE travels SET ordered_route = ? WHERE id = ?"
            );
            return $stmt->execute([json_encode($result), $travelId]);
        } catch (Exception $e) {
            error_log("TravelManager::saveResult — " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch a travel row by its share token.
     * Returns the row array or false if not found.
     */
    public function getTripByToken(string $token): array|bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM travels WHERE share_token = ?"
            );
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("TravelManager::getTripByToken — " . $e->getMessage());
            return false;
        }
    }
}
