"""
Main Pipeline Runner for UniScore Real-time Sports Data Architecture.
Demonstrates end-to-end extraction, parsing into the 4-tier tree, and storage.
"""

import sys
import os
import argparse
from datetime import datetime, timezone
from config import SPORTS_CONFIG
from fetcher import UniScoreFetcher
from parser import UniScoreParser
from storage import UniScoreStorage
from database import SportsDatabase

def run_pipeline(sports: list, target_date: str, max_matches_per_sport: int = 5, fetch_deep_details: bool = True, save_db: bool = True):
    fetcher = UniScoreFetcher()
    storage = UniScoreStorage(base_output_dir=os.path.join(os.path.dirname(__file__), "output"))
    db = SportsDatabase(db_path=os.path.join(os.path.dirname(__file__), "sports_database.sqlite")) if save_db else None

    print("================================================================================")
    print("UNISCORE REAL-TIME DATA PIPELINE (SPORTS ARCHITECTURE TREES)")
    print(f"Target Date: {target_date} | Deep Details: {fetch_deep_details} | Database Storage: {save_db}")
    print("================================================================================")

    # 1. Check global live events count
    live_counts = fetcher.get_event_counts()
    print(f"Live Events Summary: {live_counts}\n")

    for sport_key in sports:
        cfg = SPORTS_CONFIG.get(sport_key)
        if not cfg:
            print(f"[-] Unknown sport: {sport_key}")
            continue

        print(f"\n>>> Processing Sport: {cfg['name']} ({cfg['vietnamese_name']})")
        raw_events = fetcher.get_scheduled_events(sport_key, target_date)
        print(f"    Raw events captured: {len(raw_events)}")

        parsed_matches = []
        selected_events = raw_events[:max_matches_per_sport]

        for idx, item in enumerate(selected_events, 1):
            if cfg["type"] == "compact":
                event_dict = UniScoreParser.parse_compact_event(item)
            else:
                event_dict = item

            if not event_dict or not event_dict.get("id"):
                continue

            event_id = event_dict["id"]
            home_id = event_dict.get("homeTeam", {}).get("id", "")
            away_id = event_dict.get("awayTeam", {}).get("id", "")

            detail_dict = None
            incidents_list = None
            lineups_dict = None
            stats_list = None

            if fetch_deep_details:
                # Call detail endpoints
                detail_dict = fetcher.get_event_detail(sport_key, event_id)
                incidents_list = fetcher.get_event_incidents(sport_key, event_id)
                lineups_dict = fetcher.get_event_lineups(sport_key, event_id)
                if home_id and away_id:
                    stats_list = fetcher.get_event_statistics(sport_key, event_id, home_id, away_id)

            # Map into the 4-layer Architecture Tree
            tree_match = UniScoreParser.map_to_target_schema(
                sport_key=sport_key,
                event_dict=event_dict,
                detail_dict=detail_dict,
                incidents_list=incidents_list,
                lineups_dict=lineups_dict,
                stats_list=stats_list
            )
            parsed_matches.append(tree_match)
            if db:
                db.upsert_match(tree_match, target_date)
            print(f"    [{idx}/{len(selected_events)}] Mapped: {tree_match['entities']['teams_or_players']['home']['name']} vs {tree_match['entities']['teams_or_players']['away']['name']} (State: {tree_match['match_timeline']['state_category']})")

        # Save to hierarchical storage
        saved_file = storage.save_sport_day(sport_key, target_date, parsed_matches)
        print(f"    [+] Saved {len(parsed_matches)} matches to: {saved_file}")

    if db:
        summary = db.get_summary()
        print("\n>>> DATABASE PERSISTENCE SUMMARY (SQLite):")
        for k, v in summary.items():
            print(f"    - {k.capitalize()}: {v} records stored")

    print("\n================================================================================")
    print("PIPELINE EXECUTION COMPLETED SUCCESSFULLY!")
    print("================================================================================")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="UniScore Data Pipeline")
    parser.add_argument("--sports", nargs="+", default=["football", "basketball", "tennis"],
                        help="List of sports keys (football, basketball, tennis, baseball, volleyball, etc.)")
    parser.add_argument("--date", default=datetime.now(timezone.utc).strftime("%Y-%m-%d"),
                        help="Target match date (YYYY-MM-DD)")
    parser.add_argument("--limit", type=int, default=5, help="Max matches to crawl in deep detail per sport")
    parser.add_argument("--all-10-sports", action="store_true", help="Run for all 10 sports in the architecture tree")

    args = parser.parse_args()
    sports_to_run = list(SPORTS_CONFIG.keys()) if args.all_10_sports else args.sports
    run_pipeline(sports=sports_to_run, target_date=args.date, max_matches_per_sport=args.limit)
