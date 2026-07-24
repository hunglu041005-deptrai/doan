# Badminton Booking

Ứng dụng đặt sân cầu lông đơn giản bằng PHP + MySQL.

## Tính năng đã triển khai
- Đăng ký / đăng nhập người dùng
- Xem danh sách sân cầu lông
- Tìm kiếm / lọc theo khu vực, giá
- Xem thông tin sân và hình ảnh
- Chọn ngày, khung giờ, đặt sân
- Kiểm tra chồng lịch và tránh đặt trùng
- Thanh toán online giả lập MoMo / VNPay hoặc tiền mặt
- Xem lịch sử đặt sân, trạng thái đơn
- Admin quản lý sân, booking, người dùng
- Báo cáo doanh thu
- API RESTful mẫu cho courts và bookings
- Giao diện responsive với Bootstrap

## Cài đặt
1. Sao chép thư mục `badminton_booking` vào `htdocs`.
2. Mở `setup.php` bằng trình duyệt: `http://localhost/badminton_booking/setup.php`
3. Đăng nhập admin tại `http://localhost/badminton_booking/admin/index.php`

## Thông tin đăng nhập admin mặc định
- Email: `admin@badminton.local`
- Mật khẩu: `admin123`

## Các trang chính
- Trang khách: `index.php`
- Đăng ký: `register.php`
- Đăng nhập: `login.php`
- Xem sân: `court.php?id=...`
- Lịch sử đặt sân: `booking-history.php`
- Admin: `admin/dashboard.php`

## Lưu ý
- Nếu MySQL không dùng mật khẩu trống, cập nhật `db.php` và `setup.php`.
- Ứng dụng hiện giả lập thanh toán online. Để tích hợp thực tế cần kết nối API MoMo/VNPay.
