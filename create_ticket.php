<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$device_id = $_GET['device_id'] ?? null;
$device = null;

if (!$device_id) {
    header('Location: device_check.php');
    exit;
}

// Get device information
$stmt = $conn->prepare('SELECT * FROM device_tracking WHERE id_device_tracking = ?');
if ($stmt) {
    $stmt->bind_param('i', $device_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $device = $result->fetch_assoc();
    $stmt->close();
}

if (!$device) {
    header('Location: device_check.php');
    exit;
}

$formData = [
    'reported_by' => '',
    'issues_description' => '',
    'date' => date('Y-m-d')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['reported_by'] = trim($_POST['reported_by'] ?? '');
    $formData['issues_description'] = trim($_POST['issues_description'] ?? '');
    $formData['date'] = $_POST['date'] ?? date('Y-m-d');
    $technician_id = $_SESSION['technician_id'];

    if ($formData['reported_by'] === '' || $formData['issues_description'] === '') {
        $errors[] = 'All fields are required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('INSERT INTO ticket_intake (id_device_tracking, id_technician_assignment, reported_by, issues_description, date) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('iisss',
                $device_id,
                $technician_id,
                $formData['reported_by'],
                $formData['issues_description'],
                $formData['date']
            );

            if ($stmt->execute()) {
                $ticket_id = $stmt->insert_id;
                $stmt->close();
                header('Location: work_ticket.php?id=' . $ticket_id);
                exit;
            } else {
                $errors[] = 'Failed to create ticket. Please try again.';
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
    <title>Create Ticket | Ticketing System</title>
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
                    <h1 class="text-xl font-semibold text-slate-800 ml-4">Create Ticket</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Create Maintenance Ticket</h2>
                <p class="text-sm text-slate-500 mt-1">Fill in the details to create a new ticket for this device</p>
            </div>

            <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <h3 class="text-sm font-semibold text-slate-700 mb-2">Device Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-500">Serial Number:</span>
                        <span class="text-slate-900 font-medium ml-2"><?php echo htmlspecialchars($device['serial_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500">Model:</span>
                        <span class="text-slate-900 font-medium ml-2"><?php echo htmlspecialchars($device['model'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500">Location:</span>
                        <span class="text-slate-900 font-medium ml-2"><?php echo htmlspecialchars($device['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500">OS:</span>
                        <span class="text-slate-900 font-medium ml-2"><?php echo htmlspecialchars($device['OS'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="reported_by" class="block text-sm font-medium text-slate-600 mb-2">Reported By</label>
                    <input
                        type="text"
                        id="reported_by"
                        name="reported_by"
                        value="<?php echo htmlspecialchars($formData['reported_by'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        placeholder="Name of person reporting the issue"
                        required
                    >
                </div>

                <div>
                    <label for="issues_description" class="block text-sm font-medium text-slate-600 mb-2">Issue Description</label>
                    <textarea
                        id="issues_description"
                        name="issues_description"
                        rows="4"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        placeholder="Describe the issue or problem..."
                        required
                    ><?php echo htmlspecialchars($formData['issues_description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div>
                    <label for="date" class="block text-sm font-medium text-slate-600 mb-2">Date</label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        value="<?php echo htmlspecialchars($formData['date'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                </div>

                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                        Create Ticket
                    </button>
                    <a
                        href="dashboard.php"
                        class="flex-1 rounded-lg border border-slate-200 px-4 py-2.5 text-slate-700 font-semibold hover:bg-slate-50 text-center focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

