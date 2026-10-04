<?php

class FindingController extends Controller
{
    private FindingModel $findingModel;
    private EngagementModel $engagementModel;
    private ScopeModel $scopeModel;
    private AssignmentModel $assignmentModel;
    private FindingReviewModel $findingReviewModel;

    public function __construct()
    {
        $this->findingModel = new FindingModel();
        $this->engagementModel = new EngagementModel();
        $this->scopeModel = new ScopeModel();
        $this->assignmentModel = new AssignmentModel();
        $this->findingReviewModel = new FindingReviewModel();
    }

    /**
     * Danh sách Finding theo quyền người dùng.
     */
    public function index(): void
    {
        RoleMiddleware::require([
            'PROJECT_MANAGER',
            'PENTESTER',
            'APPROVER',
        ]);

        $user = $this->currentUser();
        $userId = (int) $user['user_id'];

        switch ($user['role_code']) {
            case 'PENTESTER':
                $findings = $this->findingModel
                    ->findByAssignedPentester($userId);
                break;

            case 'PROJECT_MANAGER':
                $findings = $this->findingModel
                    ->findByProjectManager($userId);
                break;

            case 'APPROVER':
                $findings = array_values(array_filter(
                    $this->findingModel->all(),
                    static function (array $finding): bool {
                        return
                            in_array(
                                $finding['severity'],
                                ['CRITICAL', 'HIGH'],
                                true
                            )
                            && $finding['status'] !== 'DRAFT';
                    }
                ));
                break;

            default:
                $findings = [];
        }

        $this->render('finding/index', [
            'pageTitle' => 'Findings',
            'findings' => $findings,
        ]);
    }

    /**
     * Chi tiết Finding.
     */
    public function show($id): void
    {
        RoleMiddleware::require([
            'PROJECT_MANAGER',
            'PENTESTER',
            'APPROVER',
        ]);

        $id = (int) $id;
        $finding = $this->findingModel->find($id);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('finding/index');
        }

        $user = $this->currentUser();
        $userId = (int) $user['user_id'];
        $role = $user['role_code'];

        /*
         * Quy tắc quan trọng:
         * DRAFT chỉ Pentester tạo Finding được xem.
         */
        if ($finding['status'] === 'DRAFT') {
            if (
                $role !== 'PENTESTER'
                || (int) $finding['reported_by'] !== $userId
            ) {
                $this->flash(
                    'danger',
                    'Bạn không có quyền xem bản nháp Finding này.'
                );

                $this->redirect('finding/index');
            }

            $this->assertAssignedPentester(
                (int) $finding['engagement_id'],
                $userId
            );
        } else {
            switch ($role) {
                case 'PENTESTER':
                    $this->assertAssignedPentester(
                        (int) $finding['engagement_id'],
                        $userId
                    );
                    break;

                case 'PROJECT_MANAGER':
                    $this->assertProjectManagerOwnsFinding(
                        $finding,
                        $userId
                    );
                    break;

                case 'APPROVER':
                    if (!in_array(
                        $finding['severity'],
                        ['CRITICAL', 'HIGH'],
                        true
                    )) {
                        $this->flash(
                            'danger',
                            'Bạn không có quyền xem Finding này.'
                        );

                        $this->redirect('finding/index');
                    }
                    break;
            }
        }

        $engagement = $this->engagementModel->find(
            (int) $finding['engagement_id']
        );

        $scope = null;

        if (!empty($finding['scope_id'])) {
            $scope = $this->scopeModel->find(
                (int) $finding['scope_id']
            );
        }

