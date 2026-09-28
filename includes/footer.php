<?php
/**
 * Footer Include File
 * 
 * Theme: Nordic Slate & Deep Indigo
 */
?>

    </main>
    <!-- End of Main Content Container -->
    
    <!-- Professional Nordic Slate Footer -->
    <footer class="mt-auto" style="background-color: #0F172A; color: #94A3B8; border-top: 1px solid #1E293B; font-family: 'Plus Jakarta Sans', sans-serif;">
        <div class="container py-5">
            <!-- Main Footer Content -->
            <div class="row g-4">
                
                <!-- About Inkora -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%); display: flex; align-items: center; justify-content: center; margin-right: 12px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);">
                            <i class="bi bi-feather text-white" style="font-size: 1.25rem;"></i>
                        </div>
                        <span style="color: #FFFFFF; font-weight: 700; font-size: 1.4rem; letter-spacing: -0.02em;">Inkora</span>
                    </div>
                    <p style="color: #94A3B8; font-size: 0.925rem; line-height: 1.7; max-width: 320px;">
                        A thoughtful space for curious minds. Discover deep insights, essays, and stories crafted by passionate writers worldwide.
                    </p>
                    <div class="d-flex gap-2 mt-3">
                        <a href="https://facebook.com" target="_blank" class="footer-social-btn" aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://twitter.com" target="_blank" class="footer-social-btn" aria-label="Twitter">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a href="https://linkedin.com" target="_blank" class="footer-social-btn" aria-label="LinkedIn">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="https://github.com/Minothma/inkora-blog-application" target="_blank" class="footer-social-btn" aria-label="GitHub">
                            <i class="bi bi-github"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Navigation -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h6 style="color: #FFFFFF; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1.25rem;">
                        Explore
                    </h6>
                    <ul class="list-unstyled" style="line-height: 2.2;">
                        <li><a href="<?php echo BASE_URL; ?>/index.php" class="footer-link">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/posts/index.php" class="footer-link">All Stories</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo BASE_URL; ?>/posts/create.php" class="footer-link">Write Story</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/posts/my_posts.php" class="footer-link">My Stories</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Account & Resources -->
                <div class="col-lg-3 col-md-6 col-6">
                    <h6 style="color: #FFFFFF; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1.25rem;">
                        Account
                    </h6>
                    <ul class="list-unstyled" style="line-height: 2.2;">
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo BASE_URL; ?>/profile/view.php" class="footer-link">Profile Overview</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/profile/edit.php" class="footer-link">Account Settings</a></li>
                            <?php if (isAdmin()): ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>/admin/index.php" class="footer-link text-warning">
                                        <i class="bi bi-shield-lock me-1"></i> Admin Panel
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>/auth/login.php" class="footer-link">Sign In</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/auth/register.php" class="footer-link">Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Newsletter / Platform Info -->
                <div class="col-lg-3 col-md-6">
                    <h6 style="color: #FFFFFF; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1.25rem;">
                        Get in Touch
                    </h6>
                    <p style="color: #94A3B8; font-size: 0.875rem; line-height: 1.6; margin-bottom: 1rem;">
                        Questions or suggestions? Connect directly with our team.
                    </p>
                    <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="d-inline-flex align-items-center gap-2" style="color: #CBD5E1; font-size: 0.875rem; text-decoration: none; padding: 8px 14px; background: #1E293B; border-radius: 8px; border: 1px solid #334155;">
                        <i class="bi bi-envelope-fill text-indigo" style="color: #818CF8;"></i>
                        <span><?php echo ADMIN_EMAIL; ?></span>
                    </a>
                </div>
            </div>
            
            <!-- Footer Bottom Bar -->
            <div class="mt-5 pt-4" style="border-top: 1px solid #1E293B;">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                        <p class="mb-0" style="color: #64748B; font-size: 0.85rem;">
                            &copy; <?php echo date('Y'); ?> <strong style="color: #E2E8F0;">Inkora</strong>. Crafted for writers and thinkers.
                        </p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <span style="color: #64748B; font-size: 0.85rem;">
                            v<?php echo APP_VERSION; ?>
                            <?php if (isDevelopment()): ?>
                                <span class="badge ms-2" style="background-color: #312E81; color: #A5B4FC; font-size: 0.7rem; border: 1px solid #4338CA;">
                                    DEV MODE
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .footer-link {
            color: #94A3B8;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
        }
        .footer-link:hover {
            color: #FFFFFF;
            transform: translateX(3px);
        }
        .footer-social-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #1E293B;
            border: 1px solid #334155;
            color: #94A3B8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.25s ease;
        }
        .footer-social-btn:hover {
            background: #4F46E5;
            border-color: #4F46E5;
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }
    </style>
    
    <!-- Back to Top Button -->
    <button onclick="scrollToTop()" id="backToTopBtn" class="btn" 
            style="display: none; position: fixed; bottom: 24px; right: 24px; z-index: 999; width: 44px; height: 44px; border-radius: 12px; background: #4F46E5; color: #FFFFFF; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4); border: none; transition: all 0.3s;"
            title="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo JS_URL; ?>/main.js"></script>
    
    <?php
    if (isset($additionalJS)) {
        foreach ($additionalJS as $js) {
            echo '<script src="' . JS_URL . '/' . $js . '"></script>' . "\n";
        }
    }
    ?>
    
    <script>
        window.onscroll = function() {
            const btn = document.getElementById("backToTopBtn");
            if (btn) {
                if (document.body.scrollTop > 250 || document.documentElement.scrollTop > 250) {
                    btn.style.display = "flex";
                    btn.style.alignItems = "center";
                    btn.style.justifyContent = "center";
                } else {
                    btn.style.display = "none";
                }
            }
        };
        
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>