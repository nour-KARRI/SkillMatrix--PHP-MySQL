<?php
session_start();

$servername = "mysql:host=localhost;dbname=skillmatrix;charset=utf8"; //pour l 'intsant, la connexion se fait sur le compte root
$username = "root";
$password = "";

// Create connection
try{
	$bdd = new PDO($servername, $username, $password);
	$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //connection a la base
} catch(PDOException $e) {

	$_SESSION['message']='Pas de connexion à la base '.$e;
}

$req = $bdd->prepare('SELECT * FROM role ORDER BY  ID_role ASC');
$req->execute();
$roles = $req->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <base href="./">
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <link href="css/style.css" rel="stylesheet">
	<link rel="stylesheet" href="css/pure.css"/>
	<script src="js/jQuery-3.3.1.js"></script>
	<link rel="stylesheet" href="css/skillMatrix.css"/>
  <title>Authentification</title>
</head>
<script>

function add_user(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_add_user').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_user');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}

console.log($("#rights option:selected" ).text());

function get_pwd(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_get_pwd').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_get_pwd');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}



</script>
<body class="app flex-row align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card-group">
            <div class="card p-4">
              <div class="card-body">
								<h1>Connexion</h1>
								<button class="btn btn-success right adduser" onClick="add_user()">Nouvel Utilisateur</button>
                <p class="text-muted">Veuillez vous connecter pour accéder à la matrice de compétences</p>
	            <form action="login.php" method="post">
	                <div class="input-group mb-3">
	                  <div class="input-group-prepend">
	                    <span class="input-group-text">
	                      <i class="icon-user"></i>
	                    </span>
	                  </div>
	                  <input class="form-control" type="text" id="login" name="login" placeholder="Identifiant">
	                </div>
	                <div class="input-group mb-4">
	                  <div class="input-group-prepend">
	                    <span class="input-group-text">
	                      <i class="icon-lock"></i>
	                    </span>
	                  </div>
	                  <input class="form-control" type="password" id="pwd" name="pwd" placeholder="Mot de passe">
	                </div>
	                <div class="row">
	                  <div class="col-6">
	                    <button class="pure-button pure-button-primary" type="submit">Connexion</button>
	                  </div>
	            </form>
							<div class="col-md-4 ml-md-auto">
									<button class="btn pure-button-primary right" type="button" onClick="get_pwd()">Mot de passe oublié ?</button>
							</div>
							</div>

							<div id="errorMessage">
								<?php
							if(isset($_SESSION['message'])){ //si il ya un message d erreur
								echo $_SESSION['message'];
								$_SESSION['message'] =""; //on efface le message d erreur
								}?>
							</div>

							<div class="shadow add_user_form_div" id="id_add_user"> <!-- Ce formulaire est caché-->


								<h1>Ajouter un utilisateur</h1>
								<form  class="pure-form pure-form-aligned" action="./requests.php?op=adduser" method="post">
									<fieldset>
									<div class="pure-control-group">
												<label for="login">Login de connexion</label>
												<input id="login" name="login" type="text" placeholder="Login" >
										</div>
										<div class="pure-control-group">
												<label for="password">Mot de passe</label>
												<input id="password" name="password" type="password" placeholder="Mot de passe" >
										</div>

										<div class="pure-control-group">
												<label for="name">Prénom</label>
												<input id="name" name="name" type="text" placeholder="Prénom" >
										</div>

										<div class="pure-control-group">
												<label for="family">Nom</label>
												<input id="family" name="family" type="text" placeholder="Nom de famille" >
										</div>
									
										<div class="pure-control-group rights">
										
											<label for="rights" style="margin-left: 100px;">Droits</label>
												<select  id="rights" name="rights" value="<?php echo $roles[0]['name_role'] ?>">
												<?php for($a=0; $a<sizeof($roles);$a++){ 
													echo '<option>'.$roles[$a]['ID_role']." - ".$roles[$a]['name_role'];
												}
												?>
											</select>
										<!--Ce message sera affichié si le role demandé lors du premier enregistrement !=Developper -->
											<span id="messageRole" style="display: none;">
										<p style="position: relative;right:-20px;border-radius: 5px;border: solid 1.5px #5CB3FF;margin-top: 5px;">Ce choix renvoie une demande d'approbation. <br>En attendant vous aurez un rôle "Developper" par défaut.</p>
									</span>
											<script>
						     	//afficher ou masquer dynamiquement un message lors du premier enregistrement
														function hideShowMessage() {
															var singleRole = $( "#rights" ).val();
														if(singleRole=="10 - Developper"){
																$( "#messageRole" ).hide();
														}
														else{
																$( "#messageRole" ).show();
															}
														}	
														$( "select" ).change( hideShowMessage );
														hideShowMessage();

														</script>
									
								
									
											<div style="clear:both"></div>
											</div>
										<div class="pure-control-group">
												<label for="mail">Mail</label>
												<input id="mail" name="mail" type="email" placeholder="jsmith@ausy-group.com" >
										</div>
										<button onClick="update()" type="submit" class="pure-button pure-button-primary">Valider</button>
									</fieldset>
								</form>
              </div>

            </div>
          </div>
        </div>
      </div>
			<div class="shadow add_user_form_div" id="id_get_pwd" > <!-- Ce formulaire est caché-->

			<div class="col-sm-8">
			<div class="card-group ">

			<h2 class="ml-5 p-2">Récupération mot de passe</h2>

			<form action="./requests.php?op=getPwd" method="post">

					<div class="form-group row ml-5">
								<label for="inputEmail3" class="col-sm-2 col-form-label">Email</label>
														<div class="col-sm-10">
															<input type="email" class="form-control" name="mail" type="email" placeholder="jsmith@ausy-group.com">
														</div>
													</div>
													<div class= "form-group row ml-5">
													<div class="col-sm-1">
													<button type="submit" class="pure-button pure-button-primary">Envoyer le mot de passe</button>
													</div>
													</div>
											</form>

					</div>
		</div>
		</div>
    </div>
  </body>
</html>

