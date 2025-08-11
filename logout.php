<?php
require 'includes/auth.php';
require_once __DIR__ . '/includes/router.php';

function logout() {
	// Destroy the session and redirect to login page
	session_start();
	session_unset();
	session_destroy();
	header('Location: login.php');
	exit();
}

logout();
?>