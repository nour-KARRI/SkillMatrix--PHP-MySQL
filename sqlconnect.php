<?php

	if(!isset($_SESSION['login'])){
		header('location: index.php');
	}

	$servername = "mysql:host=localhost;dbname=skillmatrix;charset=utf8"; //pour l 'intsant, la connexion se fait sur le compte root
	$username = "root";
	$password = "";
	$error = false; // erreur si la connection a la base echoue

	// Create connection
	try{
		$bdd = new PDO($servername, $username, $password);
		$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //connection a la base
	} catch(PDOException $e) {
		$error =true;
		addLogs("Connection perdue avec la base de données de l'outil :".$e->getMessage());
	}
?>
