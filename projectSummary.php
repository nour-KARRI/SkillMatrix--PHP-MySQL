<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) { // redirection si devleopper
  header('location: home.php');
}

function reduceName($name){ // fonction pour faire apparaitre des retour a la ligne dans les noms de skills trop longs
    if (strlen($name)>20){
        $fisrtPart = substr($name, 0,strpos($name, " ",15) );
        $secondPart = substr($name,strpos($name, " ",15) );

        if (strrpos($secondPart, " ")>10){
            $thirdPart = substr($secondPart,strpos($secondPart, " ",10));
            $secondPart = substr($secondPart,0,strpos($secondPart, " ",10));
            if(strlen($thirdPart)>17){
                $thirdPart= substr($thirdPart,0,17).'...';
            }
            return "[\"".$fisrtPart."\",\"".$secondPart."\",\"".$thirdPart."\"]";
        }else{
            return "[\"".$fisrtPart."\",\"".$secondPart."\"]";
        }

    }else{
        return '"'.$name."\"";
    }
}

$complianceSizeActiveRatio  = $complianceSizeSupportRatio = $complianceSizeSleepingRatio = 0;
$complianceLevelActiveRatio  = $complianceLevelSupportRatio =  $complianceLevelSleepingRatio = 0;

$scenarios = '';
$nameProject = '';
$resultToDisplay = [];
$maxSize= 4;

$req = $bdd->prepare('SELECT * FROM project LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle');
$req->execute();
$projects = $req->fetchAll();

