<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';

$formData = [
    'serial_number' => $_GET['serial_number'] ?? '',
    'model' => '',
    'location' => '',
    'OS' => '',
    'date_issued' => date('Y-m-d')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['serial_number'] = trim($_POST['serial_number'] ?? '');
    $formData['model'] = trim($_POST['model'] ?? '');
    $formData['location'] = trim($_POST['location'] ?? '');
    $formData['OS'] = trim($_POST['OS'] ?? '');
    $formData['date_issued'] = $_POST['date_issued'] ?? date('Y-m-d');

    if (in_array('', array_slice($formData, 0, 4), true)) {
        $errors[] = 'All fields are required.';
    }

    if (!$errors) {
        // Check if serial number already exists
        $stmt = $conn->prepare('SELECT id_device_tracking FROM device_tracking WHERE serial_number = ?');
        if ($stmt) {
            $stmt->bind_param('s', $formData['serial_number']);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = 'A device with this serial number already exists.';
            }
            $stmt->close();
        }
    }

    if (!$errors) {
        $stmt = $conn->prepare('INSERT INTO device_tracking (serial_number, model, location, OS, date_issued) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssss', 
                $formData['serial_number'],
                $formData['model'],
                $formData['location'],
                $formData['OS'],
                $formData['date_issued']
            );

            if ($stmt->execute()) {
                $device_id = $stmt->insert_id;
                $stmt->close();
                // Redirect to create ticket with new device
                header('Location: create_ticket.php?device_id=' . $device_id);
                exit;
            } else {
                $errors[] = 'Failed to register device. Please try again.';
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
    <title>Register Device | Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="device_check.php" class="text-slate-600 hover:text-slate-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-xl font-semibold text-slate-800 ml-4">Register Device</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Register New Device</h2>
                <p class="text-sm text-slate-500 mt-1">Device not found. Please register it in the system.</p>
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
                    <label for="serial_number" class="block text-sm font-medium text-slate-600 mb-2">Serial Number</label>
                    <input
                        type="text"
                        id="serial_number"
                        name="serial_number"
                        value="<?php echo htmlspecialchars($formData['serial_number'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                </div>

                <div>
                    <label for="model" class="block text-sm font-medium text-slate-600 mb-2">Model</label>
                    <input
                        type="text"
                        id="model"
                        name="model"
                        value="<?php echo htmlspecialchars($formData['model'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        placeholder="e.g., Dell OptiPlex 7090"
                        required
                    >
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-slate-600 mb-2">Location</label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        value="<?php echo htmlspecialchars($formData['location'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        placeholder="e.g., Office Room 101"
                        required
                    >
                </div>

                <div>
                    <label for="OS" class="block text-sm font-medium text-slate-600 mb-2">Operating System</label>
                    <input
                        type="text"
                        id="OS"
                        name="OS"
                        value="<?php echo htmlspecialchars($formData['OS'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        placeholder="e.g., Windows 11 Pro"
                        required
                    >
                </div>

                <div>
                    <label for="date_issued" class="block text-sm font-medium text-slate-600 mb-2">Date Issued</label>
                    <input
                        type="date"
                        id="date_issued"
                        name="date_issued"
                        value="<?php echo htmlspecialchars($formData['date_issued'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                </div>

                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                        Register Device
                    </button>
                    <a
                        href="device_check.php"
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

