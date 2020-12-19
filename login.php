<?php
session_start();
$_SESSION['id'] = session_id();

include 'sqlconnect.php';

if(!empty($_POST['login']) ){ // verification du remplissage des champs du formulaire


	$req = $bdd->prepare("SELECT *, CAST(AES_DECRYPT(pwd_user, UNHEX(SHA2('Secret phrase', 512))) AS CHAR(50)) as pwd_decrypted FROM user WHERE login_user = :username");//requete pour la connexion
	$req->bindParam(':username',$_POST['login']);
	$req->execute();
	$user = $req->fetchAll();
	if(!isset($user[0]['login_user'])){
		$_SESSION['message'] = "Mauvais identifiants.";//retour affiche sur la page index
		header('location: index.php');
	}
	if($user[0]['login_user'] == $_POST['login'] && $user[0]['pwd_decrypted'] == $_POST['pwd']){
		//init des variable sessions
		$_SESSION['login'] = $_POST['login'];
		$_SESSION['rights'] = $user[0]['role_user'];
		$_SESSION['name'] = $user[0]['name_user']." ". $user[0]['family_name_user'];
		$_SESSION['ID'] = $user[0]['ID_user'];
		header('location: home.php');
	}
	else {
		$_SESSION['message'] = "Mauvais couple identifiant / mot de passe.";
		header('location:index.php');
	}

} else {
	$_SESSION['message'] = 'Erreur lors de la saisie des identifiants';
	header('location:index.php');
}

?>
