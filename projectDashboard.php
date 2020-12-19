<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) { //redirect if developper
  header('location: home.php');
}

if ($_SESSION['rights'] == 11) { // on ne peut voir que ces propres projets si on est un manager
  $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category WHERE project.manager_project = ' . $_SESSION['ID']);
  $req->execute();
  $projectReq = $req->fetchAll();
  $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN role ON project_user.role = role.ID_role LEFT JOIN project on project.ID_project = project_user.project LEFT JOIN user_skill ON user_skill.id_user= project_user.user LEFT JOIN skill ON skill.ID_skill= user_skill.id_skill LEFT JOIN skill_category ON skill_category.ID_skill_category = skill.category_skill WHERE project_user.user = ' . $_SESSION['ID']);
}
else { //sinon on voit tous les skills requierements
  $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category');
  $req->execute();
  $projectReq = $req->fetchAll();
  $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN role ON project_user.role = role.ID_role LEFT JOIN project on project.ID_project = project_user.project LEFT JOIN user_skill ON user_skill.id_user= project_user.user LEFT JOIN skill ON skill.ID_skill= user_skill.id_skill LEFT JOIN skill_category ON skill_category.ID_skill_category = skill.category_skill');
}

$req->execute();
$project_user = $req->fetchAll();

$requirementsExpectationsAndReality = []; //on va comparer pour chaque skill req si la ausy size correspond
for ($i=0; $i < count($projectReq); $i++) { //pour chaque skill req
    $requirementsExpectationsAndReality[$i] = array('projectName'=>$projectReq[$i]['name_project'],
                                                    'scenario'=>$projectReq[$i]['scenario'],
                                                    'actualScenario'=>$projectReq[$i]['status_project'],
                                                    'skillReqName'=>$projectReq[$i]['name_skill'],
                                                    'skillReqSize'=>$projectReq[$i]['size'],
                                                    'skillRealitySize'=>0);
    for ($j=0; $j < count($project_user); $j++) { //pour chaque user dans le projet
        if($projectReq[$i]['id_skill'] == $project_user[$j]['id_skill'] && $projectReq[$i]['id_project'] == $project_user[$j]['ID_project'] && strpos($project_user[$j]['scenario'],$projectReq[$i]['scenario']) !== false){
            $requirementsExpectationsAndReality[$i]['skillRealitySize']++; //si il y a quelqu un avec ce skill sur le projet et sur ce scenario, alors on incrémente
        }
    }
}


?>

<!DOCTYPE html>
<html>

<head>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Skill Matrix</title>
  <script src="js/jQuery-3.3.1.js"></script>
  <link rel="stylesheet" href="css/marketing.css"/>
  <link rel="stylesheet" href="css/side-menu.css"/>
  <link rel="stylesheet" href="css/pure.css"/>
  <link rel="stylesheet" href="css/skillMatrix.css" />
</head>
<body class="preload">

    <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">
                <div style="display: block; margin: 40px 0; text-align: center">
                        <h1>Project Dashboard</h1>
                </div>
                <div id="list_id">
                    <div id="header-list">
                        Project 🡆 <input id="project_search" type="text" class="search-small" placeholder="Search any Project" />
                        Skill 🡆 <input id="skill_search" type="text" class="search-small" placeholder="Search any Skill" />
                        <div class="right">
                            <input type="checkbox" id="actualStatusCB" onClick="diplayStatus()" ><label for="actualStatusCB">&nbsp;Display only Actual status</label>
                        </div>
                    </div>

                    <hr>

                    <table class="pure-table">
                    <thead>
                    <tr>
                        <th>Project</th>
                        <th>Scenario</th>
                        <th>Skill</th>
                        <th>Skill Size Requirement</th>
                        <th>Skill Size Reality</th>
                        <th>State</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <?php  //display des projectStaff
                        for ($row=0; $row < count($requirementsExpectationsAndReality); $row++) {
                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            echo '<td class="project" >'.$requirementsExpectationsAndReality[$row]['projectName'].'</td>';
                            echo '<td class="scenario '.$requirementsExpectationsAndReality[$row]['actualScenario'].'" >'.$requirementsExpectationsAndReality[$row]['scenario'].'</td>';
                            echo '<td class="skill">'.$requirementsExpectationsAndReality[$row]['skillReqName'].'</td>';
                            echo '<td class="sizeReq">'.$requirementsExpectationsAndReality[$row]['skillReqSize'].'</td>';
                            echo '<td class="sizeReality">'.$requirementsExpectationsAndReality[$row]['skillRealitySize'].'</td>';
                            echo '<td> <img width="16px" height="16px" ';
                                    if($requirementsExpectationsAndReality[$row]['skillRealitySize']>=$requirementsExpectationsAndReality[$row]['skillReqSize']){
                                        echo 'src="./img/check.png">';
                                    }elseif($requirementsExpectationsAndReality[$row]['skillRealitySize']<$requirementsExpectationsAndReality[$row]['skillReqSize'] && $requirementsExpectationsAndReality[$row]['skillRealitySize']>0){ echo 'src="./img/warning3.svg">';}
                                    elseif($requirementsExpectationsAndReality[$row]['skillRealitySize']==0){echo 'src="./img/wrong.png">';}
                            echo'</td>';
                            echo '</tr>';
                            }
                    ?>

                    </tbody>
                </table>
                <script>

                function diplayStatus(){
                    if($('#actualStatusCB').is(':checked')){
                        $(".scenario").filter(function() {
                            return $( this ).attr("class").toUpperCase().indexOf($(this).text().toUpperCase()) === -1
                            // on filtre avec la valeur dans la barre de recherche de project
                        }).parent().hide(); // on cache les cases filtrées
                    }else{
                            $(".scenario").filter(function() {
                            return $(this);
                        }).parent().show();// on affiche tout
                        
                    }
                }
               
                // Les scripts suivants filtrent les listes
                $( "#project_search" ).keyup(function() {
                    if($("#project_search" ).val().length != 0){
                        $(".project").filter(function() {
                            return $(this).text().toUpperCase().indexOf($( "#project_search" ).val().toUpperCase()) === -1;// on filtre avec la valeur dans la barre de recherche de project
                        }).parent().hide(); // on cache les cases filtrées
                    }else{
                        $(".project").filter(function() {
                            return $(this);
                        }).parent().show();// on affiche tout
                    }

                })

                $( "#skill_search" ).keyup(function() {

                    if($("#skill_search" ).val().length != 0){
                        $(".skill").filter(function() {
                            return $(this).text().toUpperCase().indexOf($( "#skill_search" ).val().toUpperCase()) === -1;// on filtre avec la valeur de la barre de recherche de skill
                        }).parent().hide(); // on cache les cases filtrées
                    }else{
                        $(".skill").filter(function() {
                            return $(this);
                        }).parent().show();// on affiche tout
                    }

                })
                </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
