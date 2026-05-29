"""
TSP Solver — Nearest Neighbor + 2-opt
Pipeline: distance matrix → greedy tour → 2-opt improvement
Distance formula: spherical law of cosines (π=3.141592, R=6378.197 km)
"""

import math
from typing import List, Dict, Tuple

PI: float = 3.141592
R_EARTH: float = 6378.197  # km


def _deg_to_rad(degrees: float) -> float:
    return degrees * PI / 180.0


def spherical_distance(lat_a: float, lon_a: float, lat_b: float, lon_b: float) -> float:
    """Great-circle distance in km between two points (decimal degrees)."""
    lat_a_r, lon_a_r = _deg_to_rad(lat_a), _deg_to_rad(lon_a)
    lat_b_r, lon_b_r = _deg_to_rad(lat_b), _deg_to_rad(lon_b)
    inner = (
        math.sin(lat_a_r) * math.sin(lat_b_r) + math.cos(lat_a_r) * math.cos(lat_b_r) * 
        math.cos(lon_b_r - lon_a_r)
    )
    # Clamp to [-1, 1] to guard against floating-point drift before arccos
    return R_EARTH * math.acos(max(-1.0, min(1.0, inner)))


def build_distance_matrix(places: List[Dict]) -> List[List[float]]:
    """Return a symmetric N×N matrix of km distances between all places."""
    n = len(places)
    dist = [[0.0] * n for _ in range(n)]
    for i in range(n):
        for j in range(i + 1, n):
            d = spherical_distance(
                places[i]["lat"], places[i]["lng"],
                places[j]["lat"], places[j]["lng"],
            )
            dist[i][j] = d
            dist[j][i] = d
    return dist

def tour_distance(tour: List[int], dist: List[List[float]]) -> float:
    """Total length (km) of a closed tour (returns to start)."""
    n = len(tour)
    return sum(dist[tour[i]][tour[(i + 1) % n]] for i in range(n))


def nearest_neighbor(dist: List[List[float]], start: int = 0) -> List[int]:
    """
    Phase 1 — Greedy tour construction. O(n²).
    From *start*, repeatedly visit the closest unvisited place.
    """
    n = len(dist)
    unvisited = set(range(n))
    tour = [start]
    unvisited.remove(start)
    while unvisited:
        current = tour[-1]
        nearest = min(unvisited, key=lambda j: dist[current][j])
        tour.append(nearest)
        unvisited.remove(nearest)
    return tour

def two_opt(tour: List[int], dist: List[List[float]]) -> Tuple[List[int], float]:
    """
    Phase 2 — 2-opt local search. O(n²) per pass.
    Reverses sub-paths to remove crossing edges until no improvement is found.
    Returns (improved_tour, total_distance_km).
    """
    best = tour[:]
    improved = True
    while improved:
        improved = False
        n = len(best)
        for i in range(n - 1):
            for j in range(i + 2, n):
                if j == n - 1 and i == 0:
                    continue
                a, b = best[i], best[i + 1]
                c, d = best[j], best[(j + 1) % n]
                if (dist[a][b] + dist[c][d]) - (dist[a][c] + dist[b][d]) > 1e-10:
                    best[i + 1: j + 1] = best[i + 1: j + 1][::-1]
                    improved = True
    return best, tour_distance(best, dist)

def _assign_clusters(dist: List[List[float]], medoids: List[int]) -> List[int]:
    return [min(range(len(medoids)), key=lambda m: dist[i][medoids[m]]) for i in range(len(dist))]


def _update_medoids(dist: List[List[float]], assignments: List[int], k: int) -> List[int]:
    n = len(dist)
    new_medoids = []
    for m in range(k):
        cluster = [i for i in range(n) if assignments[i] == m]
        if not cluster:
            new_medoids.append(0)
            continue
        new_medoids.append(min(cluster, key=lambda c: sum(dist[c][j] for j in cluster)))
    return new_medoids


