<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['has_new' => false]);
    exit();
}

$latest_created_at = $_GET['latest_created_at'] ?? '';
$status_filter = $_GET['status'] ?? '';

$allowed_statuses = ['open', 'in_progress', 'completed'];

if ($latest_created_at === '') {
    echo json_encode(['has_new' => false]);
    exit();
}

$sql = "
    SELECT COUNT(*) AS new_count
    FROM Projects
    WHERE created_at > ?
";

$params = [$latest_created_at];
$types = "s";

if ($status_filter !== '' && in_array($status_filter, $allowed_statuses, true)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['has_new' => false]);
    exit();
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode([
    'has_new' => ((int)($row['new_count'] ?? 0) > 0)
]);
?>