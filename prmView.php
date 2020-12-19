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

$complianceSizeRatio   = 0;
$complianceLevelRatio   = 0;

$resultToDisplay = [];


$req = $bdd->prepare('SELECT * FROM prm');
$req->execute();
$prm = $req->fetchAll();

if(isset($_SESSION['ID_prm'])){


    $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project  LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category LEFT JOIN criticality ON project_skill_requirement.skill_criticality = criticality.ID_criticality WHERE project.prm_project= '.$_SESSION['ID_prm'].' AND project.status_project = project_skill_requirement.scenario ORDER BY skill.name_skill ASC');
    $req->execute();
    $projectReq= $req->fetchAll();

    $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN project on project.ID_project = project_user.project LEFT JOIN user_skill ON user_skill.id_user= project_user.user LEFT JOIN skill ON skill.ID_skill= user_skill.id_skill LEFT JOIN skill_category ON skill_category.ID_skill_category = skill.category_skill WHERE project.prm_project= '.$_SESSION['ID_prm'].' ORDER BY skill.name_skill ASC');
    $req->execute();
    $project_user = $req->fetchAll();

    //On calcule pour chaque requieremnts Les capacités actuelles et les capacités souhaitees.
    $nameProject= $projectReq[0]['prm_project'];
    $requirementsExpectationsAndReality = [];
    $maxLevel= $finalAvgLevel=0;
    $AllLevel=[];
    $index=0;
    $maxSize = 4;
    $previousSkill='empty';
    $requirementsExpectationsAndReality[0]['skillReqName']='empty';
    $sameSkillCount = 1;
    for ($i=0; $i < count($projectReq); $i++) { // pour tous les skill requieremnt

        $requirementsExpectationsAndReality[$index]['skillReqName']=$projectReq[$i]['name_skill'];

        if( $previousSkill != $requirementsExpectationsAndReality[$index]['skillReqName']){ // nouveau skill req
            $requirementsExpectationsAndReality[$index] = array('scenario'=>$projectReq[$i]['scenario'],
                                                                'skillReqName'=>$projectReq[$i]['name_skill'],
                                                                'skillReqCriticality'=>$projectReq[$i]['influence'],
                                                                'skillReqSize'=>$projectReq[$i]['size'],
                                                                'skillRealitySize'=>0,
                                                                'skillReqLevel'=>$projectReq[$i]['level_requirement'],
                                                                'skillRealityMaxLevel'=>0,
                                                                'skillRealityAvgLevel'=>0);
        }else{
            if($requirementsExpectationsAndReality[$index]['skillReqCriticality']<$projectReq[$i]['influence']){
                $requirementsExpectationsAndReality[$index]['skillReqCriticality'] = $projectReq[$i]['influence'];
            }
            $requirementsExpectationsAndReality[$index]['skillReqSize'] += $projectReq[$i]['size'];

            $requirementsExpectationsAndReality[$index]['skillReqLevel'] += $projectReq[$i]['level_requirement'];
            $sameSkillCount ++;
        }

        if($maxSize<$requirementsExpectationsAndReality[$index]['skillReqSize']){
            $maxSize = $requirementsExpectationsAndReality[$index]['skillReqSize'];
        }
        for ($j=0; $j < count($project_user); $j++) {
            if($projectReq[$i]['id_skill'] == $project_user[$j]['id_skill']
                && strpos($project_user[$j]['scenario'], $projectReq[$i]['status_project']) >-1
                && strpos($projectReq[$i]['scenario_project'], $projectReq[$i]['status_project']) >-1
                && $projectReq[$i]['id_project'] == $project_user[$j]['project']
            ){
                $requirementsExpectationsAndReality[$index]['skillRealitySize']++;
                $AllLevel[] =  $project_user[$j]['level_user_skill'];
                if($project_user[$j]['level_user_skill']>$maxLevel){
                    $maxLevel =$project_user[$j]['level_user_skill'];
                }
            }
        }
        if($requirementsExpectationsAndReality[$index]['skillRealityMaxLevel']<$maxLevel){
            $requirementsExpectationsAndReality[$index]['skillRealityMaxLevel']  = $maxLevel;
        }

        if($projectReq[$i]['size'] != 0){
            rsort($AllLevel); // on va seulement prendre le haut du panier

            for ($k=0; $k < $requirementsExpectationsAndReality[$index]['skillReqSize']; $k++) {
                if(isset($AllLevel[$k])){
                    $finalAvgLevel = $finalAvgLevel + $AllLevel[$k];
                }
            }
            $requirementsExpectationsAndReality[$index]['skillRealityAvgLevel'] += round((float) $finalAvgLevel/ $requirementsExpectationsAndReality[$index]['skillReqSize'],2);
        }
            $previousSkill = $requirementsExpectationsAndReality[$index]['skillReqName'];

            if($i < count($projectReq)-1){
                if( $projectReq[$i+1]['name_skill'] !=  $requirementsExpectationsAndReality[$index]['skillReqName'] ){
                    $requirementsExpectationsAndReality[$index]['skillReqLevel'] = $requirementsExpectationsAndReality[$index]['skillReqLevel']/$sameSkillCount;
                    $requirementsExpectationsAndReality[$index]['skillRealityAvgLevel'] = $requirementsExpectationsAndReality[$index]['skillRealityAvgLevel']/$sameSkillCount;
                    $sameSkillCount = 1;
                    $maxLevel= $finalAvgLevel=0;
                    $AllLevel=[];
                    $index++;
                }
            }else{
                $requirementsExpectationsAndReality[$index]['skillReqLevel'] = $requirementsExpectationsAndReality[$index]['skillReqLevel']/$sameSkillCount;
                $requirementsExpectationsAndReality[$index]['skillRealityAvgLevel'] = $requirementsExpectationsAndReality[$index]['skillRealityAvgLevel']/$sameSkillCount;
            }


    }
    $resultToDisplay = $requirementsExpectationsAndReality;

    $complianceSizeTotalReq = $complianceSizeTotalAusy = 0;
    $complianceLevelTotalReq = $complianceLevelTotalAusy = 0;
    $critInfluence  = 1;


    for ($row=0; $row < count($resultToDisplay); $row++)
    {
        //criticality
        if(isset($resultToDisplay[$row]['skillReqCriticality'])){
            $critInfluence = $resultToDisplay[$row]['skillReqCriticality']*0.01;
        }

        //size
        if(isset($resultToDisplay[$row]['skillReqSize']) && isset($resultToDisplay[$row]['skillRealitySize'])){
            if($resultToDisplay[$row]['skillRealitySize']>$resultToDisplay[$row]['skillReqSize']){
                $complianceSizeTotalAusy += $resultToDisplay[$row]['skillReqSize'] *$critInfluence;
            }else{
                $complianceSizeTotalAusy += $resultToDisplay[$row]['skillRealitySize']*$critInfluence;
            }
            $complianceSizeTotalReq +=  $resultToDisplay[$row]['skillReqSize']*$critInfluence;
        }

        //level
        if(isset($resultToDisplay[$row]['skillReqLevel']) && isset($resultToDisplay[$row]['skillRealityAvgLevel'] )){
            if($resultToDisplay[$row]['skillRealityAvgLevel']>$resultToDisplay[$row]['skillReqLevel']){
                $complianceLevelTotalAusy += $resultToDisplay[$row]['skillReqLevel']*$critInfluence;
            }else{
                $complianceLevelTotalAusy += $resultToDisplay[$row]['skillRealityAvgLevel']*$critInfluence;
            }
            $complianceLevelTotalReq += $resultToDisplay[$row]['skillReqLevel']*$critInfluence;

        }
    }
    //calcul des compliance finales
    if($complianceSizeTotalReq >0){
        $complianceSizeRatio=round(($complianceSizeTotalAusy/$complianceSizeTotalReq)*100,1);
    }
    if($complianceLevelTotalReq >0){
        $complianceLevelRatio=round(($complianceLevelTotalAusy/$complianceLevelTotalReq)*100,1);
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
        if(!isset($_SESSION['ID_prm'])){
        ?>
            <div class="header-compliance" >
                    <h1>What PRM do you want to watch ?</h1>
            </div>
            <div class="center">
                <form  class="pure-form pure-form-aligned" action="./requests.php?op=initPrmId" method="post">
                    <fieldset>
                        <label for="prm">List of PRM : </label>
                        <select  id="prm" name="prm">
                            <?php for($a=0; $a<sizeof($prm);$a++){
                                echo '<option>'.$prm[$a]['ID_prm']." - ".$prm[$a]['name_prm'];
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
                <h1>PRM Team Skills : <?php echo $nameProject.'<br>'; ?></h1>
                <form  class="pure-form pure-form-aligned " action="./requests.php?op=initProjectId" method="post">
                    <fieldset>
                        <label for="project">Watch another project : </label>
                        <select  id="project" name="project">
                            <?php for($a=0; $a<sizeof($prm);$a++){
                                echo '<option>'.$prm[$a]['ID_prm']." - ".$prm[$a]['name_prm'];
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
                <button  class="btn btn-primary btn-block" onClick="exportImgProjectActive()">Export Charts to PNG</button>
                <div>
              </div>
          </div>
        </div>
        <div id="scenariosRadar" class="chartContainer">
        <br>
        <h2 class="center"> Actual PRM Compliance</h2>
        <br>

            <div id="scenarioActive" class="row">

                <div id="scenarioActiveSkills" class="col-md-6">
                    <h3 class="chartTitle">Size Compliance : <?php echo $complianceSizeRatio;?>%</h3>
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
                                                    echo reduceName($resultToDisplay[$row]['skillReqName']);
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
                                                        if(isset($resultToDisplay[$row]['skillReqSize'])){
                                                            echo $resultToDisplay[$row]['skillReqSize'];
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
                                                        if(isset($resultToDisplay[$row]['skillRealitySize'])){
                                                            echo $resultToDisplay[$row]['skillRealitySize'];
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
                    <h3 class="chartTitle">Level Compliance : <?php echo $complianceLevelRatio;?>%</h3>
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
                                                    echo reduceName($resultToDisplay[$row]['skillReqName']);
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
                                                        if(isset($resultToDisplay[$row]['skillReqLevel'])){
                                                            echo $resultToDisplay[$row]['skillReqLevel'];
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
                                                        if(isset($resultToDisplay[$row]['skillRealityMaxLevel'])){
                                                            echo $resultToDisplay[$row]['skillRealityMaxLevel'];
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
                                                        if(isset($resultToDisplay[$row]['skillRealityAvgLevel'])){
                                                            echo $resultToDisplay[$row]['skillRealityAvgLevel'];
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
                            }, "options":chartOptionsLevel
                        });
                    </script>

                </div>

            </div>
        </div>

        <?php
        }
        ?>
    </div>
</body>
</html>
