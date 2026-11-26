# TÀI LIỆU ĐẶC TẢ CHI TIẾT CÂY DỮ LIỆU (API ARCHITECTURE TREE) 10 MÔN THỂ THAO
**Dự án:** `nguon2-uniscore` (Data Pipeline & Reverse API UniScore)  
**Phiên bản:** 2.0.0 (Bản nâng cấp nghiệp vụ chuyên sâu 10 môn thể thao)  
**Tác giả:** Senior Data Engineer  
**Cập nhật lần cuối:** 2026-11-25

---

## MỤC LỤC
1. [Kiến Trúc Lõi Dùng Chung 4 Tầng (Core 4-Tier Architecture)](#1-kien-truc-loi-dung-chung-4-tang)
   - [Tầng 1: Taxonomy (Danh mục phân loại & Bảng xếp hạng)](#tang-1-taxonomy-danh-muc-phan-loai--bang-xep-hang)
   - [Tầng 2: Entities (Thực thể tĩnh & Đội hình ra sân)](#tang-2-entities-thuc-the-tinh--doi-hinh-ra-san)
   - [Tầng 3: Match Timeline (Thời gian thực, Tỷ số nâng cao & Trạng thái)](#tang-3-match-timeline-thoi-gian-thuc-ty-so-nang-cao--trang-thai)
   - [Tầng 4: Match Center (Diễn biến chi tiết, Sự kiện chuyên sâu & Thống kê)](#tang-4-match-center-dien-bien-chi-tiet-su-kien-chuyen-sau--thong-ke)
2. [Cây API & Nghiệp Vụ Chuyên Sâu Từng Môn](#2-cay-api--nghiep-vu-chuyen-sau-tung-mon)
   - [Môn 1: Bóng Đá (Football / Soccer)](#mon-1-bong-da-football--soccer)
   - [Môn 2: Bóng Rổ (Basketball)](#mon-2-bong-ro-basketball)
   - [Môn 3: Quần Vợt (Tennis)](#mon-3-quan-vot-tennis)
   - [Môn 4: Bóng Bầu Dục Mỹ (American Football)](#mon-4-bong-bau-duc-my-american-football)
   - [Môn 5: Bóng Chày (Baseball)](#mon-5-bong-chay-baseball)
   - [Môn 6: Cầu Lông (Badminton)](#mon-6-cau-long-badminton)
   - [Môn 7: Bóng Chuyền (Volleyball)](#mon-7-bong-chuyen-volleyball)
   - [Môn 8: Cricket](#mon-8-cricket)
   - [Môn 9: Khúc Côn Cầu Trên Băng (Ice Hockey)](#mon-9-khuc-con-cau-tren-bang-ice-hockey)
   - [Môn 10: Bóng Bàn (Table Tennis)](#mon-10-bong-ban-table-tennis)
3. [Quy Chuẩn Trạng Thái Trận Đấu (Match State Enums & Phân Tách Special States)](#3-quy-chuan-trang-thai-tran-dau-match-state-enums)
4. [Hướng Dẫn Tích Hợp Cho Lập Trình Viên (Developer Guide & SQLite Schema)](#4-huong-dan-tich-hop-cho-lap-trinh-vien-developer-guide)

---

# 1. KIẾN TRÚC LÕI DÙNG CHUNG 4 TẦNG

Mọi môn thể thao trong hệ thống đều được chuẩn hóa thành một cấu trúc cây thống nhất gồm 4 tầng phân cấp. Thiết kế này giúp người xem (dù là Lập trình viên Backend, Frontend hay Chuyên viên Phân tích Dữ liệu) có thể nắm bắt dữ liệu ngay lập tức.

```
📦 GÓI DỮ LIỆU TRẬN ĐẤU (Match Tree Object)
 ├── 1. taxonomy        -> Phân cấp hành chính giải đấu (Môn -> Quốc gia -> Giải đấu -> Bảng xếp hạng)
 ├── 2. entities        -> Thực thể tĩnh tham gia (Đội bóng/VĐV -> Sân bãi -> Trọng tài -> Đội hình)
 ├── 3. match_timeline  -> Dòng thời gian, Đồng hồ thực & Điểm số đa chiều (Sắp đấu/Đang đấu/Kết thúc/Ngoại lệ)
 └── 4. match_center    -> Diễn biến chi tiết, Thống kê chuyên môn & Sự kiện thực địa
```

---

### TẦNG 1: TAXONOMY (DANH MỤC PHÂN LOẠI & BẢNG XẾP HẠNG)

Tầng 1 chứa toàn bộ thông tin nguồn gốc, phân loại hành chính, tính chất giải đấu và bảng xếp hạng:

```json
"taxonomy": {
  "sport": {
    "id": 1,
    "name": "Football",
    "vietnamese_name": "Bóng đá"
  },
  "country_region": {
    "id": "c9dxsq8o5wt1o5r",
    "name": "Europe",
    "iso_code": "EU",
    "flag_url": "https://img.uniscore.com/country/europe.png"
  },
  "tournament_league": {
    "id": "e4r9x31d102ql9p",
    "name": "UEFA Champions League",
    "slug": "uefa-champions-league",
    "season": "2025/2026",
    "round": "Round of 16 - 2nd leg",
    "gender": "male",             // "male" | "female" | "mixed"
    "age_group": "senior",        // "senior" | "u23" | "u21" | "u19" | "u17"
    "stage_type": "knockout",     // "league" | "group" | "knockout"
    "group_name": null,           // Tên bảng (vd: "Group A", "Bảng B") nếu stage_type = "group"
    "leg": 2,                     // Lượt trận: 1 hoặc 2 (cho các vòng knock-out 2 lượt đi-về)
    "standings_available": true,  // Có bảng xếp hạng hay không
    "standings": [                // Cấu trúc bảng xếp hạng chi tiết khi có sẵn
      {
        "rank": 1,
        "team": { "id": "t1", "name": "Real Madrid" },
        "played": 6,
        "wins": 5,
        "draws": 1,
        "losses": 0,
        "goals_for": 15,
        "goals_against": 4,
        "diff": 11,
        "points": 16,
        "form": ["W", "W", "D", "W", "W"],
        "zone_status": "promotion_round_of_16"
      }
    ]
  }
}
```

* **Mô tả chi tiết các trường mới bổ sung:**
  - `gender`: Phân định giới tính giải đấu (`"male"`: nam, `"female"`: nữ, `"mixed"`: đôi nam nữ phối hợp).
  - `age_group`: Nhóm tuổi (`"senior"`: giải vô địch quốc gia/chuyên nghiệp, `"u23"`, `"u21"`, `"u19"`, `"u17"`: lứa trẻ đào tạo).
  - `stage_type`: Phân loại giai đoạn giải (`"league"`: thi đấu vòng tròn tính điểm, `"group"`: vòng bảng, `"knockout"`: loại trực tiếp).
  - `group_name`: Tên bảng đấu (vd: `"Group A"` hoặc `"Bảng D"`), tự động trích xuất từ dữ liệu vòng đấu.
  - `leg`: Lượt trận (`1`: lượt đi, `2`: lượt về) cho các trận đấu loại trực tiếp 2 lượt.
  - `standings`: Cấu trúc bảng điểm chuẩn gồm thứ hạng (`rank`), số trận (`played`), thắng - hòa - thua (`wins`, `draws`, `losses`), bàn thắng/điểm ghi (`goals_for`), bàn thua/điểm mất (`goals_against`), hiệu số (`diff`), điểm số (`points`), phong độ 5 trận gần nhất (`form`), và phân vùng đi tiếp/xuống hạng (`zone_status`).

---

### TẦNG 2: ENTITIES (THỰC THỂ TĨNH & ĐỘI HÌNH RA SÂN)

Lưu trữ danh tính hai đối thủ tham gia thi đấu, địa điểm sân bãi, trọng tài và danh sách đăng ký thi đấu (Roster/Squad):

```json
"entities": {
  "teams_or_players": {
    "home": {
      "id": "home_team_id",
      "name": "Arsenal",
      "slug": "arsenal",
      "logo_url": "https://img.uniscore.com/football/team/home_team_id/image/small/dark"
    },
    "away": {
      "id": "away_team_id",
      "name": "Bayern Munich",
      "slug": "bayern-munich",
      "logo_url": "https://img.uniscore.com/football/team/away_team_id/image/small/dark"
    }
  },
  "venue": {
    "id": "v_102",
    "name": "Emirates Stadium",
    "city": "London",
    "capacity": 60704
  },
  "match_officials": [
    {
      "name": "Szymon Marciniak",
      "role": "Main Referee",
      "nationality": "Poland"
    }
  ],
  "squad_roster": {
    "home": [
      {
        "id": "p_881",
        "name": "Bukayo Saka",
        "shirt_number": "7",
        "position": "FW",
        "is_substitute": false,
        "stats": { "rating": 7.8, "goals": 1, "shots": 4 }
      }
    ],
    "away": []
  }
}
```

---

### TẦNG 3: MATCH TIMELINE (THỜI GIAN THỰC, TỶ SỐ NÂNG CAO & TRẠNG THÁI)

Tầng 3 cung cấp đồng hồ trận đấu thời gian thực và cấu trúc điểm số đa tầng (Full-time, hiệp phụ, tỷ số tổng 2 lượt, tổng điểm môn đánh Set, điểm mục tiêu Cricket):

```json
"match_timeline": {
  "state_category": "Live", // "Upcoming" | "Live" | "Finished" | "Special (...)"
  "status_code": 2,
  "status_description": "2H",
  "start_timestamp": 1795594200,
  "start_time_iso": "2026-11-25T20:00:00Z",
  "readtime": {
    "minute": "68'",
    "period_state": "2H",
    "remain_seconds": 0
  },
  "scores": {
    "home_total": 2,
    "away_total": 1,
    "period_breakdown": {
      "home": { "period1": 1, "period2": 1, "overTime": 0, "penalty": 0 },
      "away": { "period1": 1, "period2": 0, "overTime": 0, "penalty": 0 }
    },
    "aggregate_score": { "home": 3, "away": 3 }, // Tỷ số tổng sau 2 lượt trận knock-out
    "total_points": null,                        // Tổng điểm xét hiệu số cho Cầu lông/Bóng chuyền/Bóng bàn
    "target_score": null                         // Điểm số mục tiêu cần vượt qua cho Cricket
  }
}
```

* **Mô tả các trường điểm mới bổ sung:**
  - `aggregate_score`: `{ home: int, away: int }` - Tổng tỷ số kết hợp sau 2 lượt đấu đi và về tại các vòng knock-out cúp châu lục (vd: Cúp C1, C2, Europa League, AFC Champions League).
  - `total_points`: `{ home: int, away: int }` - Tổng điểm tích lũy của toàn bộ các Set đối với nhóm môn tính điểm Set (Cầu lông, Bóng chuyền, Bóng bàn), dùng để phân định thứ hạng theo chỉ số phụ khi bằng điểm.
  - `target_score`: `int` - Điểm số mục tiêu mà đội đánh sau trong môn Cricket cần đạt được để giành chiến thắng trong trận đấu.

---

### TẦNG 4: MATCH CENTER (DIỄN BIẾN CHI TIẾT, SỰ KIỆN CHUYÊN SÂU & THỐNG KÊ)

Tầng 4 lưu trữ toàn bộ sự kiện diễn ra từng phút (Timeline Events), sơ đồ đội hình chiến thuật và các chỉ số thống kê chuyên sâu:

```json
"match_center": {
  "live_events_summary": [
    {
      "type": "goal",                     // "goal" | "missed_penalty" | "card" | "substitution" | ...
      "goal_type": "penalty",             // "regular" | "own_goal" | "penalty" (Bóng đá)
      "goal_strength": null,              // "EV" | "PPG" | "SHG" | "ENG" (Khúc côn cầu)
      "scoring_type": null,               // "touchdown" | "field_goal" | "2pt_conversion" (Bóng bầu dục)
      "time_minute": 54,
      "period": 2,
      "scorer": "Bukayo Saka",
      "assist": null,
      "card_type": null,                  // "yellow" | "red" | "yellow_red"
      "score_at_incident": "2-1"
    }
  ],
  "match_statistics": [
    {
      "period": "ALL",
      "groups": [
        {
          "group_name": "Possession & Shots",
          "items": [
            { "name": "Ball Possession", "home": "56%", "away": "44%", "home_value": 56, "away_value": 44 },
            { "name": "Total Shots", "home": "14", "away": "8", "home_value": 14, "away_value": 8 }
          ]
        }
      ]
    }
  ],
  "confirmed_lineups": {
    "is_confirmed": true,
    "home_formation": "4-3-3",
    "away_formation": "4-2-3-1"
  },
  "drive_status": null, // Dành riêng cho Bóng bầu dục Mỹ
  "h2h_summary": null
}
```

---

# 2. CÂY API & NGHIỆP VỤ CHUYÊN SÂU TỪNG MÔN

---

### MÔN 1: BÓNG ĐÁ (FOOTBALL / SOCCER)
* **Đặc trưng nghiệp vụ:** 
  - 2 hiệp chính (45' + bù giờ), có hiệp phụ (ET: 2 hiệp 15') và luân lưu (PEN: 11m).
  - Có các vòng đấu loại trực tiếp 2 lượt đi-về yêu cầu tính `aggregate_score`.
  - Phân loại bàn thắng: Bàn thường (`"regular"`), Phản lưới nhà (`"own_goal"`), Phạt đền thành công (`"penalty"`).
  - Ghi nhận tình huống sút hỏng phạt đền (`"missed_penalty"`).
  - Phân loại thẻ phạt chi tiết: Thẻ vàng (`"yellow"`), Thẻ đỏ trực tiếp (`"red"`), Thẻ vàng thứ 2 gián tiếp thành thẻ đỏ (`"yellow_red"`).

```
Football (Bóng đá)
├── 1. taxonomy
│    ├── sport: { id: 1, name: "Football", vietnamese_name: "Bóng đá" }
│    ├── country_region: [ID, Name, ISO Code, Flag URL]
│    └── tournament_league:
│         ├── gender: "male" | "female"
│         ├── age_group: "senior" | "u23" | "u21" | "u19"
│         ├── stage_type: "league" | "group" | "knockout"
│         ├── group_name: "Group A" | "Group B" | null
│         ├── leg: 1 | 2
│         ├── standings_available: true / false
│         └── standings: Bảng điểm xếp hạng đầy đủ
│
├── 2. entities
│    ├── teams_or_players: [home, away] (Tên, Logo, Slug)
│    ├── venue: Sân vận động, Thành phố, Sức chứa
│    ├── match_officials: Trọng tài chính, trọng tài biên, VAR
│    └── squad_roster: Đội hình ra sân chính thức & Cầu thủ dự bị kèm vị trí và số áo
│
├── 3. match_timeline
│    ├── state_category: "Upcoming" | "Live" | "Finished" | "Special (...)"
│    ├── readtime: Phút bóng lăn (vd: "45+2'", "78'") + Hiệp đấu ("1H", "HT", "2H", "ET", "PEN")
│    └── scores:
│         ├── home_total / away_total: Tổng tỷ số full-time
│         ├── period_breakdown: Tỷ số Hiệp 1, Hiệp 2, Hiệp phụ, Luân lưu
│         └── aggregate_score: { home: int, away: int } (Tỷ số tổng 2 lượt trận đi-về)
│
└── 4. match_center
     ├── live_events_summary:
     │    ├── type: "goal" | "missed_penalty" | "card" | "substitution" | "var"
     │    ├── goal_type: "regular" | "own_goal" | "penalty"
     │    ├── card_type: "yellow" | "red" | "yellow_red"
     │    ├── time_minute: Phút xảy ra sự kiện
     │    ├── scorer: Người ghi bàn (kèm người kiến tạo assist)
     │    └── player_in / player_out: Cầu thủ vào sân / rời sân khi thay người
     ├── match_statistics: Kiểm soát bóng (Possession), Số cú sút, Phạt góc, Việt vị, Phạm lỗi
     └── confirmed_lineups: Sơ đồ chiến thuật (vd: "4-3-3", "3-5-2")
```

---

### MÔN 2: BÓNG RỔ (BASKETBALL)
* **Đặc trưng nghiệp vụ:** 
  - 4 hiệp thi đấu (Q1, Q2, Q3, Q4, mỗi hiệp 10 hoặc 12 phút) với đồng hồ đếm ngược từng giây.
  - Điểm số liên tục nhảy theo các bậc 1 điểm (Free Throw FT), 2 điểm (2PT), 3 điểm (3PT).
  - Quản lý lỗi cá nhân (Personal Fouls) và chỉ số Box Score từng cầu thủ.

```
Basketball (Bóng rổ)
├── 1. taxonomy: Môn -> Quốc gia (USA, Châu Âu) -> Giải đấu (NBA, EuroLeague, FIBA) -> gender, age_group, standings
├── 2. entities: Đội chủ nhà & Đội khách -> Sân bóng (Arena) -> Danh sách cầu thủ theo vị trí (PG, SG, SF, PF, C)
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished"
│    ├── readtime: Hiệp đang đấu (Q1-Q4, OT) + Đồng hồ đếm ngược thời gian trong hiệp (vd: "03:45")
│    └── scores:
│         ├── home_total / away_total: Tổng điểm số (vd: 112 - 105)
│         └── period_breakdown: Điểm từng hiệp Q1, Q2, Q3, Q4 và hiệp phụ OT
└── 4. match_center:
     ├── live_events_summary: Tường thuật pha ghi điểm (Play-by-play): Cầu thủ ném rổ, loại ném 3PT/2PT/FT
     ├── match_statistics: Tỷ lệ ném rổ FG%, Tỷ lệ ném 3 điểm 3P%, Rebounds, Assists, Steals, Blocks, Turnovers
     └── player_box_score: Bảng điểm từng cầu thủ (Số phút thi đấu, Điểm, Bắt bóng bật bảng, Kiến tạo, Lỗi)
```

---

### MÔN 3: QUẦN VỢT (TENNIS)
* **Đặc trưng nghiệp vụ:** 
  - Hệ thống tỷ số 3 cấp độ: Điểm trong Game (0-15-30-40-AD) ➡️ Game trong Set (chạm 6 hoặc 7) ➡️ Set trong Trận (thắng 2/3 hoặc 3/5 set).
  - Điểm Tie-break: Khi một Set đạt tỷ số 7-6 hoặc 6-7, hệ thống lưu trữ thêm điểm số Tie-break chi tiết (`tiebreak_home`, `tiebreak_away`).
  - Đánh dấu người cầm giao bóng trực tiếp (`live_serving`).
  - Xử lý các trường hợp VĐV bỏ cuộc hoặc thắng cuộc do đối thủ rút lui (`Special (Retired / Walkover)`).

```
Tennis (Quần vợt)
├── 1. taxonomy: Môn -> Hệ thống giải (ATP, WTA, Grand Slam) -> Tên giải đấu -> Mặt sân (Hard, Clay, Grass)
├── 2. entities: VĐV đơn hoặc Cặp VĐV đánh đôi [Họ tên, Quốc tịch, Hạt giống giải đấu]
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished" | "Special (Retired / Walkover)"
│    ├── readtime: Tổng thời gian thi đấu trên sân + Set đang diễn ra
│    └── scores:
│         ├── home_total / away_total: Tổng số Set đã thắng (vd: 2 - 1)
│         └── period_breakdown: Tỷ số Game của từng Set (period1 -> period5)
│              └── Khi tỷ số 7-6 hoặc 6-7: Lưu thêm { tiebreak_home: int, tiebreak_away: int } (vd: 7-4)
└── 4. match_center:
     ├── live_serving: Bên đang cầm quyền giao bóng (home hoặc away)
     ├── current_game_score: Điểm của Game đang đánh (vd: "40-30" hoặc "Tie-break 5-4")
     └── match_statistics: Cú phát bóng ăn điểm trực tiếp (Aces), Lỗi giao bóng kép (Double Faults), Tỷ lệ giao bóng 1, Điểm bẻ giao bóng (Break Points Converted)
```

---

### MÔN 4: BÓNG BẦU DỤC MỸ (AMERICAN FOOTBALL)
* **Đặc trưng nghiệp vụ:** 
  - 4 hiệp thi đấu (Quarters). Cách tính điểm: Touchdown (6 điểm), Field Goal (3 điểm), Safety (2 điểm), Extra Point (1 điểm).
  - Bổ sung loại ghi điểm chuyển đổi 2 điểm sau Touchdown: `2pt_conversion`.
  - Bộ theo dõi lượt tấn công chuyên sâu `drive_status`: Đội đang cầm quyền tấn công (`possession_team`: "home" | "away"), số lượt và khoảng cách (`down_and_distance`: vd "2nd & 8"), và vị trí bóng trên sân (`field_position`: vd "OPP 35").

```
American Football (Bóng bầu dục Mỹ)
├── 1. taxonomy: Môn -> Quốc gia (USA) -> Giải đấu (NFL, NCAA) -> gender, standings
├── 2. entities: Đội bóng [Tên, Logo, Sân vận động] -> Danh sách VĐV (QB, RB, WR, TE, K...)
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished"
│    ├── readtime: Hiệp đang đấu (Q1-Q4) + Đồng hồ đếm ngược thời gian trong hiệp
│    └── scores:
│         ├── home_total / away_total: Tổng điểm toàn trận (vd: 28 - 24)
│         └── period_breakdown: Điểm từng hiệp Q1, Q2, Q3, Q4, OT
└── 4. match_center:
     ├── drive_status:
     │    ├── possession_team: "home" | "away" (Đội đang cầm bóng tấn công)
     │    ├── down_and_distance: Lượt thử và khoảng cách cần đạt (vd: "1st & 10", "3rd & 4")
     │    └── field_position: Vị trí bóng trên sân cỏ (vd: "OPP 35", "OWN 20")
     ├── scoring_plays: Tóm tắt ai ghi điểm kèm phân loại:
     │    └── scoring_type: "touchdown" | "field_goal" | "2pt_conversion" | "extra_point" | "safety"
     └── match_statistics: Tổng số Yard tiến lên (Total Yards), Passing Yards, Rushing Yards, Turnovers
```

---

### MÔN 5: BÓNG CHÀY (BASEBALL)
* **Đặc trưng nghiệp vụ:** 
  - Không giới hạn thời gian chạy đồng hồ, trận đấu chia làm 9 hiệp (Innings), mỗi hiệp chia làm nửa đầu (Top) và nửa sau (Bottom).
  - Bảng tỷ số R-H-E: Runs (Điểm số), Hits (Đánh trúng bóng), Errors (Mắc lỗi phòng ngự).
  - Bộ đếm B-S-O (Ball - Strike - Out) và mô phỏng 3 góc chốt an toàn trên sân (Bases 1-2-3).

```
Baseball (Bóng chày)
├── 1. taxonomy: Môn -> Quốc gia (USA, Nhật Bản NPB, Hàn Quốc KBO) -> Giải đấu (MLB, WBC)
├── 2. entities: Đội bóng -> Cầu thủ ném bóng chính (Starting Pitcher), Danh sách 9 cầu thủ đánh bóng (Lineup 1-9)
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished"
│    ├── readtime: Nửa hiệp và hiệp thứ mấy (vd: "Top 4th", "Bottom 7th", "End 9th")
│    └── scores:
│         ├── home_total / away_total: Tổng số Runs ghi được (vd: 6 - 4)
│         └── period_breakdown: Điểm số của từng hiệp từ Inning 1 đến Inning 9 (và Extra Innings)
└── 4. match_center:
     ├── diamond_state: Trạng thái sân bóng trực tiếp
     │    ├── bso_counter: Số Balls (0-3), Số Strikes (0-2), Số Outs (0-2)
     │    └── bases_occupied: Trạng thái chốt 1, chốt 2, chốt 3 (có người hay trống)
     ├── match_statistics: Bảng thống kê toàn trận R-H-E (Runs, Hits, Errors)
     └── pitcher_batter_matchup: Chỉ số đối đầu giữa Pitcher ném bóng và Batter đánh bóng
```

---

### MÔN 6: CẦU LÔNG (BADMINTON)
* **Đặc trưng nghiệp vụ:** 
  - Thể thức 3 Set (thắng 2/3), mỗi Set chạm 21 điểm (cách biệt 2 điểm, tối đa 30 điểm).
  - Bổ sung trường `total_points` (`{ home: int, away: int }`) tính tổng số điểm ghi được qua toàn bộ các set để phục vụ xét chỉ số phụ trên bảng xếp hạng vòng tròn.
  - Theo dõi người có quyền phát cầu (`serving_indicator`).

```
Badminton (Cầu lông)
├── 1. taxonomy: Môn -> Hệ thống giải BWF (World Tour Super 1000/750/500/300, Olympic) -> Tên giải đấu
├── 2. entities: Vận động viên đơn hoặc Cặp VĐV đánh đôi [Tên, Quốc tịch, Thứ hạng BWF, Hạt giống]
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished" | "Special (Retired / Walkover)"
│    ├── readtime: Set đang đánh (Set 1 / Set 2 / Set 3) + Thời gian trận đấu
│    └── scores:
│         ├── home_total / away_total: Số Set đã thắng (vd: 2 - 1 hoặc 2 - 0)
│         ├── period_breakdown: Điểm chi tiết từng Set (period1, period2, period3)
│         └── total_points: { home: int, away: int } (Tổng điểm số cả trận, vd: { home: 60, away: 53 })
└── 4. match_center:
     ├── serving_indicator: Bên đang cầm quyền phát cầu
     ├── point_progression: Lịch sử nhảy điểm từng nhịp trong Set
     └── match_statistics: Chuỗi ghi điểm liên tiếp dài nhất, Tổng số pha đập cầu ghi điểm (Smash Winners)
```

---

### MÔN 7: BÓNG CHUYỀN (VOLLEYBALL)
* **Đặc trưng nghiệp vụ:** 
  - Thể thức 5 Set (thắng 3/5 set). Set 1-4 chạm 25 điểm; Set 5 quyết định chạm 15 điểm.
  - Bổ sung trường `total_points` (`{ home: int, away: int }`) tính tổng điểm số cả trận.
  - Theo dõi đội đang có quyền phát bóng và số lần xin hội ý chiến thuật (Timeouts) trong set.

```
Volleyball (Bóng chuyền)
├── 1. taxonomy: Môn -> Quốc gia / Châu lục -> Giải đấu (FIVB VNL, Vô địch thế giới, VĐQG) -> standings
├── 2. entities: Đội bóng -> Danh sách VĐV theo vị trí: Chủ công (OH), Phụ công (MB), Đối chuyền (OP), Chuyền hai (S), Libero (L)
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished"
│    ├── readtime: Set đang đánh (Set 1 đến Set 5) + Tổng thời gian thi đấu
│    └── scores:
│         ├── home_total / away_total: Số Set đã thắng (vd: 3 - 2)
│         ├── period_breakdown: Điểm số của từng Set (vd: Set 1: 25-23, Set 2: 20-25, Set 3: 25-21, Set 4: 22-25, Set 5: 15-12)
│         └── total_points: { home: int, away: int } (Tổng điểm tích lũy cả 5 Set, vd: { home: 107, away: 106 })
└── 4. match_center:
     ├── current_serving_team: Đội đang có quyền phát bóng
     ├── timeouts_used: Số lần xin hội ý của mỗi đội trong Set (tối đa 2 lần/set)
     └── match_statistics: Điểm tấn công (Attack Points), Điểm chắn bóng (Block Points), Điểm phát bóng ăn điểm trực tiếp (Serve Aces)
```

---

### MÔN 8: CRICKET
* **Đặc trưng nghiệp vụ:** 
  - Hỗ trợ thể thức đỉnh cao **Test Match** (thi đấu 5 ngày): Mỗi đội thi đấu 2 lượt đánh (`first_innings` và `second_innings` trong `period_breakdown`).
  - Hỗ trợ các thể thức giới hạn số bóng: ODI (50 Overs) và T20 (20 Overs).
  - Tỷ số hiển thị dạng: **Runs (Điểm) / Wickets (Số người bị loại)** kèm số Over (vd: `185/4 (20.0 ov)`).
  - Bổ sung trường `target_score`: Điểm số mục tiêu mà đội đánh sau bắt buộc phải vượt qua để giành chiến thắng.
  - Theo dõi tốc độ ghi điểm hiện tại (Current Run Rate CRR) và tốc độ cần đạt (Required Run Rate RRR).

```
Cricket
├── 1. taxonomy: Môn -> Quốc gia / Thể thức (Test Match, ODI, T20) -> Giải đấu (ICC World Cup, IPL)
├── 2. entities: Đội tuyển -> Đội trưởng (Captain), Thủ môn (Wicket-keeper), Danh sách 11 VĐV ra sân (Playing XI)
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished" | "Special (Suspended / Rain Delay)"
│    ├── readtime: Lượt đánh (1st/2nd Innings) + Số Over hiện tại (vd: "18.3 Overs")
│    └── scores:
│         ├── home_total / away_total: Điểm tổng kết
│         ├── period_breakdown:
│         │    ├── first_innings: { home: Điểm/Wickets, away: Điểm/Wickets }
│         │    └── second_innings: { home: Điểm/Wickets, away: Điểm/Wickets } (Dành cho Test Match)
│         └── target_score: Điểm số mục tiêu đội đánh sau cần đạt để thắng (vd: 186)
└── 4. match_center:
     ├── live_crease: Nhân sự đang đứng trên sân
     │    ├── 2 Batsmen đang đánh bóng: Họ tên, Điểm cá nhân, Số bóng đối mặt, Số cú đánh 4 điểm, Số cú 6 điểm
     │    └── 1 Bowler đang ném bóng: Họ tên, Số Over đã ném, Số điểm để mất, Số Wicket giành được
     └── run_rate: Tốc độ ghi điểm CRR và tốc độ yêu cầu RRR
```

---

### MÔN 9: KHÚC CÔN CẦU TRÊN BĂNG (ICE HOCKEY)
* **Đặc trưng nghiệp vụ:** 
  - 3 hiệp chính (Periods, mỗi hiệp 20 phút đếm ngược). Hiệp phụ OT và luân lưu sút luân lưu Shootout (SO).
  - Phân loại thế trận bàn thắng (`goal_strength`):
    + `"EV"`: Bàn thắng trong thế trận đều người (Even Strength, 5 đấu 5).
    + `"PPG"`: Bàn thắng khi đang hơn người trong thế trận Power Play (Power Play Goal, 5 đấu 4 hoặc 5 đấu 3).
    + `"SHG"`: Bàn thắng khi đội nhà đang thiếu người bị phạt (Short-Handed Goal).
    + `"ENG"`: Bàn thắng vào khung thành trống khi đối phương rút thủ môn (Empty Net Goal).
  - Quản lý buồng phạt (Penalty Box): Cầu thủ phạm lỗi bị phạt ngồi ngoài 2 phút (Minor) hoặc 5 phút (Major).

```
Ice Hockey (Khúc côn cầu trên băng)
├── 1. taxonomy: Môn -> Quốc gia (USA, Canada, Nga, Thụy Điển) -> Giải đấu (NHL, KHL) -> standings
├── 2. entities: Đội bóng -> Danh sách VĐV: Hàng tiền đạo, Hàng hậu vệ, Thủ môn xuất phát (Starting Goalie)
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished"
│    ├── readtime: Hiệp đang đấu (P1 / P2 / P3 / OT) + Đồng hồ đếm ngược 20 phút
│    └── scores:
│         ├── home_total / away_total: Tổng số bàn thắng vào lưới (vd: 5 - 3)
│         └── period_breakdown: Bàn thắng Hiệp 1, Hiệp 2, Hiệp 3, Hiệp phụ OT, Luân lưu Shootout (SO)
└── 4. match_center:
     ├── live_events_summary:
     │    ├── type: "goal" | "penalty"
     │    ├── goal_strength: "EV" | "PPG" | "SHG" | "ENG" (Bàn thắng đều người / Hơn người / Thiếu người / Lưới trống)
     │    ├── time_minute: Phút và giây ghi bàn
     │    └── scorer: Tên cầu thủ ghi bàn (kèm người kiến tạo 1 và kiến tạo 2)
     ├── penalties_box: Danh sách cầu thủ đang ngồi buồng phạt và thời gian phạt còn lại
     └── match_statistics: Cú sút trúng cầu môn (Shots on Goal SOG), Tỷ lệ thắng tranh bóng (Faceoff %), Số pha cản phá (Saves)
```

---

### MÔN 10: BÓNG BÀN (TABLE TENNIS)
* **Đặc trưng nghiệp vụ:** 
  - Thể thức 5 hoặc 7 Set (thắng 3/5 hoặc 4/7). Mỗi Set chạm 11 điểm (cách biệt 2 điểm).
  - Quyền giao bóng đổi luân phiên sau mỗi 2 điểm (khi hòa 10-10 thì đổi sau mỗi 1 điểm).
  - Bổ sung trường `total_points` (`{ home: int, away: int }`) tính tổng điểm số cả trận.
  - Xử lý các trường hợp bỏ cuộc giữa trận do chấn thương (`Special (Retired / Walkover)`).

```
Table Tennis (Bóng bàn)
├── 1. taxonomy: Môn -> Hệ thống giải ITTF / WTT (WTT Champions, Grand Smash, Olympic) -> Tên giải đấu
├── 2. entities: Vận động viên tham gia (Đơn nam, Đơn nữ, Đôi) [Tên, Quốc tịch, Hạng ITTF, Tay thuận, Lối cầm vợt]
├── 3. match_timeline:
│    ├── state_category: "Upcoming" | "Live" | "Finished" | "Special (Retired / Walkover)"
│    ├── readtime: Set đang đánh (Set 1 -> Set 7) + Thời gian trận đấu
│    └── scores:
│         ├── home_total / away_total: Tổng số Set đã thắng (vd: 4 - 2)
│         ├── period_breakdown: Điểm số của từng Set (vd: Set 1: 11-8, Set 2: 9-11, Set 3: 11-6...)
│         └── total_points: { home: int, away: int } (Tổng điểm tích lũy cả trận, vd: { home: 65, away: 58 })
└── 4. match_center:
     ├── current_server: VĐV đang cầm quyền giao bóng (đổi sau mỗi 2 điểm phát bóng)
     ├── point_by_point: Lịch sử từng điểm số trong Set đang diễn ra
     └── timeouts_called: Cờ báo VĐV đã dùng quyền xin hội ý chiến thuật 1 lần duy nhất trong trận
```

---

# 3. QUY CHUẨN TRẠNG THÁI TRẬN ĐẤU (MATCH STATE ENUMS)

Để đảm bảo dữ liệu luôn đồng nhất và giao diện người dùng hiển thị chuẩn xác, trường `state_category` trong toàn bộ 10 môn được chuẩn hóa vào các trạng thái lớn, đặc biệt phân tách rõ ràng **4 trạng thái ngoại lệ chuyên sâu**:

```
                              ┌── Upcoming (Chưa diễn ra)
                              ├── Live (Đang diễn ra thời gian thực)
                              ├── Finished (Đã kết thúc toàn trận)
Trạng thái lớn (state_category) │
                              └── Special (Ngoại lệ đặc thù):
                                    ├── Special (Postponed)
                                    ├── Special (Suspended / Rain Delay)
                                    ├── Special (Cancelled / Abandoned)
                                    └── Special (Retired / Walkover)
```

### 1. `Upcoming` (Chưa diễn ra):
* **Điều kiện:** `status_code = 0` hoặc mô tả là `"not_started"`.
* **Trọng tâm dữ liệu:** Thời gian bắt đầu (`start_time_iso`), đội hình dự kiến, phân tích phong độ 5 trận gần nhất (`form`) và lịch sử đối đầu H2H.

### 2. `Live` (Đang diễn ra thời gian thực):
* **Điều kiện:** `status_code` từ `1` đến `9`, hoặc mô tả là `"1H"`, `"2H"`, `"Q1-Q4"`, `"Set 1-5"`, `"Inning 1-9"`, `"P1-P3"`.
* **Trọng tâm dữ liệu:** Đồng hồ thi đấu thực (`readtime`), bảng tỷ số đang nhảy, luồng sự kiện bàn thắng/thẻ phạt/thay người, người giao bóng, tình trạng đợt tấn công (`drive_status`).

### 3. `Finished` (Đã kết thúc toàn trận):
* **Điều kiện:** `status_code = 10` hoặc mô tả là `"ended"`, `"FT"`, `"AET"`, `"PEN"`.
* **Trọng tâm dữ liệu:** Tỷ số chung cuộc toàn trận, tỷ số từng hiệp, tổng hợp bàn thắng/thẻ phạt, bảng chỉ số thống kê chuyên môn toàn trận (Box Score).

### 4. Nhóm `Special` (Ngoại lệ đặc thù):
Hệ thống phân tách rạch ròi 4 trường hợp ngoại lệ để UI/UX xử lý tương ứng:
* **`Special (Postponed)`:** Trận đấu bị **hoãn trước khi bắt đầu** do thời tiết xấu, an ninh, hoặc lịch thi đấu đột xuất. Giao diện người dùng hiển thị nhãn "Hoãn" (Postponed), dữ liệu tỷ số giữ nguyên null.
* **`Special (Suspended / Rain Delay)`:** Trận đấu bị **tạm dừng giữa chừng** (vd: mưa lớn trong Tennis, Cricket hoặc sự cố mất điện sân vận động). Trận đấu **giữ nguyên tỷ số hiện tại** và sẽ được tiếp tục thi đấu lại khi điều kiện cho phép.
* **`Special (Cancelled / Abandoned)`:** Trận đấu bị **hủy bỏ hoàn toàn** hoặc bị trọng tài cho dừng vĩnh viễn do bạo động khán đài, ẩu đả hoặc thiên tai. Trận đấu không được tính kết quả hoặc chờ ban tổ chức xử phạt.
* **`Special (Retired / Walkover)`:** Trận đấu kết thúc sớm do một bên **chấn thương bỏ cuộc (Retired)** hoặc **không thể ra sân thi đấu (Walkover / Xử thua)** trong các môn thể thao đối kháng cá nhân (Quần vợt, Cầu lông, Bóng bàn). Điểm số thắng cuộc được xử trao cho đối thủ còn lại.

---

# 4. HƯỚNG DẪN TÍCH HỢP CHO LẬP TRÌNH VIÊN (DEVELOPER GUIDE)

### Cấu Trúc Bảng SQLite (`sports_database.sqlite`)
Cơ sở dữ liệu SQLite trong dự án `nguon2-uniscore` được thiết kế theo mô hình lai: Vừa lưu các bảng quan hệ (Relational Tables) chuẩn hóa để tìm kiếm, vừa lưu trữ toàn bộ cây dữ liệu 4 tầng nguyên bản dạng JSON (`raw_json`) để API Backend có thể lấy nguyên cây trả về cho Frontend mà không cần thực hiện nhiều phép JOIN phức tạp.

```sql
-- 1. Bảng Môn Thể Thao
CREATE TABLE sports (
    id INTEGER PRIMARY KEY,
    name TEXT UNIQUE,
    vietnamese_name TEXT
);

-- 2. Bảng Giải Đấu
CREATE TABLE tournaments (
    id TEXT PRIMARY KEY,
    sport_id INTEGER,
    name TEXT,
    slug TEXT,
    country_name TEXT,
    country_code TEXT,
    FOREIGN KEY (sport_id) REFERENCES sports(id)
);

-- 3. Bảng Đội Bóng / Vận Động Viên
CREATE TABLE teams (
    id TEXT PRIMARY KEY,
    name TEXT,
    slug TEXT,
    logo_url TEXT
);

-- 4. Bảng Trận Đấu Master (Kèm Raw JSON)
CREATE TABLE matches (
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
    raw_json TEXT,                     -- Cây 4 tầng hoàn chỉnh dạng JSON
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Bảng Sự Kiện Trực Tiếp Nâng Cao
CREATE TABLE match_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_id TEXT,
    type TEXT,                          -- "goal", "card", "missed_penalty", "sub"
    time_minute INTEGER,
    period INTEGER,
    scorer TEXT,
    assist TEXT,
    card_type TEXT,                     -- "yellow", "red", "yellow_red"
    goal_type TEXT,                     -- "regular", "own_goal", "penalty"
    goal_strength TEXT,                 -- "EV", "PPG", "SHG", "ENG"
    scoring_type TEXT,                  -- "touchdown", "field_goal", "2pt_conversion"
    player_in TEXT,
    player_out TEXT,
    score_at_incident TEXT,
    FOREIGN KEY (match_id) REFERENCES matches(id)
);
```

### Ví Dụ Truy Vấn Thực Tế Trong Python
```python
import sqlite3
import json

conn = sqlite3.connect("sports_database.sqlite")
cursor = conn.cursor()

# 1. Truy vấn các bàn thắng từ chấm phạt đền (penalty):
cursor.execute("""
    SELECT m.slug, e.time_minute, e.scorer, e.score_at_incident
    FROM match_events e
    JOIN matches m ON e.match_id = m.id
    WHERE e.type = 'goal' AND e.goal_type = 'penalty';
""")
penalties = cursor.fetchall()
print("Danh sách bàn thắng Penalty:", penalties)

# 2. Rút toàn bộ cây dữ liệu 4 tầng của 1 trận đấu để làm API RESTful:
cursor.execute("SELECT raw_json FROM matches WHERE id = 'sample_match_id';")
row = cursor.fetchone()
if row:
    match_tree = json.loads(row[0])
    # Dữ liệu sẵn sàng trả ra response JSON cho Mobile App / Web Frontend:
    print("Tournament Gender:", match_tree["taxonomy"]["tournament_league"]["gender"])
    print("Scores Breakdown:", match_tree["match_timeline"]["scores"]["period_breakdown"])
```

### Lệnh Điều Phối Pipeline Tự Động
```bash
cd /home/ubuntu/Documents/nguon2-uniscore

# Thu thập toàn bộ 10 môn thể thao cho ngày hiện tại:
python3 main.py --all-10-sports --limit 5

# Thu thập riêng môn Tennis và Ice Hockey:
python3 main.py --sports tennis ice_hockey --limit 10

# Thu thập dữ liệu theo một ngày cụ thể trong quá khứ:
python3 main.py --all-10-sports --date 2026-11-20 --limit 5
```
