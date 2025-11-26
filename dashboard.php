<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

// Get assigned tickets for this technician
$technician_id = $_SESSION['technician_id'];
$tickets = [];

$stmt = $conn->prepare('
    SELECT 
        ti.id_ticket_intake,
        ti.reported_by,
        ti.issues_description,
        ti.date,
        dt.serial_number,
        dt.model,
        dt.location,
        dt.OS,
        COALESCE(psf.status, "Pending") as status
    FROM ticket_intake ti
    LEFT JOIN device_tracking dt ON ti.id_device_tracking = dt.id_device_tracking
    LEFT JOIN post_service_feedback psf ON psf.id_ticket_intake = ti.id_ticket_intake
    WHERE ti.id_technician_assignment = ?
    ORDER BY ti.date DESC
');

if ($stmt) {
    $stmt->bind_param('i', $technician_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
    <nav class="bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-slate-800">Ticketing System</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-slate-600">Welcome, <?php echo htmlspecialchars($_SESSION['technician_name'] ?? 'Technician', ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-700 font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">My Tickets</h2>
                <p class="text-sm text-slate-500 mt-1">Manage your assigned maintenance tickets</p>
            </div>
            <a href="device_check.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Ticket
            </a>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-slate-900">No tickets assigned</h3>
                <p class="mt-2 text-sm text-slate-500">Get started by creating a new ticket.</p>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ticket ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Reported By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Issue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">#<?php echo htmlspecialchars($ticket['id_ticket_intake'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        <?php echo htmlspecialchars($ticket['model'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?><br>
                                        <span class="text-xs text-slate-400">SN: <?php echo htmlspecialchars($ticket['serial_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo htmlspecialchars($ticket['reported_by'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-500 max-w-xs truncate"><?php echo htmlspecialchars($ticket['issues_description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo date('M d, Y', strtotime($ticket['date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status = $ticket['status'] ?? 'Pending';
                                        $statusColors = [
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'In Progress' => 'bg-blue-100 text-blue-800',
                                            'Completed' => 'bg-emerald-100 text-emerald-800',
                                            'Finished' => 'bg-emerald-100 text-emerald-800'
                                        ];
                                        $color = $statusColors[$status] ?? 'bg-slate-100 text-slate-800';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $color; ?>">
                                            <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="work_ticket.php?id=<?php echo $ticket['id_ticket_intake']; ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

