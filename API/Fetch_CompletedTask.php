<?php
include '../php/database.php';
session_start();

$data = [];

try {
    // Query dasar
    $query = "SELECT id, order_number, site_id, site_name, customer, destination, created_at, status 
              FROM tasks 
              WHERE deleted_at IS NULL AND status = 'completed'";
    
    $params = [];
    $types = "";

    // Tambahkan kondisi WHERE berdasarkan role user
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] !== 'superadmin') {
            // Untuk admin, user, dan customer, filter berdasarkan wh_name
            if (isset($_SESSION['wh_name'])) {
                $query .= " AND wh_name = ?";
                $params[] = $_SESSION['wh_name'];
                $types .= "s";
                error_log("Fetch_CompletedTask - Filtering by wh_name: " . $_SESSION['wh_name']);
            } else {
                error_log("Fetch_CompletedTask - No wh_name in session for role: " . $_SESSION['role']);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'No warehouse name defined for this user.']);
                exit();
            }
        }
        // Superadmin tidak perlu filter tambahan
    } else {
        error_log("Fetch_CompletedTask - No role in session");
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User role not defined.']);
        exit();
    }

    // Selesaikan query dengan pengurutan
    $query .= " ORDER BY created_at ASC";

    error_log("Fetch_CompletedTask - Query executed: " . $query);

    // Siapkan dan jalankan query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("Fetch_CompletedTask - Number of rows returned: " . $result->num_rows);

    while ($row = $result->fetch_assoc()) {
        // Debugging: Log nilai destination
        error_log("Fetch_CompletedTask - Order Number: {$row['order_number']}, Destination: " . ($row['destination'] !== '' ? $row['destination'] : 'EMPTY'));
        $data[] = $row;
    }

    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Error in Fetch_CompletedTask: " . $e->getMessage() . " | Query: " . $query);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage()]);
    exit();
}

header('Content-Type: application/json');
echo json_encode($data);
exit();
?>