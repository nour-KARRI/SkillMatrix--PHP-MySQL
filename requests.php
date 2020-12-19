<?php

session_start();
include 'sqlconnect.php';
ini_set ('max_execution_time', 0);  // temps d'execution superieur de la configuration de base
$op_type = $_GET['op'];
$now=date("d/m/Y \à G:i:s");// pour l 'historique
$nowDate = date("Y/m/d");


if ($op_type == 'adduser'){ //ajout d'un user

	$req=$bdd->prepare('SELECT `ID_role` FROM `role` where `name_role`= "Developper" ORDER BY `role`.`ID_role`');
	$req->execute();
	$id_role = $req->fetchAll();
	$id_role_BD=$id_role[0]['ID_role'];	

	$idRole= substr( $_POST['rights'],0,strpos($_POST['rights'],' - ')); // récupération de l'id du role

	//récupération de la liste des users
	$req = $bdd->prepare('SELECT * FROM user');
	$req->execute();
	$users = $req->fetchAll();

	$found = false;// init de la variable de recherche de user dans la base

	for($a=0; $a<sizeof($users);$a++){
		if($users[$a]['login_user'] == $_POST['login']){ // le login doit etre unique
			$_SESSION['message'] = "Un utilisateur possède déja ce login."; // init message d'erreur
			$found= true;
			header('Location: index.php');
		}
	}
	
	if(!$found){ // si login non présent dans la base

		$insert = $bdd->prepare("INSERT INTO user(name_user, family_name_user, mail_user, role_user, login_user, pwd_user,is_out)
								VALUES(:name, :family, :mail, :rights, :login, AES_ENCRYPT(:password, UNHEX(SHA2('Secret phrase', 512))),'false')"
								);

		$insert->bindParam(':name',$_POST['name']);
		$insert->bindParam(':password',$_POST['password']);
		$insert->bindParam(':rights',$id_role_BD); // Le rôle Developper est accordé par défaut
		$insert->bindParam(':family',$_POST['family']);
		$insert->bindParam(':mail',$_POST['mail']);
		$insert->bindParam(':login',$_POST['login']);
	
		$insert->execute();
	
		//on recherche l id de l'enregistrement
		$req = $bdd->prepare('SELECT * FROM user WHERE login_user = "'.$_POST['login'].'"');
		$req->execute();
		$id_user = $req->fetchAll();
		
		//ajout dans l historique
		$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$id_user[0]['ID_user'].',"'.$now.'","L\'utilisateur a été ajouté")');

		$_SESSION['message'] = "L'utilisateur ".$_POST['login']." vient d'être ajouté.";

		// Si le role demandé est différent de Developper  
		if ($idRole != $id_role_BD) {
			$insert = $bdd->query('INSERT INTO approval (id_user, type_approval, object_approval, demand_approval) VALUES ( '.$id_user[0]['ID_user'].',"role" , null , '.$idRole.') ');
		
		} 
	
		header('Location: index.php');
	}
}

		
		// recuperation mot de passe 
