/* =============================================================
   VIEWS - Hỗ trợ truy vấn Dashboard & Report
   Chạy SAU khi đã có schema.sql (và seed.sql nếu muốn có dữ liệu)
   ============================================================= */
USE PentestManagementDB;
GO

/* Đếm Finding theo Severity (cho Chart.js - Admin/PM Dashboard) */
IF OBJECT_ID('dbo.vw_findings_by_severity', 'V') IS NOT NULL DROP VIEW dbo.vw_findings_by_severity;
GO
CREATE VIEW dbo.vw_findings_by_severity AS
SELECT severity, COUNT(*) AS total
FROM dbo.findings
GROUP BY severity;
GO

/* Đếm Finding theo Status (cho Chart.js) */
IF OBJECT_ID('dbo.vw_findings_by_status', 'V') IS NOT NULL DROP VIEW dbo.vw_findings_by_status;
GO
CREATE VIEW dbo.vw_findings_by_status AS
SELECT status, COUNT(*) AS total
FROM dbo.findings
GROUP BY status;
GO

/* Đếm Engagement theo phòng ban */
IF OBJECT_ID('dbo.vw_engagements_by_department', 'V') IS NOT NULL DROP VIEW dbo.vw_engagements_by_department;
GO
CREATE VIEW dbo.vw_engagements_by_department AS
SELECT department, COUNT(*) AS total
FROM dbo.engagements
GROUP BY department;
GO

/* Tổng hợp số liệu cho Admin Dashboard cards */
IF OBJECT_ID('dbo.vw_admin_dashboard_cards', 'V') IS NOT NULL DROP VIEW dbo.vw_admin_dashboard_cards;
GO
CREATE VIEW dbo.vw_admin_dashboard_cards AS
SELECT
    (SELECT COUNT(*) FROM dbo.users)                                   AS total_users,
    (SELECT COUNT(*) FROM dbo.engagements WHERE status = 'IN_PROGRESS') AS active_engagements,
    (SELECT COUNT(*) FROM dbo.findings WHERE severity = 'CRITICAL')     AS critical_findings,
    (SELECT COUNT(*) FROM dbo.engagements WHERE status = 'PENDING_APPROVAL') AS pending_approval,
    (SELECT COUNT(*) FROM dbo.findings WHERE status = 'CLOSED')         AS closed_findings;
GO

/* Danh sách Finding kèm tên Engagement + người báo cáo (dùng chung nhiều màn hình) */
IF OBJECT_ID('dbo.vw_findings_detail', 'V') IS NOT NULL DROP VIEW dbo.vw_findings_detail;
GO
CREATE VIEW dbo.vw_findings_detail AS
SELECT
    f.finding_id, f.title, f.severity, f.status, f.cve, f.cwe, f.owasp_category,
    f.created_at, f.updated_at,
    e.engagement_id, e.name AS engagement_name, e.department,
    u.full_name AS reported_by_name
FROM dbo.findings f
JOIN dbo.engagements e ON e.engagement_id = f.engagement_id
JOIN dbo.users u ON u.user_id = f.reported_by;
GO

/* Danh sách Evidence kèm thông tin Finding (dùng cho trang chi tiết Finding) */
IF OBJECT_ID('dbo.vw_evidence_detail', 'V') IS NOT NULL DROP VIEW dbo.vw_evidence_detail;
GO
CREATE VIEW dbo.vw_evidence_detail AS
SELECT
    ev.evidence_id, ev.finding_id, ev.file_name, ev.file_path, ev.file_type,
    ev.file_size, ev.sha256_hash, ev.uploaded_at,
    u.full_name AS uploaded_by_name
FROM dbo.evidence ev
JOIN dbo.users u ON u.user_id = ev.uploaded_by;
GO

PRINT 'Views created successfully.';
