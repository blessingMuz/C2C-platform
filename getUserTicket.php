<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');

session_start();
require_once 'db.php';

$userId = $_SESSION['user_id'] ?? 2;

try {

    $stmt = $pdo->prepare("
        SELECT  d.id,
                CONCAT('#TK-', LPAD(d.id,4,'0'))  AS ticket_ref,
                d.subject,
                d.issue_type,
                d.status,
                d.order_id,
                d.message,
                DATE_FORMAT(d.created_at, '%M %d, %Y') AS date_logged,
                /* latest admin reply preview */
                (SELECT LEFT(dr.message, 80)
                 FROM   dispute_replies dr
                 WHERE  dr.dispute_id = d.id AND dr.sender_role = 'admin'
                 ORDER  BY dr.created_at DESC LIMIT 1)  AS last_admin_reply,
                /* unread admin replies count */
                (SELECT COUNT(*)
                 FROM   dispute_replies dr
                 WHERE  dr.dispute_id = d.id AND dr.sender_role = 'admin') AS reply_count
        FROM    disputes d
        WHERE   d.user_id = ?
        ORDER   BY d.created_at DESC
    ");
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'count'   => count($tickets),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}