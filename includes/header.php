<?php

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
    <meta name="author" content="Inkora">
    
    <!-- Page Title -->
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicons -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts (Plus Jakarta Sans & Lora) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    
    <!-- Header & Navigation Styling -->
    <style>
        :root {
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-serif: 'Lora', Georgia, serif;
            --primary-indigo: #4f46e5;
        }

        body {
            font-family: var(--font-main);
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 0;
        }

        /* Sleek Midnight Slate Navbar */
        .navbar {
            min-height: 70px;
            background: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.15);
        }

        /* Original Inkora Logo Display */
        .navbar-brand {
            padding: 0;
            margin-right: 1.5rem;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            height: 48px !important;
            width: auto;
            object-fit: contain;
            filter: invert(1) brightness(1.2);
            mix-blend-mode: screen;
            transition: transform 0.2s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.03);
        }

        /* Nav links */
        .navbar-nav .nav-link {
            color: #cbd5e1 !important;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0.9rem !important;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.08);
        }

        /* Search Bar (Fixed size across all pages) */
        .navbar .navbar-search-input {
            height: 40px !important;
            padding: 0.375rem 0.9rem !important;
            font-size: 0.875rem !important;
            border-radius: 10px 0 0 10px !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-right: none !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            width: 220px !important;
            transition: all 0.25s ease !important;
        }

        .navbar .navbar-search-input::placeholder {
            color: #94a3b8 !important;
        }

        .navbar .navbar-search-input:focus {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #6366f1 !important;
            box-shadow: none !important;
            width: 260px !important;
        }

        .navbar .navbar-search-btn {
            height: 40px !important;
            padding: 0.375rem 0.85rem !important;
            border-radius: 0 10px 10px 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-left: none !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #cbd5e1 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease !important;
        }

        .navbar .navbar-search-btn:hover {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
            color: #ffffff !important;
        }

        /* User Dropdown */
        .navbar .user-nav-link {
            color: #ffffff !important;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.35rem 0.6rem !important;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .navbar .user-nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .navbar .dropdown-menu {
            background: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3) !important;
            padding: 0.5rem !important;
            min-width: 200px;
        }

        .navbar .dropdown-item {
            color: #cbd5e1 !important;
            font-size: 0.9rem !important;
            padding: 0.55rem 0.85rem !important;
            border-radius: 6px !important;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            transform: translateX(2px);
        }

        .navbar .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.08) !important;
            margin: 0.4rem 0 !important;
        }

        .text-slate-300 { color: #cbd5e1 !important; }
        .text-slate-200 { color: #e2e8f0 !important; }

        @media (max-width: 991px) {
            .navbar .navbar-search-input {
                width: 100% !important;
            }
        }
    </style>
    
    <?php
    if (isset($additionalCSS)) {
        foreach ($additionalCSS as $css) {
            echo '<link rel="stylesheet" href="' . CSS_URL . '/' . $css . '">' . "\n";
        }
    }
    ?>
</head>
<body>
    
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            
            <!-- Original Inkora Logo -->
            <a class="navbar-brand" href="<?php echo url('index.php'); ?>">
                <img src="<?php echo IMG_URL; ?>/logo.png" alt="Inkora">
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2 text-white"></i>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
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
                <form class="d-flex me-3 mb-2 mb-lg-0" action="<?php echo url('api/search.php'); ?>" method="GET">
                    <div class="input-group">
                        <input class="form-control navbar-search-input" type="search" name="q" placeholder="Search blogs..." 
                               aria-label="Search" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button class="btn navbar-search-btn" type="submit" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- User / Auth Menu -->
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="user-nav-link dropdown-toggle" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (!empty($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== DEFAULT_AVATAR): ?>
                                    <img src="<?php echo upload('avatar', $_SESSION['profile_picture']); ?>" 
                                         alt="Profile" class="rounded-circle" width="34" height="34"
                                         style="object-fit: cover; border: 2px solid #818CF8;">
                                <?php else: ?>
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold" 
                                         style="width: 34px; height: 34px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); font-size: 0.85rem;">
                                        <?php echo strtoupper(substr(getCurrentUsername(), 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('profile/view.php'); ?>">
                                        <i class="bi bi-person text-primary"></i> My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('profile/edit.php'); ?>">
                                        <i class="bi bi-pencil text-primary"></i> Edit Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('posts/my_posts.php'); ?>">
                                        <i class="bi bi-journal-text text-primary"></i> My Blogs
                                    </a>
                                </li>
                                
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-warning" href="<?php echo url('admin/index.php'); ?>">
                                            <i class="bi bi-shield-lock-fill"></i> Admin Panel
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo url('auth/logout.php'); ?>">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/login.php'); ?>">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="<?php echo url('auth/register.php'); ?>" style="border-radius: 8px; font-weight: 600;">
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
    
    <!-- Main Content Container -->
    <main class="container my-4">