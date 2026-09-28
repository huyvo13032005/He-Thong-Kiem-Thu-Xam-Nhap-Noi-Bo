/* =============================================================
   SEED DATA - Dữ liệu demo cho Hệ thống Quản lý Kiểm thử Xâm nhập
   Chạy SAU khi đã chạy schema.sql
   ============================================================= */
USE PentestManagementDB;
GO

/* -------------------- 1. ROLES (6 vai trò cố định) -------------------- */
INSERT INTO dbo.roles (role_code, role_name, description) VALUES
('ADMIN',           N'Admin',            N'Quản trị toàn hệ thống, không tham gia nghiệp vụ Pentest'),
('PROJECT_MANAGER',   N'Project Manager',  N'Quản lý Engagement, Scope, phân công, xác nhận Finding'),
('APPROVER',        N'Approver',         N'Phê duyệt Engagement/Scope và phê duyệt đóng Finding'),
('PENTESTER',       N'Pentester',        N'Thực hiện kiểm thử, ghi Finding, upload Evidence, Retest'),
('ASSET_OWNER',     N'Asset Owner',      N'Khắc phục Finding liên quan tài sản mình phụ trách'),
('STAKEHOLDER',     N'Stakeholder',      N'Chỉ xem Dashboard và Báo cáo tổng hợp');
GO

/* -------------------- 2. USERS (20 user) --------------------
   Docker: scripts/init-db.php replaces the token below with a real bcrypt hash.
   The initial demo password comes from DEMO_PASSWORD in .env.
   Direct SSMS import: generate a hash with PHP and replace the token first.
--------------------------------------------------------------- */
DECLARE @hash VARCHAR(255) = '__GENERATE_WITH_INIT_SCRIPT__';

INSERT INTO dbo.users (full_name, username, email, password_hash, role_id, department, status) VALUES
(N'Nguyen Van Admin',   'admin',        'admin@company.local',        @hash, 1, N'IT',            'ACTIVE'),
(N'Tran Thi Quan Ly',   'pm.tran',      'pm.tran@company.local',      @hash, 2, N'IT',            'ACTIVE'),
(N'Le Van Duyet',       'approver.le',  'approver.le@company.local',  @hash, 3, N'IT',            'ACTIVE'),
(N'Pham Thi Kiem Thu',  'pentester.pham','pentester.pham@company.local',@hash,4, N'IT',            'ACTIVE'),
(N'Hoang Van Test',     'pentester.hoang','pentester.hoang@company.local',@hash,4,N'IT',           'ACTIVE'),
(N'Vu Thi Bao Mat',     'pentester.vu', 'pentester.vu@company.local', @hash, 4, N'IT',            'ACTIVE'),
(N'Do Van HR Owner',    'owner.hr',     'owner.hr@company.local',     @hash, 5, N'HR',             'ACTIVE'),
(N'Bui Thi Finance',    'owner.finance','owner.finance@company.local',@hash, 5, N'Finance',        'ACTIVE'),
(N'Dang Van Sales',     'owner.sales',  'owner.sales@company.local',  @hash, 5, N'Sales',          'ACTIVE'),
(N'Ngo Thi Infra',      'owner.infra',  'owner.infra@company.local',  @hash, 5, N'Infrastructure', 'ACTIVE'),
(N'CEO Stakeholder',    'ceo',          'ceo@company.local',          @hash, 6, N'Ban lanh dao',   'ACTIVE'),
(N'CISO Stakeholder',   'ciso',         'ciso@company.local',         @hash, 6, N'Ban lanh dao',   'ACTIVE'),
(N'Second PM',          'pm.nguyen',    'pm.nguyen@company.local',    @hash, 2, N'IT',            'ACTIVE'),
(N'Second Approver',    'approver.dao', 'approver.dao@company.local', @hash, 3, N'IT',            'ACTIVE'),
(N'Pentester Extra 1',  'pentester.mai','pentester.mai@company.local',@hash, 4, N'IT',            'ACTIVE'),
(N'Pentester Extra 2',  'pentester.duc','pentester.duc@company.local',@hash, 4, N'IT',            'ACTIVE'),
(N'Asset Owner IT',     'owner.it',     'owner.it@company.local',     @hash, 5, N'IT',            'ACTIVE'),
(N'Locked User Demo',   'locked.demo',  'locked.demo@company.local',  @hash, 4, N'IT',            'LOCKED'),
(N'Disabled User Demo', 'disabled.demo','disabled.demo@company.local',@hash, 6, N'Sales',          'DISABLED'),
(N'Admin Backup',       'admin2',       'admin2@company.local',       @hash, 1, N'IT',            'ACTIVE');
GO

/* -------------------- 3. ENGAGEMENTS (5 chiến dịch) -------------------- */
INSERT INTO dbo.engagements (name, department, description, test_type, start_date, end_date, status, created_by) VALUES
(N'Pentest Website HR Portal', N'HR',
   N'Kiểm thử bảo mật cổng thông tin nhân sự nội bộ', 'GREY_BOX', '2026-08-01', '2026-08-15', 'CLOSED', 2),