        $this->render('finding/show', [
            'pageTitle' => 'Finding #' . $id,
            'finding' => $finding,
            'engagement' => $engagement,
            'scope' => $scope,
            'reviews' => $this->findingReviewModel
                ->findByFinding($id),
            'currentRole' => $role,
        ]);
    }

    /**
     * Pentester tạo Finding.
     */
    public function store($engagementId): void
    {
        RoleMiddleware::require(['PENTESTER']);
        Csrf::verify($this->input('csrf_token', ''));

        $engagementId = (int) $engagementId;
        $userId = (int) $this->currentUser()['user_id'];

        $engagement = $this->engagementModel->find(
            $engagementId
        );

        if (!$engagement) {
            $this->flash(
                'danger',
                'Không tìm thấy Engagement.'
            );

            $this->redirect('engagement/index');
        }

        $this->assertAssignedPentester(
            $engagementId,
            $userId
        );

        if (!in_array(
            $engagement['status'],
            ['APPROVED', 'IN_PROGRESS'],
            true
        )) {
            $this->flash(
                'danger',
                'Engagement chưa ở trạng thái kiểm thử.'
            );

            $this->redirect(
                'engagement/show/' . $engagementId
            );
        }

        $data = $this->validateFindingInput(
            $engagementId
        );

        $findingId = $this->findingModel->insert([
            'engagement_id' => $engagementId,
            'scope_id' => $data['scope_id'],
            'reported_by' => $userId,
            'title' => $data['title'],
            'description' => $data['description'],
            'recommendation' => $data['recommendation'],
            'severity' => $data['severity'],
            'status' => 'DRAFT',
            'cve' => $data['cve'],
            'cwe' => $data['cwe'],
            'owasp_category' => $data['owasp_category'],
        ]);

        AuditHelper::log(
            $userId,
            'CREATE_FINDING',
            'FINDING',
            $findingId,
            'Tạo Finding #' . $findingId .
            ' trong Engagement #' . $engagementId
        );

        $this->flash(
            'success',
            'Đã tạo Finding.'
        );

        $this->redirect(
            'engagement/show/' . $engagementId
        );
    }

    /**
     * Form sửa Finding.
     */
    public function edit($id): void
    {
        RoleMiddleware::require(['PENTESTER']);

        $id = (int) $id;
        $finding = $this->findingModel->find($id);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('engagement/index');
        }

        $userId = (int) $this->currentUser()['user_id'];

        $this->assertEditableFinding(
            $finding,
            $userId
        );

        $this->render('finding/form', [
            'pageTitle' => 'Sửa Finding',
            'breadcrumbs' => [
                'Engagement' => 'engagement/index',
                'Finding #' . $id => null,
            ],
            'finding' => $finding,
            'scopeItems' => $this->scopeModel
                ->findByEngagement(
                    (int) $finding['engagement_id']
                ),
            'severities' => FindingModel::SEVERITIES,
        ]);
    }

    /**
     * Cập nhật Finding.
     */
    public function update($id): void
    {
        RoleMiddleware::require(['PENTESTER']);
        Csrf::verify($this->input('csrf_token', ''));

        $id = (int) $id;
        $finding = $this->findingModel->find($id);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('engagement/index');
        }

        $userId = (int) $this->currentUser()['user_id'];

        $this->assertEditableFinding(
            $finding,
            $userId
        );

        $engagementId =
            (int) $finding['engagement_id'];

        $data = $this->validateFindingInput(
            $engagementId
        );

        $this->findingModel->update($id, [
            'scope_id' => $data['scope_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'recommendation' => $data['recommendation'],
            'severity' => $data['severity'],
            'cve' => $data['cve'],
            'cwe' => $data['cwe'],
            'owasp_category' => $data['owasp_category'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditHelper::log(
            $userId,
            'UPDATE_FINDING',
            'FINDING',
            $id,
            'Cập nhật Finding #' . $id .
            ' trong Engagement #' . $engagementId
        );

        $this->flash(
            'success',
            'Đã cập nhật Finding.'
        );

        $this->redirect(
            'engagement/show/' . $engagementId
        );
    }

    /**
     * Xóa DRAFT.
     */
    public function destroy($id): void
    {
        RoleMiddleware::require(['PENTESTER']);
        Csrf::verify($this->input('csrf_token', ''));

        $id = (int) $id;
        $finding = $this->findingModel->find($id);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('engagement/index');
        }

        $userId = (int) $this->currentUser()['user_id'];

        $this->assertEditableFinding(
            $finding,
            $userId
        );

        $engagementId =
            (int) $finding['engagement_id'];

        AuditHelper::log(
            $userId,
            'DELETE_FINDING',
            'FINDING',
            $id,
            'Xóa Finding #' . $id .
            ' trong Engagement #' . $engagementId
        );

        $this->findingModel->delete($id);

        $this->flash(
            'success',
            'Đã xóa Finding.'
        );

        $this->redirect(
            'engagement/show/' . $engagementId
        );
    }

    /**
     * DRAFT -> SUBMITTED
     */
    public function submit($id): void
    {
        RoleMiddleware::require(['PENTESTER']);
        Csrf::verify($this->input('csrf_token', ''));

        $id = (int) $id;
        $finding = $this->findingModel->find($id);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('engagement/index');
        }

        $userId = (int) $this->currentUser()['user_id'];

        $this->assertEditableFinding(
            $finding,
            $userId
        );

        $this->findingModel->update($id, [
            'status' => 'SUBMITTED',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditHelper::log(
            $userId,
            'SUBMIT_FINDING',
            'FINDING',
            $id,
            'Gửi Finding #' . $id .
            ' để PM xác nhận trong Engagement #' .
            $finding['engagement_id']
        );

        $this->flash(
            'success',
            'Finding đã được gửi cho Project Manager.'
        );

        $this->redirect(
            'engagement/show/' .
            $finding['engagement_id']
        );
    }

    /**
     * SUBMITTED -> CONFIRMED
     */
    public function confirm($id): void
    {
        RoleMiddleware::require([
            'PROJECT_MANAGER'
        ]);

        Csrf::verify(
            $this->input('csrf_token', '')
        );

        $id = (int) $id;
        $finding = $this->findingModel->find($id);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('engagement/index');
        }

        $userId =
            (int) $this->currentUser()['user_id'];

        $this->assertProjectManagerOwnsFinding(
            $finding,
            $userId
        );

        if ($finding['status'] !== 'SUBMITTED') {
            $this->flash(
                'danger',
                'Chỉ Finding SUBMITTED mới có thể được xác nhận.'
            );

            $this->redirect(
                'engagement/show/' .
                $finding['engagement_id']
            );
        }

        $this->findingModel->update($id, [
            'status' => 'CONFIRMED',
            'confirmed_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditHelper::log(
            $userId,
            'CONFIRM_FINDING',
            'FINDING',
            $id,
            'Project Manager xác nhận Finding #' .
            $id .
            ' trong Engagement #' .
            $finding['engagement_id']
        );

        $this->flash(
            'success',
            'Finding đã được xác nhận.'
        );

        $this->redirect(
            'engagement/show/' .
            $finding['engagement_id']
        );
    }

    /**
     * SUBMITTED -> FALSE_POSITIVE
     */
    public function falsePositive($id): void
    {
        RoleMiddleware::require([
            'PROJECT_MANAGER'
        ]);

        Csrf::verify(
            $this->input('csrf_token', '')
        );

        $id = (int) $id;
        $finding = $this->findingModel->find($id);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('engagement/index');
        }

        $userId =
            (int) $this->currentUser()['user_id'];

        $this->assertProjectManagerOwnsFinding(
            $finding,
            $userId
        );

        if ($finding['status'] !== 'SUBMITTED') {
            $this->flash(
                'danger',
                'Chỉ Finding SUBMITTED mới có thể đánh dấu False Positive.'
            );

            $this->redirect(
                'engagement/show/' .
                $finding['engagement_id']
            );
        }

        $this->findingModel->update($id, [
            'status' => 'FALSE_POSITIVE',
            'confirmed_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditHelper::log(
            $userId,
            'FALSE_POSITIVE_FINDING',
            'FINDING',
            $id,
            'Project Manager đánh dấu Finding #' .
            $id .
            ' là False Positive trong Engagement #' .
            $finding['engagement_id']
        );

        $this->flash(
            'success',
            'Finding đã được đánh dấu False Positive.'
        );

        $this->redirect(
            'engagement/show/' .
            $finding['engagement_id']
        );
    }

    /**
     * Approver Approve Critical/High.
     */
    public function approveReview($id): void
    {
        RoleMiddleware::require(['APPROVER']);

        Csrf::verify(
            $this->input('csrf_token', '')
        );

        $this->reviewFinding(
            (int) $id,
            'APPROVED'
        );
    }

    /**
     * Approver Reject Critical/High.
     */
    public function rejectReview($id): void
    {
        RoleMiddleware::require(['APPROVER']);

        Csrf::verify(
            $this->input('csrf_token', '')
        );

        $this->reviewFinding(
            (int) $id,
            'REJECTED'
        );
    }

    /**
     * Xử lý review độc lập.
     */
    private function reviewFinding(
        int $findingId,
        string $decision
    ): void {
        $finding = $this->findingModel
            ->find($findingId);

        if (!$finding) {
            $this->flash(
                'danger',
                'Không tìm thấy Finding.'
            );

            $this->redirect('finding/index');
        }

        if (!in_array(
            $decision,
            FindingReviewModel::DECISIONS,
            true
        )) {
            $this->flash(
                'danger',
                'Quyết định review không hợp lệ.'
            );

            $this->redirect(
                'finding/show/' . $findingId
            );
        }

        if (!in_array(
            $finding['severity'],
            ['CRITICAL', 'HIGH'],
            true
        )) {
            $this->flash(
                'danger',
                'Chỉ Finding CRITICAL/HIGH mới cần Approver review.'
            );

            $this->redirect(
                'finding/show/' . $findingId
            );
        }

        if ($finding['status'] !== 'CONFIRMED') {
            $this->flash(
                'danger',
                'Finding phải được Project Manager xác nhận trước khi Approver review.'
            );

            $this->redirect(
                'finding/show/' . $findingId
            );
        }

        $latestReview =
            $this->findingReviewModel
                ->findLatestByFinding($findingId);

        if (
            $latestReview
            && $latestReview['decision'] === 'APPROVED'
        ) {
            $this->flash(
                'danger',
                'Finding này đã được Approver chấp thuận.'
            );

            $this->redirect(
                'finding/show/' . $findingId
            );
        }

        $userId =
            (int) $this->currentUser()['user_id'];

        $comment = trim(
            (string) $this->input('comment', '')
        );

        $reviewId =
            $this->findingReviewModel->insert([
                'finding_id' => $findingId,
                'approver_id' => $userId,
                'decision' => $decision,
                'comment' => $comment !== ''
                    ? $comment
                    : null,
            ]);

        AuditHelper::log(
            $userId,
            'REVIEW_FINDING_' . $decision,
            'FINDING',
            $findingId,
            'Approver review Finding #' .
            $findingId .
            ': ' .
            $decision .
            ' (Review #' .
            $reviewId .
            ')'
        );

        $this->flash(
            'success',
            $decision === 'APPROVED'
                ? 'Đã chấp thuận Finding.'
                : 'Đã từ chối Finding.'
        );

        $this->redirect(
            'finding/show/' . $findingId
        );
    }

    /**
     * Kiểm tra Pentester được assign.
     */
    private function assertAssignedPentester(
        int $engagementId,
        int $userId
    ): void {
        if (
            !$this->assignmentModel
                ->isAlreadyAssigned(
                    $engagementId,
                    $userId
                )
        ) {
            $this->flash(
                'danger',
                'Bạn không được phân công vào Engagement này.'
            );

            $this->redirect(
                'engagement/show/' . $engagementId
            );
        }
    }

    /**
     * Chỉ chủ sở hữu DRAFT được sửa/xóa/submit.
     */
    private function assertEditableFinding(
        array $finding,
        int $userId
    ): void {
        $engagementId =
            (int) $finding['engagement_id'];

        $this->assertAssignedPentester(
            $engagementId,
            $userId
        );

        if (
            (int) $finding['reported_by']
            !== $userId
        ) {
            $this->flash(
                'danger',
                'Bạn không phải người tạo Finding này.'
            );

            $this->redirect(
                'engagement/show/' . $engagementId
            );
        }

        if ($finding['status'] !== 'DRAFT') {
            $this->flash(
                'danger',
                'Chỉ có thể sửa, xóa hoặc gửi Finding ở trạng thái DRAFT.'
            );

            $this->redirect(
                'engagement/show/' . $engagementId
            );
        }
    }

    /**
     * Kiểm tra PM quản lý Engagement.
     */
    private function assertProjectManagerOwnsFinding(
        array $finding,
        int $userId
    ): void {
        $engagementId =
            (int) $finding['engagement_id'];

        $engagement =
            $this->engagementModel->find(
                $engagementId
            );

        if (
            !$engagement
            || (int) $engagement['created_by']
                !== $userId
        ) {
            $this->flash(
                'danger',
                'Bạn không có quyền xử lý Finding của Engagement này.'
            );

            $this->redirect('finding/index');
        }
    }

    /**
     * Validate Create/Update.
     */
    private function validateFindingInput(
        int $engagementId
    ): array {
        $title = trim(
            (string) $this->input('title')
        );

        $description = trim(
            (string) $this->input('description')
        );

        $severity = strtoupper(
            trim(
                (string) $this->input('severity')
            )
        );

        if (
            $title === ''
            || $description === ''
            || !in_array(
                $severity,
                FindingModel::SEVERITIES,
                true
            )
        ) {
            $this->flash(
                'danger',
                'Title, Description và Severity không hợp lệ.'
            );

            $this->redirect(
                'engagement/show/' . $engagementId
            );
        }

        $scopeId =
            $this->input('scope_id');

        $scopeId = (
            $scopeId === null
            || $scopeId === ''
        )
            ? null
            : (int) $scopeId;

        if ($scopeId !== null) {
            $scope =
                $this->scopeModel->find($scopeId);

            if (
                !$scope
                || (int) $scope['engagement_id']
                    !== $engagementId
                || !empty($scope['is_excluded'])
            ) {
                $this->flash(
                    'danger',
                    'Scope Item không hợp lệ.'
                );

                $this->redirect(
                    'engagement/show/' .
                    $engagementId
                );
            }
        }

        return [
            'scope_id' => $scopeId,
            'title' => $title,
            'description' => $description,

            'recommendation' => trim(
                (string) $this->input(
                    'recommendation'
                )
            ),

            'severity' => $severity,

            'cve' => trim(
                (string) $this->input('cve')
            ) ?: null,

            'cwe' => trim(
                (string) $this->input('cwe')
            ) ?: null,

            'owasp_category' => trim(
                (string) $this->input(
                    'owasp_category'
                )
            ) ?: null,
        ];
    }
}