<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System - PHP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .candidate-card {
            transition: all 0.3s ease;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<?php
// Database configuration
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root'; // Change as needed
$password = ''; // Change as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize session
session_start();

// Handle form submissions 
// Party Management in database
// Add Party (Admin)
if (isset($_POST['add_party']) && isset($_SESSION['admin_logged_in'])) {
    $party_name = $_POST['party_name'];
    $party_full_name = $_POST['party_full_name'];
    $party_description = $_POST['party_description'];
    $party_photo = '';

    // Handle file upload if provided
    if (isset($_FILES['party_photo_file']) && $_FILES['party_photo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/party_photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $tmp_name = $_FILES['party_photo_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['party_photo_file']['name'], PATHINFO_EXTENSION));
        $filename = 'party_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($tmp_name, $target)) {
            $party_photo = 'uploads/party_photos/' . $filename;
        }
    }
    // If no file, use URL if provided
    if (!$party_photo) {
        $party_photo = !empty($_POST['party_photo_url']) ? $_POST['party_photo_url'] : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';
    }

    $stmt = $pdo->prepare("INSERT INTO party_position (party_name, party_photo, description, party_full_name) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$party_name, $party_photo, $party_description, $party_full_name])) {
        $_SESSION['success_message'] = "Party added successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add party.";
    }
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Delete Party (Admin)
if (isset($_POST['delete_party']) && isset($_SESSION['admin_logged_in'])) {
    $party_id = $_POST['party_id'];
    $stmt = $pdo->prepare("DELETE FROM party_position WHERE id = ?");
    if ($stmt->execute([$party_id])) {
        $_SESSION['success_message'] = "Party deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete party.";
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Update Party (Admin)
if (isset($_POST['update_party']) && isset($_SESSION['admin_logged_in'])) {
    $party_id = $_POST['edit_party_id'];
    $party_name = $_POST['edit_party_name'];
    $party_full_name = $_POST['edit_party_full_name'];
    $party_description = $_POST['edit_party_description'];
    $party_photo = '';

    // Handle file upload if provided
    if (isset($_FILES['edit_party_photo_file']) && $_FILES['edit_party_photo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/party_photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $tmp_name = $_FILES['edit_party_photo_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['edit_party_photo_file']['name'], PATHINFO_EXTENSION));
        $filename = 'party_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($tmp_name, $target)) {
            $party_photo = 'uploads/party_photos/' . $filename;
        }
    }
    // If no file, use URL if provided
    if (!$party_photo) {
        $party_photo = !empty($_POST['edit_party_photo_url']) ? $_POST['edit_party_photo_url'] : '';
    }

    if ($party_photo) {
        $stmt = $pdo->prepare("UPDATE party_position SET party_name=?, party_full_name=?, description=?, party_photo=? WHERE id=?");
        $result = $stmt->execute([$party_name, $party_full_name, $party_description, $party_photo, $party_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE party_position SET party_name=?, party_full_name=?, description=? WHERE id=?");
        $result = $stmt->execute([$party_name, $party_full_name, $party_description, $party_id]);
    }
    if ($result) {
        $_SESSION['success_message'] = "Party updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update party.";
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Candidate (Admin)
    if (isset($_POST['update_candidate']) && isset($_SESSION['admin_logged_in'])) {
        $candidate_id = $_POST['edit_candidate_id'];
        $candidate_name = trim($_POST['edit_candidate_name'] ?? '');
        $party = $_POST['edit_party'] ?? '';
        $photo_url = $_POST['edit_photo_url'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';
        $details = $_POST['edit_details'] ?? '';
        $mobile_number = trim($_POST['edit_mobile_number'] ?? '');
        $id_card_number = trim($_POST['edit_id_card_number'] ?? '');
        $gender = $_POST['edit_gender'] ?? '';
        $age = $_POST['edit_age'] ?? '';
        $address = trim($_POST['edit_address'] ?? '');

        // Validate required fields
        if (empty($candidate_name)) {
            $error_message = "Candidate Name is required!";
        } elseif (empty($mobile_number)) {
            $error_message = "Mobile Number is required!";
        } elseif (empty($address)) {
            $error_message = "Candidate Address is required!";
        } elseif (empty($id_card_number)) {
            $error_message = "ID Card Number is required!";
        } else {
            // Check if id_card_number is unique (excluding current candidate)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE id_card_number = ? AND candidate_id != ?");
            $stmt->execute([$id_card_number, $candidate_id]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "ID Card Number already exists! Please enter a unique ID.";
            } else {
                $stmt = $pdo->prepare("UPDATE candidates SET full_name=?, party_or_position=?, photo_url=?, details=?, mobile_number=?, id_card_number=?, gender=?, age=?, address=? WHERE candidate_id=?");
                if ($stmt->execute([$candidate_name, $party, $photo_url, $details, $mobile_number, $id_card_number, $gender, $age, $address, $candidate_id])) {
                    $success_message = "Candidate updated successfully!";
                } else {
                    $error_message = "Failed to update candidate.";
                }
            }
        }
    }
    
    // Admin Login
    if (isset($_POST['admin_login'])) {
        $email = $_POST['admin_email'];
        $password = $_POST['admin_password'];

        // Allow login if email and password are the same (for demo/testing)
        if ($email === $password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = 'DEMO';
            $_SESSION['admin_name'] = 'Demo Admin';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND password = ?");
            $stmt->execute([$email, $password]);
            $admin = $stmt->fetch();

            if ($admin) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['full_name'];
            } else {
                $error_message = "Invalid admin credentials!";
            }
        }
    }
    
    // User Registration
    if (isset($_POST['user_register'])) {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $photo_url = $_POST['photo_url'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';
        
        if ($password !== $confirm_password) {
            $error_message = "Passwords do not match!";
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Email already registered!";
            } else {
                // Generate unique user_id
                $user_id = 'USER' . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password, photo_url, has_voted, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                if ($stmt->execute([$user_id, $full_name, $email, $password, $photo_url])) {
                    $success_message = "Registration successful! Your User ID is: $user_id";
                } else {
                    $error_message = "Registration failed!";
                }
            }
        }
    }
    
    // User Login
    if (isset($_POST['user_login'])) {
        $email_or_id = $_POST['email_or_id'];
        $password = $_POST['password'];
        // Allow login with email, user_id, nid_number, or mobile_number
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR user_id = ? OR nid_number = ? OR mobile_number = ?) AND password = ?");
        $stmt->execute([$email_or_id, $email_or_id, $email_or_id, $email_or_id, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['has_voted']) {
                $error_message = "You have already voted! You cannot vote again.";
            } else {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_photo'] = $user['photo_url'];
                // Redirect to user dashboard after successful login
                header("Location: ?page=user_dashboard");
                exit;
            }
        } else {
            $error_message = "Invalid credentials!";
        }
    }
    
    // Add Candidate (Admin)
    if (isset($_POST['add_candidate']) && isset($_SESSION['admin_logged_in'])) {
        $candidate_name = trim($_POST['candidate_name'] ?? '');
        $party = $_POST['party'] ?? '';
        $photo_url = $_POST['photo_url'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';
        $details = $_POST['details'] ?? '';
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $id_card_number = trim($_POST['id_card_number'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $age = $_POST['age'] ?? '';
        $candidate_id = 'CAND' . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $address = trim($_POST['candidates_address'] ?? '');

        // Validate required fields
        if (empty($candidate_name)) {
            $error_message = "Candidate Name is required!";
        } elseif (empty($mobile_number)) {
            $error_message = "Mobile Number is required!";
        } elseif (empty($address)) {
            $error_message = "Candidate Address is required!";
        } elseif (empty($id_card_number)) {
            $error_message = "ID Card Number is required!";
        } else {
            // Check if id_card_number is unique
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE id_card_number = ?");
            $stmt->execute([$id_card_number]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "ID Card Number already exists! Please enter a unique ID.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO candidates (candidate_id, full_name, party_or_position, photo_url, details, created_at, mobile_number, id_card_number, gender, age, address) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
                if ($stmt->execute([$candidate_id, $candidate_name, $party, $photo_url, $details, $mobile_number, $id_card_number, $gender, $age, $address])) {
                    $success_message = "Candidate added successfully!";
                } else {
                    $error_message = "Failed to add candidate.";
                }
            }
        }
    }
    
    // Add User (Admin)
    if (isset($_POST['admin_add_user']) && isset($_SESSION['admin_logged_in'])) {
        $full_name = $_POST['user_name'];
        $email = $_POST['user_email'];
        $password = $_POST['user_password'];
        $photo_url = $_POST['user_photo'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';
        $gender = $_POST['gender'] ?? '';
        $mobile_number = $_POST['mobile_number'] ?? '';
        $nid_number = $_POST['nid_number'] ?? '';

        // Check if email exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetchColumn() > 0) {
            $error_message = "Email already registered!";
        } else {
            $user_id = 'USER' . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password, photo_url, gender, mobile_number, nid_number, has_voted, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            if ($stmt->execute([$user_id, $full_name, $email, $password, $photo_url, $gender, $mobile_number, $nid_number])) {
                $success_message = "User added successfully! User ID: $user_id";
            }
        }
    }
    
    // Cast Vote
    if (isset($_POST['cast_vote']) && isset($_SESSION['user_logged_in'])) {
        $candidate_id = $_POST['candidate_id'];
        $user_id = $_SESSION['user_id'];
        
        // Check if user has already voted
        $stmt = $pdo->prepare("SELECT has_voted FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user['has_voted']) {
            $error_message = "You have already voted!";
        } else {
            // Get candidate and user photos
            $stmt = $pdo->prepare("SELECT photo_url FROM candidates WHERE candidate_id = ?");
            $stmt->execute([$candidate_id]);
            $candidate_photo = $stmt->fetchColumn();
            
            $user_photo = $_SESSION['user_photo'];
            
            // Record the vote
            $stmt = $pdo->prepare("INSERT INTO votes (user_id, candidate_id, vote_time, user_photo, candidate_photo) VALUES (?, ?, NOW(), ?, ?)");
            $stmt->execute([$user_id, $candidate_id, $user_photo, $candidate_photo]);
            
            // Update user's voting status
            $stmt = $pdo->prepare("UPDATE users SET has_voted = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $_SESSION['vote_cast'] = true;
            $success_message = "Vote cast successfully!";
        }
    }
    
    // Delete operations
    if (isset($_POST['delete_candidate']) && isset($_SESSION['admin_logged_in'])) {
        $candidate_id = $_POST['candidate_id'];
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE candidate_id = ?");
        $stmt->execute([$candidate_id]);
        $success_message = "Candidate deleted successfully!";
    }
    
    if (isset($_POST['delete_user']) && isset($_SESSION['admin_logged_in'])) {
        $user_id = $_POST['user_id'];
        
        // Check if user has voted
        $stmt = $pdo->prepare("SELECT has_voted FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user['has_voted']) {
            $error_message = "Cannot delete user who has already voted!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $success_message = "User deleted successfully!";
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get current page
$page = $_GET['page'] ?? 'login';
if (isset($_SESSION['admin_logged_in'])) {
    $page = $_GET['page'] ?? 'admin_dashboard';
} elseif (isset($_SESSION['user_logged_in'])) {
    $page = $_GET['page'] ?? 'user_dashboard';
}
?>

<!-- Display Messages -->
<?php
if (isset($_SESSION['error_message'])) {
    echo '<div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    echo '<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}
?>

<?php if ($page === 'login'): ?>
<!-- Login Screen -->
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md fade-in">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">🗳️ Voting System</h1>
            <p class="text-gray-600">Secure Online Elections</p>
        </div>
        
        <div class="space-y-4">
            <a href="?page=admin_login" class="block w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 text-center">
                Administrator Login
            </a>
            <a href="?page=user_login" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 text-center">
                User Login
            </a>
            <a href="?page=register" class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 text-center">
                Register New User
            </a>
        </div>
    </div>
</div>

<?php elseif ($page === 'admin_login'): ?>
<!-- Admin Login -->
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md fade-in">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-red-600 mb-2">👨‍💼 Administrator Login</h2>
        </div>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" name="admin_email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Enter admin email" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" name="admin_password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Enter admin password" required>
            </div>
            <button type="submit" name="admin_login" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                Login as Administrator
            </button>
            <a href="?page=login" class="block w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 text-center">
                Back
            </a>
        </form>
        <!-- Demo Show data -->
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800"><strong>Demo Credentials:</strong></p>
            <p class="text-sm text-yellow-700">Email: admin@voting.com</p>
            <p class="text-sm text-yellow-700">Password: admin123</p>
        </div>
    </div>
</div>

<?php elseif ($page === 'user_login'): ?>
<!-- User Login -->
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md fade-in">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-blue-600 mb-2">👤 User Login</h2>
        </div>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 font-medium mb-2">Email / User ID / NID Number / Mobile Number</label>
                <input type="text" name="email_or_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter your email, user ID, NID number, or mobile number" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter your password" required>
            </div>
            <button type="submit" name="user_login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                Login to Vote
            </button>
            <a href="?page=login" class="block w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 text-center">
                Back
            </a>
        </form>
    </div>
</div>

<?php elseif ($page === 'register'): ?>
<!-- User Registration -->
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md fade-in">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-green-600 mb-2">📝 User Registration</h2>
        </div>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input type="text" name="full_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Enter your full name" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Email Address</label>
                <input type="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Enter your email address" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Create a password" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Confirm your password" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Profile Photo URL (Optional)</label>
                <input type="url" name="photo_url" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="https://example.com/photo.jpg">
            </div>
            <button type="submit" name="user_register" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                Register
            </button>
            <a href="?page=login" class="block w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 text-center">
                Back
            </a>
        </form>
    </div>
</div>

<?php elseif ($page === 'admin_dashboard' && isset($_SESSION['admin_logged_in'])): ?>
<!-- Admin Dashboard -->
<div class="min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Administrator Dashboard</h1>
                    <p class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
                </div>
                <a href="?logout=1" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition duration-300">
                    Logout
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white rounded-2xl shadow-lg mb-6">
            <div class="flex border-b">
                <a href="?page=admin_dashboard&tab=candidates" class="flex-1 py-4 px-6 text-center font-semibold <?php echo ($_GET['tab'] ?? 'candidates') === 'candidates' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600'; ?> transition duration-300">
                    Manage Candidates
                </a>
                <a href="?page=admin_dashboard&tab=users" class="flex-1 py-4 px-6 text-center font-semibold <?php echo ($_GET['tab'] ?? '') === 'users' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600'; ?> transition duration-300">
                    User Management
                </a>
                <a href="?page=admin_dashboard&tab=results" class="flex-1 py-4 px-6 text-center font-semibold <?php echo ($_GET['tab'] ?? '') === 'results' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600'; ?> transition duration-300">
                    Vote Results
                </a>
                <a href="?page=admin_dashboard&tab=hera_party" class="flex-1 py-4 px-6 text-center font-semibold <?php echo ($_GET['tab'] ?? '') === 'hera_party' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600'; ?> transition duration-300">
                    Party
                </a>
            </div>
        </div>

        <?php $current_tab = $_GET['tab'] ?? 'candidates'; ?>
        <?php if ($current_tab === 'hera_party'): ?>
        <!-- Hera Party Tab -->
        <div class="space-y-6">
            <!-- Add Party Form -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Party</h2>
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Name of the Party</label>
                        <input type="text" name="party_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Party name (short)" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Party Full Name</label>
                        <input type="text" name="party_full_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Full official party name" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2">Party Description</label>
                        <textarea name="party_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Description..." required></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2">Party Photo URL</label>
                        <div class="flex items-center space-x-2">
                            <input type="file" name="party_photo_file" accept="image/*" class="flex-none text-xs file:py-1 file:px-2 file:text-xs file:bg-blue-50 file:border file:border-gray-300 file:rounded file:mr-2 file:cursor-pointer" style="width: 140px;">
                            <span class="text-gray-500 text-xs">or</span>
                            <input type="url" name="party_photo_url" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="https://example.com/photo.jpg">
                        </div>
                        <style>
                        input[type='file']::-webkit-file-upload-button {
                            padding: 2px 8px;
                            font-size: 12px;
                        }
                        input[type='file']::file-selector-button {
                            padding: 2px 8px;
                            font-size: 12px;
                        }
                        </style>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" name="add_party" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-300">
                            Add Party
                        </button>
                    </div>
                </form>
            </div>
            <!-- Party List -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Registered Parties</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM party_position ORDER BY id DESC");
                    $parties = $stmt->fetchAll();
                    if (empty($parties)): ?>
                        <p class="text-gray-500 text-center col-span-full">No parties added yet.</p>
                    <?php else: ?>
                        <?php foreach ($parties as $party): ?>
                        <div class="candidate-card bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <div class="text-center mb-4">
                                <img src="<?php echo htmlspecialchars($party['party_photo']); ?>" alt="<?php echo htmlspecialchars($party['party_name']); ?>" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover border-4 border-white shadow-lg" onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';">
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($party['party_name']); ?></h3>
                                <p class="text-gray-500 text-xs mt-1"><?php echo htmlspecialchars($party['party_full_name']); ?></p>
                            </div>
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($party['description']); ?></p>
                            <div class="flex justify-center gap-2 mt-4">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this party?')">
                                    <input type="hidden" name="party_id" value="<?php echo $party['id']; ?>">
                                    <button type="submit" name="delete_party" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Delete</button>
                                </form>
                                <button type="button" onclick="openEditPartyModal(<?php echo htmlspecialchars(json_encode($party), ENT_QUOTES, 'UTF-8'); ?>)" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Edit</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
<!-- Edit Party Modal -->
<div id="editPartyModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg relative">
    <button onclick="closeEditPartyModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Party</h2>
    <form method="POST" enctype="multipart/form-data" id="editPartyForm" class="space-y-4">
      <input type="hidden" name="edit_party_id" id="edit_party_id">
      <div>
        <label class="block text-gray-700 font-medium mb-2">Name of the Party</label>
        <input type="text" name="edit_party_name" id="edit_party_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Party Full Name</label>
        <input type="text" name="edit_party_full_name" id="edit_party_full_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Party Description</label>
        <textarea name="edit_party_description" id="edit_party_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required></textarea>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Party Photo URL</label>
        <div class="flex items-center space-x-2">
          <input type="file" name="edit_party_photo_file" accept="image/*" class="flex-none text-xs file:py-1 file:px-2 file:text-xs file:bg-blue-50 file:border file:border-gray-300 file:rounded file:mr-2 file:cursor-pointer" style="width: 140px;">
          <span class="text-gray-500 text-xs">or</span>
          <input type="url" name="edit_party_photo_url" id="edit_party_photo_url" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="https://example.com/photo.jpg">
        </div>
      </div>
      <div>
        <button type="submit" name="update_party" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-300">Update Party</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditPartyModal(party) {
  document.getElementById('edit_party_id').value = party.id;
  document.getElementById('edit_party_name').value = party.party_name;
  document.getElementById('edit_party_full_name').value = party.party_full_name;
  document.getElementById('edit_party_description').value = party.description;
  document.getElementById('edit_party_photo_url').value = party.party_photo;
  document.getElementById('editPartyModal').classList.remove('hidden');
}
function closeEditPartyModal() {
  document.getElementById('editPartyModal').classList.add('hidden');
}
</script>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php elseif ($current_tab === 'candidates'): ?>
        <!-- Manage Candidates Tab -->
        <div class="space-y-6">
            <!-- Add Candidate Form -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Candidate</h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Candidate Name <span class="text-red-600">*</span></label>
                        <input type="text" name="candidate_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Full name" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Party/Position</label>
                        <select name="party" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <!-- <label class="block text-gray-700 font-medium mb-2">Candidate Name <span class="text-red-600">*</span></label> -->
                            <option value="">Select Party</option>
                            <?php
                            $party_stmt = $pdo->query("SELECT party_name FROM party_position ORDER BY party_name ASC");
                            $party_options = $party_stmt->fetchAll();
                            foreach ($party_options as $party_row): ?>
                                <option value="<?php echo htmlspecialchars($party_row['party_name']); ?>"><?php echo htmlspecialchars($party_row['party_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Mobile Number <span class="text-red-600">*</span></label>
                        <input type="text" name="mobile_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Mobile number" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">ID Card Number <span class="text-red-600">*</span></label>
                        <input type="text" name="id_card_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ID card number" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Gender</label>
                        <select name="gender" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Age</label>
                        <input type="number" name="age" min="18" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Age">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Candidate Address <span class="text-red-600">*</span></label>
                        <input type="text" name="candidates_address" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Address" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2">Photo URL</label>
                        <input type="url" name="photo_url" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/photo.jpg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2">Candidate Details</label>
                        <textarea name="details" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Background, experience, and qualifications..." required></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" name="add_candidate" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-300">
                            Add Candidate
                        </button>
                    </div>
                </form>
            </div>

            <!-- Candidates List -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Current Candidates</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM candidates ORDER BY created_at DESC");
                    $candidates = $stmt->fetchAll();
                    if (empty($candidates)): ?>
                        <p class="text-gray-500 text-center col-span-full">No candidates added yet.</p>
                    <?php else: ?>
                        <?php foreach ($candidates as $candidate): ?>
                        <div class="candidate-card bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <div class="text-center mb-4">
                                <img src="<?php echo htmlspecialchars($candidate['photo_url']); ?>" alt="<?php echo htmlspecialchars($candidate['full_name']); ?>" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover border-4 border-white shadow-lg" onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';">
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                <p class="text-blue-600 font-medium"><?php echo htmlspecialchars($candidate['party_or_position']); ?></p>
                            </div>
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($candidate['details']); ?></p>
                            <div class="flex flex-col gap-2">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to remove this candidate?')">
                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                    <button type="submit" name="delete_candidate" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                                        Remove Candidate
                                    </button>
                                </form>
                                <button type="button" onclick='openEditCandidateModal(<?php echo json_encode([
                                    "candidate_id" => $candidate["candidate_id"],
                                    "full_name" => $candidate["full_name"],
                                    "party_or_position" => $candidate["party_or_position"],
                                    "photo_url" => $candidate["photo_url"],
                                    "details" => $candidate["details"],
                                    "mobile_number" => $candidate["mobile_number"],
                                    "id_card_number" => $candidate["id_card_number"],
                                    "gender" => $candidate["gender"],
                                    "age" => $candidate["age"],
                                    "address" => $candidate["address"]
                                ]); ?>)' class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Edit</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

<!-- Edit Candidate Modal -->
<div id="editCandidateModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg relative" style="max-height:90vh; overflow-y:auto;">
    <button onclick="closeEditCandidateModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Candidate</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="edit_candidate_id" id="edit_candidate_id">
      <div>
        <label class="block text-gray-700 font-medium mb-2">Candidate Name <span class="text-red-600">*</span></label>
        <input type="text" name="edit_candidate_name" id="edit_candidate_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Party/Position</label>
        <select name="edit_party" id="edit_party" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
          <option value="">Select Party</option>
          <?php
          $party_stmt = $pdo->query("SELECT party_name FROM party_position ORDER BY party_name ASC");
          $party_options = $party_stmt->fetchAll();
          foreach ($party_options as $party_row): ?>
            <option value="<?php echo htmlspecialchars($party_row['party_name']); ?>"><?php echo htmlspecialchars($party_row['party_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Mobile Number <span class="text-red-600">*</span></label>
        <input type="text" name="edit_mobile_number" id="edit_mobile_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">ID Card Number <span class="text-red-600">*</span></label>
        <input type="text" name="edit_id_card_number" id="edit_id_card_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Gender</label>
        <select name="edit_gender" id="edit_gender" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
          <option value="">Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Age</label>
        <input type="number" name="edit_age" id="edit_age" min="18" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Age">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Candidate Address <span class="text-red-600">*</span></label>
        <input type="text" name="edit_address" id="edit_address" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Photo URL</label>
        <input type="url" name="edit_photo_url" id="edit_photo_url" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Candidate Details</label>
        <textarea name="edit_details" id="edit_details" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></textarea>
      </div>
      <div>
        <button type="submit" name="update_candidate" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-300">Update Candidate</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditCandidateModal(candidate) {
  document.getElementById('edit_candidate_id').value = candidate.candidate_id;
  document.getElementById('edit_candidate_name').value = candidate.full_name;
  // Set the party select option
  var partySelect = document.getElementById('edit_party');
  for (var i = 0; i < partySelect.options.length; i++) {
    if (partySelect.options[i].value === candidate.party_or_position) {
      partySelect.selectedIndex = i;
      break;
    }
  }
  document.getElementById('edit_mobile_number').value = candidate.mobile_number;
  document.getElementById('edit_id_card_number').value = candidate.id_card_number;
  document.getElementById('edit_gender').value = candidate.gender;
  document.getElementById('edit_age').value = candidate.age;
  document.getElementById('edit_address').value = candidate.address;
  document.getElementById('edit_photo_url').value = candidate.photo_url;
  document.getElementById('edit_details').value = candidate.details;
  document.getElementById('editCandidateModal').classList.remove('hidden');
}
function closeEditCandidateModal() {
  document.getElementById('editCandidateModal').classList.add('hidden');
}
</script>
        </div>

        <?php elseif ($current_tab === 'users'): ?>
        <!-- User Management Tab -->
        <div class="space-y-6">
            <!-- Add User Form -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New User</h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                <label class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input type="text" name="user_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Full name" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Email Address</label>
                <input type="email" name="user_email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Email address" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" name="user_password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Password" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Gender</label>
                <select name="gender" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Mobile Number</label>
                <input type="text" name="mobile_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Mobile number" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">NID Number</label>
                <input type="text" name="nid_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="NID number" required>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Profile Photo URL</label>
                <input type="url" name="user_photo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/photo.jpg">
            </div>
                    <div class="md:col-span-2">
                        <button type="submit" name="admin_add_user" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-300">
                            Add User
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users List -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Registered Users</h2>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Photo</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">User ID</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                            $users = $stmt->fetchAll();
                            
                            if (empty($users)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-gray-500">No users registered yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <img src="<?php echo htmlspecialchars($user['photo_url']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-10 h-10 rounded-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';">
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm"><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $user['has_voted'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $user['has_voted'] ? 'Voted' : 'Not Voted'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" name="delete_user" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition duration-300 <?php echo $user['has_voted'] ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $user['has_voted'] ? 'disabled' : ''; ?>>
                                                Delete
                                            </button>
                                        </form>
                                        <button type="button" onclick='openEditUserModal(<?php echo json_encode([
                                            "user_id" => $user["user_id"],
                                            "full_name" => $user["full_name"],
                                            "email" => $user["email"],
                                            "gender" => $user["gender"],
                                            "mobile_number" => $user["mobile_number"],
                                            "nid_number" => $user["nid_number"],
                                            "photo_url" => $user["photo_url"]
                                        ]); ?>)' class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition duration-300 ml-2">Edit</button>
                                    </td>
<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg relative">
    <button onclick="closeEditUserModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit User</h2>
    <form method="POST" id="editUserForm" class="space-y-4">
      <input type="hidden" name="edit_user_id" id="edit_user_id">
      <div>
        <label class="block text-gray-700 font-medium mb-2">Full Name</label>
        <input type="text" name="edit_full_name" id="edit_full_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Email Address</label>
        <input type="email" name="edit_email" id="edit_email" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Gender</label>
        <select name="edit_gender" id="edit_gender" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
          <option value="">Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Mobile Number</label>
        <input type="text" name="edit_mobile_number" id="edit_mobile_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">NID Number</label>
        <input type="text" name="edit_nid_number" id="edit_nid_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Profile Photo URL</label>
        <input type="url" name="edit_photo_url" id="edit_photo_url" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
      </div>
      <div>
        <button type="submit" name="update_user" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-300">Update User</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditUserModal(user) {
  document.getElementById('edit_user_id').value = user.user_id;
  document.getElementById('edit_full_name').value = user.full_name;
  document.getElementById('edit_email').value = user.email;
  document.getElementById('edit_gender').value = user.gender;
  document.getElementById('edit_mobile_number').value = user.mobile_number;
  document.getElementById('edit_nid_number').value = user.nid_number;
  document.getElementById('edit_photo_url').value = user.photo_url;
  document.getElementById('editUserModal').classList.remove('hidden');
}
function closeEditUserModal() {
  document.getElementById('editUserModal').classList.add('hidden');
}
</script>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($current_tab === 'results'): ?>
        <!-- Vote Results Tab -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Live Vote Count</h2>
            <div class="space-y-4">
                <?php
                // Get vote statistics
                $stmt = $pdo->query("SELECT COUNT(*) as total_votes FROM votes");
                $total_votes = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) as voted_users FROM users WHERE has_voted = 1");
                $voted_users = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
                $total_users = $stmt->fetchColumn();
                
                // Get vote results
                $stmt = $pdo->query("
                    SELECT c.*, COUNT(v.candidate_id) as vote_count 
                    FROM candidates c 
                    LEFT JOIN votes v ON c.candidate_id = v.candidate_id 
                    GROUP BY c.candidate_id 
                    ORDER BY vote_count DESC
                ");
                $results = $stmt->fetchAll();
                ?>
                
                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-blue-800">Total Votes Cast: <?php echo $total_votes; ?></h3>
                    <p class="text-blue-600">Users Who Voted: <?php echo $voted_users; ?> / <?php echo $total_users; ?></p>
                </div>
                
                <?php if (empty($results)): ?>
                    <p class="text-gray-500 text-center">No candidates available.</p>
                <?php else: ?>
                    <?php foreach ($results as $index => $candidate): ?>
                        <?php 
                        $vote_count = $candidate['vote_count'];
                        $percentage = $total_votes > 0 ? ($vote_count / $total_votes) * 100 : 0;
                        $is_winner = $index === 0 && $vote_count > 0;
                        ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4 <?php echo $is_winner ? 'border-2 border-yellow-400 bg-yellow-50' : ''; ?>">
                            <div class="flex items-center space-x-4">
                                <?php if ($is_winner): ?>
                                    <span class="text-2xl">👑</span>
                                <?php endif; ?>
                                <img src="<?php echo htmlspecialchars($candidate['photo_url']); ?>" alt="<?php echo htmlspecialchars($candidate['full_name']); ?>" class="w-12 h-12 rounded-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';">
                                <div>
                                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($candidate['full_name']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($candidate['party_or_position']); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-800"><?php echo $vote_count; ?></div>
                                <div class="text-sm text-gray-600"><?php echo number_format($percentage, 1); ?>%</div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($page === 'user_dashboard' && isset($_SESSION['user_logged_in'])): ?>
<!-- User Voting Interface -->
<div class="min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Voting Booth</h1>
                    <p class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
                <a href="?logout=1" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition duration-300">
                    Exit Booth
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['vote_cast'])): ?>
        <!-- Vote Confirmation -->
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-2xl font-bold text-green-600 mb-4">Vote Submitted Successfully!</h2>
            <p class="text-gray-600 mb-6">Thank you for participating in the election.</p>
            <a href="?logout=1" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                Exit Voting Booth
            </a>
        </div>
        <?php else: ?>
        <!-- Voting Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-6">
            <h2 class="text-xl font-semibold text-blue-800 mb-2">📋 Voting Instructions</h2>
            <p class="text-blue-700">Select your preferred candidate by clicking the "Vote" button. You can only vote once.</p>
        </div>

        <!-- Candidates for Voting -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Select Your Candidate</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $stmt = $pdo->query("SELECT * FROM candidates ORDER BY full_name");
                $candidates = $stmt->fetchAll();
                
                if (empty($candidates)): ?>
                    <p class="text-gray-500 text-center col-span-full">No candidates available for voting.</p>
                <?php else: ?>
                    <?php foreach ($candidates as $candidate): ?>
                    <div class="candidate-card bg-gray-50 rounded-xl p-6 border border-gray-200">
                        <div class="text-center mb-4">
                            <img src="<?php echo htmlspecialchars($candidate['photo_url']); ?>" alt="<?php echo htmlspecialchars($candidate['full_name']); ?>" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-white shadow-lg" onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face';">
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                            <p class="text-blue-600 font-medium text-lg"><?php echo htmlspecialchars($candidate['party_or_position']); ?></p>
                        </div>
                        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($candidate['details']); ?></p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to vote for this candidate? This action cannot be undone.')">
                            <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                            <button type="submit" name="cast_vote" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105">
                                🗳️ Vote for <?php echo htmlspecialchars($candidate['full_name']); ?>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Redirect to login if not authenticated -->
<script>window.location.href = '?page=login';</script>
<?php endif; ?>

<script>
// Auto-hide messages after 5 seconds
setTimeout(function() {
    const messages = document.querySelectorAll('.fixed.top-4.right-4');
    messages.forEach(function(message) {
        message.style.display = 'none';
    });
}, 5000);
</script>

<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'967133a0a212ba5f',t:'MTc1Mzg0MDE4OS4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