(N'Pentest API Finance', N'Finance',
   N'Kiểm thử REST API hệ thống kế toán - tài chính', 'BLACK_BOX', '2026-09-01', '2026-09-20', 'IN_PROGRESS', 2),
(N'Pentest Active Directory - Infrastructure', N'Infrastructure',
   N'Đánh giá bảo mật Domain Controller và AD nội bộ', 'WHITE_BOX', '2026-09-10', '2026-09-30', 'APPROVED', 13),
(N'Pentest VPN Gateway', N'Infrastructure',
   N'Kiểm thử cổng VPN truy cập từ xa cho nhân viên', 'GREY_BOX', '2026-10-01', '2026-10-10', 'PENDING_APPROVAL', 2),
(N'Pentest Sales CRM Web App', N'Sales',
   N'Kiểm thử ứng dụng CRM quản lý khách hàng', 'BLACK_BOX', '2026-10-15', '2026-10-25', 'DRAFT', 13);
GO

/* -------------------- 4. ENGAGEMENT_APPROVALS --------------------
   LƯU Ý: chỉ insert quyết định cho Engagement đã thực sự có kết quả
   (APPROVED/REJECTED). Engagement #4 đang ở trạng thái PENDING_APPROVAL
   (chưa có quyết định) nên KHÔNG được seed sẵn 1 bản ghi ở đây, nếu
   không sẽ gây mâu thuẫn dữ liệu (đã "duyệt" nhưng status vẫn "chờ duyệt").
------------------------------------------------------------------- */
INSERT INTO dbo.engagement_approvals (engagement_id, approver_id, decision, comment) VALUES
(1, 3, 'APPROVED', N'Phạm vi rõ ràng, đồng ý cho kiểm thử'),
(2, 3, 'APPROVED', N'Đồng ý phạm vi API Finance, lưu ý khung giờ kiểm thử ngoài giờ hành chính'),
(3, 3, 'APPROVED', N'Đã xác nhận với phòng Infrastructure');
GO

/* -------------------- 5. SCOPE_ITEMS -------------------- */
INSERT INTO dbo.scope_items (engagement_id, target, target_type, description, is_excluded, is_locked) VALUES
(1, N'hr.company.local', 'WEB_APP', N'Web chính HR Portal', 0, 1),
(1, N'api.hr.company.local', 'API', N'API backend HR', 0, 1),
(1, N'mail.company.local', 'SERVER', N'Mail server - LOẠI TRỪ khỏi phạm vi', 1, 1),
(2, N'api.finance.company.local', 'API', N'API hệ thống kế toán', 0, 1),
(3, N'dc01.company.local', 'DOMAIN_CONTROLLER', N'Domain Controller chính', 0, 1),
(3, N'ad.company.local', 'SERVER', N'Active Directory server', 0, 1),
(4, N'vpn.company.local', 'VPN_GATEWAY', N'Cổng VPN truy cập từ xa', 0, 0),
(5, N'crm.company.local', 'WEB_APP', N'CRM web app', 0, 0);
GO

/* -------------------- 6. PENTESTER_ASSIGNMENTS -------------------- */
INSERT INTO dbo.pentester_assignments (engagement_id, pentester_id, assigned_by, priority, deadline) VALUES
(1, 4, 2, 'HIGH',   '2026-08-14'),
(2, 5, 2, 'HIGH',   '2026-09-19'),
(2, 6, 2, 'MEDIUM', '2026-09-19'),
(3, 4, 13,'HIGH',   '2026-09-29'),
(3, 15,13,'MEDIUM', '2026-09-29');
GO

/* -------------------- 7. FINDINGS (30 finding, sinh bằng vòng lặp) -------------------- */
DECLARE @i INT = 1;
DECLARE @severities TABLE (s VARCHAR(10));
INSERT INTO @severities VALUES ('CRITICAL'),('HIGH'),('MEDIUM'),('LOW'),('INFO');
DECLARE @statuses TABLE (st VARCHAR(20));
INSERT INTO @statuses VALUES ('DRAFT'),('SUBMITTED'),('CONFIRMED'),('REMEDIATION'),('READY_FOR_RETEST'),('CLOSED');

