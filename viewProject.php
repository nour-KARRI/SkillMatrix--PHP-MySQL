<?php
session_start();
include 'sqlconnect.php';
if(!isset($_GET['id_project'])){
    header('Location: home.php');
}

if (!isset($_SESSION['rights'])) {
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) {
  header('location: home.php');
}

$id_project = $_GET['id_project'];

//on recupere l'historique du projet
$req = $bdd->prepare('SELECT * FROM history LEFT JOIN project ON id_object_history = ID_project WHERE type_history = "project" AND id_object_history = '.$id_project);
$req->execute();
$history = $req->fetchAll();

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

</script>
<body class="preload">

  <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

            <div class="content">

                <div id="userLogs" >
                    <div style="display: block; margin: 120px 0 20px 0; text-align: center">
                        <h1>History of <?php echo $history[0]['name_project'];?></h1>
                    </div>
                    <div id=logs >
                        <?php
                        if(isset($history[0]['object_history'])){ // on display tout l historique du ^rojet
                            for ($row=0; $row < count($history); $row++) {
                                echo $history[$row]['date_history'].': '.$history[$row]['object_history'].'<br>';
                            }

                        }else{
                            echo '<h3>No logs recorded about this object</h3>';//ce cas n est pas censé se produire
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
