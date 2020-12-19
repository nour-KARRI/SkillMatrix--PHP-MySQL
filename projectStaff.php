<?php
session_start();
//cette page permet de d'associer un user a un projet
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) { //cas improbable
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) { //redirect si developper
  header('location: home.php');
}

if ($_SESSION['rights'] == 11) { //on ne peut voir que ses propres projets si on est un manager
$req = $bdd->prepare('SELECT * FROM project WHERE project.manager_project = ' . $_SESSION['ID']);
}
else {//sinon on voit tous les projets
  $req = $bdd->prepare('SELECT * FROM project');
}
$req->execute();
$project = $req->fetchAll();

$req = $bdd->prepare('SELECT * FROM user WHERE is_out="false" '); // on ne peut pas ajouter des useres sortis du perimetre
$req->execute();
$userNotOut = $req->fetchAll();

$req = $bdd->prepare('SELECT * FROM scenario');
$req->execute();
$scenario = $req->fetchAll();

if ($_SESSION['rights'] == 11) {//on ne peut voir que ces propres projets si on est un manager

  $req = $bdd->prepare("SELECT * FROM project_user LEFT join role ON project_user.role = role.ID_role
    LEFT JOIN user ON project_user.user = user.ID_user
    LEFT JOIN project ON project.ID_project = project_user.project
    WHERE project.ID_project IN " . "(SELECT ID_project FROM project WHERE project.manager_project = " . $_SESSION['ID'] . ")");
}
else {//sinon on voit tous les projects user
  $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN role ON project_user.role = role.ID_role LEFT JOIN project on project.ID_project = project_user.project');
}
$req->execute();
$project_user = $req->fetchAll();

if ($_SESSION['rights'] == 11) { //on ne peut voir que ces propres projets si on est un manager - un manager ne peut ajouter que des developper
    $req = $bdd->prepare("SELECT * FROM role WHERE name_role = 'Developper'");
}
else { ////sinon on voit tous les roles
  $req = $bdd->prepare('SELECT * FROM role');
}
$req->execute();
$role = $req->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Skill Matrix</title>
  <script src="js/jQuery-3.3.1.js"></script>
  <script src="js/formValidation.js"></script>
  <link rel="stylesheet" href="css/marketing.css"/>
  <link rel="stylesheet" href="css/side-menu.css"/>
  <link rel="stylesheet" href="css/pure.css"/>
  <link rel="stylesheet" href="css/skillMatrix.css" />
</head>
<script>

  $(document).ready(function() {
    $('.form_error').css("display", "none");
  })

  function add_member(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_add_member').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_member');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}

function info_suppression(project,name,idUserProject) {
        var r = confirm("Confirmez vous que "+name+" ne travaille plus sur le projet "+project+" ?");
        if (r == true) {
            window.location = "requests.php?op=deleteUserOnProject&idUserProject="+idUserProject;
        }

    }