WHILE @i <= 30
BEGIN
    DECLARE @eng INT = ((@i - 1) % 4) + 1;              -- rải đều engagement 1-4
    DECLARE @sev VARCHAR(10) = (SELECT s FROM (SELECT s, ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) rn FROM @severities) x WHERE rn = ((@i - 1) % 5) + 1);
    DECLARE @stt VARCHAR(20) = (SELECT st FROM (SELECT st, ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) rn FROM @statuses) x WHERE rn = ((@i - 1) % 6) + 1);
    DECLARE @reporter INT = CASE WHEN @i % 3 = 0 THEN 6 WHEN @i % 2 = 0 THEN 5 ELSE 4 END;

    INSERT INTO dbo.findings (engagement_id, scope_id, reported_by, title, description, recommendation, severity, status, cwe, owasp_category)
    VALUES (
        @eng,
        NULL,
        @reporter,
        N'Finding demo #' + CAST(@i AS NVARCHAR(5)) + N' - ' +
            CASE @sev WHEN 'CRITICAL' THEN N'SQL Injection tại form đăng nhập'
                      WHEN 'HIGH'     THEN N'Broken Access Control tại API'
                      WHEN 'MEDIUM'   THEN N'Thiếu rate-limiting cho endpoint nhạy cảm'
                      WHEN 'LOW'      THEN N'Thiếu header bảo mật (CSP, HSTS)'
                      ELSE N'Thông tin phiên bản phần mềm bị lộ' END,
        N'Mô tả chi tiết lỗ hổng demo số ' + CAST(@i AS NVARCHAR(5)) + N' phục vụ dữ liệu mẫu cho đồ án.',
        N'Áp dụng input validation, parameterized query và rà soát lại cấu hình bảo mật liên quan.',
        @sev,
        @stt,
        CASE @sev WHEN 'CRITICAL' THEN 'CWE-89' WHEN 'HIGH' THEN 'CWE-284' ELSE NULL END,
        CASE @sev WHEN 'CRITICAL' THEN 'A03:2021-Injection' WHEN 'HIGH' THEN 'A01:2021-Broken Access Control' ELSE NULL END
    );

    SET @i += 1;
END
GO

/* -------------------- 8. EVIDENCE (50 evidence, sinh bằng vòng lặp) -------------------- */
DECLARE @j INT = 1;
DECLARE @maxFinding INT = (SELECT MAX(finding_id) FROM dbo.findings);

WHILE @j <= 50
BEGIN
    DECLARE @fid INT = ((@j - 1) % @maxFinding) + 1;
    DECLARE @ftype VARCHAR(10) = CASE (@j % 5)
        WHEN 0 THEN 'PNG' WHEN 1 THEN 'JPG' WHEN 2 THEN 'PDF' WHEN 3 THEN 'TXT' ELSE 'ZIP' END;
    DECLARE @uploader INT = CASE WHEN @j % 3 = 0 THEN 6 WHEN @j % 2 = 0 THEN 5 ELSE 4 END;

    INSERT INTO dbo.evidence (finding_id, file_name, file_path, file_type, file_size, sha256_hash, uploaded_by)
    VALUES (
        @fid,
        N'evidence_' + CAST(@j AS NVARCHAR(5)) + '.' + LOWER(@ftype),
        '/public/uploads/evidence_' + CAST(@j AS NVARCHAR(5)) + '.' + LOWER(@ftype),
        @ftype,
        102400 + (@j * 137),
        CONVERT(CHAR(64), HASHBYTES('SHA2_256', CAST(@j AS VARCHAR(10)) + 'demo-seed-evidence'), 2),
        @uploader
    );

    SET @j += 1;
END
GO

/* -------------------- 9. REMEDIATIONS (cho các Finding ở trạng thái REMEDIATION/READY_FOR_RETEST/CLOSED) -------------------- */
INSERT INTO dbo.remediations (finding_id, asset_owner_id, description, status)
SELECT finding_id,
       7,  -- gán demo cho Asset Owner HR
       N'Đang xử lý theo khuyến nghị của Pentester.',
       CASE status
            WHEN 'REMEDIATION'       THEN 'IN_PROGRESS'
            WHEN 'READY_FOR_RETEST'  THEN 'FIXED'
            WHEN 'CLOSED'            THEN 'FIXED'
            ELSE 'IN_PROGRESS'
       END
FROM dbo.findings
WHERE status IN ('REMEDIATION','READY_FOR_RETEST','CLOSED');
GO

/* -------------------- 10. AUDIT_LOGS (một số log mẫu) -------------------- */
INSERT INTO dbo.audit_logs (user_id, action, object_type, object_id, description, ip_address) VALUES
(1, 'LOGIN', 'USER', 1, N'Admin đăng nhập hệ thống', '10.0.0.5'),
(2, 'CREATE_ENGAGEMENT', 'ENGAGEMENT', 1, N'Tạo Engagement Pentest Website HR Portal', '10.0.0.11'),
(3, 'APPROVE_ENGAGEMENT', 'ENGAGEMENT', 1, N'Phê duyệt Engagement #1', '10.0.0.12'),
(4, 'CREATE_FINDING', 'FINDING', 1, N'Ghi nhận Finding demo #1', '10.0.0.20'),
(4, 'UPLOAD_EVIDENCE', 'EVIDENCE', 1, N'Upload evidence_1.png', '10.0.0.20');
GO

PRINT 'Seed data inserted successfully: 6 roles, 20 users, 5 engagements, 8 scope items, 30 findings, 50 evidence.';
