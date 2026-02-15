<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Database Connection
$host = 'localhost';
$dbname = 'crmprojects';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get admin details
$stmt = $pdo->prepare("
    SELECT u.*, a.admin_id, a.department, a.position 
    FROM users u 
    JOIN admins a ON u.id = a.user_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];

// Total Students
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND is_active = 1");
$stats['total_students'] = $stmt->fetchColumn();

// Total Staff 
$stmt = $pdo->query("SELECT COUNT(*) FROM staff");
$stats['total_staff'] = $stmt->fetchColumn();

// New Students This Month
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stats['new_students'] = $stmt->fetchColumn();

// Active Sessions
$stmt = $pdo->query("SELECT COUNT(*) FROM user_sessions WHERE is_active = 1");
$stats['active_sessions'] = $stmt->fetchColumn();

// Recent Activities
$stmt = $pdo->prepare("
    SELECT lh.*, u.full_name, u.user_type, u.username 
    FROM login_history lh 
    JOIN users u ON lh.user_id = u.id 
    ORDER BY lh.login_time DESC 
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Student Course Distribution
$stmt = $pdo->query("
    SELECT course, COUNT(*) as count 
    FROM students 
    GROUP BY course 
    ORDER BY count DESC 
    LIMIT 5
");
$course_distribution = $stmt->fetchAll();

// Handle Add Student Form Submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'add_student') {
        $username = strtolower(str_replace(' ', '.', $_POST['full_name'])) . rand(100, 999);
        $password = password_hash('student@123', PASSWORD_DEFAULT);
        
        try {
            $pdo->beginTransaction();
            
            // Insert into users table
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, user_type, full_name, phone) 
                VALUES (?, ?, ?, 'student', ?, ?)
            ");
            $stmt->execute([$username, $_POST['email'], $password, $_POST['full_name'], $_POST['phone']]);
            $user_id = $pdo->lastInsertId();
            
            // Insert into students table
            $student_id = 'STU' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                INSERT INTO students (user_id, student_id, course, semester, batch_year, date_of_birth, address, parent_name, parent_phone, enrollment_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $student_id, $_POST['course'], $_POST['semester'], 
                $_POST['batch_year'], $_POST['dob'], $_POST['address'],
                $_POST['parent_name'], $_POST['parent_phone'], date('Y-m-d')
            ]);
            
            $pdo->commit();
            $success_message = "Student added successfully! Student ID: " . $student_id;
        } catch(Exception $e) {
            $pdo->rollBack();
            $error_message = "Error adding student: " . $e->getMessage();
        }
    }
    
    elseif($_POST['action'] == 'delete_student') {
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $success_message = "Student deactivated successfully!";
        } catch(Exception $e) {
            $error_message = "Error deleting student: " . $e->getMessage();
        }
    }
}

