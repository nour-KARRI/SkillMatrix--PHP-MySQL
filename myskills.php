<?php
//onsubmit="return validateSkillForm(true)"
session_start();
$idUser = $_SESSION['ID'];
include 'sqlconnect.php';

$req = $bdd->prepare('SELECT * FROM `user_skill` LEFT JOIN user ON user.ID_user = user_skill.id_user LEFT JOIN skill ON user_skill.id_skill = skill.ID_skill  LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category WHERE user.ID_user ='.$_SESSION['ID']);
$req->execute();
$user_skill = $req->fetchAll();

$req = $bdd->prepare('SELECT * FROM skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category ORDER BY skill.ID_skill ASC');
$req->execute();
$skill = $req->fetchAll();

$req = $bdd->prepare('SELECT name_skill, demand_approval FROM approval JOIN skill ON ID_skill=object_approval WHERE id_user = "'.$idUser .'" ');
$req->execute();
$skillApproval = $req->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Skill Matrix</title>
  <script src="js/jQuery-3.3.1.js"></script>
  <script src="js/list.js"></script>
  <script src="js/formValidation.js"></script>
  <link rel="stylesheet" href="css/marketing.css"/>
  <link rel="stylesheet" href="css/side-menu.css"/>
  <link rel="stylesheet" href="css/pure.css"/>
  <link rel="stylesheet" href="css/skillMatrix.css" />

