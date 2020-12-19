<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) {
  header('location: home.php');
}

$scenarios = '';
$resultToDisplay = [];
$nameBundle = '';
$nameProject = '';
$resultToDisplayBundle = [];

//recuupérations des projets
$req = $bdd->prepare('SELECT * FROM project LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle');
$req->execute();
$projects = $req->fetchAll();

if(isset($_SESSION['ID_project'])){ // si un  projet a été selectioné
    $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project  LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category WHERE project_skill_requirement.id_project= '.$_SESSION['ID_project'].' ORDER BY skill.name_skill ASC');
    $req->execute();
    $projectReq= $req->fetchAll();

    $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN project on project.ID_project = project_user.project LEFT JOIN user_skill ON user_skill.id_user= project_user.user LEFT JOIN skill ON skill.ID_skill= user_skill.id_skill LEFT JOIN skill_category ON skill_category.ID_skill_category = skill.category_skill WHERE project_user.project= '.$_SESSION['ID_project']);
    $req->execute();
    $project_user = $req->fetchAll();

    $requirementsExpectationsAndReality = [];
    $maxLevel= $finalAvgLevel =0;
    $AllLevel=[];
    for ($i=0; $i < count($projectReq); $i++) {// on compare les requierements avec les user_projects dans un tableau
        $requirementsExpectationsAndReality[$i] = array('projectName'=>$projectReq[$i]['name_project'],
                                                        'scenario'=>$projectReq[$i]['scenario'],
                                                        'skillReqName'=>$projectReq[$i]['name_skill_category']." : ".$projectReq[$i]['name_skill'],
                                                        'skillReqSize'=>$projectReq[$i]['size'],
                                                        'skillRealitySize'=>0,
                                                        'skillReqLevel'=>$projectReq[$i]['level_requirement'],
                                                        'skillRealityMaxLevel'=>0,
                                                        'skillRealityAvgLevel'=>0);
        if(!strpos($scenarios,$projectReq[$i]['scenario'])){
            $scenarios = $scenarios.$projectReq[$i]['scenario'];
        }
        for ($j=0; $j < count($project_user); $j++) {
            if($projectReq[$i]['id_skill'] == $project_user[$j]['id_skill'] && $projectReq[$i]['id_project'] == $project_user[$j]['ID_project'] && strpos($project_user[$j]['scenario'],$projectReq[$i]['scenario']) !== false){
                $requirementsExpectationsAndReality[$i]['skillRealitySize']++;
                $AllLevel [] = $project_user[$j]['level_user_skill'];
                if($project_user[$j]['level_user_skill']>$maxLevel){
                    $maxLevel =$project_user[$j]['level_user_skill'];
                }
            }
        }
        $requirementsExpectationsAndReality[$i]['skillRealityMaxLevel'] = $maxLevel;
        if($projectReq[$i]['size'] == 0){
            $requirementsExpectationsAndReality[$i]['skillRealityAvgLevel'] = 0;
        }else{
            rsort($AllLevel); // on va seulement prendre le haut du panier
            for ($k=0; $k < $projectReq[$i]['size']; $k++) {
                if(isset($AllLevel[$k])){
                    $finalAvgLevel = $finalAvgLevel + $AllLevel[$k];
                }
            }
            $requirementsExpectationsAndReality[$i]['skillRealityAvgLevel'] = round((float) $finalAvgLevel/$projectReq[$i]['size'],2);
        }
        $maxLevel= $finalAvgLevel=0;
        $AllLevel=[];
    }

    //on les divise par scenario
    $reqAndRealBySce =  $requirementsExpectationsAndReality;
    $index=0;
    $previousSkill='empty';
    for($row=0; $row<count($reqAndRealBySce);$row++){
        if($previousSkill != $reqAndRealBySce[$row]['skillReqName']){
            $previousSkill =  $reqAndRealBySce[$row]['skillReqName'];
            $resultToDisplay[$index]=array('name_skill'=>$reqAndRealBySce[$row]['skillReqName'],
                                        'sizeReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqSize'],
                                        'sizeAusy'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealitySize'],
                                        'levelReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqLevel'],
                                        'ausyMaxLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityMaxLevel'],
                                        'ausyAvgLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityAvgLevel']);
            if($row+1<count($reqAndRealBySce)){
                if($reqAndRealBySce[$row+1]['skillReqName'] != $previousSkill){
                    $index++;
                }
            }
        }else{
            $resultToDisplay[$index]=array_merge($resultToDisplay[$index],array('sizeReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqSize'],
                                                                                'sizeAusy'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealitySize'],
                                                                                'levelReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqLevel'],
                                                                                'ausyMaxLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityMaxLevel'],
                                                                                'ausyAvgLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityAvgLevel']));
            if($row+1<count($reqAndRealBySce)){
                if($reqAndRealBySce[$row+1]['skillReqName'] != $previousSkill){
                    $index++;
                }
            }
        }

    }

    //Bundle Team Skills

    $idBundle= $projectReq[0]['ID_bundle'];
    $nameBundle= $projectReq[0]['name_bundle'];
    $nameProject= $projectReq[0]['name_project'];

    $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project  LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category WHERE project.bundle_project= '.$idBundle.' ORDER BY project_skill_requirement.id_skill ASC');
    $req->execute();
    $skillBundle = $req->fetchAll();

    $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN project on project.ID_project = project_user.project LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle LEFT JOIN user_skill ON user_skill.id_user= project_user.user LEFT JOIN skill ON skill.ID_skill= user_skill.id_skill LEFT JOIN skill_category ON skill_category.ID_skill_category = skill.category_skill WHERE project.bundle_project= '.$idBundle);
    $req->execute();
    $project_user_bundle = $req->fetchAll();

    $previousSkill ="";
    $index = 0;
    $bundleSize=0;
    for ($i=0; $i < count($skillBundle); $i++) {
        if($previousSkill != $skillBundle[$i]['name_skill'] ){
            $previousSkill = $skillBundle[$i]['name_skill'];
            $resultToDisplayBundle[$index] = array('skillName'=>$skillBundle[$i]['name_skill_category']." : ".$skillBundle[$i]['name_skill']);
        }else{
            continue;
        }
        for ($j=0; $j < count($project_user_bundle); $j++) {
            if($skillBundle[$i]['id_skill'] == $project_user_bundle[$j]['id_skill']){
               if(!array_key_exists(substr($project_user_bundle[$j]['name_user'],0,1).substr($project_user_bundle[$j]['family_name_user'],0,2),$resultToDisplayBundle[$index])){
                    $bundleSize++;
                    $resultToDisplayBundle[$index] = array_merge($resultToDisplayBundle[$index],array(substr($project_user_bundle[$j]['name_user'],0,1).substr($project_user_bundle[$j]['family_name_user'],0,2)=>$project_user_bundle[$j]['level_user_skill']));
               }
            }
        }
        $resultToDisplayBundle[$index]= array_merge($resultToDisplayBundle[$index],array('bundleSize'=>$bundleSize));
        $index++;
        $bundleSize=0;
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
  <script src="js/list.js"></script>
  <script src="js/skillMatrix.js"></script>
  <script src="js/html2canvas.js"></script>
  <link rel="stylesheet" href="css/marketing.css"/>
  <link rel="stylesheet" href="css/side-menu.css"/>
  <link rel="stylesheet" href="css/pure.css"/>
  <link rel="stylesheet" href="css/skillMatrix.css" />
</head>
<body class="preload">

  <?php include 'header.php'; ?>

    <div id="layout">

        <div id="main">

                <?php
                if(!isset($_SESSION['ID_project'])){
                ?>
                    <div style="display: block; margin: 40px 0; text-align: center">
                            <h1>What project do you want to watch ?</h1>
                    </div>
                    <div style="text-align: center">
                        <form  class="pure-form pure-form-aligned" action="./requests.php?op=initProjectId" method="post">
                            <fieldset>
                                <label for="project">List of Projects : </label>
                                <select  id="project" name="project">
                                    <?php for($a=0; $a<sizeof($projects);$a++){
                                        echo '<option>'.$projects[$a]['ID_project']." - ".$projects[$a]['name_bundle']." : ".$projects[$a]['name_project'];
                                    }
                                    ?>
                                </select>
                                <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                            </fieldset>
                        </form>
                    </div>
                <?php
                }else{
                ?>
                <div id="project_skills">
                    <div style="display: block; margin: 40px 0; text-align: center">
                            <h1>Project Team Skills : <?php echo $nameProject;?></h1>
                    </div>

                    <div id="list_id">
                        <div id="header-list">
                          <div>
                              <div class="inline">
                                  <input id="activeFilter" class="filterCheckBox" type="checkbox" onchange="active(this)"> <b> Hide Active </b>
                                  <br>
                                  <input class="filterCheckBox" type="checkbox" onchange="support(this)"> <b>Hide Support </b>
                                  <br>
                                  <input class="filterCheckBox" type="checkbox" onchange="sleeping(this)"> <b>Hide Sleeping </b>
                              </div>
                              <div class="inline" id="skillsFilter">
                                  <br>
                                  <input class="filterCheckBox" type="checkbox" onchange="functional(this)"> <b>Hide Functional Skills </b>
                                  <br>
                                  <input class="filterCheckBox" type="checkbox" onchange="technical(this)"> <b>Hide Technical Skills</b>
                                  <br>
                                  <button  class="pure-button pure-button-primary" onClick="exportImg()">Export to PNG</button>
                              </div>
                          </div>
                            <input type="text" style="margin-top:16px" class="search" placeholder="Search any Skill Name" />
                        <form  class="pure-form pure-form-aligned right" action="./requests.php?op=initProjectId" method="post">
                            <fieldset>
                                <label for="project">Watch another project :</label>
                                  <input type ="text" class="radius" list="project_list" placeholder="Search.." name="project" value="">

                                      <datalist id=project_list>
                                        <?php for($a=0; $a<sizeof($projects);$a++){
                                            echo '<option>'.$projects[$a]['ID_project']." - ".$projects[$a]['name_bundle']." : ".$projects[$a]['name_project'];
                                        }
                                        ?>
                                      </datalist>
                                <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                            </fieldset>
                        </form>
                        </div>
                        <hr>
                        <div style="position:relative">

                            <div id="idSkillProjectTableDiv">
                                <table id="idSkillProjectTable" class="pure-table">
                                <thead>
                                <tr>
                                    <th><div class="large-cell">Skill Name<div></th>
                                    <?php
                                        if(!(strpos($scenarios,'Active')=== false)){
                                    ?>
                                    <th>Size Required<br>(Active)</th>
                                    <th>Ausy Size<br>(Active)</th>
                                    <th>Size Compliance<br>(Active)</th>
                                    <th>Level Required<br>(Active)</th>
                                    <th>Ausy Max Level<br>(Active)</th>
                                    <th>Ausy Avg Level<br>(Active)</th>
                                    <th>Level Compliance<br>(Active)</th>
                                    <?php
                                        }
                                        if(!(strpos($scenarios,'Support')=== false)){
                                    ?>
                                    <th>Size Required<br>(Support)</th>
                                    <th>Ausy Size<br>(Support)</th>
                                    <th>Size Compliance<br>(Support)</th>
                                    <th>Level Required<br>(Support)</th>
                                    <th>Ausy Max Level<br>(Support)</th>
                                    <th>Ausy Avg Level<br>(Support)</th>
                                    <th>Level Compliance<br>(Support)</th>
                                    <?php
                                        }
                                        if(!(strpos($scenarios,'Sleeping') === false)){
                                    ?>
                                    <th>Size Required<br>(Sleeping)</th>
                                    <th>Ausy Size<br>(Sleeping)</th>
                                    <th>Size Compliance<br>(Sleeping)</th>
                                    <th>Level Required<br>(Sleeping)</th>
                                    <th>Ausy Max Level<br>(Sleeping)</th>
                                    <th>Ausy Avg Level<br>(Sleeping)</th>
                                    <th>Level Compliance<br>(Sleeping)</th>
                                    <?php
                                        }
                                    ?>
                                </tr>

                                <tr>
                                  <th></th>
                                  <?php
                                      if(!(strpos($scenarios,'Active')=== false)){
                                  ?>
                                      <th style="border-left: 2px solid black; border-right: 2px solid black; border-top: 2px solid black;" colspan="7">Active</th>
                                  <?php
                                      }
                                      if(!(strpos($scenarios,'Support')=== false)){
                                  ?>
                                      <th style="border-left: 2px solid black; border-right: 2px solid black; border-top: 2px solid black;" colspan="7">Support</th>
                                  <?php
                                      }
                                      if(!(strpos($scenarios,'Sleeping') === false)){
                                  ?>
                                      <th style="border-left: 2px solid black; border-right: 2px solid black; border-top: 2px solid black;" colspan="7">Sleeping</th>
                                  <?php
                                      }
                                  ?>
                                </tr>
                                </thead>
                                <tbody class="list">
                                <?php  //display des projectStaff
                                    for ($row=0; $row < count($resultToDisplay); $row++) {

                                        // if ($row % 2 == 0) {
                                        // echo '<tr>';
                                        // } else {
                                        // echo '<tr class="border pure-table-odd">';
                                        // }
                                        echo '<tr>';
                                        echo '<td class="skillName" >'.$resultToDisplay[$row]['name_skill'].'</td>';

                                        if(!(strpos($scenarios,'Active')=== false)){// si le projet est prevu pour le scenario active
                                            if(!isset($resultToDisplay[$row]['sizeReqActive'])){ //si il n'ya pas de scenario active
                                                echo '<td class="sizeReqSleeping" >N/A</td>';
                                                echo '<td class="sizeAusySleeping" >N/A</td>';
                                                echo '<td class="complianceSizeActive">N/A</td>';
                                                echo '<td class="levelReqSleeping" >N/A</td>';
                                                echo '<td class="ausyMaxLevelSleeping" >N/A</td>';
                                                echo '<td class="ausyAvgLevelSleeping" >N/A</td>';
                                                echo '<td class="complianceLevelActive">N/A</td>';
                                            }else{
                                                if ($row === count($resultToDisplay) - 1) {
                                                  echo '<td class="sizeReqActiveBottom" >'.$resultToDisplay[$row]['sizeReqActive'].'</td>';
                                                  echo '<td class="sizeAusyActiveBottom" >'.$resultToDisplay[$row]['sizeAusyActive'].'</td>';
                                                  echo '<td class="complianceSizeActiveBottom"> <img width="16px" height="16px" ';
                                                  if($resultToDisplay[$row]['sizeAusyActive']>=$resultToDisplay[$row]['sizeReqActive']){
                                                      echo 'src="./img/check.png">';
                                                  }elseif($resultToDisplay[$row]['sizeAusyActive']<$resultToDisplay[$row]['sizeReqActive'] && $resultToDisplay[$row]['sizeAusyActive']>0){ echo 'src="./img/warning3.svg">';}
                                                  elseif($resultToDisplay[$row]['sizeAusyActive']==0){echo 'src="./img/wrong.png">';}
                                                  echo'</td>';
                                                  echo '<td class="levelReqActiveBottom" >'.$resultToDisplay[$row]['levelReqActive'].'</td>';
                                                  echo '<td class="ausyMaxLevelActiveBottom" >'.$resultToDisplay[$row]['ausyMaxLevelActive'].'</td>';
                                                  echo '<td class="ausyAvgLevelActiveBottom" >'.$resultToDisplay[$row]['ausyAvgLevelActive'].'</td>';
                                                  echo '<td class="complianceLevelActiveBottom"> <img width="16px" height="16px" ';
                                                  if($resultToDisplay[$row]['ausyAvgLevelActive']>=$resultToDisplay[$row]['levelReqActive']){
                                                      echo 'src="./img/check.png">';
                                                  }elseif($resultToDisplay[$row]['ausyAvgLevelActive']<$resultToDisplay[$row]['levelReqActive'] && $resultToDisplay[$row]['ausyAvgLevelActive']>0){ echo 'src="./img/warning3.svg">';}
                                                  elseif($resultToDisplay[$row]['ausyAvgLevelActive']==0){echo 'src="./img/wrong.png">';}
                                                  echo'</td>';
                                                }
                                                else { // gestion de la derniere ligne differente pour gerer l'encadrement des scenarios
                                                  echo '<td class="sizeReqActive" >'.$resultToDisplay[$row]['sizeReqActive'].'</td>';

                                                echo '<td class="sizeAusyActive" >'.$resultToDisplay[$row]['sizeAusyActive'].'</td>';
                                                echo '<td class="complianceSizeActive"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['sizeAusyActive']>=$resultToDisplay[$row]['sizeReqActive']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['sizeAusyActive']<$resultToDisplay[$row]['sizeReqActive'] && $resultToDisplay[$row]['sizeAusyActive']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['sizeAusyActive']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                                echo '<td class="levelReqActive" >'.$resultToDisplay[$row]['levelReqActive'].'</td>';
                                                echo '<td class="ausyMaxLevelActive" >'.$resultToDisplay[$row]['ausyMaxLevelActive'].'</td>';
                                                echo '<td class="ausyAvgLevelActive" >'.$resultToDisplay[$row]['ausyAvgLevelActive'].'</td>';
                                                echo '<td class="complianceLevelActive"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['ausyAvgLevelActive']>=$resultToDisplay[$row]['levelReqActive']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['ausyAvgLevelActive']<$resultToDisplay[$row]['levelReqActive'] && $resultToDisplay[$row]['ausyAvgLevelActive']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['ausyAvgLevelActive']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                              }
                                            }
                                        }
                                        if(!(strpos($scenarios,'Support')=== false)){// si il le projet est prevu au niveau support
                                            if(!isset($resultToDisplay[$row]['sizeReqSupport'])){ //si il n'ya pas de scenario support requis
                                                echo '<td class="sizeReqSleeping" >N/A</td>';
                                                echo '<td class="sizeAusySleeping" >N/A</td>';
                                                echo '<td class="complianceSizeSupport">N/A</td>';
                                                echo '<td class="levelReqSleeping" >N/A</td>';
                                                echo '<td class="ausyMaxLevelSleeping" >N/A</td>';
                                                echo '<td class="ausyAvgLevelSleeping" >N/A</td>';
                                                echo '<td class="complianceLevelSupport">N/A</td>';
                                            }else{
                                                if ($row === count($resultToDisplay) - 1) {
                                                echo '<td class="sizeReqSupportBottom" >'.$resultToDisplay[$row]['sizeReqSupport'].'</td>';
                                                echo '<td class="sizeAusySupportBottom" >'.$resultToDisplay[$row]['sizeAusySupport'].'</td>';
                                                echo '<td class="complianceSizeSupportBottom"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['sizeAusySupport']>=$resultToDisplay[$row]['sizeReqSupport']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['sizeAusySupport']<$resultToDisplay[$row]['sizeReqSupport'] && $resultToDisplay[$row]['sizeAusySupport']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['sizeAusySupport']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                                echo '<td class="levelReqSupportBottom" >'.$resultToDisplay[$row]['levelReqSupport'].'</td>';
                                                echo '<td class="ausyMaxLevelSupportBottom" >'.$resultToDisplay[$row]['ausyMaxLevelSupport'].'</td>';
                                                echo '<td class="ausyAvgLevelSupportBottom" >'.$resultToDisplay[$row]['ausyAvgLevelSupport'].'</td>';
                                                echo '<td class="complianceLevelSupportBottom"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['ausyAvgLevelSupport']>=$resultToDisplay[$row]['levelReqSupport']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['ausyAvgLevelSupport']<$resultToDisplay[$row]['levelReqSupport'] && $resultToDisplay[$row]['ausyAvgLevelSupport']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['ausyAvgLevelSupport']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                              }
                                              else {
                                                echo '<td class="sizeReqSupport" >'.$resultToDisplay[$row]['sizeReqSupport'].'</td>';
                                                echo '<td class="sizeAusySupport" >'.$resultToDisplay[$row]['sizeAusySupport'].'</td>';
                                                echo '<td class="complianceSizeSupport"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['sizeAusySupport']>=$resultToDisplay[$row]['sizeReqSupport']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['sizeAusySupport']<$resultToDisplay[$row]['sizeReqSupport'] && $resultToDisplay[$row]['sizeAusySupport']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['sizeAusySupport']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                                echo '<td class="levelReqSupport" >'.$resultToDisplay[$row]['levelReqSupport'].'</td>';
                                                echo '<td class="ausyMaxLevelSupport" >'.$resultToDisplay[$row]['ausyMaxLevelSupport'].'</td>';
                                                echo '<td class="ausyAvgLevelSupport" >'.$resultToDisplay[$row]['ausyAvgLevelSupport'].'</td>';
                                                echo '<td class="complianceLevelSupport"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['ausyAvgLevelSupport']>=$resultToDisplay[$row]['levelReqSupport']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['ausyAvgLevelSupport']<$resultToDisplay[$row]['levelReqSupport'] && $resultToDisplay[$row]['ausyAvgLevelSupport']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['ausyAvgLevelSupport']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                              }
                                            }
                                        }
                                        if(!(strpos($scenarios,'Sleeping') === false)){
                                            if(!isset($resultToDisplay[$row]['sizeReqSleeping'])){
                                                echo '<td class="sizeReqSleeping" >N/A</td>';
                                                echo '<td class="sizeAusySleeping" >N/A</td>';
                                                echo '<td class="complianceSizeSleeping">N/A</td>';
                                                echo '<td class="levelReqSleeping" >N/A</td>';
                                                echo '<td class="ausyMaxLevelSleeping" >N/A</td>';
                                                echo '<td class="ausyAvgLevelSleeping" >N/A</td>';
                                                echo '<td class="complianceLevelSleeping">N/A</td>';
                                            }else{
                                              if ($row === count($resultToDisplay) - 1) {
                                                echo '<td class="sizeReqSleepingBottom" >'.$resultToDisplay[$row]['sizeReqSleeping'].'</td>';
                                                echo '<td class="sizeAusySleepingBottom" >'.$resultToDisplay[$row]['sizeAusySleeping'].'</td>';
                                                echo '<td class="complianceSizeSleepingBottom"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['sizeAusySleeping']>=$resultToDisplay[$row]['sizeReqSleeping']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['sizeAusySleeping']<$resultToDisplay[$row]['sizeReqSleeping'] && $resultToDisplay[$row]['sizeAusySleeping']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['sizeAusySleeping']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                                echo '<td class="levelReqSleepingBottom" >'.$resultToDisplay[$row]['levelReqSleeping'].'</td>';
                                                echo '<td class="ausyMaxLevelSleepingBottom" >'.$resultToDisplay[$row]['ausyMaxLevelSleeping'].'</td>';
                                                echo '<td class="ausyAvgLevelSleepingBottom" >'.$resultToDisplay[$row]['ausyAvgLevelSleeping'].'</td>';
                                                echo '<td class="complianceLevelSleepingBottom"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['ausyAvgLevelSleeping']>=$resultToDisplay[$row]['levelReqSleeping']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['ausyAvgLevelSleeping']<$resultToDisplay[$row]['levelReqSleeping'] && $resultToDisplay[$row]['ausyAvgLevelSleeping']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['ausyAvgLevelSleeping']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                              }
                                              else {
                                                echo '<td class="sizeReqSleeping" >'.$resultToDisplay[$row]['sizeReqSleeping'].'</td>';
                                                echo '<td class="sizeAusySleeping" >'.$resultToDisplay[$row]['sizeAusySleeping'].'</td>';
                                                echo '<td class="complianceSizeSleeping"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['sizeAusySleeping']>=$resultToDisplay[$row]['sizeReqSleeping']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['sizeAusySleeping']<$resultToDisplay[$row]['sizeReqSleeping'] && $resultToDisplay[$row]['sizeAusySleeping']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['sizeAusySleeping']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                                echo '<td class="levelReqSleeping" >'.$resultToDisplay[$row]['levelReqSleeping'].'</td>';
                                                echo '<td class="ausyMaxLevelSleeping" >'.$resultToDisplay[$row]['ausyMaxLevelSleeping'].'</td>';
                                                echo '<td class="ausyAvgLevelSleeping" >'.$resultToDisplay[$row]['ausyAvgLevelSleeping'].'</td>';
                                                echo '<td class="complianceLevelSleeping"> <img width="16px" height="16px" ';
                                                if($resultToDisplay[$row]['ausyAvgLevelSleeping']>=$resultToDisplay[$row]['levelReqSleeping']){
                                                    echo 'src="./img/check.png">';
                                                }elseif($resultToDisplay[$row]['ausyAvgLevelSleeping']<$resultToDisplay[$row]['levelReqSleeping'] && $resultToDisplay[$row]['ausyAvgLevelSleeping']>0){ echo 'src="./img/warning3.svg">';}
                                                elseif($resultToDisplay[$row]['ausyAvgLevelSleeping']==0){echo 'src="./img/wrong.png">';}
                                                echo'</td>';
                                              }
                                            }
                                        }
                                        echo '</tr>';
                                    }
                                ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <script>
                            var options = {valueNames: ['skillName']};

                            // Init list pour la recherche par CMS
                            var varList = new List('list_id', options);
                    </script>
                    </div>
                </div>
                <div id="bundle_skills">
                    <div style="display: block; margin: 40px 0; text-align: center">
                            <h1>Bundle Team Skills : <?php echo $nameBundle;?></h1>
                    </div>

                    <div id="list_id">
                        <div id="header-list">
                          <div>
                              <div class="inline" id="skillsFilter">
                                  <br>
                                  <input class="filterCheckBox" type="checkbox" onchange="functionalBundle(this)"> <b>Hide Functional Skills </b>
                                  <br>
                                  <input class="filterCheckBox" type="checkbox" onchange="technicalBundle(this)"> <b>Hide Technical Skills</b>
                              </div>
                          </div>
                            <input type="text" style="margin-top:16px" class="search" placeholder="Search any Skill Name" />
                        </div>
                        <hr>
                        <div style="position:relative">
                            <div id="idSkillBundleTableDiv">
                                <table id="idSkillBundleTable" class="pure-table">
                                    <thead>
                                        <tr>
                                            <th>Skill Name</th>
                                            <th>Bundle Size</th>
                                            <?php
                                            $persons = [];
                                            //display du level du des skills dans le bundle
                                            foreach ($resultToDisplayBundle as $array)
                                            {
                                                foreach ($array as $key=>$value)
                                                {
                                                    if(strlen($key) ==3 && !in_array($key,$persons)){
                                                        echo'<th>Level of '.$key.'</th>';
                                                        $persons[] = $key;
                                                    }
                                                }
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                    <?php
                                    for ($row=0; $row < count($resultToDisplayBundle); $row++) {

                                        if ($row % 2 == 0) {
                                        echo '<tr>';
                                        } else {
                                        echo '<tr class="border pure-table-odd">';
                                        }

                                        echo '<td class="skillName" >'.$resultToDisplayBundle[$row]['skillName'].'</td>';
                                        echo '<td class="bundleSize" >'.$resultToDisplayBundle[$row]['bundleSize'].'</td>';
                                        for ($a=0; $a < count($persons); $a++) {
                                            echo '<td class="'.$persons[$a].'" >';
                                                if(isset($resultToDisplayBundle[$row][$persons[$a]])){
                                                    echo $resultToDisplayBundle[$row][$persons[$a]];
                                                }
                                            echo '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                    </tbody>
                              </table>
                            </div>
                    </div>
                <?php }//fin de la verif var session 'ID_project'
                ?>
        </div>
    </div>
    <script>
    function addCellColors() {
        classesNames = ['sizeAusyActive', 'sizeAusyActiveBottom', 'ausyAvgLevelActive', 'ausyAvgLevelActiveBottom', 'sizeAusySupport', 'sizeAusySupportBottom',
         'ausyAvgLevelSupport', 'ausyAvgLevelSupportBottom', 'sizeAusySleeping', 'sizeAusySleepingBottom', 'ausyAvgLevelSleeping', 'ausyAvgLevelSleepingBottom'];
        var table = document.getElementById('idSkillProjectTable');
        for (var i = 0, row; row = table.rows[i]; i++) {
           //iterate through rows
           //rows would be accessed using the "row" variable assigned in the for loop
           for (var j = 0, col; col = row.cells[j]; j++) {

             if (classesNames.includes(row.cells[j].className) === true) {
               const skillValue = parseFloat(row.cells[j].innerText);
               
               if (skillValue < 2) {
                 row.cells[j].style.backgroundColor = "#DCEFE9";
               }
               else if (skillValue >= 2 && skillValue < 3) {
                 row.cells[j].style.backgroundColor = "#C0E3CB";
               }
               else if (skillValue >= 3 && skillValue < 4) {
                 row.cells[j].style.backgroundColor = "#8ACE9D";
               }
               else if (skillValue >= 4){
                 row.cells[j].style.backgroundColor = "#62BF79";
               }
             }
             //if the item class name match one in the classNames array, put its background in green.
             //iterate through columns
             //columns would be accessed using the "col" variable assigned in the for loop
           }
        }
    }
    addCellColors();
  </script>
</body>
</html>
