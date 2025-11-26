<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$ticket_id = $_GET['id'] ?? null;
$ticket = null;
$device = null;
$parts_used = [];

if (!$ticket_id) {
    header('Location: dashboard.php');
    exit;
}

// Get ticket information
$stmt = $conn->prepare('
    SELECT ti.*, dt.*, ta.first_name, ta.last_name
    FROM ticket_intake ti
    JOIN device_tracking dt ON ti.id_device_tracking = dt.id_device_tracking
    JOIN technician_assignment ta ON ti.id_technician_assignment = ta.id_technician_assignment
    WHERE ti.id_ticket_intake = ? AND ti.id_technician_assignment = ?
');
$technician_id = $_SESSION['technician_id'];
if ($stmt) {
    $stmt->bind_param('ii', $ticket_id, $technician_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();
}

if (!$ticket) {
    header('Location: dashboard.php');
    exit;
}

// Get parts used for this ticket
$stmt = $conn->prepare('SELECT * FROM part_usage WHERE id_ticket_intake = ? ORDER BY date DESC');
if ($stmt) {
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $parts_used[] = $row;
    }
    $stmt->close();
}

// Check if feedback already exists for this ticket
$stmt = $conn->prepare('SELECT * FROM post_service_feedback WHERE id_ticket_intake = ? LIMIT 1');
$feedback_exists = false;
if ($stmt) {
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $feedback_exists = true;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work on Ticket | Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-slate-600 hover:text-slate-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-xl font-semibold text-slate-800 ml-4">Ticket #<?php echo htmlspecialchars($ticket_id, ENT_QUOTES, 'UTF-8'); ?></h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="space-y-6">
            <!-- Ticket Information -->
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                <h2 class="text-xl font-bold text-slate-900 mb-4">Ticket Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-slate-500">Reported By:</span>
                        <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($ticket['reported_by'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-slate-500">Date:</span>
                        <p class="text-slate-900 font-medium"><?php echo date('M d, Y', strtotime($ticket['date'])); ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-sm text-slate-500">Issue Description:</span>
                        <p class="text-slate-900 mt-1"><?php echo nl2br(htmlspecialchars($ticket['issues_description'], ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                </div>
            </div>

            <!-- Device Information -->
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                <h2 class="text-xl font-bold text-slate-900 mb-4">Device Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-slate-500">Serial Number:</span>
                        <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($ticket['serial_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-slate-500">Model:</span>
                        <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($ticket['model'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-slate-500">Location:</span>
                        <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($ticket['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-slate-500">Operating System:</span>
                        <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($ticket['OS'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Parts Used -->
            <?php if (!empty($parts_used)): ?>
                <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Parts Used</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Part Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Quantity</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Cost</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($parts_used as $part): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-slate-900"><?php echo htmlspecialchars($part['part_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-500"><?php echo htmlspecialchars($part['quantity'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-500">$<?php echo htmlspecialchars($part['cost'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-500"><?php echo date('M d, Y', strtotime($part['date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                <h2 class="text-xl font-bold text-slate-900 mb-4">Actions</h2>
                <div class="space-y-4">
                    <?php if (empty($parts_used) && !$feedback_exists): ?>
                        <a href="part_usage.php?ticket_id=<?php echo $ticket_id; ?>" class="block w-full text-center rounded-lg bg-indigo-600 px-4 py-3 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            Record Parts Usage
                        </a>
                        <a href="service_feedback.php?ticket_id=<?php echo $ticket_id; ?>&parts=no" class="block w-full text-center rounded-lg border border-slate-200 px-4 py-3 text-slate-700 font-semibold hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                            No Parts Needed - Complete Service
                        </a>
                    <?php elseif (!empty($parts_used) && !$feedback_exists): ?>
                        <a href="service_feedback.php?ticket_id=<?php echo $ticket_id; ?>&parts=yes" class="block w-full text-center rounded-lg bg-indigo-600 px-4 py-3 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            Complete Service Feedback
                        </a>
                    <?php else: ?>
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                            <p class="text-sm text-emerald-700 font-medium">✓ Service completed and feedback submitted</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

