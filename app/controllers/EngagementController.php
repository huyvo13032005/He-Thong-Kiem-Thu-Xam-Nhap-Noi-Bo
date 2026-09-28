<?php
/**
 * app/controllers/EngagementController.php
 * -----------------------------------------------------------------------
 * Module ENGAGEMENT MANAGEMENT (Phase 2).
 *
 * Quyền theo 6 vai trò:
 *   - PROJECT_MANAGER : CRUD Engagement (chỉ sửa khi DRAFT/REJECTED), gửi phê duyệt
 *   - APPROVER        : chỉ xem (thao tác Approve/Reject nằm ở ApprovalController)
 *   - PENTESTER       : chỉ xem Engagement mình được phân công (lọc ở view)
 *   - ASSET_OWNER     : chỉ xem
 *   - STAKEHOLDER     : chỉ xem
 *   - ADMIN           : KHÔNG được truy cập (Admin không tham gia nghiệp vụ Pentest)
 *
 * Route:
 *   GET  /index.php?url=engagement/index
 *   GET  /index.php?url=engagement/create
 *   POST /index.php?url=engagement/store
 *   GET  /index.php?url=engagement/show/{id}          (tabs: Overview/Scope/Assignments/Audit)
 *   GET  /index.php?url=engagement/edit/{id}
 *   POST /index.php?url=engagement/update/{id}
 *   POST /index.php?url=engagement/submitForApproval/{id}
 * -----------------------------------------------------------------------
 */

class EngagementController extends Controller
{
    private const VIEW_ROLES = ['PROJECT_MANAGER', 'APPROVER', 'PENTESTER', 'ASSET_OWNER', 'STAKEHOLDER'];
    private const MANAGE_ROLES = ['PROJECT_MANAGER'];

    private EngagementModel $engagementModel;
    private ScopeModel $scopeModel;
    private ApprovalModel $approvalModel;
    private AssignmentModel $assignmentModel;

    public function __construct()
    {
        $this->engagementModel = new EngagementModel();
        $this->scopeModel      = new ScopeModel();
        $this->approvalModel   = new ApprovalModel();
        $this->assignmentModel = new AssignmentModel();
    }

    public function index(): void
    {
        RoleMiddleware::require(self::VIEW_ROLES);

        $status     = trim($_GET['status'] ?? '');
        $department = trim($_GET['department'] ?? '');
        $page       = max((int) ($_GET['page'] ?? 1), 1);
        $perPage    = 10;

        $result = $this->engagementModel->paginate($status, $department, $page, $perPage);

        $this->render('engagement/index', [
            'pageTitle'    => 'Engagement',
            'breadcrumbs'  => ['Engagement' => null],
            'engagements'  => $result['data'],
            'total'        => $result['total'],
            'page'         => $page,
            'totalPages'   => (int) ceil($result['total'] / $perPage),
            'statusFilter' => $status,
            'deptFilter'   => $department,
            'departments'  => $this->engagementModel->distinctDepartments(),
            'statuses'     => EngagementModel::STATUSES,
        ]);
    }

    public function create(): void
    {
        RoleMiddleware::require(self::MANAGE_ROLES);

        $this->render('engagement/form', [
            'pageTitle'   => 'Tạo Engagement',
            'breadcrumbs' => ['Engagement' => 'engagement/index', 'Tạo mới' => null],
            'engagement'  => null,
            'testTypes'   => EngagementModel::TEST_TYPES,
            'formAction'  => 'engagement/store',
        ]);
    }

    public function store(): void
    {
        RoleMiddleware::require(self::MANAGE_ROLES);
        Csrf::verify($this->input('csrf_token', ''));

        $errors = $this->validate($this->input('name'), $this->input('department'), $this->input('test_type'),
            $this->input('start_date'), $this->input('end_date'));

        if (!empty($errors)) {
            $this->flash('danger', implode(' ', $errors));
            $this->redirect('engagement/create');
        }

        $engagementId = $this->engagementModel->insert([
            'name'        => $this->input('name'),
            'department'  => $this->input('department'),
            'description' => $this->input('description'),
            'test_type'   => $this->input('test_type'),
            'start_date'  => $this->input('start_date'),
            'end_date'    => $this->input('end_date'),
            'status'      => 'DRAFT',
            'created_by'  => $this->currentUser()['user_id'],
        ]);

        AuditHelper::log($this->currentUser()['user_id'], 'CREATE_ENGAGEMENT', 'ENGAGEMENT', $engagementId,
            'Tạo Engagement: ' . $this->input('name'));

        $this->flash('success', 'Tạo Engagement thành công (trạng thái Draft).');
        $this->redirect('engagement/show/' . $engagementId);
    }

