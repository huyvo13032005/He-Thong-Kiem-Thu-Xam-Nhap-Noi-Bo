<?php
/**
 * app/controllers/DashboardController.php
 * -----------------------------------------------------------------------
 * Mỗi Role có 1 Dashboard riêng (view riêng), nhưng dùng chung 1 Controller
 * vì route vào là như nhau (/index.php?url=dashboard).
 *
 * Dữ liệu Dashboard lấy từ các VIEW đã tạo sẵn trong database/views.sql
 * (vw_admin_dashboard_cards, vw_findings_by_severity, vw_findings_by_status,
 * vw_engagements_by_department) để tránh lặp lại logic JOIN/GROUP BY ở PHP.
 * -----------------------------------------------------------------------
 */

class DashboardController extends Controller
{
    public function index(): void
    {
        $role = $this->currentRole();
        $db = Database::getConnection();

        switch ($role) {
            case 'ADMIN':
                $cards = $db->query('SELECT * FROM vw_admin_dashboard_cards')->fetch();
                $bySeverity = $db->query('SELECT * FROM vw_findings_by_severity')->fetchAll();
                $byStatus   = $db->query('SELECT * FROM vw_findings_by_status')->fetchAll();
                $byDept     = $db->query('SELECT * FROM vw_engagements_by_department')->fetchAll();

                $this->render('dashboard/admin', [
                    'pageTitle'   => 'Admin Dashboard',
                    'cards'       => $cards,
                    'bySeverity'  => $bySeverity,
                    'byStatus'    => $byStatus,
                    'byDept'      => $byDept,
                ]);
                break;

            case 'PROJECT_MANAGER':
                $stmt = $db->query(
                    "SELECT
                        (SELECT COUNT(*) FROM engagements WHERE status = 'IN_PROGRESS') AS running,
                        (SELECT COUNT(*) FROM engagements WHERE status = 'PENDING_APPROVAL') AS pending_approval,
                        (SELECT COUNT(DISTINCT pentester_id) FROM pentester_assignments) AS pentesters_working,
                        (SELECT COUNT(*) FROM findings WHERE status = 'SUBMITTED') AS unconfirmed_findings,
                        (SELECT COUNT(*) FROM reports) AS total_reports"
                );
                $this->render('dashboard/project_manager', [
                    'pageTitle' => 'Project Manager Dashboard',
                    'cards' => $stmt->fetch(),
                ]);
                break;

            case 'APPROVER':
                $stmt = $db->query(
                    "SELECT
                        (SELECT COUNT(*) FROM engagements WHERE status = 'PENDING_APPROVAL') AS pending_engagements,
                        (SELECT COUNT(*) FROM findings WHERE status = 'READY_FOR_RETEST') AS pending_close,
                        (SELECT COUNT(*) FROM engagement_approvals WHERE decision = 'APPROVED') AS approved_total,
                        (SELECT COUNT(*) FROM engagement_approvals WHERE decision = 'REJECTED') AS rejected_total"
                );
                $this->render('dashboard/approver', [
                    'pageTitle' => 'Approver Dashboard',
                    'cards' => $stmt->fetch(),
                ]);
                break;

            case 'PENTESTER':
                $userId = $this->currentUser()['user_id'];
                $stmt = $db->prepare(
                    "SELECT
                        (SELECT COUNT(*) FROM pentester_assignments WHERE pentester_id = :uid1) AS assigned_tasks,
                        (SELECT COUNT(*) FROM findings WHERE reported_by = :uid2 AND status = 'DRAFT') AS findings_draft,
                        (SELECT COUNT(*) FROM findings WHERE reported_by = :uid3 AND status = 'CONFIRMED') AS findings_confirmed,
                        (SELECT COUNT(*) FROM remediations WHERE status = 'FIXED' AND retest_result IS NULL) AS retest_waiting"
                );
                $stmt->execute(['uid1' => $userId, 'uid2' => $userId, 'uid3' => $userId]);
                $this->render('dashboard/pentester', [
                    'pageTitle' => 'Pentester Dashboard',
                    'cards' => $stmt->fetch(),
                ]);
                break;

            case 'ASSET_OWNER':
                $userId = $this->currentUser()['user_id'];
                $stmt = $db->prepare(
                    "SELECT
                        (SELECT COUNT(*) FROM remediations WHERE asset_owner_id = :uid1 AND status = 'IN_PROGRESS') AS findings_open,
                        (SELECT COUNT(*) FROM remediations WHERE asset_owner_id = :uid2 AND status = 'FIXED' AND retest_result IS NULL) AS ready_for_retest,
                        (SELECT COUNT(*) FROM remediations WHERE asset_owner_id = :uid3 AND retest_result = 'PASS') AS fixed_total,
                        (SELECT COUNT(*) FROM remediations WHERE asset_owner_id = :uid4 AND retest_result = 'FAIL') AS failed_retest"
                );
                $stmt->execute(['uid1' => $userId, 'uid2' => $userId, 'uid3' => $userId, 'uid4' => $userId]);
                $this->render('dashboard/asset_owner', [
                    'pageTitle' => 'Asset Owner Dashboard',
                    'cards' => $stmt->fetch(),
                ]);
                break;

            case 'STAKEHOLDER':
            default:
                $bySeverity = $db->query('SELECT * FROM vw_findings_by_severity')->fetchAll();
                $byDept     = $db->query('SELECT * FROM vw_engagements_by_department')->fetchAll();
                $this->render('dashboard/stakeholder', [
                    'pageTitle'  => 'Stakeholder Dashboard',
                    'bySeverity' => $bySeverity,
                    'byDept'     => $byDept,
                ]);
                break;
        }
    }
}
