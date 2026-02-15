<?php
// admin/staff_dashboard.php
require_once 'admin/config/db.php';
require_once 'admin/config/session.php';

$database = new Database();
$session = new SessionManager($database);

// Only staff can access
$session->requireLogin(['staff']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Teacher Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f7fc;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #2c3e50, #1e2b3a);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .logo span {
            background: #ffd700;
            color: #2c3e50;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .notifications {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            padding: 5px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 40px;
            transition: background 0.3s;
        }

        .profile:hover {
            background: rgba(255,255,255,0.2);
        }

        .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffd700;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
        }

        .profile-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .profile-role {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        /* Main Container */
        .container {
            display: grid;
            grid-template-columns: 260px 1fr 320px;
            gap: 1.5rem;
            padding: 1.5rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Sidebar Styles */
        .sidebar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 90px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            margin: 0.3rem 0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            color: #555;
        }

        .menu-item:hover {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .menu-item.active {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            color: white;
            box-shadow: 0 4px 10px rgba(25, 118, 210, 0.3);
        }

        .menu-item i {
            width: 24px;
            font-size: 1.2rem;
        }

        .menu-divider {
            height: 1px;
            background: #eee;
            margin: 1rem 0;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #1976d2, #0d47a1);
            color: white;
            padding: 1.8rem;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(25, 118, 210, 0.3);
        }

        .welcome-text h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .date-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            font-size: 0.95rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-card {
            background: white;
            padding: 1.2rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            background: #e3f2fd;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #1976d2;
        }

        .action-info h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.2rem;
        }

        .action-info p {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }

        /* Today's Schedule */
        .schedule-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            font-size: 1.3rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all {
            color: #1976d2;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #1976d2;
        }

        .schedule-time {
            min-width: 100px;
            font-weight: 600;
            color: #1976d2;
        }

        .schedule-info {
            flex: 1;
        }

        .schedule-info h4 {
            color: #333;
            margin-bottom: 0.2rem;
        }

        .schedule-info p {
            color: #666;
            font-size: 0.85rem;
        }

        .schedule-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-ongoing {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-upcoming {
            background: #fff3e0;
            color: #f57c00;
        }

        /* Classes Grid */
        .classes-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.2rem;
            margin-top: 1rem;
        }

        .class-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.2rem;
            transition: transform 0.3s;
            border: 1px solid #eee;
        }

        .class-card:hover {
            transform: translateY(-3px);
            border-color: #1976d2;
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .class-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }

        .class-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .class-details {
            margin-bottom: 1rem;
        }

        .class-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }

        .class-progress {
            margin-top: 1rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .progress-bar {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #1976d2;
            border-radius: 3px;
        }

        /* Right Sidebar */
        .right-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Pending Tasks */
        .pending-tasks {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .task-filters {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .filter-btn {
            padding: 4px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: none;
            cursor: pointer;
            font-size: 0.8rem;
            color: #666;
        }

        .filter-btn.active {
            background: #1976d2;
            color: white;
            border-color: #1976d2;
        }

        .task-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #1976d2;
            border-radius: 5px;
            cursor: pointer;
        }

        .task-checkbox.checked {
            background: #1976d2;
            position: relative;
        }

        .task-checkbox.checked::after {
            content: "✓";
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
        }

        .task-content {
            flex: 1;
        }

        .task-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.2rem;
        }

        .task-meta {
            font-size: 0.75rem;
            color: #999;
            display: flex;
            gap: 1rem;
        }

        .task-priority {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .priority-high {
            background: #ffebee;
            color: #c62828;
        }

        .priority-medium {
            background: #fff3e0;
            color: #ef6c00;
        }

        /* Recent Submissions */
        .recent-submissions {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .submission-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .student-avatar {
            width: 35px;
            height: 35px;
            background: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #1976d2;
        }

        .submission-info {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.2rem;
        }

        .assignment-name {
            font-size: 0.8rem;
            color: #666;
        }

        .submission-time {
            font-size: 0.7rem;
            color: #4caf50;
        }

        /* Attendance Overview */
        .attendance-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .attendance-stats {
            display: flex;
            justify-content: space-around;
            margin: 1rem 0;
        }

        .stat-circle {
            text-align: center;
        }

        .circle-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1976d2;
        }

        .circle-label {
            font-size: 0.8rem;
            color: #666;
        }

        .attendance-list {
            margin-top: 1rem;
        }

        .attendance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .attendance-item:last-child {
            border-bottom: none;
        }

        .att-percent {
            font-weight: 600;
            color: #2e7d32;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                grid-template-columns: 220px 1fr;
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
                padding: 1rem;
            }
            
            .profile-section {
                width: 100%;
                justify-content: center;
            }
            
            .welcome-card {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-content > * {
            animation: slideIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="logo">
            <h2>📚 Staff Portal</h2>
            <span>Teacher</span>
        </div>
        <div class="profile-section">
            <div class="notifications">
                <span>🔔</span>
                <span class="badge">5</span>
            </div>
            <div class="profile">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='20' fill='%23333'/%3E%3Ctext x='20' y='25' text-anchor='middle' fill='%23fff' font-size='16' font-family='Arial'%3E👨‍🏫%3C/text%3E" alt="Profile">
                <div class="profile-info">
                    <span class="profile-name">Dr. Suresh Kumar</span>
                    <span class="profile-role">Mathematics Teacher</span>
                </div>
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
                <span>My Classes</span>
            </div>
            <div class="menu-item">
                <span>📝</span>
                <span>Assignments</span>
                <span style="margin-left: auto; background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">8</span>
            </div>
            <div class="menu-item">
                <span>📊</span>
                <span>Grades</span>
            </div>
            <div class="menu-item">
                <span>👥</span>
                <span>Students</span>
            </div>
            <div class="menu-item">
                <span>📅</span>
                <span>Timetable</span>
            </div>
            <div class="menu-item">
                <span>📋</span>
                <span>Attendance</span>
            </div>
            
            <div class="menu-divider"></div>
            
            <div class="menu-item">
                <span>📁</span>
                <span>Study Materials</span>
            </div>
            <div class="menu-item">
                <span>💬</span>
                <span>Messages</span>
                <span style="margin-left: auto; background: #1976d2; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">3</span>
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
                    <h1>Good Morning, Dr. Suresh! 👨‍🏫</h1>
                    <p>You have 3 classes today · 28 students present</p>
                </div>
                <div class="date-badge">
                    📅 Saturday, February 14, 2026
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon">📝</div>
                    <div class="action-info">
                        <h3>Assignments to Check</h3>
                        <p>12 Pending</p>
                    </div>
                </div>
                <div class="action-card">
                    <div class="action-icon">👥</div>
                    <div class="action-info">
                        <h3>Total Students</h3>
                        <p>128</p>
                    </div>
                </div>
                <div class="action-card">
                    <div class="action-icon">📊</div>
                    <div class="action-info">
                        <h3>Average Attendance</h3>
                        <p>86%</p>
                    </div>
                </div>
                <div class="action-card">
                    <div class="action-icon">📅</div>
                    <div class="action-info">
                        <h3>Classes Today</h3>
                        <p>5 Periods</p>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="schedule-section">
                <div class="section-header">
                    <h2>📋 Today's Schedule</h2>
                    <a href="#" class="view-all">View Full Timetable →</a>
                </div>
                <div class="schedule-list">
                    <div class="schedule-item">
                        <div class="schedule-time">08:30 - 09:30</div>
                        <div class="schedule-info">
                            <h4>Mathematics - Grade 10A</h4>
                            <p>Chapter 8: Quadratic Equations · Room 204</p>
                        </div>
                        <span class="schedule-status status-ongoing">Ongoing</span>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-time">09:45 - 10:45</div>
                        <div class="schedule-info">
                            <h4>Mathematics - Grade 10B</h4>
                            <p>Chapter 8: Quadratic Equations · Room 205</p>
                        </div>
                        <span class="schedule-status status-upcoming">Next</span>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-time">11:00 - 12:00</div>
                        <div class="schedule-info">
                            <h4>Statistics - Grade 12A</h4>
                            <p>Probability Distribution · Room 301</p>
                        </div>
                        <span class="schedule-status status-upcoming">Upcoming</span>
                    </div>
                    <div class="schedule-item">
                        <div class="schedule-time">13:30 - 14:30</div>
                        <div class="schedule-info">
                            <h4>Mathematics - Grade 9C</h4>
                            <p>Linear Equations · Room 102</p>
                        </div>
                        <span class="schedule-status status-upcoming">After Lunch</span>
                    </div>
                </div>
            </div>

            <!-- My Classes -->
            <div class="classes-section">
                <div class="section-header">
                    <h2>📚 My Classes</h2>
                    <a href="#" class="view-all">Manage Classes →</a>
                </div>
                <div class="classes-grid">
                    <div class="class-card">
                        <div class="class-header">
                            <span class="class-name">Grade 10A</span>
                            <span class="class-badge">Mathematics</span>
                        </div>
                        <div class="class-details">
                            <div class="class-detail">👥 42 Students</div>
                            <div class="class-detail">📊 Avg. Performance: B+</div>
                            <div class="class-detail">📝 Pending Tasks: 5</div>
                        </div>
                        <div class="class-progress">
                            <div class="progress-header">
                                <span>Syllabus Completion</span>
                                <span>65%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 65%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="class-card">
                        <div class="class-header">
                            <span class="class-name">Grade 10B</span>
                            <span class="class-badge">Mathematics</span>
                        </div>
                        <div class="class-details">
                            <div class="class-detail">👥 38 Students</div>
                            <div class="class-detail">📊 Avg. Performance: A-</div>
                            <div class="class-detail">📝 Pending Tasks: 3</div>
                        </div>
                        <div class="class-progress">
                            <div class="progress-header">
                                <span>Syllabus Completion</span>
                                <span>58%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 58%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="class-card">
                        <div class="class-header">
                            <span class="class-name">Grade 12A</span>
                            <span class="class-badge">Statistics</span>
                        </div>
                        <div class="class-details">
                            <div class="class-detail">👥 35 Students</div>
                            <div class="class-detail">📊 Avg. Performance: A</div>
                            <div class="class-detail">📝 Pending Tasks: 2</div>
                        </div>
                        <div class="class-progress">
                            <div class="progress-header">
                                <span>Syllabus Completion</span>
                                <span>72%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 72%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="class-card">
                        <div class="class-header">
                            <span class="class-name">Grade 9C</span>
                            <span class="class-badge">Mathematics</span>
                        </div>
                        <div class="class-details">
                            <div class="class-detail">👥 45 Students</div>
                            <div class="class-detail">📊 Avg. Performance: B</div>
                            <div class="class-detail">📝 Pending Tasks: 4</div>
                        </div>
                        <div class="class-progress">
                            <div class="progress-header">
                                <span>Syllabus Completion</span>
                                <span>45%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 45%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Pending Tasks -->
            <div class="pending-tasks">
                <h2 style="margin-bottom: 1rem;">✅ Pending Tasks</h2>
                <div class="task-filters">
                    <button class="filter-btn active">All</button>
                    <button class="filter-btn">Teaching</button>
                    <button class="filter-btn">Admin</button>
                    <button class="filter-btn">Personal</button>
                </div>
                <div class="task-item">
                    <div class="task-checkbox"></div>
                    <div class="task-content">
                        <div class="task-title">Grade 10 Mathematics Papers</div>
                        <div class="task-meta">
                            <span>📅 Due: Today</span>
                            <span class="task-priority priority-high">High Priority</span>
                        </div>
                    </div>
                </div>
                <div class="task-item">
                    <div class="task-checkbox"></div>
                    <div class="task-content">
                        <div class="task-title">Parent-Teacher Meeting Prep</div>
                        <div class="task-meta">
                            <span>📅 Due: Tomorrow</span>
                            <span class="task-priority priority-medium">Medium</span>
                        </div>
                    </div>
                </div>
                <div class="task-item">
                    <div class="task-checkbox"></div>
                    <div class="task-content">
                        <div class="task-title">Create Monthly Test Paper</div>
                        <div class="task-meta">
                            <span>📅 Due: Feb 20</span>
                            <span class="task-priority priority-high">High Priority</span>
                        </div>
                    </div>
                </div>
                <div class="task-item">
                    <div class="task-checkbox checked"></div>
                    <div class="task-content">
                        <div class="task-title">Submit Attendance Report</div>
                        <div class="task-meta">
                            <span>✅ Completed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Submissions -->
            <div class="recent-submissions">
                <h2 style="margin-bottom: 1rem;">📥 Recent Submissions</h2>
                <div class="submission-item">
                    <div class="student-avatar">P</div>
                    <div class="submission-info">
                        <div class="student-name">Priya Sharma</div>
                        <div class="assignment-name">Mathematics Assignment 5</div>
                        <div class="submission-time">Submitted 5 min ago</div>
                    </div>
                </div>
                <div class="submission-item">
                    <div class="student-avatar">R</div>
                    <div class="submission-info">
                        <div class="student-name">Rahul Kumar</div>
                        <div class="assignment-name">Quadratic Equations</div>
                        <div class="submission-time">Submitted 15 min ago</div>
                    </div>
                </div>
                <div class="submission-item">
                    <div class="student-avatar">A</div>
                    <div class="submission-info">
                        <div class="student-name">Anjali Singh</div>
                        <div class="assignment-name">Statistics Project</div>
                        <div class="submission-time">Submitted 1 hour ago</div>
                    </div>
                </div>
                <div class="submission-item">
                    <div class="student-avatar">V</div>
                    <div class="submission-info">
                        <div class="student-name">Vikram Patel</div>
                        <div class="assignment-name">Practice Worksheet</div>
                        <div class="submission-time">Submitted 2 hours ago</div>
                    </div>
                </div>
            </div>

            <!-- Attendance Overview -->
            <div class="attendance-card">
                <h2 style="margin-bottom: 1rem;">📊 Today's Attendance</h2>
                <div class="attendance-stats">
                    <div class="stat-circle">
                        <div class="circle-value">124</div>
                        <div class="circle-label">Present</div>
                    </div>
                    <div class="stat-circle">
                        <div class="circle-value">4</div>
                        <div class="circle-label">Absent</div>
                    </div>
                    <div class="stat-circle">
                        <div class="circle-value">97%</div>
                        <div class="circle-label">Rate</div>
                    </div>
                </div>
                <div class="attendance-list">
                    <div class="attendance-item">
                        <span>Grade 10A</span>
                        <span class="att-percent">98%</span>
                    </div>
                    <div class="attendance-item">
                        <span>Grade 10B</span>
                        <span class="att-percent">95%</span>
                    </div>
                    <div class="attendance-item">
                        <span>Grade 12A</span>
                        <span class="att-percent">100%</span>
                    </div>
                    <div class="attendance-item">
                        <span>Grade 9C</span>
                        <span class="att-percent">94%</span>
                    </div>
                </div>
                <button style="width: 100%; margin-top: 1rem; padding: 0.8rem; background: #1976d2; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Mark Attendance
                </button>
            </div>

            <!-- Quick Note -->
            <div style="background: linear-gradient(135deg, #f5f7fa, #e8ecf1); border-radius: 15px; padding: 1.5rem;">
                <h3 style="margin-bottom: 0.5rem;">📝 Quick Note</h3>
                <textarea placeholder="Type your notes here..." style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; margin: 0.5rem 0; resize: vertical;" rows="3"></textarea>
                <button style="background: #2c3e50; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">Save Note</button>
            </div>
        </aside>
    </div>
</body>
</html>