<?php
/**
 * Entry Point - Redirect to login or dashboard
 */

require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: pages/index.php');
}
exit;
