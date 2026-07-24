<?php
// Redirect về dashboard
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php?redirect=' . urlencode('coach/dashboard.php'));
    exit;
}

header('Location: dashboard.php');
exit;
