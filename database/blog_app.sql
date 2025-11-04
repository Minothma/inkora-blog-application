-- ========================================
-- BLOG APPLICATION DATABASE SCHEMA
-- ========================================
-- Database: blog_app_db
-- Version: 1.0
-- Description: Complete database structure for blog application
-- Author: Your Name
-- Date: 2025
-- ========================================

-- Set SQL mode and charset
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- ========================================
-- 1. USERS TABLE
-- ========================================
-- Stores user account information
-- Includes authentication and profile data

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password using bcrypt',
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `profile_picture` VARCHAR(255) DEFAULT 'default-avatar.png',
  `bio` TEXT DEFAULT NULL COMMENT 'User biography/description',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores user account information';


-- ========================================
-- 2. BLOG_POSTS TABLE
-- ========================================
-- Stores all blog posts/articles
-- Includes content, metadata, and publication status

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'Author of the blog post',
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly version of title',
  `content` TEXT NOT NULL COMMENT 'Blog post content (can be HTML)',
  `featured_image` VARCHAR(255) DEFAULT NULL COMMENT 'Main image for the blog post',
  `excerpt` TEXT DEFAULT NULL COMMENT 'Short summary/preview of the post',
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  `views` INT(11) NOT NULL DEFAULT 0 COMMENT 'Number of times post was viewed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at` DESC),
  FULLTEXT INDEX `idx_search` (`title`, `content`) COMMENT 'For full-text search'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores blog posts';


-- ========================================
-- 3. COMMENTS TABLE
-- ========================================
-- Stores comments on blog posts
-- Supports nested comments (optional for future)

CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `blog_post_id` INT(11) NOT NULL COMMENT 'Blog post this comment belongs to',
  `user_id` INT(11) NOT NULL COMMENT 'User who wrote the comment',
  `comment` TEXT NOT NULL COMMENT 'Comment content',
  `parent_id` INT(11) DEFAULT NULL COMMENT 'For nested/threaded comments (optional)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_blog_post_id` (`blog_post_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_parent_id` (`parent_id`),
  INDEX `idx_created_at` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores comments on blog posts';


-- ========================================
-- 4. REACTIONS TABLE
-- ========================================
-- Stores reactions/likes on blog posts
-- One user can have only one reaction per post (can be changed)

CREATE TABLE IF NOT EXISTS `reactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `blog_post_id` INT(11) NOT NULL COMMENT 'Blog post being reacted to',
  `user_id` INT(11) NOT NULL COMMENT 'User who reacted',
  `reaction_type` ENUM('like', 'love', 'wow', 'sad', 'angry') NOT NULL DEFAULT 'like',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_post_reaction` (`blog_post_id`, `user_id`) COMMENT 'One reaction per user per post',
  FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_blog_post_id` (`blog_post_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_reaction_type` (`reaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores reactions on blog posts';


-- ========================================
-- 5. SESSIONS TABLE
-- ========================================
-- Stores active user sessions for better security
-- Helps track logged-in users and prevent session hijacking

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL COMMENT 'Session ID',
  `user_id` INT(11) NOT NULL COMMENT 'User who owns this session',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP address of user',
  `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'Browser information',
  `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores user sessions';


-- ========================================
-- 6. CATEGORIES TABLE (Optional - for future)
-- ========================================
-- Stores blog post categories
-- Helps organize blog posts

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores blog categories';


-- ========================================
-- 7. POST_CATEGORIES TABLE (Optional - for future)
-- ========================================
-- Junction table for many-to-many relationship
-- One post can have multiple categories

CREATE TABLE IF NOT EXISTS `post_categories` (
  `post_id` INT(11) NOT NULL,
  `category_id` INT(11) NOT NULL,
  PRIMARY KEY (`post_id`, `category_id`),
  FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Links posts to categories';


-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Insert default admin user
-- Username: admin
-- Password: Admin@123 (CHANGE THIS AFTER FIRST LOGIN!)
-- Password hash is for: Admin@123
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `bio`) VALUES
('admin', 'admin@blogapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator'),
('johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Technology enthusiast and blogger');

-- Insert sample categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `description`) VALUES
('Technology', 'technology', 'Articles about technology and innovation'),
('Lifestyle', 'lifestyle', 'Lifestyle tips and tricks'),
('Travel', 'travel', 'Travel experiences and guides'),
('Food', 'food', 'Food recipes and reviews'),
('Health', 'health', 'Health and wellness articles');

-- Insert sample blog post
INSERT IGNORE INTO `blog_posts` (`user_id`, `title`, `slug`, `content`, `excerpt`, `status`, `views`) VALUES
(1, 'Welcome to My Blog', 'welcome-to-my-blog', '<p>This is my first blog post. Welcome to my blogging platform!</p><p>Here I will share my thoughts, experiences, and knowledge about various topics.</p>', 'Welcome to my new blogging platform. Read my first post!', 'published', 0);


-- ========================================
-- IMPORTANT NOTES
-- ========================================
-- 1. Default admin password is: Admin@123 (CHANGE IT!)
-- 2. Default user password is: Admin@123 (for testing)
-- 3. All passwords are hashed using bcrypt (password_hash in PHP)
-- 4. Foreign keys ensure data integrity (cascade deletes)
-- 5. Indexes improve query performance
-- 6. Use FULLTEXT index for blog post search
-- 7. The 'slug' fields are for SEO-friendly URLs
-- 8. Sessions table helps with security and session management
-- 9. Categories tables are optional (for future enhancement)
-- 10. Remember to backup your database regularly!


-- ========================================
-- USEFUL QUERIES FOR TESTING
-- ========================================

-- Get all published blog posts with author info:
-- SELECT bp.*, u.username, u.profile_picture 
-- FROM blog_posts bp 
-- JOIN users u ON bp.user_id = u.id 
-- WHERE bp.status = 'published' 
-- ORDER BY bp.created_at DESC;

-- Get comment count for each post:
-- SELECT bp.id, bp.title, COUNT(c.id) as comment_count 
-- FROM blog_posts bp 
-- LEFT JOIN comments c ON bp.id = c.blog_post_id 
-- GROUP BY bp.id;

-- Get reaction count for each post:
-- SELECT bp.id, bp.title, COUNT(r.id) as reaction_count 
-- FROM blog_posts bp 
-- LEFT JOIN reactions r ON bp.id = r.blog_post_id 
-- GROUP BY bp.id;

-- ========================================
-- END OF DATABASE SCHEMA
-- ========================================