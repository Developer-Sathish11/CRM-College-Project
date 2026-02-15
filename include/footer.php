
    <style>
     

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
            --footer-bg: #1a252f;
            --footer-text: #95a5a6;
        }


        /* Footer Styles */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 60px 0 0 0;
            margin-top: auto;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section {
            padding: 0 15px;
        }

        .footer-section h3 {
            color: white;
            font-size: 20px;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
            position: relative;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary);
        }

        .footer-about p {
            margin-bottom: 20px;
            font-size: 15px;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: var(--footer-text);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .footer-links a:hover {
            color: var(--primary);
            padding-left: 5px;
        }

        .footer-links i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .footer-contact p {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .footer-contact i {
            margin-right: 15px;
            color: var(--primary);
            width: 20px;
            text-align: center;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }

        .footer-newsletter p {
            margin-bottom: 20px;
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
        }

        .newsletter-form input {
            flex: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            font-family: 'Poppins', sans-serif;
        }

        .newsletter-form input::placeholder {
            color: var(--footer-text);
        }

        .newsletter-form button {
            padding: 12px 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .newsletter-form button:hover {
            background-color: #2980b9;
        }

        .footer-bottom {
            background-color: #141e27;
            padding: 25px 0;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .copyright p {
            margin-bottom: 0;
            font-size: 14px;
        }

        .footer-menu {
            display: flex;
            gap: 20px;
        }

        .footer-menu a {
            color: var(--footer-text);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .footer-menu a:hover {
            color: var(--primary);
        }

        .quick-stats {
            display: flex;
            justify-content: space-around;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--footer-text);
        }

       
        /* Responsive Design */
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-bottom-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .quick-stats {
                flex-wrap: wrap;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .footer-section {
                padding: 0;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
            }
        }

        @media (max-width: 480px) {
            .footer-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .stat-item {
                flex: 0 0 50%;
                margin-bottom: 15px;
            }
        }

    </style>
</head>
<body>
    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-content">
                <!-- About Section -->
                <div class="footer-section footer-about">
                    <h3>College CRM</h3>
                    <p>A comprehensive Customer Relationship Management system designed specifically for educational institutions to streamline operations, enhance communication, and improve student engagement.</p>
                    
                    <div class="quick-stats">
                        <div class="stat-item">
                            <span class="stat-number" id="stat-students">1,245</span>
                            <span class="stat-label">Students</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" id="stat-staff">86</span>
                            <span class="stat-label">Staff</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" id="stat-courses">42</span>
                            <span class="stat-label">Courses</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-section footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Dashboard</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Student Management</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Staff Directory</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Course Catalog</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Attendance Tracking</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Fees Management</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Reports & Analytics</a></li>
                    </ul>
                </div>

                <!-- Contact Information -->
                <div class="footer-section footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Education Street, Campus City, CC 12345</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@collegecrm.edu</p>
                    <p><i class="fas fa-clock"></i> Mon - Fri: 8:00 AM - 6:00 PM</p>
                    
                    <div class="social-links">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Newsletter -->
                <div class="footer-section footer-newsletter">
                    <h3>Newsletter</h3>
                    <p>Subscribe to our newsletter to receive updates on new features, announcements, and educational insights.</p>
                    
                    <form class="newsletter-form" id="newsletterForm">
                        <input type="email" placeholder="Your email address" required>
                        <button type="submit">Subscribe</button>
                    </form>
                    
                    <p style="font-size: 12px; margin-top: 10px;">We respect your privacy. Unsubscribe at any time.</p>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; 2023 College CRM System. All rights reserved.</p>
                </div>
                
                <div class="footer-menu">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                    <a href="#">Sitemap</a>
                    <a href="#">Help Center</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>