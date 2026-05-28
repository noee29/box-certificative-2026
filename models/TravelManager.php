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
     * Create a new trip itinerary with a specific privacy level and share token.
     */
    public function createTrip(int $userId, string $title, array $orderedPlaces, string $privacy = 'private'): string|bool {
        try {
            // Generate a secure unique random token for sharing
            $shareToken = bin2hex(random_bytes(16)); 
            
            $stmt = $this->db->prepare("
                INSERT INTO trips (user_id, title, ordered_route, privacy, share_token) 
                VALUES (:user_id, :title, :ordered_route, :privacy, :share_token)
            ");
            
            $success = $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':ordered_route' => json_encode($orderedPlaces),
                ':privacy' => $privacy, // 'public' or 'private'
                ':share_token' => $shareToken
            ]);

            return $success ? $shareToken : false;
        } catch (Exception $e) {
            error_log("Error creating trip: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch a trip by its share token (Crucial for M1 peer collaboration).
     */
    public function getTripByToken(string $token): array|bool {
        try {
            $stmt = $this->db->prepare("SELECT * FROM trips WHERE share_token = :token");
            $stmt->execute([':token' => $token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching trip by token: " . $e->getMessage());
            return false;
        }
    }
}