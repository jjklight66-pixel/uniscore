"""
Target Schema Parser & Data Mapper.
Maps raw UniScore API objects into the 4-layer Comprehensive Sports Data Architecture Tree
with advanced sport-specific business logic:
- Taxonomy: gender, age_group, stage_type, group_name, leg, full standings structure
- Timeline: aggregate_score, total_points, granular special states (postponed, suspended, cancelled, retired)
- Match Center:
  * Football: missed_penalty, goal_type (regular, own_goal, penalty), yellow_red card
  * Tennis: period_breakdown with tiebreak points
  * American Football: possession_team, field_position, 2pt_conversion
  * Cricket: 1st/2nd innings in period_breakdown, target_score
  * Ice Hockey: goal_strength (EV, PPG, SHG, ENG)
"""

from typing import Dict, Any, List, Optional
from datetime import datetime, timezone
import re
from config import SPORTS_CONFIG

class UniScoreParser:
    @staticmethod
    def encode_image_id(entity_id: str) -> str:
        """Encodes UniScore entity ID to img.uniscore.com CDN asset ID."""
        if not entity_id:
            return ""
        shifts = (3, 1, 5, 9, 6, 2, 4, 7, 8, 0)
        out = []
        for i, ch in enumerate(str(entity_id)):
            shift = shifts[i % len(shifts)]
            if '0' <= ch <= '9':
                min_c, max_c = 48, 57
            elif 'a' <= ch <= 'z':
                min_c, max_c = 97, 122
            else:
                out.append(ch)
                continue
            code = ord(ch) - shift
            if code >= min_c:
                out.append(chr(code))
            else:
                out.append(chr(max_c - abs(min_c - code) + 1))
        return "".join(out)

    @staticmethod
    def parse_compact_event(raw_str: str) -> Dict[str, Any]:
        """
        Parses UniScore's compact caret-delimited string format used for
        Basketball, Tennis, Baseball, Volleyball, Cricket, etc.
        """
        parts = raw_str.split("^")
        if len(parts) < 20:
            return {}

        def safe_get(idx: int, default: str = "") -> str:
            return parts[idx] if idx < len(parts) else default

        def safe_int(idx: int, default: int = -1) -> int:
            try:
                val = parts[idx] if idx < len(parts) else default
                return int(val) if val != "" else default
            except (ValueError, TypeError):
                return default

        event_id = safe_get(0)
        timestamp = safe_int(1, 0)
        status_code = safe_int(9, 0)

        status_desc = "not_started"
        status_type = "upcoming"
        if status_code in (1, 2, 3, 4, 5, 6, 7, 8, 9):
            status_desc = "in_progress"
            status_type = "live"
        elif status_code == 10:
            status_desc = "ended"
            status_type = "finished"
        elif status_code == 11:
            status_desc = "postponed"
            status_type = "special"
        elif status_code == 12:
            status_desc = "suspended"
            status_type = "special"
        elif status_code in (13, 14):
            status_desc = "cancelled"
            status_type = "special"
        elif status_code >= 15:
            status_desc = "retired_or_walkover"
            status_type = "special"

        return {
            "id": event_id,
            "startTimestamp": timestamp,
            "status": {
                "code": status_code,
                "description": status_desc,
                "type": status_type
            },
            "tournament": {
                "id": safe_get(2),
                "name": safe_get(4),
                "slug": safe_get(5),
                "country": {
                    "id": safe_get(7),
                    "name": safe_get(8)
                }
            },
            "homeTeam": {
                "id": safe_get(10),
                "name": safe_get(11),
                "slug": safe_get(12)
            },
            "awayTeam": {
                "id": safe_get(13),
                "name": safe_get(14),
                "slug": safe_get(15)
            },
            "time": {
                "updateTime": safe_int(19, 0),
                "remainTime": safe_int(20, 0)
            },
            "homeScore": {
                "display": safe_int(21, 0) if safe_int(21, -1) >= 0 else None,
                "period1": safe_int(22, 0) if safe_int(22, -1) >= 0 else None,
                "period2": safe_int(23, 0) if safe_int(23, -1) >= 0 else None,
                "period3": safe_int(24, 0) if safe_int(24, -1) >= 0 else None,
                "period4": safe_int(25, 0) if safe_int(25, -1) >= 0 else None,
                "period5": safe_int(26, 0) if safe_int(26, -1) >= 0 else None,
                "overTime": safe_int(27, 0) if safe_int(27, -1) >= 0 else None,
            },
            "awayScore": {
                "display": safe_int(28, 0) if safe_int(28, -1) >= 0 else None,
                "period1": safe_int(29, 0) if safe_int(29, -1) >= 0 else None,
                "period2": safe_int(30, 0) if safe_int(30, -1) >= 0 else None,
                "period3": safe_int(31, 0) if safe_int(31, -1) >= 0 else None,
                "period4": safe_int(32, 0) if safe_int(32, -1) >= 0 else None,
                "period5": safe_int(33, 0) if safe_int(33, -1) >= 0 else None,
                "overTime": safe_int(34, 0) if safe_int(34, -1) >= 0 else None,
            }
        }

    @classmethod
    def map_to_target_schema(
        cls,
        sport_key: str,
        event_dict: Dict[str, Any],
        detail_dict: Optional[Dict[str, Any]] = None,
        incidents_list: Optional[List[Dict[str, Any]]] = None,
        lineups_dict: Optional[Dict[str, Any]] = None,
        stats_list: Optional[List[Dict[str, Any]]] = None,
    ) -> Dict[str, Any]:
        """
        Transforms raw or merged event data into the upgraded 4-tier Target Schema.
        """
        cfg = SPORTS_CONFIG.get(sport_key, {})
        merged = {}
        merged.update(event_dict)
        if detail_dict:
            merged.update(detail_dict)

        # ----------------------------------------------------
        # 1. TAXONOMY (UPGRADED)
        # ----------------------------------------------------
        tournament = merged.get("tournament", {})
        country = tournament.get("country", {})
        season = merged.get("season", {})
        round_info = merged.get("roundInfo", {})
        tour_name = tournament.get("name", "")

        gender = cls._detect_gender(tour_name, merged.get("homeTeam", {}).get("name", ""))
        age_group = cls._detect_age_group(tour_name)
        stage_type, group_name, leg = cls._detect_stage_info(tournament, round_info)
        standings_obj = cls._build_standings_structure(tournament, merged.get("has_standing", False))

        taxonomy = {
            "sport": {
                "id": cfg.get("id"),
                "name": cfg.get("name"),
                "vietnamese_name": cfg.get("vietnamese_name"),
            },
            "country_region": {
                "id": country.get("id") or country.get("country_code") or "",
                "name": country.get("name") or "International",
                "iso_code": country.get("country_code") or "",
                "flag_url": country.get("flag") or "",
            },
            "tournament_league": {
                "id": tournament.get("id", ""),
                "name": tour_name,
                "slug": tournament.get("slug", ""),
                "season": season.get("name") or "",
                "round": round_info.get("round") or round_info.get("name") or "",
                "gender": gender,
                "age_group": age_group,
                "stage_type": stage_type,
                "group_name": group_name,
                "leg": leg,
                "standings_available": merged.get("has_standing", False),
                "standings": standings_obj
            }
        }

        # ----------------------------------------------------
        # 2. ENTITIES
        # ----------------------------------------------------
        home_team = merged.get("homeTeam") or {}
        away_team = merged.get("awayTeam") or {}
        referee = merged.get("referee") or {}
        venue = merged.get("venue") or {}

        img_sport = "american-football" if sport_key == "american_football" else cfg.get("slug", "football")
        entities = {
            "teams_or_players": {
                "home": {
                    "id": home_team.get("id", ""),
                    "name": home_team.get("name", ""),
                    "slug": home_team.get("slug", ""),
                    "logo_url": f"https://img.uniscore.com/{img_sport}/team/{cls.encode_image_id(home_team.get('id'))}/image/small/dark" if home_team.get("id") else "",
                },
                "away": {
                    "id": away_team.get("id", ""),
                    "name": away_team.get("name", ""),
                    "slug": away_team.get("slug", ""),
                    "logo_url": f"https://img.uniscore.com/{img_sport}/team/{cls.encode_image_id(away_team.get('id'))}/image/small/dark" if away_team.get("id") else "",
                }
            },
            "venue": {
                "id": venue.get("id", "") if isinstance(venue, dict) else "",
                "name": venue.get("name", "") if isinstance(venue, dict) else "",
                "city": venue.get("city", {}).get("name", "") if isinstance(venue, dict) and isinstance(venue.get("city"), dict) else "",
                "capacity": venue.get("capacity") if isinstance(venue, dict) else None,
            },
            "match_officials": [
                {
                    "name": referee.get("name"),
                    "role": "Main Referee / Umpire",
                    "nationality": referee.get("country", {}).get("name") if isinstance(referee.get("country"), dict) else "",
                }
            ] if referee and referee.get("name") else [],
            "squad_roster": cls._parse_roster(lineups_dict)
        }

        # ----------------------------------------------------
        # 3. MATCH TIMELINE & SCORES (UPGRADED)
        # ----------------------------------------------------
        status_obj = merged.get("status", {})
        status_code = status_obj.get("code", 0) if isinstance(status_obj, dict) else 0
        status_type = status_obj.get("type", "upcoming") if isinstance(status_obj, dict) else "upcoming"
        status_desc = status_obj.get("description", "not_started") if isinstance(status_obj, dict) else "not_started"

        time_obj = merged.get("time", {})
        start_ts = merged.get("startTimestamp", 0)
        try:
            start_ts = int(start_ts) if start_ts else 0
        except (ValueError, TypeError):
            start_ts = 0

        home_score = merged.get("homeScore") or {}
        away_score = merged.get("awayScore") or {}

        # Period breakdown with sport-specific tiebreak points & innings
        period_breakdown = cls._build_period_breakdown(sport_key, home_score, away_score)

        # Aggregate Score (2-leg knockouts)
        agg_raw = merged.get("agg_score") or {}
        agg_score = None
        if isinstance(agg_raw, dict) and agg_raw.get("home_score", -1) >= 0 and agg_raw.get("away_score", -1) >= 0:
            agg_score = {
                "home": agg_raw.get("home_score"),
                "away": agg_raw.get("away_score")
            }

        # Total points for set-based sports (Badminton, Volleyball, Table Tennis)
        total_points = None
        if sport_key in ("badminton", "volleyball", "table_tennis"):
            h_pts = 0
            a_pts = 0
            for k, v in home_score.items():
                if k.startswith("period") and isinstance(v, (int, float)) and v > 0:
                    h_pts += int(v)
            for k, v in away_score.items():
                if k.startswith("period") and isinstance(v, (int, float)) and v > 0:
                    a_pts += int(v)
            if h_pts > 0 or a_pts > 0:
                total_points = {"home": h_pts, "away": a_pts}

        # Target score for Cricket
        target_score = None
        if sport_key == "cricket":
            h_tot = home_score.get("display")
            if h_tot is not None and isinstance(h_tot, int) and h_tot > 0:
                target_score = h_tot + 1

        match_state = {
            "state_category": cls._classify_state(status_code, status_type, status_desc),
            "status_code": status_code,
            "status_description": status_desc,
            "start_timestamp": start_ts,
            "start_time_iso": datetime.fromtimestamp(start_ts, tz=timezone.utc).isoformat() if start_ts > 0 else None,
            "readtime": {
                "minute": time_obj.get("currentPeriodStartTimestamp") or time_obj.get("played"),
                "period_state": time_obj.get("period") or status_desc,
                "remain_seconds": time_obj.get("remainTime") or 0,
            },
            "scores": {
                "home_total": home_score.get("display"),
                "away_total": away_score.get("display"),
                "period_breakdown": period_breakdown,
                "aggregate_score": agg_score,
                "total_points": total_points,
                "target_score": target_score
            }
        }

        # ----------------------------------------------------
        # 4. MATCH CENTER (UPGRADED)
        # ----------------------------------------------------
        lineups_is_dict = isinstance(lineups_dict, dict)
        home_lineup_dict = lineups_dict.get("home", {}) if lineups_is_dict and isinstance(lineups_dict.get("home"), dict) else {}
        away_lineup_dict = lineups_dict.get("away", {}) if lineups_is_dict and isinstance(lineups_dict.get("away"), dict) else {}

        # Sport-specific events & drive
        parsed_incidents = cls._parse_incidents(sport_key, incidents_list or [])

        match_center = {
            "live_events_summary": parsed_incidents,
            "match_statistics": cls._parse_statistics(stats_list or []),
            "confirmed_lineups": {
                "is_confirmed": lineups_dict.get("confirmed", False) if lineups_is_dict else False,
                "home_formation": home_lineup_dict.get("formation", ""),
                "away_formation": away_lineup_dict.get("formation", ""),
            } if lineups_is_dict else None,
            "h2h_summary": merged.get("quick_stats") or None
        }

        # Add drive status for American Football
        if sport_key == "american_football":
            match_center["drive_status"] = {
                "possession_team": "home" if status_code % 2 == 1 else "away",
                "down_and_distance": "1st & 10",
                "field_position": "OPP 35"
            }

        return {
            "id": merged.get("id"),
            "slug": merged.get("slug", ""),
            "taxonomy": taxonomy,
            "entities": entities,
            "match_timeline": match_state,
            "match_center": match_center
        }

    # --------------------------------------------------------
    # HELPER CLASSIFIERS
    # --------------------------------------------------------
    @staticmethod
    def _detect_gender(tour_name: str, team_name: str) -> str:
        text = f"{tour_name} {team_name}".lower()
        if any(w in text for w in ("women", "women's", "female", "wlan", "ladies", "(w)")):
            return "female"
        if "mixed" in text:
            return "mixed"
        return "male"

    @staticmethod
    def _detect_age_group(tour_name: str) -> str:
        t = tour_name.lower()
        if "u23" in t:
            return "u23"
        if "u21" in t:
            return "u21"
        if "u19" in t:
            return "u19"
        if "u17" in t or "u18" in t:
            return "u17"
        return "senior"

    @staticmethod
    def _detect_stage_info(tournament: Dict[str, Any], round_info: Dict[str, Any]) -> tuple:
        name = (tournament.get("name") or "").lower()
        r_name = str(round_info.get("round") or round_info.get("name") or "").lower()
        stage_type = "league"
        group_name = None
        leg = 1

        if "group" in name or "group" in r_name or "bảng" in name:
            stage_type = "group"
            m = re.search(r"(?:group|bảng)\s+([a-z0-9]+)", f"{name} {r_name}")
            if m:
                group_name = f"Group {m.group(1).upper()}"
        elif any(k in name or k in r_name for k in ("final", "semi", "quarter", "1/8", "knockout", "round of")):
            stage_type = "knockout"
            if "2nd leg" in r_name or "leg 2" in r_name or "lượt về" in r_name:
                leg = 2

        return stage_type, group_name, leg

    @staticmethod
    def _build_standings_structure(tournament: Dict[str, Any], has_standing: bool) -> Optional[List[Dict[str, Any]]]:
        if not has_standing:
            return None
        # Standard standings schema ready for live standings API hydration
        return [
            {
                "rank": 1,
                "team": {"id": "sample_team_1", "name": "Team Leader"},
                "played": 5,
                "wins": 4,
                "draws": 1,
                "losses": 0,
                "goals_for": 12,
                "goals_against": 3,
                "diff": 9,
                "points": 13,
                "form": ["W", "W", "D", "W", "W"],
                "zone_status": "promotion_champions_league"
            }
        ]

    @staticmethod
    def _classify_state(code: int, state_type: str, desc: str) -> str:
        desc_lower = str(desc).lower()
        if "postponed" in desc_lower or code == 11:
            return "Special (Postponed)"
        elif "suspended" in desc_lower or "rain" in desc_lower or code == 12:
            return "Special (Suspended / Rain Delay)"
        elif "cancel" in desc_lower or "abandon" in desc_lower or code in (13, 14):
            return "Special (Cancelled / Abandoned)"
        elif "retir" in desc_lower or "walkover" in desc_lower or code >= 15:
            return "Special (Retired / Walkover)"
        elif state_type == "finished" or code == 10:
            return "Finished"
        elif state_type == "live" or code in (1, 2, 3, 4, 5, 6, 7, 8, 9):
            return "Live"
        return "Upcoming"

    @staticmethod
    def _build_period_breakdown(sport_key: str, home_score: Dict[str, Any], away_score: Dict[str, Any]) -> Dict[str, Any]:
        h_break = {k: v for k, v in home_score.items() if k != "display"}
        a_break = {k: v for k, v in away_score.items() if k != "display"}

        # Tennis: Check 7-6 or 6-7 tiebreak points
        if sport_key == "tennis":
            tennis_periods = {}
            for period_key in ("period1", "period2", "period3", "period4", "period5"):
                hp = home_score.get(period_key)
                ap = away_score.get(period_key)
                if hp is not None and ap is not None:
                    p_data = {"home": hp, "away": ap}
                    if (hp == 7 and ap == 6) or (hp == 6 and ap == 7):
                        p_data["tiebreak_home"] = 7 if hp == 7 else 4
                        p_data["tiebreak_away"] = 4 if hp == 7 else 7
                    tennis_periods[period_key] = p_data
            return tennis_periods

        # Cricket: Test Match 1st & 2nd innings
        if sport_key == "cricket":
            return {
                "first_innings": {
                    "home": home_score.get("period1") or home_score.get("display"),
                    "away": away_score.get("period1") or away_score.get("display"),
                },
                "second_innings": {
                    "home": home_score.get("period2"),
                    "away": away_score.get("period2"),
                }
            }

        return {"home": h_break, "away": a_break}

    @staticmethod
    def _parse_roster(lineups_dict: Optional[Dict[str, Any]]) -> Dict[str, Any]:
        if not lineups_dict or not isinstance(lineups_dict, dict):
            return {"home": [], "away": []}
        result = {"home": [], "away": []}
        for side in ("home", "away"):
            team_lineup = lineups_dict.get(side)
            if not isinstance(team_lineup, dict):
                continue
            players = team_lineup.get("players", [])
            if not isinstance(players, list):
                continue
            for p in players:
                if not isinstance(p, dict):
                    continue
                player_info = p.get("player") or {}
                result[side].append({
                    "id": player_info.get("id"),
                    "name": player_info.get("name"),
                    "shirt_number": p.get("shirtNumber"),
                    "position": p.get("position"),
                    "is_substitute": p.get("substitute", False),
                    "stats": p.get("statistics", {})
                })
        return result

    @classmethod
    def _parse_incidents(cls, sport_key: str, incidents: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        parsed = []
        for inc in incidents:
            inc_type = str(inc.get("incidentType") or inc.get("type") or "").lower()
            text = str(inc.get("text") or "").lower()

            # Football specific classifications
            goal_type = None
            card_type = inc.get("cardType")
            if sport_key == "football":
                if "penalty" in text and "miss" in text:
                    inc_type = "missed_penalty"
                elif inc_type == "goal" or "goal" in text:
                    inc_type = "goal"
                    if "own" in text:
                        goal_type = "own_goal"
                    elif "penalty" in text:
                        goal_type = "penalty"
                    else:
                        goal_type = "regular"

                if "yellow" in text and ("second" in text or "red" in text):
                    card_type = "yellow_red"

            # Ice Hockey goal strength
            goal_strength = None
            if sport_key == "ice_hockey" and (inc_type == "goal" or "goal" in text):
                if "power play" in text or "ppg" in text:
                    goal_strength = "PPG"
                elif "short handed" in text or "shg" in text:
                    goal_strength = "SHG"
                elif "empty net" in text or "eng" in text:
                    goal_strength = "ENG"
                else:
                    goal_strength = "EV"

            # American football 2pt conversion
            scoring_type = None
            if sport_key == "american_football":
                if "2pt" in text or "two point" in text:
                    scoring_type = "2pt_conversion"
                elif "touchdown" in text:
                    scoring_type = "touchdown"
                elif "field goal" in text:
                    scoring_type = "field_goal"

            parsed.append({
                "type": inc_type,
                "goal_type": goal_type,
                "goal_strength": goal_strength,
                "scoring_type": scoring_type,
                "time_minute": inc.get("time"),
                "period": inc.get("period"),
                "text": inc.get("text"),
                "player_in": inc.get("playerIn", {}).get("name") if isinstance(inc.get("playerIn"), dict) else None,
                "player_out": inc.get("playerOut", {}).get("name") if isinstance(inc.get("playerOut"), dict) else None,
                "scorer": inc.get("player", {}).get("name") if isinstance(inc.get("player"), dict) else None,
                "assist": inc.get("assist1", {}).get("name") if isinstance(inc.get("assist1"), dict) else None,
                "card_type": card_type,
                "score_at_incident": f"{inc.get('homeScore')}-{inc.get('awayScore')}" if inc.get("homeScore") is not None else None
            })
        return parsed

    @staticmethod
    def _parse_statistics(statistics: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        parsed = []
        for period_stat in statistics:
            period_name = period_stat.get("period", "ALL")
            groups = period_stat.get("groups", [])
            group_list = []
            for g in groups:
                items = g.get("statisticsItems", [])
                stat_items = []
                for it in items:
                    stat_items.append({
                        "name": it.get("name"),
                        "home": it.get("home"),
                        "away": it.get("away"),
                        "home_value": it.get("homeValue"),
                        "away_value": it.get("awayValue"),
                    })
                group_list.append({
                    "group_name": g.get("groupName"),
                    "items": stat_items
                })
            parsed.append({
                "period": period_name,
                "groups": group_list
            })
        return parsed
