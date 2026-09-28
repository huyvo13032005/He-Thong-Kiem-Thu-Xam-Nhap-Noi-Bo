<?php
/**
 * app/helpers/functions.php
 * -----------------------------------------------------------------------
 * Các hàm dùng chung trong VIEW (không thuộc về 1 class cụ thể nào).
 * Được nạp 1 lần trong public/index.php (require_once).
 * -----------------------------------------------------------------------
 */

/** Trả về class Bootstrap badge tương ứng với mức độ nghiêm trọng của Finding */
function severityBadgeClass(string $severity): string
{
    return match (strtoupper($severity)) {
        'CRITICAL' => 'badge-severity-critical', // đỏ
        'HIGH'     => 'badge-severity-high',     // cam
        'MEDIUM'   => 'badge-severity-medium',   // vàng
        'LOW'      => 'badge-severity-low',      // xanh
        default    => 'badge-severity-info',     // xám
    };
}

/** Trả về class Bootstrap badge tương ứng với trạng thái Finding/Engagement */
function statusBadgeClass(string $status): string
{
    return match (strtoupper($status)) {
        'DRAFT'            => 'bg-secondary',
        'SUBMITTED', 'PENDING_APPROVAL' => 'bg-info text-dark',
        'CONFIRMED', 'APPROVED'         => 'bg-primary',
        'REMEDIATION', 'IN_PROGRESS'    => 'bg-warning text-dark',
        'READY_FOR_RETEST'              => 'bg-warning text-dark',
        'REOPENED', 'REJECTED', 'FAILED_RETEST' => 'bg-danger',
        'CLOSED', 'FIXED'                => 'bg-success',
        'FALSE_POSITIVE'                 => 'bg-secondary',
        default                          => 'bg-secondary',
    };
}

/** Định dạng ngày giờ kiểu Việt Nam: dd/mm/YYYY HH:ii */
function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) return '-';
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y H:i', $ts) : '-';
}

function formatDate(?string $date): string
{
    if (empty($date)) return '-';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : '-';
}

/** Escape output chống XSS - dùng thay cho echo trực tiếp trong view */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Lấy flash message và xóa khỏi session (hiện 1 lần duy nhất) */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Tên hiển thị tiếng Việt cho role_code */
function roleLabel(string $roleCode): string
{
    return match ($roleCode) {
        'ADMIN'           => 'Admin',
        'PROJECT_MANAGER' => 'Project Manager',
        'APPROVER'        => 'Approver',
        'PENTESTER'       => 'Pentester',
        'ASSET_OWNER'     => 'Asset Owner',
        'STAKEHOLDER'     => 'Stakeholder',
        default           => $roleCode,
    };
}

function formatFileSize(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
