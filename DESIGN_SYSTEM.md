# Floodlight — Design System của UniScore

Giao diện được thiết kế lại theo kỹ năng **frontend-design** (Anthropic skills, liệt kê trong *awesome-claude-skills*).

## Ý tưởng
Một sân vận động về đêm: phần khung (header, sidebar, bảng tỷ số chi tiết) là màu **navy của khán đài lúc tối**, còn **danh sách trận đấu là "mặt sân được chiếu đèn"** — nền trắng xanh lạnh, chữ mực đậm, dễ đọc nhất. Màu **hổ phách (amber)** chỉ dùng cho hai việc: trận đang **Live** và **mục đang được chọn**. Tỷ số dùng chữ condensed lớn như bảng điện tử.

Điểm nhấn duy nhất: sự tương phản giữa "sân sáng" ở giữa và "khán đài tối" xung quanh. Mọi thứ khác giữ yên tĩnh.

## Màu
| Token | Hex | Dùng cho |
|---|---|---|
| `night` | `#0A1B30` | Nền trang |
| `stand` | `#10253F` | Panel nổi trên nền navy |
| `stand2` | `#163152` | Hover / ô lõm trên navy |
| `seat` | `#22406A` | Viền trên navy |
| `mist` | `#8EA3BF` | Chữ phụ trên navy |
| `frost` | `#E8EEF6` | Chữ chính trên navy |
| `flood` | `#F3F6FA` | Mặt sân sáng (danh sách trận) |
| `flood2` | `#E6ECF3` | Header nhóm / hover trên nền sáng |
| `chalk` | `#D3DCE7` | Đường kẻ trên nền sáng |
| `ink` | `#0C1A2C` | Chữ trên nền sáng |
| `ink2` | `#4A5D75` | Chữ phụ trên nền sáng |
| `live` | `#FFB020` | Live + mục đang chọn |
| `liveink` | `#8A5A00` | Chữ amber trên nền sáng (đạt tương phản AA) |
| `goal` | `#2FBF71` | Thắng / tích cực |
| `alert` | `#E5484D` | Thẻ đỏ / lỗi |

## Chữ
- **Be Vietnam Pro** — chữ thân (thiết kế riêng cho tiếng Việt).
- **Barlow Condensed** — tỷ số, số liệu, tiêu đề lớn (`font-score`, `.fl-score`, `.fl-display`), luôn dùng số dạng bảng (`tabular-nums`).
- Viết hoa kiểu câu (sentence case), không dùng nhãn ALL CAPS, không dùng chữ mono cho nhãn nhỏ (chỉ dùng ở tab JSON).

## Bố cục trang chủ
```
┌───────────────────────────────────────────────────────────────┐
│ Header navy: Logo · Tìm kiếm · Live n · Mission · Admin         │
│ Thanh môn thể thao (tab ngang, gạch chân amber cho môn đang chọn)│
├───────────────────────────────────────────────────────────────┤
│ Dải ngày (7 ngày) + chọn khoảng ngày + bộ lọc trạng thái         │
├────────────┬──────────────────────────────┬───────────────────┤
│ Giải đấu   │ SÂN SÁNG: trận theo giải       │ BẢNG TỶ SỐ (navy)  │
│ (navy)     │                                │ tỷ số lớn + 9 tab  │
└────────────┴──────────────────────────────┴───────────────────┘
```
- Mobile: sidebar giải đấu thành ngăn kéo, bảng tỷ số chi tiết mở toàn màn hình khi chọn trận.

## Thành phần dùng chung
File: [`web/public/css/floodlight.css`](web/public/css/floodlight.css), nạp qua [`partials/fl-head.blade.php`](web/resources/views/partials/fl-head.blade.php).

`.fl-panel` · `.fl-field` · `.fl-input` · `.fl-btn` (`--primary`, `--danger`) · `.fl-seg` (+ `.is-active`) · `.fl-tab` (+ `.is-active`) · `.fl-live-dot` · `.fl-score` · `.fl-display` · `.fl-table` · `.match-row-active`

Bo góc: 4px (chip, input) · 8px (panel) · 14px (chỉ bảng tỷ số chính).

## Chất lượng tối thiểu
Responsive tới mobile, focus bàn phím hiển thị (viền amber), tôn trọng `prefers-reduced-motion`, chuyển động duy nhất tự chạy là chấm Live.
