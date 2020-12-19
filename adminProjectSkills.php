<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
  header('location: home.php'); //Ce cas n'est pas censé se produire
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) { // redirection si l'utilisateur est un developper
  header('location: home.php');
}
$req = $bdd->prepare('SELECT * FROM skill_category'); //touts les skills categories
$req->execute();
$category = $req->fetchAll();

$req = $bdd->prepare('SELECT * FROM skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category'); //tous les skill avec les categories
$req->execute();
$skill = $req->fetchAll();

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
<script>function add_skill(){ // apparition et disparition du formulaire de maniere dynamique
    $('#id_add_skill').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_skill');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}

function info_suppression(idSkill,skillName) { //fonction de suppression de skill
        var r = confirm("Confirmez vous la suppression du skill "+skillName+" ? Cela entrainera la suppression des skills associés aux utilisateurs et aux projects requirements");
        if (r == true) {
            window.location = "requests.php?op=deleteSkill&idSkill="+idSkill;
        }

    }
function add_category(){ // apparition et disparition du formulaire de maniere dynamique
  $('#id_add_category').fadeToggle();

  $(document).mouseup(function (e) {
    var container = $('#id_add_category');

    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut();
    }
  });
}

function info_suppression_category(idCategory,categoryName) {//fonction de suppression de skill category
        var r = confirm("Confirmez vous la suppression de la catégorie "+categoryName+" ? Cela entrainera la suppression des skills associés aux utilisateurs et aux projects requirements");
        if (r == true) {
            window.location = "requests.php?op=deleteCategory&idCategory="+idCategory;
        }

    }


</script>
<body class="preload">

    <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

                <div style="display: block; margin: 40px 0; text-align: center">
                        <h1>Admin Project</h1>
                </div>
                <div id="categoryAndSkillLists">
                    <div id="skillList" class="half">
                    <div style="text-align: center">
                        <h2>All Skills</h2>
                    </div>
                        <div id="header-list">
                            <input type="text" class="search" placeholder="Search any Category, Skill or Skill level " />
                            <button class="button-success pure-button right" onClick="add_skill()">Add a Skill</button>
                            <div class="shadow add_user_form_div" id="id_add_skill"> <!-- Ce formulaire d ajout de skill est caché-->
                                <h1>Add a skill</h1>
                                <form  class="pure-form pure-form-aligned" action="./requests.php?op=addSkill" method="post">
                                    <fieldset>
                                        <div class="pure-control-group">
                                            <label for="project">Category</label>
                                            <select id=categories name="category">
                                                <?php for($a=0; $a<sizeof($category);$a++){
                                                    echo '<option>'.$category[$a]['name_skill_category']; // on considere que le name_cqtegory est unique
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="pure-control-group">
                                            <label for="skillName">Skill Name</label>
                                            <input type ="text" id="skillName" name="skillName" value="">
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
                                    <th>Category</th>
                                    <th>Skill</th>
                                    <?php if($_SESSION['rights'] == 13 || $_SESSION['rights'] == 14){
                                        echo '<th>Action</th>';
                                    }
                                    ?>

                                </tr>
                            </thead>
                            <tbody class="list">
                            <?php
                                for ($row=0; $row < count($skill); $row++) {   //affichage de tous les skills de la base
                                    if ($row % 2 == 0) {
                                    echo '<tr>';
                                    } else {
                                    echo '<tr class="border pure-table-odd">';
                                    }
                                    echo '<td class="category" >'.$skill[$row]['name_skill_category'].'</td>';
                                    echo '<td class="skill" >'.$skill[$row]['name_skill'].'</td>';
                                    if($_SESSION['rights'] == 13 || $_SESSION['rights'] == 14 ){
                                        echo '<td><button class="button-error pure-button" onClick="info_suppression(\''.$skill[$row]['ID_skill'].'\',\''.$skill[$row]['name_skill'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button></td>';
                                    }
                                    echo '</tr>';
                                    }
                            ?>

                            </tbody>
                        </table>
                        <script>
                                var options = {valueNames: ['category','skill']};

                                //Init list pour la recherche temps réel
                                var varList = new List('skillList', options);
                        </script>
                    </div>
                    <div id="categoryList" class="half">
                    <div style="text-align: center">
                        <h2>All Skill Categories</h2>
                    </div>
                        <div id="header-list">
                            <input type="text" class="search" placeholder="Search any Category, Skill or Skill level " />
                            <button class="button-success pure-button right" onClick="add_category()">Add a Category</button>
                            <div class="shadow add_user_form_div" id="id_add_category"> <!-- Ce formulaire est caché-->
                                <h1>Add a category</h1>
                                <form  class="pure-form pure-form-aligned" action="./requests.php?op=addCategory" method="post">
                                    <fieldset>
                                        <div class="pure-control-group">
                                            <label for="categoryName">Category Name</label>
                                            <input type ="text" id="categoryName" name="categoryName" value="">
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
                                    <th>Category</th>
                                    <?php if($_SESSION['rights'] == 13 || $_SESSION['rights'] == 14) {
                                        echo '<th>Action</th>';
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody class="list">
                            <?php  //display des category skill
                                for ($row=0; $row < count($category); $row++) {
                                    if ($row % 2 == 0) {
                                    echo '<tr>';
                                    } else {
                                    echo '<tr class="border pure-table-odd">';
                                    }

                                    echo '<td class="category" >'.$category[$row]['name_skill_category'].'</td>';
                                    if($_SESSION['rights'] == 13 || $_SESSION['rights'] == 14 ){
                                        echo '<td><button class="button-error pure-button" onClick="info_suppression_category(\''.$category[$row]['ID_skill_category'].'\',\''.$category[$row]['name_skill_category'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button></td>';
                                    }
                                    echo '</tr>';
                                    }
                            ?>

                            </tbody>
                        </table>
                        <script>
                                var options = {valueNames: ['category']};

                                //Init list pour la recherche temps réel
                                var varList = new List('categoryList', options);
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
