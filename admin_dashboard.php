
<?php
// admin/admin_dashboard.php
require_once 'admin/config/db.php';
require_once 'admin/config/session.php';

$database = new Database();
$session = new SessionManager($database);

// Only admin can access
$session->requireLogin(['admin']);
?>