# Technical Documentation — Travel Planning Application

**Authors:** Issam Atrari, Noé Labbé, Mohamed Amir Kolawolé Ali-Ligali  
**Date:** May 2026  
**Version:** 1.0  
**Project:** Box Certificative Finale — Travel Planning

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Technology Choices & Justifications](#3-technology-choices--justifications)
4. [Subject Interpretation](#4-subject-interpretation)
5. [Database Schema](#5-database-schema)
6. [API Endpoints](#6-api-endpoints)
7. [Algorithm Description](#7-algorithm-description)
8. [Installation & Setup](#8-installation--setup)
9. [Known Limitations & Future Improvements](#9-known-limitations--future-improvements)

---

## 1. Project Overview

The Travel Planning Application is a web-based tool that allows authenticated users to create travel itineraries by selecting cities to visit. The system automatically computes an optimised route using a custom clustering and TSP (Travelling Salesman Problem) algorithm, grouping nearby cities around shared hotel bases to minimise both total travel distance and the number of hotel changes.

**Core features:**
- Secure multi-account authentication with password hashing
- City search with automatic GPS coordinate retrieval via external API (OpenStreetMap Nominatim)
- Automated optimised tour generation (hotel-based clustering + TSP)
- Manual itinerary adjustment without re-optimisation
- Tour sharing with public/private visibility control

---

## Task Allocation

- **Issam:** Python algorithm, algorithm tests, Flask API sharing
- **Noe:** Travel database setup, authentication (login/register/logout), dashboard creation, main page (index.php), PHP tests
- **Amir:** OpenStreetMap API usage, places management, Python-PHP integration (with Issam)

---

## 2. System Architecture

### 2.1 Three-Tier Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
│              PHP pages (server-side rendered HTML)           │
│  login · register · dashboard · places · generate · results  │
└───────────────────────┬──────────────────────────────────────┘
                        │ HTTP (forms / fetch)
┌───────────────────────▼──────────────────────────────────────┐
│                    BUSINESS LAYER                            │
│              PHP API endpoints + Models + Services           │
│   api/geocode.php  ·  api/generate_trip.php                  │
│   models/PlaceManager  ·  models/TravelManager               │
│   Services/GeocodingService                                  │
└──────────┬─────────────────────────────┬─────────────────────┘
           │ PDO                         │ proc_open (stdin/stdout pipe)
┌──────────▼──────────┐       ┌──────────▼──────────────────────┐
│      MySQL DB        │       │     Python Engine (algo.py)     │
│  users               │       │  - build_distance_matrix        │
│  travels             │       │  - nearest_neighbor + 2-opt     │
│  places              │       │  - k-medoids clustering         │
│  results             │       │  - greedy hotel merge           │
└─────────────────────┘       └─────────────────────────────────┘
                    ┌──────────────────────────────────────────┐
                    │   OpenStreetMap Nominatim (external API)  │
                    │   city name → latitude / longitude        │
                    └──────────────────────────────────────────┘
```

### 2.2 Directory Structure

```
box-certificative-2026/
├── algo/
│   ├── algo.py              # Core optimisation engine (TSP + clustering)
│   ├── api.py               # Alternative Flask REST server
│   └── algotest.py          # Unit tests (unittest)
├── api/
│   ├── geocode.php          # Geocoding REST endpoint
│   ├── generate_trip.php    # Tour generation endpoint (calls algo.py)
│   └── optimize_trip.php    # Alternative endpoint (calls Flask server)
├── config/
│   └── database.php         # PDO MySQL connection singleton
├── doc/
│   ├── technical_documentation.md
│   └── user_manual.md
├── models/
│   ├── PlaceManager.php     # CRUD for places (cities)
│   └── TravelManager.php    # CRUD for trips + share token management
├── pages/
│   ├── auth/
│   │   ├── login.php        # Login + redirect to register
│   │   └── register.php     # Account creation
│   ├── dashboard.php        # Trip list + creation form
│   ├── places.php           # Add / delete cities for a trip
│   ├── generate.php         # Launch optimisation, store result in session
│   ├── results.php          # Display itinerary + manual reordering
│   ├── view_trip.php        # Public/private shared trip view
│   └── logout.php           # Session destruction
├── public/assets/css/
│   └── style.css            # Global CSS (green/blue pastel palette)
└── Services/
    └── GeocodingService.php # Nominatim wrapper
```

### 2.3 Data Flow — Tour Generation

```
User submits generate.php form
         │
         ▼
generate.php (POST handler)
  ├─ Loads places from MySQL via PlaceManager
  ├─ Builds JSON payload: {"places": [...], "max_hotels": N}
  └─ HTTP POST ──► api/generate_trip.php
                            │
                            ▼
                   generate_trip.php
                     ├─ Validates request
                     ├─ Locates algo.py + Python executable
                     ├─ proc_open: spawns Python process
                     ├─ Writes JSON payload to stdin pipe
                     ├─ Reads JSON result from stdout pipe
                     └─ Returns JSON to generate.php
                            │
                   algo.py (__main__ block)
                     ├─ Reads places from stdin
                     ├─ build_distance_matrix()  [O(n²)]
                     ├─ For k = 1 to n:
                     │    ├─ k-medoids clustering
                     │    ├─ validity check (200km constraint)
                     │    └─ compute total distance
                     ├─ Select minimum-distance valid plan
                     ├─ _greedy_merge_hotels() [reduce k]
                     └─ Print JSON result to stdout
                            │
                            ▼
generate.php
  ├─ Stores result in $_SESSION["trip_result"]
  ├─ Saves distance to results table
  └─ Redirects to results.php
```

---

## 3. Technology Choices & Justifications

| Technology | Role | Justification |
|---|---|---|
| **PHP 8** | Web backend, routing, session management | Native support in Laragon; mature PDO layer for secure DB access; team proficiency |
| **MySQL** | Relational data persistence | Well-suited to the users/travels/places relational model; ACID-compliant; integrated with Laragon |
| **Python 3** | Algorithmic computation engine | Superior for mathematical/numerical code; cleaner syntax for complex algorithms; `math` standard library sufficient (no external dependencies for core algo) |
| **OpenStreetMap Nominatim** | Geocoding API | Free, no API key required, globally accurate; satisfies the subject requirement of using an external API for coordinate retrieval |
| **proc_open (PHP → Python)** | Inter-process communication | Passes data via stdin/stdout pipe — avoids shell argument escaping issues on Windows (unlike `shell_exec` with `escapeshellarg`) |
| **Flask** | Alternative Python REST server | Available as `algo/api.py` for deployments preferring a persistent microservice over per-request process spawning |
| **Laragon** | Local development stack | Zero-configuration on Windows; bundles Apache, PHP 8, MySQL, Python |
| **Vanilla CSS** | UI styling | Lightweight; no framework dependency; consistent with the project's minimal footprint philosophy |

### 3.1 Two Python Integration Modes

The application supports two ways to call the Python engine, demonstrating architectural flexibility:

**Mode A — proc_open (default):**  
`generate_trip.php` spawns `algo.py` as a child process per request, communicating via stdin/stdout JSON pipes. No background service required.

**Mode B — Flask REST server:**  
`optimize_trip.php` sends HTTP POST requests to `algo/api.py` running on port 5000. Better for high-frequency use or containerised deployments.

The default is Mode A because it requires no separate service to manage during development and demonstration.

---

## 4. Subject Interpretation

### 4.1 Hotel-Based Tour Model

The subject defines a tour as visiting all places exactly once and returning to the start. We extended this to a **hotel-cluster model** which better reflects real travel:

> The traveller stays at a hotel in a central city and makes daily round-trip excursions to nearby cities. When all nearby cities are visited, they move to the next hotel base.

This model minimises total distance *and* hotel changes simultaneously — two naturally competing objectives resolved by our algorithm.

### 4.2 Tour Score (Distance Formula)

As specified in the subject, tour quality equals total distance. In our model:

```
total_distance = circuit_between_hotels + Σ(2 × distance(hotel, excursion_city))
```

The distance formula used exactly matches the subject specification:

```
D(Va, Vb) = R_earth × arccos(sin(lat_a) × sin(lat_b) + cos(lat_a) × cos(lat_b) × cos(lng_b − lng_a))
```

with **π = 3.141592** and **R_earth = 6378.197 km**, all coordinates converted to radians.

### 4.3 Day-Trip Constraint (200 km)

We added a practical constraint beyond the subject specification: **maximum 200 km one-way** for any day trip from a hotel. Plans where any city exceeds this threshold are marked invalid and excluded from selection. This prevents unrealistic itineraries (e.g., a "day trip" of 800 km round-trip) that would technically minimise hotel count but are physically impossible to accomplish in a single day.

### 4.4 Sharing Levels

| Level | Access rule |
|---|---|
| **Public** | Anyone with the URL `view_trip.php?token=<token>` can view the trip |
| **Private** | Only the trip owner, when logged in, can view it; others receive HTTP 401/403 |

Tokens are generated using `random_bytes(16)` → `bin2hex`, producing a 32-character hexadecimal string with 128 bits of entropy.

---

## 5. Database Schema

### 5.1 Tables

```sql
-- User accounts
CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    pseudo   VARCHAR(100) NOT NULL,
    email    VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL          -- bcrypt hash
);

-- Travel projects
CREATE TABLE travels (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    titre       VARCHAR(255) NOT NULL,
    statut      ENUM('private','public') DEFAULT 'private',
    share_token VARCHAR(32) UNIQUE,         -- random_bytes(16) → bin2hex
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cities / places of interest
CREATE TABLE places (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    travel_id  INT NOT NULL,
    nom        VARCHAR(255) NOT NULL,
    latitude   DECIMAL(10,7) NOT NULL,
    longitude  DECIMAL(10,7) NOT NULL,
    FOREIGN KEY (travel_id) REFERENCES travels(id) ON DELETE CASCADE
);

-- Saved optimisation results
CREATE TABLE results (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    travel_id       INT NOT NULL,
    distance_totale FLOAT,
    FOREIGN KEY (travel_id) REFERENCES travels(id) ON DELETE CASCADE
);
```

### 5.2 Security Measures

- Passwords stored as **bcrypt hashes** (`password_hash(PASSWORD_BCRYPT)`)
- All queries use **PDO prepared statements** — SQL injection protected
- Share tokens generated with `random_bytes()` — cryptographically secure
- Session data validates ownership before displaying private trips

---

## 6. API Endpoints

### `GET /api/geocode.php?q=<city>`

Converts a city name to GPS coordinates.

**Success response:**
```json
{ "status": "success", "data": { "name": "Lyon", "lat": 45.764, "lon": 4.8357 } }
```

**Error response:**
```json
{ "status": "error", "message": "Location not found." }
```

---

### `POST /api/generate_trip.php`

Launches the Python optimisation engine and returns the hotel-based plan.

**Request:**
```json
{
  "places": [
    { "id": 1, "name": "Paris",      "lat": 48.8566, "lng": 2.3522 },
    { "id": 2, "name": "Versailles", "lat": 48.8048, "lng": 2.1203 },
    { "id": 3, "name": "Lyon",       "lat": 45.7640, "lng": 4.8357 }
  ],
  "max_hotels": 2
}
```

**Response:**
```json
{
  "optimal_k": 2,
  "scored_k": 2,
  "hotels_circuit": [ {"id":1,"name":"Paris","lat":48.8566,"lng":2.3522}, {"id":3,...} ],
  "clusters": [
    { "hotel": {"id":1,"name":"Paris",...}, "day_trips": [{"id":2,"name":"Versailles",...}] },
    { "hotel": {"id":3,"name":"Lyon",...},  "day_trips": [] }
  ],
  "circuit_distance_km": 392.5,
  "day_trips_distance_km": 50.2,
  "total_distance_km": 442.7,
  "cost_by_k": [
    { "k":1, "valid":false, "total_distance_km":800.0, "score":null, "recommended":false },
    { "k":2, "valid":true,  "total_distance_km":442.7, "score":0.5,  "recommended":true  },
    { "k":3, "valid":true,  "total_distance_km":460.0, "score":0.8,  "recommended":false }
  ]
}
```

**Fields:**
- `optimal_k` — number of hotels after proximity merge
- `scored_k` — k of the minimum-distance valid plan before merge
- `hotels_circuit` — hotels in optimal visiting order
- `clusters` — each hotel with its day-trip cities
- `cost_by_k` — comparison table for all k values tested

---

### `POST /api/optimize_trip.php`

Alternative endpoint that forwards the request to the Flask server (`algo/api.py`) running on `http://localhost:5000/optimize`. Requires `python algo/api.py` to be running in a separate terminal.

---

## 7. Algorithm Description

### 7.1 Configuration Constants

| Constant | Value | Purpose |
|---|---|---|
| `PI` | 3.141592 | Per subject specification |
| `R_EARTH` | 6378.197 km | Earth radius for spherical distance |
| `MAX_DAY_TRIP_KM` | 200.0 km | Maximum one-way excursion distance |
| `MAX_KM_COST_PER_HOTEL_SAVED` | 200.0 km | Max km overhead accepted to eliminate one hotel during merge |

### 7.2 Step 1 — Distance Matrix

```
FOR each pair of cities (i, j):
    dist[i][j] = spherical_distance(city_i, city_j)
```

**Complexity: O(n²)**

The spherical law of cosines formula uses floating-point clamping (`max(-1, min(1, inner))`) to guard against domain errors in `arccos` caused by floating-point precision.

### 7.3 Step 2 — Nearest Neighbor (TSP Phase 1)

```
tour = [start_city]
WHILE unvisited cities remain:
    go to the closest unvisited city
RETURN tour
```

**Complexity: O(n²)**  
Produces an initial tour approximately 20–25% above optimal. Used as warm start for 2-opt.

### 7.4 Step 3 — 2-opt Local Search (TSP Phase 2)

```
REPEAT UNTIL no improvement found:
    FOR all pairs of edges (a→b) and (c→d):
        IF swapping produces shorter tour:  (dist[a][c] + dist[b][d] < dist[a][b] + dist[c][d])
            reverse the sub-path between b and c
```

**Complexity: O(n²) per pass, O(n³) worst-case total**  
Guarantees a local optimum: no single edge-pair swap can further reduce the tour length.

### 7.5 Step 4 — k-Medoids Clustering

k-medoids is chosen over k-means because **hotels must be located in actual visited cities**, not at a computed geographic centroid.

```
FOR each initialisation strategy (multiple starting configurations):
    medoids = k initial cities chosen by strategy
    REPEAT up to 100 iterations:
        FOR each city: assign it to the nearest medoid
        FOR each cluster: recompute medoid as the city minimising intra-cluster distance sum
        IF medoids unchanged: converge
    EVALUATE total intra-cluster cost
RETURN best (medoids, assignments) found across all initialisations
```

**Complexity: O(k² × n) per call** (multiple initialisations × iterations × n)  
Multiple initialisations reduce the risk of poor local minima.

### 7.6 Step 5 — Validity Check (200 km Constraint)

```
FOR each non-hotel city:
    IF distance(city, its_hotel) > MAX_DAY_TRIP_KM:
        mark plan as INVALID
```

Invalid plans are excluded from selection, preventing unrealistic day trips.

### 7.7 Step 6 — Plan Selection

```
valid_plans = all plans where every city is within 200km of its hotel
IF valid_plans is empty: use all_plans (fallback for edge cases)

recommended = argmin(valid_plans, key=total_distance_km)
```

The plan with the shortest total distance (circuit + all day trips) is chosen. No normalisation or weighting is needed — the 200 km constraint already eliminates degenerate low-k solutions.

### 7.8 Step 7 — Greedy Hotel Merge

k-medoids assigns a solo hotel to every city by default (intra-cluster cost = 0). This post-processing pass consolidates nearby hotels:

```
REPEAT UNTIL no merge performed:
    FOR each pair of hotels (A, B) where distance(A, B) ≤ 200 km:
        IF all cities of B fit within 200 km of A:
            km_cost_A = total_distance_if_A_absorbs_B - current_total
        IF all cities of A fit within 200 km of B:
            km_cost_B = total_distance_if_B_absorbs_A - current_total
        
        best_cost = min(km_cost_A, km_cost_B)
        IF best_cost ≤ MAX_KM_COST_PER_HOTEL_SAVED:
            perform the merge in the cheaper direction
            BREAK (restart outer loop)
```

**Direction selection:** When both directions are valid, the surviving hotel is chosen as the one whose resulting hotel circuit is shorter — i.e. the hotel geometrically best-positioned relative to the remaining destinations.

**Complexity: O(k⁴) worst case** — acceptable for k ≤ 20.

### 7.9 Overall Complexity Summary

| Phase | Complexity |
|---|---|
| Distance matrix | O(n²) |
| Build all plans (k = 1..n) | O(n³) |
| Select minimum-distance plan | O(n) |
| Greedy hotel merge | O(k⁴) |
| **Total pipeline** | **O(n³)** |

For n ≤ 20 cities: response time under 1 second. Tested up to n = 18 cities in under 500 ms.

---

## 8. Installation & Setup

### 8.1 Prerequisites

| Software | Minimum Version | Notes |
|---|---|---|
| Laragon | 6.0 | Apache 2.4, PHP 8.0, MySQL 8.0 |
| Python | 3.8 | Must be accessible as `python` in system PATH |
| Git | 2.x | For cloning and version control |

### 8.2 Step-by-Step Installation

**1. Clone the repository**
```bash
git clone <repository_url>
```

**2. Move to Laragon's web root**
```
Place the folder at: C:\laragon\www\box-certificative-2026\
```

**3. Create the database**

In phpMyAdmin (Laragon tray → phpMyAdmin) or any MySQL client:

```sql
CREATE DATABASE travel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE travel;

CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    pseudo   VARCHAR(100) NOT NULL,
    email    VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE travels (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    titre       VARCHAR(255) NOT NULL,
    statut      ENUM('private','public') DEFAULT 'private',
    share_token VARCHAR(32) UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE places (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    travel_id  INT NOT NULL,
    nom        VARCHAR(255) NOT NULL,
    latitude   DECIMAL(10,7) NOT NULL,
    longitude  DECIMAL(10,7) NOT NULL,
    FOREIGN KEY (travel_id) REFERENCES travels(id) ON DELETE CASCADE
);

CREATE TABLE results (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    travel_id       INT NOT NULL,
    distance_totale FLOAT,
    FOREIGN KEY (travel_id) REFERENCES travels(id) ON DELETE CASCADE
);
```

**4. Check database credentials**

Open `config/database.php`:
```php
$bdd = new PDO('mysql:host=localhost;dbname=travel;charset=utf8', 'root', '');
```
Update credentials if your MySQL setup differs from the Laragon defaults.

**5. Verify Python is accessible**
```bash
python --version
# Expected: Python 3.x.x
```

**6. Start Laragon and open the application**
- Start Laragon → click "Start All"
- Open browser: `http://localhost/box-certificative-2026/pages/auth/login.php`

### 8.3 Running Unit Tests

```bash
cd C:\laragon\www\box-certificative-2026
python algo/algotest.py
```

Expected output:
```
....................
----------------------------------------------------------------------
Ran 20 tests in 0.XXXs
OK
```

---

## 9. Known Limitations & Future Improvements

### 9.1 Current Limitations

| Area | Limitation |
|---|---|
| Results persistence | Trip results are stored only in `$_SESSION`. Closing the browser loses the result. Full JSON persistence in MySQL would enable history. |
| Day-trip threshold | `MAX_DAY_TRIP_KM = 200` is a hardcoded constant. A user-configurable value would increase flexibility. |
| k-medoids quality | The algorithm is heuristic. Multiple initialisations mitigate poor local minima but do not guarantee global optimality. |
| No map visualisation | Results are text-only. A Leaflet.js or Google Maps integration would dramatically improve user experience. |
| Nominatim rate limit | OpenStreetMap enforces 1 request/second. Adding a city too quickly may cause brief delays. |
| Single language | The web interface is in French. Internationalisation (i18n) was not in scope. |

### 9.2 Proposed Improvements

- **Lin-Kernighan or Or-opt heuristics** for TSP: produce higher quality tours than 2-opt at similar complexity
- **DBSCAN clustering**: density-based alternative that auto-determines cluster count without specifying k
- **Interactive map** with Leaflet.js: drag cities, see the route rendered on a real map
- **Full result persistence**: store the complete JSON plan in a `trip_results` table with timestamps
- **User-adjustable constraints**: expose `MAX_DAY_TRIP_KM` and `MAX_KM_COST_PER_HOTEL_SAVED` as form inputs
- **Trip editing**: allow users to move cities between hotel clusters via the UI