</script>
<body class="preload">

  <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">
                <div style="display: block; margin: 40px 0; text-align: center">
                    <h1>Project Staff</h1>
                </div>
                <div id="list_id">
                    <div id="header-list">
                    Project 🡆 <input id="project_search" type="text" class="search" <?php if(isset($_SESSION['project_search'])){echo 'value="'.$_SESSION['project_search'].'"'; }else { echo' placeholder="Search any Project"';}?> />
                        <?php
                        if(isset($_SESSION['message'])){ //si il ya un message d erreur
                            echo '<b id="errorMessage">'.$_SESSION['message'].'</b>';
                            $_SESSION['message'] =""; //on efface le message d erreur
                        }?>
                        <button class="button-success pure-button right" onClick="add_member()">Add someone in any Project</button>
                        <div class="shadow add_user_form_div" id="id_add_member"> <!-- Ce formulaire est caché-->
                            <h1>Add a new member</h1>
                            <div class="form_error"></div>
                            <form class="pure-form pure-form-aligned" action="./requests.php?op=addMemberInProject" method="post" name="addMemberToProject" onsubmit="return validateNewMemberForm()">
                                <fieldset>
                                  <div class="pure-control-group">
                                    <label for="project">Project</label>
                                      <input type ="text" class="radius" list="project_list" placeholder="Search.." name="project" value="">

                                          <datalist id=project_list>
                                              <?php
                                              for($a=0; $a<sizeof($project);$a++) {
                                                echo '<option value="'.$project[$a]['ID_project']." - " .$project[$a]['name_project'] . '">';
                                              }
                                              ?>
                                          </datalist>
                                  </div>
                                    <div class="pure-control-group">
                                        <label for="user">User</label>
                                        <input type ="text" class="radius" list="user_list" placeholder="Search.." name="user" value="">
                                        <datalist id=user_list>
                                            <?php for($a=0; $a<sizeof($userNotOut);$a++){
                                                echo '<option>'.$userNotOut[$a]['ID_user']." - ".$userNotOut[$a]['name_user']." ".$userNotOut[$a]['family_name_user'];
                                            }
                                            ?>
                                      </datalist>
                                    </div>

                                    <div class="pure-control-group">
                                           <div style="vertical-align:top;display:inline-block">
                                              <label>Scenario</label>
                                           </div>
                                           <div style="display:inline-block">
                                                <?php for($a=0; $a<sizeof($scenario);$a++){
                                                    echo '<div style="display:block">';
                                                    echo '<input class="inline" style="margin-left:5px" type="checkbox" name="scenario[]"  value="'.$scenario[$a]['name_scenario'].'" />';
                                                    echo '<label style="text-align: left;width:80px;margin-left:5px" for="'.$scenario[$a]['name_scenario'].'">'.$scenario[$a]['name_scenario'].'</label>';
                                                    echo '</div>';
                                                }
                                            ?>
                                            </div>

                                    </div>
                                    <div class="pure-control-group rights">
                                            <label for="role">Role</label>
                                            <select id="role" name="role" value="<?php echo $role[0]['name_role'] ?>">
                                            <?php for($a=0; $a<sizeof($role);$a++){
                                                echo '<option>'.$role[$a]['ID_role']." - ".$role[$a]['name_role'];
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="pure-control-group">
                                           <div style="vertical-align:top;display:inline-block">
                                              <label>Core Team</label>
                                           </div>
                                           <div style="display:inline-block">
                                                    <div style="display:block">
                                                    <input class="inline" style="margin-left:5px" type="checkbox" name="core_team"  value="yes" />
                                                    <label style="text-align: left;width:80px;margin-left:5px" for="yes">Yes</label>
                                                    </div>
                                            </div>

                                    </div>
                                    <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                                </fieldset>
                            </form>
                      </div>
                    </div>

                    <hr>

                    <table class="pure-table">
                    <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Scenario</th>
                        <th>Core Team</th>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <?php  //display des projectStaff
                        for ($row=0; $row < count($project_user); $row++) {

                            $scenarioDisplay = "";
                            $scenarioDisplay = str_replace(";"," - ",rtrim($project_user[$row]['scenario'],';'));
                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            echo '<td class="change projectName" ><a href="viewProject.php?id_project='.$project_user[$row]['ID_project'].'">'.$project_user[$row]['name_project'].'</a></td>';
                            echo '<td class="scenario" >'.$scenarioDisplay.'</td>';
                            echo '<td> <img width="16px" height="16px" ';
                                if($project_user[$row]['core_team']=="yes"){
                                    echo 'src="./img/check.png">';
                                }else{ echo 'src="./img/wrong.png">';}
                            echo'</td>';
                            echo '<td class="role" >'.$project_user[$row]['name_role'].'</td>';
                            echo '<td class="user" ><a href="viewUser.php?id_user='.$project_user[$row]['ID_user'].'">'.$project_user[$row]['name_user']." ".$project_user[$row]['family_name_user'].'</a></td>';
                            echo '<td><button class="button-error pure-button" onClick="info_suppression(\''.$project_user[$row]['name_project'].'\',\''.$project_user[$row]['name_user'].'\',\''.$project_user[$row]['ID_project_user'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button></td>';
                            echo '</tr>';
                            }
                    ?>

                    </tbody>
                </table>
                <script>
                
                $( "#project_search" ).on('input',function() {
                    $.post("utils.php", {project_search:$( "#project_search" ).val()});
                    projectSearch();
                })

                function projectSearch (){
                    if($("#project_search" ).val().length != 0){
                        $(".projectName").filter(function() {
                            return $(this).text().toUpperCase().indexOf($( "#project_search" ).val().toUpperCase()) === -1;// on filtre avec la valeur dans la barre de recherche de project
                        }).parent().hide(); // on cache les cases filtrées
                    }else{
                        $(".projectName").filter(function() {
                            return $(this);
                        }).parent().show();// on affiche tout
                        $("#project_search" ).attr("placeholder", "Search any Project")
                    }
                }
                <?php
                if(isset($_SESSION['project_search'])){
                   echo 'projectSearch();';
                }
                ?>
                </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
