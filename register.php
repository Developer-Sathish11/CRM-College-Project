<?php
// register.php
require_once 'admin/config/db.php';

$database = new Database();
$conn = $database->getConnection();
$error = '';
$success = '';

// Get user type from URL parameter (default to student)
$userType = isset($_GET['type']) && in_array($_GET['type'], ['student', 'staff', 'admin']) ? $_GET['type'] : 'student';

// Get courses from database or use default list
$courses = ['Computer Science', 'Engineering', 'Business Administration', 'Medicine', 'Arts', 'Law', 'Education', 'Nursing'];

// Get departments for staff
$departments = ['Computer Science', 'Engineering', 'Business Administration', 'Mathematics', 'Physics', 'Chemistry', 'English', 'History', 'Physical Education'];

// Get positions for staff
$positions = ['Professor', 'Associate Professor', 'Assistant Professor', 'Lecturer', 'Senior Lecturer', 'Lab Assistant', 'Administrative Staff'];

// Get current year for batch year dropdown
$currentYear = date('Y');
$years = range($currentYear - 5, $currentYear + 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userType = $_POST['user_type'] ?? 'student';
    $fullName = $database->escapeString($_POST['full_name']);
    $email = $database->escapeString($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $phone = $database->escapeString($_POST['phone']);
    
    // Common fields
    $dateOfBirth = $_POST['date_of_birth'] ?? null;
    $address = $database->escapeString($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    // Student-specific validation
    if ($userType == 'student') {
        $course = $database->escapeString($_POST['course'] ?? '');
        $semester = intval($_POST['semester'] ?? 0);
        $batchYear = intval($_POST['batch_year'] ?? 0);
        $parentName = $database->escapeString($_POST['parent_name'] ?? '');
        $parentPhone = $database->escapeString($_POST['parent_phone'] ?? '');
        
        if (empty($course)) {
            $errors[] = 'Please select a course';
        }
        
        if (empty($semester)) {
            $errors[] = 'Please select semester';
        }
        
        if (empty($batchYear)) {
            $errors[] = 'Please select batch year';
        }
    }
    
    // Staff-specific validation
    if ($userType == 'staff') {
        $employeeId = $database->escapeString($_POST['employee_id'] ?? '');
        $department = $database->escapeString($_POST['department'] ?? '');
        $position = $database->escapeString($_POST['position'] ?? '');
        $qualification = $database->escapeString($_POST['qualification'] ?? '');
        $experience = intval($_POST['experience'] ?? 0);
        $joiningDate = $_POST['joining_date'] ?? date('Y-m-d');
        $emergencyContact = $database->escapeString($_POST['emergency_contact'] ?? '');
        
        if (empty($employeeId)) {
            $errors[] = 'Employee ID is required';
        }
        
        if (empty($department)) {
            $errors[] = 'Please select department';
        }
        
        if (empty($position)) {
            $errors[] = 'Please select position';
        }
    }
    
    // Admin-specific validation
    if ($userType == 'admin') {
        $adminLevel = $database->escapeString($_POST['admin_level'] ?? '');
        $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';
        
        if (empty($adminLevel)) {
            $errors[] = 'Please select admin level';
        }
    }
    
    if (empty($errors)) {
        // Check if email already exists
        $checkQuery = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = 'Email already registered. Please use a different email or login.';
        } else {
            // Generate unique ID based on user type
            $year = date('Y');
            $randomNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            if ($userType == 'student') {
                $uniqueId = 'STU' . $year . $randomNum;
            } elseif ($userType == 'staff') {
                $uniqueId = 'EMP' . $year . $randomNum;
            } else {
                $uniqueId = 'ADM' . $year . $randomNum;
            }
            
            // Generate username from email
            $username = strtolower(explode('@', $email)[0]);
            
            // Ensure username is unique
            $usernameCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $usernameCheck->bind_param("s", $username);
            $usernameCheck->execute();
            $usernameResult = $usernameCheck->get_result();
            
            if ($usernameResult->num_rows > 0) {
                $username = $username . rand(100, 999);
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert into users table
                $userQuery = "INSERT INTO users (username, email, password, user_type, full_name, phone, gender, date_of_birth, address, is_active, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param("sssssssss", $username, $email, $hashedPassword, $userType, $fullName, $phone, $gender, $dateOfBirth, $address);
                $userStmt->execute();
                
                $userId = $conn->insert_id;
                
                // Insert into specific tables based on user type
                if ($userType == 'student') {
                    $studentQuery = "INSERT INTO students (user_id, student_id, course, semester, batch_year, parent_name, parent_phone, enrollment_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
                    $studentStmt = $conn->prepare($studentQuery);
                    $studentStmt->bind_param("ississs", $userId, $uniqueId, $course, $semester, $batchYear, $parentName, $parentPhone);
                    $studentStmt->execute();
                } elseif ($userType == 'staff') {
                    $staffQuery = "INSERT INTO staff (user_id, employee_id, department, position, qualification, experience, joining_date, emergency_contact) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $staffStmt = $conn->prepare($staffQuery);
                    $staffStmt->bind_param("issssiss", $userId, $employeeId, $department, $position, $qualification, $experience, $joiningDate, $emergencyContact);
                    $staffStmt->execute();
                } elseif ($userType == 'admin') {
                    $adminQuery = "INSERT INTO admins (user_id, admin_id, admin_level, permissions) 
                                 VALUES (?, ?, ?, ?)";
                    $adminStmt = $conn->prepare($adminQuery);
                    $adminStmt->bind_param("isss", $userId, $uniqueId, $adminLevel, $permissions);
                    $adminStmt->execute();
                }
                
                $conn->commit();
                $success = 'Registration successful! You can now login with your email.';
                
                // Clear form data
                $_POST = array();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - College CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .register-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
        }

        .register-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .register-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .user-type-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .user-type-tab {
            flex: 1;
            text-align: center;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .user-type-tab i {
            font-size: 20px;
            margin-right: 8px;
        }

        .user-type-tab.active {
            background: white;
            border-bottom-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }

        .user-type-tab:not(.active):hover {
            background: #e9ecef;
        }

        .register-form {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
        }

        .form-section h3 {
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .form-section h3 i {
            color: var(--primary);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }

        .form-group label span {
            color: var(--accent);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #eee;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 14px;
        }

        .input-group input {
            padding-left: 35px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-register:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .password-strength {
            margin-top: 5px;
            height: 4px;
            border-radius: 4px;
            background: #eee;
            position: relative;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 4px;
            transition: all 0.3s ease;
            width: 0;
        }

        .password-strength-text {
            font-size: 11px;
            margin-top: 3px;
            color: #666;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
            cursor: pointer;
        }

        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        @media (max-width: 768px) {
            .register-header {
                padding: 25px 15px;
            }
            
            .register-form {
                padding: 25px 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .user-type-tab {
                padding: 10px;
                font-size: 13px;
            }
            
            .user-type-tab i {
                font-size: 16px;
                margin-right: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-<?php echo $userType == 'student' ? 'user-graduate' : ($userType == 'staff' ? 'chalkboard-teacher' : 'user-cog'); ?>"></i>
            <h1><?php echo ucfirst($userType); ?> Registration</h1>
            <p>Create your account to access the College CRM System</p>
        </div>

        <!-- User Type Tabs -->
        <div class="user-type-tabs">
            <a href="?type=student" class="user-type-tab <?php echo $userType == 'student' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Student
            </a>
            <a href="?type=staff" class="user-type-tab <?php echo $userType == 'staff' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i> Staff
            </a>
            <a href="?type=admin" class="user-type-tab <?php echo $userType == 'admin' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i> Admin
            </a>
        </div>

        <div class="register-form">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $success; ?></div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" class="btn-register" style="background: var(--primary); text-decoration: none; display: inline-block; width: auto; padding: 10px 25px;">
                        <i class="fas fa-sign-in-alt"></i> Proceed to Login
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="register.php?type=<?php echo $userType; ?>" id="registerForm" onsubmit="return validateForm()">
                <input type="hidden" name="user_type" value="<?php echo $userType; ?>">
                
                <!-- Personal Information Section (Common for all) -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name <span>*</span></label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="full_name" id="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" placeholder="Enter your full name" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address <span>*</span></label>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="Enter your email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number <span>*</span></label>
                            <div class="input-group">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="Enter phone number" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <div class="input-group">
                                <i class="fas fa-calendar"></i>
                                <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="gender" value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'checked' : ''; ?>> Male
                                </label>
                                <label>
                                    <input type="radio" name="gender" value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'checked' : ''; ?>> Female
                                </label>
                                <label>
                                    <input type="radio" name="gender" value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'checked' : ''; ?>> Other
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" id="address" placeholder="Enter your full address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Student Specific Information -->
                <?php if ($userType == 'student'): ?>
                <div class="form-section">
                    <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Select Course <span>*</span></label>
                            <select name="course" id="course" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach($courses as $course): ?>
                                    <option value="<?php echo $course; ?>" <?php echo (isset($_POST['course']) && $_POST['course'] == $course) ? 'selected' : ''; ?>>
                                        <?php echo $course; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Semester <span>*</span></label>
                            <select name="semester" id="semester" required>
                                <option value="">-- Select Semester --</option>
                                <?php for($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (isset($_POST['semester']) && $_POST['semester'] == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Batch Year <span>*</span></label>
                            <select name="batch_year" id="batch_year" required>
                                <option value="">-- Select Batch Year --</option>
                                <?php foreach($years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo (isset($_POST['batch_year']) && $_POST['batch_year'] == $year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-users"></i> Parent/Guardian Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Parent/Guardian Name</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="parent_name" id="parent_name" value="<?php echo isset($_POST['parent_name']) ? htmlspecialchars($_POST['parent_name']) : ''; ?>" placeholder="Enter parent/guardian name">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Parent/Guardian Phone</label>
                            <div class="input-group">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="parent_phone" id="parent_phone" value="<?php echo isset($_POST['parent_phone']) ? htmlspecialchars($_POST['parent_phone']) : ''; ?>" placeholder="Enter parent/guardian phone">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Staff Specific Information -->
                <?php if ($userType == 'staff'): ?>
                <div class="form-section">
                    <h3><i class="fas fa-briefcase"></i> Employment Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Employee ID <span>*</span></label>
                            <div class="input-group">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="employee_id" id="employee_id" value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>" placeholder="Enter employee ID" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Department <span>*</span></label>
                            <select name="department" id="department" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept; ?>" <?php echo (isset($_POST['department']) && $_POST['department'] == $dept) ? 'selected' : ''; ?>>
                                        <?php echo $dept; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Position <span>*</span></label>
                            <select name="position" id="position" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach($positions as $pos): ?>
                                    <option value="<?php echo $pos; ?>" <?php echo (isset($_POST['position']) && $_POST['position'] == $pos) ? 'selected' : ''; ?>>
                                        <?php echo $pos; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Qualification</label>
                            <input type="text" name="qualification" id="qualification" value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>" placeholder="e.g., M.Sc, Ph.D">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Years of Experience</label>
                            <input type="number" name="experience" id="experience" value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>" placeholder="Years of experience" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Joining Date</label>
                            <div class="input-group">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" name="joining_date" id="joining_date" value="<?php echo isset($_POST['joining_date']) ? htmlspecialchars($_POST['joining_date']) : date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Contact</label>
                        <div class="input-group">
                            <i class="fas fa-phone-alt"></i>
                            <input type="tel" name="emergency_contact" id="emergency_contact" value="<?php echo isset($_POST['emergency_contact']) ? htmlspecialchars($_POST['emergency_contact']) : ''; ?>" placeholder="Emergency contact number">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Admin Specific Information -->
                <?php if ($userType == 'admin'): ?>
                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> Admin Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Admin Level <span>*</span></label>
                            <select name="admin_level" id="admin_level" required>
                                <option value="">-- Select Admin Level --</option>
                                <option value="super" <?php echo (isset($_POST['admin_level']) && $_POST['admin_level'] == 'super') ? 'selected' : ''; ?>>Super Admin</option>
                                <option value="senior" <?php echo (isset($_POST['admin_level']) && $_POST['admin_level'] == 'senior') ? 'selected' : ''; ?>>Senior Admin</option>
                                <option value="junior" <?php echo (isset($_POST['admin_level']) && $_POST['admin_level'] == 'junior') ? 'selected' : ''; ?>>Junior Admin</option>
                                <option value="support" <?php echo (isset($_POST['admin_level']) && $_POST['admin_level'] == 'support') ? 'selected' : ''; ?>>Support Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_users" <?php echo (isset($_POST['permissions']) && in_array('manage_users', $_POST['permissions'])) ? 'checked' : ''; ?>> Manage Users
                            </label>
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_courses" <?php echo (isset($_POST['permissions']) && in_array('manage_courses', $_POST['permissions'])) ? 'checked' : ''; ?>> Manage Courses
                            </label>
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_fees" <?php echo (isset($_POST['permissions']) && in_array('manage_fees', $_POST['permissions'])) ? 'checked' : ''; ?>> Manage Fees
                            </label>
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_attendance" <?php echo (isset($_POST['permissions']) && in_array('manage_attendance', $_POST['permissions'])) ? 'checked' : ''; ?>> Manage Attendance
                            </label>
                            <label>
                                <input type="checkbox" name="permissions[]" value="manage_reports" <?php echo (isset($_POST['permissions']) && in_array('manage_reports', $_POST['permissions'])) ? 'checked' : ''; ?>> Manage Reports
                            </label>
                            <label>
                                <input type="checkbox" name="permissions[]" value="system_settings" <?php echo (isset($_POST['permissions']) && in_array('system_settings', $_POST['permissions'])) ? 'checked' : ''; ?>> System Settings
                            </label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Account Security Section (Common for all) -->
                <div class="form-section">
                    <h3><i class="fas fa-lock"></i> Account Security</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password <span>*</span></label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="password" placeholder="Create password (min. 6 characters)" required onkeyup="checkPasswordStrength()">
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="password-strength-text" id="passwordStrengthText"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm Password <span>*</span></label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required onkeyup="checkPasswordMatch()">
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                            </div>
                            <div id="passwordMatchMessage" style="font-size: 11px; margin-top: 3px;"></div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Register as <?php echo ucfirst($userType); ?>
                </button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId, element) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                element.classList.remove('fa-eye');
                element.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                element.classList.remove('fa-eye-slash');
                element.classList.add('fa-eye');
            }
        }

        // Check password strength
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[$@#&!]+/)) strength += 1;
            
            const width = (strength / 6) * 100;
            strengthBar.style.width = width + '%';
            
            if (width < 33) {
                strengthBar.style.background = '#e74c3c';
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#e74c3c';
            } else if (width < 66) {
                strengthBar.style.background = '#f39c12';
                strengthText.textContent = 'Medium password';
                strengthText.style.color = '#f39c12';
            } else {
                strengthBar.style.background = '#27ae60';
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#27ae60';
            }
        }

        // Check if passwords match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const message = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword === '') {
                message.innerHTML = '';
            } else if (password === confirmPassword) {
                message.innerHTML = '<i class="fas fa-check-circle" style="color: #27ae60;"></i> Passwords match';
                message.style.color = '#27ae60';
            } else {
                message.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i> Passwords do not match';
                message.style.color = '#e74c3c';
            }
        }

        // Form validation
        function validateForm() {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!fullName || !email || !phone || !password || !confirmPassword) {
                alert('Please fill in all required fields');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return false;
            }
            
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            
            const phonePattern = /^\d{10,}$/;
            if (!phonePattern.test(phone.replace(/[^0-9]/g, ''))) {
                alert('Please enter a valid phone number (minimum 10 digits)');
                return false;
            }
            
            // Student-specific validation
            <?php if ($userType == 'student'): ?>
            const course = document.getElementById('course')?.value;
            const semester = document.getElementById('semester')?.value;
            const batchYear = document.getElementById('batch_year')?.value;
            
            if (!course || !semester || !batchYear) {
                alert('Please fill in all academic information');
                return false;
            }
            <?php endif; ?>
            
            // Staff-specific validation
            <?php if ($userType == 'staff'): ?>
            const employeeId = document.getElementById('employee_id')?.value;
            const department = document.getElementById('department')?.value;
            const position = document.getElementById('position')?.value;
            
            if (!employeeId || !department || !position) {
                alert('Please fill in all employment information');
                return false;
            }
            <?php endif; ?>
            
            // Admin-specific validation
            <?php if ($userType == 'admin'): ?>
            const adminLevel = document.getElementById('admin_level')?.value;
            
            if (!adminLevel) {
                alert('Please select admin level');
                return false;
            }
            <?php endif; ?>
            
            return true;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>