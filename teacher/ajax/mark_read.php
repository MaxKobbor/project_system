<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['group_id']) && !isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$id = intval($_GET['id'] ?? 0);
$all = intval($_GET['all'] ?? 0);
$type = $_GET['type'] ?? '';

try {
    if ($all === 1) {
        if ($type === 'student' && isset($_SESSION['group_id'])) {
            $stmt = $pdo->prepare("UPDATE `notifications` SET is_read = 1 WHERE type = 'student' AND group_id = ?");
            $stmt->execute([$_SESSION['group_id']]);
        } else if ($type === 'teacher' && isset($_SESSION['teacher_id'])) {
            $stmt = $pdo->prepare("UPDATE `notifications` SET is_read = 1 WHERE type = 'teacher' AND teacher_id = ?");
            $stmt->execute([$_SESSION['teacher_id']]);
        }
    } else if ($id > 0) {
        // Just mark one as read
        $stmt = $pdo->prepare("UPDATE `notifications` SET is_read = 1 WHERE notification_id = ?");
        $stmt->execute([$id]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
