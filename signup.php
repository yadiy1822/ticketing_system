<?php
session_start();
require_once __DIR__ . '/config.php';

if (isset($_SESSION['technician_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';

$formData = [
    'first_name' => '',
    'last_name' => '',
    'phone' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $key => $value) {
        $formData[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (in_array('', $formData, true)) {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('SELECT id_technician_assignment FROM technician_assignment WHERE email = ?');
        if ($stmt) {
            $stmt->bind_param('s', $formData['email']);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = 'An account with this email already exists.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Unable to validate email. Please try again later.';
        }
    }

    if (!$errors) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO technician_assignment (first_name, last_name, phone, email, password) VALUES (?, ?, ?, ?, ?)');

        if ($stmt) {
            $stmt->bind_param(
                'sssss',
                $formData['first_name'],
                $formData['last_name'],
                $formData['phone'],
                $formData['email'],
                $hashedPassword
            );

            if ($stmt->execute()) {
                $success = 'Account created successfully. You can now sign in.';
                $formData = array_map(static fn() => '', $formData);
            } else {
                $errors[] = 'Failed to create account. Please try again.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Unable to process request right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Signup | Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="bg-white shadow-xl rounded-xl w-full max-w-2xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">Create Technician Account</h1>
            <p class="text-sm text-slate-500 mt-2">Join the maintenance team to receive and manage tickets.</p>
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
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="first_name" class="block text-sm font-medium text-slate-600 mb-2">First Name</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="<?php echo htmlspecialchars($formData['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    required
                >
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-slate-600 mb-2">Last Name</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="<?php echo htmlspecialchars($formData['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    required
                >
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-600 mb-2">Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($formData['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    required
                >
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-600 mb-2">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>"
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
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-slate-600 mb-2">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    required
                >
            </div>
            <div class="md:col-span-2">
                <button
                    type="submit"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                    Create Account
                </button>
            </div>
        </form>
        <div class="mt-6 text-center">
            <span class="text-sm text-slate-500">Already have an account?</span>
            <a href="index.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 ml-2">Back to login</a>
        </div>
    </div>
</body>
</html>

