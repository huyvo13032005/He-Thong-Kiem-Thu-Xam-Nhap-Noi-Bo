# Hệ thống quản lý kiểm thử xâm nhập nội bộ

Bản Phase 2 được bổ sung Docker cho PHP/Apache và SQL Server. Phục vụ đồ án và demo local: lập kế hoạch, phạm vi kiểm thử, phê duyệt, phân công Pentester, dashboard và audit log.

**Tình trạng kiểm chứng:** cấu trúc YAML/Compose, thứ tự khởi động, tách secrets, form CSRF, đường dẫn assets và phần tách schema đã qua kiểm tra tĩnh; chưa chạy build Docker hoặc kiểm thử PHP + SQL Server tích hợp trong môi trường chuẩn bị gói này. Chạy các bước kiểm tra bên dưới trên máy có Docker Desktop trước khi demo.

## Chạy nhanh trên Windows bằng Docker

Yêu cầu: máy Windows x64, Docker Desktop đang chạy **Linux containers**, WSL 2 hoạt động. Nên dành ít nhất 4 GB RAM cho môi trường Docker chạy SQL Server và PHP; lần build đầu cần Internet. Không cần cài PHP, XAMPP, SQL Server hay SSMS riêng trên Windows.

Giải nén gói, mở PowerShell tại thư mục chứa `compose.yaml` và `Dockerfile`:

```powershell
cd E:\pentest-mgmt-docker
docker version
docker compose version
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\setup.ps1
docker compose up -d --build
docker compose ps -a
docker compose logs --tail 80 init web
```

`setup.ps1` sinh `.env` với hai mật khẩu database ngẫu nhiên, không ghi đè `.env` đang có. `ExecutionPolicy Bypass` chỉ áp dụng cho tiến trình chạy script này, không thay đổi chính sách máy lâu dài. Có thể thay thế bằng cách copy `.env.example` thành `.env` và điền hai mật khẩu khác nhau đủ mạnh, từ 16 ký tự, gồm chữ hoa/thường/số/ký tự đặc biệt. Không giữ giá trị `CHANGE_ME`.

