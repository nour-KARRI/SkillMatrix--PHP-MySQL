<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) {
  header('location: home.php');
}
//on recupere la liste des managers, si jamais on veut modifier le manager d 'un projet (ID_role manager toujours égal a 1)
$req = $bdd->prepare('SELECT * FROM user LEFT JOIN role ON user.role_user = role.ID_role WHERE ( ID_role=11 OR ID_role=12 ) AND is_out="false"'); //Project manager or Delivery manager
$req->execute();
$managers = $req->fetchAll();

//on recupere tous les bundle
$req = $bdd->prepare('SELECT * FROM bundle');
$req->execute();
$bundle = $req->fetchAll();

//on recupere la liste de tous les scenarios possible ( Si les scenarios changent, lourdes modifications a faire.)
$req = $bdd->prepare('SELECT * FROM scenario');
$req->execute();
$scenario = $req->fetchAll();

//On recupere la liste de tous les projets
$req = $bdd->prepare('SELECT * FROM project LEFT JOIN user ON user.ID_user = project.manager_project LEFT JOIN bundle ON bundle.ID_bundle = project.bundle_project LEFT JOIN prm ON prm.ID_prm = project.prm_project');
$req->execute();
$projects = $req->fetchAll();

//On récupère la liste de toutes les PRMs
$req = $bdd->prepare('SELECT * FROM prm');
$req->execute();
$prms = $req->fetchAll();
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
<script>function add_project(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_add_project').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_project');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}

function info_suppression(id,projectName) { // fonction de suppression de projet
        var r = confirm("Confirmez vous la suppression du projet "+projectName+" ? Cela entrainera la suppression des projects requierements liés");
        if (r == true) {
            window.location = "requests.php?op=deleteProject&idProject="+id;
        }
    }

function update(id,projectName,row){ // modifictaion de projet
    var r = confirm("Confirmez vous la modification du projet "+projectName+" ? (Double cliquez sur les champs pour les modifier)");
        if (r == true) {
            var bundleID='';
            if( ($("#bundle"+row).text()).match(/^\d/)){ // si la valeur commence par un chiffre, alors on a fait un modifictaion et on recupere l 'id du nouveau bundle
                bundleID = " , bundle_project = "+($("#bundle"+row).text()).substring(0,($("#bundle"+row).text()).indexOf(' '))+" ";
            }
            var managerID='';
            if( ($("#manager"+row).text()).match(/^\d/)){// si la valeur commence par un chiffre, alors on a fait un modifictaion et on recupere l 'id du nouveau manager
                managerID = " , manager_project = "+($("#manager"+row).text()).substring(0,($("#manager"+row).text()).indexOf(' '))+" ";
            }

            var prmID='';
            if( ($("#code"+row).text()).match(/^\d/)){// si la valeur commence par un chiffre, alors on a fait un modifictaion et on recupere l 'id du nouveau manager
                prmID = " , prm_project = "+($("#code"+row).text()).substring(0,($("#code"+row).text()).indexOf(' '))+" ";
            }

            var scenarioList=''

            if($('#sceInput0x'+row).length){//si la div existe, c 'est qu'une modification veut etre faite
                $('#sce'+row+' :checkbox:checked').each(function(i){
                scenarioList = scenarioList + $(this).val()+";";//on récupere les scenarios cochés
                });
                scenarioList = " , scenario_project = \""+scenarioList+"\""; //string a rajouté pour l'update
            }
            if ($("#status"+row).text() !== "Active" && $("#status"+row).text() !== "Support" && $("#status"+row).text() != "Sleeping") {
              return ;
            }
            //la modification se fait sans recharger la page            
          $.post( "requests.php?op=executeSql", { sqlReq: "UPDATE project SET name_project = \""+ $("#project"+row).text() + "\"" + ", siglum_project=\""+ $("#siglum"+row).text() + "\"" + ", status_project=\""+ $("#status"+row).text() +"\" "+scenarioList+managerID+bundleID+prmID+" WHERE project.ID_project = "+id});

        }
}

$(function () { //fonction d'initiation de modification
    $(".change").dblclick(function (e) {
        e.stopPropagation();
        var row = (e.target.id).substring((e.target.id).indexOf(e.target.id.match(/\d/)));
        updateVal($(this), $(this).html(),row);
    });
});

