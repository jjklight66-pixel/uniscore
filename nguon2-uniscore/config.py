"""
UniScore Sports Scraper & Real-time Pipeline Configuration
Mapping all 10 target sports to UniScore endpoints and specifications.
"""

BASE_URL = "https://api.uni-score.com/api/v2"

DEFAULT_HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
    "Origin": "https://uniscore.com",
    "Referer": "https://uniscore.com/",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

# The 10 sports specified in the Architecture Tree
SPORTS_CONFIG = {
    "football": {
        "id": 1,
        "name": "Football",
        "slug": "football",
        "vietnamese_name": "Bóng đá",
        "type": "pagination",
        "period_names": ["1H", "2H", "ET", "PEN"],
    },
    "basketball": {
        "id": 2,
        "name": "Basketball",
        "slug": "basketball",
        "vietnamese_name": "Bóng rổ",
        "type": "compact",
        "period_names": ["Q1", "Q2", "Q3", "Q4", "OT"],
    },
    "tennis": {
        "id": 3,
        "name": "Tennis",
        "slug": "tennis",
        "vietnamese_name": "Quần vợt",
        "type": "compact",
        "period_names": ["Set 1", "Set 2", "Set 3", "Set 4", "Set 5"],
    },
    "american_football": {
        "id": 4,
        "name": "American Football",
        "slug": "am-football",
        "vietnamese_name": "Bóng bầu dục Mỹ",
        "type": "compact",
        "period_names": ["Q1", "Q2", "Q3", "Q4", "OT"],
    },
    "baseball": {
        "id": 5,
        "name": "Baseball",
        "slug": "baseball",
        "vietnamese_name": "Bóng chày",
        "type": "compact",
        "period_names": ["Inning 1-9", "Extra"],
    },
    "badminton": {
        "id": 6,
        "name": "Badminton",
        "slug": "badminton",
        "vietnamese_name": "Cầu lông",
        "type": "compact",
        "period_names": ["Set 1", "Set 2", "Set 3"],
    },
    "volleyball": {
        "id": 7,
        "name": "Volleyball",
        "slug": "volleyball",
        "vietnamese_name": "Bóng chuyền",
        "type": "compact",
        "period_names": ["Set 1", "Set 2", "Set 3", "Set 4", "Set 5"],
    },
    "cricket": {
        "id": 8,
        "name": "Cricket",
        "slug": "cricket",
        "vietnamese_name": "Cricket",
        "type": "compact",
        "period_names": ["1st Innings", "2nd Innings"],
    },
    "ice_hockey": {
        "id": 9,
        "name": "Ice Hockey",
        "slug": "hockey",
        "vietnamese_name": "Khúc côn cầu trên băng",
        "type": "compact",
        "period_names": ["P1", "P2", "P3", "OT", "SO"],
    },
    "table_tennis": {
        "id": 10,
        "name": "Table Tennis",
        "slug": "table-tennis",
        "vietnamese_name": "Bóng bàn",
        "type": "compact",
        "period_names": ["Set 1", "Set 2", "Set 3", "Set 4", "Set 5", "Set 6", "Set 7"],
    },
}

# MQTT Real-time WebSocket Broker
MQTT_BROKER_URL = "wss://ws.unik8s.com/mqtt"
