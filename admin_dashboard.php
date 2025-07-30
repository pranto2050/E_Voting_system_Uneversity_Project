<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php?page=admin_login');
    exit;
}
// Database connection (copied from index.php)
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
// Handle tab selection
$current_tab = $_GET['tab'] ?? 'candidates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen p-6 bg-gradient-to-br from-blue-50 to-indigo-100">
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
                <a href="?tab=candidates" class="flex-1 py-4 px-6 text-center font-semibold <?php echo ($current_tab === 'candidates') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600'; ?> transition duration-300">
                    Manage Candidates
                </a>
                <a href="?tab=users" class="flex-1 py-4 px-6 text-center font-semibold <?php echo ($current_tab === 'users') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600'; ?> transition duration-300">
                    User Management
                </a>
                <a href="?tab=results" class="flex-1 py-4 px-6 text-center font-semibold <?php echo ($current_tab === 'results') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-blue-600'; ?> transition duration-300">
                    Vote Results
                </a>
            </div>
        </div>

        <?php if ($current_tab === 'candidates'): ?>
        <!-- Manage Candidates Tab -->
        <div class="space-y-6">
            <!-- Add Candidate Form -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Candidate</h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Candidate Name</label>
                        <input type="text" name="candidate_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Full name" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Party/Position</label>
                        <input type="text" name="party" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Political party or position" required>
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
                            <form method="POST" onsubmit="return confirm('Are you sure you want to remove this candidate?')">
                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                <button type="submit" name="delete_candidate" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">
                                    Remove Candidate
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
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
                                    </td>
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
                $stmt = $pdo->query("SELECT COUNT(*) as total_votes FROM votes");
                $total_votes = $stmt->fetchColumn();
                $stmt = $pdo->query("SELECT COUNT(*) as voted_users FROM users WHERE has_voted = 1");
                $voted_users = $stmt->fetchColumn();
                $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
                $total_users = $stmt->fetchColumn();
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
</body>
</html>
