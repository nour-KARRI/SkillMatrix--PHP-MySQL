<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) { // cas improbable
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) { //redicrect si developper
  header('location: home.php');
}

if ($_SESSION['rights'] == 11) { // on ne peut voir que ses propres projets si on est un manager
  $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category WHERE project.manager_project = ' . $_SESSION['ID'] . ' ORDER BY project.name_project ASC');
}
else {//sinon on voit tous les skills requierements
  $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category ORDER BY project.name_project ASC');
}

$req->execute();
$projectReq = $req->fetchAll();

if ($_SESSION['rights'] == 11) { // on ne peut voir que ces propres projets si on est un manager
  $req = $bdd->prepare('SELECT * FROM project WHERE project.manager_project = ' . $_SESSION['ID']);
}
else {//sinon on voit tous les sprojets
    $req = $bdd->prepare('SELECT * FROM project');
}

$req->execute();
$project = $req->fetchAll();

$req = $bdd->prepare('SELECT * FROM skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category');
$req->execute();
$skill = $req->fetchAll();

$req = $bdd->prepare('SELECT * FROM scenario'); // un peu useless, on ne prévoit pas d'autres scenario.
$req->execute();
$scenario = $req->fetchAll();

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

function add_skill(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_add_skill_req').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_skill_req');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}



function info_suppression(idSkillReq,nameSkill,nameProject,scenario) {
        var r = confirm("Confirmez vous la suppression du nombres requis pour le skill "+nameSkill+" pour le projet "+nameProject+" ("+scenario+ ") ?");
        if (r == true) {
            window.location = "requests.php?op=deleteSkillReq&idSkillReq="+idSkillReq;
        }

    }
