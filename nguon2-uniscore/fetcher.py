"""
UniScore API Fetcher with Rate Limiting, Retries, and Error Handling.
"""

import time
import requests
from typing import Dict, Any, Optional, List
from config import BASE_URL, DEFAULT_HEADERS, SPORTS_CONFIG

class UniScoreFetcher:
    def __init__(self, timeout: int = 10, max_retries: int = 3, backoff_factor: float = 1.0):
        self.session = requests.Session()
        self.session.headers.update(DEFAULT_HEADERS)
        self.timeout = timeout
        self.max_retries = max_retries
        self.backoff_factor = backoff_factor

    def _get(self, endpoint: str, params: Optional[Dict[str, Any]] = None) -> Optional[Dict[str, Any]]:
        url = f"{BASE_URL}/{endpoint}" if not endpoint.startswith("http") else endpoint
        for attempt in range(self.max_retries):
            try:
                response = self.session.get(url, params=params, timeout=self.timeout)
                if response.status_code == 200:
                    return response.json()
                elif response.status_code in (429, 500, 502, 503, 504):
                    time.sleep(self.backoff_factor * (2 ** attempt))
                    continue
                else:
                    return None
            except requests.RequestException:
                time.sleep(self.backoff_factor * (2 ** attempt))
        return None

    def get_event_counts(self) -> Dict[str, Any]:
        """Fetch live match count across all sports."""
        res = self._get("event-count", params={"language": "en"})
        return res.get("data", {}) if res else {}

    def get_scheduled_events(self, sport_key: str, date_str: str, page: int = 1) -> List[Any]:
        """
        Fetch scheduled/live events for a specific sport and date (YYYY-MM-DD).
        Handles both pagination-v2 (Football) and compact string list (Basketball, Tennis, etc.).
        """
        cfg = SPORTS_CONFIG.get(sport_key)
        if not cfg:
            return []

        slug = cfg["slug"]
        if cfg["type"] == "pagination":
            endpoint = f"sport/{slug}/scheduled-events-pagination-v2/{date_str}/locale/VN/type/all"
            res = self._get(endpoint, params={"sortByMatchTime": "false", "page": page, "language": "en"})
            if res and isinstance(res.get("data"), dict):
                return res["data"].get("events", [])
            return []
        else:
            # Compact string delimiter format
            endpoint = f"sport/{slug}/scheduled-events/{date_str}/offset/-420"
            res = self._get(endpoint, params={"language": "en"})
            if res and isinstance(res.get("data"), dict):
                events_raw = res["data"].get("events", "")
                if isinstance(events_raw, str) and events_raw:
                    return events_raw.split("!!")
            return []

    def get_event_detail(self, sport_key: str, event_id: str) -> Optional[Dict[str, Any]]:
        """Fetch full event detail (teams, tournament, venue, referee, score)."""
        cfg = SPORTS_CONFIG.get(sport_key)
        if not cfg:
            return None
        slug = cfg["slug"]
        res = self._get(f"{slug}/event/{event_id}", params={"language": "en"})
        if res and isinstance(res.get("data"), dict):
            return res["data"].get("event", {})
        return None

    def get_event_incidents(self, sport_key: str, event_id: str) -> List[Dict[str, Any]]:
        """Fetch match incidents (goals, cards, substitutions, periods)."""
        cfg = SPORTS_CONFIG.get(sport_key)
        if not cfg:
            return []
        slug = cfg["slug"]
        res = self._get(f"{slug}/event/{event_id}/incidents", params={"language": "en"})
        if res and isinstance(res.get("data"), dict):
            return res["data"].get("incidents", [])
        return []

    def get_event_lineups(self, sport_key: str, event_id: str) -> Dict[str, Any]:
        """Fetch confirmed or expected lineups with players, positions, and shirt numbers."""
        cfg = SPORTS_CONFIG.get(sport_key)
        if not cfg:
            return {}
        slug = cfg["slug"]
        res = self._get(f"{slug}/event/{event_id}/lineups", params={"language": "en"})
        if res and isinstance(res.get("data"), dict):
            return res.get("data", {})
        return {}

    def get_event_statistics(self, sport_key: str, event_id: str, home_id: str, away_id: str) -> List[Dict[str, Any]]:
        """Fetch match statistics (possession, shots, fouls, etc.)."""
        cfg = SPORTS_CONFIG.get(sport_key)
        if not cfg:
            return []
        slug = cfg["slug"]
        endpoint = f"{slug}/event/{event_id}/home/{home_id}/away/{away_id}/statistics"
        res = self._get(endpoint, params={"language": "en"})
        if res and isinstance(res.get("data"), dict):
            return res["data"].get("statistics", [])
        return []
