<?php
// login.php
require_once 'admin/config/db.php';
require_once 'admin/config/session.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    $session = new SessionManager($database);

    $email = $database->escapeString($_POST['email']);
    $password = $_POST['password'];
    $userType = $_POST['user_type'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        // FIXED QUERY - Removed user_id reference from staff table
        $query = "SELECT u.*, 
                  s.student_id, s.course, s.semester,
                  st.id as staff_table_id, st.staff_id, st.department, st.designation,
                  a.admin_id, a.department as admin_dept
                  FROM users u 
                  LEFT JOIN students s ON u.id = s.user_id 
                  LEFT JOIN staff st ON u.id = st.id  /* Changed: Now joining on st.id instead of st.user_id */
                  LEFT JOIN admins a ON u.id = a.user_id 
                  WHERE u.email = ? AND u.user_type = ? AND u.is_active = 1";
        
        // Add error checking
        $stmt = $conn->prepare($query);
        
        // Check if prepare failed
        if ($stmt === false) {
            die('MySQL Error: ' . $conn->error . '<br>Query: ' . $query);
        }
        
        $stmt->bind_param("ss", $email, $userType);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Create user session
                $session->createUserSession($user['id'], $user['user_type'], $user['full_name'], $user['email']);
                
                // Store additional user data in session
                $_SESSION['user_phone'] = $user['phone'];
                
                // Store role-specific IDs
                switch ($user['user_type']) {
                    case 'student':
                        $_SESSION['student_id'] = $user['student_id'];
                        $_SESSION['course'] = $user['course'];
                        $_SESSION['semester'] = $user['semester'];
                        $redirect = 'student_dashboard.php';
                        break;
                    case 'staff':
                        $_SESSION['staff_id'] = $user['staff_id'];
                        $_SESSION['department'] = $user['department'];
                        $_SESSION['designation'] = $user['designation'];
                        $redirect = 'staff_dashboard.php';
                        break;
                    case 'admin':
                        $_SESSION['admin_id'] = $user['admin_id'];
                        $_SESSION['admin_dept'] = $user['admin_dept'];
                        $redirect = 'admin/index.php';
                        break;
                }
                
                header('Location: ' . $redirect);
                exit();
            } else {
                // Record failed login attempt
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                $failedStmt = $conn->prepare("INSERT INTO login_history (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'failed')");
                
                if ($failedStmt !== false) {
                    $failedStmt->bind_param("iss", $user['id'], $ipAddress, $userAgent);
                    $failedStmt->execute();
                    $failedStmt->close();
                }
                
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found or inactive';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College CRM System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .login-left {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-left h1 {
            font-size: 36px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .login-left p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .login-left i {
            font-size: 80px;
            margin-bottom: 30px;
            color: rgba(255,255,255,0.9);
        }

        .feature-list {
            margin-top: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .feature-item i {
            font-size: 20px;
            margin-bottom: 0;
            color: var(--warning);
        }

        .login-right {
            padding: 50px 40px;
            background: white;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: var(--dark);
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
        }

        .user-type-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .user-type {
            padding: 20px 10px;
            border: 2px solid #eee;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-type:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .user-type.active {
            border-color: var(--primary);
            background: rgba(52, 152, 219, 0.1);
        }

        .user-type i {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .user-type h4 {
            margin-bottom: 5px;
            color: var(--dark);
        }

        .user-type p {
            font-size: 12px;
            color: #666;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .input-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .forgot a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .demo-credentials {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .demo-credentials h4 {
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .credential-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            color: #666;
            font-size: 14px;
        }

        .credential-item i {
            width: 20px;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <i class="fas fa-graduation-cap"></i>
            <h1>College CRM System</h1>
            <p>Complete solution for managing students, staff, courses, fees and attendance in one platform.</p>
            
            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Student Management</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Staff Administration</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Course Tracking</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Fee Management</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Attendance System</span>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Please login to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="user-type-selector">
                    <div class="user-type active" data-type="student">
                        <i class="fas fa-user-graduate"></i>
                        <h4>Student</h4>
                        <p>Access courses & attendance</p>
                    </div>
                    <div class="user-type" data-type="staff">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h4>Staff</h4>
                        <p>Manage classes & grades</p>
                    </div>
                    <div class="user-type" data-type="admin">
                        <i class="fas fa-user-shield"></i>
                        <h4>Admin</h4>
                        <p>Full system access</p>
                    </div>
                </div>

                <input type="hidden" name="user_type" id="userType" value="student">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label class="remember">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <div class="forgot">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register as Student</a>
                </div>
            </form>

            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Demo Credentials</h4>
                <div class="credential-item">
                    <i class="fas fa-user-graduate"></i>
                    <span><strong>Student:</strong> john.smith@college.edu / password</span>
                </div>
                <div class="credential-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span><strong>Staff:</strong> robert.taylor@college.edu / password</span>
                </div>
                <div class="credential-item">
                    <i class="fas fa-user-shield"></i>
                    <span><strong>Admin:</strong> admin@college.edu / password</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User type selection
        const userTypes = document.querySelectorAll('.user-type');
        const userTypeInput = document.getElementById('userType');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        userTypes.forEach(type => {
            type.addEventListener('click', function() {
                userTypes.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const userType = this.getAttribute('data-type');
                userTypeInput.value = userType;
                
                // Auto-fill demo credentials based on user type
                if (userType === 'student') {
                    emailInput.value = 'john.smith@college.edu';
                    passwordInput.value = 'password';
                } else if (userType === 'staff') {
                    emailInput.value = 'robert.taylor@college.edu';
                    passwordInput.value = 'password';
                } else if (userType === 'admin') {
                    emailInput.value = 'admin@college.edu';
                    passwordInput.value = 'password';
                }
            });
        });

        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please enter both email and password');
            }
        });

        // Set default student credentials on page load
        window.addEventListener('load', function() {
            emailInput.value = 'john.smith@college.edu';
            passwordInput.value = 'password';
        });
    </script>
</body>
</html>