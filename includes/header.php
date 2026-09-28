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
    <meta name="author" content="Inkora Publishing">
    
    <!-- Page Title -->
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicons -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts (Plus Jakarta Sans & Lora Serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    
    <!-- Header & Navigation Design System -->
    <style>
        :root {
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-serif: 'Lora', Georgia, serif;
            --primary-indigo: #4F46E5;
            --primary-indigo-hover: #4338CA;
            --midnight-slate: #0F172A;
            --slate-800: #1E293B;
            --slate-300: #CBD5E1;
            --slate-400: #94A3B8;
        }

        body {
            font-family: var(--font-main);
            background-color: #F8FAFC;
            color: #334155;
            margin: 0;
            padding: 0;
        }

        /* Frosted Glass Header */
        .navbar-inkora {
            min-height: 72px;
            background: rgba(15, 23, 42, 0.96) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.25);
            transition: all 0.3s ease;
        }

        /* Brand Logo Emblem & Wordmark */
        .brand-logo-container {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none !important;
            transition: transform 0.2s ease;
        }

        .brand-logo-container:hover {
            transform: scale(1.02);
        }

        .brand-emblem {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 50%, #818CF8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 1.25rem;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.45);
        }

        .brand-text {
            color: #FFFFFF;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .brand-dot {
            color: #818CF8;
        }

        /* Nav links */
        .navbar-nav .nav-link {
            color: #94A3B8 !important;
            font-weight: 600;
            font-size: 0.925rem;
            padding: 0.5rem 0.95rem !important;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .navbar-nav .nav-link:hover {
            color: #FFFFFF !important;
            background: rgba(255, 255, 255, 0.08);
        }

        .navbar-nav .nav-link.active {
            color: #FFFFFF !important;
            background: rgba(79, 70, 229, 0.2);
        }

        /* Write Story Pill Button */
        .btn-nav-write {
            background: #4F46E5;
            color: #FFFFFF !important;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.45rem 1.1rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-nav-write:hover {
            background: #4338CA;
            color: #FFFFFF !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.5);
        }

        /* Search Bar */
        .navbar-search-input {
            height: 40px !important;
            padding: 0.375rem 1rem !important;
            font-size: 0.875rem !important;
            border-radius: 10px 0 0 10px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-right: none !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #FFFFFF !important;
            width: 190px !important;
            transition: all 0.25s ease !important;
        }

        .navbar-search-input::placeholder {
            color: #94A3B8 !important;
        }

        .navbar-search-input:focus {
            background-color: #FFFFFF !important;
            color: #0F172A !important;
            border-color: #6366F1 !important;
            box-shadow: none !important;
            width: 240px !important;
        }

        .navbar-search-input:focus::placeholder {
            color: #64748B !important;
        }

        .navbar-search-btn {
            height: 40px !important;
            padding: 0.375rem 0.9rem !important;
            border-radius: 0 10px 10px 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-left: none !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #94A3B8 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease !important;
        }

        .navbar-search-btn:hover {
            background-color: #4F46E5 !important;
            border-color: #4F46E5 !important;
            color: #FFFFFF !important;
        }

        /* User Avatar Dropdown */
        .nav-avatar-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 4px 12px 4px 6px;
            border-radius: 50px;
            color: #FFFFFF !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .nav-avatar-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .nav-avatar-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #818CF8;
        }

        .nav-avatar-initials {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-menu-dark-custom {
            background: #1E293B !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 14px !important;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35) !important;
            padding: 0.5rem !important;
            min-width: 210px;
        }

        .dropdown-menu-dark-custom .dropdown-item {
            color: #CBD5E1 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            padding: 0.6rem 0.85rem !important;
            border-radius: 8px !important;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.15s ease;
        }

        .dropdown-menu-dark-custom .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #FFFFFF !important;
            transform: translateX(2px);
        }

        .dropdown-menu-dark-custom .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.08) !important;
            margin: 0.4rem 0 !important;
        }

        /* Subtitle high-contrast helper */
        .text-slate-300 {
            color: #CBD5E1 !important;
        }
        .text-slate-200 {
            color: #E2E8F0 !important;
        }
        .text-slate-400 {
            color: #94A3B8 !important;
        }

        @media (max-width: 991px) {
            .navbar-search-input {
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
    <nav class="navbar navbar-expand-lg navbar-dark navbar-inkora sticky-top">
        <div class="container">
            
            <!-- Brand Logo -->
            <a class="brand-logo-container me-3" href="<?php echo url('index.php'); ?>">
                <div class="brand-emblem">
                    <i class="bi bi-feather"></i>
                </div>
                <span class="brand-text">Inkora<span class="brand-dot">.</span></span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2 text-white"></i>
            </button>
            
            <!-- Menu Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php'); ?>">
                            <i class="bi bi-compass"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('posts/index.php'); ?>">
                            <i class="bi bi-journal-richtext"></i> All Stories
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('posts/my_posts.php'); ?>">
                                <i class="bi bi-person-workspace"></i> My Stories
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Bar -->
                <form class="d-flex me-3 mb-2 mb-lg-0" action="<?php echo url('api/search.php'); ?>" method="GET">
                    <div class="input-group">
                        <input class="form-control navbar-search-input" type="search" name="q" placeholder="Search stories..." 
                               aria-label="Search" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button class="btn navbar-search-btn" type="submit" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- Right Action Items -->
                <div class="d-flex align-items-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <!-- Quick Write Button -->
                        <a href="<?php echo url('posts/create.php'); ?>" class="btn-nav-write me-2">
                            <i class="bi bi-plus-lg"></i>
                            <span>Write</span>
                        </a>
                        
                        <!-- User Dropdown Menu -->
                        <div class="dropdown">
                            <a class="nav-avatar-btn dropdown-toggle text-decoration-none" href="#" id="userMenuDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (!empty($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== DEFAULT_AVATAR): ?>
                                    <img src="<?php echo upload('avatar', $_SESSION['profile_picture']); ?>" 
                                         alt="Profile" class="nav-avatar-img">
                                <?php else: ?>
                                    <div class="nav-avatar-initials">
                                        <?php echo strtoupper(substr(getCurrentUsername(), 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="d-none d-sm-inline"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark-custom mt-2" aria-labelledby="userMenuDropdown">
                                <li class="px-3 py-2 border-bottom" style="border-color: rgba(255,255,255,0.08) !important;">
                                    <span class="d-block text-white fw-bold small"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                                    <span class="d-block text-slate-400" style="font-size: 0.75rem;"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span>
                                </li>
                                <li>
                                    <a class="dropdown-item mt-1" href="<?php echo url('profile/view.php'); ?>">
                                        <i class="bi bi-person text-indigo"></i> Profile Overview
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('posts/my_posts.php'); ?>">
                                        <i class="bi bi-journal-text text-indigo"></i> My Stories
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('profile/edit.php'); ?>">
                                        <i class="bi bi-gear text-indigo"></i> Account Settings
                                    </a>
                                </li>
                                
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-warning fw-semibold" href="<?php echo url('admin/index.php'); ?>">
                                            <i class="bi bi-shield-lock-fill text-warning"></i> Admin Panel
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo url('auth/logout.php'); ?>">
                                        <i class="bi bi-box-arrow-right text-danger"></i> Sign Out
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Auth Links -->
                        <a class="nav-link text-slate-300" href="<?php echo url('auth/login.php'); ?>">
                            Sign In
                        </a>
                        <a class="btn btn-nav-write" href="<?php echo url('auth/register.php'); ?>">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages Container -->
    <div class="container mt-3">
        <?php echo displayFlashMessage(); ?>
    </div>
    
    <!-- Main Content Container -->
    <main class="container my-4">