</head>
<script>

    $(document).ready(function() {
    $('.form_error').css("display", "none");
    addLevelDescription();
    })

    function add_skill(){
        $('#addedSkill').append($('#skillInput').val()+" to level "+$('#level').val()+'\n<br>');
    }

    function submit_all(){

        var lines = $("#addedSkill").text().split("\n");
        $.each(lines, function(n, elem) {
            if(elem.trim().length>1 && elem.trim().substr(0,1).match(/^\d/)){
                var firstPart = elem.trim().substr(elem.trim().indexOf(" : ")+3);
                var skillNameString =firstPart.substr(0,firstPart.indexOf(" to level"));
                //console.log(elem.trim().substr(0,elem.trim().indexOf(" - "))+" / "+elem.trim().substr(elem.trim().indexOf(" : ")+3,elem.trim().indexOf('to level'))+" / "+elem.trim().substr((elem.trim().length)-1));
                $.post("requests.php?op=addUserSkill", {skillId: elem.trim().substr(0,elem.trim().indexOf(" - ")), level : elem.trim().substr((elem.trim().length)-1),skillName : skillNameString})
            }
          });
          window.location = "mySkills.php";
    }

    function addLevelDescription() {
    var value = $('#level').val();
    switch (value) {
        case '1':
        $('#level_description').text("Level description : Beginner, no knowledge, team member just start exploring skill.");
        break;
        case '2':
        $('#level_description').text("Level description : Familiar, shallow and basic knowledge on the skill, active to learn more");
        break;
        case '3':
        $('#level_description').text("Level description : Proficient, deep knowledge");
        break;
        case '4':
        $('#level_description').text("Level description : Expert, know skill inside and out");
        break;
    }
    }

    function display_skill(){ // apparition et disparition du formulaire de maniere dynamique
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

    function info_suppression(id,skillName) {
        var r = confirm("Confirmez vous la suppression de votre skill "+skillName+" ?");
        if (r == true) {
            window.location = "requests.php?op=deleteUserSkill&id_user_skill="+id;
        }
    }

    function update(id,skillName,row, level, skillIDs) {

        var today = new Date();
        var date = today.getDate()+'/'+(today.getMonth()+1)+'/'+today.getFullYear();
        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
        var dateTime = date+' à '+time;

        var sessionId = "<?php echo $_SESSION['ID']; ?>";

        var r = confirm("Confirmez vous la modification de votre skill "+skillName+" ? (Double cliquez sur les champs pour les modifier)");
            if (r == true) {// on fait un enregistrement dans la table de demande de validation
                $.post( "requests.php?op=executeSql", { sqlReq: 'INSERT INTO approval (id_user, type_approval, object_approval, demand_approval) VALUES ( '+sessionId+',"skill" , '+skillIDs+' , '+$("#level"+row).text()+') '} );
            }


    }

    $(function () {
        $(".change").dblclick(function (e) {//permet de changer dynamiquement des valeurs
            e.stopPropagation();
            updateVal($(this), $(this).html());
        });
    });

    function updateVal(currentEle, value) {
        var idInput = "N/A";
        if ( currentEle.hasClass('level')) { // creation d un iput
            $(currentEle).html('<input id="lvl" class="lvl" type ="number" value="1" min="1" max ="4">');
            idInput = "lvl";
        }
        $("#"+idInput).focus();
        $(document).click(function (e) {
            if(e.target.id != idInput )
            {//modifie la valeur de la case
                if($("#"+idInput).val()>3){
                    $(currentEle).html("4");
                }else if($("#"+idInput).val()<1){
                    $(currentEle).html("1");
                }else{
                    $(currentEle).html($("#"+idInput).val());
                }
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
                        <h1>My Skills</h1>
                </div>
                <div id="list_id">
                    <div id="header-list">
                        <input type="text" class="search" placeholder="Search any Category, Skill or Skill level " />
                        <button class="button-success pure-button right" onClick="display_skill()">Add a New Skill</button>
                        <div class="shadow add_user_form_div" id="id_add_skill"> <!-- Ce formulaire est caché-->
                            <h1>Add a new skill</h1>
                            <div class="form_error"></div>
                            <div class="pure-form pure-form-aligned" >
                                <fieldset>
                                  <div class="pure-control-group">
                                    <label for="project">Skill</label>
                                      <input type ="text" class="radius" id ="skillInput" list="skill_list" placeholder="Search.." name="skill" value="">
                                          <datalist id="skill_list">
                                            <?php for($a=0; $a<sizeof($skill);$a++){
                                                echo '<option>'.$skill[$a]['ID_skill']." - ".$skill[$a]['name_skill_category']." : ".$skill[$a]['name_skill'];
                                            }
                                            ?>
                                          </datalist>
                                  </div>
                                    <div class="pure-control-group">
                                        <p id="level_description"></p>
                                        <label for="level">Level</label>
                                        <input type ="number" id="level" name="level" value="1" min="1" max ="4" onclick="addLevelDescription(value)">
                                    </div>
                                    <div class="pure-control-group" id="addedSkill">

                                    </div>
                                    <button onClick="add_skill()" class="pure-button button-success">Add this Skill</button>   
                                    <button onClick="submit_all()" class="pure-button pure-button-primary validSkillButton">Submit All</button>
                                </fieldset>
                            </div>
                      </div>
                    </div>

                    <hr>

                    <table class="pure-table">
                    <thead>
                    <tr>
                        <th>Skill</th>
                        <th>Level</th>
                        <th>Approval Date</th>
                        <th>Action</th>
                    </tr>

                    </thead>
                    <tbody class="list">
                    <?php  //display des skills
                    //' <a href="#" ><strong color="red">You need to re-evaluate your skill every 2 months</strong></a>
                        $now = time();// today date
                        for ($row=0; $row < count($user_skill); $row++) {


                            $new_date = date('d-m-Y', strtotime($user_skill[$row]['date_approval'])); // Europe format date

                            //Calculate days between todayDate and date_approval
                            $dateApproval = strtotime($user_skill[$row]['date_approval']);
                            $datediff = $now - $dateApproval;
                            $numberOfDays= round($datediff / (60 * 60 * 24));


                            if ($row % 2 == 0) {
                            echo '<tr>';
                            } else {
                            echo '<tr class="border pure-table-odd">';
                            }

                            if ($numberOfDays>60) {
                                 echo '<tr bgcolor="#fdd" style="border: solid red;">';
                                 echo '<td  id="skillWarning'.$row.'" class=" skill"> 
                                    <div id="photo_warning">
                                        <img class="left" src="img/warning2.svg" alt="Warning!">
                                            <span class="tooltiptext">You need to re-evaluate your skill every 2 months</span>
                                    </div> 
                                    <div class="tdSkill1">'. $user_skill[$row]['name_skill_category']." - ".$user_skill[$row]['name_skill']. '</div>
                                </td>';
                             }
                             else {
                                echo '<td id="skill'.$row.'" class="skill" >'.$user_skill[$row]['name_skill_category']." - ".$user_skill[$row]['name_skill'].'</td>';
                             }

                            echo '<td id="level'.$row.'" class="change level" title="Double click to update" >'. $user_skill[$row]['level_user_skill'].'</td>';
                            echo '<td id="date'.$row.'">'.$new_date.'</td>';
                            echo '<td><button class="button-error pure-button" onClick="info_suppression(\''.$user_skill[$row]['ID_user_skill'].'\',\''.$user_skill[$row]['name_skill'].'\')"><img width="16px" height="16px" src="./img/svg-delete.svg"></button>
                                <button class="button-success pure-button" style="margin:0 0 0 10px"onClick="update(\''.$user_skill[$row]['ID_user_skill'].'\',\''.$user_skill[$row]['name_skill'].'\',\''.$row.'\', \''.$user_skill[$row]['level_user_skill'].'\',\''.$user_skill[$row]['ID_skill'].'\')"><img width="16px" height="16px" src="./img/white_check.svg"></td>';
                            echo '</tr>';
                            }
                  ?>

                    </tbody>
                </table>

                            <div style="display: block; margin: 40px 0; text-align: center">
                                                    <h1>Waiting for approval</h1>
                                            </div>
                 <table class="pure-table">
                            <thead>
                            <tr>
                                <th>Skill</th>
                                <th>Level</th>
                            </tr>
                            </thead>
                            <tbody class="list">
                            <?php  //display des projectStaff
                                for ($row=0; $row < count($skillApproval); $row++) {
                                    if ($row % 2 == 0) {
                                    echo '<tr>';
                                    } else {
                                    echo '<tr class="border pure-table-odd">';
                                    }
                                    echo '<td id="skill'.$row.'" class=" skill" >'.$skillApproval[$row]['name_skill'].'</td>';

                                    echo '<td class=" level">'. $skillApproval[$row]['demand_approval'].'</td>';

                                    }
                        ?>

                            </tbody>
                </table>

                <script>

                        var options = {valueNames: ['category','skill','level']};

                         //Init list pour la recherche temps réel
                        var varList = new List('list_id', options);



                </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
