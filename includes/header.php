<?php
/**
 * Header Include File
 * 
 * This file contains the HTML header, navigation bar, and meta tags
 * Include this file at the top of every page: require_once 'includes/header.php';
 * 
 * Features:
 * - Responsive navigation bar
 * - User authentication status display
 * - Dynamic page title
 * - Bootstrap 5 styling
 * - Mobile-friendly menu
 * 
 * @author Your Name
 * @version 1.0
 */

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';

// Get page title from $pageTitle variable (set in each page)
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Inkora - Where words come to life. Share your stories, ideas, and experiences with the world.">
    <meta name="author" content="Your Name">
    
    <!-- Page Title -->
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicons - Complete Set for All Devices -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo IMG_URL; ?>/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo IMG_URL; ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo IMG_URL; ?>/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/site.webmanifest">
    <meta name="theme-color" content="#0d6efd">
    <!-- Fallback Favicon -->
<link rel="icon" href="<?php echo BASE_URL; ?>/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="<?php echo BASE_URL; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    
    <!-- Logo Sizing Fix -->
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
        
        .navbar-brand img {
            height: 80px !important; /* Increase logo size */
            width: auto;
            object-fit: contain;
            filter: invert(1);
            mix-blend-mode: screen;
        }
        
        /* Responsive logo sizing */
        @media (max-width: 768px) {
            .navbar-brand img {
                height: 60px !important; /* Smaller on mobile */
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand img {
                height: 50px !important; /* Even smaller on small mobile */
            }
        }
    </style>
    
    <?php
    // Allow pages to add additional CSS files
    if (isset($additionalCSS)) {
        foreach ($additionalCSS as $css) {
            echo '<link rel="stylesheet" href="' . CSS_URL . '/' . $css . '">' . "\n";
        }
    }
    ?>
    
</head>
<body>
    
    <!-- Navigation Bar - Black Theme -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #000000;">
        <div class="container">
            <!-- Logo/Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo url('index.php'); ?>">
                <!-- Inkora Logo -->
                <img src="<?php echo IMG_URL; ?>/logo.png" 
                     alt="Inkora">
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
                        <a class="nav-link" href="<?php echo url('index.php'); ?>">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('posts/index.php'); ?>">
                            <i class="bi bi-book"></i> All Blogs
                        </a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('posts/create.php'); ?>">
                                <i class="bi bi-plus-circle"></i> Create Blog
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('posts/my_posts.php'); ?>">
                                <i class="bi bi-journal-check"></i> My Blogs
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-3" action="<?php echo url('api/search.php'); ?>" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search blogs..." 
                           aria-label="Search" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                
                <!-- Right Side Menu (User/Auth) -->
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <!-- Logged In User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo upload('avatar', $_SESSION['profile_picture']); ?>" 
                                     alt="Profile" class="rounded-circle me-2" width="32" height="32"
                                     style="object-fit: cover;">
                                <span><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('profile/view.php'); ?>">
                                        <i class="bi bi-person"></i> My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('profile/edit.php'); ?>">
                                        <i class="bi bi-pencil"></i> Edit Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('posts/my_posts.php'); ?>">
                                        <i class="bi bi-journal-text"></i> My Blogs
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                
                                <?php if (isAdmin()): ?>
                                    <li>
                                        <a class="dropdown-item text-warning" href="<?php echo url('admin/index.php'); ?>">
                                            <i class="bi bi-shield-lock"></i> Admin Panel
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo url('auth/logout.php'); ?>">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Not Logged In Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/login.php'); ?>">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-light ms-2" href="<?php echo url('auth/register.php'); ?>">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages Container -->
    <div class="container mt-3">
        <?php echo displayFlashMessage(); ?>
    </div>
    
    <!-- Main Content Container (opened here, closed in footer.php) -->
    <main class="container my-4">