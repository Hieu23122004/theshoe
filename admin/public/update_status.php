<?php
require_once "../../includes/database.php";
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('log_errors', 1);
try {
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Update status - Received data: " . json_encode($data));
    if (!isset($data['orderId']) || !isset($data['status'])) {
        throw new Exception('Missing required parameters');
    }
    $orderId = (int)$data['orderId'];
    $status = $data['status'];
    error_log("Update status - Order ID: $orderId, New Status: $status");
    $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status');
    }
    $checkStmt = $conn->prepare("SELECT order_id, status FROM orders WHERE order_id = ?");
    $checkStmt->bind_param("i", $orderId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Order not found');
    }
    $currentOrder = $result->fetch_assoc();
    $validTransitions = [
        'Pending' => ['Processing', 'Cancelled'],
        'Processing' => ['Shipped'],
        'Shipped' => ['Delivered'],
        'Delivered' => [],
        'Cancelled' => []
    ];
    if (!isset($validTransitions[$currentOrder['status']]) || 
        !in_array($status, $validTransitions[$currentOrder['status']])) {
        throw new Exception('Invalid status transition from ' . $currentOrder['status'] . ' to ' . $status);
    }
    error_log("Update status - Transition valid, updating database...");
    $conn->begin_transaction();
    
    // Nếu trạng thái mới là "Delivered", cần giảm stock_quantity của các sản phẩm
    if ($status === 'Delivered') {
        // Lấy thông tin đơn hàng để trừ stock
        $orderStmt = $conn->prepare("SELECT order_details FROM orders WHERE order_id = ?");
        $orderStmt->bind_param("i", $orderId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        
        if ($orderResult->num_rows > 0) {
            $orderData = $orderResult->fetch_assoc();
            $orderDetails = json_decode($orderData['order_details'], true);
            
            // Debug: Log toàn bộ order_details để kiểm tra
            error_log("Update status - Order details JSON: " . $orderData['order_details']);
            error_log("Update status - Parsed order details: " . json_encode($orderDetails));
            
            // Fallback: Nếu order_details không có đầy đủ thông tin, lấy từ database
            if (empty($orderDetails)) {
                error_log("Update status - Order details is empty, trying to get from user's cart or order data");
                // Có thể cần thêm logic lấy từ cart_items nếu cần thiết
            }
            
            // Cập nhật stock_quantity cho từng sản phẩm trong đơn hàng
            foreach ($orderDetails as $index => $item) {
                error_log("Update status - Processing item $index: " . json_encode($item));
                
                if (isset($item['product_id'])) {
                    $productId = (int)$item['product_id'];
                    
                    // Kiểm tra các khả năng về quantity
                    $quantity = 0;
                    if (isset($item['quantity'])) {
                        $quantity = (int)$item['quantity'];
                    } elseif (isset($item['qty'])) {
                        $quantity = (int)$item['qty'];
                    } elseif (isset($item['amount'])) {
                        $quantity = (int)$item['amount'];
                    }
                    
                    if ($quantity <= 0) {
                        error_log("Update status - Warning: No valid quantity found for product $productId. Item data: " . json_encode($item));
                        continue;
                    }
                    
                    $color = $item['color'] ?? '';
                    $size = $item['size'] ?? '';
                    
                    error_log("Update status - Item details - ProductID: $productId, Quantity: $quantity, Color: $color, Size: $size");
                    
                    // Lấy thông tin sản phẩm hiện tại
                    $checkStockStmt = $conn->prepare("SELECT stock_quantity, size_stock, name FROM products WHERE product_id = ?");
                    $checkStockStmt->bind_param("i", $productId);
                    $checkStockStmt->execute();
                    $stockResult = $checkStockStmt->get_result();
                    
                    if ($stockResult->num_rows > 0) {
                        $productData = $stockResult->fetch_assoc();
                        $currentStock = (int)$productData['stock_quantity'];
                        $sizeStock = json_decode($productData['size_stock'], true);
                        $productName = $productData['name'];
                        
                        // Cập nhật size_stock JSON nếu có màu và size cụ thể
                        if (!empty($color) && !empty($size) && is_array($sizeStock)) {
                            if (isset($sizeStock[$color][$size])) {
                                $currentColorSizeStock = (int)$sizeStock[$color][$size];
                                
                                // Log cảnh báo nếu stock của màu/size không đủ
                                if ($currentColorSizeStock < $quantity) {
                                    error_log("Update status - Warning: Product '$productName' (ID: $productId) Color: $color, Size: $size has insufficient stock. Current: $currentColorSizeStock, Required: $quantity. Stock will be set to 0.");
                                }
                                
                                error_log("Update status - Before update - Color: $color, Size: $size, Current stock: $currentColorSizeStock, Will subtract: $quantity");
                                
                                // Trừ số lượng cho màu/size cụ thể
                                $sizeStock[$color][$size] = max(0, $currentColorSizeStock - $quantity);
                                
                                error_log("Update status - After calculation - New stock for $color/$size: " . $sizeStock[$color][$size]);
                                
                                // Tính tổng stock mới từ tất cả màu/size
                                $newTotalStock = 0;
                                foreach ($sizeStock as $colorStocks) {
                                    if (is_array($colorStocks)) {
                                        foreach ($colorStocks as $sizeQty) {
                                            $newTotalStock += (int)$sizeQty;
                                        }
                                    }
                                }
                                
                                // Cập nhật cả size_stock JSON, stock_quantity tổng và sold_quantity
                                $newSizeStockJson = json_encode($sizeStock);
                                $updateStockStmt = $conn->prepare("UPDATE products SET stock_quantity = ?, size_stock = ?, sold_quantity = sold_quantity + ? WHERE product_id = ?");
                                $updateStockStmt->bind_param("isii", $newTotalStock, $newSizeStockJson, $quantity, $productId);
                                
                                if (!$updateStockStmt->execute()) {
                                    error_log("Update status - Failed to update stock for product $productId: " . $updateStockStmt->error);
                                    throw new Exception('Failed to update product stock: ' . $updateStockStmt->error);
                                }
                                
                                error_log("Update status - Updated stock for product $productId ($productName), Color: $color, Size: $size, reduced by $quantity. New total stock: $newTotalStock");
                                $updateStockStmt->close();
                            } else {
                                error_log("Update status - Warning: Product '$productName' (ID: $productId) does not have stock data for Color: $color, Size: $size");
                                
                                // Fallback: chỉ cập nhật stock_quantity tổng và sold_quantity
                                $updateStockStmt = $conn->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?), sold_quantity = sold_quantity + ? WHERE product_id = ?");
                                $updateStockStmt->bind_param("iii", $quantity, $quantity, $productId);
                                
                                if (!$updateStockStmt->execute()) {
                                    error_log("Update status - Failed to update stock for product $productId: " . $updateStockStmt->error);
                                    throw new Exception('Failed to update product stock: ' . $updateStockStmt->error);
                                }
                                
                                error_log("Update status - Updated total stock for product $productId, reduced by $quantity (fallback method)");
                                $updateStockStmt->close();
                            }
                        } else {
                            // Nếu không có thông tin màu/size hoặc size_stock không phải array, chỉ trừ stock_quantity tổng
                            $updateStockStmt = $conn->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?), sold_quantity = sold_quantity + ? WHERE product_id = ?");
                            $updateStockStmt->bind_param("iii", $quantity, $quantity, $productId);
                            
                            if (!$updateStockStmt->execute()) {
                                error_log("Update status - Failed to update stock for product $productId: " . $updateStockStmt->error);
                                throw new Exception('Failed to update product stock: ' . $updateStockStmt->error);
                            }
                            
                            error_log("Update status - Updated total stock for product $productId, reduced by $quantity (no color/size info)");
                            $updateStockStmt->close();
                        }
                    } else {
                        error_log("Update status - Warning: Product with ID $productId not found in database");
                    }
                    $checkStockStmt->close();
                } else {
                    error_log("Update status - Warning: Item missing product_id. Item data: " . json_encode($item));
                }
            }
        }
        $orderStmt->close();
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $orderId);
    if (!$stmt->execute()) {
        error_log("Update status - SQL Error: " . $stmt->error);
        throw new Exception('Failed to update order status: ' . $stmt->error);
    }
    $affected_rows = $stmt->affected_rows;
    error_log("Update status - Affected rows: " . $affected_rows);
    $conn->commit();
    error_log("Update status - Successfully updated order $orderId to status $status");
    echo json_encode([
        'success' => true, 
        'message' => 'Status updated successfully',
        'newStatus' => $status
    ]);
} catch (Exception $e) {
    error_log("Update status - Error: " . $e->getMessage());
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
