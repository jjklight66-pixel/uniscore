# HƯỚNG DẪN TOÀN DIỆN HỆ THỐNG THỂ THAO TRỰC TIẾP UNISCORE (10 MÔN THỂ THAO)

**Dự án:** `sport` (Nền tảng Web Thể thao PHP Laravel + REST API v1 cho Mobile App + Pipeline Thu thập & Mission Control Python FastAPI)  
**Phiên bản:** `2.1 Production (Ready for Mobile App)`  
**Phạm vi:** 10 môn thể thao (*Bóng đá, Bóng rổ, Quần vợt, Bóng bầu dục Mỹ, Bóng chày, Cầu lông, Bóng chuyền, Cricket, Khúc côn cầu trên băng, Bóng bàn*)

---

## TRUY CẬP NHANH TÀI LIỆU & API CHO MOBILE APP (FLUTTER / REACT NATIVE / ANDROID / IOS)

Hệ thống đã tích hợp sẵn bộ **REST API v1 (Stateless, CORS, Rate Limit, Bearer Token Auth, DB-First)**. Xem nhanh các tài liệu & mã nguồn API tại đây:

| Tài nguyên | Link trực tiếp | Mô tả |
|---|---|---|
| **Tài liệu hướng dẫn tích hợp API App** | [API_APP_DOCS.md](./API_APP_DOCS.md) | Danh sách endpoint, format JSON, ví dụ code Flutter & React Native |
| **File đặc tả OpenAPI 3.0 (Postman)** | [web/public/openapi.json](./web/public/openapi.json) | Import trực tiếp vào Postman / Insomnia / OpenAPI Generator |
| **Swagger UI tương tác (khi chạy local)** | `http://localhost:8000/api/docs` | Giao diện test trực tiếp mọi API ngay trên trình duyệt |
| **Danh sách Routes API v1** | [web/routes/api.php](./web/routes/api.php) | Toàn bộ đường dẫn `/api/v1/*` |
| **API Dữ liệu 10 môn thể thao** | [SportsApiController.php](./web/app/Http/Controllers/Api/V1/SportsApiController.php) | `health`, `sports`, `matches`, `live`, `categories`, `leagues`, `competitions` |
| **API Đăng ký / Đăng nhập Token** | [AuthApiController.php](./web/app/Http/Controllers/Api/V1/AuthApiController.php) | `register`, `login`, `me`, `logout` (Bearer Token SHA-256) |
| **API Yêu thích & Push Notification** | [UserAppController.php](./web/app/Http/Controllers/Api/V1/UserAppController.php) | `favorites` (thêm/xóa/toggle) & `devices` (FCM / APNs token) |
| **Hướng dẫn hệ thống chi tiết** | [HUONG_DAN_HE_THONG.md](./HUONG_DAN_HE_THONG.md) | Giải thích kiến trúc, giao diện 10 môn, Admin Panel & Mission Control |
| **Cây API 10 môn thể thao gốc** | [DOCUMENTATION_API_TREES_10_SPORTS.md](./nguon2-uniscore/DOCUMENTATION_API_TREES_10_SPORTS.md) | Đặc tả chi tiết cấu trúc dữ liệu từng môn của nguồn UniScore |

### Bảng tóm tắt Endpoint `/api/v1/*`
- **Hệ thống & Môn:** `GET /api/v1/health` · `GET /api/v1/sports`
- **Trận đấu & Live:** `GET /api/v1/matches` · `GET /api/v1/matches/{sport}/{eventId}` · `GET /api/v1/matches/{sport}/{eventId}/extended` · `GET /api/v1/live/{sport}`
- **Quốc gia & Giải đấu:** `GET /api/v1/categories/{sport}` · `GET /api/v1/leagues/{sport}/top` · `GET /api/v1/leagues/{sport}/category/{categoryId}` · `GET /api/v1/competitions/{sport}/{id}`
- **Xác thực (Bearer Token):** `POST /api/v1/auth/register` · `POST /api/v1/auth/login` · `GET /api/v1/auth/me` · `POST /api/v1/auth/logout`
- **Yêu thích & Push Token:** `GET /api/v1/favorites` · `POST /api/v1/favorites` · `POST /api/v1/favorites/toggle` · `DELETE /api/v1/favorites/{id}` · `POST /api/v1/devices/register` · `DELETE /api/v1/devices/{token}`

---

