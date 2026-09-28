<?php
/**
 * app/controllers/ApprovalController.php
 * -----------------------------------------------------------------------
 * Module ENGAGEMENT APPROVAL (Phase 2). CHỈ APPROVER được truy cập.
 *
 * Route:
 *   GET  /index.php?url=approval/index            -> danh sách Engagement PENDING_APPROVAL
 *   POST /index.php?url=approval/approve/{id}     -> phê duyệt (+ comment)
 *   POST /index.php?url=approval/reject/{id}      -> từ chối (+ comment bắt buộc)
 * -----------------------------------------------------------------------
 */

class ApprovalController extends Controller
{
    private ApprovalModel $approvalModel;
    private EngagementModel $engagementModel;
    private ScopeModel $scopeModel;

    public function __construct()
    {
        $this->approvalModel = new ApprovalModel();
        $this->engagementModel = new EngagementModel();
        $this->scopeModel = new ScopeModel();
    }

    public function index(): void
    {
        RoleMiddleware::require(['APPROVER']);

        $department = trim($_GET['department'] ?? '');

        $this->render('approval/index', [
            'pageTitle'   => 'Chờ phê duyệt',
            'breadcrumbs' => ['Chờ phê duyệt' => null],
            'pending'     => $this->approvalModel->pendingEngagements($department),
            'departments' => $this->engagementModel->distinctDepartments(),
            'deptFilter'  => $department,
        ]);
    }

    public function approve($id): void
    {
        RoleMiddleware::require(['APPROVER']);
        Csrf::verify($this->input('csrf_token', ''));
        $id = (int) $id;

        $engagement = $this->engagementModel->find($id);
        if (!$engagement || $engagement['status'] !== 'PENDING_APPROVAL') {
            $this->flash('danger', 'Engagement không ở trạng thái chờ phê duyệt.');
            $this->redirect('approval/index');
        }

        $comment = $this->input('comment', '');

        $this->engagementModel->updateStatus($id, 'APPROVED');
        $this->scopeModel->setLockedForEngagement($id, true); // khóa Scope sau khi Approve

        $this->approvalModel->recordDecision($id, $this->currentUser()['user_id'], 'APPROVED', $comment);

        AuditHelper::log($this->currentUser()['user_id'], 'APPROVE_ENGAGEMENT', 'ENGAGEMENT', $id,
            'Phê duyệt Engagement #' . $id . (empty($comment) ? '' : " - Comment: {$comment}"));

        $this->flash('success', 'Đã phê duyệt Engagement. Scope đã được khóa.');
        $this->redirect('approval/index');
    }

    public function reject($id): void
    {
        RoleMiddleware::require(['APPROVER']);
        Csrf::verify($this->input('csrf_token', ''));
        $id = (int) $id;

        $engagement = $this->engagementModel->find($id);
        if (!$engagement || $engagement['status'] !== 'PENDING_APPROVAL') {
            $this->flash('danger', 'Engagement không ở trạng thái chờ phê duyệt.');
            $this->redirect('approval/index');
        }

        $comment = $this->input('comment', '');
        if (empty($comment)) {
            $this->flash('danger', 'Vui lòng nhập lý do từ chối.');
            $this->redirect('approval/index');
        }

        $this->engagementModel->updateStatus($id, 'REJECTED');
        // Scope KHÔNG bị khóa khi Reject - Project Manager cần sửa lại và gửi duyệt lại
        $this->scopeModel->setLockedForEngagement($id, false);

        $this->approvalModel->recordDecision($id, $this->currentUser()['user_id'], 'REJECTED', $comment);

        AuditHelper::log($this->currentUser()['user_id'], 'REJECT_ENGAGEMENT', 'ENGAGEMENT', $id,
            "Từ chối Engagement #{$id} - Lý do: {$comment}");

        $this->flash('warning', 'Đã từ chối Engagement.');
        $this->redirect('approval/index');
    }
}
