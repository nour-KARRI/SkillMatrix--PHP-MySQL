<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
  header('location: home.php');
}

if ($_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) {
  header('location: home.php');
}
//on recupere la liste des managers, si jamais on veut modifier le manager d 'un bundle (ID_role manager toujours égal a 1)
$req = $bdd->prepare('SELECT * FROM user LEFT JOIN role ON user.role_user = role.ID_role WHERE  ( ID_role=11 OR ID_role=12 ) AND is_out="false" ');
$req->execute();
$managers = $req->fetchAll();
//on recupere la liste des bundle
$req = $bdd->prepare('SELECT * FROM bundle LEFT JOIN user ON bundle.manager_bundle = user.ID_user');
$req->execute();
$bundles = $req->fetchAll();
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
<script>function add_bundle(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_add_bundle').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_bundle');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}

function info_suppression(id,bundleName) {//fonction de suppression du bundle
        var r = confirm("Confirmez vous la suppression du bundle "+bundleName+" ? Cela entrainera la suppression des projects et projects requierements liés ");
        if (r == true) {
            window.location = "requests.php?op=deleteBundle&idBundle="+id;
        }
    }

    function update(id,skillName,row) {// fonction update du bundle
    var r = confirm("Confirmez vous la modification de votre skill "+skillName+" ? (Double cliquez sur les champs pour les modifier)");
        if (r == true) {
            var managerID='';
            if( ($("#manager"+row).text()).match(/^\d/)){// si la div existe et qu'elle commence par un chiffre, on recupere l'id pour la mise a jour du bundle
                managerID = " , manager_bundle = "+($("#manager"+row).text()).substring(0,($("#manager"+row).text()).indexOf(' '))+" ";
            }
            //la modifictaion se fait sans recharger la page
          $.post( "requests.php?op=executeSql", { sqlReq: "UPDATE bundle SET name_bundle = '"+ $("#bundle"+row).text() +"'"+ managerID+"  WHERE bundle.ID_bundle = "+id} );
        }
}

$(function () {
    $(".change").dblclick(function (e) {
        e.stopPropagation();
        updateVal($(this), $(this).html());
    });
});

function updateVal(currentEle, value) { // en fonction de la case que l'on veut modifié, les inputs sont différents
    var idInput = "N/A";
    if ( currentEle.hasClass('bundle')) {
        $(currentEle).html('<input id="bnd" type ="text" value="'+value+'">');
        idInput = "bnd";
    }else if(currentEle.hasClass('manager')){
        $(currentEle).html('<select id="man" ><?php for($a=0; $a<sizeof($managers);$a++){echo '<option>'.$managers[$a]['ID_user']." - ".$managers[$a]['name_user']." ".$managers[$a]['family_name_user'];}?></select>');
        idInput = "man";
    }
    $("#"+idInput).focus();
    $(document).click(function (e) {
        if(e.target.id != idInput )
        {
            $(currentEle).html($("#"+idInput).val());
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
                        <h1>All Bundles</h1>
                </div>
                <div id="list_id">
                    <div id="header-list">
                        <input type="text" class="search" placeholder="Search any Bundle or Manager " />
                        <button class="button-success pure-button right" onClick="add_bundle()">Add a New Bundle</button>
                        <div class="shadow add_user_form_div" id="id_add_bundle"> <!-- Ce formulaire d ajout de bundle est caché-->
                            <h1>New Bundle</h1>
                            <form  class="pure-form pure-form-aligned" action="./requests.php?op=addBundle" method="post">
                                <fieldset>
                                    <div class="pure-control-group">
                                        <label for="bundle">Bundle</label>
                                        <input type ="text" id="bundle" name="bundle" placeholder="Bundle Name">
                                    </div>
                                    <div class="pure-control-group">
                                        <label for="manager">Manager</label>
                                        <input type ="text" list="managers" id="manager" name="manager" value="">
                                        <datalist id=managers>
                                            <?php for($a=0; $a<sizeof($managers);$a++){
                                                echo '<option>'.$managers[$a]['ID_user']." - ".$managers[$a]['name_user']." ".$managers[$a]['family_name_user'];
                                            }
                                            ?>
                                        </datalist>
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
                        <th>Manager</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <?php  //display des projectStaff
                        for ($row=0; $row < count($bundles); $row++) {
                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            echo '<td id="bundle'.$row.'" class="change bundle" >'.$bundles[$row]['name_bundle'].'</td>';
                            echo '<td id="manager'.$row.'" class="change manager" >'.$bundles[$row]['name_user']." ".$bundles[$row]['family_name_user'].'</td>';
                            echo '<td><button class="button-error pure-button" onClick="info_suppression(\''.$bundles[$row]['ID_bundle'].'\',\''.$bundles[$row]['name_bundle'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button> <button class="button-success pure-button" style="margin:0 0 0 10px"onClick="update(\''.$bundles[$row]['ID_bundle'].'\',\''.$bundles[$row]['name_bundle'].'\',\''.$row.'\')"><img width="16px" height="16px" src="./img/svg-update.svg"></td>';
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
