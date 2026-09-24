<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (isLoggedIn()) {
    logout();
}

redirect('/modules/auth/login.php');
