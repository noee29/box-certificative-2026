"""
api.py — Flask server
Exposes POST /generate to solve a TSP tour from a list of places.
"""

from flask import Flask, request, jsonify
<<<<<<< HEAD
from algo import solve
=======
from algo import solve, solve_clustered
>>>>>>> develop

app = Flask(__name__)


@app.route("/generate", methods=["POST"])
def generate():
    data = request.get_json()

    if not data or "places" not in data:
        return jsonify({"error": "Missing 'places' field"}), 400

    places = data["places"]

    if not isinstance(places, list) or len(places) < 2:
        return jsonify({"error": "At least 2 places are required"}), 400

    result = solve(places)
    return jsonify(result), 200


<<<<<<< HEAD
=======
@app.route("/optimize", methods=["POST"])
def optimize():
    data = request.get_json()

    if not data or "places" not in data:
        return jsonify({"error": "Missing 'places' field"}), 400

    places = data["places"]

    if not isinstance(places, list) or len(places) < 2:
        return jsonify({"error": "At least 2 places are required"}), 400

    max_hotels = data.get("max_hotels")
    result = solve_clustered(places, max_hotels=max_hotels)
    return jsonify(result), 200


>>>>>>> develop
if __name__ == "__main__":
    app.run(port=5000, debug=True)
