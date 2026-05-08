<?php
require_once '../config.php';
session_unset();
session_destroy();
session_start();
set_success_message("You have been logged out.");
header("Location: ../csr/login.php");
exit();
?>
