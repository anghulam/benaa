<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(isSuperAdmin() ? '/superadmin/index.php' : '/modules/dashboard/index.php');
}

redirect('/modules/auth/login.php');
