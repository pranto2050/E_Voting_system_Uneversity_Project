<?php
// CandidateComponent.php
// This component renders the Add New Candidate form and the Current Candidates list.

if (!isset($pdo)) {
    // Database connection (if not already set)
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
}
?>
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
