<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check security clearance level
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Support_Admin', 'Super_Admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Access denied."]);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'c2c_platform');

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection error."]);
    exit();
}

//  Fetch live metrics totals for the cards and Chart.js graph
$total = $conn->query("SELECT COUNT(*) as total FROM disputes")->fetch_assoc()['total'] ?? 0;
$open = $conn->query("SELECT COUNT(*) as total FROM disputes WHERE status = 'Open'")->fetch_assoc()['total'] ?? 0;
$escrow = $conn->query("SELECT COUNT(*) as total FROM disputes WHERE urgency = 'High' AND status = 'Open'")->fetch_assoc()['total'] ?? 0;
$resolved = $conn->query("SELECT COUNT(*) as total FROM disputes WHERE status = 'Resolved'")->fetch_assoc()['total'] ?? 0;

$response = [
    "success" => true,
    "metrics" => [
        "total" => (int)$total,
        "open" => (int)$open,
        "escrow" => (int)$escrow,
        "resolved" => (int)$resolved
    ],
    "tickets" => []
];

// Fetch the complaints queue list (Joining users table to get name and email)
$query = "SELECT d.id, d.subject, d.message, d.urgency, d.status, u.username, u.email 
          FROM disputes d 
          JOIN users u ON d.user_id = u.id 
          ORDER BY d.status ASC, d.id DESC";

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response["tickets"][] = [
            "raw_id" => (int)$row['id'],
            "id" => "#TK-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
            "username" => htmlspecialchars($row['username']),
            "email" => htmlspecialchars($row['email']),
            "subject" => htmlspecialchars($row['subject']),
            "message" => htmlspecialchars($row['message']),
            "urgency" => ucfirst(strtolower($row['urgency'])), // High, Medium, Low
            "status" => ucfirst(strtolower($row['status']))   // Open, Resolved
        ];
    }
}

$conn->close();
echo json_encode($response);
?>