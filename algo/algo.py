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

def solve(places: List[Dict]) -> Dict:
    """
    Full TSP solver entry point.git

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
