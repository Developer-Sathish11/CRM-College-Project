<?php
// config/session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SessionManager {
    private $db;
    private $conn;

    public function __construct($database) {
        $this->db = $database;
        $this->conn = $database->getConnection();
    }

    public function createUserSession($userId, $userType, $userName, $email) {
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        // Store session in database
        $stmt = $this->conn->prepare("INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $sessionId, $userId, $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();

        // Record login history
        $stmt = $this->conn->prepare("INSERT INTO login_history (user_id, ip_address, user_agent, session_id, status) VALUES (?, ?, ?, ?, 'success')");
        $stmt->bind_param("isss", $userId, $ipAddress, $userAgent, $sessionId);
        $stmt->execute();
        $stmt->close();

        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $email;
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['login_time'] = time();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'type' => $_SESSION['user_type'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email']
            ];
        }
        return null;
    }

    public function destroySession() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
            // Update logout time in login history
            $stmt = $this->conn->prepare("UPDATE login_history SET logout_time = CURRENT_TIMESTAMP WHERE session_id = ? AND logout_time IS NULL");
            $stmt->bind_param("s", $_SESSION['session_id']);
            $stmt->execute();
            $stmt->close();

            // Deactivate session
            $stmt = $this->conn->prepare("UPDATE user_sessions SET is_active = FALSE WHERE session_id = ?");
            $stmt->bind_param("s", $_SESSION['session_id']);
            $stmt->execute();
            $stmt->close();
        }

        session_unset();
        session_destroy();
    }

    public function requireLogin($allowedTypes = []) {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }

        if (!empty($allowedTypes) && !in_array($_SESSION['user_type'], $allowedTypes)) {
            header('Location: dashboard.php?error=unauthorized');
            exit();
        }
    }
}
?>