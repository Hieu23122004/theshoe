<?php
include '../../includes/database.php';
header('Content-Type: application/json');
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data->review_id) || empty($data->review_id)) {
        throw new Exception('Review ID is required');
    }
    $review_id = intval($data->review_id);
    $checkStmt = $conn->prepare("SELECT review_id FROM product_reviews WHERE review_id = ?");
    $checkStmt->bind_param("i", $review_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Review not found');
    }
    $stmt = $conn->prepare("DELETE FROM product_reviews WHERE review_id = ?");
    $stmt->bind_param("i", $review_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete review: ' . $stmt->error);
    }
    echo json_encode([
        'success' => true,
        'message' => 'Review deleted successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
