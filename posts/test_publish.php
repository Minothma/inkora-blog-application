<?php
/**
 * Test Publish Script
 * Save this as: posts/test_publish.php
 * Access: http://localhost/blogApp/posts/test_publish.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Blog Post Publish Test</h1>";

// Test 1: Include files
echo "<h2>Test 1: Loading Files</h2>";
try {
    require_once '../config/database.php';
    echo "✅ database.php loaded<br>";
} catch (Exception $e) {
    echo "❌ database.php failed: " . $e->getMessage() . "<br>";
}

try {
    require_once '../config/constants.php';
    echo "✅ constants.php loaded<br>";
} catch (Exception $e) {
    echo "❌ constants.php failed: " . $e->getMessage() . "<br>";
}

try {
    require_once '../config/session.php';
    echo "✅ session.php loaded<br>";
} catch (Exception $e) {
    echo "❌ session.php failed: " . $e->getMessage() . "<br>";
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
if (isset($conn)) {
    echo "✅ Database connected<br>";
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM blog_posts");
        $result = $stmt->fetch();
        echo "✅ blog_posts table exists (found " . $result['count'] . " posts)<br>";
    } catch (PDOException $e) {
        echo "❌ blog_posts table error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Database not connected<br>";
}

// Test 3: Check session
echo "<h2>Test 3: Session Check</h2>";
if (isLoggedIn()) {
    echo "✅ User is logged in<br>";
    echo "User ID: " . getCurrentUserId() . "<br>";
    echo "Username: " . getCurrentUsername() . "<br>";
} else {
    echo "❌ User is NOT logged in<br>";
    echo "<a href='../auth/login.php'>Go to Login</a><br>";
}

// Test 4: Check upload directories
echo "<h2>Test 4: Upload Directories</h2>";
$dirs = [
    'uploads' => '../uploads',
    'avatars' => '../uploads/avatars',
    'blog_images' => '../uploads/blog_images'
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "✅ $name directory exists and is writable<br>";
        } else {
            echo "⚠️ $name directory exists but is NOT writable<br>";
        }
    } else {
        echo "❌ $name directory does NOT exist<br>";
    }
}

// Test 5: Try to insert a test post (only if logged in)
echo "<h2>Test 5: Insert Test Post</h2>";
if (isLoggedIn()) {
    try {
        $testTitle = "Test Post " . date('Y-m-d H:i:s');
        $testSlug = "test-post-" . time();
        $testContent = "<p>This is a test post created by test_publish.php script. You can safely delete this.</p>";
        $testExcerpt = "Test post excerpt";
        $userId = getCurrentUserId();
        
        $stmt = $conn->prepare("
            INSERT INTO blog_posts (user_id, title, slug, content, excerpt, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, 'published', NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $userId,
            $testTitle,
            $testSlug,
            $testContent,
            $testExcerpt
        ]);
        
        if ($result) {
            $postId = $conn->lastInsertId();
            echo "✅ SUCCESS! Test post created with ID: $postId<br>";
            echo "<a href='view.php?id=$postId' target='_blank'>View Test Post</a><br>";
            echo "<br><strong>This means your publishing SHOULD work!</strong><br>";
        } else {
            echo "❌ Failed to insert post<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠️ Skipped (you need to be logged in)<br>";
}

// Summary
echo "<hr><h2>📊 Summary</h2>";
echo "<p>If all tests passed, your blog publishing should work!</p>";
echo "<p>If any tests failed, that's your problem area.</p>";
?>
```

---

## 💾 **HOW TO SAVE AND RUN IT**

### **Step 1: Create the File**
1. Open **Notepad** or your code editor
2. Copy the code above
3. Paste it into the editor
4. Save as: `test_publish.php`

### **Step 2: Put it in the Right Folder**
Move the file to: `BLOG-APP/posts/test_publish.php`

Your folder structure should look like:
```
BLOG-APP/
├── posts/
│   ├── create.php
│   ├── edit.php
│   ├── test_publish.php  ← NEW FILE HERE
│   └── ...
```

### **Step 3: Run It**
1. Make sure XAMPP/WAMP is running
2. Make sure you're **logged in** to your blog app first
3. Open your browser
4. Go to: `http://localhost/blogApp/posts/test_publish.php`

---

## 🎯 **WHAT YOU'LL SEE**

The page will show results like this:
```
Blog Post Publish Test

Test 1: Loading Files
✅ database.php loaded
✅ constants.php loaded
✅ session.php loaded

Test 2: Database Connection
✅ Database connected
✅ blog_posts table exists (found 3 posts)

Test 3: Session Check
✅ User is logged in
User ID: 1
Username: admin

Test 4: Upload Directories
❌ uploads directory does NOT exist
❌ avatars directory does NOT exist
❌ blog_images directory does NOT exist

Test 5: Insert Test Post
⚠️ Skipped (directories need to be created first)

📊 Summary
Fix the failed tests above!