<?php
session_start();
include 'sqlconnect.php';

$req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN role ON project_user.role = role.ID_role LEFT JOIN project on project.ID_project = project_user.project WHERE project_user.user = '.$_SESSION['ID'] );
$req->execute();
$project_user = $req->fetchAll();

// on prepare la requete sql pour chercher tous les skills requierement où le user est présent
$idProjectsForSql ="";
for ($row=0; $row < count($project_user); $row++) {
    if(!strpos($idProjectsForSql,$project_user[$row]['project'])){
        $idProjectsForSql= $idProjectsForSql." project.ID_project=".$project_user[$row]['project']." OR ";
    }
}
if($idProjectsForSql != ""){
    $idProjectsForSql =" WHERE ".substr($idProjectsForSql,0,-3);
}else{
    $idProjectsForSql =" WHERE project.ID_project=0 ";
}
$req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category '.$idProjectsForSql);
$req->execute();
$requirementsInMyProjects = $req->fetchAll();
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
<body class="preload">

  <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">
                <div id="project_history">
                    <div style="display: block; margin: 40px 0; text-align: center">
                        <h1>My Projects</h1>
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
                        <?php  //display des projets associés au projet
                        for ($row=0; $row < count($project_user); $row++) {
                                $scenarioDisplay = "";
                                $scenarioDisplay = str_replace(";"," - ",rtrim($project_user[$row]['scenario'],';')); // on arrange l affichage des scenario
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
                        var options = {valueNames: ['project','scenario','role']};
                        // Init list pour la recherche par CMS
                        var varList = new List('list_project_history_id', options);
                    </script>
                    </div>
                <div>
                <div id="skills_required">
                <div style="display: block; margin: 120px 0 40px 0; text-align: center">
                        <h1>Skills required in my projects</h1>
                    </div>
                    <div id="list_required_project_history_id">

                    <div id="header-list">
                            <input type="text" class="search" placeholder="Search any project name, role or scenario" />

                        </div>

                        <hr>

                        <table class="pure-table">
                        <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Scenario</th>
                            <th>Skill</th>
                            <th>Skill level</th>
                            <th>Size</th>
                        </tr>
                        </thead>
                        <tbody class="list">
                        <?php  //display des skills req des projets ou le user est associé
                        for ($row=0; $row < count($requirementsInMyProjects); $row++) {
                                if ($row % 2 == 0) {
                                echo '<tr>';
                                } else {
                                echo '<tr class="border pure-table-odd">';
                                }

                                echo '<td class="project" >'.$requirementsInMyProjects[$row]['name_project'].'</td>';
                                echo '<td class="scenario">'.$requirementsInMyProjects[$row]['scenario'].'</td>';
                                echo '<td class="skill">'.$requirementsInMyProjects[$row]['name_skill_category']." - ".$requirementsInMyProjects[$row]['name_skill'].'</td>';
                                echo '<td class="skill level">'.$requirementsInMyProjects[$row]['level_requirement'].'</td>';
                                echo '<td class="size">'.$requirementsInMyProjects[$row]['size'].'</td>';
                                echo '</tr>';
                                }
                        ?>

                        </tbody>
                    </table>
                    <script>
                            var options2 = {valueNames: ['project','scenario','role']};

                            // Init list pour la recherche par CMS
                            var varList2 = new List('list_required_project_history_id', options2);
                    </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
