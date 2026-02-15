<?php
// admin/student_dashboard.php
require_once 'admin/config/db.php';
require_once 'admin/config/session.php';

$database = new Database();
$session = new SessionManager($database);

// Only students can access
$session->requireLogin(['student']);

$currentUser = $session->getCurrentUser();
echo "Student Dashboard - Welcome " . $currentUser['name'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f2f5;
        }

        /* Header Styles */
        .header {
            background-color: #1a237e;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo h2 {
            font-size: 1.5rem;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .notifications {
            position: relative;
            cursor: pointer;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff5252;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Main Container */
        .container {
            display: grid;
            grid-template-columns: 250px 1fr 300px;
            gap: 1.5rem;
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Sidebar Styles */
        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem;
            margin: 0.3rem 0;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .menu-item:hover, .menu-item.active {
            background-color: #e8eaf6;
            color: #1a237e;
        }

        .menu-item i {
            width: 20px;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #1a237e, #283593);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .date {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        /* Continue Learning */
        .continue-learning {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .course-progress {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-info {
            flex: 1;
        }

        .progress-info h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #ddd;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #1a237e;
            width: 75%;
        }

        .resume-btn {
            background: #1a237e;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            margin-left: 1rem;
        }

        /* My Courses Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .course-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .course-card:hover {
            transform: translateY(-5px);
        }

        .course-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .course-card h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .course-progress-small {
            margin-top: 0.5rem;
        }

        .progress-percent {
            font-size: 0.8rem;
            color: #666;
        }

        /* Right Sidebar */
        .right-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Announcements */
        .announcements {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .announcement-item {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .announcement-date {
            font-size: 0.8rem;
            color: #666;
        }

        .urgent {
            background: #ffebee;
            padding: 0.8rem;
            border-radius: 5px;
            border-left: 4px solid #ff5252;
        }

        .urgent .announcement-title {
            color: #c62828;
        }

        /* Upcoming Tasks */
        .upcoming-tasks {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .task-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ff5252;
        }

        .task-dot.completed {
            background: #4caf50;
        }

        .task-info {
            flex: 1;
        }

        .task-info h4 {
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 0.2rem;
        }

        .task-info p {
            font-size: 0.8rem;
            color: #666;
        }

        .task-deadline {
            font-size: 0.8rem;
            color: #ff5252;
            font-weight: 600;
        }

        /* Quick Stats */
        .quick-stats {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin: 0.8rem 0;
        }

        .stat-label {
            color: #666;
        }

        .stat-value {
            font-weight: 600;
            color: #1a237e;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 200px 1fr;
            }
            
            .right-sidebar {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .welcome-card {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="logo">
            <h2>🎓 Student Portal</h2>
        </div>
        <div class="profile-section">
            <div class="notifications">
                <span>🔔</span>
                <span class="badge">3</span>
            </div>
            <div class="profile">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='20' fill='%23333'/%3E%3Ctext x='20' y='25' text-anchor='middle' fill='%23fff' font-size='16' font-family='Arial'%3E👤%3C/text%3E" alt="Profile">
                <span>Kumar</span>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <div class="menu-item active">
                <span>🏠</span>
                <span>Dashboard</span>
            </div>
            <div class="menu-item">
                <span>📚</span>
                <span>My Courses</span>
            </div>
            <div class="menu-item">
                <span>📝</span>
                <span>Assignments</span>
            </div>
            <div class="menu-item">
                <span>📅</span>
                <span>Calendar</span>
            </div>
            <div class="menu-item">
                <span>📁</span>
                <span>Study Materials</span>
            </div>
            <div class="menu-item">
                <span>💬</span>
                <span>Messages</span>
                <span style="margin-left: auto; background: #ff5252; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">2</span>
            </div>
            <div class="menu-item">
                <span>⚙️</span>
                <span>Settings</span>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-text">
                    <h1>Welcome back, Kumar! 👋</h1>
                    <p>Ready to continue your learning journey?</p>
                </div>
                <div class="date">
                    📅 Monday, Feb 14, 2026
                </div>
            </div>

            <!-- Continue Learning Section -->
            <div class="continue-learning">
                <h2 class="section-title">Continue Learning</h2>
                <div class="course-progress">
                    <div class="progress-info">
                        <h3>Mathematics - Chapter 3: Calculus</h3>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <span style="font-size: 0.9rem; color: #666;">75% Completed</span>
                    </div>
                    <button class="resume-btn">▶ Resume</button>
                </div>
            </div>

            <!-- My Courses Grid -->
            <div>
                <h2 class="section-title">My Courses</h2>
                <div class="courses-grid">
                    <div class="course-card">
                        <div class="course-icon">📐</div>
                        <h3>Mathematics</h3>
                        <div class="course-progress-small">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 80%;"></div>
                            </div>
                            <span class="progress-percent">80% Complete</span>
                        </div>
                    </div>
                    <div class="course-card">
                        <div class="course-icon">🔬</div>
                        <h3>Science</h3>
                        <div class="course-progress-small">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 45%; background: #ff9800;"></div>
                            </div>
                            <span class="progress-percent">45% Complete</span>
                        </div>
                    </div>
                    <div class="course-card">
                        <div class="course-icon">📖</div>
                        <h3>English</h3>
                        <div class="course-progress-small">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 60%; background: #4caf50;"></div>
                            </div>
                            <span class="progress-percent">60% Complete</span>
                        </div>
                    </div>
                    <div class="course-card">
                        <div class="course-icon">💻</div>
                        <h3>Computer Science</h3>
                        <div class="course-progress-small">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 30%; background: #f44336;"></div>
                            </div>
                            <span class="progress-percent">30% Complete</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Assignments -->
            <div>
                <h2 class="section-title">📝 Pending Assignments</h2>
                <div style="background: white; border-radius: 10px; padding: 1rem;">
                    <div class="task-item">
                        <div class="task-dot"></div>
                        <div class="task-info">
                            <h4>Physics Practical Report</h4>
                            <p>Due: Tomorrow, 5:00 PM</p>
                        </div>
                        <span class="task-deadline">Urgent</span>
                    </div>
                    <div class="task-item">
                        <div class="task-dot"></div>
                        <div class="task-info">
                            <h4>Mathematics Problem Set</h4>
                            <p>Due: Feb 20, 2026</p>
                        </div>
                    </div>
                    <div class="task-item">
                        <div class="task-dot completed"></div>
                        <div class="task-info">
                            <h4>English Essay (Completed)</h4>
                            <p>Submitted on Feb 13</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Announcements Section -->
            <div class="announcements">
                <h2 class="section-title">🔔 Announcements</h2>
                <div class="announcement-item urgent">
                    <div class="announcement-title">⚠️ Holiday Tomorrow</div>
                    <div class="announcement-date">Due to sports event</div>
                </div>
                <div class="announcement-item">
                    <div class="announcement-title">Fee Payment Deadline</div>
                    <div class="announcement-date">Feb 25, 2026</div>
                </div>
                <div class="announcement-item">
                    <div class="announcement-title">Parent-Teacher Meeting</div>
                    <div class="announcement-date">March 1, 2026</div>
                </div>
            </div>

            <!-- Upcoming Tasks -->
            <div class="upcoming-tasks">
                <h2 class="section-title">📅 Upcoming</h2>
                <div class="task-item">
                    <span>📝</span>
                    <div class="task-info">
                        <h4>Math Assignment</h4>
                        <p>Tomorrow</p>
                    </div>
                </div>
                <div class="task-item">
                    <span>📊</span>
                    <div class="task-info">
                        <h4>Science Quiz</h4>
                        <p>Friday</p>
                    </div>
                </div>
                <div class="task-item">
                    <span>🗣️</span>
                    <div class="task-info">
                        <h4>Group Discussion</h4>
                        <p>Next Monday</p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <h2 class="section-title">📊 Quick Stats</h2>
                <div class="stat-item">
                    <span class="stat-label">Pending Tasks</span>
                    <span class="stat-value">3</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Unread Messages</span>
                    <span class="stat-value">2</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Overall Attendance</span>
                    <span class="stat-value">85%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">CGPA</span>
                    <span class="stat-value">8.5/10</span>
                </div>
            </div>

            <!-- Contact Support -->
            <div style="background: #e8eaf6; border-radius: 10px; padding: 1rem; text-align: center;">
                <p style="margin-bottom: 0.5rem;">Need Help?</p>
                <button style="background: #1a237e; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                    💬 Contact Teacher
                </button>
            </div>
        </aside>
    </div>
</body>
</html>