<?php
/**
 * API Endpoint for Chart Data
 * Returns JSON data for dynamic Chart.js updates
 * Prevents SQL injection with prepared statements
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get optional filters
$period = isset($_GET['period']) ? (int)$_GET['period'] : null;
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$productGroupId = isset($_GET['product_group_id']) ? (int)$_GET['product_group_id'] : null;

try {
    // Sales by Period/Year (for bar chart)
    $sql = "SELECT s.period, s.year, SUM(s.amount) as total_amount 
            FROM sales s 
            JOIN products p ON s.product_id = p.id 
            WHERE 1=1";
    $params = [];
    
    if ($period) {
        $sql .= " AND s.period = ?";
        $params[] = $period;
    }
    if ($productId) {
        $sql .= " AND s.product_id = ?";
        $params[] = $productId;
    }
    if ($productGroupId) {
        $sql .= " AND p.product_group_id = ?";
        $params[] = $productGroupId;
    }
    
    $sql .= " GROUP BY s.period, s.year ORDER BY s.year, s.period";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $salesByPeriod = $stmt->fetchAll();
    
    // Sales by Product Group (for pie chart)
    $sql2 = "SELECT pg.name, SUM(s.amount) as total_amount 
             FROM sales s 
             JOIN products p ON s.product_id = p.id 
             JOIN product_groups pg ON p.product_group_id = pg.id 
             WHERE 1=1";
    $params2 = [];
    if ($period) {
        $sql2 .= " AND s.period = ?";
        $params2[] = $period;
    }
    if ($productGroupId) {
        $sql2 .= " AND p.product_group_id = ?";
        $params2[] = $productGroupId;
    }
    $sql2 .= " GROUP BY pg.id, pg.name";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute($params2);
    $salesByGroup = $stmt2->fetchAll();
    
    // Summary stats
    $sql3 = "SELECT 
                COUNT(DISTINCT s.id) as total_records,
                COALESCE(SUM(s.amount), 0) as total_sales,
                COALESCE(SUM(s.quantity), 0) as total_quantity
             FROM sales s
             JOIN products p ON s.product_id = p.id
             WHERE 1=1";
    $params3 = [];
    if ($period) {
        $sql3 .= " AND s.period = ?";
        $params3[] = $period;
    }
    if ($productId) {
        $sql3 .= " AND s.product_id = ?";
        $params3[] = $productId;
    }
    if ($productGroupId) {
        $sql3 .= " AND p.product_group_id = ?";
        $params3[] = $productGroupId;
    }
    
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute($params3);
    $summary = $stmt3->fetch();
    
    // Get filter options
    $periods = $pdo->query("SELECT DISTINCT period FROM sales ORDER BY period")->fetchAll(PDO::FETCH_COLUMN);
    $productGroups = $pdo->query("SELECT id, name FROM product_groups ORDER BY name")->fetchAll();
    $products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'salesByPeriod' => $salesByPeriod,
        'salesByGroup' => $salesByGroup,
        'summary' => $summary,
        'filters' => [
            'periods' => $periods,
            'productGroups' => $productGroups,
            'products' => $products
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Chart API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch chart data']);
}
