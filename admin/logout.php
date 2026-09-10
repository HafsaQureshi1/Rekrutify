<?php
/** Admin: end the session and return to the login form. */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

logout();
header('Location: ./index.php');
