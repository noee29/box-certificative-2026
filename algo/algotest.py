import unittest
from algo import (
    _deg_to_rad,
    spherical_distance,
    build_distance_matrix,
    tour_distance,
    nearest_neighbor,
    two_opt,
    solve,
    cluster_cities,
    _hotel_circuit,
    _day_trips_distance,
    _build_plan,
    _balanced_score,
    solve_clustered,
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


PLACES_6 = [
    {"id": 0, "lat": 48.8566, "lng":  2.3522, "name": "Paris"},
    {"id": 1, "lat": 47.3220, "lng":  5.0415, "name": "Dijon"},
    {"id": 2, "lat": 45.7640, "lng":  4.8357, "name": "Lyon"},
    {"id": 3, "lat": 40.4168, "lng": -3.7038, "name": "Madrid"},
    {"id": 4, "lat": 41.3851, "lng":  2.1734, "name": "Barcelone"},
    {"id": 5, "lat": 37.3891, "lng": -5.9845, "name": "Seville"},
]


class TestClusterCities(unittest.TestCase):
    def setUp(self):
        self.dist = build_distance_matrix(PLACES_6)

    def test_returns_k_medoids(self):
        medoids, _ = cluster_cities(self.dist, 2)
        self.assertEqual(len(medoids), 2)

    def test_medoids_are_valid_indices(self):
        medoids, _ = cluster_cities(self.dist, 2)
        for m in medoids:
            self.assertIn(m, range(len(PLACES_6)))

    def test_all_cities_assigned(self):
        _, assignments = cluster_cities(self.dist, 2)
        self.assertEqual(len(assignments), len(PLACES_6))

    def test_assignments_reference_valid_clusters(self):
        _, assignments = cluster_cities(self.dist, 2)
        for a in assignments:
            self.assertIn(a, range(2))


class TestHotelCircuit(unittest.TestCase):
    def setUp(self):
        self.dist = build_distance_matrix(PLACES_6)

    def test_single_hotel_zero_distance(self):
        _, km = _hotel_circuit([0], self.dist)
        self.assertEqual(km, 0.0)

    def test_two_hotels_round_trip(self):
        _, km = _hotel_circuit([0, 3], self.dist)
        expected = 2 * self.dist[0][3]
        self.assertAlmostEqual(km, expected, places=2)

    def test_three_hotels_visits_all(self):
        ordered, _ = _hotel_circuit([0, 3, 4], self.dist)
        self.assertEqual(sorted(ordered), [0, 3, 4])


class TestDayTripsDistance(unittest.TestCase):
    def setUp(self):
        self.dist = [
            [0,  50, 80],
            [50,  0, 60],
            [80, 60,  0],
        ]

    def test_single_hotel_all_day_trips(self):
        # hôtel = ville 0, villes 1 et 2 sont des day trips
        medoids     = [0]
        assignments = [0, 0, 0]
        km = _day_trips_distance(medoids, assignments, self.dist)
        self.assertAlmostEqual(km, 2 * 50 + 2 * 80)

    def test_no_day_trips_when_all_hotels(self):
        medoids     = [0, 1, 2]
        assignments = [0, 1, 2]
        km = _day_trips_distance(medoids, assignments, self.dist)
        self.assertAlmostEqual(km, 0.0)


class TestBuildPlan(unittest.TestCase):
    def setUp(self):
        self.dist = build_distance_matrix(PLACES_6)

    def test_returns_required_keys(self):
        plan = _build_plan(2, PLACES_6, self.dist)
        for key in ("k", "hotels_circuit", "clusters", "circuit_distance_km",
                    "day_trips_distance_km", "total_distance_km"):
            self.assertIn(key, plan)

    def test_k_matches(self):
        plan = _build_plan(2, PLACES_6, self.dist)
        self.assertEqual(plan["k"], 2)

    def test_total_is_sum(self):
        plan = _build_plan(2, PLACES_6, self.dist)
        self.assertAlmostEqual(
            plan["total_distance_km"],
            round(plan["circuit_distance_km"] + plan["day_trips_distance_km"], 3),
        )


class TestBalancedScore(unittest.TestCase):
    def test_best_dist_scores_zero_on_distance(self):
        plan = {"total_distance_km": 100.0, "k": 1}
        score = _balanced_score(plan, worst_dist=200.0, best_dist=100.0, n=5)
        self.assertAlmostEqual(score, 0.0 * 0.5 + 0.0 * 0.5, places=5)

    def test_worst_dist_scores_half_on_distance(self):
        plan = {"total_distance_km": 200.0, "k": 1}
        score = _balanced_score(plan, worst_dist=200.0, best_dist=100.0, n=5)
        self.assertAlmostEqual(score, 0.5 * 1.0 + 0.5 * 0.0, places=5)


class TestSolveClustered(unittest.TestCase):
    def test_returns_required_keys(self):
        result = solve_clustered(PLACES_6)
        for key in ("optimal_k", "hotels_circuit", "clusters",
                    "circuit_distance_km", "day_trips_distance_km",
                    "total_distance_km", "cost_by_k"):
            self.assertIn(key, result)

    def test_optimal_k_in_valid_range(self):
        result = solve_clustered(PLACES_6)
        self.assertIn(result["optimal_k"], range(1, len(PLACES_6) + 1))

    def test_max_hotels_respected(self):
        result = solve_clustered(PLACES_6, max_hotels=2)
        self.assertEqual(result["optimal_k"], 2)

    def test_cost_by_k_length(self):
        result = solve_clustered(PLACES_6)
        self.assertEqual(len(result["cost_by_k"]), len(PLACES_6))

    def test_all_cities_covered(self):
        result = solve_clustered(PLACES_6, max_hotels=2)
        all_cities = {result["clusters"][0]["hotel"]["id"],
                      result["clusters"][1]["hotel"]["id"]}
        for c in result["clusters"]:
            for city in c["day_trips"]:
                all_cities.add(city["id"])
        self.assertEqual(len(all_cities), len(PLACES_6))


if __name__ == "__main__":
    unittest.main()
