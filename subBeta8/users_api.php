<?php
include('mysql_config.php');

header('Content-Type: application/json');

// Allow activity tracking POSTs from signcollect subdomains (e.g. mocap.signcollect.nl)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin && preg_match('#^https://[a-z0-9-]+\.signcollect\.nl$#i', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}
$conn->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'list':
            listUsers();
            break;
        case 'add':
            requireAdmin();
            addUser();
            break;
        case 'update':
            requireAdmin();
            updateUser();
            break;
        case 'block':
            requireAdmin();
            toggleBlock();
            break;
        case 'delete':
            requireAdmin();
            deleteUser();
            break;
        case 'activity':
            updateActivity();
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

function requireAdmin() {
    global $conn;
    $requestingUserId = isset($_POST['requestingUserId']) ? intval($_POST['requestingUserId']) : 0;
    if ($requestingUserId <= 0) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $stmt = $conn->prepare("SELECT role FROM users WHERE userId = ?");
    $stmt->bind_param("i", $requestingUserId);
    $stmt->execute();
    $stmt->bind_result($role);
    if ($stmt->fetch()) {
        $stmt->close();
        if ($role !== 'admin') {
            echo json_encode(['error' => 'Unauthorized: admin role required']);
            exit;
        }
    } else {
        $stmt->close();
        echo json_encode(['error' => 'Unauthorized: user not found']);
        exit;
    }
}

function listUsers() {
    global $conn;
    $result = $conn->query("SELECT userId, user, lang, role, last_login, last_activity, last_page, blocked FROM users ORDER BY userId");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
}

function addUser() {
    global $conn;
    $user = isset($_POST['user']) ? $_POST['user'] : '';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';
    $lang = isset($_POST['lang']) ? $_POST['lang'] : 'nld';
    $role = isset($_POST['role']) ? $_POST['role'] : 'user';

    if (empty($user) || empty($pass)) {
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }

    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT userId FROM users WHERE user = ?");
    $checkStmt->bind_param("s", $user);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        echo json_encode(['error' => 'Username already exists']);
        return;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO users (user, pass, lang, role, last_login, blocked, logboek, tableCheck) VALUES (?, ?, ?, ?, NULL, 0, '', '')");
    $stmt->bind_param("ssss", $user, $pass, $lang, $role);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'userId' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Failed to add user: ' . $conn->error]);
    }
    $stmt->close();
}

function updateUser() {
    global $conn;
    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $user = isset($_POST['user']) ? $_POST['user'] : '';
    $lang = isset($_POST['lang']) ? $_POST['lang'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';

    if ($userId <= 0) {
        echo json_encode(['error' => 'Valid userId is required']);
        return;
    }

    $fields = [];
    $types = '';
    $values = [];

    if (!empty($user)) {
        $fields[] = "user = ?";
        $types .= 's';
        $values[] = $user;
    }
    if (!empty($lang)) {
        $fields[] = "lang = ?";
        $types .= 's';
        $values[] = $lang;
    }
    if (!empty($role)) {
        $fields[] = "role = ?";
        $types .= 's';
        $values[] = $role;
    }
    if (!empty($pass)) {
        $fields[] = "pass = ?";
        $types .= 's';
        $values[] = $pass;
    }

    if (empty($fields)) {
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE userId = ?";
    $types .= 'i';
    $values[] = $userId;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update user: ' . $conn->error]);
    }
    $stmt->close();
}

function toggleBlock() {
    global $conn;
    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;

    if ($userId <= 0) {
        echo json_encode(['error' => 'Valid userId is required']);
        return;
    }

    // Toggle the blocked status
    $stmt = $conn->prepare("UPDATE users SET blocked = IF(blocked = 1, 0, 1) WHERE userId = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Fetch new status
        $getStmt = $conn->prepare("SELECT blocked FROM users WHERE userId = ?");
        $getStmt->bind_param("i", $userId);
        $getStmt->execute();
        $getStmt->bind_result($newBlocked);
        $getStmt->fetch();
        $getStmt->close();
        echo json_encode(['success' => true, 'blocked' => $newBlocked]);
    } else {
        echo json_encode(['error' => 'Failed to toggle block: ' . $conn->error]);
    }
    $stmt->close();
}

function updateActivity() {
    global $conn;
    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
    $page = isset($_POST['page']) ? $_POST['page'] : '';

    if ($userId <= 0) {
        echo json_encode(['error' => 'Valid userId is required']);
        return;
    }

    // page column is VARCHAR(255) — hard-cap to avoid insert errors
    if (strlen($page) > 255) {
        $page = substr($page, 0, 255);
    }

    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW(), last_page = ? WHERE userId = ?");
    $stmt->bind_param("si", $page, $userId);
    $stmt->execute();
    $stmt->close();

    $logStmt = $conn->prepare("INSERT INTO activity_log (userId, page, visited_at) VALUES (?, ?, NOW())");
    $logStmt->bind_param("is", $userId, $page);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode(['success' => true]);
}

function deleteUser() {
    global $conn;
    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;

    if ($userId <= 0) {
        echo json_encode(['error' => 'Valid userId is required']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE userId = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete user: ' . $conn->error]);
    }
    $stmt->close();
}

$conn->close();
?>
