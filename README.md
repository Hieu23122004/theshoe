# Hướng dẫn cài đặt dự án PHP này

## 1. Cài đặt Composer
- Nếu chưa có Composer, tải và cài tại: https://getcomposer.org/

## 2. Cài đặt các thư viện phụ thuộc
Chạy lệnh sau trong thư mục dự án:

```
composer install
```

## 3. Tạo file `.env`
Tạo file `.env` ở thư mục gốc dự án (cùng cấp với `.env.example`) với nội dung từ .env.example:

- Thay giá trị các biến cho phù hợp với cấu hình database của bạn.

## 4. Khởi động dự án
- Đảm bảo đã import database và cấu hình đúng.
- Truy cập dự án qua trình duyệt (ví dụ: http://localhost/app)

## 5. Hướng dẫn làm việc nhóm với Git

### Tạo nhánh mới khi làm việc
1. Tạo nhánh mới (ví dụ: tên nhánh là `ten-nhanh`):
   ```
   git checkout -b ten-nhanh
   ```
2. Làm việc, commit code:
   ```
   git add .
   git commit -m "Mô tả thay đổi"
   ```
3. Đẩy nhánh mới lên git:
   ```
   git push origin ten-nhanh
   ```

### Lấy code mới nhất từ nhóm về máy
1. Chuyển về nhánh chính (thường là `main`):
   ```
   git checkout main
   ```
2. Kéo code mới nhất về:
   ```
   git pull origin main
   ```
3. Nếu muốn cập nhật nhánh đang làm việc với code mới nhất:
   ```
   git checkout ten-nhanh
   git merge main
   ```

### Lưu ý
- Luôn tạo nhánh riêng cho từng tính năng/bug để tránh xung đột.
- Khi có xung đột, cần tự xử lý rồi mới push tiếp.
- Không commit file `.env` và thư mục `vendor` lên git.
- Nếu thêm thư viện mới, dùng lệnh `composer require ten-thu-vien` và commit cả `composer.json`, `composer.lock`.
