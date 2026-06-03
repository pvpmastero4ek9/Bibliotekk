<?php

declare(strict_types=1);

session_start();
unset($_SESSION['admin']);
header('Location: /admin/login.php');
exit;
