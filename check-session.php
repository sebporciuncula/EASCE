<?php
// Save this as: check-session.php in your root directory
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>🔍 Session Debug Information</h4>
            </div>
            <div class="card-body">
                <h5>Current Session Data:</h5>
                <div class="alert alert-info">
                    <strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Not Active'; ?><br>
                    <strong>Session ID:</strong> <?php echo session_id() ?: 'None'; ?>
                </div>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Session Key</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($_SESSION)): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">
                                    ⚠️ No session data found. Please log in first.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($_SESSION as $key => $value): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                    <td><?php echo htmlspecialchars(print_r($value, true)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h5 class="mt-4">Expected Session Variables:</h5>
                <div class="alert <?php echo isset($_SESSION['user_id']) ? 'alert-success' : 'alert-danger'; ?>">
                    <strong>user_id:</strong> <?php echo $_SESSION['user_id'] ?? '❌ Not Set'; ?>
                </div>
                <div class="alert <?php echo isset($_SESSION['user_type']) ? 'alert-success' : 'alert-danger'; ?>">
                    <strong>user_type:</strong> <?php echo $_SESSION['user_type'] ?? '❌ Not Set'; ?>
                </div>
                <div class="alert <?php echo isset($_SESSION['email']) ? 'alert-success' : 'alert-danger'; ?>">
                    <strong>email:</strong> <?php echo $_SESSION['email'] ?? '❌ Not Set'; ?>
                </div>

                <h5 class="mt-4">User Type Check:</h5>
                <?php if (isset($_SESSION['user_type'])): ?>
                    <div class="alert alert-info">
                        <strong>Raw Value:</strong> "<?php echo $_SESSION['user_type']; ?>"<br>
                        <strong>Lowercase:</strong> "<?php echo strtolower($_SESSION['user_type']); ?>"<br>
                        <strong>Is Cashier?</strong> <?php echo strtolower($_SESSION['user_type']) === 'cashier' ? '✅ Yes' : '❌ No'; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        User type not set in session
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <a href="department/cashier/request.php" class="btn btn-success">Test Cashier Access</a>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>