<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$serial_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial_number = trim($_POST['serial_number'] ?? '');

    if ($serial_number === '') {
        $errors[] = 'Serial number is required.';
    }

    if (!$errors) {
        // Check if device exists
        $stmt = $conn->prepare('SELECT id_device_tracking FROM device_tracking WHERE serial_number = ?');
        if ($stmt) {
            $stmt->bind_param('s', $serial_number);
            $stmt->execute();
            $result = $stmt->get_result();
            $device = $result->fetch_assoc();
            $stmt->close();

            if ($device) {
                // Device exists, redirect to create ticket
                header('Location: create_ticket.php?device_id=' . $device['id_device_tracking']);
                exit;
            } else {
                // Device doesn't exist, redirect to register
                header('Location: device_register.php?serial_number=' . urlencode($serial_number));
                exit;
            }
        } else {
            $errors[] = 'Unable to check device. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Device | Ticketing System</title>
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
                    <h1 class="text-xl font-semibold text-slate-800 ml-4">Check Device</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Device Lookup</h2>
                <p class="text-sm text-slate-500 mt-1">Enter the device serial number to check if it exists in the system</p>
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
                        value="<?php echo htmlspecialchars($serial_number, ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        placeholder="Enter device serial number"
                        required
                        autofocus
                    >
                </div>

                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                        Check Device
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