if ($op_type == 'getPwd'){ 	
	//récupération de la liste des users
	$req = $bdd->prepare('SELECT * FROM user');
	$req->execute();
	$users = $req->fetchAll();
	$found = false;// init de la variable de recherche de user dans la base
	for($a=0; $a<sizeof($users);$a++){
		if($users[$a]['mail_user'] == $_POST['mail']){
			$found= true;
		}
	}
	if(!$found){
		$_SESSION['message'] = 'Your email address is invalid. Please enter a valid address';
	}
	else{ // si le mail est présent dans la base
		$req = $bdd->prepare('SELECT login_user FROM user WHERE mail_user = "'.$_POST['mail'].'" ');
		$req->execute();
		$users = $req->fetchAll();
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOP1234567890";
		$pwdRandom = substr(str_shuffle($chars), 0, 8);
		// UPDATE THE PASSwORD IN THE DB
		$userMail= $_POST['mail'];
		$login = $users[0]['login_user'];
		$update = $bdd->prepare('UPDATE user SET pwd_user=AES_ENCRYPT("'.$pwdRandom.'", UNHEX(SHA2(\'Secret phrase\', 512))) WHERE mail_user  = '. "'$userMail'");
		//$update->execute();

			
		$coeurMessage=  // cette variable sera inclus dans sendMail.php
					'<h3>Veuillez utiliser le login et le mot de passe ci-dessous pour se connecter :</h3>
						<table>
							<tbody>
								<tr> <td>login: <strong>'.$login.'</strong></td><tr>
									
										<tr><td>Password: <strong>'.$pwdRandom.'</strong> </td><tr>
											<tr> <td>email: <strong>'.$userMail.'</strong></td> <tr>
													
							</tbody>
						</table>

						<p class="text-left">
						<strong>Important:</strong> Pensez &agrave; modifier votre mot de passe dans la rubrique : nom utilisateur/My account.</p>';
		
include 'sendMail.php';
	/*
		***************************************
		 send  a passWord within Email
		***************************************
		*/ 
		$to = $userMail;
		$subject = 'Password reset email-SkillMatrix';
		$from = 'From: noreply@ausy-group.com';

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Create email headers
		$headers .= 'From: '.$from."\r\n".
			'Reply-To: '.$from."\r\n" .
			'X-Mailer: PHP/' . phpversion(); 
			// Sending email
		mail($to,$subject,$message,$headers);
	
		$dir = "./mails";// folder name 
		if( is_dir($dir) === false )
		{
			mkdir($dir); // Create a folder
		}
		$handle = fopen($dir . '/' . $mail[$i]['mail_user'].'.html', 'w','/mails') or die('Cannot open file:  '.$my_file); // Create file 
		fwrite($handle, $message);
		fclose($handle);
		
	}
}
//On ajoute ici le sqlConnect (si variables sessions non instanciés, retour a la page home)
include 'sqlconnect.php';

if ($op_type == 'addMemberInProject'){ // association d'un user a un projet
	//on recupere tous les id
	$idUser= substr( $_POST['user'],0,strpos($_POST['user'],' - '));
	$idProject= substr( $_POST['project'],0,strpos($_POST['project'],' - '));
	//on recupere tous les noms
	$nameProject =  substr( $_POST['project'],3+strpos($_POST['project'],' - '));
	$nameUser =  substr( $_POST['user'],3+strpos($_POST['user'],' - '));
	//on recupere l id du role donné
	$idRole= substr( $_POST['role'],0,strpos($_POST['role'],' - '));

	if(isset($_POST['core_team']) && $_POST['core_team'] == "yes"){
		$coreTeam = "yes";
	}else{
		$coreTeam = "no";
	}

	$isUserAlreadyOnProject = $bdd->prepare('SELECT * FROM project_user WHERE user = "' . $idUser . '" AND project = "' . $idProject . '"');
	$isUserAlreadyOnProject->execute();
	if ($isUserAlreadyOnProject->rowCount() > 0) {
		$_SESSION['message'] = "Erreur : ".$nameUser." est déjà affilié au projet ".$nameProject.".";
		header('Location: projectStaff.php?add=false');
	}
	else {
		$isUserAlreadyOnProject = $isUserAlreadyOnProject->fetchAll();

		$sqlRequest='INSERT INTO project_user (user, project, scenario,role,core_team,is_out)VALUES( "'.$idUser.'", "'.$idProject.'", "';
		if(isset($_POST['scenario'])){
			foreach($_POST['scenario'] as $scenario){
				$sqlRequest = $sqlRequest.$scenario.';'; // pour chaque scenario
			}
		}
		$sqlRequest= $sqlRequest.'","'.$idRole.'","'.$coreTeam.'","no")'; // is_out par default vaut no
		$insert = $bdd->query($sqlRequest);

		//ajout dans  l'historique du user et du projet
		$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$idUser.',"'.$now.'","L\'utilisateur a été asscocié au projet '.$nameProject.'"),("project",'.$idProject.',"'.$now.'","L\'utilisateur '.$nameUser.' a été ajouté au projet.")');

		header('Location: projectStaff.php');
	}	
}else if ($op_type == 'deleteUserOnProject'){
	
	//ajout dans  l'historique du user et du projet
	$req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN project ON project.ID_project = project_user.project LEFT JOIN user ON user.ID_user = project_user.user WHERE ID_project_user = '.$_GET['idUserProject']);
	$req->execute();
	$id_user = $req->fetchAll();
	$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$id_user[0]["user"].',"'.$now.'","L\'utilisateur a été retiré du projet '.$id_user[0]["name_project"].'."),("project",'.$id_user[0]["ID_project"].',"'.$now.'","L\'utilisateur '.$id_user[0]["name_user"].' '.$id_user[0]["family_name_user"].' a été retiré du projet. ")');

	$delete = $bdd->query('DELETE FROM project_user WHERE ID_project_user = '.$_GET['idUserProject'] );

	header('Location: projectStaff.php');	
}else if ($op_type == 'deleteUserSkill'){// suppresion de l association d'un skill a un user
	$delete = $bdd->query('DELETE FROM user_skill WHERE ID_user_skill ='.$_GET['id_user_skill']);
	
	header('Location: myskills.php');	
}