</script>
<body class="preload">

  <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">
                <div style="display: block; margin: 40px 0; text-align: center">
                    <h1>Project Requirement</h1>
                </div>
                <div id="list_id">
                    <div id="header-list">
                      Project 🡆 <input id="project_search" type="text" class="search-small" <?php if(isset($_SESSION['project_search'])){echo 'value="'.$_SESSION['project_search'].'"'; }else { echo' placeholder="Search any Project"';}?> />
					  Scenario 🡆 <input id="scenario_search" type="text" class="search-small" <?php if(isset($_SESSION["scenario_search"])){echo 'value="'.$_SESSION["scenario_search"].'"'; }else { echo ' placeholder="Search any Scenario"';}?> />
                      Skill 🡆 <input id="skill_search" type="text" class="search-small" <?php if(isset($_SESSION["skill_search"])){echo 'value="'.$_SESSION["skill_search"].'"'; }else { echo ' placeholder="Search any Skill"';}?> />
                        <?php
                        if(isset($_SESSION['message'])){ //si il ya un message d erreur
                            echo '<b id="errorMessage">'.$_SESSION['message'].'</b>';
                            $_SESSION['message'] =""; //on efface le message d erreur
                        }?>
                        <button class="button-success pure-button right" onClick="add_skill()">Add a Skill Requirement</button>
                        <div class="shadow add_user_form_div" id="id_add_skill_req"> <!-- Ce formulaire est caché-->
                            <h1>Add a new skill Requirement</h1>
                            <div class="form_error"></div>
                            <form  class="pure-form pure-form-aligned" action="./requests.php?op=addSkillReq" method="post" name="skillForm" onsubmit="return validateSkillRequirementForm()">
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
                                           <div style="vertical-align:top;display:inline-block">
                                              <label>Scenario</label>
                                           </div>
                                           <div style="display:inline-block">
                                                <?php for($a=0; $a<sizeof($scenario);$a++){ // pour traiter les scenario , on fait apparaitre des checkbox
                                                    echo '<div style="display:block">';
                                                    echo '<input class="inline" style="margin-left:5px" type="checkbox" name="scenario[]"  value="'.$scenario[$a]['name_scenario'].'" />';
                                                    echo '<label style="text-align: left;width:80px;margin-left:5px" for="'.$scenario[$a]['name_scenario'].'">'.$scenario[$a]['name_scenario'].'</label>';
                                                    echo '</div>';
                                                }
                                            ?>
                                            </div>

                                    </div>
                                    <div class="pure-control-group">
                                      <label for="project">Skill</label>
                                        <input type ="text" class="radius" list="skill_list" placeholder="Search.." name="skill" value="">

                                            <datalist id="skill_list">
                                              <?php for($a=0; $a<sizeof($skill);$a++){
                                                  echo '<option>'.$skill[$a]['ID_skill']." - ".$skill[$a]['name_skill_category']." : ".$skill[$a]['name_skill'];
                                              }
                                              ?>
                                            </datalist>
                                    </div>
                                    <div class="pure-control-group">
                                        <label for="level">Level</label>
                                        <input type ="number" id="level" name="level" value="1" min="1" max ="4">
                                    </div>
                                    <div class="pure-control-group">
                                        <label for="size">Size</label>
                                        <input type ="number" id="size" name="size" value="1" min="0" max ="99">
                                    </div>
                                    <div class="pure-control-group">
                                        <label for="crit">Skill Criticality</label>
                                        <input type ="number" id="crit" name="crit" value="1" min="0" max ="4">
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
                        <th>Project</th>
                        <th>Scenario</th>
                        <th>Category</th>
                        <th>Skill</th>
                        <th>Level</th>
                        <th>Size</th>
                        <th>Skill Criticality</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <?php  //display des projectStaff
                        for ($row=0; $row < count($projectReq); $row++) {
                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }
                            echo '<td class="project" >'.$projectReq[$row]['name_project'].'</td>';
                            echo '<td class="scenario" >'.$projectReq[$row]['scenario'].'</td>';
                            echo '<td class="category" >'.$projectReq[$row]['name_skill_category'].'</td>';
                            echo '<td class="skill" >'.$projectReq[$row]['name_skill'].'</td>';
                            echo '<td class="level">'.$projectReq[$row]['level_requirement'].'</td>';
                            echo '<td class="size">'.$projectReq[$row]['size'].'</td>';
                            echo '<td class="skillCrit">'.$projectReq[$row]['skill_criticality'].'</td>';
                            echo '<td><button class="button-error pure-button" onClick="info_suppression(\''.$projectReq[$row]['ID_project_skill_requirement'].'\',\''.$projectReq[$row]['name_skill'].'\',\''.$projectReq[$row]['name_project'].'\',\''.$projectReq[$row]['scenario'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button></td>';
                            echo '</tr>';
                            }
                    ?>

                    </tbody>
                </table>
                <script>
                // Les scripts suivants filtrent les listes
                $( "#project_search" ).on('input',function() {
                    $.post("utils.php", {project_search:$( "#project_search" ).val()});
                    projectSearch();
                })

                $( "#skill_search" ).on('input',function() {
                    $.post("utils.php", {skill_search: $( "#skill_search" ).val()})
                    skillSearch();
                })

                $( '#scenario_search' ).on('input',function() {
                    $.post("utils.php", {scenario_search: $( "#scenario_search" ).val()})
                    scenarioSearch();
                })

                function scenarioSearch (){
                    if($("#scenario_search" ).val().length != 0){
                        $(".scenario").filter(function() {
                            return $(this).text().toUpperCase().indexOf($( "#scenario_search" ).val().toUpperCase()) === -1;// on filtre avec la valeur de la barre de recherche de scenario
                        }).parent().hide(); // on cache les cases filtrées
                    }else{
                        $(".scenario").filter(function() {
                            return $(this);
                        }).parent().show();// on affiche tout
                        $("#scenario_search" ).attr("placeholder", "Search any Scenario")
                    }
                }

                function skillSearch (){
                    if($("#skill_search" ).val().length != 0){
                        $(".skill").filter(function() {
                            return $(this).text().toUpperCase().indexOf($( "#skill_search" ).val().toUpperCase()) === -1;// on filtre avec la valeur de la barre de recherche de skill
                        }).parent().hide(); // on cache les cases filtrées
                    }else{
                        $(".skill").filter(function() {
                            return $(this);
                        }).parent().show();// on affiche tout
                        $("#skill_search" ).attr("placeholder", "Search any Skill")
                    }
                }

                function projectSearch (){
                    if($("#project_search" ).val().length != 0){
                        $(".project").filter(function() {
                            return $(this).text().toUpperCase().indexOf($( "#project_search" ).val().toUpperCase()) === -1;// on filtre avec la valeur dans la barre de recherche de project
                        }).parent().hide(); // on cache les cases filtrées
                    }else{
                        $(".project").filter(function() {
                            return $(this);
                        }).parent().show();// on affiche tout
                        $("#project_search" ).attr("placeholder", "Search any Project")
                    }
                }
                $(document).ready(function() {
                    <?php
                if(isset($_SESSION['project_search'])){
                    if(strlen($_SESSION['project_search'])>0){
                        echo 'projectSearch();';
                    }else{
                        echo'$("#project_search" ).attr("placeholder", "Search any Project");';
                    }
                }
                if(isset($_SESSION['skill_search'])){
                    if(strlen($_SESSION['skill_search'])>0){
                        echo 'skillSearch();';
                    }else {
                        echo '$("#skill_search" ).attr("placeholder", "Search any Skill");';
                    }
                }
                if(isset($_SESSION['scenario_search'])){
                    if(strlen($_SESSION['scenario_search'])>0){
                        echo 'scenarioSearch();';
                    }else{
                        echo '$("#scenario_search" ).attr("placeholder", "Search any Scenario");';
                    }
                }
                ?>
                });
                
                </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