if(isset($_SESSION['ID_project'])){
    $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project  LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category LEFT JOIN criticality ON project_skill_requirement.skill_criticality = criticality.ID_criticality WHERE project_skill_requirement.id_project= '.$_SESSION['ID_project'].' ORDER BY skill.name_skill ASC');
    $req->execute();
    $projectReq= $req->fetchAll();

    $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN project on project.ID_project = project_user.project LEFT JOIN user_skill ON user_skill.id_user= project_user.user LEFT JOIN skill ON skill.ID_skill= user_skill.id_skill LEFT JOIN skill_category ON skill_category.ID_skill_category = skill.category_skill WHERE project_user.project= '.$_SESSION['ID_project']);
    $req->execute();
    $project_user = $req->fetchAll();

    //On calcule pour chaque requieremnts Les capacités actuelles et les capacités souhaitees.
    $nameProject= $projectReq[0]['name_project'];
    $requirementsExpectationsAndReality = [];
    $maxLevel= $finalAvgLevel=0;
    $AllLevel=[];
    for ($i=0; $i < count($projectReq); $i++) { // pour tous les skill requieremnt
        $requirementsExpectationsAndReality[$i] = array('projectName'=>$projectReq[$i]['name_project'],
                                                        'scenario'=>$projectReq[$i]['scenario'],
                                                        'skillReqName'=>$projectReq[$i]['name_skill'],
                                                        'skillReqCriticality'=>$projectReq[$i]['influence'],
                                                        'skillReqSize'=>$projectReq[$i]['size'],
                                                        'skillRealitySize'=>0,
                                                        'skillReqLevel'=>$projectReq[$i]['level_requirement'],
                                                        'skillRealityMaxLevel'=>0,
                                                        'skillRealityAvgLevel'=>0);
        if(!strpos($scenarios,$projectReq[$i]['scenario'])){
            $scenarios = $scenarios.$projectReq[$i]['scenario'];
        }
        if($maxSize<$projectReq[$i]['size']){
            $maxSize = $projectReq[$i]['size'];
        }
        for ($j=0; $j < count($project_user); $j++) {
            if($projectReq[$i]['id_skill'] == $project_user[$j]['id_skill'] && $projectReq[$i]['id_project'] == $project_user[$j]['ID_project'] && strpos($project_user[$j]['scenario'],$projectReq[$i]['scenario']) !== false){
                $requirementsExpectationsAndReality[$i]['skillRealitySize']++;
                $AllLevel[] =  $project_user[$j]['level_user_skill'];
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
            $requirementsExpectationsAndReality[$i]['skillRealityAvgLevel'] = round((float) $finalAvgLevel/$requirementsExpectationsAndReality[$i]['skillReqSize'],2);
        }
        $maxLevel= $finalAvgLevel=0;
        $AllLevel=[];
    }

    //on sépare ensuite par scénario
    $reqAndRealBySce =  $requirementsExpectationsAndReality;
    $index=0;
    $previousSkill='empty';
    for($row=0; $row<count($reqAndRealBySce);$row++){
        if($previousSkill != $reqAndRealBySce[$row]['skillReqName']){
            $previousSkill =  $reqAndRealBySce[$row]['skillReqName'];
            $resultToDisplay[$index]=array('name_skill'=>$reqAndRealBySce[$row]['skillReqName'],
                                        'skillReqCriticality'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqCriticality'],
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
            $resultToDisplay[$index]=array_merge($resultToDisplay[$index],array('skillReqCriticality'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqCriticality'],
                                                                                'sizeReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqSize'],
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

    //calcul Compliance (beaucoup de lignes car 6 cas a traiter, mais rien de difficile)

$complianceSizeActiveTotalReq = $complianceSizeActiveTotalAusy = $complianceSizeSupportTotalReq =$complianceSizeSupportTotalAusy= $complianceSizeSleepingTotalReq = $complianceSizeSleepingTotalAusy = 0;
$complianceLevelActiveTotalReq = $complianceLevelActiveTotalAusy = $complianceLevelSupportTotalReq = $complianceLevelSupportTotalAusy =  $complianceLevelSleepingTotalReq = $complianceLevelSleepingTotalAusy = 0;
$critInfluenceActive = $critInfluenceSupport = $critInfluenceSleeping = 1;

    for ($row=0; $row < count($resultToDisplay); $row++)
    {
        //criticality
        if(isset($resultToDisplay[$row]['skillReqCriticalityActive'])){
            $critInfluenceActive = $resultToDisplay[$row]['skillReqCriticalityActive']*0.01;
        }
        if(isset($resultToDisplay[$row]['skillReqCriticalitySupport'])){
            $critInfluenceSupport = $resultToDisplay[$row]['skillReqCriticalitySupport']*0.01;
        }
        if(isset($resultToDisplay[$row]['skillReqCriticalitySleeping'])){
            $critInfluenceSleeping = $resultToDisplay[$row]['skillReqCriticalitySleeping']*0.01;
    }
        //size
        if(isset($resultToDisplay[$row]['sizeReqActive']) && isset($resultToDisplay[$row]['sizeAusyActive'])){
            if($resultToDisplay[$row]['sizeAusyActive']>$resultToDisplay[$row]['sizeReqActive']){
                $complianceSizeActiveTotalAusy += $resultToDisplay[$row]['sizeReqActive'] *$critInfluenceActive;
            }else{
                $complianceSizeActiveTotalAusy += $resultToDisplay[$row]['sizeAusyActive']*$critInfluenceActive;
            }
            $complianceSizeActiveTotalReq +=  $resultToDisplay[$row]['sizeReqActive']*$critInfluenceActive;
        }
        if(isset($resultToDisplay[$row]['sizeReqSupport']) && isset($resultToDisplay[$row]['sizeAusySupport'] )){
            if($resultToDisplay[$row]['sizeAusySupport']>$resultToDisplay[$row]['sizeReqSupport']){
                $complianceSizeSupportTotalAusy += $resultToDisplay[$row]['sizeReqSupport']*$critInfluenceSupport;
            }else{
                $complianceSizeSupportTotalAusy += $resultToDisplay[$row]['sizeAusySupport']*$critInfluenceSupport;
            }
            $complianceSizeSupportTotalReq += $resultToDisplay[$row]['sizeReqSupport']*$critInfluenceSupport;
        }
        if(isset($resultToDisplay[$row]['sizeReqSleeping']) && isset($resultToDisplay[$row]['sizeAusySleeping']  )){
            if($resultToDisplay[$row]['sizeAusySleeping']>$resultToDisplay[$row]['sizeReqSleeping']){
                $complianceSizeSleepingTotalAusy += $resultToDisplay[$row]['sizeReqSleeping']*$critInfluenceSleeping;
            }else{
                $complianceSizeSleepingTotalAusy += $resultToDisplay[$row]['sizeAusySleeping']*$critInfluenceSleeping;
            }
            $complianceSizeSleepingTotalReq += $resultToDisplay[$row]['sizeReqSleeping']*$critInfluenceSleeping;
        }

        //level
        if(isset($resultToDisplay[$row]['levelReqActive']) && isset($resultToDisplay[$row]['ausyAvgLevelActive'] )){
            if($resultToDisplay[$row]['ausyAvgLevelActive']>$resultToDisplay[$row]['levelReqActive']){
                $complianceLevelActiveTotalAusy += $resultToDisplay[$row]['levelReqActive']*$critInfluenceActive;
            }else{
                $complianceLevelActiveTotalAusy += $resultToDisplay[$row]['ausyAvgLevelActive']*$critInfluenceActive;
            }
            $complianceLevelActiveTotalReq += $resultToDisplay[$row]['levelReqActive']*$critInfluenceActive;

        }
        if(isset($resultToDisplay[$row]['levelReqSupport']) && isset($resultToDisplay[$row]['ausyAvgLevelSupport'] )){
            if( $resultToDisplay[$row]['ausyAvgLevelSupport'] > $resultToDisplay[$row]['levelReqSupport']){
                $complianceLevelSupportTotalAusy += $resultToDisplay[$row]['levelReqSupport']*$critInfluenceSupport;
            }else{
                $complianceLevelSupportTotalAusy += $resultToDisplay[$row]['ausyAvgLevelSupport']*$critInfluenceSupport;
            }
            $complianceLevelSupportTotalReq += $resultToDisplay[$row]['levelReqSupport']*$critInfluenceSupport;
        }
        if(isset($resultToDisplay[$row]['levelReqSleeping']) && isset($resultToDisplay[$row]['ausyAvgLevelSleeping'] )){
            if($resultToDisplay[$row]['ausyAvgLevelSleeping'] > $resultToDisplay[$row]['levelReqSleeping']){
                $complianceLevelSleepingTotalAusy += $resultToDisplay[$row]['levelReqSleeping']*$critInfluenceSleeping;
            }else{
                $complianceLevelSleepingTotalAusy += $resultToDisplay[$row]['ausyAvgLevelSleeping']*$critInfluenceSleeping;
            }
            $complianceLevelSleepingTotalReq += $resultToDisplay[$row]['levelReqSleeping']*$critInfluenceSleeping;
        }
    }
    //calcul des compliance finales
    if($complianceSizeActiveTotalReq >0){
        $complianceSizeActiveRatio=round(($complianceSizeActiveTotalAusy/$complianceSizeActiveTotalReq)*100,1);
    }
    if($complianceSizeSupportTotalReq >0){
        $complianceSizeSupportRatio =round(($complianceSizeSupportTotalAusy/$complianceSizeSupportTotalReq)*100,1);
    }
    if($complianceSizeSleepingTotalReq >0){
        $complianceSizeSleepingRatio =round(($complianceSizeSleepingTotalAusy/$complianceSizeSleepingTotalReq)*100,1);
    }
    if($complianceLevelActiveTotalReq >0){
        $complianceLevelActiveRatio  = round(($complianceLevelActiveTotalAusy/$complianceLevelActiveTotalReq)*100,1);
    }
    if($complianceLevelSupportTotalReq >0){
        $complianceLevelSupportRatio =round(($complianceLevelSupportTotalAusy/$complianceLevelSupportTotalReq)*100,1);
    }
    if($complianceLevelSleepingTotalReq >0){
        $complianceLevelSleepingRatio =round(($complianceLevelSleepingTotalAusy/$complianceLevelSleepingTotalReq)*100,1);
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
  <script src="js/Chart.js"></script>
  <script src="js/chartOptions.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="css/marketing.css"/>
  <link rel="stylesheet" href="css/side-menu.css"/>
  <link rel="stylesheet" href="css/pure.css"/>
  <link rel="stylesheet" href="css/skillMatrix.css" />
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<script>
</script>
<body class="preload">
<?php include 'header.php'; ?>
    <div id="main" class="container">


        <?php
        if(!isset($_SESSION['ID_project'])){
        ?>
            <div class="header-compliance" >
                    <h1>What project do you want to watch ?</h1>
            </div>
            <div class="center">
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
        <div>
        <div id="project_skills" class="row">
            <div class="col-md-8">
                <h1>Project Team Skills : <?php echo $nameProject.'<br>'; ?></h1>
                <form  class="pure-form pure-form-aligned " action="./requests.php?op=initProjectId" method="post">
                    <fieldset>
                        <label for="project">Watch another project : </label>
                        <select  id="project" name="project">
                            <?php for($a=0; $a<sizeof($projects);$a++){
                                echo '<option>'.$projects[$a]['ID_project']." - ".$projects[$a]['name_bundle']." : ".$projects[$a]['name_project'];
                            }
                            ?>
                        </select>
                        <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                    </fieldset>
                </form>
                <form  class="pure-form pure-form-aligned " action="" method="get">
                    <fieldset>
                        <label for="chart_type">Choose chart type : </label>
                        <select  id="chart_type" name="chart_type">
                            <option value="radar">Radar</option>
                            <option value="bar">Bar</option>
                        </select>
                        <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                    </fieldset>
                </form>
            </div>
              <div class="col-md-4">
                <div class="exportButtonsDiv">
                <button  class="btn btn-primary btn-block" onClick="exportImgProjectActive()">Export Active Charts to PNG</button>
                <button  class="btn btn-primary btn-block" onClick="exportImgProjectSupport()">Export Support Charts to PNG</button>
                <button  class="btn btn-primary btn-block" onClick="exportImgProjectSleeping()">Export Sleeping Charts to PNG</button>
                <div>
              </div>
          </div>
        </div>
            <div id="scenariosRadar" class="chartContainer">
            <?php
                if(!(strpos($scenarios,'Active')=== false)){
            ?>
            <br>
            <h2 class="center"> Scenario : Active</h2>
            <br>

                <div id="scenarioActive" class="row">

                    <div id="scenarioActiveSkills" class="col-md-6">
                    <h3 class="chartTitle">Size Compliance : <?php echo $complianceSizeActiveRatio;?>%</h3>
                    <canvas id="scenarioActiveSkillsRadar" style="width: 500px; height: 500px;"></canvas>
                    <script>
                        var ctxAS = document.getElementById('scenarioActiveSkillsRadar');
                        var ctxAS = document.getElementById('scenarioActiveSkillsRadar').getContext('2d');
                        var ctxAS = $('#scenarioActiveSkillsRadar');
                        var ctxAS = 'scenarioActiveSkillsRadar';

                        const chartOptionsParameters = getChartOptionsParameters();
                        const chartOptionsSize = getChartOptions(chartOptionsParameters['chart_type'], "projectSize",<?php echo $maxSize;?>);
                        const chartOptionsLevel = getChartOptions(chartOptionsParameters['chart_type'], "projectLevel",0);
                        const chartType = getChartType(chartOptionsParameters['chart_type']);
                        const datasetType = getDatasetType(chartOptionsParameters['chart_type']);

                        var myRadarChart = new Chart(ctxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($resultToDisplay); $row++)
                                                {
                                                    echo reduceName($resultToDisplay[$row]['name_skill']);
                                                    if($row+1 < count($resultToDisplay))
                                                    {
                                                        echo ',';
                                                    }

                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "type": datasetType[0],
                                        "label":"Size Requirement",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['sizeReqActive'])){
                                                            echo $resultToDisplay[$row]['sizeReqActive'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill": datasetType[1],
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Size Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['sizeAusyActive'])){
                                                            echo $resultToDisplay[$row]['sizeAusyActive'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }
                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(54, 162, 235, 0.3)",
                                        "borderColor":"rgb(54, 162, 235)",
                                        "pointBackgroundColor":"rgb(54, 162, 235)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(54, 162, 235)"
                                    }
                                ]
                            }, "options":chartOptionsSize
                        });
                    </script>
                    </div>
                    <div id="scenarioActiveLevel" class="col-md-6">
                        <h3 class="chartTitle">Level Compliance : <?php echo $complianceLevelActiveRatio;?>%</h3>
                    <canvas id="scenarioActiveLevelRadar" style="width: 800px; height: 800px;"></canvas>
                    <script>
                        var ctxAS = document.getElementById('scenarioActiveLevelRadar');
                        var ctxAS = document.getElementById('scenarioActiveLevelRadar').getContext('2d');
                        var ctxAS = $('#scenarioActiveLevelRadar');
                        var ctxAS = 'scenarioActiveLevelRadar';

                        var myRadarChart = new Chart(ctxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($resultToDisplay); $row++)
                                                {
                                                    echo reduceName($resultToDisplay[$row]['name_skill']);
                                                    if($row+1 < count($resultToDisplay))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "type": datasetType[0],
                                        "label":"Level Requirement",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['levelReqActive'])){
                                                            echo $resultToDisplay[$row]['levelReqActive'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill": datasetType[1],
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Level Max Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['ausyMaxLevelActive'])){
                                                            echo $resultToDisplay[$row]['ausyMaxLevelActive'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(54, 162, 235, 0.3)",
                                        "borderColor":"rgb(54, 162, 235)",
                                        "pointBackgroundColor":"rgb(54, 162, 235)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(54, 162, 235)"
                                    },
                                    {
                                        "label":"Level AVG Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['ausyAvgLevelActive'])){
                                                            echo $resultToDisplay[$row]['ausyAvgLevelActive'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(0, 133, 78, 0.3)",
                                        "borderColor":"rgb(0, 133, 78)",
                                        "pointBackgroundColor":"rgb(0, 133, 78)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(0, 133, 78)"
                                    }
                                ]
                            }, "options":chartOptionsSize
                        });
                    </script>

                    </div>
                </div>
            <?php
                }
                if(!(strpos($scenarios,'Support')=== false)){
            ?>
                <br>
                <h2 class="center"> Scenario : Support</h2>
                <br>
                <div id="scenarioSupport" class="row">
                    <div id="scenarioSupportSkills" class="col-md-6">
                    <h3 class="chartTitle">Size Compliance : <?php echo $complianceSizeSupportRatio;?> %</h3>
                    <canvas id="scenarioSupportSkillsRadar" style="width: 500px; height: 500px;"></canvas>
                    <script>
                        var ctxAS = document.getElementById('scenarioSupportSkillsRadar');
                        var ctxAS = document.getElementById('scenarioSupportSkillsRadar').getContext('2d');
                        var ctxAS = $('#scenarioSupportSkillsRadar');
                        var ctxAS = 'scenarioSupportSkillsRadar';

                        var myRadarChart = new Chart(ctxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($resultToDisplay); $row++)
                                                {
                                                    echo reduceName($resultToDisplay[$row]['name_skill']);
                                                    if($row+1 < count($resultToDisplay))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "type": datasetType[0],
                                        "label":"Size Requirement",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['sizeReqSupport'])){
                                                            echo $resultToDisplay[$row]['sizeReqSupport'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }
                                                    }
                                                ?>],
                                        "fill": datasetType[1],
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Size Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['sizeAusySupport'])){
                                                            echo $resultToDisplay[$row]['sizeAusySupport'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }
                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(54, 162, 235, 0.3)",
                                        "borderColor":"rgb(54, 162, 235)",
                                        "pointBackgroundColor":"rgb(54, 162, 235)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(54, 162, 235)"
                                    }
                                ]
                            }, "options":chartOptionsSize
                        });
                    </script>
                    </div>
                    <div id="scenarioSupportLevel" class="col-md-6">
                        <h3 class="chartTitle">Level Compliance : <?php echo $complianceLevelSupportRatio;?>%</h3>
                    <canvas id="scenarioSupportLevelRadar" style="width: 500px; height: 500px;"></canvas>
                    <script>
                        var ctxAS = document.getElementById('scenarioSupportLevelRadar');
                        var ctxAS = document.getElementById('scenarioSupportLevelRadar').getContext('2d');
                        var ctxAS = $('#scenarioSupportLevelRadar');
                        var ctxAS = 'scenarioSupportLevelRadar';

                        var myRadarChart = new Chart(ctxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($resultToDisplay); $row++)
                                                {
                                                    echo reduceName($resultToDisplay[$row]['name_skill']);
                                                    if($row+1 < count($resultToDisplay))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "type": datasetType[0],
                                        "label":"Level Requirement",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['levelReqSupport'])){
                                                            echo $resultToDisplay[$row]['levelReqSupport'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill": datasetType[1],
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Level Max Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['ausyMaxLevelSupport'])){
                                                            echo $resultToDisplay[$row]['ausyMaxLevelSupport'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(54, 162, 235, 0.3)",
                                        "borderColor":"rgb(54, 162, 235)",
                                        "pointBackgroundColor":"rgb(54, 162, 235)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(54, 162, 235)"
                                    },
                                    {
                                        "label":"Level AVG Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['ausyAvgLevelSupport'])){
                                                            echo $resultToDisplay[$row]['ausyAvgLevelSupport'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }
                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(0, 133, 78, 0.3)",
                                        "borderColor":"rgb(0, 133, 78)",
                                        "pointBackgroundColor":"rgb(0, 133, 78)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(0, 133, 78)"
                                    }
                                ]
                            }, "options":chartOptionsSize
                        });
                    </script>

                    </div>
                </div>
            <?php
                }
                if(!(strpos($scenarios,'Sleeping')=== false)){
            ?>
                <br>
                <h2> Scenario : Sleeping</h2>
                <br>
                <div id="scenarioSleeping" class="row">
                    <div id="scenarioSleepingSkills" class="col-md-6">
                    <h3 class="chartTitle">Size Compliance : <?php echo $complianceSizeSleepingRatio;?>%</h3>
                    <canvas id="scenarioSleepingSkillsRadar" style="width: 500px; height: 500px;"></canvas>
                    <script>
                        var ctxAS = document.getElementById('scenarioSleepingSkillsRadar');
                        var ctxAS = document.getElementById('scenarioSleepingSkillsRadar').getContext('2d');
                        var ctxAS = $('#scenarioSleepingSkillsRadar');
                        var ctxAS = 'scenarioSleepingSkillsRadar';

                        var myRadarChart = new Chart(ctxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($resultToDisplay); $row++)
                                                {
                                                    echo reduceName($resultToDisplay[$row]['name_skill']);
                                                    if($row+1 < count($resultToDisplay))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "type": datasetType[0],
                                        "label":"Size Requirement",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['sizeReqSleeping'])){
                                                            echo $resultToDisplay[$row]['sizeReqSleeping'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }
                                                    }
                                                ?>],
                                        "fill": datasetType[1],
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Size Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['sizeAusySleeping'])){
                                                            echo $resultToDisplay[$row]['sizeAusySleeping'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }
                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(54, 162, 235, 0.3)",
                                        "borderColor":"rgb(54, 162, 235)",
                                        "pointBackgroundColor":"rgb(54, 162, 235)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(54, 162, 235)"
                                    }
                                ]
                            }, "options":chartOptionsSize
                        });
                    </script>
                    </div>
                    <div id="scenarioSleepingLevel" class="col-md-6">
                        <h3 class="chartTitle">Level Compliance : <?php echo $complianceLevelSleepingRatio;?>%</h3>
                    <canvas id="scenarioSleepingLevelRadar" style="width: 500px; height: 500px;"></canvas>
                    <script>
                        var ctxAS = document.getElementById('scenarioSleepingLevelRadar');
                        var ctxAS = document.getElementById('scenarioSleepingLevelRadar').getContext('2d');
                        var ctxAS = $('#scenarioSleepingLevelRadar');
                        var ctxAS = 'scenarioSleepingLevelRadar';

                        var myRadarChart = new Chart(ctxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($resultToDisplay); $row++)
                                                {
                                                    echo reduceName($resultToDisplay[$row]['name_skill']);
                                                    if($row+1 < count($resultToDisplay))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "type": datasetType[0],
                                        "label":"Level Requirement",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['levelReqSleeping'])){
                                                            echo $resultToDisplay[$row]['levelReqSleeping'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill": datasetType[1],
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Level Max Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['ausyMaxLevelSleeping'])){
                                                            echo $resultToDisplay[$row]['ausyMaxLevelSleeping'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }

                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(54, 162, 235, 0.3)",
                                        "borderColor":"rgb(54, 162, 235)",
                                        "pointBackgroundColor":"rgb(54, 162, 235)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(54, 162, 235)"
                                    },
                                    {
                                        "label":"Level AVG Ausy",
                                        "data" : [<?php
                                                for ($row=0; $row < count($resultToDisplay); $row++)
                                                    {
                                                        if(isset($resultToDisplay[$row]['ausyAvgLevelSleeping'])){
                                                            echo $resultToDisplay[$row]['ausyAvgLevelSleeping'];
                                                        }else{
                                                            echo '0';
                                                        }
                                                        if($row+1 < count($resultToDisplay))
                                                        {
                                                            echo ',';
                                                        }
                                                    }
                                                ?>],
                                        "fill":true,"backgroundColor":"rgba(0, 133, 78, 0.3)",
                                        "borderColor":"rgb(0, 133, 78)",
                                        "pointBackgroundColor":"rgb(0, 133, 78)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(0, 133, 78)"
                                    }
                                ]
                            }, "options":chartOptionsSize
                        });
                    </script>

                    </div>
                </div>
            <?php
                }
            ?>
            <div>
        </div>
        <?php
        }
        ?>
    </div>
</body>
</html>
