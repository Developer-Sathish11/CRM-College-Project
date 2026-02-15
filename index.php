<?php
session_start();

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

// Fetch real data from database
// Total Students
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND is_active = 1");
$total_students = $stmt->fetchColumn();

// Total Staff
$stmt = $pdo->query("SELECT COUNT(*) FROM staff");
$total_staff = $stmt->fetchColumn();

// Total Courses (you might need a courses table - for now counting distinct student courses)
$stmt = $pdo->query("SELECT COUNT(DISTINCT course) FROM students WHERE course IS NOT NULL");
$total_courses = $stmt->fetchColumn();

// Get students data
$stmt = $pdo->prepare("
    SELECT u.*, s.student_id, s.course, s.semester 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    WHERE u.user_type = 'student' AND u.is_active = 1 
    ORDER BY s.student_id DESC
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get staff data
$stmt = $pdo->prepare("SELECT * FROM staff ORDER BY id DESC");
$stmt->execute();
$staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get courses data (from students table for now)
$stmt = $pdo->query("
    SELECT course, COUNT(*) as student_count 
    FROM students 
    WHERE course IS NOT NULL 
    GROUP BY course
");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For demo, if no data exists, use defaults
if (empty($students)) {
    $total_students = 0;
}
if (empty($staff_members)) {
    $total_staff = 0;
}
if (empty($courses)) {
    $total_courses = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        /* Your existing CSS styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #7f8c8d;
            --light-gray: #bdc3c7;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        /* Header & Navigation */
        header {
            background-color: var(--secondary);
            color: white;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 28px;
            color: var(--primary);
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .logo span {
            color: var(--primary);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        nav a.active {
            background-color: var(--primary);
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: var(--light);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: #d5dbdb;
        }

        .btn-accent {
            background-color: var(--accent);
            color: white;
        }

        .btn-accent:hover {
            background-color: #c0392b;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #229954;
        }

        /* Carousel */
        .carousel-container {
            max-width: 1400px;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            position: relative;
        }

        .carousel {
            position: relative;
            height: 400px;
            overflow: hidden;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease;
            display: flex;
            align-items: center;
            padding: 0 50px;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .slide-1 {
            background: linear-gradient(to right, #2c3e50, #3498db);
        }

        .slide-2 {
            background: linear-gradient(to right, #3498db, #2ecc71);
        }

        .slide-3 {
            background: linear-gradient(to right, #e74c3c, #f39c12);
        }

        .slide-content {
            color: white;
            max-width: 600px;
        }

        .slide-content h2 {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .slide-content p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .carousel-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: var(--transition);
        }

        .carousel-dot.active {
            background-color: white;
        }

        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }

        .page-title h2 {
            color: var(--secondary);
            font-size: 28px;
        }

        /* Dashboard */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 5px solid var(--primary);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.staff {
            border-left-color: var(--success);
        }

        .stat-card.courses {
            border-left-color: var(--warning);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: white;
        }

        .stat-icon.students {
            background-color: var(--primary);
        }

        .stat-icon.staff {
            background-color: var(--success);
        }

        .stat-icon.courses {
            background-color: var(--warning);
        }

        .stat-info h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 16px;
        }

        /* Forms and Tables */
        .section {
            margin-bottom: 40px;
            display: none;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-container {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        thead {
            background-color: var(--secondary);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }

        /* Login Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            color: var(--secondary);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
        }

        .login-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .login-option {
            padding: 20px;
            text-align: center;
            border: 2px solid #eee;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .login-option:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }

        .login-option i {
            font-size: 40px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .login-option h4 {
            margin-bottom: 5px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }

            .carousel-slide {
                padding: 0 20px;
            }

            .slide-content h2 {
                font-size: 28px;
            }

            .slide-content p {
                font-size: 16px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .login-options {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .auth-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                justify-content: center;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .table-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    
<?php include "include/nav.php"; ?>
    <!-- Carousel Section -->
    <div class="carousel-container">
        <div class="carousel">
            <div class="carousel-slide slide-1 active">
                <div class="slide-content">
                    <h2>Welcome to College CRM System</h2>
                    <p>Streamline student management, track attendance, manage fees, and generate comprehensive reports all in one platform.</p>
                    <button class="btn btn-primary">Get Started</button>
                </div>
            </div>
            <div class="carousel-slide slide-2">
                <div class="slide-content">
                    <h2>Efficient Student Management</h2>
                    <p>Add, edit, and track student information with our intuitive interface designed for educational institutions.</p>
                    <button class="btn btn-success">Learn More</button>
                </div>
            </div>
            <div class="carousel-slide slide-3">
                <div class="slide-content">
                    <h2>Comprehensive Reporting</h2>
                    <p>Generate detailed reports on attendance, fees, and academic performance to make data-driven decisions.</p>
                    <button class="btn btn-accent">View Reports</button>
                </div>
            </div>
            <div class="carousel-controls">
                <div class="carousel-dot active" data-slide="0"></div>
                <div class="carousel-dot" data-slide="1"></div>
                <div class="carousel-dot" data-slide="2"></div>
            </div>
        </div>
    </div>

    <!-- Main Content Router -->
    <div class="main-container">
        <div class="router-container">
            <!-- Dashboard Section -->
            <section id="dashboard" class="section active">
                <div class="page-title">
                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2>
                    <div class="action-buttons">
                        <button class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Refresh</button>
                    </div>
                </div>
                
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon students">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="totalStudents"><?php echo $total_students ?: 0; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card staff">
                        <div class="stat-icon staff">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="totalStaff"><?php echo $total_staff ?: 0; ?></h3>
                            <p>Total Staff</p>
                        </div>
                    </div>
                    
                    <div class="stat-card courses">
                        <div class="stat-icon courses">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="totalCourses"><?php echo $total_courses ?: 0; ?></h3>
                            <p>Courses Count</p>
                        </div>
                    </div>
                </div> 
                </div>
            </section>

            <!-- Student Management Section -->
            <section id="students" class="section">
                <div class="page-title">
                    <h2><i class="fas fa-users"></i> Student Management</h2>
                    <div class="action-buttons">
                        
                        <button class="btn btn-secondary"><i class="fas fa-download"></i> Export</button>
                    </div>
                </div>
                
                <div class="form-container" id="addStudentForm" style="display: none;">
                    <h3>Add New Student</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="studentName">Full Name</label>
                            <input type="text" id="studentName" placeholder="Enter student name">
                        </div>
                        <div class="form-group">
                            <label for="studentEmail">Email Address</label>
                            <input type="email" id="studentEmail" placeholder="Enter email address">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="studentPhone">Phone Number</label>
                            <input type="tel" id="studentPhone" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label for="studentCourse">Course</label>
                            <select id="studentCourse">
                                <option value="">Select Course</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Arts">Arts</option>
                                <option value="Commerce">Commerce</option>
                                <option value="Science">Science</option>
                                <option value="Management">Management</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-success" id="saveStudentBtn">Save Student</button>
                        <button class="btn btn-secondary" id="cancelStudentBtn">Cancel</button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id'] ?? $student['id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['course'] ?? 'Not assigned'); ?></td>
                                    <td><span class="badge badge-success">Active</span></td>
                                    <td class="table-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editStudent(this)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-accent" onclick="deleteStudent(this)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Staff Management Section -->
            <section id="staff" class="section">
                <div class="page-title">
                    <h2><i class="fas fa-chalkboard-teacher"></i> Staff Management</h2>
                    <div class="action-buttons">
                       
                    </div>
                </div>
                
                <div class="form-container" id="addStaffForm" style="display: none;">
                    <h3>Add New Staff Member</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="staffName">Full Name</label>
                            <input type="text" id="staffName" placeholder="Enter staff name">
                        </div>
                        <div class="form-group">
                            <label for="staffRole">Role</label>
                            <select id="staffRole">
                                <option value="">Select Role</option>
                                <option value="Professor">Professor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Lecturer">Lecturer</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-success" id="saveStaffBtn">Save Staff</button>
                        <button class="btn btn-secondary" id="cancelStaffBtn">Cancel</button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="staffTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($staff_members)): ?>
                                <?php foreach ($staff_members as $staff): ?>
                                <tr>
                                    <td><?php echo $staff['id']; ?></td>
                                    <td><?php echo htmlspecialchars($staff['staff_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($staff['designation'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($staff['role'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($staff['department'] ?? 'N/A'); ?></td>
                                    <td class="table-actions">
                                        <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-accent"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No staff members found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Course Management Section -->
            <section id="courses" class="section">
                <div class="page-title">
                    <h2><i class="fas fa-book"></i> Course Management</h2>
                    <div class="action-buttons">
                        <button class="btn btn-success"><i class="fas fa-user-graduate"></i> Assign Course</button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="coursesTable">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Students Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($courses)): ?>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course']); ?></td>
                                    <td><?php echo $course['student_count']; ?></td>
                                    <td class="table-actions">
                                        <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-success"><i class="fas fa-user-plus"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No courses found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Attendance Section -->
            <section id="attendance" class="section">
                <div class="page-title">
                    <h2><i class="fas fa-clipboard-check"></i> Attendance Management</h2>
                    <div class="action-buttons">
                        <button class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Mark Attendance</button>
                        <button class="btn btn-secondary"><i class="fas fa-eye"></i> View Attendance</button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="attendanceTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Total Students</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Percentage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center">Attendance feature coming soon</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Fees Management Section -->
            <section id="fees" class="section">
                <div class="page-title">
                    <h2><i class="fas fa-money-check-alt"></i> Fees Management</h2>
                    <div class="action-buttons">
                        <button class="btn btn-primary"><i class="fas fa-money-bill-wave"></i> Collect Fees</button>
                        <button class="btn btn-secondary"><i class="fas fa-file-invoice-dollar"></i> Generate Invoice</button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="feesTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Total Fees</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center">Fees management feature coming soon</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Reports Section -->
            <section id="reports" class="section">
                <div class="page-title">
                    <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
                    <div class="action-buttons">
                        <button class="btn btn-primary"><i class="fas fa-file-pdf"></i> Export PDF</button>
                        <button class="btn btn-success"><i class="fas fa-file-excel"></i> Export Excel</button>
                    </div>
                </div>
                
                <div class="form-container">
                    <h3>Generate Reports</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reportType">Report Type</label>
                            <select id="reportType">
                                <option value="student">Student Report</option>
                                <option value="attendance">Attendance Report</option>
                                <option value="fees">Fees Report</option>
                                <option value="academic">Academic Performance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reportPeriod">Period</label>
                            <select id="reportPeriod">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-success"><i class="fas fa-chart-line"></i> Generate Report</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

<?php include "include/footer.php"; ?>

    <!-- Login Modal -->
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Login to College CRM</h3>
                <button class="close-modal" id="closeLoginModal">&times;</button>
            </div>
            <div class="form-group">
                <label for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" placeholder="Enter your password">
            </div>
            <button class="btn btn-primary btn-block" style="width: 100%; margin-top: 20px;">Login</button>
            
            <div style="text-align: center; margin: 20px 0;">
                <span style="color: var(--gray);">Or login as</span>
            </div>
            
            <div class="login-options">
                <div class="login-option" data-role="admin">
                    <i class="fas fa-user-shield"></i>
                    <h4>Admin</h4>
                    <p>Full access</p>
                </div>
                <div class="login-option" data-role="staff">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h4>Staff</h4>
                    <p>Limited access</p>
                </div>
                <div class="login-option" data-role="student">
                    <i class="fas fa-user-graduate"></i>
                    <h4>Student</h4>
                    <p>View only</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = document.querySelectorAll('.section');
        const loginBtn = document.getElementById('login-btn');
        const demoBtn = document.getElementById('demo-btn');
        const loginModal = document.getElementById('loginModal');
        const closeLoginModal = document.getElementById('closeLoginModal');
        const loginOptions = document.querySelectorAll('.login-option');
        const addStudentBtn = document.getElementById('addStudentBtn');
        const addStudentForm = document.getElementById('addStudentForm');
        const cancelStudentBtn = document.getElementById('cancelStudentBtn');
        const saveStudentBtn = document.getElementById('saveStudentBtn');
        const addStaffBtn = document.getElementById('addStaffBtn');
        const addStaffForm = document.getElementById('addStaffForm');
        const cancelStaffBtn = document.getElementById('cancelStaffBtn');
        const saveStaffBtn = document.getElementById('saveStaffBtn');
        const carouselDots = document.querySelectorAll('.carousel-dot');
        const carouselSlides = document.querySelectorAll('.carousel-slide');

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize carousel
            startCarousel();
            
            // Set up event listeners
            setupEventListeners();
        });

        // Set up all event listeners
        function setupEventListeners() {
            // Navigation
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    
                    // Update active nav link
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show selected section
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === page) {
                            section.classList.add('active');
                        }
                    });
                });
            });

            // Login modal
            if (loginBtn) {
                loginBtn.addEventListener('click', () => {
                    loginModal.classList.add('active');
                });
            }

            if (closeLoginModal) {
                closeLoginModal.addEventListener('click', () => {
                    loginModal.classList.remove('active');
                });
            }

            loginOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const role = this.getAttribute('data-role');
                    alert(`Logging in as ${role}...`);
                    loginModal.classList.remove('active');
                    
                    // Update login button
                    if (loginBtn) {
                        loginBtn.innerHTML = `<i class="fas fa-user"></i> ${role.charAt(0).toUpperCase() + role.slice(1)}`;
                    }
                });
            });

            // Demo button
            if (demoBtn) {
                demoBtn.addEventListener('click', () => {
                    alert('Demo mode activated! You can explore all features.');
                });
            }

            // Student management
            if (addStudentBtn) {
                addStudentBtn.addEventListener('click', () => {
                    addStudentForm.style.display = 'block';
                });
            }

            if (cancelStudentBtn) {
                cancelStudentBtn.addEventListener('click', () => {
                    addStudentForm.style.display = 'none';
                });
            }

            if (saveStudentBtn) {
                saveStudentBtn.addEventListener('click', () => {
                    const name = document.getElementById('studentName').value;
                    const email = document.getElementById('studentEmail').value;
                    
                    if (name && email) {
                        alert('This is a demo. In production, this would save to database.');
                        addStudentForm.style.display = 'none';
                    } else {
                        alert('Please fill in at least name and email');
                    }
                });
            }

            // Staff management
            if (addStaffBtn) {
                addStaffBtn.addEventListener('click', () => {
                    addStaffForm.style.display = 'block';
                });
            }

            if (cancelStaffBtn) {
                cancelStaffBtn.addEventListener('click', () => {
                    addStaffForm.style.display = 'none';
                });
            }

            if (saveStaffBtn) {
                saveStaffBtn.addEventListener('click', () => {
                    const name = document.getElementById('staffName').value;
                    const role = document.getElementById('staffRole').value;
                    
                    if (name && role) {
                        alert('This is a demo. In production, this would save to database.');
                        addStaffForm.style.display = 'none';
                    } else {
                        alert('Please fill in all fields');
                    }
                });
            }

            // Carousel controls
            carouselDots.forEach(dot => {
                dot.addEventListener('click', function() {
                    const slideIndex = parseInt(this.getAttribute('data-slide'));
                    showCarouselSlide(slideIndex);
                });
            });
        }

        // Carousel functionality
        let currentSlide = 0;
        const slideInterval = 5000; // 5 seconds

        function startCarousel() {
            setInterval(() => {
                currentSlide = (currentSlide + 1) % carouselSlides.length;
                showCarouselSlide(currentSlide);
            }, slideInterval);
        }

        function showCarouselSlide(index) {
            carouselSlides.forEach(slide => slide.classList.remove('active'));
            carouselDots.forEach(dot => dot.classList.remove('active'));
            
            carouselSlides[index].classList.add('active');
            carouselDots[index].classList.add('active');
            currentSlide = index;
        }

        // Student actions
        function editStudent(button) {
            const row = button.closest('tr');
            const cells = row.cells;
            
            // Get current values
            const name = cells[1].textContent;
            const email = cells[2].textContent;
            
            alert('Sorry Students are not allowed for edit: ' + name);
        }

        function deleteStudent(button) {
            if (confirm('Are you sure you want to delete this student?')) {
                alert('Delete feature coming soon');
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.classList.remove('active');
            }
        });
    </script>
</body>
</html>