else if ($op_type == 'addUserSkill'){// ajout d' une demande de skill dans la table approval
	$req = $bdd->prepare('SELECT DISTINCT mail_user 
						  FROM user RIGHT JOIN project ON user.ID_user IN (SELECT manager_project FROM project_user JOIN project ON project_user.project = project.ID_project
																		   WHERE project_user.user = '.$_SESSION['ID'].')');
	$req->execute();
	$mail = $req->fetchAll();
	
	$req = $bdd->prepare('SELECT DISTINCT name_user, family_name_user FROM user WHERE ID_user = '.$_SESSION['ID']);
	$req->execute(); 
	$nameUser = $req->fetchAll();


	//Ce message sera envoyer par mail au manager 
	$coeurMessage= 		// cette variable sera inclus dans sendMail.php
					'<p align="left">Bonjour, </p>
					<p align="left">Vous avez re&#231;u une demande d&#39;approbation de Skill, Veuillez-vous connecter pour l&#39;approuver.</p> 
					<table>
							<tbody>
								<tr> <td>User : <strong>'.$nameUser[0]['name_user']." ".$nameUser[0]['family_name_user'] .'</strong></td><tr>
										<tr><td>Requested skill : <strong>'.$_POST['skillName'].'</strong> </td><tr>
											<tr> <td>Requested level : <strong>'.$_POST['level'].'</strong></td> <tr>
													
							</tbody>
						</table> 
					<p align="left">Cordialement.</p>';
include 'sendMail.php';
for ($i=0; $i < sizeof($mail); $i++) {
	
	$to = $mail[$i]['mail_user'];
		$subject = 'Request for Approval Skill';
		$from = 'From: noreply@ausy-group.com';

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Create email headers
		$headers .= 'From: '.$from."\r\n".
			'Reply-To: '.$from."\r\n" .
			'X-Mailer: PHP/' . phpversion(); 
			// Sending email
		mail($to,$subject,$message,$headers);




	$dir = "./mails";// folder name 
		if( is_dir($dir) === false )
		{
			mkdir($dir); // Create a folder
		}
		$handle = fopen($dir . '/' . $mail[$i]['mail_user'].'.html', 'w','/mails') or die('Cannot open file:  '.$my_file); // Create file 
		fwrite($handle, $message);
		fclose($handle);
}

	$req = $bdd->prepare('SELECT * FROM approval WHERE id_user = '.$_SESSION['ID'].' AND type_approval="skill" AND object_approval = '.$_POST['skillId']);
	$req->execute(); 
	$alreadyExist = $req->fetchAll();
	
	if(isset($alreadyExist[0]['demand_approval'])){
		$update = $bdd->query('UPDATE approval SET demand_approval = '.$_POST['level'].' WHERE ID_approval='.$alreadyExist[0]['ID_approval']);
	}else{
		$insert = $bdd->query('INSERT INTO approval (id_user, type_approval, object_approval, demand_approval) VALUES ( '.$_SESSION['ID'].',"skill" , '.$_POST['skillId'].' , '.$_POST['level'].') ');
	}
			
	header('Location: myskills.php');	
}else if ($op_type == 'deleteSkill'){ //suppresion d'un skill
	$delete = $bdd->query('DELETE FROM skill WHERE ID_skill ='.$_GET['idSkill']);

	$delete = $bdd->query('DELETE FROM user_skill WHERE id_skill ='.$_GET['idSkill']); //suppresion des associations liés a un user

	$delete = $bdd->query('DELETE FROM project_skill_requirement WHERE id_skill ='.$_GET['idSkill']); // suppression des associosios liés a un project requierement
	
	header('Location: adminProjectSkills.php');	
}else if ($op_type == 'addSkill'){ // ajout d 'un skill dans la base

	$req = $bdd->query('SELECT * FROM  skill_category  WHERE skill_category.name_skill_category=\''.$_POST['category'].'\' '); // modif a faire pour recup l id
	$categoryId = $req->fetchAll();
	
	if (strlen($categoryId[0]['ID_skill_category'])<1){
		header('Location: adminProjectSkills.php');
	}
	$insert = $bdd->query('INSERT INTO skill (name_skill,category_skill) VALUES ( \''.$_POST['skillName'].'\',\' '.$categoryId[0]['ID_skill_category'].'\')');
	header('Location: adminProjectSkills.php');
}else if ($op_type == 'addCategory'){ // ajout d'une category (Prevoir des modifs en consequences)
	$insert = $bdd->query('INSERT INTO skill_category (name_skill_category) VALUES ( \''.$_POST['categoryName'].'\')');
	header('Location: adminProjectSkills.php');
}else if ($op_type == 'deleteCategory'){ // suppression d'une category de skill. n 'est pas recommandé
	$delete = $bdd->query('DELETE FROM skill_category WHERE ID_skill_category ='.$_GET['idCategory']);
	
	$req = $bdd->prepare('SELECT * FROM skill WHERE category_skill = '.$_GET['idCategory']);//recuperation tous les skills de la categorie
	$req->execute();
	$skills = $req->fetchAll();

	$delete = $bdd->query('DELETE FROM skill WHERE category_skill = '.$_GET['idCategory']);//suppression les skills

	$sqlSuppr = '';
	for($a=0; $a<sizeof($skills);$a++){
		$sqlSuppr = ' id_skill ='.$skills[$a]['ID_skill'].' OR';
	}

	$delete = $bdd->query('DELETE FROM project_skill_requirement WHERE '.substr($sqlSuppr,0,-3));//suppression des projectskill  resquirement lié a ces skills

	$delete = $bdd->query('DELETE FROM user_skill WHERE '.substr($sqlSuppr,0,-3)); // idem user skill

	header('Location: adminProjectSkills.php');	
}else if ($op_type == 'addBundle'){ // ajout bundle

	$idManager= substr( $_POST['manager'],0,strpos($_POST['manager'],' - ')); //recuperation de l'id du manager

	$insert = $bdd->query('INSERT INTO  bundle (name_bundle,manager_bundle) VALUES ( \''.$_POST['bundle'].'\','.$idManager.')');
	header('Location: viewBundles.php');
}else if ($op_type == 'deleteBundle'){ // suppresion d'un bundle

	$delete = $bdd->query('DELETE FROM bundle WHERE ID_bundle ='.$_GET['idBundle']); //suppression du bundle

	$req = $bdd->prepare('SELECT * FROM project WHERE bundle_project = '.$_GET['idBundle']); //recuperation des projets du bundle
	$req->execute();
	$projects = $req->fetchAll();
	
	$delete = $bdd->query('DELETE FROM project WHERE bundle_project ='.$_GET['idBundle']); //suppression des projets

	$sqlSupprProjectsRequierments = '';
	$sqlSupprProjectsUser = '';
	for($a=0; $a<sizeof($projects);$a++){
		$sqlSupprProjectsRequierments = ' id_project ='.$projects[$a]['ID_project'].' OR'; // création d'un bout de la requete sql liés aux projets
		$sqlSupprProjectsUser = ' project ='.$projects[$a]['ID_project'].' OR'; // création d'un bout de la requete sql liés aux projets
	}
	if(!empty($sqlSupprProjectsRequierments)){
		$sqlSupprProjectsRequierments = substr($sqlSupprProjectsRequierments,0,-3);
	}

	$delete = $bdd->query('DELETE FROM project_skill_requirement WHERE '.$sqlSupprProjectsRequierments); // suppression de tous les skills requirement liés aux projets du  bundle

	
	if(!empty($sqlSupprProjectsUser)){
		$sqlSupprProjectsUser = substr($sqlSupprProjectsUser,0,-3);
	}

	$delete = $bdd->query('DELETE FROM project_user WHERE '.$sqlSupprProjectsUser); // suppresion des assiociationdes users aux projets du bundle

	header('Location: viewBundles.php');

}else if ($op_type == 'addProject'){ // ajout d'un projet dans la base
//********************** 
$idManager= substr( $_POST['manager'],0,strpos($_POST['manager'],' - ')); // on recupere l'id du manager

$idBundle= substr( $_POST['bundle'],0,strpos($_POST['bundle'],' - ')); // on recupere l'id du bundle

$idPRM = substr( $_POST['code'],0,strpos($_POST['code'],' - '));

$sqlRequest ='INSERT INTO project (name_project, status_project, bundle_project, manager_project, prm_project, siglum_project, scenario_project)
              VALUES( "'.$_POST['project'].'", "'.$_POST['status'].'", '.$idBundle.','.$idManager.','.$idPRM.',"'.$_POST['siglum'].'","';


if(isset($_POST['scenario'])){
			   foreach($_POST['scenario'] as $scenario){
							   $sqlRequest = $sqlRequest.$scenario.';';
			   }
}

$sqlRequest = $sqlRequest.'")';
$insert = $bdd->query($sqlRequest);

//recuperation de l'id du role
$reqRole = $bdd->prepare('SELECT role_user FROM user WHERE ID_user = "'.$idManager.'"');
$reqRole->execute();
$ID_role = $reqRole->fetchAll();


//recuperation de l'id

$req = $bdd->prepare('SELECT * FROM project WHERE bundle_project = "'.$idBundle.'" AND name_project = "'.$_POST['project'].'"');
$req->execute();
$id_project = $req->fetchAll();
//ajout dans l historique

$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) 
					   VALUES ("project",'.$id_project[0]['ID_project'].',"'.$now.'","Le projet vient d\'être crée.")');
					   
// insert into project_user

$sqlRequest2='INSERT INTO project_user (user, project, scenario,role,core_team,is_out)VALUES( "'.$idManager.'", "'.$id_project[0]['ID_project'].'", "';
		if(isset($_POST['scenario'])){
			foreach($_POST['scenario'] as $scenario){
				$sqlRequest2 = $sqlRequest2.$scenario.';'; // pour chaque scenario
			}
		}
		$sqlRequest2= $sqlRequest2.'","'.$ID_role[0]["role_user"].'","yes","no")'; // is_out par default vaut no
		$insert = $bdd->query($sqlRequest2);

header('Location: viewProjects.php');


	//**************************************************** */
}else if ($op_type == 'deleteProject'){ // suppression d'un projet

	$delete = $bdd->query('DELETE FROM project WHERE ID_project ='.$_GET['idProject']); //suppession du projet

	$delete = $bdd->query('DELETE FROM project_skill_requirement WHERE id_project ='.$_GET['idProject']); //  suppression du project kill req du projet
	
	$delete = $bdd->query('DELETE FROM project_user WHERE project ='.$_GET['idProject']); //  suppression du user associé au projet

	//suppression dans l historique
	$delete = $bdd->query('DELETE FROM history WHERE type_history= "project" AND id_object_history='.$_GET['idProject']);


	header('Location: viewProjects.php');
}else if ($op_type == 'addPRM'){ // ajout d'une PRM dans la base

	$sqlRequest ='INSERT INTO prm (ID_prm, name_prm) VALUES(NULL, "'.$_POST['prm'].'")';
	var_dump($sqlRequest);
	$insert = $bdd->query($sqlRequest);

	header('Location: editPRM.php');
}else if ($op_type == 'deletePRM'){ // suppression d'une PRM

	$updatePRMValue = $bdd->query('UPDATE project SET prm_project = 0 WHERE prm_project = '.$_GET['idPRM']); //Mise à jour des projets concernés par cette PRM

	$delete = $bdd->query('DELETE FROM prm WHERE ID_prm ='.$_GET['idPRM']); //suppession de la PRM

	header('Location: editPRM.php');

}else if ($op_type == 'addSkillReq'){ // ajout d'un skill requirement

	$idSkill= substr( $_POST['skill'],0,strpos($_POST['skill'],' - ')); // on recupere l'id du skill
	$idProject= substr( $_POST['project'],0,strpos($_POST['project'],' - ')); // on recupere l'id du projet
 
	//on recupere tous les noms
	$nameProject =  substr( $_POST['project'],3+strpos($_POST['project'],' - '));// on recupere le nom du projet
	$nameSkill =  substr( $_POST['skill'],3+strpos($_POST['skill'],' - '));// on recupere le nom du skill

	if(isset($_POST['scenario'])){
		foreach($_POST['scenario'] as $scenario){
			$isProjectReqAlreadyExists = $bdd->prepare('SELECT * FROM project_skill_requirement WHERE id_project = "' . $idProject . '" AND id_skill = "' . $idSkill . '" AND scenario = "'.$scenario.'"');
			$isProjectReqAlreadyExists->execute();
			if ($isProjectReqAlreadyExists->rowCount() > 0) { // on insere pas un skill requieremnt deja present
				$_SESSION['message'] = "Erreur : ".$nameProject." a déja un Skill Requierement ".$nameSkill." pour le scenario ".$scenario.".";
			}else{			
			$insert = $bdd->query('INSERT INTO project_skill_requirement (id_project,scenario,id_skill,level_requirement,size,skill_criticality) VALUES ( '.$idProject.',\''.$scenario.'\','.$idSkill.','.$_POST['level'].','.$_POST['size'].','.$_POST['crit'].')');
			}
		}
	}
	header('Location: projectRequirement.php');
}else if ($op_type == 'deleteSkillReq'){ // suppression skill req
	$delete = $bdd->query('DELETE FROM project_skill_requirement WHERE ID_project_skill_requirement ='.$_GET['idSkillReq']);
	header('Location: projectRequirement.php');

}else if($op_type == 'executeSql'){

	$update = $bdd->query($_POST['sqlReq']);
		
}else if($op_type=='mailApprovalRole' ){
	if( $_POST['rights']=="10 - Developper" &&  $_SESSION['previous_location']= "index.php"){
		break 2;
	}
	$role = " "; 
	$req = $bdd->prepare('SELECT mail_user FROM user JOIN role ON role_user = ID_role where name_role= "Delivery Director" '); //Sélection de tous les mails des Deliver_Director
	$req->execute();
	$mail = $req->fetchAll();

	//Ce message sera envoyer par mail au manager 
	$role= substr( $_POST['role'],2+strpos($_POST['role'],' - '));
	 
	$right= substr( $_POST['rights'],2+strpos($_POST['rights'],' - '));
	$coeurMessage= // cette variable sera inclus dans sendMail.php
					'<p align="left">Bonjour, </p>
					<p align="left">Vous avez re&#231;u une demande d&#39;approbation de role, Veuillez-vous connecter pour l&#39;approuver.</p>  
					<table>
					<tbody>
						<tr> <td>User: <strong>'.$_POST['name'] . " " . $_POST['family'].'</strong></td><tr>
								<tr><td>Requested role: <strong>' .$role . $right.'</strong> </td><tr>	
											
					</tbody>
				</table> 
					<p align="left">Cordialement.</p>';
include 'sendMail.php';

for ($i=0; $i < sizeof($mail); $i++) { 


	$to = $mail[$i]['mail_user'];
		$subject = 'Request for Approval Role';
		$from = 'From: noreply@ausy-group.com';

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Create email headers
		$headers .= 'From: '.$from."\r\n".
			'Reply-To: '.$from."\r\n" .
			'X-Mailer: PHP/' . phpversion(); 
			// Sending email
		mail($to,$subject,$message,$headers);

	
		$dir = "./mails";
	if( is_dir($dir) === false )
	{
		mkdir($dir); // Create a folder
	}
	$handle = fopen($dir . '/' . $mail[$i]['mail_user'].'.html', 'w','/mails') or die('Cannot open file:  '.$my_file); // Create file 
	fwrite($handle, $message);
	fclose($handle);}

}else if($op_type == 'initProjectId'){
	//init l id du project pour project summary 
	$idProject= substr( $_POST['project'],0,strpos($_POST['project'],' - '));
	$_SESSION['ID_project'] = $idProject;
	header("location:".$_SERVER['HTTP_REFERER']); 
}else if($op_type == 'initBundleId'){
	//init l id du bundle pour project summary 
	$idBundle= substr( $_POST['bundle'],0,strpos($_POST['bundle'],' - '));
	$_SESSION['ID_bundle'] = $idBundle;
	header("location:".$_SERVER['HTTP_REFERER']); 
}else if($op_type == 'is_out'){
	//gestion de la sortie d un user du perimetre airbus
	$idUser = $_POST['idUser'];
	$update = $bdd->prepare('UPDATE user SET is_out="'.$_POST['newValue'].'" WHERE ID_user  = '.$idUser);
	$update->execute();
	if($_POST['newValue'] == "true"){

			//recuperations des projets ou le user est associé
		$req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN project ON project.ID_project = project_user.project LEFT JOIN user ON user.ID_user = project_user.user WHERE project_user.user = '.$idUser);
		$req->execute();
		$id_projects = $req->fetchAll();
		//insertion dans l historique
		$sqlRequest = 'INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$idUser.',"'.$now.'","L\'utilisateur a quitté le périmetre Airbus.") ';
		for($a=0; $a<sizeof($id_projects);$a++){
$sqlRequest = $sqlRequest.'("project",'.$id_projects[$a]["ID_project"].',"'.$now.'","L\'utilisateur '.$id_projects[$a]["name_user"].' '.$id_projects[$a]["family_name_user"].' a été retiré du projet. "),';
		}
		$insert = $bdd->query(susbtring($sqlRequest,0,-1));//ajout dans tous les historiques du projet

		$delete = $bdd->query('DELETE FROM project_user WHERE user = '.$idUser);
	}else{
		//ajout dans l historique
		$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$idUser.',"'.$now.'","L\'utilisateur est revenu dans le périmetre Airbus.")');
	}	
}else if($op_type == 'modifyMyPwd'){ //modifictaion de password
	$req = $bdd->prepare("SELECT *, CAST(AES_DECRYPT(pwd_user, UNHEX(SHA2('Secret phrase', 512))) AS CHAR(50)) as pwd_decrypted FROM user WHERE ID_user=".$_SESSION['ID']);//requete pour la connexion
	$req->execute();
	$user = $req->fetchAll();

	if($user[0]['pwd_decrypted'] == $_POST['old']){ //on compare 
		echo 'UPDATE user SET pwd_user= AES_ENCRYPT("'.$_POST['new'].'", UNHEX(SHA2(\'Secret phrase\', 512))) WHERE ID_user  = '.$_SESSION['ID'];
		//insertion le mot de passe chiffré
		$update = $bdd->prepare('UPDATE user SET pwd_user= AES_ENCRYPT("'.$_POST['new'].'", UNHEX(SHA2(\'Secret phrase\', 512))) WHERE ID_user  = '.$_SESSION['ID']);
		$update->execute();
		//Mise en place d 'un retour par message
		$_SESSION['message'] = "Password Updated";
	}else{
		$_SESSION['message'] = "Wrong Password";
	}
	header("location:".$_SERVER['HTTP_REFERER']); 
}else if($op_type == 'validSkillApproval'){
	// update du skill pour le user
	$req = $bdd->prepare('SELECT * FROM  user_skill WHERE id_user = '.$_GET['id_user'].' AND id_skill ='.$_GET['id_skill']);
	$req->execute();
	$getSkill = $req->fetchAll();
	if(strlen($getSkill[0]['level_user_skill'])>0){
		$delete = $bdd->query('DELETE FROM user_skill WHERE ID_user_skill = '.$getSkill[0]['ID_user_skill']);
	}
	$insert = $bdd->query('INSERT INTO user_skill (id_user, id_skill, level_user_skill,date_approval) VALUES ( '.$_GET['id_user'].','.$_GET['id_skill'].', "'.$_GET['new_value'].'", "'.$nowDate .'") ');

	//echo 'INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$_GET['id_user'].' ,"'.$now.'","La demande ou mise a jour du skill '.$_GET['skill_name'].' a acceptée au niveau '.$_GET['new_value'].' ")';

	$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$_GET['id_user'].' ,"'.$now.'","La demande ou mise a jour du skill '.$_GET['skill_name'].' a acceptée au niveau '.$_GET['new_value'].'. ")');
	$delete = $bdd->query('DELETE FROM approval WHERE ID_approval = '.$_GET['id_approval']);
	header("location:".$_SERVER['HTTP_REFERER']);
}else if($op_type == 'refuseSkillApproval'){
	//suppression du skill approval
	$delete = $bdd->query('DELETE FROM approval WHERE ID_approval = '.$_GET['id_approval']);

	//ajout dans l historique
	$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$_GET['id_user'].',"'.$now.'","La demande ou mise a jour du skill '.$_GET['skill_name'].' a été refusée au niveau '.$_GET['new_value'].'.")');
	header("location:".$_SERVER['HTTP_REFERER']);
}else if($op_type == 'validRoleApproval'){
	// update du role pour le user
    $req = $bdd->prepare('UPDATE user SET role_user = '.$_GET['id_role'].' WHERE ID_user ='.$_GET['id_user']);
	$req->execute();
	//rajout dans l historique
	$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$_GET['id_user'].' ,"'.$now.'",\'Evolution du role au niveau "'.$_GET['role_name'].'" a été acceptée.\')');
	//delete du role approval
	$delete = $bdd->query('DELETE FROM approval WHERE ID_approval = '.$_GET['id_approval']);
	header("location:".$_SERVER['HTTP_REFERER']);
}else if($op_type == 'refuseRoleApproval'){ //refus d'un role
	//ajout dans l historique du user
	$insert = $bdd->query('INSERT INTO history (type_history, id_object_history,date_history,object_history) VALUES ("user",'.$_GET['id_user'].' ,"'.$now.'",\'Evolution du role au niveau "'.$_GET['role_name'].'" a été refusée.\')');
	//delete du role approval
	$delete = $bdd->query('DELETE FROM approval WHERE ID_approval = '.$_GET['id_approval']);

 	header("location:".$_SERVER['HTTP_REFERER']);
}else if($op_type == 'initPrmId'){
    //init l id du prm pour prm_view
    $idPrm= substr( $_POST['prm'],0,strpos($_POST['prm'],' - '));
    $_SESSION['ID_prm'] = $idPrm;
    header("location:".$_SERVER['HTTP_REFERER']);
}

