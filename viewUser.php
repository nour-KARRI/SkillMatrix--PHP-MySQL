<?php
session_start();
include 'sqlconnect.php';
if(!isset($_GET['id_user'])){
    header('Location: home.php');
}

$id_user = $_GET['id_user'];
//on recupere le nom du user si il n'est present dans aucune base
$req = $bdd->prepare('SELECT * FROM user WHERE user.ID_user ='.$id_user);
$req->execute();
$userView = $req->fetchAll();
$nameUser = $userView[0]["name_user"];
$familyNameUser = $userView[0]["family_name_user"];
//on recupere tous les skills du user passé en parametre
$req = $bdd->prepare('SELECT * FROM `user_skill` LEFT JOIN user ON user.ID_user = user_skill.id_user LEFT JOIN skill ON user_skill.id_skill = skill.ID_skill  LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category WHERE user.ID_user ='.$id_user);
$req->execute();
$user_skill = $req->fetchAll();

//on recupere l historique du user passé en parametre
$req = $bdd->prepare('SELECT * FROM history  LEFT JOIN user ON id_object_history = ID_user WHERE type_history = "user" AND id_object_history = '.$id_user); //on mentionne bien aue le type doit etre user
$req->execute();
$history = $req->fetchAll();

//on recupere la liste des projets du user passé en parametre
$req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN role ON project_user.role = role.ID_role LEFT JOIN project on project.ID_project = project_user.project WHERE project_user.user = '.$id_user );
$req->execute();
$project_user = $req->fetchAll();
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

</head>
<script>
function out(cb){
    $.post( "requests.php?op=is_out", { newValue :cb.checked, idUser :<?php echo $id_user; ?>});
}
</script>
<body class="preload">

    <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">
                <div style="display: block; margin: 40px 0; text-align: center">
                        <h1>Skills of <?php echo $nameUser.' '.$familyNameUser;?></h1> <!-- Affichage du Nom -->
                        <div class="right"><a href="mailto:<?php echo $history[0]['mail_user']?>"> Send an e-mail </a> </div> <!-- Lien d'envoie e mail -->
                </div>
                <?php if ($_SESSION['rights'] == 11 || $_SESSION['rights'] == 12 || $_SESSION['rights'] == 13 || $_SESSION['rights'] == 14) { ?>
                <div id="outDiv" >
                    <div>
                    <input type="checkbox" onclick='out(this);' <?php if($userView[0]["is_out"] == "true"){echo "checked";}?>/>
                        <h3>Out of airbus perimeter</h3>

                    </div>
                </div>
                <?php }?>
                <div id="list_id">
                    <div id="header-list">
                        <input type="text" class="search" placeholder="Search any Category, Skill or Skill level " />
                    </div>

                    <hr>

                    <table class="pure-table">
                    <thead>
                    <tr>
                        <th>Skill</th>
                        <th>Level</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <?php  //display des skills
                        for ($row=0; $row < count($user_skill); $row++) {
                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            echo '<td id="skill'.$row.'" class="change skill" >'.$user_skill[$row]['name_skill_category']." - ".$user_skill[$row]['name_skill'].'</td>';
                            echo '<td id="level'.$row.'" class="change level">'.$user_skill[$row]['level_user_skill'].'</td>';
                            echo '</tr>';
                            }
                    ?>

                    </tbody>
                </table>
                <script>
                        var options = {valueNames: ['category','skill','level']};

                         //Init list pour la recherche temps réel
                        var varList = new List('list_id', options);
                </script>
                </div>
                <div id="project_history">
                    <div style="display: block; margin: 120px 0 20px 0; text-align: center">
                        <h1>Projects associated to <?php echo $nameUser.' '.$familyNameUser;?></h1>
                    </div>
                    <div id="list_project_history_id">
                        <div id="header-list">
                            <input type="text" class="search" placeholder="Search any project name, role or scenario" />

                        </div>

                        <hr>

                        <table class="pure-table">
                        <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Scenario</th>
                            <th>Core Team</th>
                            <th>Role</th>
                        </tr>
                        </thead>
                        <tbody class="list">
                        <?php  //display des projets du users
                        for ($row=0; $row < count($project_user); $row++) {
                                $scenarioDisplay = "";
                                $scenarioDisplay = str_replace(";"," - ",rtrim($project_user[$row]['scenario'],';'));
                                if ($row % 2 == 0) {
                                echo '<tr>';
                                } else {
                                echo '<tr class="border pure-table-odd">';
                                }

                                echo '<td class="project" >'.$project_user[$row]['name_project'].'</td>';
                                echo '<td class="scenario">'.$scenarioDisplay.'</td>';
                                echo '<td> <img width="16px" height="16px" ';
                                    if($project_user[$row]['core_team']=="yes"){
                                        echo 'src="./img/check.png">';
                                    }else{ echo 'src="./img/wrong.png">';}
                                echo'</td>';
                                echo '<td class="role">'.$project_user[$row]['name_role'].'</td>';
                                echo '</tr>';
                                }
                        ?>

                        </tbody>
                    </table>
                    <script>
                            var options2 = {valueNames: ['project','scenario','role']};

                            // Init list pour la recherche par projets
                            var varList2 = new List('list_project_history_id', options2);
                    </script>
                    </div>
                <div>
                <div id="userLogs" >
                    <div style="display: block; margin: 120px 0 20px 0; text-align: center">
                        <h1>History of <?php echo $nameUser.' '.$familyNameUser;?></h1> <!-- Affichage de l historique -->
                    </div>
                    <div id=logs >
                        <?php
                        if(isset($history[0]['object_history'])){
                            for ($row=0; $row < count($history); $row++) {
                                echo $history[$row]['date_history'].': '.$history[$row]['object_history'].'<br>';
                            }

                        }else{
                            echo '<h3>No logs recorded about this object</h3>'; //ce cas n 'est pas censé se produire ; valeur de test
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