function updateVal(currentEle, value,row) { // en fonction de la case que l'on veut modifié, les inputs sont différents
    var idInput = "N/A";
    if ( currentEle.hasClass('bundle')) {
        $(currentEle).html('<select id="bnd" class ="skl" ><?php for($a=0; $a<sizeof($bundle);$a++){echo '<option>'.$bundle[$a]['ID_bundle']." - ".$bundle[$a]['name_bundle'];}?></select>');
        idInput = "bnd";
    }else if(currentEle.hasClass('code')){
              $(currentEle).html('<select id="prm" class ="skl" ><?php for($a=0; $a<sizeof($prms);$a++){echo '<option>'.$prms[$a]['ID_prm']." - ".$prms[$a]['name_prm'];}?></select>');
        idInput = "prm";
    }else if(currentEle.hasClass('project')){
        $(currentEle).html('<input id="pro'+'" type="text" value="' + value + '" />');
        idInput = "pro";
    }else if(currentEle.hasClass('siglum')){
        $(currentEle).html('<input id="sig'+'" type="text" value="' + value + '" />');
        idInput = "sig";
    }else if(currentEle.hasClass('scenario')){
        $(currentEle).html('<div id="sce'+row+'"><?php for($a=0; $a<sizeof($scenario);$a++){ //display de checkbox
                                                    echo '<div style="display:block">';
                                                    echo '<input id="sceInput'.$a.'x';?>'+row+'<?php echo '" class="inline" style="margin-left:5px" type="checkbox" name="scenario[]"  value="'.$scenario[$a]['name_scenario'].'" />';
                                                    echo '<label id="sceInputLabel'.$a.'x';?>'+row+'<?php echo '" style="text-align: left;width:80px;margin-left:5px" for="'.$scenario[$a]['name_scenario'].'">'.$scenario[$a]['name_scenario'].'</label>';
                                                    echo '</div>';
                                                }?></div>');
        idInput = "sceInput";

    }else if(currentEle.hasClass('status')){
              $(currentEle).html('<select id="status_project" class ="skl">\
                                  <option>Active</option>\
                                  <option>Support</option>\
                                  <option>Sleeping</option>\
                                  </select>');
        idInput = "status_project";
    }else if(currentEle.hasClass('manager')){
        $(currentEle).html('<select id="man" class ="skl" ><?php for($a=0; $a<sizeof($managers);$a++){echo '<option>'.$managers[$a]['ID_user']." - ".$managers[$a]['name_user']." ".$managers[$a]['family_name_user'];}?></select>');
        idInput = "man";
    }

    $("#"+idInput).focus();
    $(document).click(function (e) {
        if(e.target.id != idInput  && idInput != "sceInput")
        {
            $(currentEle).html($("#"+idInput).val()); //mise a jour de la valeur
        }
    });
}

</script>
<body class="preload">

  <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">
                <div style="display: block; margin: 40px 0; text-align: center">
                        <h1>All Projects</h1>
                </div>

                <div id="list_id">
                    <div id="header-list">
                        <input type="text" class="search" placeholder="Search any Project, Bundle, Siglum, Code or Manager " />
                        <button class="button-success pure-button right" onClick="add_project()">Add a New Project</button>
                        <div class="shadow add_user_form_div" id="id_add_project"> <!-- Ce formulaire d ajout est caché-->
                            <h1>New Project</h1>
                            <form  class="pure-form pure-form-aligned" action="./requests.php?op=addProject" method="post">
                                <fieldset>
                                    <div class="pure-control-group">
                                        <label for="project">Project Name</label>
                                        <input type ="text" id="project" name="project" placeholder="Project Name">
                                    </div>
                                    <div class="pure-control-group">
                                        <label  for="bundle">Bundle</label>
                                        <select id="bundle" name="bundle" value="">
                                            <?php for($a=0; $a<sizeof($bundle);$a++){
                                                echo '<option>'.$bundle[$a]['ID_bundle']." - ".$bundle[$a]['name_bundle'];
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="pure-control-group">
                                        <label for="manager">Manager</label>
                                        <select id="manager" name="manager" >
                                            <?php for($a=0; $a<sizeof($managers);$a++) {
                                                echo '<option>'.$managers[$a]['ID_user']." - ".$managers[$a]['name_user']." ".$managers[$a]['family_name_user'];
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="pure-control-group">
                                        <label for="code">Project PRM</label>
                                        <select id="code" name="code" >
                                            <?php for($a=0; $a<sizeof($prms);$a++) {
                                                echo '<option>'.$prms[$a]['ID_prm']." - ".$prms[$a]['name_prm'];
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="pure-control-group">
                                    <div class="pure-control-group">
                                        <label for="siglum">Siglum</label>
                                        <input type ="text" id="siglum" name="siglum" placeholder="EYYXX">
                                    </div>
                                    <div class="pure-control-group">
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
                                    <div class="pure-control-group">
                                        <label for="status">Project Status</label>
                                        <select id="status" name="status" >
                                                <option> Active </option>
                                                <option> Support </option>
                                                <option> Sleeping </option>
                                        </select>
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
                        <th>Bundle</th>
                        <th>PRM</th>
                        <th>Project</th>
                        <th>Manager</th>
                        <th>Siglum</th>
                        <th>Scenario</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <?php  //display des projects
                        for ($row=0; $row < count($projects); $row++) {
                            //change presentation of scenarios
                            $scenarioDisplay = str_replace(";"," - ",rtrim($projects[$row]['scenario_project'],';'));

                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            echo '<td id ="bundle'.$row.'" class="change bundle" >'.$projects[$row]['name_bundle'].'</td>';
                            echo '<td id ="code'.$row.'" class="change code" >'.$projects[$row]['name_prm'].'</td>';
                            echo '<td id ="project'.$row.'" class="change project" >'.$projects[$row]['name_project'].'</td>';
                            echo '<td id ="manager'.$row.'" class="change manager" >'.$projects[$row]['name_user']." ".$projects[$row]['family_name_user'].'</td>';
                            echo '<td id ="siglum'.$row.'" class="change siglum" >'.$projects[$row]['siglum_project'].'</td>';
                            echo '<td id ="scenario'.$row.'" class="change scenario" >'.$scenarioDisplay.'</td>';
                            echo '<td id ="status'.$row.'" class="change status" >'.$projects[$row]['status_project'].'</td>';
                            echo '<td> <button class="button-error pure-button" onClick="info_suppression(\''.$projects[$row]['ID_project'].'\',\''.$projects[$row]['name_project'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button> <button class="button-success pure-button" style="margin:0 0 0 10px"onClick="update(\''.$projects[$row]['ID_project'].'\',\''.$projects[$row]['name_project'].'\',\''.$row.'\')"><img width="16px" height="16px" src="./img/svg-update.svg"></td>';
                            echo '</tr>';
                            }
                    ?>

                    </tbody>
                </table>
                <script>
                        var options = {valueNames: ['manager','bundle']};

                         //Init list pour la recherche temps réel
                        var varList = new List('list_id', options);
                </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
