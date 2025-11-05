<?php
/**
 * Footer Include File
 * 
 * This file contains the HTML footer, scripts, and closing tags
 * Include this file at the bottom of every page: require_once 'includes/footer.php';
 * 
 * Features:
 * - Professional responsive footer design
 * - Copyright information
 * - Social media links
 * - JavaScript includes
 * - Bootstrap scripts
 * 
 * @author Your Name
 * @version 1.0
 */
?>

    </main>
    <!-- End of Main Content Container -->
    
    <!-- Professional Footer -->
    <footer class="mt-auto" style="background-color: #1a1d20; color: #ffffff; border-top: 1px solid #2d3238;">
        <div class="container">
            <!-- Main Footer Content -->
            <div class="row py-4">
                
                <!-- About Inkora -->
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-feather me-2" style="color: #0d6efd; font-size: 1.8rem;"></i>
                        <h5 class="mb-0" style="color: #ffffff; font-weight: 600; font-size: 1.5rem;">Inkora</h5>
                    </div>
                    <p style="color: #adb5bd; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                        A modern blogging platform where writers share stories, ideas, and insights. 
                        Join our community of passionate content creators and readers.
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h6 class="mb-3" style="color: #ffffff; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Quick Links
                    </h6>
                    <ul class="list-unstyled" style="line-height: 2;">
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/index.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/posts/index.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                All Blogs
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>/posts/create.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                    Create Blog
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>/posts/my_posts.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                    My Blogs
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Resources -->
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h6 class="mb-3" style="color: #ffffff; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Resources
                    </h6>
                    <ul class="list-unstyled" style="line-height: 2;">
                        <?php if (isLoggedIn()): ?>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>/profile/view.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                    My Profile
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>/profile/edit.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                    Edit Profile
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                                <li class="mb-2">
                                    <a href="<?php echo BASE_URL; ?>/admin/index.php" style="color: #ffc107; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.paddingLeft='5px'" onmouseout="this.style.paddingLeft='0'">
                                        Admin Panel
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>/auth/login.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                    Login
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>/auth/register.php" style="color: #adb5bd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block;" onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px'" onmouseout="this.style.color='#adb5bd'; this.style.paddingLeft='0'">
                                    Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Get in Touch -->
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h6 class="mb-3" style="color: #ffffff; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Get in Touch
                    </h6>
                    <p style="color: #adb5bd; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                        Have questions or suggestions?
                    </p>
                    <div class="mb-2">
                        <i class="bi bi-envelope me-2" style="color: #0d6efd;"></i>
                        <a href="mailto:<?php echo ADMIN_EMAIL; ?>" style="color: #adb5bd; text-decoration: none; font-size: 0.85rem; transition: color 0.3s; word-break: break-all;" onmouseover="this.style.color='#0d6efd'" onmouseout="this.style.color='#adb5bd'">
                            <?php echo ADMIN_EMAIL; ?>
                        </a>
                    </div>
                </div>
                
                <!-- Follow Us - Social Media -->
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h6 class="mb-3" style="color: #ffffff; font-weight: 700; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Follow Us
                    </h6>
                    <p style="color: #adb5bd; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                        Stay connected with us on social media for updates and news.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" style="color: #6c757d; text-decoration: none; font-size: 1.4rem; transition: color 0.3s;" onmouseover="this.style.color='#1877f2'" onmouseout="this.style.color='#6c757d'" title="Facebook" target="_blank">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" style="color: #6c757d; text-decoration: none; font-size: 1.4rem; transition: color 0.3s;" onmouseover="this.style.color='#E4405F'" onmouseout="this.style.color='#6c757d'" title="Instagram" target="_blank">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" style="color: #6c757d; text-decoration: none; font-size: 1.4rem; transition: color 0.3s;" onmouseover="this.style.color='#0077b5'" onmouseout="this.style.color='#6c757d'" title="LinkedIn" target="_blank">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="#" style="color: #6c757d; text-decoration: none; font-size: 1.4rem; transition: color 0.3s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#6c757d'" title="GitHub" target="_blank">
                            <i class="bi bi-github"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div style="border-top: 1px solid #2d3238;">
                <div class="row py-3 align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0" style="color: #6c757d; font-size: 0.85rem;">
                            &copy; <?php echo date('Y'); ?> <strong style="color: #ffffff;">Inkora</strong>. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <p class="mb-0" style="color: #6c757d; font-size: 0.85rem;">
                            Version <?php echo APP_VERSION; ?>
                            <?php if (isDevelopment()): ?>
                                <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">
                                    DEV MODE
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button onclick="scrollToTop()" id="backToTopBtn" class="btn btn-primary" 
            style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 999; width: 45px; height: 45px; border-radius: 50%; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4); border: none; transition: all 0.3s;"
            onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 16px rgba(13, 110, 253, 0.5)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(13, 110, 253, 0.4)'"
            title="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>
    
    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for easier DOM manipulation) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo JS_URL; ?>/main.js"></script>
    
    <?php
    // Allow pages to add additional JavaScript files
    if (isset($additionalJS)) {
        foreach ($additionalJS as $js) {
            echo '<script src="' . JS_URL . '/' . $js . '"></script>' . "\n";
        }
    }
    ?>
    
    <!-- Inline JavaScript for Common Functions -->
    <script>
        // Back to top button functionality
        window.onscroll = function() {
            const backToTopBtn = document.getElementById("backToTopBtn");
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTopBtn.style.display = "block";
            } else {
                backToTopBtn.style.display = "none";
            }
        };
        
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Confirm delete actions
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this? This action cannot be undone.');
        }
        
        // Show loading spinner on form submit
        function showLoading(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
                    }
                });
            }
        }
    </script>
    
</body>
</html>