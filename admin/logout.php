<?php
// logout.php
require_once 'config/db.php';
require_once 'config/session.php';

$database = new Database();
$session = new SessionManager($database);

$session->destroySession();

// Redirect to login page
header('Location: index.php');
exit();
?>
