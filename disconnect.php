<?php
//destruction des variables de session
session_start();
session_destroy();

header('location: index.php');
?>