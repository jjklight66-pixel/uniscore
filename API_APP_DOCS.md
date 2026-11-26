# Tài liệu REST API v1 cho Mobile App (UniScore 10-Sports)

Bộ API chuẩn RESTful (`/api/v1/*`) chạy hoàn toàn **Stateless** (không cần CSRF/Cookie), hỗ trợ **CORS**, **Rate Limit**, **Bearer Token Auth**, **Yêu thích (Favorites)**, **Push Notification Device Tokens (FCM/APNs)** và cơ chế **DB-First** cho cả 10 môn thể thao.

- **Base URL (Local):** `http://localhost:8000/api/v1`
- **Swagger UI tương tác (test trực tiếp trên trình duyệt):** `http://localhost:8000/api/docs`
- **File OpenAPI 3.0 (import vào Postman / Insomnia / OpenAPI Generator):** `http://localhost:8000/openapi.json`

---

## 1. Quy chuẩn Response JSON

Mọi endpoint đều trả về cấu trúc thống nhất:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {}
}
```

Khi lỗi (`401`, `404`, `422`, `429`):

```json
{
  "success": false,
  "message": "Mô tả lỗi chi tiết",
  "data": null,
  "errors": {}
}
```

---

## 2. Danh sách Endpoint đầy đủ

### A. Dữ liệu thể thao (Public — Không cần Token)

| Method | Endpoint | Mô tả |
|---|---|---|
| `GET` | `/api/v1/health` | Kiểm tra trạng thái API, thời gian server, tổng trận trong DB |
| `GET` | `/api/v1/sports` | Danh sách 10 môn thể thao + số trận đang live |
| `GET` | `/api/v1/matches` | Danh sách trận đấu (hỗ trợ `sport`, `date`, `date_from`, `date_to`, `status=all\|live\|finished\|upcoming`, `league`, `league_id`, `q`, `group_by_league=1`, `page`, `per_page`) |
| `GET` | `/api/v1/matches/{sport}/{eventId}` | Chi tiết trận đấu (diễn biến, thống kê, đội hình, BXH, H2H — **DB-First**) |
| `GET` | `/api/v1/matches/{sport}/{eventId}/extended` | Dữ liệu mở rộng (kèo, phân tích AI, nhánh đấu...) |
| `GET` | `/api/v1/live/{sport}` | Cập nhật tỷ số live theo môn (gợi ý app poll mỗi 15 giây) |
| `GET` | `/api/v1/categories/{sport}` | Danh sách quốc gia / khu vực theo môn |
| `GET` | `/api/v1/leagues/{sport}/top` | Danh sách giải đấu hàng đầu theo môn |
| `GET` | `/api/v1/leagues/{sport}/category/{categoryId}` | Danh sách giải đấu thuộc 1 quốc gia |
| `GET` | `/api/v1/competitions/{sport}/{id}` | Chi tiết giải đấu (tổng quan, BXH, lịch đấu, kết quả, vua phá lưới, thống kê) |

### B. Xác thực người dùng App (Bearer Token)

| Method | Endpoint | Auth | Mô tả |
|---|---|---|---|
| `POST` | `/api/v1/auth/register` | Không | Đăng ký tài khoản mới (`name`, `email`, `password`, `device_name`) → trả về `token` |
| `POST` | `/api/v1/auth/login` | Không | Đăng nhập (`email`, `password`, `device_name`) → trả về `token` |
| `GET` | `/api/v1/auth/me` | **Bearer** | Lấy thông tin user, số mục yêu thích, số thiết bị |
| `POST` | `/api/v1/auth/logout` | **Bearer** | Thu hồi token hiện tại (gửi `{"all_devices": true}` để đăng xuất mọi thiết bị) |

> Gửi header cho các route yêu cầu xác thực:
> `Authorization: Bearer <token>`

### C. Yêu thích (Favorites — Cần Bearer Token)

| Method | Endpoint | Mô tả |
|---|---|---|
| `GET` | `/api/v1/favorites` | Lấy danh sách yêu thích (lọc tùy chọn `?type=match\|team\|competition&sport=football`) |
| `POST` | `/api/v1/favorites` | Thêm 1 mục yêu thích (`type`, `sport`, `entity_id`, `name`, `meta`) |
| `POST` | `/api/v1/favorites/toggle` | Bật/tắt yêu thích trong 1 lần gọi (tiện nhất cho nút ⭐ / ❤️ trên App) |
| `DELETE` | `/api/v1/favorites/{id}` | Xóa mục yêu thích theo ID |

### D. Thông báo đẩy (Push Notifications — FCM / APNs)

| Method | Endpoint | Auth | Mô tả |
|---|---|---|---|
| `POST` | `/api/v1/devices/register` | Tùy chọn | Đăng ký `device_token` (`android` \| `ios` \| `web`), tự gắn `user_id` nếu gửi kèm Bearer Token |
| `DELETE` | `/api/v1/devices/{token}` | Tùy chọn | Hủy đăng ký nhận thông báo đẩy |

---

## 3. Ví dụ tích hợp nhanh

### Flutter (Dart — `http` / `dio`)
```dart
final res = await http.get(
  Uri.parse('http://YOUR_SERVER:8000/api/v1/matches?sport=football&group_by_league=1'),
  headers: {'Accept': 'application/json'},
);
final json = jsonDecode(res.body);
if (json['success'] == true) {
  final leagues = json['data']; // Danh sách trận đã gom nhóm theo giải
}
```

### React Native / TypeScript (`fetch`)
```ts
const res = await fetch('http://YOUR_SERVER:8000/api/v1/favorites/toggle', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`,
  },
  body: JSON.stringify({
    type: 'match',
    sport: 'football',
    entity_id: '123456',
    name: 'Arsenal vs Chelsea',
  }),
});
const data = await res.json();
```
