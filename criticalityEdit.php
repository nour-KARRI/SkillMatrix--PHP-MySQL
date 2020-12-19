<?php
session_start();

include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
    header('location: home.php');
  }

  if ($_SESSION['rights'] != 13 && $_SESSION['rights'] != 12) { // redirection si pas Delivery Director
    header('location: home.php');
  }

$req = $bdd->prepare('SELECT * FROM criticality ');
$req->execute();
$criticality = $req->fetchAll();
?>

<!DOCTYPE html>
<html>

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

function update(id,level,row) {
    var r = confirm("Confirmez vous la modification de l'influence du niveau "+level+" ? (Double cliquez sur les champs pour les modifier)");
        if (r == true) {
            $.post( "requests.php?op=executeSql", { sqlReq: 'UPDATE criticality SET influence = '+$("#level"+row).text()+' WHERE criticality.ID_criticality = '+id+';'} );
        }
 }

            $(function () {
                $(".change").dblclick(function (e) {
                    e.stopPropagation();
                    updateVal($(this), $(this).html());
                });
            });

            function updateVal(currentEle, value) {
                var idInput = "N/A";
                if ( currentEle.hasClass('level')) {
                    $(currentEle).html('<input id="lvl" class="lvl" type ="number" value="1" min="1" max ="1000">');
                    idInput = "lvl";
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
                        <h1>Edit criticality influence</h1>
                </div>
                <div id="list_id">

                    <hr>

                <table class="pure-table">
                    <thead>
                    <tr>
                        <th>Level</th>
                        <th>Influence</th>
                        <th>Action</th>
                    </tr>

                    </thead>
                    <tbody class="list">
                    <?php
                        for ($row=0; $row < count($criticality); $row++) {
                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            echo '<td id="crit'.$row.'" class=" skill" >'.$criticality[$row]['level'].'</td>';
                            echo '<td id="level'.$row.'" class="change level" title="Double click to update" >'. $criticality[$row]['influence'].'%</td>';
                            echo '<td>
                                <button class="button-success pure-button" onClick="update(\''.$criticality[$row]['ID_criticality'].'\',\''.$criticality[$row]['level'].'\',\''.$row.'\')"><img width="16px" height="16px" src="./img/svg-update.svg"></td>';
                            echo '</tr>';
                            }
                    ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
