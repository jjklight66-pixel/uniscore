"""
SQLite Database Storage Handler for UniScore Sports Data Architecture.
Supports upserts, relational tables, and JSON document queries.
100% Zero-config, built into Python, ready for production inspection.
"""

import sqlite3
import json
import os
from typing import Dict, Any, List

class SportsDatabase:
    def __init__(self, db_path: str = "sports_database.sqlite"):
        self.db_path = db_path
        self._init_db()

    def _get_connection(self):
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row
        return conn

    def _init_db(self):
        with self._get_connection() as conn:
            cursor = conn.cursor()

            # 1. Sports Table
            cursor.execute("""
            CREATE TABLE IF NOT EXISTS sports (
                id INTEGER PRIMARY KEY,
                name TEXT UNIQUE,
                vietnamese_name TEXT
            );
            """)

            # 2. Tournaments Table
            cursor.execute("""
            CREATE TABLE IF NOT EXISTS tournaments (
                id TEXT PRIMARY KEY,
                sport_id INTEGER,
                name TEXT,
                slug TEXT,
                country_name TEXT,
                country_code TEXT,
                FOREIGN KEY (sport_id) REFERENCES sports(id)
            );
            """)

            # 3. Teams / Competitors Table
            cursor.execute("""
            CREATE TABLE IF NOT EXISTS teams (
                id TEXT PRIMARY KEY,
                name TEXT,
                slug TEXT,
                logo_url TEXT
            );
            """)

            # 4. Matches Master Table
            cursor.execute("""
            CREATE TABLE IF NOT EXISTS matches (
                id TEXT PRIMARY KEY,
                sport_id INTEGER,
                slug TEXT,
                date_str TEXT,
                tournament_id TEXT,
                home_team_id TEXT,
                away_team_id TEXT,
                state_category TEXT,
                status_code INTEGER,
                status_description TEXT,
                start_timestamp INTEGER,
                start_time_iso TEXT,
                readtime_minute TEXT,
                home_score_total INTEGER,
                away_score_total INTEGER,
                raw_json TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sport_id) REFERENCES sports(id),
                FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
                FOREIGN KEY (home_team_id) REFERENCES teams(id),
                FOREIGN KEY (away_team_id) REFERENCES teams(id)
            );
            """)

            # 5. Live Events / Incidents Table
            cursor.execute("""
            CREATE TABLE IF NOT EXISTS match_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                match_id TEXT,
                type TEXT,
                time_minute INTEGER,
                period INTEGER,
                scorer TEXT,
                assist TEXT,
                card_type TEXT,
                goal_type TEXT,
                goal_strength TEXT,
                scoring_type TEXT,
                player_in TEXT,
                player_out TEXT,
                score_at_incident TEXT,
                FOREIGN KEY (match_id) REFERENCES matches(id)
            );
            """)

            # Safe migration for existing DB
            cursor.execute("PRAGMA table_info(match_events)")
            existing_cols = [row[1] for row in cursor.fetchall()]
            for col in ("goal_type", "goal_strength", "scoring_type"):
                if col not in existing_cols:
                    cursor.execute(f"ALTER TABLE match_events ADD COLUMN {col} TEXT;")

            # 6. Players / Roster Table
            cursor.execute("""
            CREATE TABLE IF NOT EXISTS match_players (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                match_id TEXT,
                team_id TEXT,
                side TEXT,
                player_id TEXT,
                player_name TEXT,
                shirt_number TEXT,
                position TEXT,
                is_substitute INTEGER,
                stats_json TEXT,
                FOREIGN KEY (match_id) REFERENCES matches(id)
            );
            """)

            conn.commit()

    def upsert_match(self, match: Dict[str, Any], date_str: str):
        with self._get_connection() as conn:
            cursor = conn.cursor()

            tax = match.get("taxonomy", {})
            sport = tax.get("sport", {})
            tour = tax.get("tournament_league", {})
            country = tax.get("country_region", {})
            entities = match.get("entities", {})
            teams = entities.get("teams_or_players", {})
            home = teams.get("home", {})
            away = teams.get("away", {})
            timeline = match.get("match_timeline", {})
            scores = timeline.get("scores", {})
            readtime = timeline.get("readtime", {})
            center = match.get("match_center", {})

            # Upsert Sport
            if sport.get("id"):
                cursor.execute("""
                INSERT INTO sports (id, name, vietnamese_name)
                VALUES (?, ?, ?)
                ON CONFLICT(id) DO UPDATE SET
                    name=excluded.name,
                    vietnamese_name=excluded.vietnamese_name;
                """, (sport.get("id"), sport.get("name"), sport.get("vietnamese_name")))

            # Upsert Tournament
            if tour.get("id"):
                cursor.execute("""
                INSERT INTO tournaments (id, sport_id, name, slug, country_name, country_code)
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT(id) DO UPDATE SET
                    name=excluded.name,
                    slug=excluded.slug,
                    country_name=excluded.country_name;
                """, (tour.get("id"), sport.get("id"), tour.get("name"), tour.get("slug"), country.get("name"), country.get("iso_code")))

            # Upsert Teams
            for team in (home, away):
                if team.get("id"):
                    cursor.execute("""
                    INSERT INTO teams (id, name, slug, logo_url)
                    VALUES (?, ?, ?, ?)
                    ON CONFLICT(id) DO UPDATE SET
                        name=excluded.name,
                        slug=excluded.slug,
                        logo_url=excluded.logo_url;
                    """, (team.get("id"), team.get("name"), team.get("slug"), team.get("logo_url")))

            # Upsert Match
            match_id = match.get("id")
            if match_id:
                cursor.execute("""
                INSERT INTO matches (
                    id, sport_id, slug, date_str, tournament_id, home_team_id, away_team_id,
                    state_category, status_code, status_description, start_timestamp, start_time_iso,
                    readtime_minute, home_score_total, away_score_total, raw_json
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(id) DO UPDATE SET
                    state_category=excluded.state_category,
                    status_code=excluded.status_code,
                    status_description=excluded.status_description,
                    readtime_minute=excluded.readtime_minute,
                    home_score_total=excluded.home_score_total,
                    away_score_total=excluded.away_score_total,
                    raw_json=excluded.raw_json,
                    updated_at=CURRENT_TIMESTAMP;
                """, (
                    match_id, sport.get("id"), match.get("slug"), date_str, tour.get("id"), home.get("id"), away.get("id"),
                    timeline.get("state_category"), timeline.get("status_code"), timeline.get("status_description"),
                    timeline.get("start_timestamp"), timeline.get("start_time_iso"),
                    str(readtime.get("minute") or ""),
                    scores.get("home_total"), scores.get("away_total"),
                    json.dumps(match, ensure_ascii=False)
                ))

            # Insert Events (clear old ones for this match)
            incidents = center.get("live_events_summary", [])
            if match_id and incidents:
                cursor.execute("DELETE FROM match_events WHERE match_id = ?", (match_id,))
                for inc in incidents:
                    cursor.execute("""
                    INSERT INTO match_events (
                        match_id, type, time_minute, period, scorer, assist, card_type,
                        goal_type, goal_strength, scoring_type, player_in, player_out, score_at_incident
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    """, (
                        match_id, inc.get("type"), inc.get("time_minute"), inc.get("period"),
                        inc.get("scorer"), inc.get("assist"), inc.get("card_type"),
                        inc.get("goal_type"), inc.get("goal_strength"), inc.get("scoring_type"),
                        inc.get("player_in"), inc.get("player_out"), inc.get("score_at_incident")
                    ))

            # Insert Lineups / Players
            roster = entities.get("squad_roster", {})
            if match_id and (roster.get("home") or roster.get("away")):
                cursor.execute("DELETE FROM match_players WHERE match_id = ?", (match_id,))
                for side, team_id in (("home", home.get("id")), ("away", away.get("id"))):
                    for p in roster.get(side, []):
                        cursor.execute("""
                        INSERT INTO match_players (
                            match_id, team_id, side, player_id, player_name, shirt_number, position, is_substitute, stats_json
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        """, (
                            match_id, team_id, side, p.get("id"), p.get("name"),
                            str(p.get("shirt_number") or ""), p.get("position"),
                            1 if p.get("is_substitute") else 0,
                            json.dumps(p.get("stats") or {})
                        ))

            conn.commit()

    def get_summary(self) -> Dict[str, int]:
        with self._get_connection() as conn:
            cursor = conn.cursor()
            return {
                "sports": cursor.execute("SELECT COUNT(*) FROM sports").fetchone()[0],
                "tournaments": cursor.execute("SELECT COUNT(*) FROM tournaments").fetchone()[0],
                "teams": cursor.execute("SELECT COUNT(*) FROM teams").fetchone()[0],
                "matches": cursor.execute("SELECT COUNT(*) FROM matches").fetchone()[0],
                "match_events": cursor.execute("SELECT COUNT(*) FROM match_events").fetchone()[0],
                "match_players": cursor.execute("SELECT COUNT(*) FROM match_players").fetchone()[0],
            }
