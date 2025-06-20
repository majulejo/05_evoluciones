<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
    $pass = $_POST['pass'];
    echo password_hash($pass, PASSWORD_DEFAULT);
}
