<?php
/**
 * app/controllers/UserController.php
 * -----------------------------------------------------------------------
 * Module "User Management" - CHỈ ADMIN được truy cập (RoleMiddleware::require).
 * Route:
 *   GET  /index.php?url=user/index                 -> danh sách (search/filter/phân trang)
 *   POST /index.php?url=user/store                  -> tạo user mới
 *   POST /index.php?url=user/update/{id}             -> cập nhật user
 *   POST /index.php?url=user/toggleStatus/{id}       -> khóa/mở khóa tài khoản
 *   POST /index.php?url=user/destroy/{id}            -> xóa user
 * -----------------------------------------------------------------------
 */

class UserController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        RoleMiddleware::require(['ADMIN']);

        $search = trim($_GET['search'] ?? '');
        $role   = trim($_GET['role'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $page   = max((int)($_GET['page'] ?? 1), 1);
        $perPage = 10;

        $result = $this->userModel->paginate($search, $role, $status, $page, $perPage);

        $this->render('users/index', [
            'pageTitle'  => 'Quản lý người dùng',
            'breadcrumbs'=> ['Người dùng' => null],
            'users'      => $result['data'],
            'total'      => $result['total'],
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($result['total'] / $perPage),
            'roles'      => $this->userModel->allRoles(),
            'search'     => $search,
            'roleFilter' => $role,
            'statusFilter' => $status,
        ]);
    }

    public function store(): void
    {
        RoleMiddleware::require(['ADMIN']);
        Csrf::verify($this->input('csrf_token', ''));

        $fullName = $this->input('full_name');
        $username = $this->input('username');
        $email    = $this->input('email');
        $password = $this->input('password');
        $roleId   = (int) $this->input('role_id');
        $department = $this->input('department');

        if (!$fullName || !$username || !$email || !$password || !$roleId) {
            $this->flash('danger', 'Vui lòng nhập đầy đủ thông tin bắt buộc.');
            $this->redirect('user/index');
        }

        if ($this->userModel->usernameOrEmailExists($username, $email)) {
            $this->flash('danger', 'Username hoặc Email đã tồn tại.');
            $this->redirect('user/index');
        }

        $userId = $this->userModel->insert([
            'full_name'     => $fullName,
            'username'      => $username,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT), // KHÔNG BAO GIỜ lưu plaintext
            'role_id'       => $roleId,
            'department'    => $department,
            'status'        => 'ACTIVE',
        ]);

        AuditHelper::log($this->currentUser()['user_id'], 'CREATE_USER', 'USER', $userId, "Tạo user mới: {$username}");
        $this->flash('success', 'Tạo người dùng thành công.');
        $this->redirect('user/index');
    }

    public function update($id): void
    {
        RoleMiddleware::require(['ADMIN']);
        Csrf::verify($this->input('csrf_token', ''));
        $id = (int) $id;

        $data = [
            'full_name'  => $this->input('full_name'),
            'email'      => $this->input('email'),
            'role_id'    => (int) $this->input('role_id'),
            'department' => $this->input('department'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $password = $this->input('password');
        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->userModel->update($id, $data);
        AuditHelper::log($this->currentUser()['user_id'], 'UPDATE_USER', 'USER', $id, "Cập nhật user #{$id}");
        $this->flash('success', 'Cập nhật người dùng thành công.');
        $this->redirect('user/index');
    }

    public function toggleStatus($id): void
    {
        RoleMiddleware::require(['ADMIN']);
        $id = (int) $id;
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->flash('danger', 'Không tìm thấy người dùng.');
            $this->redirect('user/index');
        }

        // Không cho Admin tự khóa chính mình (tránh tự khóa mất quyền truy cập)
        if ($id === $this->currentUser()['user_id']) {
            $this->flash('warning', 'Bạn không thể tự khóa tài khoản của chính mình.');
            $this->redirect('user/index');
        }

        $newStatus = $user['status'] === 'ACTIVE' ? 'LOCKED' : 'ACTIVE';
        $this->userModel->update($id, ['status' => $newStatus]);

        AuditHelper::log($this->currentUser()['user_id'], 'TOGGLE_USER_STATUS', 'USER', $id, "Đổi trạng thái user #{$id} -> {$newStatus}");
        $this->flash('success', "Đã đổi trạng thái tài khoản sang {$newStatus}.");
        $this->redirect('user/index');
    }

    public function destroy($id): void
    {
        RoleMiddleware::require(['ADMIN']);
        $id = (int) $id;

        if ($id === $this->currentUser()['user_id']) {
            $this->flash('warning', 'Bạn không thể xóa chính mình.');
            $this->redirect('user/index');
        }

        $this->userModel->delete($id);
        AuditHelper::log($this->currentUser()['user_id'], 'DELETE_USER', 'USER', $id, "Xóa user #{$id}");
        $this->flash('success', 'Đã xóa người dùng.');
        $this->redirect('user/index');
    }
}