## MỤC LỤC
1. [Tổng Quan Kiến Trúc & Cấu Trúc Thư Mục](#1-tong-quan-kien-truc--cau-truc-thu-muc)
2. [Nguyên Lý Hoạt Động Cốt Lõi Của Hệ Thống](#2-nguyen-ly-hoat-dong-cot-loi-cua-he-thong)
3. [Hướng Dẫn Cài Đặt & Khởi Chạy](#3-huong-dan-cai-dat--khoi-chay)
4. [Giải Thích Chi Tiết Từng Mục Trên Giao Diện Người Dùng (Trang Chủ)](#4-giai-thich-chi-tiet-tung-muc-tren-giao-dien-nguoi-dung)
5. [Đặc Thù Hiển Thị Của Từng Môn Trong 10 Môn Thể Thao](#5-dac-thu-hien-thi-cua-tung-mon-trong-10-mon-the-thao)
6. [Giải Thích Chi Tiết Trang Giải Đấu (Competition Page)](#6-giai-thich-chi-tiet-trang-giai-dau)
7. [Giải Thích Chi Tiết Trang Quản Trị Admin (`/admin`)](#7-giai-thich-chi-tiet-trang-quan-tri-admin)
8. [Giải Thích Chi Tiết Mission Control Dashboard (`:8501`)](#8-giai-thich-chi-tiet-mission-control-dashboard)
9. [Cấu Trúc Cơ Sở Dữ Liệu & API Nội Bộ](#9-cau-truc-co-so-du-lieu--api-noi-bo)

---

## 1. TỔNG QUAN KIẾN TRÚC & CẤU TRÚC THƯ MỤC

Dự án nằm trong thư mục gốc `sport/` gồm 2 phân hệ liên kết chặt chẽ:

```text
sport/
├── README.md                              -> Tóm tắt dự án (hiển thị trên GitHub)
├── HUONG_DAN_HE_THONG.md                  -> Tài liệu hướng dẫn chi tiết này
├── .gitignore                             -> Danh sách file không đưa lên GitHub
│
├── web/                                   -> [PHÂN HỆ 1] Ứng dụng Web PHP Laravel (Cổng 8000)
│   ├── app/
│   │   ├── Console/Commands/
│   │   │   ├── CrawlRangeCommand.php      -> Lệnh cào ngầm không giới hạn theo khoảng ngày & tốc độ request
│   │   │   └── SyncSportsData.php         -> Lệnh đồng bộ dữ liệu từ thư mục output vào SQLite
│   │   ├── Http/Controllers/
│   │   │   ├── SportsController.php       -> Điều phối Trang chủ, Trang Giải đấu, API AJAX & Admin CRUD
│   │   │   └── AdminAuthController.php    -> Xác thực Đăng nhập / Đăng xuất Admin
│   │   ├── Models/SportsMatch.php         -> Model bảng trận đấu trung tâm `sports_matches`
│   │   └── Services/UniScoreApiService.php -> Lõi kết nối UniScore, mã hóa ảnh CDN, DB-First & Cache-Through
│   ├── database/migrations/               -> Tạo bảng `sports_matches`, `users`, `cache`, `jobs`
│   ├── resources/views/
│   │   ├── uniscore/index.blade.php       -> Giao diện người dùng 4 cột chuẩn UniScore + 9 tab chi tiết
│   │   ├── uniscore/competition.blade.php -> Trang chi tiết giải đấu 9 tab (BXH, Chuyển nhượng, Nhánh đấu...)
│   │   └── admin/
│   │       ├── login.blade.php            -> Trang đăng nhập quản trị viên
│   │       └── index.blade.php            -> Trang quản trị Admin (CRUD, Cào ngầm, Bảo trì, Ưu tiên giải)
│   └── routes/web.php                     -> Khai báo toàn bộ đường dẫn Web & API nội bộ
│
└── nguon2-uniscore/                       -> [PHÂN HỆ 2] Bộ cào Python & Mission Control (Cổng 8501)
    ├── app.py                             -> Web Dashboard FastAPI + WebSocket real-time + Export CSV/Excel
    ├── config.py                          -> Cấu hình 10 môn thể thao & headers chống chặn
    ├── fetcher.py                         -> Module gọi API UniScore kèm exponential backoff retry
    ├── parser.py                          -> Bộ chuẩn hóa dữ liệu thô sang Cây 4 Tầng (Target Schema)
    ├── database.py                        -> Quản lý SQLite riêng của tool Python
    ├── storage.py                         -> Xuất file JSON phân cấp `output/{sport}/{date}.json`
    ├── main.py                            -> Chạy pipeline cào nhanh bằng dòng lệnh CLI
    ├── requirements.txt                   -> Danh sách thư viện Python cần thiết
    └── DOCUMENTATION_API_TREES_10_SPORTS.md -> Đặc tả kỹ thuật cây JSON 4 tầng của 10 môn
```

---

## 2. NGUYÊN LÝ HOẠT ĐỘNG CỐT LÕI CỦA HỆ THỐNG

### 2.1. Cơ chế Cache-Through Auto-Crawl ("Chưa có trong CSDL -> Tự động kéo & lưu")
- Dữ liệu của 10 môn thể thao qua 365 ngày là cực kỳ lớn, không cần cào trước toàn bộ.
- Khi người dùng truy cập bất kỳ **Môn thể thao** + **Ngày / Khoảng ngày** nào trên Trang chủ:
  1. Hệ thống kiểm tra trong bảng `sports_matches` (SQLite) trước.
  2. Nếu ngày đó **chưa có dữ liệu**, Laravel lập tức đóng vai trò trung gian gọi thẳng API gốc UniScore, giải mã đúng cấu trúc của môn đó, mã hóa link ảnh logo thật, **lưu toàn bộ vào SQLite** rồi hiển thị ngay cho người dùng.
  3. Từ lần truy cập sau, dữ liệu được đọc trực tiếp từ SQLite chỉ trong vài mili-giây.

### 2.2. Cơ chế DB-First Vĩnh Viễn cho trận đã có kết quả cuối (`Finished`)
- **Trận đã kết thúc (`Finished`) & Ngày quá khứ (`date < Hôm nay`):**
  - Ngay sau khi người dùng hoặc Admin mở xem chi tiết 1 lần, toàn bộ diễn biến (`detail_cached`) và dữ liệu mở rộng H2H/AI/Nhánh đấu (`extended_cached`) được **lưu vĩnh viễn** vào cột `payload` của SQLite.
  - Mọi lần click tiếp theo sẽ đọc **100% từ CSDL cục bộ** (hiển thị nhãn xanh **`💾 TỪ CSDL`** ở góc trên cột chi tiết), **không bao giờ phát sinh request ra trang gốc nữa**.
- **Chống ghi đè thông minh (Smart Skip) khi Admin cào hàng loạt:**
  - Khi Admin chạy cào một khoảng ngày rộng, mọi trận đã tồn tại trong DB với trạng thái `Finished` đều được **bỏ qua tự động**, không bị ghi đè mất dữ liệu chi tiết hay chỉnh sửa tay của Admin. Chỉ các trận mới hoặc đang `Upcoming`/`Live` mới được cập nhật.

### 2.3. Cập nhật Thời Gian Thực (Real-Time Live Engine)
- **Tab `Live` & Ngày hôm nay:** Luôn đồng bộ từ API Live chuyên dụng của UniScore (cache ngắn 12 giây) để hiển thị đủ toàn bộ các trận đang đá thật, đồng thời tự chuyển những trận đã hết giờ sang `Finished`.
- **Đồng hồ nhảy từng giây (1-Second Ticker):** Tính phút thi đấu theo đúng công thức gốc của UniScore (`status_code` + `currentPeriodStartTimestamp`), tự nhảy từng giây (`53:06'` -> `53:07'`) trên trình duyệt.
- **Tự làm mới mỗi 15 giây (Silent Polling):** Trình duyệt tự gọi ngầm `/api/live-updates/{sport}` để cập nhật tỷ số (nháy màu xanh khi có bàn thắng mới), bộ đếm `LIVE: X`, và tự làm mới ngầm nội dung **Chi Tiết**, **Thống Kê**, **BXH** của trận đang xem mà không cần F5.

### 2.4. Thuật toán mã hóa ảnh Logo CDN (`encodeImageId`)
- UniScore không dùng ID thực thể gốc để tải ảnh logo mà dịch chuyển ký tự theo mảng `[3, 1, 5, 9, 6, 2, 4, 7, 8, 0]`.
- Hệ thống đã tích hợp sẵn hàm `encodeImageId()` ở cả PHP và Python để hiển thị 100% ảnh thật từ `https://img.uniscore.com/` cho **Logo đội bóng**, **Logo giải đấu**, **Cờ quốc gia**, và **Ảnh đại diện cầu thủ**.

---

## 3. HƯỚNG DẪN CÀI ĐẶT & KHỞI CHẠY

### Yêu cầu môi trường
- **PHP >= 8.2** (kèm extension `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`) & **Composer**
- **Python >= 3.10** & **pip**

### Bước 1: Cài đặt & Khởi chạy Web Laravel (Cổng 8000)
```bash
cd sport/web
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# Tạo tài khoản Admin mặc định (admin@uniscore.local / admin123)
php artisan tinker --execute="App\Models\User::updateOrCreate(['email' => 'admin@uniscore.local'], ['name' => 'Super Admin', 'password' => Illuminate\Support\Facades\Hash::make('admin123')]);"

# Khởi chạy Web Server tại http://localhost:8000
php artisan serve --host 0.0.0.0 --port 8000
```

### Bước 2: Cài đặt & Khởi chạy Mission Control Python (Cổng 8501 — Tùy chọn)
```bash
cd sport/nguon2-uniscore
pip install -r requirements.txt

# Khởi chạy Mission Control Dashboard tại http://localhost:8501
uvicorn app:app --host 0.0.0.0 --port 8501
```

### Các đường dẫn truy cập chính
- **Trang chủ Người dùng:** `http://localhost:8000`
- **Trang Đăng nhập Admin:** `http://localhost:8000/admin/login` (`admin@uniscore.local` / `admin123`)
- **Trang Quản trị Admin:** `http://localhost:8000/admin`
- **Mission Control Dashboard:** `http://localhost:8501`

---

## 4. GIẢI THÍCH CHI TIẾT TỪNG MỤC TRÊN GIAO DIỆN NGƯỜI DÙNG

Giao diện trang chủ (`http://localhost:8000`) được thiết kế theo **bố cục 4 cột chuẩn UniScore**:

### 4.1. Thanh Tiêu Đề Trên Cùng (Top Navbar)
- **Logo `UNISCORE`:** Bấm để quay về trang chủ mặc định.
- **Ô Tìm kiếm (`🔍 Tìm kiếm đội bóng, giải đấu...`):** Gõ tên bất kỳ CLB, VĐV hoặc tên giải đấu rồi nhấn Enter để lọc nhanh các trận đấu.
- **Nhãn `● LIVE: X`:** Đếm tổng số trận đang thi đấu trực tiếp, tự cập nhật mỗi 15 giây.
- **Nút `XẾP HẠNG`:** Mở nhanh bảng xếp hạng của giải đấu hàng đầu thuộc môn đang chọn.
- **Nút `⚙️ Mission Control`:** Mở tab mới sang trung tâm điều khiển cào Python (cổng 8501).
- **Nút `🛠️ Admin`:** Đi tới trang quản trị hệ thống (`/admin`).

### 4.2. Cột 1 (Ngoài cùng bên trái) — Thanh Dọc 10 Môn Thể Thao
- Liệt kê dọc 10 môn kèm biểu tượng: ⚽ Bóng đá, 🏀 Bóng rổ, 🎾 Quần vợt, 🏈 Bóng bầu dục Mỹ, ⚾ Bóng chày, 🏸 Cầu lông, 🏐 Bóng chuyền, 🏏 Cricket, 🏒 Khúc côn cầu trên băng, 🏓 Bóng bàn.
- Môn đang chọn được tô viền vàng cam nổi bật. Bấm vào môn nào sẽ tải ngay toàn bộ giải đấu, quốc gia và lịch thi đấu của môn đó.

### 4.3. Cột 2 (Sidebar Trái) — 2 Khối Danh Mục Giải Đấu
1. **Khối `Giải Đấu Nổi Bật (★ TOP)`:**
   - Hiển thị 16 giải đấu hàng đầu của môn đang chọn (vd Bóng đá: *Premier League, La Liga, Champions League, V.League 1...*; Bóng rổ: *NBA, EuroLeague, WNBA...*) kèm logo giải thật.
   - Bấm vào tên bất kỳ giải nào để mở **Trang Chi Tiết Giải Đấu**.
2. **Khối `Tất Cả Giải Đấu (theo Quốc gia)`:**
   - Có ô **`🔍 Tìm quốc gia...`** lọc ngay lập tức theo tên nước.
   - Danh sách hơn 200 quốc gia/khu vực kèm cờ tròn và số trận đang có.
   - **Bấm vào tên một quốc gia** (vd *Việt Nam*, *Anh*): danh sách sẽ xổ xuống (accordion) toàn bộ các giải đấu của quốc gia đó kèm logo; bấm vào từng giải để xem chi tiết giải đấu.

### 4.4. Cột 3 (Ở giữa) — Bộ Lọc Ngày & Danh Sách Trận Đấu
- **Thanh chọn 7 ngày nhanh:** `CN`, `T2`, `T3`, `NAY`, `T5`, `T6`, `T7` kèm ngày/tháng.
- **Ô Lịch Chọn Khoảng Ngày (`📅 Từ ngày - Đến ngày`):** Bấm vào mở lịch nền tối Flatpickr, cho phép chọn **1 ngày lẻ** hoặc **kéo chọn từ ngày A đến ngày B**, rồi bấm **`Lọc`**.
- **4 Nút lọc trạng thái:**
  - `Tất cả`: Toàn bộ trận trong ngày/khoảng ngày.
  - `● Live`: Chỉ hiển thị tất cả các trận đang diễn ra trực tiếp.
  - `Sắp diễn ra`: Các trận chưa đá.
  - `Kết thúc`: Các trận đã có kết quả chung cuộc.
- **Thông báo `⚡ Cache-Through Auto-Sync`:** Xuất hiện khi hệ thống vừa tự động kéo thêm trận mới từ nguồn gốc và lưu vào CSDL cho ngày bạn vừa chọn.
- **Mỗi Khối Giải Đấu:**
  - Tiêu đề giải gồm Logo giải + Tên giải + Quốc gia/Vòng đấu (bấm vào để mở Trang Giải Đấu) + Số lượng trận.
- **Mỗi Hàng Trận Đấu (Match Row):**
  - **Cột trái:** Nếu trận đang đá sẽ có nhãn đỏ nhấp nháy **`● LIVE`** phía trên đồng hồ phút màu xanh lá (`53:06'`, `Q3 05:29'`, `Set 2`). Nếu đã xong hiện `FT`/`AOT`/`ASO`; chưa đá hiện giờ bắt đầu (`19:45`).
  - **Cột giữa:** `[Tên đội nhà] [Logo nhà] [Ô Tỷ số] [Logo khách] [Tên đội khách]`. Ô tỷ số nháy xanh lá khi có bàn thắng mới.
  - **Cột phải (`★`):** Nút ngôi sao ghim trận lên đầu danh sách (dành cho Admin).
  - **Bấm vào bất kỳ hàng trận nào:** Nạp toàn bộ chi tiết trận đó sang **Cột 4 bên phải**.

### 4.5. Cột 4 (Bên phải cố định) — Trung Tâm Chi Tiết Trận Đấu (9 Tab)
- **Phần Đầu (Hero Scoreboard):**
  - Tên giải đấu (bấm để sang Trang Giải Đấu).
  - **Nhãn nguồn dữ liệu minh bạch:** **`💾 TỪ CSDL`** (đọc 100% từ SQLite) hoặc **`🌐 ĐỒNG BỘ NGUỒN`** (vừa đồng bộ từ UniScore và lưu DB).
  - Logo & tên 2 đội, ô tỷ số lớn, huy hiệu **`● ĐANG LIVE`** + đồng hồ nhảy từng giây, Sân vận động (`🏟️`) và Trọng tài (`🧑‍⚖️`).
- **9 Tab Chức Năng:**
  1. **`Chi Tiết`:** Thanh kiểm soát bóng, thông số nhanh (xG, Sút trúng đích, Tấn công nguy hiểm, Phạt góc), bảng điểm chi tiết đặc thù của từng môn và dòng thời gian diễn biến trận đấu.
  2. **`Đội Hình` (hoặc `Bảng Điểm` với Bóng rổ):** Sơ đồ chiến thuật, danh sách Đá chính & Dự bị kèm ảnh đại diện, số áo, vị trí, giá trị thị trường (hoặc bảng Box Score từng cầu thủ với Bóng rổ).
  3. **`Thống Kê`:** Toàn bộ chỉ số chuyên môn Việt hóa kèm thanh so sánh 2 màu xanh/vàng.
  4. **`Tường Thuật`:** Bộ lọc `Tất cả` / `Điểm nhấn`, hiển thị từng thẻ tường thuật chi tiết theo trình tự thời gian.
  5. **`Đối Đầu` (H2H):** Checkbox lọc `Sân nhà` và `Cùng giải đấu`, thanh tỷ lệ thắng/hòa/thua trực quan và danh sách các lần chạm trán lịch sử.
  6. **`Phân Tích AI`:** Dự đoán 1X2 (%), tỷ số dự đoán, Tài/Xỉu phạt góc & thẻ phạt, và 6 thẻ Datalytics (`Tài 2.5%`, `Tài 1.5%`, `BTTS%`, `Bàn/Trận TB`, `Phạt góc TB`, `Thẻ phạt TB`).
  7. **`BXH`:** Bảng xếp hạng mini của đúng giải/bảng đấu chứa 2 đội đang thi đấu (tô sáng 2 đội).
  8. **`Nhánh Đấu`:** Sơ đồ cây loại trực tiếp (Vòng 1/8 -> Tứ kết -> Bán kết -> Chung kết) đối với các giải cúp.
  9. **`JSON`:** Toàn vẹn cấu trúc JSON kỹ thuật dành cho lập trình viên kiểm tra dữ liệu.

---

## 5. ĐẶC THÙ HIỂN THỊ CỦA TỪNG MÔN TRONG 10 MÔN THỂ THAO

1. **⚽ Bóng đá:** Đủ 9 tab, đồng hồ nhảy từng giây (`45+'`, `HT`, `90+'`), thanh kiểm soát bóng, xG, đội hình 2 đội kèm sơ đồ (`4-4-2`, `4-2-3-1`), AI dự đoán 1X2 & 6 chỉ số Datalytics.
2. **🏀 Bóng rổ:** Đồng hồ đếm ngược từng hiệp (`Q1–Q4–OT`), **Biểu đồ đường dẫn điểm SVG (Lead Chart)**, bảng điểm `Tổng trận | 1 | 2 | 3 | 4 | (OT)`, bảng `Tổng quan thông số` 4 dòng (*Điểm, Bắt bóng bật bảng, Mất bóng, Tỷ lệ ném phạt %*), **Bảng Điểm (Box Score)** chi tiết từng cầu thủ (`PTS, MIN, REB, AST, FG, 3P, STL, BLK`), đối đầu 2 màu, BXH phân miền kèm tỷ lệ thắng `PCT`.
3. **🎾 Quần vợt:** Bảng điểm từng Set `S1–S5` kèm **chỉ số Tie-break dạng mũ** (vd `7⁴–6`), thống kê *Aces, Double Faults, Tỷ lệ giao bóng 1, Break Points*.
4. **🏈 Bóng bầu dục Mỹ:** Bảng điểm 4 hiệp `Q1–Q4–OT`, diễn biến ghi điểm giải mã rõ *Touchdown (+6đ), Field Goal (+3đ), Extra Point (+1đ), Safety (+2đ), 2PT Conversion (+2đ)*, thống kê *First Downs, 3rd/4th Down, Passing/Rushing Yards*.
5. **⚾ Bóng chày:** Bảng điểm chuẩn MLB **9 Innings** kèm 3 cột **`R` (Runs) – `H` (Hits) – `E` (Errors)** và thống kê Hits/Errors.
6. **🏸 Cầu lông:** Bảng điểm từng Set `S1–S3` + huy hiệu **Tổng điểm (`Total Points`)**, phân loại chuẩn trạng thái bỏ cuộc (`RETIRED`) và hủy (`CANCELED`).
7. **🏐 Bóng chuyền:** Bảng điểm 5 Set `S1–S5` + **Tổng điểm tích lũy** + lịch sử đối đầu `teamDuel`.
8. **🏏 Cricket:** Bảng **Innings Scorecard** hiển thị chuẩn `Runs/Wickets (Overs)` từng lượt đánh (vd `138/7 (20 Overs)`), kèm **Diễn biến từng Over & từng quả bóng (Ball-by-Ball)** có màu sắc phân biệt (`W` đỏ, `6` vàng, `4` xanh) và đối đầu `teamDuel`.
9. **🏒 Khúc côn cầu trên băng:** Bảng điểm 3 hiệp chính `P1–P3–OT`, xử lý đúng tỷ số cuối sau hiệp phụ/luân lưu (`AOT`/`ASO`), thống kê *SOG, Power Play, PIM, Faceoffs* và đối đầu `teamDuel`.
10. **🏓 Bóng bàn:** Bảng điểm 5–7 Set `S1–S7` + **Tổng điểm**, thống kê từng Set (*Tổng điểm thắng, Chuỗi điểm liên tiếp, Lội ngược dòng, Điểm giao/đỡ bóng*) và lịch sử đối đầu H2H.

---

## 6. GIẢI THÍCH CHI TIẾT TRANG GIẢI ĐẤU (COMPETITION PAGE)

Truy cập khi bấm vào bất kỳ tên giải đấu nào (`/competition/{sport}/{id}`):
- **Banner đầu trang:** Logo giải lớn, Tên giải, Quốc gia, dropdown **chọn Mùa giải (`2026-2027`, `2025-2026`...)** và 9 tab:
  1. **`☰ Tổng Quan`:** Trận sắp tới + Trận gần nhất + Bảng xếp hạng.
  2. **`📅 Danh Sách Trận Đấu`:** Lịch thi đấu & Kết quả có **bộ lọc khoảng ngày (`Từ ngày → Đến ngày`)** và nút chuyển trang (`Trang trước` / `Trang sau`), tự động lưu trận vào CSDL.
  3. **`📊 Bảng Xếp Hạng`:** Đầy đủ vị trí, logo, Trận, Thắng, Hòa, Bại, BT:BB, Hiệu số, Điểm (hoặc `Tỷ lệ thắng PCT` và chia miền Đông/Tây với Bóng rổ).
  4. **`🌳 Nhánh Đấu`:** Sơ đồ cây loại trực tiếp cho các giải cúp.
  5. **`⇄ Chuyển Nhượng`:** Thanh lọc **`Tất cả` | `← Gia nhập` | `Rời đội →`** + dropdown lọc từng **CLB**. Từng dòng ghi rõ ảnh & họ tên cầu thủ, vị trí, loại & phí chuyển nhượng (`Cho mượn`, `Chuyển nhượng €60M`, `Tự do`), mũi tên xanh/đỏ, logo & tên CLB đối tác, và ngày chuyển nhượng.
  6. **`⭐ Cầu Thủ Nổi Bật`:** Top Vua phá lưới, Top Kiến tạo, Top Điểm đánh giá kèm ảnh cầu thủ.
  7. **`🏆 Đội Bóng Hàng Đầu`:** Top CLB ghi nhiều bàn nhất, kiến tạo nhiều nhất, giữ sạch lưới nhiều nhất.
  8. **`📈 Thống Kê Hàng Đầu`:** Tổng số trận, tổng bàn thắng, trung bình bàn/trận, tỷ lệ Chủ nhà thắng / Hòa / Khách thắng, thẻ vàng & thẻ đỏ trung bình.
  9. **`🏅 Cúp`:** Đương kim vô địch & CLB vô địch nhiều lần nhất.

---

## 7. GIẢI THÍCH CHI TIẾT TRANG QUẢN TRỊ ADMIN (`/admin`)

- **Đăng nhập bảo mật:** Bắt buộc đăng nhập tại `/admin/login` (`admin@uniscore.local` / `admin123`).
- **4 Thẻ KPI:** Tổng số trận lưu trữ, Trận đang Live, Trận đã kết thúc (Khóa DB), Tổng số giải đấu.
- **Khối `🚀 Khởi Chạy Cào Dữ Liệu (Không Giới Hạn Số Trận)`:**
  1. **Chọn Môn:** Chọn 1 trong 10 môn -> tự động tải danh sách **Giải đấu** tương ứng.
  2. **Chọn Giải đấu:** Chọn 1 giải cụ thể hoặc `-- Tất cả giải đấu --`.
  3. **Từ ngày A → Ngày B:** Kéo chọn bất kỳ khoảng ngày nào trên lịch tối.
  4. **Giới hạn tốc độ Request:** `1.0s/req` (khuyên dùng), `0.5s/req`, `1.5s/req`, `2.0s/req` để tránh bị nguồn chặn.
  5. **Chạy ngầm + Khung theo dõi trực tiếp:** Tự chạy background không treo web, cập nhật log mỗi 2 giây, tự bỏ qua các trận đã `Finished` trong DB, có nút **`⏹ Dừng cào`**.
- **Khối `⚙️ Bảo Trì Hệ Thống & Cache`:**
  - `🔄 Đồng bộ DB`: Nạp lại dữ liệu từ các file JSON output vào SQLite.
  - `🧹 Xóa Cache`: Xóa toàn bộ cache ứng dụng & giao diện.
  - `🗑️ Dọn dẹp trận cũ (> 7 ngày)`: Xóa bớt các trận quá cũ để tối ưu dung lượng.
- **Tab `📋 Quản Lý Trận Đấu (CRUD)`:**
  - Bộ lọc kết hợp: Tìm kiếm từ khóa, Lọc theo Môn, Trạng thái, Ngày + nút Reset.
  - Nút **`★`** (Ghim trận lên đầu), **`+ Thêm Trận Đấu Thủ Công`**, **`✏️ Sửa`** (chỉnh tỷ số, trạng thái, phút, tên đội/giải), **`🗑️ Xóa`**.
- **Tab `🏆 Quản Lý Ưu Tiên Giải Đấu`:**
  - Bấm **`☆ Ghim cả giải lên đầu`** để ghim toàn bộ các trận thuộc giải đấu đó lên đầu trang chủ người dùng chỉ bằng 1 click.

---

## 8. GIẢI THÍCH CHI TIẾT MISSION CONTROL DASHBOARD (`:8501`)

Giao diện Python chuyên sâu dành cho kỹ sư dữ liệu (`http://localhost:8501`):
- **Thanh bên (Sidebar):** Chế độ chạy (1 lần / Định kỳ), Số luồng (`Workers 1–20`), Độ trễ (`Delay 0.2s–5.0s`), Danh sách Proxy xoay vòng, Nút `🔄 Làm mới Cookie/Token`, Chọn đích lưu trữ (`JSON` / `Database` / `Cả 2` / `Webhook`).
- **Khu vực Điều khiển:** Chọn nhiều môn cùng lúc (`Chọn tất cả 10 môn`), Trạng thái, Ngày, Lọc giải đấu, và 5 công tắc bật/tắt độ sâu dữ liệu (*Scoreboard, Events, Lineups, Stats, Standings*).
- **Giám sát & 3 Tab dưới:** Nút **`▶ START` / `⏸ PAUSE` / `⏹ STOP`**, 4 thẻ KPI thời gian thực, thanh tiến trình %, **Tab 1: Live Console WebSocket** (log màu theo thời gian thực), **Tab 2: Data Preview** (xem trước cây JSON 4 tầng), **Tab 3: Export & Download** (tải trực tiếp file **`.json`**, **`.csv`**, hoặc **`.xlsx` Excel**).

---

## 9. CẤU TRÚC CƠ SỞ DỮ LIỆU & API NỘI BỘ

### Bảng chính `sports_matches` (SQLite: `web/database/database.sqlite`)
| Cột | Kiểu | Ý nghĩa |
| :--- | :--- | :--- |
| `external_id` | `string` | Mã định danh duy nhất của trận đấu từ UniScore |
| `sport_key` | `string` | Mã môn (`football`, `basketball`, `tennis`, ...) |
| `tournament_name` | `string` | Tên giải đấu |
| `home_team` / `away_team` | `string` | Tên đội nhà / đội khách (hoặc VĐV 1 / VĐV 2) |
| `home_logo` / `away_logo` | `string` | Đường dẫn ảnh logo thật đã mã hóa CDN |
| `home_score` / `away_score` | `int` | Tỷ số tổng hiện tại hoặc chung cuộc |
| `state_category` | `string` | `Upcoming`, `Live`, `Finished`, `Special (...)` |
| `match_date` | `string` | Ngày thi đấu (`YYYY-MM-DD`) |
| `readtime` | `string` | Phút thi đấu / Hiệp / Set hiện tại |
| `is_pinned` | `boolean` | Cờ ghim ưu tiên hiển thị lên đầu trang chủ |
| `payload` | `json` | Toàn bộ cây dữ liệu 4 tầng + bản lưu vĩnh viễn `detail_cached` & `extended_cached` |

### Danh sách API nội bộ của Web Laravel
- `GET /api/leagues/{sport}/{categoryId}` — Lấy danh sách giải đấu theo quốc gia.
- `GET /api/top-leagues/{sport}` — Lấy danh sách giải đấu nổi bật của 1 môn.
- `GET /api/live-updates/{sport}` — Đồng bộ & trả về danh sách trận đang Live (polling 15s).
- `GET /api/match-detail/{sport}/{eventId}` — Chi tiết trận (DB-First cho trận `Finished`, live cho trận đang đá).
- `GET /api/match-extended/{sport}/{eventId}` — Đối đầu H2H, Phân tích AI, Datalytics & Nhánh đấu (DB-First cho trận `Finished`).
- `GET /admin/scraper/status` & `POST /admin/scraper/stop` — Giám sát & dừng tiến trình cào ngầm.
