<?php 

require __DIR__ . '/vendor/autoload.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['connected'])) {
	include 'accueil.php';
} else {
	include 'login.php';
}

