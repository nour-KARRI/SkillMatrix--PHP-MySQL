<?php

echo 'Sarting';
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
    echo 'Euror Connection to database';
}

$req = $bdd->prepare('SELECT * FROM `user_skill` LEFT JOIN user ON user.ID_user = user_skill.id_user LEFT JOIN skill ON user_skill.id_skill = skill.ID_skill ');
$req->execute();
$user_skill = $req->fetchAll();

$now = time();// today date


for ($i=0; $i< sizeof($user_skill); $i++) { 
    
     //Calculate days between todayDate and date_approval
     $dateApproval = strtotime($user_skill[$i]['date_approval']);
     $datediff = $now - $dateApproval;
     $numberOfDays= round($datediff / (60 * 60 * 24));

     if ($numberOfDays>60) {

        $coeurMessage ='
                <p align="left">Bonjour, </p>

				<p align="left">Vous avez la compétence <strong>'.$user_skill[$i]['name_skill'].'</strong> à re-évaluer, Veuillez-vous connecter et effectuer une demande d’approbation.</p> 

				<p align="left">Cordialement.</p>';

include 'sendMail.php';

        $dir = "C:/Program Files (x86)/EasyPHP-Devserver-17/eds-www/SkillMatrixRebuilt/mails";// folder name 
            if( is_dir($dir) === false )
            {
                echo 'false';
                mkdir($dir); // Create a folder
            }
            $handle = fopen($dir . '/' . $user_skill[$i]['mail_user'].'.html', 'w','/mails') or die('Cannot open file:  '.$my_file); // Create file 
            fwrite($handle, $message);
            fclose($handle);

        }   
}
echo 'Done';
?>