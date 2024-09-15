<?php 
error_reporting(0); 
$conn = mysqli_connect('localhost', 'root', '', 'survey_system');
if (!$conn) {
    die('Connection Failed: ' . mysqli_connect_error());
}
  
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>