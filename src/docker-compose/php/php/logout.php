<?php
require_once 'config.php';

// Destroy session and redirect to home page
session_destroy();
header('Location: ../index.html');
exit;
?>