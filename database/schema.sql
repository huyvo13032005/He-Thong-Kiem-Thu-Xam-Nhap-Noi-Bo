/* =============================================================
   HỆ THỐNG QUẢN LÝ KIỂM THỬ XÂM NHẬP NỘI BỘ
   Database Schema - Microsoft SQL Server
   =============================================================
   Cách chạy:
     sqlcmd -S localhost -U sa -P "YourPassword" -i schema.sql
   hoặc mở trong SQL Server Management Studio (SSMS) và Execute.
   ============================================================= */

IF DB_ID('PentestManagementDB') IS NULL
BEGIN
    CREATE DATABASE PentestManagementDB;
END
GO

USE PentestManagementDB;
GO

/* -------------------------------------------------------------
   1. ROLES  (tra cứu 6 vai trò cố định - RBAC)
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.roles', 'U') IS NOT NULL DROP TABLE dbo.roles;
GO
CREATE TABLE dbo.roles (
    role_id     TINYINT IDENTITY(1,1) PRIMARY KEY,
    role_code   VARCHAR(30)  NOT NULL UNIQUE,   -- ADMIN, PROJECT_MANAGER, APPROVER, PENTESTER, ASSET_OWNER, STAKEHOLDER
    role_name   NVARCHAR(100) NOT NULL,
    description NVARCHAR(255) NULL
);
GO

/* -------------------------------------------------------------
   2. USERS
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.users', 'U') IS NOT NULL DROP TABLE dbo.users;
GO
CREATE TABLE dbo.users (
    user_id        INT IDENTITY(1,1) PRIMARY KEY,
    full_name      NVARCHAR(150) NOT NULL,
    username       VARCHAR(50)   NOT NULL UNIQUE,
    email          VARCHAR(150)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255)  NOT NULL,         -- password_hash() output (bcrypt/argon2)
    role_id        TINYINT       NOT NULL,
    department     NVARCHAR(100) NULL,             -- IT, HR, Finance, Sales, Infrastructure...
    avatar_path    VARCHAR(255)  NULL,
    status         VARCHAR(20)   NOT NULL DEFAULT 'ACTIVE', -- ACTIVE, LOCKED, DISABLED
    created_at     DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    updated_at     DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    last_login_at  DATETIME2     NULL,
    CONSTRAINT FK_users_role FOREIGN KEY (role_id) REFERENCES dbo.roles(role_id),
    CONSTRAINT CK_users_status CHECK (status IN ('ACTIVE','LOCKED','DISABLED'))
);
GO
CREATE INDEX IX_users_role_id ON dbo.users(role_id);
CREATE INDEX IX_users_status  ON dbo.users(status);
GO

/* -------------------------------------------------------------
   3. ENGAGEMENTS  (chiến dịch Pentest)
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.engagements', 'U') IS NOT NULL DROP TABLE dbo.engagements;
GO
CREATE TABLE dbo.engagements (
    engagement_id   INT IDENTITY(1,1) PRIMARY KEY,
    name            NVARCHAR(200) NOT NULL,
    department      NVARCHAR(100) NOT NULL,     -- phòng ban sở hữu hệ thống bị test
    description     NVARCHAR(MAX) NULL,
    test_type       VARCHAR(20)   NOT NULL,     -- BLACK_BOX, GREY_BOX, WHITE_BOX
    start_date      DATE          NOT NULL,
    end_date        DATE          NOT NULL,
    status          VARCHAR(20)   NOT NULL DEFAULT 'DRAFT',
        -- DRAFT, PENDING_APPROVAL, APPROVED, REJECTED, IN_PROGRESS, CLOSED
    created_by      INT           NOT NULL,
    created_at      DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    updated_at      DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_engagements_creator FOREIGN KEY (created_by) REFERENCES dbo.users(user_id),
    CONSTRAINT CK_engagements_status CHECK (status IN
        ('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED','IN_PROGRESS','CLOSED')),
    CONSTRAINT CK_engagements_test_type CHECK (test_type IN ('BLACK_BOX','GREY_BOX','WHITE_BOX')),
    CONSTRAINT CK_engagements_dates CHECK (end_date >= start_date)
);
GO
CREATE INDEX IX_engagements_status ON dbo.engagements(status);
CREATE INDEX IX_engagements_creator ON dbo.engagements(created_by);
GO

/* -------------------------------------------------------------
   4. ENGAGEMENT_APPROVALS  (lịch sử phê duyệt Engagement)
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.engagement_approvals', 'U') IS NOT NULL DROP TABLE dbo.engagement_approvals;
GO
CREATE TABLE dbo.engagement_approvals (
    approval_id    INT IDENTITY(1,1) PRIMARY KEY,
    engagement_id  INT         NOT NULL,
    approver_id    INT         NOT NULL,
    decision       VARCHAR(20) NOT NULL,        -- APPROVED, REJECTED
    comment        NVARCHAR(500) NULL,
    decided_at     DATETIME2   NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_approvals_engagement FOREIGN KEY (engagement_id) REFERENCES dbo.engagements(engagement_id) ON DELETE CASCADE,
    CONSTRAINT FK_approvals_approver FOREIGN KEY (approver_id) REFERENCES dbo.users(user_id),
    CONSTRAINT CK_approvals_decision CHECK (decision IN ('APPROVED','REJECTED'))
);
GO
CREATE INDEX IX_approvals_engagement ON dbo.engagement_approvals(engagement_id);
GO

/* -------------------------------------------------------------
   5. SCOPE_ITEMS
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.scope_items', 'U') IS NOT NULL DROP TABLE dbo.scope_items;
GO
CREATE TABLE dbo.scope_items (
    scope_id       INT IDENTITY(1,1) PRIMARY KEY,
    engagement_id  INT           NOT NULL,
    target         NVARCHAR(200) NOT NULL,      -- vd: hr.company.local, 10.0.5.20
    target_type    VARCHAR(30)   NOT NULL,      -- WEB_APP, API, SERVER, DOMAIN_CONTROLLER, VPN_GATEWAY, NETWORK
    description    NVARCHAR(500) NULL,
    is_excluded    BIT           NOT NULL DEFAULT 0,  -- 1 = loại trừ khỏi phạm vi
    is_locked      BIT           NOT NULL DEFAULT 0,  -- 1 = đã khóa sau khi Engagement Approved
    created_at     DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_scope_engagement FOREIGN KEY (engagement_id) REFERENCES dbo.engagements(engagement_id) ON DELETE CASCADE,
    CONSTRAINT CK_scope_target_type CHECK (target_type IN
        ('WEB_APP','API','SERVER','DOMAIN_CONTROLLER','VPN_GATEWAY','NETWORK','OTHER'))
);
GO
CREATE INDEX IX_scope_engagement ON dbo.scope_items(engagement_id);
GO

/* -------------------------------------------------------------
   6. PENTESTER_ASSIGNMENTS
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.pentester_assignments', 'U') IS NOT NULL DROP TABLE dbo.pentester_assignments;
GO
CREATE TABLE dbo.pentester_assignments (
    assignment_id  INT IDENTITY(1,1) PRIMARY KEY,
    engagement_id  INT         NOT NULL,
    pentester_id   INT         NOT NULL,
    assigned_by    INT         NOT NULL,
    priority       VARCHAR(10) NOT NULL DEFAULT 'MEDIUM',  -- LOW, MEDIUM, HIGH
    deadline       DATE        NULL,
    assigned_at    DATETIME2   NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_assign_engagement FOREIGN KEY (engagement_id) REFERENCES dbo.engagements(engagement_id) ON DELETE CASCADE,
    CONSTRAINT FK_assign_pentester FOREIGN KEY (pentester_id) REFERENCES dbo.users(user_id),
    CONSTRAINT FK_assign_by FOREIGN KEY (assigned_by) REFERENCES dbo.users(user_id),
    CONSTRAINT CK_assign_priority CHECK (priority IN ('LOW','MEDIUM','HIGH')),
    CONSTRAINT UQ_assign_engagement_pentester UNIQUE (engagement_id, pentester_id)
);
GO
CREATE INDEX IX_assign_pentester ON dbo.pentester_assignments(pentester_id);
GO

/* -------------------------------------------------------------
   7. FINDINGS
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.findings', 'U') IS NOT NULL DROP TABLE dbo.findings;
GO
CREATE TABLE dbo.findings (
    finding_id      INT IDENTITY(1,1) PRIMARY KEY,
    engagement_id   INT           NOT NULL,
    scope_id        INT           NULL,          -- tài sản bị ảnh hưởng (tham chiếu scope_items)
    reported_by     INT           NOT NULL,       -- Pentester tạo Finding
    title           NVARCHAR(200) NOT NULL,
    description     NVARCHAR(MAX) NOT NULL,
    recommendation  NVARCHAR(MAX) NULL,
    severity        VARCHAR(10)   NOT NULL,       -- CRITICAL, HIGH, MEDIUM, LOW, INFO
    status          VARCHAR(20)   NOT NULL DEFAULT 'DRAFT',
        -- DRAFT, SUBMITTED, CONFIRMED, FALSE_POSITIVE, REMEDIATION, READY_FOR_RETEST, REOPENED, CLOSED
    cve             VARCHAR(30)   NULL,
    cwe             VARCHAR(30)   NULL,
    owasp_category  VARCHAR(100)  NULL,
    confirmed_by    INT           NULL,           -- Project Manager xác nhận
    closed_by       INT           NULL,           -- Approver phê duyệt đóng
    created_at      DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    updated_at      DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_findings_engagement FOREIGN KEY (engagement_id) REFERENCES dbo.engagements(engagement_id) ON DELETE CASCADE,
    CONSTRAINT FK_findings_scope FOREIGN KEY (scope_id) REFERENCES dbo.scope_items(scope_id),
    CONSTRAINT FK_findings_reporter FOREIGN KEY (reported_by) REFERENCES dbo.users(user_id),
    CONSTRAINT FK_findings_confirmer FOREIGN KEY (confirmed_by) REFERENCES dbo.users(user_id),
    CONSTRAINT FK_findings_closer FOREIGN KEY (closed_by) REFERENCES dbo.users(user_id),
    CONSTRAINT CK_findings_severity CHECK (severity IN ('CRITICAL','HIGH','MEDIUM','LOW','INFO')),
    CONSTRAINT CK_findings_status CHECK (status IN
        ('DRAFT','SUBMITTED','CONFIRMED','FALSE_POSITIVE','REMEDIATION','READY_FOR_RETEST','REOPENED','CLOSED'))
);
GO
CREATE INDEX IX_findings_engagement ON dbo.findings(engagement_id);
CREATE INDEX IX_findings_severity   ON dbo.findings(severity);
CREATE INDEX IX_findings_status     ON dbo.findings(status);
GO

/* -------------------------------------------------------------
   8. EVIDENCE
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.evidence', 'U') IS NOT NULL DROP TABLE dbo.evidence;
GO
CREATE TABLE dbo.evidence (
    evidence_id    INT IDENTITY(1,1) PRIMARY KEY,
    finding_id     INT           NOT NULL,
    file_name      NVARCHAR(255) NOT NULL,
    file_path      VARCHAR(500)  NOT NULL,       -- đường dẫn trong public/uploads
    file_type      VARCHAR(10)   NOT NULL,       -- PNG, JPG, PDF, TXT, PCAP, ZIP
    file_size      BIGINT        NOT NULL,       -- bytes
    sha256_hash    CHAR(64)      NOT NULL,       -- toàn vẹn dữ liệu
    uploaded_by    INT           NOT NULL,
    uploaded_at    DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_evidence_finding FOREIGN KEY (finding_id) REFERENCES dbo.findings(finding_id) ON DELETE CASCADE,
    CONSTRAINT FK_evidence_uploader FOREIGN KEY (uploaded_by) REFERENCES dbo.users(user_id),
    CONSTRAINT CK_evidence_file_type CHECK (file_type IN ('PNG','JPG','PDF','TXT','PCAP','ZIP'))
);
GO
CREATE INDEX IX_evidence_finding ON dbo.evidence(finding_id);
GO

/* -------------------------------------------------------------
   9. REMEDIATIONS
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.remediations', 'U') IS NOT NULL DROP TABLE dbo.remediations;
GO
CREATE TABLE dbo.remediations (
    remediation_id  INT IDENTITY(1,1) PRIMARY KEY,
    finding_id      INT           NOT NULL UNIQUE,   -- 1-1 với Finding
    asset_owner_id  INT           NOT NULL,
    description     NVARCHAR(MAX) NULL,
    status          VARCHAR(20)   NOT NULL DEFAULT 'IN_PROGRESS',
        -- IN_PROGRESS, FIXED, RISK_ACCEPTED, FAILED_RETEST
    evidence_path   VARCHAR(500)  NULL,   -- file minh chứng đã khắc phục (upload riêng)
    retest_result   VARCHAR(10)   NULL,   -- PASS, FAIL
    retest_comment  NVARCHAR(500) NULL,
    retested_by     INT           NULL,
    retested_at     DATETIME2     NULL,
    updated_at      DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_remediation_finding FOREIGN KEY (finding_id) REFERENCES dbo.findings(finding_id) ON DELETE CASCADE,
    CONSTRAINT FK_remediation_owner FOREIGN KEY (asset_owner_id) REFERENCES dbo.users(user_id),
    CONSTRAINT FK_remediation_retester FOREIGN KEY (retested_by) REFERENCES dbo.users(user_id),
    CONSTRAINT CK_remediation_status CHECK (status IN ('IN_PROGRESS','FIXED','RISK_ACCEPTED','FAILED_RETEST')),
    CONSTRAINT CK_remediation_retest CHECK (retest_result IN ('PASS','FAIL') OR retest_result IS NULL)
);
GO
CREATE INDEX IX_remediation_owner ON dbo.remediations(asset_owner_id);
GO

/* -------------------------------------------------------------
   10. REPORTS
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.reports', 'U') IS NOT NULL DROP TABLE dbo.reports;
GO
CREATE TABLE dbo.reports (
    report_id      INT IDENTITY(1,1) PRIMARY KEY,
    engagement_id  INT           NOT NULL,
    generated_by   INT           NOT NULL,
    report_type    VARCHAR(20)   NOT NULL DEFAULT 'FULL',  -- FULL (nội bộ), SUMMARY (Stakeholder)
    file_path      VARCHAR(500)  NULL,
    generated_at   DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_reports_engagement FOREIGN KEY (engagement_id) REFERENCES dbo.engagements(engagement_id) ON DELETE CASCADE,
    CONSTRAINT FK_reports_generator FOREIGN KEY (generated_by) REFERENCES dbo.users(user_id),
    CONSTRAINT CK_reports_type CHECK (report_type IN ('FULL','SUMMARY'))
);
GO
CREATE INDEX IX_reports_engagement ON dbo.reports(engagement_id);
GO

/* -------------------------------------------------------------
   11. AUDIT_LOGS
   ------------------------------------------------------------- */
IF OBJECT_ID('dbo.audit_logs', 'U') IS NOT NULL DROP TABLE dbo.audit_logs;
GO
CREATE TABLE dbo.audit_logs (
    log_id        BIGINT IDENTITY(1,1) PRIMARY KEY,
    user_id       INT           NULL,        -- NULL nếu hành động của hệ thống / trước khi đăng nhập
    action        VARCHAR(100)  NOT NULL,    -- LOGIN, LOGIN_FAILED, CREATE_ENGAGEMENT, APPROVE_SCOPE, UPLOAD_EVIDENCE...
    object_type   VARCHAR(50)   NULL,        -- ENGAGEMENT, FINDING, EVIDENCE, USER...
    object_id     INT           NULL,
    description   NVARCHAR(500) NULL,
    ip_address    VARCHAR(45)   NULL,
    created_at    DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT FK_audit_user FOREIGN KEY (user_id) REFERENCES dbo.users(user_id)
);
GO
CREATE INDEX IX_audit_user ON dbo.audit_logs(user_id);
CREATE INDEX IX_audit_created ON dbo.audit_logs(created_at);
GO

PRINT 'Schema created successfully.';