def cluster_cities(dist: List[List[float]], k: int) -> Tuple[List[int], List[int]]:
    """k-medoids clustering. Returns (medoids, assignments)."""
    n = len(dist)
    best_medoids, best_assignments, best_cost = None, None, float("inf")

    inits = [list(range(0, n, max(1, n // k)))[:k]]
    for start in range(1, k + 1):
        inits.append([(start * i) % n for i in range(k)])

    for init in inits:
        medoids = list(dict.fromkeys(init))[:k]
        while len(medoids) < k:
            medoids.append((medoids[-1] + 1) % n)
        for _ in range(100):
            assignments = _assign_clusters(dist, medoids)
            new_medoids = _update_medoids(dist, assignments, k)
            if new_medoids == medoids:
                break
            medoids = new_medoids
        cost = sum(dist[medoids[assignments[i]]][i] for i in range(n) if i not in medoids)
        if cost < best_cost:
            best_cost = cost
            best_medoids = medoids[:]
            best_assignments = assignments[:]

    return best_medoids, best_assignments


def _hotel_circuit(hotel_indices: List[int], dist: List[List[float]]) -> Tuple[List[int], float]:
    """TSP circuit through hotels. Returns (ordered hotel indices, km)."""
    k = len(hotel_indices)
    if k <= 1:
        return hotel_indices, 0.0
    if k == 2:
        return hotel_indices, round(2 * dist[hotel_indices[0]][hotel_indices[1]], 3)
    sub = [[dist[hotel_indices[i]][hotel_indices[j]] for j in range(k)] for i in range(k)]
    optimized, total = two_opt(nearest_neighbor(sub, start=0), sub)
    return [hotel_indices[i] for i in optimized], total


def _day_trips_distance(medoids: List[int], assignments: List[int], dist: List[List[float]]) -> float:
    """Sum of all round-trip distances from each hotel to its assigned cities."""
    return sum(
        2 * dist[medoids[assignments[i]]][i]
        for i in range(len(dist))
        if i not in medoids
    )


def _build_plan(k: int, places: List[Dict], dist: List[List[float]]) -> Dict:
    medoids, assignments = cluster_cities(dist, k)
    ordered_hotels, circuit_km = _hotel_circuit(medoids, dist)
    day_km = _day_trips_distance(medoids, assignments, dist)
    clusters = []
    for m_idx, hotel_idx in enumerate(medoids):
        clusters.append({
            "hotel": places[hotel_idx],
            "day_trips": [
                places[i] for i, a in enumerate(assignments)
                if a == m_idx and i != hotel_idx
            ],
        })
    return {
        "k": k,
        "hotels_circuit": [places[i] for i in ordered_hotels],
        "clusters": clusters,
        "circuit_distance_km": round(circuit_km, 3),
        "day_trips_distance_km": round(day_km, 3),
        "total_distance_km": round(circuit_km + day_km, 3),
    }


def solve(places: List[Dict]) -> Dict:
    """
    Full TSP solver entry point.

    Input:  [{"id": …, "name": …, "lat": float, "lng": float}, …]
    Output: {"ordered_places": […], "total_distance_km": float, "algorithm": str}
    """
    n = len(places)

    if n <= 1:
        return {"ordered_places": places, "total_distance_km": 0.0, "algorithm": "nearest_neighbor+2opt"}

    if n == 2:
        d = spherical_distance(places[0]["lat"], places[0]["lng"], places[1]["lat"], places[1]["lng"])
        return {"ordered_places": places, "total_distance_km": round(2 * d, 3), "algorithm": "nearest_neighbor+2opt"}

    dist = build_distance_matrix(places)
    initial_tour = nearest_neighbor(dist, start=0)
    optimized_tour, total_km = two_opt(initial_tour, dist)

    return {
        "ordered_places": [places[i] for i in optimized_tour],
        "total_distance_km": round(total_km, 3),
        "algorithm": "nearest_neighbor+2opt",
    }
