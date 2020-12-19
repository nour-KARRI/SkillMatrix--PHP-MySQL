<?php
session_start();
include 'sqlconnect.php';

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) { // redirection si l'utilisateur est un developper
    header('location: home.php');
  }

// on recupere toutes les demandes de skills des users dont on est le manager
$req = $bdd->prepare('SELECT * FROM approval LEFT JOIN user ON user.ID_user IN (SELECT user FROM project_user RIGHT JOIN project ON project_user.project = project.ID_project WHERE project.manager_project = '.$_SESSION['ID'].') RIGHT JOIN skill ON skill.ID_skill = approval.object_approval WHERE approval.type_approval ="skill" AND approval.id_user = user.ID_user');
$req->execute();
$skill_approval = $req->fetchAll();

if ($_SESSION['rights'] == 13 || $_SESSION['rights'] == 14) { //on recherche les demandes de roles si on est un delivery director
    $req = $bdd->prepare('SELECT * FROM approval LEFT JOIN user ON user.ID_user = approval.id_user LEFT JOIN role ON role.ID_role = approval.demand_approval WHERE approval.type_approval ="role"');
    $req->execute();
    $role_approval = $req->fetchAll();
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
  <script src="js/list.js"></script>
  <link rel="stylesheet" href="css/marketing.css"/>
  <link rel="stylesheet" href="css/side-menu.css"/>
  <link rel="stylesheet" href="css/pure.css"/>
  <link rel="stylesheet" href="css/skillMatrix.css" />
</head>
<script>
    //Les scripts suivants renvoient sur la page requests pour valider ou refuser des demandes de skills ou de roles
function validSkill(id,idUser,idSkill,value,skillName) {
        var r = confirm("Confirmez vous le niveau de "+skillName+" ?");
        if (r == true) {
            window.location = "requests.php?op=validSkillApproval&id_approval="+id+"&id_skill="+idSkill+"&id_user="+idUser+"&new_value="+value+"&skill_name="+skillName;
        }
}
function refuseSkill(id,idUser,idSkill,value,skillName) {
        var r = confirm("Confirmez vous la refus de cette demande ?");
        if (r == true) {
            window.location = "requests.php?op=refuseSkillApproval&id_approval="+id+"&id_skill="+idSkill+"&id_user="+idUser+"&new_value="+value+"&skill_name="+skillName;
        }
}
function validRole(id,idUser,idRole,roleName) {
        var r = confirm("Confirmez vous le role de "+roleName+" ?");
        if (r == true) {
            window.location = "requests.php?op=validRoleApproval&id_approval="+id+"&id_role="+idRole+"&id_user="+idUser+"&role_name="+roleName;
        }
}
function refuseRole(id,idUser,idRole,roleName) {
        var r = confirm("Confirmez vous la refus de cette demande ?");
        if (r == true) {
            window.location = "requests.php?op=refuseRoleApproval&id_approval="+id+"&id_role="+idRole+"&id_user="+idUser+"&role_name="+roleName;
        }
}

</script>
<body class="preload">

    <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">
                <div class="first-title">
                        <h1>Waiting for approval</h1>
                </div>
                <div id="skills">
                    <div style="approval-title">
                            <h2>Skills</h2>
                    </div>
                    <div id="list_skill">
                    <?php if(count($skill_approval)<1){
                            echo  'Pas de roles en attente de validation.';
                            }else{
                            ?>

                        <table class="pure-table">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Skill</th>
                            <th>Level</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody class="list">
                        <?php  //display des approval skill
                            for ($row=0; $row < count($skill_approval); $row++) {
                                if ($row % 2 == 0) {
                                echo '<tr>';
                                } else {
                                echo '<tr class="border pure-table-odd">';
                                }

                                echo '<td class="name" >'.$skill_approval[$row]['name_user']." ".$skill_approval[$row]['family_name_user'].'</td>';
                                echo '<td class="skill">'.$skill_approval[$row]['name_skill'].'</td>';
                                echo '<td class="lvl">'.$skill_approval[$row]['demand_approval'].'</td>';
                                echo '<td><button class="button-error pure-button" onClick="refuseSkill(\''.$skill_approval[$row]['ID_approval'].'\',\''.$skill_approval[$row]['ID_user'].'\',\''.$skill_approval[$row]['ID_skill'].'\',\''.$skill_approval[$row]['demand_approval'].'\',\''.$skill_approval[$row]['name_skill'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button>
                                    <button class="button-success pure-button" style="margin:0 0 0 10px" onClick="validSkill(\''.$skill_approval[$row]['ID_approval'].'\',\''.$skill_approval[$row]['ID_user'].'\',\''.$skill_approval[$row]['ID_skill'].'\',\''.$skill_approval[$row]['demand_approval'].'\',\''.$skill_approval[$row]['name_skill'].'\')"><img width="16px" height="16px" src="./img/white_check.svg"></td>';
                                echo '</tr>';
                                }
                        ?>

                        </tbody>
                    </table>
                    <script>
                            var options = {valueNames: ['name','skill','lvl']};

                            //Init list pour la recherche temps réel
                            var varList = new List('list_skill', options);
                    </script>
                    <?php } ?>
                    </div>
                </div>
                <?php if($_SESSION['rights'] == 13 || $_SESSION['rights'] == 14) {?>
                <div id="role-approval">
                    <div style="approval-title">
                            <h2>Roles</h2>
                    </div>
                    <div id="list_role">
                        <?php if(count($role_approval)<1){
                            echo  'Pas de roles en attente de validation.';
                        }else{
                        ?>
                        <table class="pure-table">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody class="list">
                        <?php  //display des approval role
                            for ($row=0; $row < count($role_approval); $row++) {
                                if ($row % 2 == 0) {
                                echo '<tr>';
                                } else {
                                echo '<tr class="border pure-table-odd">';
                                }

                                echo '<td class="name" >'.$role_approval[$row]['name_user']." ".$role_approval[$row]['family_name_user'].'</td>';
                                echo '<td class="role">'.$role_approval[$row]['name_role'].'</td>';
                                echo '<td><button class="button-error pure-button" onClick="refuseRole(\''.$role_approval[$row]['ID_approval'].'\',\''.$role_approval[$row]['ID_user'].'\',\''.$role_approval[$row]['ID_role'].'\',\''.$role_approval[$row]['name_role'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button>
                                    <button class="button-success pure-button" style="margin:0 0 0 10px" onClick="validRole(\''.$role_approval[$row]['ID_approval'].'\',\''.$role_approval[$row]['ID_user'].'\',\''.$role_approval[$row]['ID_role'].'\',\''.$role_approval[$row]['name_role'].'\')"><img width="16px" height="16px" src="./img/white_check.svg"></td>';
                                echo '</tr>';
                                }
                        ?>

                        </tbody>
                    </table>
                    <script>
                            var options = {valueNames: ['name','role']};

                            //Init list pour la recherche temps réel
                            var varList = new List('list_role', options);
                    </script>
                    <?php } ?>
                    </div>
                </div>
                <?php }?>
            </div>
        </div>
    </div>
</body>
</html>
