<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

$ticket_id = $_GET['ticket_id'] ?? null;
$errors = [];
$success = '';

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

$formData = [
    'part_name' => '',
    'quantity' => '1',
    'cost' => '',
    'date' => date('Y-m-d')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['part_name'] = trim($_POST['part_name'] ?? '');
    $formData['quantity'] = trim($_POST['quantity'] ?? '1');
    $formData['cost'] = trim($_POST['cost'] ?? '');
    $formData['date'] = $_POST['date'] ?? date('Y-m-d');

    if ($formData['part_name'] === '' || $formData['cost'] === '') {
        $errors[] = 'Part name and cost are required.';
    }

    if (!is_numeric($formData['quantity']) || $formData['quantity'] < 1) {
        $errors[] = 'Quantity must be a positive number.';
    }

    if (!is_numeric($formData['cost']) || $formData['cost'] < 0) {
        $errors[] = 'Cost must be a valid number.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('INSERT INTO part_usage (id_ticket_intake, part_name, quantity, cost, date) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('isiss',
                $ticket_id,
                $formData['part_name'],
                $formData['quantity'],
                $formData['cost'],
                $formData['date']
            );

            if ($stmt->execute()) {
                $success = 'Part usage recorded successfully.';
                $formData = [
                    'part_name' => '',
                    'quantity' => '1',
                    'cost' => '',
                    'date' => date('Y-m-d')
                ];
            } else {
                $errors[] = 'Failed to record part usage. Please try again.';
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
    <title>Record Part Usage | Ticketing System</title>
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
                    <h1 class="text-xl font-semibold text-slate-800 ml-4">Record Part Usage</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Record Part Usage</h2>
                <p class="text-sm text-slate-500 mt-1">Enter the parts used for ticket #<?php echo htmlspecialchars($ticket_id, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <?php if ($errors): ?>
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <p><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="part_name" class="block text-sm font-medium text-slate-600 mb-2">Part Name</label>
                    <input
                        type="text"
                        id="part_name"
                        name="part_name"
                        value="<?php echo htmlspecialchars($formData['part_name'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        placeholder="e.g., RAM 8GB DDR4"
                        required
                    >
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-600 mb-2">Quantity</label>
                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="<?php echo htmlspecialchars($formData['quantity'], ENT_QUOTES, 'UTF-8'); ?>"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            min="1"
                            required
                        >
                    </div>

                    <div>
                        <label for="cost" class="block text-sm font-medium text-slate-600 mb-2">Cost ($)</label>
                        <input
                            type="number"
                            id="cost"
                            name="cost"
                            value="<?php echo htmlspecialchars($formData['cost'], ENT_QUOTES, 'UTF-8'); ?>"
                            class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            required
                        >
                    </div>
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
                        Record Part
                    </button>
                    <a
                        href="work_ticket.php?id=<?php echo $ticket_id; ?>"
                        class="flex-1 rounded-lg border border-slate-200 px-4 py-2.5 text-slate-700 font-semibold hover:bg-slate-50 text-center focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                        Back to Ticket
                    </a>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-200">
                <a href="service_feedback.php?ticket_id=<?php echo $ticket_id; ?>&parts=yes" class="block w-full text-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-indigo-700 font-semibold hover:bg-indigo-100">
                    Done Recording Parts - Continue to Feedback
                </a>
            </div>
        </div>
    </main>
</body>
</html>

