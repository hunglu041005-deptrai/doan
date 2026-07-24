# ⭐ Hướng dẫn hệ thống đánh giá sản phẩm

## 🎯 Tổng quan

Hệ thống đánh giá sản phẩm hoàn chỉnh với các tính năng:
- ✅ **Đánh giá 5 sao** với tiêu đề và nhận xét chi tiết
- ✅ **Trang chi tiết sản phẩm** với rating summary và reviews
- ✅ **Admin quản lý** duyệt/từ chối đánh giá
- ✅ **Auto-update rating** trung bình cho sản phẩm
- ✅ **Responsive design** tương thích mobile

## 🗄️ Cấu trúc Database

### Bảng mới được tạo:
```sql
- product_reviews: Lưu đánh giá của khách hàng
- order_items: Track việc mua hàng (chuẩn bị cho tương lai)
```

### Cột mới trong bảng products:
```sql
- average_rating: Rating trung bình (tự động cập nhật)
- review_count: Số lượng đánh giá (tự động cập nhật)
```

### Triggers tự động:
- Cập nhật rating trung bình khi có đánh giá mới
- Cập nhật số lượng đánh giá
- Xử lý khi xóa đánh giá

## 🚀 Thiết lập hệ thống

### 1. Chạy migration
1. Đăng nhập admin
2. Vào `setup-reviews.php`
3. Click "Thiết lập hệ thống đánh giá"
4. Hệ thống sẽ tự động:
   - Tạo bảng database
   - Thêm triggers
   - Tạo dữ liệu đánh giá mẫu

### 2. Kiểm tra thiết lập
- Vào `admin/reviews.php` để xem đánh giá
- Vào `equipment.php` để xem rating trên sản phẩm
- Vào `product-detail.php?id=1` để xem trang chi tiết

## 🛍️ Trải nghiệm khách hàng

### Trang danh sách sản phẩm (equipment.php)
- Hiển thị rating sao và số lượng đánh giá
- Link "Xem chi tiết" đến trang product detail
- Rating được cập nhật real-time từ database

### Trang chi tiết sản phẩm (product-detail.php)
- **Thông tin sản phẩm đầy đủ**: Hình ảnh, giá, mô tả, tồn kho
- **Rating summary**: Điểm trung bình, phân bố sao
- **Danh sách đánh giá**: Tất cả reviews đã được duyệt
- **Form đánh giá**: Cho user đã đăng nhập (chưa đánh giá)

### Quy trình đánh giá:
1. **Đăng nhập** (bắt buộc)
2. **Vào trang chi tiết sản phẩm**
3. **Click "Viết đánh giá"**
4. **Chọn số sao** (1-5)
5. **Nhập tiêu đề** (tùy chọn)
6. **Viết nhận xét** (bắt buộc)
7. **Gửi đánh giá** → Hiển thị ngay (status: approved)

## 🛠️ Quản lý Admin

### Trang quản lý đánh giá (admin/reviews.php)
- **Thống kê tổng quan**: Tổng số, chờ duyệt, đã duyệt, từ chối
- **Danh sách đánh giá**: Tất cả reviews với thông tin chi tiết
- **Hành động**: Duyệt, từ chối, xóa, xem chi tiết
- **Modal chi tiết**: Xem đầy đủ nội dung đánh giá

### Các trạng thái đánh giá:
- **pending**: Chờ duyệt (màu vàng)
- **approved**: Đã duyệt (màu xanh) - Hiển thị trên web
- **rejected**: Từ chối (màu đỏ) - Không hiển thị

### Quy trình duyệt:
1. **Vào admin/reviews.php**
2. **Xem danh sách đánh giá chờ duyệt**
3. **Click "Xem chi tiết"** để đọc đầy đủ
4. **Duyệt** ✅ hoặc **Từ chối** ❌
5. **Rating tự động cập nhật** trên trang sản phẩm

## 📊 Tính năng nâng cao

### Auto-update Rating
- Trigger database tự động tính rating trung bình
- Cập nhật số lượng đánh giá
- Chỉ tính các đánh giá đã được duyệt

### Rating Display
- Hiển thị sao đầy/rỗng dựa trên điểm số
- Format: "4.5/5 (23 đánh giá)"
- Responsive trên mobile

### Security & Validation
- Chỉ user đăng nhập mới đánh giá được
- Mỗi user chỉ đánh giá 1 lần/sản phẩm
- Validation rating 1-5 sao
- HTML escaping để tránh XSS

## 🔗 Files và URLs

### Files chính:
```
setup-reviews.php              # Thiết lập database
product-detail.php             # Trang chi tiết sản phẩm  
admin/reviews.php              # Quản lý đánh giá
database/migrations/create_reviews_table.sql  # SQL migration
```

### URLs quan trọng:
- `setup-reviews.php` - Thiết lập hệ thống
- `product-detail.php?id=1` - Chi tiết sản phẩm
- `admin/reviews.php` - Quản lý đánh giá admin
- `equipment.php` - Danh sách sản phẩm với rating

## 🎨 Giao diện

### Responsive Design
- Bootstrap 5 responsive grid
- Mobile-friendly rating stars
- Touch-friendly buttons
- Optimized modal dialogs

### Visual Elements
- ⭐ Rating stars (FontAwesome)
- 📊 Progress bars cho rating distribution
- 🏷️ Status badges với màu sắc
- 💬 Comment bubbles
- 📱 Mobile-optimized layout

## 🧪 Test hệ thống

### Test flow hoàn chỉnh:
1. **Setup**: Chạy `setup-reviews.php`
2. **Đăng nhập user** (không phải admin)
3. **Vào equipment.php** → Thấy rating trên sản phẩm
4. **Click sản phẩm** → Vào trang chi tiết
5. **Viết đánh giá** → Gửi thành công
6. **Đăng nhập admin** → Vào `admin/reviews.php`
7. **Duyệt đánh giá** → Rating cập nhật tự động
8. **Quay lại trang sản phẩm** → Thấy đánh giá mới

### Test cases:
- ✅ User chưa đăng nhập → Không thể đánh giá
- ✅ User đã đánh giá → Không thể đánh giá lại
- ✅ Rating 1-5 sao → Validation OK
- ✅ Admin duyệt → Rating cập nhật ngay
- ✅ Admin từ chối → Không hiển thị trên web
- ✅ Xóa đánh giá → Rating tự động tính lại

## 🎉 Kết quả

✅ **Hoàn thành 100%** hệ thống đánh giá như yêu cầu:
- Giao diện giống hình mẫu với rating stars
- Trang chi tiết sản phẩm với reviews
- Admin quản lý đánh giá
- Auto-update rating trung bình
- Responsive design

✅ **Tính năng bonus**:
- Rating distribution chart
- Review moderation system  
- Helpful votes (chuẩn bị)
- Mobile-optimized UI
- Real-time rating updates

Hệ thống đánh giá đã sẵn sàng để sử dụng! 🚀