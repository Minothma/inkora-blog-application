<?php

// Include required files if not already included
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../config/session.php';
}
?>

<!-- Logo Sizing Fix CSS -->
<style>
/* Fixed navbar height */
.navbar {
    min-height: 60px; /* Fixed navbar height */
}

/* Logo styling - increase size without affecting navbar */
.navbar-brand {
    padding-top: 0;
    padding-bottom: 0;
    height: 60px; /* Match navbar height */
    display: flex;
    align-items: center;
}

.navbar-brand .logo-icon {
    font-size: 2.5rem; /* Increased from 2rem */
}

.navbar-brand .logo-text {
    font-size: 2.5rem; /* Increased from 2rem */
    letter-spacing: 1px;
    font-weight: 700;
}

/* Reduce logo size on smaller screens */
@media (max-width: 768px) {
    .navbar-brand .logo-icon {
        font-size: 2rem;
    }
    .navbar-brand .logo-text {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .navbar-brand .logo-icon {
        font-size: 1.5rem;
    }
    .navbar-brand .logo-text {
        font-size: 1.5rem;
    }
}
</style>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
    <div class="container">
        
        <!-- Logo/Brand - Responsive -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/index.php">
            <!-- Feather Icon -->
            <i class="bi bi-feather me-2 logo-icon"></i>
            <!-- Text Logo -->
            <strong class="logo-text">Inkora</strong>
        </a>
        
        <!-- Mobile Menu Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            
            <!-- Left Side Menu -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house-door"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/posts/index.php">
                        <i class="bi bi-book"></i> All Blogs
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/posts/create.php">
                            <i class="bi bi-plus-circle"></i> Create Blog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/posts/my_posts.php">
                            <i class="bi bi-journal-check"></i> My Blogs
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- Search Form -->
            <form class="d-flex me-3" action="<?php echo BASE_URL; ?>/api/search.php" method="GET" role="search">
                <div class="input-group">
                    <input class="form-control" 
                           type="search" 
                           name="q" 
                           placeholder="Search blogs..." 
                           aria-label="Search"
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- Right Side Menu (User/Auth) -->
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <!-- Logged In User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" 
                           href="#" 
                           id="userDropdown" 
                           role="button" 
                           data-bs-toggle="dropdown" 
                           aria-expanded="false">
                            <img src="<?php echo upload('avatar', $_SESSION['profile_picture']); ?>" 
                                 alt="Profile" 
                                 class="rounded-circle me-2" 
                                 width="32" 
                                 height="32"
                                 style="object-fit: cover; border: 2px solid white;">
                            <span class="d-none d-lg-inline"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile/view.php">
                                    <i class="bi bi-person"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile/edit.php">
                                    <i class="bi bi-pencil"></i> Edit Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/posts/my_posts.php">
                                    <i class="bi bi-journal-text"></i> My Blogs
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <?php if (isAdmin()): ?>
                                <li>
                                    <a class="dropdown-item text-warning fw-bold" href="<?php echo BASE_URL; ?>/admin/index.php">
                                        <i class="bi bi-shield-lock"></i> Admin Panel
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Not Logged In Menu -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light ms-2" href="<?php echo BASE_URL; ?>/auth/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
        </div>
    </div>
</nav>

<!-- Flash Messages Container (if not in header.php) -->
<?php if (!isset($flashMessagesDisplayed)): ?>
    <div class="container mt-3">
        <?php echo displayFlashMessage(); ?>
    </div>
    <?php $flashMessagesDisplayed = true; ?>
<?php endif; ?>

<!-- Active Page Indicator Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Highlight current page in navigation
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath || 
            currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});
</script>

<?php
/**
 * USAGE:
 * 
 * Include this file after config files:
 * require_once 'includes/navbar.php';
 * 
 * Or use header.php which already includes navigation
 */
?>