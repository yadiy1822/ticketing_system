<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$ticket_id = $_GET['ticket_id'] ?? null;
$parts_used = $_GET['parts'] ?? 'no';
$errors = [];

if (!$ticket_id) {
    header('Location: dashboard.php');
    exit;
}

// Verify ticket belongs to technician
$stmt = $conn->prepare('SELECT id_ticket_intake FROM ticket_intake WHERE id_ticket_intake = ? AND id_technician_assignment = ?');
$technician_id = $_SESSION['technician_id'];
if ($stmt) {
    $stmt->bind_param('ii', $ticket_id, $technician_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        header('Location: dashboard.php');
        exit;
    }
    $stmt->close();
}

// Check if feedback already exists for this ticket
$stmt = $conn->prepare('SELECT id_post_service_feedback FROM post_service_feedback WHERE id_ticket_intake = ? LIMIT 1');
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

$formData = [
    'remarks' => '',
    'status' => 'Completed',
    'date_solved' => date('Y-m-d')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$feedback_exists) {
    $formData['remarks'] = trim($_POST['remarks'] ?? '');
    $formData['status'] = trim($_POST['status'] ?? 'Completed');
    $formData['date_solved'] = $_POST['date_solved'] ?? date('Y-m-d');

    if ($formData['remarks'] === '') {
        $errors[] = 'Remarks are required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('INSERT INTO post_service_feedback (id_technician_assignment, id_ticket_intake, remarks, status, date_solved) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('iisss',
                $technician_id,
                $ticket_id,
                $formData['remarks'],
                $formData['status'],
                $formData['date_solved']
            );

            if ($stmt->execute()) {
                header('Location: dashboard.php?success=feedback');
                exit;
            } else {
                $errors[] = 'Failed to submit feedback. Please try again.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Unable to process request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Feedback | Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="work_ticket.php?id=<?php echo $ticket_id; ?>" class="text-slate-600 hover:text-slate-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-xl font-semibold text-slate-800 ml-4">Service Feedback</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Post-Service Feedback</h2>
                <p class="text-sm text-slate-500 mt-1">Complete the service by providing feedback for ticket #<?php echo htmlspecialchars($ticket_id, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <?php if ($feedback_exists): ?>
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <p>✓ Feedback has already been submitted for this ticket.</p>
                </div>
                <div class="mt-6">
                    <a href="dashboard.php" class="block w-full text-center rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-500">
                        Back to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($parts_used === 'yes'): ?>
                    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        <p>✓ Parts usage has been recorded. Please provide final service feedback.</p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="remarks" class="block text-sm font-medium text-slate-600 mb-2">Remarks</label>
                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="5"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            placeholder="Describe the work completed, any issues encountered, and final status..."
                            required
                        ><?php echo htmlspecialchars($formData['remarks'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-600 mb-2">Status</label>
                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            required
                        >
                            <option value="Completed" <?php echo $formData['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Finished" <?php echo $formData['status'] === 'Finished' ? 'selected' : ''; ?>>Finished</option>
                            <option value="Resolved" <?php echo $formData['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>

                    <div>
                        <label for="date_solved" class="block text-sm font-medium text-slate-600 mb-2">Date Solved</label>
                        <input
                            type="date"
                            id="date_solved"
                            name="date_solved"
                            value="<?php echo htmlspecialchars($formData['date_solved'], ENT_QUOTES, 'UTF-8'); ?>"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            required
                        >
                    </div>

                    <div class="flex space-x-4">
                        <button
                            type="submit"
                            class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                            Submit Feedback
                        </button>
                        <a
                            href="work_ticket.php?id=<?php echo $ticket_id; ?>"
                            class="flex-1 rounded-lg border border-slate-200 px-4 py-2.5 text-slate-700 font-semibold hover:bg-slate-50 text-center focus:outline-none focus:ring-2 focus:ring-slate-200"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