Mở [http://localhost:8080](http://localhost:8080).

Kết quả mong đợi: `db` healthy, `init` Exited (0), `web` healthy. `init` kết thúc thành công là bình thường, không phải container lỗi.

### Ba dịch vụ

| Dịch vụ | Công việc |
| --- | --- |
| `db` | SQL Server 2022 Developer; dữ liệu nằm trong volume `sqlserver_data` |
| `init` | Chờ SQL Server khỏe, tạo database mới, import schema/seed/views, sinh hash mật khẩu, tạo login `pentest_app` |
| `web` | PHP 8.3 + Apache + PDO_SQLSRV + ODBC 18; document root là `public`; chỉ mở cổng web trên loopback |

SQL Server không publish cổng ra Windows theo mặc định. PHP kết nối bằng hostname dịch vụ `db:1433`, không phải `localhost`. Web không giữ mật khẩu `sa`. Thư mục chuẩn bị cho bằng chứng nằm ngoài document root và dùng volume riêng; module Evidence vẫn chưa triển khai hoàn chỉnh.

SQL Server Developer dùng cho phát triển/thử nghiệm. Cấu hình này là demo local, chưa phải cấu hình triển khai production.

## Tài khoản demo

Mật khẩu ban đầu: `Password123!` (hoặc giá trị `DEMO_PASSWORD` bạn đặt trước lần khởi tạo đầu tiên).

| Vai trò | Username |
| --- | --- |
| Admin | `admin` |
| Project Manager | `pm.tran` |
| Approver | `approver.le` |
| Pentester | `pentester.pham` |
| Asset Owner HR | `owner.hr` |
| Stakeholder | `ceo` |

Hai tài khoản `locked.demo` và `disabled.demo` chủ động bị chặn. Thay đổi `DEMO_PASSWORD` sau khi khởi tạo không đổi mật khẩu người dùng cũ; dùng màn hình Admin để đổi. Không thay mật khẩu `MSSQL_SA_PASSWORD` trong `.env` một cách tùy ý khi volume đã tồn tại: SQL Server vẫn giữ mật khẩu đã khởi tạo trước đó.

## Kiểm tra trước khi demo

```powershell
docker compose exec web php -r "print_r(PDO::getAvailableDrivers());"
docker compose exec web php scripts/smoke-test.php
```

Nếu đã đặt mật khẩu demo khác, truyền nó vào tiến trình kiểm tra qua biến môi trường `DEMO_PASSWORD` của `docker compose exec`. Không cần chạy lại seed. Smoke test kiểm tra đăng nhập/dashboard sáu vai trò, tìm kiếm user/audit, CSS và chặn thao tác GET/CSRF. Nó chỉ tạo audit đăng nhập, không tạo/xóa engagement.

Kiểm tra nghiệp vụ thủ công:

1. `pm.tran`: tạo Engagement phòng HR, loại GREY_BOX, ngày hợp lệ.
2. Thêm Scope `hr.company.local`, WEB_APP, không loại trừ; gửi duyệt.
3. `approver.le`: vào Chờ phê duyệt, Approve Engagement mới.
4. `pm.tran`: kiểm tra Scope khóa; phân công `pentester.pham`.
5. Engagement chuyển IN_PROGRESS; `admin` xem audit log của các thao tác.

Chạy lại sau khi sửa code:

```powershell
docker compose up -d --build
```

Source được COPY vào image, không bind mount từ Windows, nên phải rebuild để đưa thay đổi vào container.

## Tắt, chạy lại và xử lý lỗi

```powershell
docker compose stop
docker compose start
```

Hoặc `docker compose down` rồi `docker compose up -d`. Volume vẫn giữ dữ liệu. **Không dùng `docker compose down -v` nếu muốn giữ dữ liệu**: tùy chọn `-v` xóa volume database và evidence của project.

| Biểu hiện | Cách kiểm tra |
| --- | --- |
| Docker daemon không kết nối được | Mở Docker Desktop, chờ Engine chạy; kiểm tra `docker version` |
| Port 8080 đã được dùng | Đổi `WEB_PORT=8085` trong `.env`, chạy lại `docker compose up -d`, mở localhost:8085 |
| `db` unhealthy | `docker compose logs --tail 100 db`; kiểm tra RAM, mật khẩu SA và kiến trúc x64 |
| `init` Exited (1) | `docker compose logs --tail 100 init`; chưa khởi động web nếu init chưa thành công |
| Database tồn tại nhưng thiếu bootstrap marker | Script chủ động không ghi đè. Kiểm tra/backup database trước; không tự động xóa volume |
| Không tải được package/image | Kiểm tra Internet/proxy của Docker: Docker Hub, Microsoft package registry, PECL |
| UI thiếu icon/chart/Bootstrap | Các thư viện frontend vẫn lấy từ CDN, cần Internet |
| Đăng nhập sai sau khi sửa `.env` | Mật khẩu demo chỉ dùng trong lần khởi tạo đầu; database cũ không tự reset |

`init` không chạy lại schema/seed phá dữ liệu khi database đã có marker khởi tạo. Nếu bootstrap mới thất bại giữa chừng, database rỗng có thể còn tồn tại; lần sau script sẽ dừng để bạn kiểm tra, thay vì âm thầm ghi đè. `schema.sql`/`seed.sql` gốc không phải migration chạy lặp; không import thủ công lên database đang có dữ liệu.

## Chức năng hiện có và giới hạn

Có đăng nhập, quản lý người dùng, dashboard sáu vai trò, Engagement, Scope, phê duyệt, phân công và audit. Chưa có CRUD Findings, Evidence, khắc phục/Retest, đóng Engagement hoặc xuất Reports. Một số menu Findings/Reports còn dẫn tới controller chưa tồn tại. Dashboard có số liệu seed, không phải kết quả từ module đã hoàn thiện.

Phòng ban hiện là trường văn bản `department`, có lọc/thống kê. Chưa có bảng phòng ban chuẩn hóa hoặc phân quyền theo từng engagement/phòng ban. Pentester/Asset Owner/Stakeholder vẫn có thể xem Engagement ngoài phạm vi nghiệp vụ mong muốn. Không sử dụng dữ liệu pentest thật hoặc triển khai công khai trước khi hoàn thiện các kiểm soát này.

Seed có dữ liệu chỉ phục vụ hiển thị: file Evidence không tồn tại thật, có Findings ở đợt chưa duyệt và khắc phục gán mẫu chưa đúng phòng ban.

## Các sửa đổi trong gói Docker

- Sửa DSN PDO_SQLSRV, cấu hình UTF-8 bằng PDO attribute.
- Sửa đường dẫn CSS/JS phù hợp document root `public`.
- Bỏ `php_flag engine off` ở cấp toàn bộ thư mục web; Apache chặn trực tiếp uploads cũ.
- Thay hash giả bằng hash sinh lúc bootstrap; không đưa mật khẩu database vào Git/image.
- Tách named placeholders trong dashboard và tìm kiếm để tương thích native prepared statements.
- Bắt buộc POST + CSRF cho các action thay đổi dữ liệu, bổ sung token cho form xóa/khóa.
- Refresh trạng thái/vai trò từ DB mỗi request; regenerate session ID sau đăng nhập.
- Web dùng login riêng, cấm sửa/xóa Audit Log bằng login ứng dụng.
- Thêm healthcheck DB/web, bootstrap một lần, Docker volumes, script setup và smoke test.

Chưa khắc phục toàn bộ lỗi nghiệp vụ/bảo mật: thiếu transaction cho quyết định phê duyệt và kiểm tra quyền trên từng đối tượng; backend phân công chưa xác minh đầy đủ role/status người nhận. Một số xóa user có thể bị FK chặn, nên dùng khóa tài khoản để giữ lịch sử.

## Push lên GitHub của huyvo13032005

GitHub lưu mã nguồn. Push repository không tạo một website PHP đang chạy; GitHub Pages không chạy PHP/SQL Server. Demo bằng Docker local, hoặc sau này triển khai lên máy chủ có Docker.

### Cách dùng Git + trình duyệt

1. Đăng nhập đúng tài khoản `huyvo13032005`.
2. Mở [tạo repository](https://github.com/new), đặt tên `pentest-mgmt`, chọn Private để chuẩn bị. Nếu muốn portfolio public, bạn có thể chọn Public khi đã rà soát dữ liệu.
3. Không chọn tạo README, `.gitignore` hoặc license ở bước tạo repository (gói đã có README và `.gitignore`).
4. Mở PowerShell trong thư mục project, chạy:

```powershell
git init -b main
git config user.name "Vo Quoc Huy"
git config user.email "EMAIL_DA_XAC_MINH_TREN_GITHUB_HOAC_NOREPLY_CUA_BAN"
git add .
git status --short
git diff --cached --stat
git commit -m "Add pentest management phase 2 with Docker"
git remote add origin https://github.com/huyvo13032005/pentest-mgmt.git
git push -u origin main
```

Thay email placeholder bằng email của bạn trong GitHub Settings → Emails trước khi commit. Git for Windows thường mở đăng nhập trình duyệt qua Git Credential Manager. Không dán token vào code, URL remote hoặc chat.

Nếu repository tên khác, sửa URL tương ứng. Nếu repo đã có code/commit, clone repo đó và làm trên nhánh riêng; không force-push theo hướng dẫn khởi tạo mới này.

`.env`, uploads, database backup và file capture đã được ignore. Kiểm tra bằng:

```powershell
git check-ignore .env
git ls-files .env
```

Lệnh đầu phải in `.env` khi file tồn tại; lệnh sau không được in `.env`.

### Các lần cập nhật sau

```powershell
git add .
git diff --cached --stat
git commit -m "Describe the actual change"
git push
```

## Cấu trúc mã

| Thư mục/file | Vai trò |
| --- | --- |
| `app/controllers` | Xử lý nghiệp vụ |
| `app/models` | Truy vấn SQL Server |
| `app/views` | Giao diện PHP/Bootstrap |
| `app/core` | Router, controller/model nền, kết nối DB |
| `app/middleware` | Đăng nhập và vai trò |
| `public` | Điểm vào, CSS/JS; document root |
| `database` | Schema/seed/views của Phase 2 |
| `docker` | Cấu hình Apache/PHP |
| `scripts` | Setup, bootstrap, healthcheck, smoke test |
| `compose.yaml` | Các dịch vụ và volume Docker |

## Tài liệu kỹ thuật

- [Microsoft: PHP SQL Server driver trên Linux](https://learn.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac?view=sql-server-ver17)
- [Microsoft: SQL Server Docker](https://learn.microsoft.com/en-us/sql/linux/install-upgrade/quickstart-install-docker?view=sql-server-ver17)
- [PDO_SQLSRV 5.13.3](https://pecl.php.net/package/pdo_sqlsrv/5.13.3)
- [GitHub Pages là dịch vụ host nội dung tĩnh](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages)
