# UniScore Sports Real-Time Data Pipeline & 10-Sport Architecture Trees

Hệ thống Data Engineering & Scraper chuyên biệt cho **UniScore (https://uniscore.com/en)**, bóc tách luồng dữ liệu thời gian thực (REST API v2 & MQTT over WebSocket) và ánh xạ (map) vào **Cây phân tích dữ liệu 10 môn thể thao chuẩn hóa 4 tầng**.

---

## 1. Cấu trúc Dự án (Project Architecture)

```
uniscore_scraper/
├── config.py          # Cấu hình 10 môn, Slug, Header, Base URL & MQTT Broker
├── fetcher.py         # Module gọi API REST v2 (Rate Limiting, Exponential Backoff, Error Handling)
├── parser.py          # Data Mapper ánh xạ từ dữ liệu UniScore sang Target Schema 4 tầng
├── storage.py         # Lưu trữ phân cấp theo từng môn: output/{sport}/{date}.json
├── main.py            # Script CLI điều phối toàn bộ pipeline
├── requirements.txt   # Thư viện phụ thuộc (requests, urllib3)
└── output/            # Thư mục chứa dữ liệu JSON kết quả cào được
    ├── football/
    ├── basketball/
    ├── tennis/
    ├── american_football/
    ├── baseball/
    ├── badminton/
    ├── volleyball/
    ├── cricket/
    ├── ice_hockey/
    └── table_tennis/
```

---

## 2. Ánh xạ 10 Môn thể thao sang UniScore API Endpoints

| STT | Môn thể thao | Slug UniScore | Endpoint lịch thi đấu ngày | Endpoint chi tiết trận đấu |
|---|---|---|---|---|
| 1 | **Football** (Bóng đá) | `football` | `/sport/football/scheduled-events-pagination-v2/{date}/locale/VN/type/all?page=1` | `/football/event/{id}` + `/incidents` + `/lineups` + `/statistics` |
| 2 | **Basketball** (Bóng rổ) | `basketball` | `/sport/basketball/scheduled-events/{date}/offset/-420` | `/basketball/event/{id}` + `/incidents` + `/lineups` |
| 3 | **Tennis** (Quần vợt) | `tennis` | `/sport/tennis/scheduled-events/{date}/offset/-420` | `/tennis/event/{id}` + `/statistics` + `/incidents` |
| 4 | **American Football** | `am-football` | `/sport/am-football/scheduled-events/{date}/offset/-420` | `/am-football/event/{id}` |
| 5 | **Baseball** (Bóng chày) | `baseball` | `/sport/baseball/scheduled-events/{date}/offset/-420` | `/baseball/event/{id}` |
| 6 | **Badminton** (Cầu lông) | `badminton` | `/sport/badminton/scheduled-events/{date}/offset/-420` | `/badminton/event/{id}` |
| 7 | **Volleyball** (Bóng chuyền)| `volleyball` | `/sport/volleyball/scheduled-events/{date}/offset/-420` | `/volleyball/event/{id}` |
| 8 | **Cricket** | `cricket` | `/sport/cricket/scheduled-events/{date}/offset/-420` | `/cricket/event/{id}` |
| 9 | **Ice Hockey** (Khúc côn cầu) | `hockey` | `/sport/hockey/scheduled-events/{date}/offset/-420` | `/hockey/event/{id}` |
| 10| **Table Tennis** (Bóng bàn)| `table-tennis`| `/sport/table-tennis/scheduled-events/{date}/offset/-420` | `/table-tennis/event/{id}` |

### Luồng WebSocket / MQTT Thời gian thực:
- **Broker:** `wss://ws.unik8s.com/mqtt`
- **Topic Topic:** `{sport_prefix}/match/{matchId}` (ví dụ: `fb-live-v1/match/{id}`, `basket-live-v1/match/{id}`)
- **Format:** Binary Uint8Array nén bằng `pako` (inflate), phân cách bằng `^` và `!`.

---

## 3. Cấu trúc Cây Dữ liệu 4 Tầng Đầu ra (Output Schema)

Mỗi trận đấu trong file JSON đều tuân thủ chính xác chuẩn:
1. **`taxonomy`**:
   - `sport`: id, name, vietnamese_name
   - `country_region`: id, name, iso_code, flag_url
   - `tournament_league`: id, name, slug, season, round, standings_available
2. **`entities`**:
   - `teams_or_players`: home, away (id, name, slug, logo_url)
   - `venue`: id, name, city, capacity
   - `match_officials`: danh sách trọng tài
   - `squad_roster`: danh sách cầu thủ đá chính & dự bị, số áo, vị trí, thống kê cá nhân
3. **`match_timeline`**:
   - `state_category`: Upcoming | Live | Finished | Special (Postponed/Cancelled)
   - `status_code`, `status_description`
   - `start_timestamp`, `start_time_iso`
   - `readtime`: minute, period_state, remain_seconds
   - `scores`: home_total, away_total, period_breakdown (tỷ số từng hiệp/set/quarter)
4. **`match_center`**:
   - `live_events_summary`: bàn thắng, thẻ phạt, thay người (thời gian, người ghi bàn, kiến tạo, loại thẻ)
   - `match_statistics`: kiểm soát bóng, số cú sút, phạt góc, v.v.
   - `confirmed_lineups`: sơ đồ chiến thuật (formation) & xác nhận đội hình

---

## 4. Hướng dẫn cài đặt & Chạy Pipeline

### Bước 1: Cài đặt thư viện
```bash
cd /home/ubuntu/Documents/api/uniscore_scraper
pip install -r requirements.txt
```

### Bước 2: Chạy kiểm thử các môn chỉ định
```bash
# Chạy 3 môn chính (Football, Basketball, Tennis)
python3 main.py --sports football basketball tennis --limit 5

# Chạy toàn bộ 10 môn thể thao cùng lúc
python3 main.py --all-10-sports --limit 3

# Chạy cho một ngày cụ thể
python3 main.py --sports football --date 2026-11-25 --limit 10
```

Dữ liệu sẽ tự động được làm sạch và lưu tại `output/{sport_name}/{date}.json`.
