<?php
/**
 * app/controllers/AssignmentController.php
 * -----------------------------------------------------------------------
 * Module PENTESTER ASSIGNMENT (Phase 2).
 * Chỉ PROJECT_MANAGER được phân công, và CHỈ khi Engagement đã ở trạng
 * thái APPROVED hoặc IN_PROGRESS (phải qua phê duyệt trước khi giao việc).
 * Hỗ trợ multi-select (nhiều Pentester cùng lúc), mỗi người có
 * priority + deadline riêng.
 *
 * Route:
 *   POST /index.php?url=assignment/store/{engagementId}
 *   POST /index.php?url=assignment/destroy/{id}
 * -----------------------------------------------------------------------
 */

class AssignmentController extends Controller
{
    private AssignmentModel $assignmentModel;
    private EngagementModel $engagementModel;

    public function __construct()
    {
        $this->assignmentModel = new AssignmentModel();
        $this->engagementModel = new EngagementModel();
    }

    public function store($engagementId): void
    {
        RoleMiddleware::require(['PROJECT_MANAGER']);
        Csrf::verify($this->input('csrf_token', ''));
        $engagementId = (int) $engagementId;

        $engagement = $this->engagementModel->find($engagementId);
        if (!$engagement) {
            $this->flash('danger', 'Không tìm thấy Engagement.');
            $this->redirect('engagement/index');
        }
        if (!in_array($engagement['status'], ['APPROVED', 'IN_PROGRESS'], true)) {
            $this->flash('danger', 'Chỉ có thể phân công Pentester khi Engagement đã được Approved.');
            $this->redirect('engagement/show/' . $engagementId);
        }

        $pentesterIds = $_POST['pentester_ids'] ?? []; // multi-select -> mảng user_id
        $priority = $this->input('priority', 'MEDIUM');
        $deadline = $this->input('deadline') ?: null;

        if (empty($pentesterIds) || !is_array($pentesterIds)) {
            $this->flash('danger', 'Vui lòng chọn ít nhất 1 Pentester.');
            $this->redirect('engagement/show/' . $engagementId);
        }
        if (!in_array($priority, AssignmentModel::PRIORITIES, true)) {
            $priority = 'MEDIUM';
        }

        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($pentesterIds as $pentesterId) {
            $pentesterId = (int) $pentesterId;
            if ($pentesterId <= 0) continue;

            if ($this->assignmentModel->isAlreadyAssigned($engagementId, $pentesterId)) {
                $skippedCount++;
                continue; // tránh trùng lặp (đã có UNIQUE constraint ở DB làm lớp bảo vệ thứ 2)
            }

            $assignmentId = $this->assignmentModel->insert([
                'engagement_id' => $engagementId,
                'pentester_id'  => $pentesterId,
                'assigned_by'   => $this->currentUser()['user_id'],
                'priority'      => $priority,
                'deadline'      => $deadline,
            ]);

            AuditHelper::log($this->currentUser()['user_id'], 'ASSIGN_PENTESTER', 'ASSIGNMENT', $assignmentId,
                "Phân công Pentester #{$pentesterId} vào Engagement #{$engagementId}");

            $assignedCount++;
        }

        // Khi đã có Pentester được phân công, Engagement chuyển sang IN_PROGRESS (nếu đang APPROVED)
        if ($assignedCount > 0 && $engagement['status'] === 'APPROVED') {
            $this->engagementModel->updateStatus($engagementId, 'IN_PROGRESS');
        }

        if ($assignedCount > 0) {
            $msg = "Đã phân công {$assignedCount} Pentester.";
            if ($skippedCount > 0) $msg .= " ({$skippedCount} người đã được phân công từ trước, bỏ qua.)";
            $this->flash('success', $msg);
        } else {
            $this->flash('warning', 'Không có Pentester mới nào được phân công (có thể đã được phân công từ trước).');
        }

        $this->redirect('engagement/show/' . $engagementId);
    }

    public function destroy($id): void
    {
        RoleMiddleware::require(['PROJECT_MANAGER']);
        $id = (int) $id;

        $stmt = Database::getConnection()->prepare('SELECT * FROM pentester_assignments WHERE assignment_id = :id');
        $stmt->execute(['id' => $id]);
        $assignment = $stmt->fetch();

        if (!$assignment) {
            $this->flash('danger', 'Không tìm thấy phân công.');
            $this->redirect('engagement/index');
        }

        $this->assignmentModel->delete($id);

        AuditHelper::log($this->currentUser()['user_id'], 'REMOVE_ASSIGNMENT', 'ASSIGNMENT', $id,
            "Gỡ phân công #{$id} khỏi Engagement #{$assignment['engagement_id']}");

        $this->flash('success', 'Đã gỡ phân công Pentester.');
        $this->redirect('engagement/show/' . $assignment['engagement_id']);
    }
}
