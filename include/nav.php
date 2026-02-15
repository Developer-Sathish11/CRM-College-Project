<?php
// include/nav.php
if (session_status() === PHP_SESSION_NONE) {
   
}
?>
<header>
    <div class="header-container">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h1>College <span>CRM</span></h1>
        </div>
        <br>
        <nav>
            <ul>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Logged in navigation - Dashboard link to role-specific page -->
                    <li><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    
                    <?php if($_SESSION['user_type'] == 'admin'): ?>
                        <li><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
                    <?php elseif($_SESSION['user_type'] == 'staff'): ?>
                        <li><a href="staff_dashboard.php" class="nav-link"><i class="fas fa-chalkboard-teacher"></i> Staff Panel</a></li>
                    <?php elseif($_SESSION['user_type'] == 'student'): ?>
                        <li><a href="student_dashboard.php" class="nav-link"><i class="fas fa-user-graduate"></i> Student Panel</a></li>
                    <?php endif; ?>
                    
                    <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <!-- Public navigation - SPA sections -->
                    <li><a href="index.php#dashboard" class="nav-link active" data-page="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="index.php#students" class="nav-link" data-page="students"><i class="fas fa-users"></i> Students</a></li>
                    <li><a href="index.php#staff" class="nav-link" data-page="staff"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                    <li><a href="index.php#courses" class="nav-link" data-page="courses"><i class="fas fa-book"></i> Courses</a></li>
                    <li><a href="index.php#attendance" class="nav-link" data-page="attendance"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
                    <li><a href="index.php#fees" class="nav-link" data-page="fees"><i class="fas fa-money-check-alt"></i> Fees</a></li>
                    <li><a href="index.php#reports" class="nav-link" data-page="reports"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="auth-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <span style="color: white; margin-right: 10px; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a href="logout.php" class="btn btn-accent"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Register</a>
                
            <?php endif; ?>
        </div>
    </div>
</header>