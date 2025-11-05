# Inkora - Modern Blogging Platform

![Inkora Banner](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

A feature-rich, modern blogging platform built with PHP and MySQL. Inkora provides a beautiful, intuitive interface for writers to create, share, and manage their stories with powerful admin tools and engaging user interactions.

## Features

### User Experience
- **Modern Gradient Design** - Stunning cyan-to-purple gradient theme throughout
- **Responsive Layout** - Fully mobile-optimized using Bootstrap 5
- **Rich Text Editor** - TinyMCE integration for advanced content formatting
- **Reading Progress Bar** - Visual reading progress indicator on articles
- **Social Sharing** - Share posts on Facebook, Twitter/X, and LinkedIn
- **Reaction System** - 5 reaction types (Like, Love, Wow, Sad, Angry)
- **Real-time Engagement** - Comments, reactions, and view tracking

### Content Management
- **Create & Edit Posts** - Intuitive post creation with featured images
- **Draft System** - Save posts as drafts before publishing
- **Rich Media Support** - Upload images (JPG, PNG, GIF, WEBP)
- **Auto-slug Generation** - SEO-friendly URLs automatically created
- **Excerpt Management** - Auto-generated or custom excerpts
- **View Analytics** - Track post views and engagement

### User Management
- **Secure Authentication** - Bcrypt password hashing
- **User Profiles** - Customizable profiles with avatars and bios
- **Role-Based Access** - User and Admin roles
- **Password Reset** - Email-based password recovery system
- **Session Management** - Secure session handling with CSRF protection

### Admin Panel
- **Comprehensive Dashboard** - Statistics and activity overview
- **User Management** - View, manage, and moderate users
- **Post Moderation** - Manage all posts with filtering
- **Comment Moderation** - Review and delete comments
- **Role Management** - Promote users to admin

### Security Features
- **CSRF Protection** - Token-based form security
- **SQL Injection Prevention** - PDO prepared statements
- **XSS Protection** - Input sanitization and output escaping
- **Secure File Uploads** - File type and size validation
- **Session Hijacking Prevention** - User agent and IP validation
- **Password Strength Requirements** - Enforced strong passwords

## Technology Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 8.0+** - Relational database
- **PDO** - Database abstraction layer

### Frontend
- **Bootstrap 5.3** - CSS framework
- **Bootstrap Icons** - Icon library
- **TinyMCE 6** - Rich text editor
- **Vanilla JavaScript** - Client-side interactivity

### Development
- **Environment Variables** - `.env` file configuration
- **Error Logging** - Comprehensive error tracking
- **Auto-save Drafts** - LocalStorage draft recovery

## Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (8.0+ recommended)
- **Apache/Nginx**: Web server with mod_rewrite
- **PHP Extensions**:
  - PDO
  - PDO_MySQL
  - mbstring
  - fileinfo
  - session
  - openssl

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Minothma/inkora-blog-application
cd inkora-blog
```

### 2. Create Environment File

Copy the `.env.example` file and configure your settings:

```bash
cp .env.example .env
```

Edit `.env` with your configuration:

```env
# Application Settings
APP_NAME="Inkora"
APP_ENV=development
APP_URL=http://localhost/inkora-blog
APP_TIMEZONE=Asia/Colombo

# Database Configuration
DB_HOST=localhost
DB_NAME=blog_app_db
DB_USER=root
DB_PASS=your_password
DB_CHARSET=utf8mb4

# Security Settings
SESSION_LIFETIME=7200
SESSION_NAME=INKORA_SESSION
COOKIE_SECURE=false
COOKIE_HTTPONLY=true
COOKIE_SAMESITE=Lax

# Admin Credentials
ADMIN_EMAIL=admin@inkora.com
ADMIN_USERNAME=admin

# File Upload Settings
MAX_UPLOAD_SIZE=5242880
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif,webp
MAX_IMAGE_WIDTH=1920
MAX_IMAGE_HEIGHT=1080

# Pagination
POSTS_PER_PAGE=10
COMMENTS_PER_PAGE=20
USERS_PER_PAGE=15

# Logging
LOG_QUERIES=false
```

### 3. Create Database

Execute the SQL schema file to create the database structure:

```bash
mysql -u root -p < database/schema.sql
```

Or manually:

1. Create database:
```sql
CREATE DATABASE blog_app_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import schema:
```sql
USE blog_app_db;
SOURCE database/schema.sql;
```

### 4. Create Password Reset Tokens Table

Add this table to your database (missing from main schema):

```sql
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Configure Web Server

#### Apache (.htaccess)

Create `.htaccess` in the root directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /inkora-blog/
    
    # Redirect to HTTPS (optional)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Prevent directory listing
    Options -Indexes
    
    # Protect config files
    <FilesMatch "^\.env$">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

#### Nginx

Add to your server block:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/inkora-blog;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Protect sensitive files
    location ~ /\.env {
        deny all;
    }

    # Deny access to config files
    location ~ ^/(config|database|includes)/ {
        deny all;
    }
}
```

### 6. Set Permissions

```bash
# Create upload directories
mkdir -p uploads/avatars uploads/blog_images

# Set appropriate permissions
chmod 755 uploads uploads/avatars uploads/blog_images
chmod 644 config/*.php

# Secure .env file
chmod 600 .env
```

### 7. Access the Application

Open your browser and navigate to:
```
http://localhost/inkora-blog/
```

## Default Credentials

### Admin Account
- **Username**: `admin`
- **Email**: `admin@blogapp.com`
- **Password**: `Admin@123`

### Test User Account
- **Username**: `johndoe`
- **Email**: `john@example.com`
- **Password**: `Admin@123`

 **IMPORTANT**: Change these passwords immediately after first login!

## Project Structure

```
inkora-blog/
├── admin/                      # Admin panel
│   ├── index.php              # Admin dashboard
│   ├── users.php              # User management
│   ├── posts.php              # Post management
│   └── comments.php           # Comment moderation
├── api/                       # API endpoints
│   ├── comments.php           # Comment operations
│   ├── reactions.php          # Reaction handling
│   └── search.php             # Search functionality
├── assets/                    # Static assets
│   ├── css/
│   │   └── style.css          # Custom styles
│   ├── js/
│   │   └── main.js            # Custom JavaScript
│   └── images/                # Images and icons
├── auth/                      # Authentication
│   ├── login.php              # Login page
│   ├── register.php           # Registration page
│   ├── logout.php             # Logout handler
│   ├── forgot_password.php    # Password reset request
│   └── reset_password.php     # Password reset form
├── config/                    # Configuration files
│   ├── database.php           # Database connection
│   ├── constants.php          # Application constants
│   ├── session.php            # Session management
│   └── errors.php             # Error handling
├── database/                  # Database files
│   └── database.sql           # Database schema
├── includes/                  # Reusable components
│   ├── error_handler.php      # Error handling 
│   ├── header.php             # Header template
│   ├── footer.php             # Footer template
│   ├── navbar.php             # Navigation bar
│   └── helpers.php            # Helper functions
├── posts/                     # Blog post management
│   ├── index.php              # All posts listing
│   ├── view.php               # Single post view
│   ├── create.php             # Create new post
│   ├── edit.php               # Edit post
│   ├── delete.php             # Delete post
│   └── my_posts.php           # User's posts
├── profile/                   # User profile
│   ├── view.php               # View profile
│   ├── edit.php               # Edit profile
│   └── change_password.php    # Change password
├── uploads/                   # Uploaded files
│   ├── avatars/               # User avatars
│   └── blog_images/           # Blog post images
├── .env
├── .env.example               # Environment template
├── .gitignore                 # Git ignore file
├── favicon.ico.png
├── browserconfig.xml
├── index.php                  # Home page
└── README.md                  # This file
```

## Key Features Explained

### 1. Rich Text Editor

Posts use TinyMCE for advanced formatting:
- Bold, italic, underline
- Headers (H1-H6)
- Lists (ordered/unordered)
- Links and images
- Code blocks
- Blockquotes

### 2. Reaction System

Five reaction types with emoji support:
- 👍 Like
- ❤️ Love
- 😮 Wow
- 😢 Sad
- 😠 Angry

### 3. Comment System

Threaded comments with:
- Real-time posting
- Author/post owner can delete
- User avatars
- Timestamp display

### 4. Search Functionality

Full-text search across:
- Post titles
- Post content
- Results highlighting
- Pagination support

### 5. Admin Dashboard

Comprehensive statistics:
- Total users/posts/comments
- Recent activity
- User management
- Content moderation

## 🔧 Configuration

### Application Settings

Edit `config/constants.php` to customize:

```php
// Pagination
define('POSTS_PER_PAGE', 10);
define('COMMENTS_PER_PAGE', 20);

// Upload limits
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Password requirements
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Session timeout
define('SESSION_LIFETIME', 7200); // 2 hours
```

### Email Configuration

For password reset emails, configure your SMTP settings in `auth/forgot_password.php`:

```php
// Example using PHPMailer (recommended)
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

## Troubleshooting

### Database Connection Issues

```php
// Check config/database.php
// Verify credentials in .env file
// Ensure MySQL service is running
```

### File Upload Errors

```bash
# Check PHP settings
upload_max_filesize = 10M
post_max_size = 10M
file_uploads = On

# Check directory permissions
chmod 755 uploads/
```

### Session Issues

```php
// Clear browser cookies
// Check session.php configuration
// Verify SESSION_LIFETIME setting
```

### TinyMCE Not Loading

```html
<!-- Verify CDN in create.php and edit.php -->
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>
```

## Security Best Practices

1. **Change Default Passwords** immediately
2. **Use HTTPS** in production
3. **Set `APP_ENV=production`** in .env
4. **Disable Error Display** in production
5. **Regular Backups** of database and uploads
6. **Keep PHP Updated** to latest stable version
7. **Use Strong Passwords** for database and admin
8. **Restrict File Permissions** appropriately
9. **Enable CSP Headers** for XSS protection
10. **Regular Security Audits** of codebase

## Performance Optimization

### Database Optimization

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_post_status ON blog_posts(status);
CREATE INDEX idx_created_at ON blog_posts(created_at DESC);
```

### Image Optimization

- Compress images before upload
- Use WebP format when possible
- Implement lazy loading
- Consider CDN for static assets

### Caching

```php
// Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards
- Comment complex logic
- Use meaningful variable names
- Write descriptive commit messages

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Author

**Minothma**
- GitHub: [@Minothma](https://github.com/Minothma)
- Email: minothmasithumini@gmail.com

## Acknowledgments

- Bootstrap team for the amazing CSS framework
- TinyMCE for the rich text editor
- Bootstrap Icons for the icon library
- PHP community for excellent documentation

## Support

For support, open an issue on GitHub.

## Roadmap

- [ ] Categories and tags system
- [ ] Advanced search with filters
- [ ] Email notifications for comments
- [ ] Bookmark/save posts feature
- [ ] Follow/unfollow users
- [ ] RSS feed generation
- [ ] Social media authentication (OAuth)
- [ ] Multi-language support
- [ ] Dark mode theme
- [ ] Post scheduling
- [ ] Analytics dashboard
- [ ] API endpoints for mobile app

## Version History

### Version 1.0.0 (Current)
- Initial release
- User authentication and authorization
- Post creation, editing, and deletion
- Comment system
- Reaction system
- Admin panel
- Profile management
- Search functionality
- Password reset
- File upload system

---

**Made with using PHP and MySQL**

*For detailed documentation, visit our [Wiki](https://github.com/Minothma/inkora-blog-application)*