<?php
/**
 * app/controllers/ScopeController.php
 * -----------------------------------------------------------------------
 * Module SCOPE MANAGEMENT (Phase 2).
 * Scope Item luôn gắn với 1 Engagement cụ thể, được quản lý trực tiếp
 * từ tab "Scope" trong trang chi tiết Engagement (engagement/show).
 *
 * Chỉ PROJECT_MANAGER được thêm/sửa/xóa, và CHỈ khi Engagement đang ở
 * trạng thái DRAFT hoặc REJECTED (chưa gửi duyệt / bị từ chối và đang sửa lại).
 * Sau khi Approved, Scope bị khóa (is_locked = 1) - không cho sửa/xóa.
 *
 * Route:
 *   POST /index.php?url=scope/store/{engagementId}
 *   POST /index.php?url=scope/update/{id}
 *   POST /index.php?url=scope/destroy/{id}
 * -----------------------------------------------------------------------
 */

class ScopeController extends Controller
{
    private ScopeModel $scopeModel;
    private EngagementModel $engagementModel;

    public function __construct()
    {
        $this->scopeModel = new ScopeModel();
        $this->engagementModel = new EngagementModel();
    }

    public function store($engagementId): void
    {
        RoleMiddleware::require(['PROJECT_MANAGER']);
        Csrf::verify($this->input('csrf_token', ''));
        $engagementId = (int) $engagementId;

        $engagement = $this->assertEditable($engagementId);

        $target = $this->input('target');
        $targetType = $this->input('target_type');

        if (empty($target) || !in_array($targetType, ScopeModel::TARGET_TYPES, true)) {
            $this->flash('danger', 'Target và Target Type là bắt buộc / không hợp lệ.');
            $this->redirect('engagement/show/' . $engagementId);
        }

        $scopeId = $this->scopeModel->insert([
            'engagement_id' => $engagementId,
            'target'        => $target,
            'target_type'   => $targetType,
            'description'   => $this->input('description'),
            'is_excluded'   => $this->input('is_excluded') ? 1 : 0,
            'is_locked'     => 0,
        ]);

        AuditHelper::log($this->currentUser()['user_id'], 'ADD_SCOPE_ITEM', 'SCOPE_ITEM', $scopeId,
            "Thêm Scope Item '{$target}' vào Engagement #{$engagementId}");

        $this->flash('success', 'Đã thêm Scope Item.');
        $this->redirect('engagement/show/' . $engagementId);
    }

    public function update($id): void
    {
        RoleMiddleware::require(['PROJECT_MANAGER']);
        Csrf::verify($this->input('csrf_token', ''));
        $id = (int) $id;

        $scope = $this->scopeModel->find($id);
        if (!$scope) {
            $this->flash('danger', 'Không tìm thấy Scope Item.');
            $this->redirect('engagement/index');
        }

        $this->assertEditable((int) $scope['engagement_id']);

        $target = $this->input('target');
        $targetType = $this->input('target_type');
        if (empty($target) || !in_array($targetType, ScopeModel::TARGET_TYPES, true)) {
            $this->flash('danger', 'Target và Target Type là bắt buộc / không hợp lệ.');
            $this->redirect('engagement/show/' . $scope['engagement_id']);
        }

        $this->scopeModel->update($id, [
            'target'      => $target,
            'target_type' => $targetType,
            'description' => $this->input('description'),
            'is_excluded' => $this->input('is_excluded') ? 1 : 0,
        ]);

        AuditHelper::log($this->currentUser()['user_id'], 'UPDATE_SCOPE_ITEM', 'SCOPE_ITEM', $id,
            "Cập nhật Scope Item #{$id}");

        $this->flash('success', 'Đã cập nhật Scope Item.');
        $this->redirect('engagement/show/' . $scope['engagement_id']);
    }

    public function destroy($id): void
    {
        RoleMiddleware::require(['PROJECT_MANAGER']);
        $id = (int) $id;

        $scope = $this->scopeModel->find($id);
        if (!$scope) {
            $this->flash('danger', 'Không tìm thấy Scope Item.');
            $this->redirect('engagement/index');
        }

        $this->assertEditable((int) $scope['engagement_id']);
        $this->scopeModel->delete($id);

        AuditHelper::log($this->currentUser()['user_id'], 'DELETE_SCOPE_ITEM', 'SCOPE_ITEM', $id,
            "Xóa Scope Item #{$id}");

        $this->flash('success', 'Đã xóa Scope Item.');
        $this->redirect('engagement/show/' . $scope['engagement_id']);
    }

    /** Chặn thao tác nếu Engagement không ở trạng thái cho phép sửa Scope (DRAFT/REJECTED) hoặc đã bị khóa */
    private function assertEditable(int $engagementId): array
    {
        $engagement = $this->engagementModel->find($engagementId);
        if (!$engagement) {
            $this->flash('danger', 'Không tìm thấy Engagement.');
            $this->redirect('engagement/index');
        }
        if (!in_array($engagement['status'], ['DRAFT', 'REJECTED'], true)) {
            $this->flash('danger', 'Scope đã bị khóa - chỉ có thể sửa khi Engagement ở trạng thái Draft hoặc Rejected.');
            $this->redirect('engagement/show/' . $engagementId);
        }
        return $engagement;
    }
}