// Get all students
$stmt = $pdo->prepare("
    SELECT u.*, s.student_id, s.course, s.semester, s.batch_year, s.enrollment_date 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    WHERE u.user_type = 'student' AND u.is_active = 1 
    ORDER BY s.student_id DESC
");
$stmt->execute();
$students = $stmt->fetchAll();

// Courses array for dropdown
$courses = ['Computer Science', 'Engineering', 'Arts', 'Commerce', 'Science', 'Management'];

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Admin Panel Styles */
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
        }

        /* Sidebar Styles */
        #sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1a2e4f 0%, #0f1a2b 100%);
        }

        #sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
            transition: all 0.3s;
        }

        #sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,.1);
        }

        #sidebar .nav-link.active {
            color: white;
            background-color: var(--primary-color);
        }

        #sidebar .nav-link i {
            margin-right: 0.5rem;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card.border-left-primary {
            border-left: 0.25rem solid var(--primary-color) !important;
        }

        .card.border-left-success {
            border-left: 0.25rem solid var(--success-color) !important;
        }

        .card.border-left-info {
            border-left: 0.25rem solid var(--info-color) !important;
        }

        .card.border-left-warning {
            border-left: 0.25rem solid var(--warning-color) !important;
        }

        /* Statistics Cards */
        .text-xs {
            font-size: 0.7rem;
        }

        .text-gray-300 {
            color: #dddfeb;
        }

        .text-gray-800 {
            color: #5a5c69;
        }

        /* Table Styles */
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #4e73df;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }

        /* Button Styles */
        .btn {
            border-radius: 0.35rem;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.375rem 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }

        /* Badge Styles */
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.65rem;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 0.5rem;
        }

        .modal-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .modal-footer {
            background-color: #f8f9fc;
            border-top: 1px solid #e3e6f0;
        }

        /* Form Styles */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            #sidebar {
                min-height: auto;
            }
            
            .card-body {
                padding: 1rem;
            }
        }

        /* Loading Spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Content Sections */
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR - Fixed -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4 mt-3">
                        <i class="bi bi-building fs-1 text-white"></i>
                        <h5 class="text-white mt-2">College CRM</h5>
                        <span class="badge bg-danger">Admin Panel</span>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'index.php' && !isset($_GET['page']) ? 'active' : ''; ?>" href="index.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['page']) && $_GET['page'] == 'students' ? 'active' : ''; ?>" href="?page=students">
                                <i class="bi bi-people"></i> Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['page']) && $_GET['page'] == 'staff' ? 'active' : ''; ?>" href="?page=staff">
                                <i class="bi bi-person-badge"></i> Staff
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['page']) && $_GET['page'] == 'reports' ? 'active' : ''; ?>" href="?page=reports">
                                <i class="bi bi-file-earmark-bar-graph"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['page']) && $_GET['page'] == 'settings' ? 'active' : ''; ?>" href="?page=settings">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>

                    <div class="mt-5 p-3 text-white">
                        <hr class="bg-white">
                        <div class="small">
                            <i class="bi bi-shield-check"></i> Logged in as:<br>
                            <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong><br>
                            <span class="text-muted"><?php echo $admin['admin_id']; ?></span>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- MAIN CONTENT -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                
                <?php if(!isset($_GET['page']) || $_GET['page'] == 'dashboard'): ?>
                <!-- ==================== DASHBOARD SECTION ==================== -->
                <div id="dashboard-section" class="content-section active">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Dashboard</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <span class="me-3">
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo date('d M Y'); ?>
                                </span>
                                <span>
                                    <i class="bi bi-person-circle"></i> 
                                    <?php echo htmlspecialchars($admin['full_name']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if(isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Students</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['total_students']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-people fs-2 text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Staff</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['total_staff']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-person-badge fs-2 text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                New Students (This Month)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['new_students']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-person-plus fs-2 text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Active Sessions</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['active_sessions']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-laptop fs-2 text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Course Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="courseChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Login Activity</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Type</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($recent_activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $activity['user_type'] == 'admin' ? 'danger' : 
                                                                ($activity['user_type'] == 'staff' ? 'info' : 'success'); 
                                                        ?>">
                                                            <?php echo ucfirst($activity['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('h:i A', strtotime($activity['login_time'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $activity['status'] == 'success' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($activity['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <button type="button" class="btn btn-primary m-1" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                        <i class="bi bi-person-plus"></i> Add Student
                                    </button>
                                    <a href="index.php" class="btn btn-success m-1">
                                        <i class="bi bi-person-badge"></i> Add Staff
                                    </a>
                                    <a href="?page=reports" class="btn btn-info m-1">
                                        <i class="bi bi-file-earmark-text"></i> Generate Report
                                    </a>
                                    <button class="btn btn-warning m-1" onclick="sendNotification()">
                                        <i class="bi bi-bell"></i> Send Notification
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ==================== STUDENTS MANAGEMENT SECTION ==================== -->
                <?php if(isset($_GET['page']) && $_GET['page'] == 'students'): ?>
                <div id="students-section" class="content-section active">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Student Management</h1>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus"></i> Add New Student
                        </button>
                    </div>

                    <!-- Alert Messages -->
                    <?php if(isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Search Bar -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search by name, ID, course...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="courseFilter" class="form-select">
                                <option value="">All Courses</option>
                                <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course; ?>"><?php echo $course; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="semesterFilter" class="form-select">
                                <option value="">All Semesters</option>
                                <?php for($i=1; $i<=8; $i++): ?>
                                <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="studentsTable">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Course</th>
                                            <th>Semester</th>
                                            <th>Batch</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Enrollment Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($students as $student): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?php echo $student['student_id']; ?></span></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo $student['course']; ?></td>
                                            <td>Sem <?php echo $student['semester']; ?></td>
                                            <td><?php echo $student['batch_year']; ?></td>
                                            <td><?php echo $student['email']; ?></td>
                                            <td><?php echo $student['phone']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($student['enrollment_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="editStudent(<?php echo $student['id']; ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate this student?');">
                                                    <input type="hidden" name="action" value="delete_student">
                                                    <input type="hidden" name="user_id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

<?php if(isset($_GET['page']) && $_GET['page'] == 'staff'): ?>
<div id="staff-section" class="content-section active">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Staff Management</h1>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="bi bi-person-badge"></i> Add New Staff
        </button>
    </div>
    
    <!-- Alert Messages -->
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-body">
            <!-- Staff Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Staff ID</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Qualification</th>
                            <th>Joining Date</th>
                            <th>Salary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Use the existing PDO connection
                            $staff_query = "SELECT * FROM staff ORDER BY id DESC";
                            $staff_stmt = $pdo->prepare($staff_query);
                            $staff_stmt->execute();
                            $staff_members = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if(count($staff_members) > 0):
                                foreach($staff_members as $staff):
                        ?>
                        <tr>
                            <td><?php echo $staff['id']; ?></td>
                            <td><?php echo htmlspecialchars($staff['staff_id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($staff['role'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($staff['department'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($staff['designation'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($staff['qualification'] ?? 'N/A'); ?></td>
                            <td><?php echo $staff['joining_date'] ? date('d-m-Y', strtotime($staff['joining_date'])) : 'N/A'; ?></td>
                            <td>₹<?php echo $staff['salary'] ? number_format($staff['salary'], 2) : '0.00'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editStaff(<?php echo $staff['id']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStaff(<?php echo $staff['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <tr>
                            <td colspan="9" class="text-center">No staff members found</td>
                        </tr>
                        <?php 
                            endif;
                        } catch(PDOException $e) {
                            echo "<tr><td colspan='9' class='text-center text-danger'>Error loading staff: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_staff">
                <!-- Automatically set user_id from session -->
                <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Staff ID <span class="text-danger">*</span></label>
                            <input type="text" name="staff_id" class="form-control" required 
                                   placeholder="e.g., STAFF2024001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="Professor">Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Lecturer">Lecturer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <input type="text" name="department" class="form-control" required
                                   placeholder="e.g., Computer Science">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" required
                                   placeholder="e.g., Professor">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Qualification <span class="text-danger">*</span></label>
                            <input type="text" name="qualification" class="form-control" required
                                   placeholder="e.g., Ph.D in Computer Science">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" name="joining_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="salary" class="form-control" required
                                   placeholder="e.g., 50000.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="submit_staff" class="btn btn-primary">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php

require_once 'config/db.php'; // Make sure this path is correct

// Handle Add Staff Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_staff') {
    try {
        // Get form data
        $staff_id = $_POST['staff_id'];
        $role = $_POST['role'];
        $department = $_POST['department'];
        $designation = $_POST['designation'];
        $qualification = $_POST['qualification'];
        $joining_date = $_POST['joining_date'];
        $salary = $_POST['salary'];
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Check if staff_id already exists
        $check_query = "SELECT id FROM staff WHERE staff_id = :staff_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([':staff_id' => $staff_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $_SESSION['error'] = "Staff ID already exists!";
        } else {
            // Insert new staff
            $insert_query = "INSERT INTO staff (staff_id, role, department, designation, qualification, joining_date, salary) 
                            VALUES (:staff_id, :role, :department, :designation, :qualification, :joining_date, :salary)";
            
            // If you added user_id column, use this query instead:
            // $insert_query = "INSERT INTO staff (user_id, staff_id, role, department, designation, qualification, joining_date, salary) 
            //                 VALUES (:user_id, :staff_id, :role, :department, :designation, :qualification, :joining_date, :salary)";
            
            $insert_stmt = $pdo->prepare($insert_query);
            
            $params = [
                ':staff_id' => $staff_id,
                ':role' => $role,
                ':department' => $department,
                ':designation' => $designation,
                ':qualification' => $qualification,
                ':joining_date' => $joining_date,
                ':salary' => $salary
            ];
            
            // If using user_id, add it to params
            // $params[':user_id'] = $user_id;
            
            if ($insert_stmt->execute($params)) {
                $_SESSION['success'] = "Staff added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add staff!";
            }
        }
        

        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=staff");
        exit();
    }
}

// Handle Delete Staff
if (isset($_GET['delete_staff'])) {
    try {
        $id = $_GET['delete_staff'];
        $delete_query = "DELETE FROM staff WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_query);
        
        if ($delete_stmt->execute([':id' => $id])) {
            $_SESSION['success'] = "Staff deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete staff!";
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=staff");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=staff");
        exit();
    }
}
?>
   <?php endif; ?>


                <!-- ==================== REPORTS SECTION ==================== -->
                <?php if(isset($_GET['page']) && $_GET['page'] == 'reports'): ?>
                <div id="reports-section" class="content-section active">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Reports & Analytics</h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card shadow">
                                <div class="card-body text-center">
                                    <i class="bi bi-people fs-1 text-primary"></i>
                                    <h5 class="mt-2">Student Report</h5>
                                    <p class="text-muted">Total Students: <?php echo $stats['total_students']; ?></p>
                                    <button class="btn btn-sm btn-primary" onclick="alert('Download feature coming soon!')">
                                        <i class="bi bi-download"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card shadow">
                                <div class="card-body text-center">
                                    <i class="bi bi-person-badge fs-1 text-success"></i>
                                    <h5 class="mt-2">Staff Report</h5>
                                    <p class="text-muted">Total Staff: <?php echo $stats['total_staff']; ?></p>
                                    <button class="btn btn-sm btn-success" onclick="alert('Download feature coming soon!')">
                                        <i class="bi bi-download"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card shadow">
                                <div class="card-body text-center">
                                    <i class="bi bi-activity fs-1 text-info"></i>
                                    <h5 class="mt-2">Activity Report</h5>
                                    <p class="text-muted">Last 30 days activity</p>
                                    <button class="btn btn-sm btn-info" onclick="alert('Download feature coming soon!')">
                                        <i class="bi bi-download"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ==================== SETTINGS SECTION ==================== -->
                <?php if(isset($_GET['page']) && $_GET['page'] == 'settings'): ?>
                <div id="settings-section" class="content-section active">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Settings</h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Profile Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['full_name']); ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo $admin['email']; ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Admin ID</label>
                                            <input type="text" class="form-control" value="<?php echo $admin['admin_id']; ?>" readonly>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="alert('Edit profile feature coming soon!')">
                                            Edit Profile
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card shadow mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">System Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">System Name</label>
                                        <input type="text" class="form-control" value="College CRM System" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Academic Year</label>
                                        <input type="text" class="form-control" value="2025-2026" readonly>
                                    </div>
                                    <button type="button" class="btn btn-warning" onclick="alert('Change password feature coming soon!')">
                                        Change Password
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- ==================== ADD STUDENT MODAL ==================== -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_student">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone *</label>
                                <input type="text" name="phone" class="form-control" required pattern="[0-9]{10}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Course *</label>
                                <select name="course" class="form-select" required>
                                    <option value="">Select Course</option>
                                    <?php foreach($courses as $course): ?>
                                    <option value="<?php echo $course; ?>"><?php echo $course; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester *</label>
                                <select name="semester" class="form-select" required>
                                    <option value="">Select Semester</option>
                                    <?php for($i=1; $i<=8; $i++): ?>
                                    <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Batch Year *</label>
                                <select name="batch_year" class="form-select" required>
                                    <option value="">Select Year</option>
                                    <?php for($i=2020; $i<=2030; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Parent Name</label>
                                <input type="text" name="parent_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent Phone</label>
                                <input type="text" name="parent_phone" class="form-control" pattern="[0-9]{10}">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Default password: <strong>student@123</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== ADD STAFF MODAL ==================== -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i> Staff management feature coming soon!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Course Distribution Chart (only show on dashboard)
        <?php if(!isset($_GET['page']) || $_GET['page'] == 'dashboard'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('courseChart');
            if(ctx) {
                const courseChart = new Chart(ctx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: [<?php 
                            foreach($course_distribution as $course) {
                                echo "'" . addslashes($course['course']) . "',";
                            }
                        ?>],
                        datasets: [{
                            data: [<?php 
                                foreach($course_distribution as $course) {
                                    echo $course['count'] . ",";
                                }
                            ?>],
                            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
                        }]
                    }
                });
            }
        });
        <?php endif; ?>

        // Search and Filter Functions
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    filterTable();
                });
            }

            // Course filter
            const courseFilter = document.getElementById('courseFilter');
            if(courseFilter) {
                courseFilter.addEventListener('change', function() {
                    filterTable();
                });
            }

            // Semester filter
            const semesterFilter = document.getElementById('semesterFilter');
            if(semesterFilter) {
                semesterFilter.addEventListener('change', function() {
                    filterTable();
                });
            }
        });

        function filterTable() {
            const searchValue = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const courseValue = document.getElementById('courseFilter')?.value || '';
            const semesterValue = document.getElementById('semesterFilter')?.value || '';
            const table = document.getElementById('studentsTable');
            
            if(!table) return;
            
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for(let row of rows) {
                let showRow = true;
                
                // Search filter
                if(searchValue) {
                    const rowText = row.innerText.toLowerCase();
                    if(!rowText.includes(searchValue)) {
                        showRow = false;
                    }
                }
                
                // Course filter
                if(courseValue && showRow) {
                    const courseCell = row.cells[2].innerText;
                    if(courseCell !== courseValue) {
                        showRow = false;
                    }
                }
                
                // Semester filter
                if(semesterValue && showRow) {
                    const semesterCell = row.cells[3].innerText.replace('Sem ', '');
                    if(semesterCell !== semesterValue) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        }

        function viewStudent(userId) {
            alert('View student feature coming soon! Student ID: ' + userId);
        }

        function editStudent(userId) {
            alert('Edit student feature coming soon! Student ID: ' + userId);
        }

        function sendNotification() {
            alert('Notification feature coming soon!');
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                let bsAlert = new bootstrap.Alert(alert);
                setTimeout(function() {
                    bsAlert.close();
                }, 5000);
            });
        }, 100);
    </script>
</body>
</html>