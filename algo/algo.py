"""
TSP Solver — Nearest Neighbor + 2-opt
Pipeline: distance matrix → greedy tour → 2-opt improvement
Distance formula: spherical law of cosines (π=3.141592, R=6378.197 km)
"""

import math
from typing import List, Dict, Tuple

PI: float = 3.141592
R_EARTH: float = 6378.197  # km
MAX_DAY_TRIP_KM: float = 200.0          # one-way distance limit for a day trip from a hotel
MAX_KM_COST_PER_HOTEL_SAVED: float = 200.0  # max extra km acceptable to eliminate one hotel


def _deg_to_rad(degrees: float) -> float:
    """Convert degrees to radians."""
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
    """Assign each node to its nearest medoid index."""
    return [min(range(len(medoids)), key=lambda m: dist[i][medoids[m]]) for i in range(len(dist))]


def _update_medoids(dist: List[List[float]], assignments: List[int], k: int) -> List[int]:
    """Recompute medoids by minimizing intra-cluster distance."""
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


def _is_valid_clustering(medoids: List[int], assignments: List[int], dist: List[List[float]]) -> bool:
    """Returns True if every city is within MAX_DAY_TRIP_KM of its assigned hotel."""
    medoid_set = set(medoids)
    for i in range(len(dist)):
        if i not in medoid_set:
            if dist[medoids[assignments[i]]][i] > MAX_DAY_TRIP_KM:
                return False
    return True


def _build_plan(k: int, places: List[Dict], dist: List[List[float]]) -> Dict:
    """Build a clustered tour plan for a given number of hotels."""
    medoids, assignments = cluster_cities(dist, k)
    valid = _is_valid_clustering(medoids, assignments, dist)
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
        "valid": valid,
        "hotels_circuit": [places[i] for i in ordered_hotels],
        "clusters": clusters,
        "circuit_distance_km": round(circuit_km, 3),
        "day_trips_distance_km": round(day_km, 3),
        "total_distance_km": round(circuit_km + day_km, 3),
    }


def _balanced_score(plan: Dict, worst_dist: float, best_dist: float, n: int) -> float:
    """
    Kept for informational display in cost_by_k only — not used for selection.
    Selection is now based solely on total_distance_km (minimum wins),
    followed by a greedy hotel merge to reduce hotel count.
    """
    dist_range  = worst_dist - best_dist or 1.0
    norm_dist   = (plan["total_distance_km"] - best_dist) / dist_range
    norm_hotels = (plan["k"] - 1) / (n - 1) if n > 1 else 0.0
    return 0.5 * norm_dist + 0.5 * norm_hotels


def _plan_total_km(clusters: List[Dict], dist: List[List[float]], idx_of: Dict) -> float:
    """Circuit km + all day-trip round-trip km for a given set of clusters."""
    hotel_idxs = [idx_of[_key(c["hotel"])] for c in clusters]
    _, circuit_km = _hotel_circuit(hotel_idxs, dist)
    day_km = sum(
        2 * dist[idx_of[_key(c["hotel"])]][idx_of[_key(city)]]
        for c in clusters for city in c["day_trips"]
    )
    return circuit_km + day_km


def _key(place: Dict) -> object:
    """Stable identifier for a place — prefers numeric id, falls back to name."""
    return place.get("id", place["name"])


def _greedy_merge_hotels(
    clusters: List[Dict],
    dist: List[List[float]],
    places: List[Dict],
) -> List[Dict]:
    """
    Post-processing pass: greedily merge clusters whose hotels are within
    MAX_DAY_TRIP_KM of each other, provided all cities in the merged cluster
    remain within MAX_DAY_TRIP_KM of the surviving hotel.

    This fixes the k-medoids bias toward high k: k-medoids minimises
    intra-cluster distance (solo city = 0), so it tends to give every city
    its own hotel even when grouping is clearly better. This pass corrects
    that by merging whenever the constraint is satisfied.
    """
    idx_of = {_key(p): i for i, p in enumerate(places)}

    changed = True
    while changed:
        changed = False
        for i in range(len(clusters)):
            for j in range(i + 1, len(clusters)):
                hi_idx = idx_of[_key(clusters[i]["hotel"])]
                hj_idx = idx_of[_key(clusters[j]["hotel"])]

                if dist[hi_idx][hj_idx] > MAX_DAY_TRIP_KM:
                    continue  # hotels too far apart — cannot merge

                # Cities that would move to cluster i if j is absorbed
                cities_j = [clusters[j]["hotel"]] + clusters[j]["day_trips"]
                # Cities that would move to cluster j if i is absorbed
                cities_i = [clusters[i]["hotel"]] + clusters[i]["day_trips"]

                can_absorb_j = all(dist[hi_idx][idx_of[_key(c)]] <= MAX_DAY_TRIP_KM for c in cities_j)
                can_absorb_i = all(dist[hj_idx][idx_of[_key(c)]] <= MAX_DAY_TRIP_KM for c in cities_i)

                if not can_absorb_j and not can_absorb_i:
                    continue

                # Build candidate cluster lists for each direction
                def _candidate_j() -> List[Dict]:
                    m = {"hotel": clusters[i]["hotel"], "day_trips": clusters[i]["day_trips"] + cities_j}
                    return [m if idx == i else c for idx, c in enumerate(clusters) if idx != j]

                def _candidate_i() -> List[Dict]:
                    m = {"hotel": clusters[j]["hotel"], "day_trips": clusters[j]["day_trips"] + cities_i}
                    return [m if idx == j else c for idx, c in enumerate(clusters) if idx != i]

                km_before = _plan_total_km(clusters, dist, idx_of)
                cost_j = (_plan_total_km(_candidate_j(), dist, idx_of) - km_before) if can_absorb_j else float("inf")
                cost_i = (_plan_total_km(_candidate_i(), dist, idx_of) - km_before) if can_absorb_i else float("inf")

                # Skip if both directions cost more km than the threshold allows
                if cost_j > MAX_KM_COST_PER_HOTEL_SAVED and cost_i > MAX_KM_COST_PER_HOTEL_SAVED:
                    continue

                # Pick the cheaper direction
                if cost_j <= cost_i:
                    clusters = _candidate_j()
                else:
                    clusters = _candidate_i()
                changed = True
                break
            if changed:
                break

    return clusters


