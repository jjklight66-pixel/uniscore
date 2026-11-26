"""
Storage Module: Saves parsed Sports Trees into hierarchical JSON files.
"""

import os
import json
from typing import Dict, Any, List

class UniScoreStorage:
    def __init__(self, base_output_dir: str = "output"):
        self.base_output_dir = base_output_dir
        os.makedirs(self.base_output_dir, exist_ok=True)

    def save_sport_day(self, sport_key: str, date_str: str, matches: List[Dict[str, Any]]) -> str:
        sport_dir = os.path.join(self.base_output_dir, sport_key)
        os.makedirs(sport_dir, exist_ok=True)
        file_path = os.path.join(sport_dir, f"{date_str}.json")

        payload = {
            "meta": {
                "sport": sport_key,
                "date": date_str,
                "total_matches": len(matches),
                "generated_at": datetime_now_iso(),
                "schema_version": "1.0.0-architecture-tree",
            },
            "matches": matches
        }

        with open(file_path, "w", encoding="utf-8") as f:
            json.dump(payload, f, ensure_ascii=False, indent=2)

        return file_path

def datetime_now_iso() -> str:
    from datetime import datetime, timezone
    return datetime.now(timezone.utc).isoformat()
