import unittest
from algo import (
    _deg_to_rad,
    spherical_distance,
    build_distance_matrix,
    tour_distance,
    nearest_neighbor,
    two_opt,
    solve,
)

class TestDegToRad(unittest.TestCase):
    def test_zero(self):
        self.assertAlmostEqual(_deg_to_rad(0), 0.0)

    def test_180(self):
        self.assertAlmostEqual(_deg_to_rad(180), 3.141592, places=5)


class TestSphericalDistance(unittest.TestCase):
    def test_same_point(self):
        self.assertAlmostEqual(spherical_distance(48.8566, 2.3522, 48.8566, 2.3522), 0.0, places=5)

    def test_paris_london(self):
        # Paris → London ≈ 341 km
        d = spherical_distance(48.8566, 2.3522, 51.5074, -0.1278)
        self.assertAlmostEqual(d, 341, delta=5)


class TestBuildDistanceMatrix(unittest.TestCase):
    def setUp(self):
        self.places = [
            {"id": 1, "lat": 48.8566, "lng": 2.3522},
            {"id": 2, "lat": 51.5074, "lng": -0.1278},
            {"id": 3, "lat": 52.3676, "lng": 4.9041},
        ]
        self.dist = build_distance_matrix(self.places)

    def test_diagonal_zero(self):
        for i in range(3):
            self.assertEqual(self.dist[i][i], 0.0)

    def test_symmetric(self):
        for i in range(3):
            for j in range(3):
                self.assertAlmostEqual(self.dist[i][j], self.dist[j][i], places=10)


class TestTourDistance(unittest.TestCase):
    def setUp(self):
        self.dist = [
            [0,   100, 120],
            [100, 0,   210],
            [120, 210, 0  ],
        ]

    def test_simple_tour(self):
        # 0→1→2→0 : 100 + 210 + 120 = 430
        self.assertAlmostEqual(tour_distance([0, 1, 2], self.dist), 430.0)

    def test_single_city(self):
        self.assertAlmostEqual(tour_distance([0], self.dist), 0.0)


class TestNearestNeighbor(unittest.TestCase):
    def setUp(self):
        self.dist = [
            [0,   100, 120],
            [100, 0,   210],
            [120, 210, 0  ],
        ]

    def test_visits_all_cities(self):
        tour = nearest_neighbor(self.dist)
        self.assertEqual(sorted(tour), [0, 1, 2])

    def test_starts_at_start(self):
        tour = nearest_neighbor(self.dist, start=1)
        self.assertEqual(tour[0], 1)


class TestTwoOpt(unittest.TestCase):
    def setUp(self):
        self.places = [
            {"id": i, "lat": lat, "lng": lng}
            for i, (lat, lng) in enumerate([
                (48.8566, 2.3522),
                (51.5074, -0.1278),
                (52.3676, 4.9041),
                (50.8503, 4.3517),
            ])
        ]
        from algo import build_distance_matrix
        self.dist = build_distance_matrix(self.places)
        self.initial = [0, 1, 2, 3]

    def test_tour_not_longer(self):
        initial_d = tour_distance(self.initial, self.dist)
        _, improved_d = two_opt(self.initial, self.dist)
        self.assertLessEqual(improved_d, initial_d + 1e-9)

    def test_visits_all_cities(self):
        improved_tour, _ = two_opt(self.initial, self.dist)
        self.assertEqual(sorted(improved_tour), [0, 1, 2, 3])


class TestSolve(unittest.TestCase):
    def test_single_place(self):
        result = solve([{"id": 1, "lat": 48.8566, "lng": 2.3522}])
        self.assertEqual(result["total_distance_km"], 0.0)

    def test_two_places(self):
        places = [
            {"id": 1, "lat": 48.8566, "lng": 2.3522},
            {"id": 2, "lat": 51.5074, "lng": -0.1278},
        ]
        result = solve(places)
        self.assertAlmostEqual(result["total_distance_km"], 341 * 2, delta=20)

    def test_returns_all_places(self):
        places = [
            {"id": i, "lat": lat, "lng": lng}
            for i, (lat, lng) in enumerate([
                (48.8566, 2.3522),
                (51.5074, -0.1278),
                (52.3676, 4.9041),
            ])
        ]
        result = solve(places)
        self.assertEqual(len(result["ordered_places"]), 3)
        self.assertEqual(result["algorithm"], "nearest_neighbor+2opt")


if __name__ == "__main__":
    unittest.main()
