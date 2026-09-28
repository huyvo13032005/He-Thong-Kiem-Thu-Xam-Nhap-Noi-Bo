<?php
/**
 * app/models/UserModel.php
 * -----------------------------------------------------------------------
 * Model cho bảng `users`. Chịu trách nhiệm mọi câu SQL liên quan đến User:
 * đăng nhập, CRUD, tìm kiếm/lọc/phân trang cho trang User Management.
 * -----------------------------------------------------------------------
 */

class UserModel extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'user_id';

    /** Dùng cho đăng nhập: lấy user theo username kèm tên role (role_code) */
    public function findByUsername(string $username): ?array
    {
        return $this->queryOne(
            'SELECT u.*, r.role_code, r.role_name
             FROM users u JOIN roles r ON r.role_id = u.role_id
             WHERE u.username = :username',
            ['username' => $username]
        );
    }

    public function findWithRole(int $id): ?array
    {
        return $this->queryOne(
            'SELECT u.*, r.role_code, r.role_name
             FROM users u JOIN roles r ON r.role_id = u.role_id
             WHERE u.user_id = :id',
            ['id' => $id]
        );
    }

    /**
     * Danh sách user có search + filter + phân trang (dùng cho trang User Management).
     *
     * @return array{data: array, total: int}
     */
    public function paginate(string $search, string $roleFilter, string $statusFilter, int $page, int $perPage): array
    {
        $where = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[] = '(u.full_name LIKE :search1 OR u.username LIKE :search2 OR u.email LIKE :search3)';
            foreach (['search1', 'search2', 'search3'] as $key) {
                $params[$key] = '%' . $search . '%';
            }
        }
        if ($roleFilter !== '') {
            $where[] = 'r.role_code = :role';
            $params['role'] = $roleFilter;
        }
        if ($statusFilter !== '') {
            $where[] = 'u.status = :status';
            $params['status'] = $statusFilter;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) $this->queryOne(
            "SELECT COUNT(*) AS cnt FROM users u JOIN roles r ON r.role_id = u.role_id WHERE {$whereSql}",
            $params
        )['cnt'];

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT u.*, r.role_code, r.role_name
                FROM users u JOIN roles r ON r.role_id = u.role_id
                WHERE {$whereSql}
                ORDER BY u.created_at DESC
                OFFSET {$offset} ROWS FETCH NEXT {$perPage} ROWS ONLY";

        return ['data' => $this->query($sql, $params), 'total' => $total];
    }

    public function usernameOrEmailExists(string $username, string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS cnt FROM users WHERE (username = :username OR email = :email)';
        $params = ['username' => $username, 'email' => $email];
        if ($excludeId !== null) {
            $sql .= ' AND user_id != :id';
            $params['id'] = $excludeId;
        }
        return (int) $this->queryOne($sql, $params)['cnt'] > 0;
    }

    public function updateLastLogin(int $id): void
    {
        $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    public function allRoles(): array
    {
        return $this->query('SELECT * FROM roles ORDER BY role_id');
    }

    public function countAll(): int
    {
        return (int) $this->queryOne('SELECT COUNT(*) AS cnt FROM users')['cnt'];
    }
}