    public function show($id): void
    {
        RoleMiddleware::require(self::VIEW_ROLES);
        $id = (int) $id;

        $engagement = $this->engagementModel->findDetailed($id);
        if (!$engagement) {
            $this->flash('danger', 'Không tìm thấy Engagement.');
            $this->redirect('engagement/index');
        }

        // Truy vết Audit Log liên quan đến Engagement này (kể cả Scope/Assignment con của nó)
        $auditStmt = Database::getConnection()->prepare(
            "SELECT l.*, u.full_name FROM audit_logs l LEFT JOIN users u ON u.user_id = l.user_id
             WHERE (l.object_type = 'ENGAGEMENT' AND l.object_id = :id1)
                OR l.description LIKE :pattern
             ORDER BY l.created_at DESC"
        );
        $auditStmt->execute(['id1' => $id, 'pattern' => '%Engagement #' . $id . '%']);

        $this->render('engagement/show', [
            'pageTitle'      => $engagement['name'],
            'breadcrumbs'    => ['Engagement' => 'engagement/index', $engagement['name'] => null],
            'engagement'     => $engagement,
            'scopeItems'     => $this->scopeModel->findByEngagement($id),
            'targetTypes'    => ScopeModel::TARGET_TYPES,
            'assignments'    => $this->assignmentModel->findByEngagement($id),
            'pentesters'     => $this->assignmentModel->availablePentesters(),
            'priorities'     => AssignmentModel::PRIORITIES,
            'approvalHistory'=> $this->approvalModel->historyForEngagement($id),
            'auditTrail'     => $auditStmt->fetchAll(),
            'canManage'      => RoleMiddleware::can(self::MANAGE_ROLES)
                                 && in_array($engagement['status'], ['DRAFT', 'REJECTED'], true),
            'canAssign'      => RoleMiddleware::can(self::MANAGE_ROLES)
                                 && in_array($engagement['status'], ['APPROVED', 'IN_PROGRESS'], true),
            'canSubmit'      => RoleMiddleware::can(self::MANAGE_ROLES)
                                 && in_array($engagement['status'], ['DRAFT', 'REJECTED'], true),
        ]);
    }

    public function edit($id): void
    {
        RoleMiddleware::require(self::MANAGE_ROLES);
        $id = (int) $id;
        $engagement = $this->engagementModel->find($id);

        if (!$engagement) {
            $this->flash('danger', 'Không tìm thấy Engagement.');
            $this->redirect('engagement/index');
        }
        if (!in_array($engagement['status'], ['DRAFT', 'REJECTED'], true)) {
            $this->flash('warning', 'Chỉ có thể sửa Engagement khi ở trạng thái Draft hoặc Rejected.');
            $this->redirect('engagement/show/' . $id);
        }

        $this->render('engagement/form', [
            'pageTitle'   => 'Sửa Engagement',
            'breadcrumbs' => ['Engagement' => 'engagement/index', $engagement['name'] => 'engagement/show/' . $id, 'Sửa' => null],
            'engagement'  => $engagement,
            'testTypes'   => EngagementModel::TEST_TYPES,
            'formAction'  => 'engagement/update/' . $id,
        ]);
    }

    public function update($id): void
    {
        RoleMiddleware::require(self::MANAGE_ROLES);
        Csrf::verify($this->input('csrf_token', ''));
        $id = (int) $id;

        $engagement = $this->engagementModel->find($id);
        if (!$engagement || !in_array($engagement['status'], ['DRAFT', 'REJECTED'], true)) {
            $this->flash('danger', 'Không thể sửa Engagement ở trạng thái hiện tại.');
            $this->redirect('engagement/show/' . $id);
        }

        $errors = $this->validate($this->input('name'), $this->input('department'), $this->input('test_type'),
            $this->input('start_date'), $this->input('end_date'));

        if (!empty($errors)) {
            $this->flash('danger', implode(' ', $errors));
            $this->redirect('engagement/edit/' . $id);
        }

        $this->engagementModel->update($id, [
            'name'        => $this->input('name'),
            'department'  => $this->input('department'),
            'description' => $this->input('description'),
            'test_type'   => $this->input('test_type'),
            'start_date'  => $this->input('start_date'),
            'end_date'    => $this->input('end_date'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        AuditHelper::log($this->currentUser()['user_id'], 'UPDATE_ENGAGEMENT', 'ENGAGEMENT', $id,
            'Cập nhật Engagement #' . $id);

        $this->flash('success', 'Cập nhật Engagement thành công.');
        $this->redirect('engagement/show/' . $id);
    }

    /** Project Manager gửi Engagement + Scope đi phê duyệt (DRAFT/REJECTED -> PENDING_APPROVAL) */
    public function submitForApproval($id): void
    {
        RoleMiddleware::require(self::MANAGE_ROLES);
        Csrf::verify($this->input('csrf_token', ''));
        $id = (int) $id;

        $engagement = $this->engagementModel->find($id);
        if (!$engagement || !in_array($engagement['status'], ['DRAFT', 'REJECTED'], true)) {
            $this->flash('danger', 'Engagement không ở trạng thái có thể gửi phê duyệt.');
            $this->redirect('engagement/show/' . $id);
        }

        if ($this->engagementModel->countActiveScope($id) === 0) {
            $this->flash('danger', 'Vui lòng thêm ít nhất 1 Scope Item trước khi gửi phê duyệt.');
            $this->redirect('engagement/show/' . $id);
        }

        $this->engagementModel->updateStatus($id, 'PENDING_APPROVAL');

        AuditHelper::log($this->currentUser()['user_id'], 'SUBMIT_ENGAGEMENT_FOR_APPROVAL', 'ENGAGEMENT', $id,
            'Gửi phê duyệt Engagement #' . $id);

        $this->flash('success', 'Đã gửi Engagement đi phê duyệt.');
        $this->redirect('engagement/show/' . $id);
    }

    /** Validation phía server dùng chung cho store() và update() */
    private function validate(?string $name, ?string $department, ?string $testType, ?string $start, ?string $end): array
    {
        $errors = [];

        if (empty($name)) $errors[] = 'Tên Engagement là bắt buộc.';
        if (empty($department)) $errors[] = 'Phòng ban là bắt buộc.';
        if (!in_array($testType, EngagementModel::TEST_TYPES, true)) $errors[] = 'Loại kiểm thử không hợp lệ.';
        if (empty($start) || empty($end)) {
            $errors[] = 'Ngày bắt đầu/kết thúc là bắt buộc.';
        } elseif (strtotime($end) < strtotime($start)) {
            $errors[] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
        }

        return $errors;
    }
}
