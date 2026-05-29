# User Manual — Travel Planning Application

**Authors:** Issam Atrari, Noé Labbé, Mohamed Amir Kolawolé Ali-Ligali  
**Date:** May 2026  
**Version:** 1.0

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Prerequisites & Installation](#2-prerequisites--installation)
3. [Getting Started](#3-getting-started)
4. [Creating a Trip](#4-creating-a-trip)
5. [Adding Cities](#5-adding-cities)
6. [Generating the Optimised Tour](#6-generating-the-optimised-tour)
7. [Understanding Your Itinerary](#7-understanding-your-itinerary)
8. [Adjusting Your Itinerary Manually](#8-adjusting-your-itinerary-manually)
9. [Sharing a Trip](#9-sharing-a-trip)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Introduction

The Travel Planning Application helps you build optimised travel itineraries. Instead of simply listing cities in a route, the application groups cities around **hotel bases**: you sleep at a central hotel and make day trips to nearby cities, returning each evening. This minimises the number of times you change hotels while keeping total travel distance as short as possible.

**Example:**  
You want to visit Paris, Versailles, Chartres, Lyon, and Annecy.  
The application might suggest:
- **Hotel in Paris** → day trip to Versailles (50 km), day trip to Chartres (90 km)
- **Hotel in Lyon** → day trip to Annecy (140 km)

Total: 2 hotels, far fewer hotel changes than visiting each city separately.

---

## 2. Prerequisites & Installation

### 2.1 Requirements

- **Laragon** (Windows) with Apache, PHP 8, and MySQL running
- **Python 3.8+** installed and accessible as `python` in your terminal
- A modern web browser (Chrome, Firefox, Edge)

### 2.2 Installation

1. Place the project folder in Laragon's web root:
   ```
   C:\laragon\www\box-certificative-2026\
   ```

2. Create the database. Open phpMyAdmin (Laragon tray → phpMyAdmin) and run the SQL from `doc/technical_documentation.md` section 8.2.

3. Start Laragon → click **"Start All"**.

4. Open your browser and navigate to:
   ```
   http://localhost/Box%20certificative%202026/pages/
   ```

---

## 3. Getting Started

### 3.1 Create an Account

1. On the login page, click **"Créer un compte"**.
2. Fill in your **pseudo** (display name), **email address**, and **password**.
3. Click **"Créer le compte"**.
4. Sign in with your new credentials.

### 3.2 Log In

1. Enter your **email** and **password**.
2. Click **"Se connecter"**.
3. You will be taken to your **Dashboard**.

### 3.3 Log Out

Click the **"Déconnexion"** button at the top of any page. Your session will be terminated immediately.

---

## 4. Creating a Trip

From the Dashboard:

1. Under **"Nouveau voyage"**, enter a name for your trip (e.g., "Japan 2026", "Weekend France").
2. Choose the **visibility**:
   - **Privé** — only you can view this trip (requires login to access the shared link)
   - **Public** — anyone with the link can view it, no login required
3. Click **"Créer"**.

Your new trip appears in the **"Voyages existants"** list below.

---

## 5. Adding Cities

1. From the Dashboard, click **"Gérer les lieux"** next to your trip.
2. In the **"Ajouter une ville"** field, type the name of a city (e.g., "Lyon", "Tokyo", "New York").
3. Click **"Ajouter"**.
4. The application searches OpenStreetMap for the city's GPS coordinates. If found, the city appears in the list with its latitude and longitude.

**Tips:**
- Be specific if a city name is ambiguous: type "Lyon, France" or "Springfield, Illinois".
- You can add as many cities as you wish before generating the tour.
- To **remove a city**, click the **"Supprimer"** button next to it and confirm.

**Minimum required:** At least **2 cities** are needed to generate a tour.

---

## 6. Generating the Optimised Tour

1. From the Dashboard, click **"Générer le tour"** next to your trip, or click the link at the bottom of the places page.
2. You will see the list of cities you added.
3. *(Optional)* Enter a **maximum number of hotels** in the input field. Leave it blank to let the algorithm decide automatically.
4. Click **"Générer le tour optimisé"**.

The algorithm runs and you are automatically redirected to the results page. Generation typically takes **1–5 seconds** depending on the number of cities.

### What happens during generation?

The algorithm:
1. Computes the distances between all city pairs
2. Tests groupings from 1 hotel up to n hotels (one per city)
3. Rejects any grouping where a city would be more than **200 km** from its hotel (not viable as a day trip)
4. Selects the grouping with the **shortest total distance** among valid options
5. Merges hotels that are close to each other, if the extra distance is below **200 km per hotel saved**

---

## 7. Understanding Your Itinerary

### 7.1 Summary Line

At the top of the results page:

```
3 hôtels · 1284 km  |  circuit 900 km · excursions 384 km
```

| Value | Meaning |
|---|---|
| **3 hôtels** | You will stay in 3 different hotels during this trip |
| **1284 km total** | Sum of all travel: circuit between hotels + all day-trip round-trips |
| **circuit 900 km** | Distance of the route connecting your hotels in order |
| **excursions 384 km** | Sum of all day-trip round-trips (hotel → city → hotel) |

### 7.2 Hotel Blocks

Each hotel is displayed as a card:

```
Paris                          ← hotel city
  hotel                        ← indicates this is a hotel base

  ↑ remonter  partir d'ici  ↓ descendre   ← reordering buttons

  — Versailles   50 km aller-retour       ← day trip (round-trip distance)
  — Chartres     180 km aller-retour      ← day trip

  vers Lyon — 392 km           ← travel to next hotel
```

**"aller-retour"** = the round-trip distance for that day trip (you leave the hotel in the morning and return the same evening).

### 7.3 Comparison Table

At the bottom, a table shows all tested configurations:

| Hotels | Distance totale (km) | Faisabilité |
|---|---|---|
| 1 | 2920 km | excursion > 200 km |
| 2 | 1849 km | excursion > 200 km |
| **3 ←** | **1284 km** | ok |
| 4 | 1190 km | ok |

- **excursion > 200 km** — this option was rejected because at least one city would require a day trip longer than 200 km
- **← (arrow)** — this is the recommended plan currently shown
- Valid options with fewer km are shown for reference if you want to manually switch

---

## 8. Adjusting Your Itinerary Manually

You can reorder your itinerary without triggering a re-optimisation. Your manual changes are saved in your session.

> **Note:** Manual changes are intentional overrides. The application will not re-optimise after a manual edit — the responsibility for any distance increase is yours.

### 8.1 Reordering Hotels

Each hotel card has up to three buttons:

| Button | Action |
|---|---|
| **↑ remonter** | Move this hotel earlier in the circuit |
| **↓ descendre** | Move this hotel later in the circuit |
| **partir d'ici** | Set this hotel as the starting point of the trip |

After each click, the **circuit distance and total distance are automatically recalculated** and updated in the summary.

### 8.2 Reordering Day Trips Within a Hotel

Each day-trip city has small **↑** and **↓** arrows. Use these to change the order of days you visit the cities from that hotel. This does not change distances (the hotel is the same), only the day order.

### 8.3 Regenerating

If you want to start over with a fresh optimisation, click **"Régénérer"** at the bottom of the results page. This discards your manual changes and runs the algorithm again.

---

## 9. Sharing a Trip

Every trip has a unique shareable link. To share it:

1. Generate your tour (results must be available in session).
2. The share link is based on your trip's token. To access it, use the link format:
   ```
   http://localhost/box-certificative-2026/pages/view_trip.php?token=<token>
   ```
   *(The token is set when you create the trip.)*

### Visibility Rules

| Setting | Who can view |
|---|---|
| **Public** | Anyone with the link, no login required |
| **Privé** | Only you, when logged in — others are blocked |

To change the visibility of a trip, the trip must be recreated with the desired setting (editing visibility after creation is not currently supported in the UI).

---

## 10. Troubleshooting

### "Erreur lors de la generation du tour"

**Cause:** The Python engine could not be started or returned an error.  
**Fix:**
- Verify Python is installed: open a terminal and type `python --version`
- Make sure you are running at least 2 cities
- Check that `algo/algo.py` exists in the project folder

### "Ville introuvable"

**Cause:** OpenStreetMap could not find the city name you typed.  
**Fix:**
- Check the spelling
- Add the country: "Lyon, France" instead of "Lyon"
- Try an alternative name: "NYC" → "New York"

### Page loads but shows no data after generation

**Cause:** The PHP session expired or was cleared.  
**Fix:** Go back to the trip in the Dashboard and click **"Générer le tour"** again.

### "excursion > 200 km" for all configurations

**Cause:** Your cities are spread too far apart for any grouping to stay within the 200 km day-trip limit, or the algorithm found no valid clustering for the lower k values.  
**Fix:**
- The recommended plan (marked ←) is still valid — it uses more hotels but satisfies the constraint
- Consider setting a **max hotels** value equal to the number of cities (each city gets its own hotel)

### Generation takes more than 30 seconds

**Cause:** Too many cities (more than 25) or the Python process could not start.  
**Fix:**
- For large city lists (> 20), the algorithm may take several seconds — this is normal
- If the page times out, increase the PHP `max_execution_time` in `php.ini`

### The share link returns "Itinerary not found"

**Cause:** The share token in the URL is incorrect or the trip was deleted.  
**Fix:** Copy the correct link from the trip details or regenerate the tour.
