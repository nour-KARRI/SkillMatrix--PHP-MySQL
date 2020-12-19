<?php 
session_start();
include 'sqlconnect.php';

//on recupere toutes les infos du user connecté
$req = $bdd->prepare('SELECT * FROM user RIGHT JOIN role ON user.role_user = role.ID_role WHERE user.ID_user ='.$_SESSION['ID']);
$req->execute();
$user_me = $req->fetchAll();

//on recupere les roles pour la demande de modifictaion de roles
$req = $bdd->prepare('SELECT * FROM role ORDER BY ID_role ASC');
$req->execute();
$roles = $req->fetchAll();

?>

<!DOCTYPE html>
<html>

<head>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Skill Matrix</title>
  <script src="js/jQuery-3.3.1.js"></script>
  <script src="js/list.js"></script>
  <link rel="stylesheet" href="css/marketing.css"/>
  <link rel="stylesheet" href="css/side-menu.css"/>
  <link rel="stylesheet" href="css/pure.css"/>
  <link rel="stylesheet" href="css/skillMatrix.css" />
</head>
<script>
function update() {
    
    //on met a jour les infos renseignés
    $.post( "requests.php?op=executeSql", { sqlReq: "UPDATE user SET name_user = '"+ $("#name").val()+"',  family_name_user= '"+ $("#family").val()+"',mail_user= '"+ $("#mail").val()+"' WHERE ID_user = <?php echo $_SESSION["ID"];?>"} );

    //on fait une demande de update de role si le role est different.
    if ("<?php echo $user_me[0]['role_user']; ?>" != $("#rights option:selected").val().substring(0,$("#rights option:selected").val().indexOf(' - ') )){
        console.log("here")
        $.post( "requests.php?op=executeSql", { sqlReq: "INSERT INTO approval (id_user, type_approval,object_approval,demand_approval) VALUES ("+<?php echo $_SESSION["ID"];?>+", 'role',null, '"+$("#rights option:selected").val().substring(0,$("#rights option:selected").val().indexOf(' - ') )+"')" } );
      
      //Envoyer un mail a tous les delevry directors
        $.post( "requests.php?op=mailApprovalRole", { name: $("#name").val(), family: $("#family").val(), role : $("#rights").val() });    
    }

    alert("Your profile have been updated.\nIf you just asked for a new role, a Delivery Director needs to approve it first.")
}
</script>
<body class="preload">
<?php include 'header.php'; ?>
    <div id="layout">

        <div id="main">
            
          

            <div class="content">
                <div style="display: block; margin: 40px 0; text-align: center">
                        <h1>My Account</h1>
                </div>
                <div id="modify" class="center">
                    <div  class="inline">
                        <h1>Modify Basic Data</h1> 
                        <div  class="pure-form pure-form-aligned">
                            <fieldset>
                            
                            <div class="pure-control-group">
                                        <label for="name">First Name</label>
                                        <input id="name" name="name" type="text" value="<?php echo $user_me[0]["name_user"] ;?>" >
                                </div>

                                <div class="pure-control-group">
                                        <label for="family">Last Name</label>
                                        <input id="family" name="family" type="text" value="<?php echo $user_me[0]["family_name_user"]; ?>" >
                                </div>

                                <div class="pure-control-group">
                                        <label for="rights">Ask another role</label>
                                
                                        <select id="rights" name="rights" value="<?php echo $user_me[0]['name_role'] ;?>">
                                        <?php for($a=0; $a<sizeof($roles);$a++){// un user peut demander n 'importe quel role
                                            echo '<option ';
                                            if($user_me[0]['role_user'] == $roles[$a]['ID_role']){
                                                echo 'selected';
                                            }
                                            echo '>'.$roles[$a]['ID_role']." - ".$roles[$a]['name_role'];
                                        }
                                        ?>
                                    </select>
                                    </div>
                                <div class="pure-control-group">
                                        <label for="mail">Mail</label>
                                        <input id="mail" name="mail" type="email" value="<?php echo $user_me[0]["mail_user"]; ?>" >
                                </div>
                                <button onClick="update()" class="pure-button pure-button-primary">Valider</button>
                               
                            </fieldset>
                        </div>				
                    </div>




                    <div  class="inline margin-left-2items"> 
                            <h1>Modify my Password</h1>
                            <form  class="pure-form pure-form-aligned" action="./requests.php?op=modifyMyPwd" method="post">
                                <fieldset>
                                    <div class="pure-control-group">
                                            <label for="old">Actual Password</label>
                                            <input id="old" name="old" type="password" >
                                    </div>

                                    <div class="pure-control-group">
                                            <label for="new">New Password</label>
                                            <input id="new" name="new" type="password" >
                                    </div>

                                    <button type="submit" class="pure-button pure-button-primary">Valider</button>
                                </fieldset>
                            </form>
                            <div id="errorMessage"><?php 
							if(isset($_SESSION['message'])){ //si il ya un message d erreur
								echo $_SESSION['message'];
								$_SESSION['message'] =""; //on efface le message d erreur
								}?>
							</div>		
                    </div>
                </div>
            </div>
        </div>
    </div>    
</body>
</html>