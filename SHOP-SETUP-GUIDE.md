# 🛍️ Hướng dẫn thiết lập Shop cho Admin

## Tổng quan
Hệ thống quản lý shop đã được tích hợp vào admin panel, cho phép bạn quản lý sản phẩm, danh mục và đơn hàng một cách dễ dàng.

## Bước 1: Thiết lập Database
Chạy script thiết lập database cho shop:

```
http://localhost/badminton_booking/setup-shop.php
```

Script này sẽ:
- Tạo các bảng cần thiết cho shop
- Thêm dữ liệu mẫu (danh mục và sản phẩm)
- Kiểm tra kết quả thiết lập

## Bước 2: Truy cập Admin Panel
1. Đăng nhập admin tại: `login.php`
   - Email: `admin@badminton.local`
   - Password: `admin123`

2. Vào Dashboard admin và click "🛍️ Quản lý Shop"

## Bước 3: Quản lý Shop

### 📦 Quản lý Sản phẩm
- **Xem danh sách**: Tab "Sản phẩm" hiển thị tất cả sản phẩm
- **Thêm sản phẩm**: Tab "Thêm sản phẩm" để tạo sản phẩm mới
- **Cập nhật tồn kho**: Sửa số lượng trực tiếp trong danh sách
- **Xóa sản phẩm**: Nút xóa trong hành động

### 🏷️ Quản lý Danh mục
- **Xem danh mục**: Tab "Danh mục" 
- **Thêm danh mục**: Form bên trái để tạo danh mục mới
- **Sửa/Xóa**: Nút hành động trong bảng danh sách

### 📋 Quản lý Đơn hàng
- **Xem đơn hàng**: Click "📦 Đơn hàng" từ trang shop
- **Cập nhật trạng thái**: Dropdown để thay đổi trạng thái đơn hàng
- **Xem chi tiết**: Nút "👁️" để xem chi tiết đơn hàng

## Cấu trúc Database

### Bảng chính:
- `product_categories`: Danh mục sản phẩm
- `products`: Sản phẩm
- `product_variants`: Biến thể sản phẩm (size, màu sắc)
- `orders`: Đơn hàng
- `order_items`: Chi tiết đơn hàng

### Trạng thái đơn hàng:
- `pending`: Chờ xử lý
- `processing`: Đang xử lý  
- `shipped`: Đã gửi
- `delivered`: Đã giao
- `cancelled`: Đã hủy

## Tính năng Shop cho Khách hàng

### Trang Shop: `equipment.php`
- Hiển thị sản phẩm theo danh mục
- Giỏ hàng (sidebar)
- Tìm kiếm và lọc sản phẩm
- Thêm vào giỏ hàng

### Tích hợp với hệ thống hiện tại:
- Sử dụng cùng database và user system
- Tích hợp với navigation menu
- Responsive design với Bootstrap

## Thống kê Shop

Dashboard admin hiển thị:
- 📦 Tổng số sản phẩm
- 🏷️ Số danh mục
- ⚠️ Sản phẩm sắp hết hàng
- ⭐ Sản phẩm nổi bật
- 🛒 Tổng đơn hàng
- 💰 Doanh thu

## Tùy chỉnh và Mở rộng

### Thêm tính năng:
1. **Đánh giá sản phẩm**: Thêm bảng `product_reviews`
2. **Mã giảm giá**: Thêm bảng `coupons`
3. **Wishlist**: Thêm bảng `user_wishlist`
4. **Inventory tracking**: Lịch sử nhập/xuất kho

### Tùy chỉnh giao diện:
- Sửa file `equipment.php` cho trang shop
- Sửa file `assets/css/style.css` cho styling
- Sửa file `assets/js/equipment.js` cho JavaScript

## Bảo mật

### Đã implement:
- ✅ Kiểm tra quyền admin (`requireAdmin()`)
- ✅ Escape output (`htmlspecialchars()`)
- ✅ Prepared statements cho SQL
- ✅ Validation input

### Khuyến nghị:
- Backup database thường xuyên
- Kiểm tra log lỗi
- Cập nhật thông tin sản phẩm định kỳ
- Monitor đơn hàng và thanh toán

## Troubleshooting

### Lỗi thường gặp:
1. **Database không tồn tại**: Chạy lại `setup-shop.php`
2. **Không hiển thị sản phẩm**: Kiểm tra `status = 1` trong database
3. **Lỗi quyền admin**: Đảm bảo user có `role = 'admin'`

### Debug:
- Kiểm tra PHP error log
- Sử dụng browser developer tools
- Kiểm tra database connection

## Kết luận
Hệ thống shop đã được tích hợp hoàn chỉnh với:
- ✅ Admin panel quản lý đầy đủ
- ✅ Trang shop cho khách hàng  
- ✅ Database structure hoàn chỉnh
- ✅ Security và validation
- ✅ Responsive design

Bạn có thể bắt đầu sử dụng ngay sau khi chạy `setup-shop.php`!