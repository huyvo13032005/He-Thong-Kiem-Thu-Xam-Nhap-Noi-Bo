<?php
/**
 * app/controllers/AuditController.php
 * -----------------------------------------------------------------------
 * Admin xem Audit Log - CHỈ ĐỌC (không có update/delete route nào được
 * định nghĩa cho bảng audit_logs, đúng yêu cầu "Không được sửa").
 * -----------------------------------------------------------------------
 */

class AuditController extends Controller
{
    public function index(): void
    {
        RoleMiddleware::require(['ADMIN']);

        $db = Database::getConnection();
        $search = trim($_GET['search'] ?? '');
        $action = trim($_GET['action'] ?? '');
        $page = max((int)($_GET['page'] ?? 1), 1);
        $perPage = 15;

        $where = ['1=1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.full_name LIKE :search1 OR l.description LIKE :search2 OR l.ip_address LIKE :search3)';
            foreach (['search1', 'search2', 'search3'] as $key) {
                $params[$key] = '%' . $search . '%';
            }
        }
        if ($action !== '') {
            $where[] = 'l.action = :action';
            $params['action'] = $action;
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM audit_logs l LEFT JOIN users u ON u.user_id = l.user_id WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['cnt'];

        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare(
            "SELECT l.*, u.full_name, u.username
             FROM audit_logs l LEFT JOIN users u ON u.user_id = l.user_id
             WHERE {$whereSql}
             ORDER BY l.created_at DESC
             OFFSET {$offset} ROWS FETCH NEXT {$perPage} ROWS ONLY"
        );
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $distinctActions = $db->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

        $this->render('audit/index', [
            'pageTitle'   => 'Audit Log',
            'breadcrumbs' => ['Audit Log' => null],
            'logs'        => $logs,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => (int) ceil($total / $perPage),
            'search'      => $search,
            'actionFilter'=> $action,
            'distinctActions' => $distinctActions,
        ]);
    }
}
