<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: ../html/login.php");
    exit();
} elseif ($_SESSION['isTeacher'] !== 1) {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['microsoft_token'])) {
    $_SESSION['microsoft_token'] = $_POST['microsoft_token'];
    echo "Token empfangen!";
}