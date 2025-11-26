<?php
session_start();
require_once __DIR__ . '/config.php';

if (isset($_SESSION['technician_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('SELECT id_technician_assignment, first_name, last_name, password FROM technician_assignment WHERE email = ?');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $technician = $result->fetch_assoc();
            $stmt->close();

            if ($technician && password_verify($password, $technician['password'])) {
                $_SESSION['technician_id'] = $technician['id_technician_assignment'];
                $_SESSION['technician_name'] = $technician['first_name'] . ' ' . $technician['last_name'];
                header('Location: dashboard.php');
                exit;
            }

            $errors[] = 'Invalid credentials. Please try again.';
        } else {
            $errors[] = 'Unable to process request. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Login | Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="bg-white shadow-xl rounded-xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">Technician Portal</h1>
            <p class="text-sm text-slate-500 mt-2">Sign in to access assigned tickets.</p>
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
                <label for="email" class="block text-sm font-medium text-slate-600 mb-2">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    required
                >
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-600 mb-2">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    required
                >
            </div>
            <button
                type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
                Sign In
            </button>
        </form>
        <div class="mt-6 text-center">
            <span class="text-sm text-slate-500">New technician?</span>
            <a href="signup.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 ml-2">Create an account</a>
        </div>
    </div>
</body>
</html>