def solve_clustered(places: List[Dict], max_hotels: int = None) -> Dict:
    """
    Finds the optimal hotel-based travel plan minimising:
      - total distance (hotel circuit + day-trip round-trips)
      - number of hotels

    Only plans where every city is within MAX_DAY_TRIP_KM of its hotel are
    considered valid. The score is computed on valid plans only, which prevents
    degenerate k=1 solutions where day trips would be unrealistically long.
    Fallback to all plans if no valid plan exists (edge case: single city).
    """
    n = len(places)
    dist = build_distance_matrix(places)
    k_range = range(1, (max_hotels or n) + 1)

    all_plans = [_build_plan(k, places, dist) for k in k_range]

    # Only score valid plans — eliminates the trap case
    valid_plans = [p for p in all_plans if p["valid"]]
    scored_plans = valid_plans if valid_plans else all_plans  # fallback

    worst_dist = max(p["total_distance_km"] for p in scored_plans)
    best_dist  = min(p["total_distance_km"] for p in scored_plans)

    # Select the plan with the shortest total distance among valid plans.
    # Hotel count reduction is handled by the greedy merge below.
    recommended = min(scored_plans, key=lambda p: p["total_distance_km"])
    scored_k = recommended["k"]  # k of the minimum-distance plan, before merge

    # Post-processing: merge hotels that are within MAX_DAY_TRIP_KM — fixes
    # k-medoids tendency to assign solo hotels to nearby cities.
    idx_of = {_key(p): i for i, p in enumerate(places)}
    merged_clusters = _greedy_merge_hotels(
        [{"hotel": c["hotel"], "day_trips": list(c["day_trips"])} for c in recommended["clusters"]],
        dist, places,
    )
    if len(merged_clusters) != scored_k:
        new_hotel_idxs  = [idx_of[_key(c["hotel"])] for c in merged_clusters]
        ordered_idxs, circuit_km = _hotel_circuit(new_hotel_idxs, dist)
        day_km = sum(
            2 * dist[idx_of[_key(c["hotel"])]][idx_of[_key(city)]]
            for c in merged_clusters for city in c["day_trips"]
        )
        hotel_to_cluster = {_key(c["hotel"]): c for c in merged_clusters}
        recommended = {
            **recommended,
            "k":                     len(merged_clusters),
            "hotels_circuit":        [places[i] for i in ordered_idxs],
            "clusters":              [hotel_to_cluster[_key(places[i])] for i in ordered_idxs],
            "circuit_distance_km":   round(circuit_km, 3),
            "day_trips_distance_km": round(day_km, 3),
            "total_distance_km":     round(circuit_km + day_km, 3),
        }

    return {
        "optimal_k":             recommended["k"],
        "scored_k":              scored_k,  # k that had the best score (before merge)
        "hotels_circuit":        recommended["hotels_circuit"],
        "clusters":              recommended["clusters"],
        "circuit_distance_km":   recommended["circuit_distance_km"],
        "day_trips_distance_km": recommended["day_trips_distance_km"],
        "total_distance_km":     recommended["total_distance_km"],
        "cost_by_k": [
            {
                "k":                 p["k"],
                "valid":             p["valid"],
                "total_distance_km": p["total_distance_km"],
                "score":             round(_balanced_score(p, worst_dist, best_dist, n), 4) if p["valid"] else None,
                "recommended":       p["k"] == scored_k,
            }
            for p in all_plans
        ],
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


if __name__ == "__main__":
    import sys
    import json as _json

    try:
        raw = sys.stdin.read()
        if not raw.strip():
            raise ValueError("No input data provided.")
        data = _json.loads(raw)
        # Accept plain list (legacy) or {"places": [...], "max_hotels": N}
        if isinstance(data, list):
            places, max_hotels = data, None
        else:
            places     = data.get("places", [])
            max_hotels = data.get("max_hotels")
        if not isinstance(places, list) or len(places) == 0:
            raise ValueError("places must be a non-empty list")
        print(_json.dumps(solve_clustered(places, max_hotels=max_hotels)))
    except Exception as exc:
        print(_json.dumps({"message": str(exc)}))
        sys.exit(1)
