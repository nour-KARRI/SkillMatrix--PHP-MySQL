<?php
session_start();

include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
    header('location: home.php');
  }

  if ($_SESSION['rights'] != 13 && $_SESSION['rights'] != 15) { // redirection si pas Delivery Director
    header('location: home.php');
  }

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
<script>
function add_prm(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_add_prm').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_prm');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}

function info_suppression(id, prmName) { // fonction de suppression de projet
        var r = confirm("Confirmez vous la suppression de la PRM "+ prmName+" ?");
        if (r == true) {
            window.location = "requests.php?op=deletePRM&idPRM="+id;
        }
    }

function update(id, prmName, row){ // modifictaion de projet
    var r = confirm("Confirmez vous la modification de la PRM "+prmName+" ? (Double cliquez sur les champs pour les modifier)");
        if (r == true) {

            var prmID='';
            if( ($("#code"+row).text()).match(/^\d/)){// si la valeur commence par un chiffre, alors on a fait un modifictaion et on recupere l 'id du nouveau manager
                prmID = " , prm_project = "+($("#code"+row).text()).substring(0,($("#code"+row).text()).indexOf(' '))+" ";
            }
            //la modification se fait sans recharger la page
          $.post( "requests.php?op=executeSql", { sqlReq: "UPDATE prm SET name_prm = \""+ $("#prm"+row).text() + "\" "+prmID+" WHERE prm.ID_prm = "+id});
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
    if(currentEle.hasClass('prm')){
        $(currentEle).html('<input id="prm_name'+'" type="text" value="' + value + '" />');
        idInput = "prm_name";
    }

    $("#"+idInput).focus();
    $(document).click(function (e) {
        if(e.target.id != idInput)
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
                        <h1>All PRMS</h1>
                </div>
                <div id="list_id">
                    <div id="header-list">
                        <input type="text" class="search" placeholder="Search any PRM" />
                        <button class="button-success pure-button right" onClick="add_prm()">Add a New PRM</button>
                        <div class="shadow add_user_form_div" id="id_add_prm"> <!-- Ce formulaire d ajout est caché-->
                            <h1>New PRM</h1>
                            <form  class="pure-form pure-form-aligned" action="./requests.php?op=addPRM" method="post">
                                <fieldset>
                                    <div class="pure-control-group">
                                        <label for="prm">PRM Name</label>
                                        <input type ="text" id="prm" name="prm" placeholder="PRM Name">
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
                        <th>PRM</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <?php  //display des projects
                        for ($row=0; $row < count($prms); $row++) {
                            //change presentation of scenarios

                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            echo '<td id ="prm'.$row.'" class="change prm" >'.$prms[$row]['name_prm'].'</td>';
                            echo '<td> <button class="button-error pure-button" onClick="info_suppression(\''.$prms[$row]['ID_prm'].'\',\''.$prms[$row]['name_prm'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button>
                                       <button class="button-success pure-button" style="margin:0 0 0 10px"onClick="update(\''.$prms[$row]['ID_prm'].'\',\''.$prms[$row]['name_prm'].'\',\''.$row.'\')"><img width="16px" height="16px" src="./img/svg-update.svg">
                                  </td>';
                            echo '</tr>';
                            }
                    ?>

                    </tbody>
                </table>
                <script>
                        var options = {valueNames: ['prm']};

                         //Init list pour la recherche temps réel
                        var varList = new List('list_id', options);
                </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
