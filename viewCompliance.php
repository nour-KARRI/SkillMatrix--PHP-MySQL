<?php
session_start();
include 'sqlconnect.php';

if (!isset($_SESSION['rights'])) {
  header('location: home.php');
}

if ($_SESSION['rights'] != 11 && $_SESSION['rights'] != 12 && $_SESSION['rights'] != 13 && $_SESSION['rights'] != 14) {
  header('location: home.php');
}

ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);

$req = $bdd->prepare('SELECT * FROM bundle');
$req->execute();
$bundles = $req->fetchAll();
//initiations des variables pour les compliance des projets par scenario
$complianceSizeActiveRatio  = $complianceSizeSupportRatio = $complianceSizeSleepingRatio = 0;
$complianceLevelActiveRatio  = $complianceLevelSupportRatio =  $complianceLevelSleepingRatio = 0;
//init des variables pour les moyennes de compliances
$finalComplianceActive = $finalComplianceSupport = $finalComplianceSleeping =0;
//tableau final
$resultToDisplay = [];
//init liste des projets
$projects;

if(isset($_SESSION['ID_bundle'])){ // si un ID de bundle n 'est pas prescisé, la page demande d'en séléctionné un

    //on récupere tous les projets liés au bundle
    $req = $bdd->prepare('SELECT * FROM project LEFT JOIN user ON user.ID_user = project.manager_project LEFT JOIN bundle ON bundle.ID_bundle = project.bundle_project WHERE ID_bundle='.$_SESSION['ID_bundle']);
    $req->execute();
    $projects = $req->fetchAll();
    //on init le nom du bundle
    for ($i=0; $i < count($bundles); $i++) {
        if($bundles[$i]['ID_bundle']==$_SESSION['ID_bundle']){
            $nameBundle= $bundles[$i]['name_bundle'];
        }
    }
    //on recupere tous les projects requierements des projets associé au bundle
    $req = $bdd->prepare('SELECT * FROM project_skill_requirement LEFT JOIN project ON project.ID_project = project_skill_requirement.id_project  LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle LEFT JOIN skill on skill.ID_skill = project_skill_requirement.id_skill LEFT JOIN skill_category ON skill.category_skill = skill_category.ID_skill_category LEFT JOIN criticality ON project_skill_requirement.skill_criticality = criticality.ID_criticality WHERE bundle.ID_bundle='.$_SESSION['ID_bundle'].' ORDER BY project.ID_project ASC ,skill.name_skill ASC');
    $req->execute();
    $projectReq= $req->fetchAll();

    // on recupere tous les users associés a tous les projets du bundle
    $req = $bdd->prepare('SELECT * FROM project_user LEFT JOIN user ON project_user.user = user.ID_user LEFT JOIN project on project.ID_project = project_user.project LEFT JOIN bundle ON project.bundle_project = bundle.ID_bundle LEFT JOIN user_skill ON user_skill.id_user= project_user.user LEFT JOIN skill ON skill.ID_skill= user_skill.id_skill LEFT JOIN skill_category ON skill_category.ID_skill_category = skill.category_skill WHERE bundle.ID_bundle='.$_SESSION['ID_bundle'].' ORDER BY project.ID_project ASC');
    $req->execute();
    $project_user = $req->fetchAll();

    $requirementsExpectationsAndReality = [];
    $maxLevel= $finalAvgLevel = 0; // init des variables du calcul de max Level et average Level
    $AllLevel=[]; // Pour selectionner les Top level seulement slors du calcul du average level
    for ($i=0; $i < count($projectReq); $i++) { // pour tous les skill requieremnt
        $requirementsExpectationsAndReality[$i] = array('bundleName'=>$projectReq[$i]['name_bundle'],
                                                        'projectName'=>$projectReq[$i]['name_project'],
                                                        'skillReqCriticality'=>$projectReq[$i]['influence'],
                                                        'scenario'=>$projectReq[$i]['scenario'],
                                                        'skillReqName'=>$projectReq[$i]['name_skill'],
                                                        'skillReqSize'=>$projectReq[$i]['size'],
                                                        'skillRealitySize'=>0,
                                                        'skillReqLevel'=>$projectReq[$i]['level_requirement'],
                                                        'skillRealityMaxLevel'=>0,
                                                        'skillRealityAvgLevel'=>0);
        for ($j=0; $j < count($project_user); $j++) {// On Recherche les utilisateurs couvrant ce skill requierement associé au projet
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
            $requirementsExpectationsAndReality[$i]['skillRealityAvgLevel'] = round((float) $finalAvgLevel/$projectReq[$i]['size'],2);
        }

        //re init des variables
        $maxLevel= $finalAvgLevel=0;
        $AllLevel=[];
    }

    $reqAndRealBySce = $requirementsExpectationsAndReality; // on renomme pour plus de visibilité
    //init de vraibales nécéssaires au calcul
    $index=0;
    $previousSkill='empty';
    $previousProject='empty';
    for($row=0; $row<count($reqAndRealBySce);$row++){//on va diviser pour chaque scenario

        if($previousProject != $reqAndRealBySce[$row]['projectName']){ //Si noubeau projet
            $resultToDisplay[$index]=array('bundleName'=>$reqAndRealBySce[$row]['bundleName'],
                                        'projectName'=>$reqAndRealBySce[$row]['projectName']);
        }
        if($previousSkill != $reqAndRealBySce[$row]['skillReqName'] ){// Si nouveau skill
            $previousSkill = $reqAndRealBySce[$row]['skillReqName'];
            $previousProject = $reqAndRealBySce[$row]['projectName'];
            if(!isset($resultToDisplay[$index])){ // cas improbable
                $resultToDisplay[$index]=array('bundleName'=>$reqAndRealBySce[$row]['bundleName'],
                'projectName'=>$reqAndRealBySce[$row]['projectName']);
            }

            $resultToDisplay[$index]=array_merge($resultToDisplay[$index],array( // on merge le nouveau skill dans le nouveau tableau
                                        'name_skill'=>$reqAndRealBySce[$row]['skillReqName'],
                                        'sizeReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqSize'],
                                        'skillReqCriticality'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqCriticality'],
                                        'sizeAusy'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealitySize'],
                                        'levelReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqLevel'],
                                        'ausyMaxLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityMaxLevel'],
                                        'ausyAvgLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityAvgLevel']));
            if($row+1<count($reqAndRealBySce)){
                if($reqAndRealBySce[$row+1]['skillReqName'] != $previousSkill ||  ( $reqAndRealBySce[$row+1]['skillReqName'] == $previousSkill && $previousProject != $reqAndRealBySce[$row+1]['projectName'] ) ){
                    $index++;
                }
            }
        }else{ //si même skill , mais scenario different, on merge dans le tableau
            $resultToDisplay[$index]=array_merge($resultToDisplay[$index],array('sizeReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqSize'],
																				'skillReqCriticality'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqCriticality'],
                                                                                'sizeAusy'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealitySize'],
                                                                                'levelReq'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillReqLevel'],
                                                                                'ausyMaxLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityMaxLevel'],
                                                                                'ausyAvgLevel'.$reqAndRealBySce[$row]['scenario']=>$reqAndRealBySce[$row]['skillRealityAvgLevel']));
            if($row+1<count($reqAndRealBySce) ){
                if($reqAndRealBySce[$row+1]['skillReqName'] != $previousSkill || ( $reqAndRealBySce[$row+1]['skillReqName'] == $previousSkill && $previousProject != $reqAndRealBySce[$row+1]['projectName'] )){
                    $index++; // nouvelle ligne dans le tableau
                }
            }
        }

    }

    //calcul Compliance - beaucoup de ligne parce que 6 cas a gérer, rien de difficile
$critInfluenceActive = $critInfluenceSupport = $critInfluenceSleeping = 1;
$indexcompliance=0;
$compliance =[];
    for ($row=0; $row < count($resultToDisplay); $row++) //contrirement a project summary , on doit calculer la compliance et les stocker dans un tableau pour calculer la compliance finale
    {

        if($row==0){//init
            $compliance[$indexcompliance]=array('projectName'=>$resultToDisplay[$row]['projectName'],'complianceSizeActiveTotalReq' =>0, 'complianceSizeActiveTotalAusy' =>0, 'complianceSizeSupportTotalReq'=>0,'complianceSizeSupportTotalAusy'=>0, 'complianceSizeSleepingTotalReq' =>0, 'complianceSizeSleepingTotalAusy' =>0, 'complianceLevelActiveTotalReq' =>0, 'complianceLevelActiveTotalAusy' =>0, 'complianceLevelSupportTotalReq' =>0, 'complianceLevelSupportTotalAusy' =>0,  'complianceLevelSleepingTotalReq' =>0, 'complianceLevelSleepingTotalAusy' => 0 ,'complianceSizeActiveRatio' =>0, 'complianceSizeSupportRatio' =>0, 'complianceSizeSleepingRatio' => 0,'complianceLevelActiveRatio'  =>0, 'complianceLevelSupportRatio' =>0,  'complianceLevelSleepingRatio' => 0);
        }
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
                $compliance[$indexcompliance]['complianceSizeActiveTotalAusy'] += $resultToDisplay[$row]['sizeReqActive'] * $critInfluenceActive;
            }else{
                $compliance[$indexcompliance]['complianceSizeActiveTotalAusy'] += $resultToDisplay[$row]['sizeAusyActive'] * $critInfluenceActive;
            }
            $compliance[$indexcompliance]['complianceSizeActiveTotalReq'] +=  $resultToDisplay[$row]['sizeReqActive'] * $critInfluenceActive;
        }
        if(isset($resultToDisplay[$row]['sizeReqSupport']) && isset($resultToDisplay[$row]['sizeAusySupport'] )){
            if($resultToDisplay[$row]['sizeAusySupport']>$resultToDisplay[$row]['sizeReqSupport']){
                $compliance[$indexcompliance]['complianceSizeSupportTotalAusy'] += $resultToDisplay[$row]['sizeReqSupport'] * $critInfluenceSupport;
            }else{
                $compliance[$indexcompliance]['complianceSizeSupportTotalAusy'] += $resultToDisplay[$row]['sizeAusySupport'] * $critInfluenceSupport;
            }
            $compliance[$indexcompliance]['complianceSizeSupportTotalReq'] += $resultToDisplay[$row]['sizeReqSupport'] * $critInfluenceSupport;
        }
        if(isset($resultToDisplay[$row]['sizeReqSleeping']) && isset($resultToDisplay[$row]['sizeAusySleeping']  )){
            if($resultToDisplay[$row]['sizeAusySleeping']>$resultToDisplay[$row]['sizeReqSleeping']){
                $compliance[$indexcompliance]['complianceSizeSleepingTotalAusy'] += $resultToDisplay[$row]['sizeReqSleeping'] * $critInfluenceSleeping;
            }else{
                $compliance[$indexcompliance]['complianceSizeSleepingTotalAusy'] += $resultToDisplay[$row]['sizeAusySleeping'] * $critInfluenceSleeping;
            }
            $compliance[$indexcompliance]['complianceSizeSleepingTotalReq'] += $resultToDisplay[$row]['sizeReqSleeping'] * $critInfluenceSleeping;
        }

        //level
        if(isset($resultToDisplay[$row]['levelReqActive']) && isset($resultToDisplay[$row]['ausyAvgLevelActive'] )){
            if($resultToDisplay[$row]['ausyAvgLevelActive']>$resultToDisplay[$row]['levelReqActive']){
                $compliance[$indexcompliance]['complianceLevelActiveTotalAusy'] += $resultToDisplay[$row]['levelReqActive'] * $critInfluenceActive;
            }else{
                $compliance[$indexcompliance]['complianceLevelActiveTotalAusy'] += $resultToDisplay[$row]['ausyAvgLevelActive'] * $critInfluenceActive;
            }
            $compliance[$indexcompliance]['complianceLevelActiveTotalReq'] += $resultToDisplay[$row]['levelReqActive'] * $critInfluenceActive;

        }
        if(isset($resultToDisplay[$row]['levelReqSupport']) && isset($resultToDisplay[$row]['ausyAvgLevelSupport'] )){
            if( $resultToDisplay[$row]['ausyAvgLevelSupport'] > $resultToDisplay[$row]['levelReqSupport']){
                $compliance[$indexcompliance]['complianceLevelSupportTotalAusy'] += $resultToDisplay[$row]['levelReqSupport'] * $critInfluenceSupport;
            }else{
                $compliance[$indexcompliance]['complianceLevelSupportTotalAusy'] += $resultToDisplay[$row]['ausyAvgLevelSupport'] * $critInfluenceSupport;
            }
            $compliance[$indexcompliance]['complianceLevelSupportTotalReq'] += $resultToDisplay[$row]['levelReqSupport'] * $critInfluenceSupport;
        }
        if(isset($resultToDisplay[$row]['levelReqSleeping']) && isset($resultToDisplay[$row]['ausyAvgLevelSleeping'] )){
            if($resultToDisplay[$row]['ausyAvgLevelSleeping'] > $resultToDisplay[$row]['levelReqSleeping']){
                $compliance[$indexcompliance]['complianceLevelSleepingTotalAusy'] += $resultToDisplay[$row]['levelReqSleeping'] * $critInfluenceSleeping;
            }else{
                $compliance[$indexcompliance]['complianceLevelSleepingTotalAusy'] += $resultToDisplay[$row]['ausyAvgLevelSleeping'] * $critInfluenceSleeping;
            }
            $compliance[$indexcompliance]['complianceLevelSleepingTotalReq'] += $resultToDisplay[$row]['levelReqSleeping'] * $critInfluenceSleeping;
        }

        //calcul de la compliance finale par projet par scenario
        if($row == count($resultToDisplay)-1 || $resultToDisplay[$row+1]['projectName'] != $resultToDisplay[$row]['projectName'] ){

            if( $compliance[$indexcompliance]['complianceSizeActiveTotalReq'] >0){
                $compliance[$indexcompliance]['complianceSizeActiveRatio']=round(( $compliance[$indexcompliance]['complianceSizeActiveTotalAusy']/ $compliance[$indexcompliance]['complianceSizeActiveTotalReq'])*100,0);
            }
            if( $compliance[$indexcompliance]['complianceSizeSupportTotalReq'] >0){
                $compliance[$indexcompliance]['complianceSizeSupportRatio'] =round(( $compliance[$indexcompliance]['complianceSizeSupportTotalAusy']/ $compliance[$indexcompliance]['complianceSizeSupportTotalReq'])*100,0);
            }
            if( $compliance[$indexcompliance]['complianceSizeSleepingTotalReq'] >0){
                $compliance[$indexcompliance]['complianceSizeSleepingRatio'] =round(( $compliance[$indexcompliance]['complianceSizeSleepingTotalAusy']/ $compliance[$indexcompliance]['complianceSizeSleepingTotalReq'])*100,0);
            }
            if( $compliance[$indexcompliance]['complianceLevelActiveTotalReq'] >0){
                $compliance[$indexcompliance]['complianceLevelActiveRatio']  = round(( $compliance[$indexcompliance]['complianceLevelActiveTotalAusy']/ $compliance[$indexcompliance]['complianceLevelActiveTotalReq'])*100,0);
            }
            if( $compliance[$indexcompliance]['complianceLevelSupportTotalReq'] >0){
                $compliance[$indexcompliance]['complianceLevelSupportRatio'] =round(( $compliance[$indexcompliance]['complianceLevelSupportTotalAusy']/ $compliance[$indexcompliance]['complianceLevelSupportTotalReq'])*100,0);
            }
            if( $compliance[$indexcompliance]['complianceLevelSleepingTotalReq'] >0){
                $compliance[$indexcompliance]['complianceLevelSleepingRatio'] =round(( $compliance[$indexcompliance]['complianceLevelSleepingTotalAusy']/ $compliance[$indexcompliance]['complianceLevelSleepingTotalReq'])*100,0);
            }
            if(isset($resultToDisplay[$row+1]['projectName'])){
                $indexcompliance++;
                $compliance[$indexcompliance]=array('projectName'=>$resultToDisplay[$row+1]['projectName'],'complianceSizeActiveTotalReq' =>0, 'complianceSizeActiveTotalAusy' =>0, 'complianceSizeSupportTotalReq'=>0,'complianceSizeSupportTotalAusy'=>0, 'complianceSizeSleepingTotalReq' =>0, 'complianceSizeSleepingTotalAusy' =>0, 'complianceLevelActiveTotalReq' =>0, 'complianceLevelActiveTotalAusy' =>0, 'complianceLevelSupportTotalReq' =>0, 'complianceLevelSupportTotalAusy' =>0,  'complianceLevelSleepingTotalReq' =>0, 'complianceLevelSleepingTotalAusy' => 0 ,'complianceSizeActiveRatio' =>0, 'complianceSizeSupportRatio' =>0, 'complianceSizeSleepingRatio' => 0,'complianceLevelActiveRatio'  =>0, 'complianceLevelSupportRatio' =>0,  'complianceLevelSleepingRatio' => 0);

            }
        }
        $critInfluenceActive = $critInfluenceSupport = $critInfluenceSleeping = 1;
    }
    //calcul final compliance du bundle (propre a view compliance)
    for ($row=0; $row < count($compliance); $row++){
        if( isset($compliance[$row]['complianceSizeActiveRatio'])){
            $finalComplianceActive= round($finalComplianceActive+$compliance[$row]['complianceSizeActiveRatio']/(count($compliance)*2),1);
        }
        if( isset($compliance[$row]['complianceSizeSupportRatio'])){
            $finalComplianceSupport=  round($finalComplianceSupport+$compliance[$row]['complianceSizeSupportRatio']/(count($compliance)*2),1);
        }
        if( isset($compliance[$row]['complianceSizeSleepingRatio'])){
            $finalComplianceSleeping=  round($finalComplianceSleeping+$compliance[$row]['complianceSizeSleepingRatio']/(count($compliance)*2),1);
        }
        if( isset($compliance[$row]['complianceLevelActiveRatio'])){
            $finalComplianceActive=  round($finalComplianceActive+$compliance[$row]['complianceLevelActiveRatio']/(count($compliance)*2),1);
        }
        if( isset($compliance[$row]['complianceLevelSupportRatio'])){
            $finalComplianceSupport=  round($finalComplianceSupport+$compliance[$row]['complianceLevelSupportRatio']/(count($compliance)*2),1);
        }
        if( isset($compliance[$row]['complianceLevelSleepingRatio'])){
            $finalComplianceSleeping=  round($finalComplianceSleeping+$compliance[$row]['complianceLevelSleepingRatio']/(count($compliance)*2),1);
        }
    }


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

        <div id="main" class="container">

            <?php
            if(!isset($_SESSION['ID_bundle'])){ //si la variable session ID_bundle n'est pas set, il en faut une
            ?>
                <div class="header-compliance">
                        <h1>What Bundle do you want to watch ?</h1>
                </div>
                <div class="center">
                    <form  class="pure-form pure-form-aligned" action="./requests.php?op=initBundleId" method="post">
                        <fieldset>
                            <label for="bundle">List of Bundles : </label>
                            <select  id="bundle" name="bundle">
                                <?php for($a=0; $a<sizeof($bundles);$a++){
                                    echo '<option>'.$bundles[$a]['ID_bundle']." - ".$bundles[$a]['name_bundle'];
                                }
                                ?>
                            </select>
                            <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                        </fieldset>
                    </form>
                </div>
            <?php
            }else{ // variable ID_bundle set
            ?>
            <div class="header-compliance">
                <div class="row">
                <div class="col-md-8">
                <h1>Project Team Skills : <?php echo $nameBundle.'<br>'; ?></h1>

                <form  class="pure-form pure-form-aligned" action="./requests.php?op=initBundleId" method="post">
                    <fieldset>
                        <label for="bundle">Watch another bundle : </label> <!-- Formulaire pour regarder un autre Bundle -->
                        <select  id="bundle" name="bundle">
                            <?php for($a=0; $a<sizeof($bundles);$a++){
                                echo '<option>'.$bundles[$a]['ID_bundle']." - ".$bundles[$a]['name_bundle'];
                            }
                            ?>
                        </select>
                        <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                    </fieldset>
                </form>
                <form  class="pure-form pure-form-aligned " action="" method="get">
                    <fieldset>
                        <label for="chart_type">Choose chart type : </label> <!-- Formulaire pour modifier la nature des graphes -->
                        <select  id="chart_type" name="chart_type">
                            <option value="radar">Radar</option>
                            <option value="bar">Bar</option>
                        </select>
                        <button type="submit"  class="pure-button pure-button-primary">Valider</button>
                    </fieldset>
                </form>
              </div>
                <div class="col-md-4">
                <div class="exportButtonsDiv"> <!-- Boutons d'export des graphs -->
                    <button  class="btn btn-primary btn-block" onClick="exportImgBundleActive()">Export Active Charts to PNG</button>
                    <button  class="btn btn-primary btn-block" onClick="exportImgBundleSupport()">Export Support Charts to PNG</button>
                    <button  class="btn btn-primary btn-block" onClick="exportImgBundleSleeping()">Export Sleeping Charts to PNG</button>
                </div>
              </div>
            </div>
            <br>

            <div id="scenariosRadar" class="chartContainer">
              <div class="row">
                <div id = "active"  class="col-md-12"> <!-- div active -->
                    <h2 class="chartTitle" >Final Active Compliance : <?php echo $finalComplianceActive;?>%</h2>
                    <div class="canvas-wrapper inline">
                    <canvas id="scenarioActiveSkillsRadar" style="width: 600px; height: 600px;"></canvas>
                    <script>// TOUS LES GRAPHS SONT GéRéS par CHART JS (FREE LICENSE)
                        var activeCtxAS = document.getElementById('scenarioActiveSkillsRadar');
                        activeCtxAS.width = 700; // on impose la taille des graphs, sinon la taille est capricieuse
                        activeCtxAS.height = 600;
                        var activeCtxAS = document.getElementById('scenarioActiveSkillsRadar').getContext('2d');
                        var activeCtxAS = $('#scenarioActiveSkillsRadar');
                        var activeCtxAS = 'scenarioActiveSkillsRadar';

                        const chartOptionsParameters = getChartOptionsParameters();
                        const chartOptions = getChartOptions(chartOptionsParameters['chart_type'], "bundle");
                        const chartType = getChartType(chartOptionsParameters['chart_type']);

                        var activeRadarChart = new Chart(activeCtxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($projects); $row++)
                                                {
                                                    echo reduceName($projects[$row]['name_project']);
                                                    if($row+1 < count($projects))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "label":"Size Compliance",
                                        "data" : [<?php
                                                for ($row=0; $row < count($projects); $row++)
                                                    {
                                                        for ($i=0; $i < count($compliance); $i++)
                                                        {
                                                            if($projects[$row]['name_project']==$compliance[$i]['projectName']){
                                                                if(isset($compliance[$row]['complianceSizeActiveRatio'])){
                                                                    echo $compliance[$row]['complianceSizeActiveRatio'];
                                                                }else{
                                                                    echo '0';
                                                                }
                                                                if($row+1 < count($resultToDisplay))
                                                                {
                                                                    echo ',';
                                                                }
                                                            }
                                                        }

                                                    }
                                                ?>],
                                        "fill": true,
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Level compliance",
                                        "data" : [<?php
                                                for ($row=0; $row < count($projects); $row++)
                                                {
                                                    for ($i=0; $i < count($compliance); $i++)
                                                    {
                                                        if($projects[$row]['name_project']==$compliance[$i]['projectName']){
                                                            if(isset($compliance[$row]['complianceLevelActiveRatio'])){
                                                                echo $compliance[$row]['complianceLevelActiveRatio'];
                                                            }else{
                                                                echo '0';
                                                            }
                                                            if($row+1 < count($resultToDisplay))
                                                            {
                                                                echo ',';
                                                            }
                                                        }
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
                            }, "options":chartOptions
                        });
                    </script>
                    </div>
                  </div>
                </div>
                    <?php /* // Ce code devait initialement proposé d'ajoutés des projets venant de d'autres bundle. l interet étant limité et la fonctionnalité compliquée à codée, ce code reste commenté
                    <div class="inline" style="position:absolute;margin:40px 0 0 100px">
                        <div id="checkBoxBundle">
                            <label class="inline" style="vertical-align:top">List of Bundles : </label>
                            <div class="inline align-left" >
                                <?php for($a=0; $a<sizeof($bundles);$a++){
                                    echo '<input class="'.$bundles[$a]['ID_bundle'].' "type="checkbox"';
                                    if($bundles[$a]['ID_bundle'] == $_SESSION['ID_bundle']){
                                        echo 'checked';
                                    }
                                    echo '><b>'.$bundles[$a]['name_bundle'].'</b><br>';
                                }
                                ?>
                            </div>
                        </div>
                        <hr>
                        <div id="checkBoxBundle">
                            <label class="inline" style="vertical-align:top">List of Projects : </label>
                            <div class="inline align-left" >
                                <?php for($a=0; $a<sizeof($projects);$a++){
                                    echo '<input onchange="showProject(this)" class="'.$projects[$a]['ID_bundle'].'" type="checkbox" ';
                                    if($projects[$a]['ID_bundle'] == $_SESSION['ID_bundle']){
                                        echo 'checked';
                                    }
                                    echo '><b>'.$projects[$a]['name_project'].'</b><br>';

                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    */?>
                <br>
                <div class="row">
                <div id = "support"  class="col-md-12"> <!-- div support -->
                    <h2 class="chartTitle">Final Support Compliance : <?php echo $finalComplianceSupport;?>%</h2>
                    <div class="canvas-wrapper inline">
                    <canvas id="scenarioSupportSkillsRadar" style="width: 600px; height: 600px;"></canvas>
                    <script>
                        var supportCtxAS = document.getElementById('scenarioSupportSkillsRadar');
                        supportCtxAS.width = 700;
                        supportCtxAS.height = 600;
                        var supportCtxAS = document.getElementById('scenarioSupportSkillsRadar').getContext('2d');
                        var supportCtxAS = $('#scenarioSupportSkillsRadar');
                        var supportCtxAS = 'scenarioSupportSkillsRadar';

                        var supportRadarChart = new Chart(supportCtxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($projects); $row++)
                                                {
                                                    echo reduceName($projects[$row]['name_project']);
                                                    if($row+1 < count($projects))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "label":"Size Compliance",
                                        "data" : [<?php
                                                for ($row=0; $row < count($projects); $row++)
                                                    {
                                                        for ($i=0; $i < count($compliance); $i++)
                                                        {
                                                            if($projects[$row]['name_project']==$compliance[$i]['projectName']){
                                                                if(isset($compliance[$row]['complianceSizeSupportRatio'])){
                                                                    echo $compliance[$row]['complianceSizeSupportRatio'];
                                                                }else{
                                                                    echo '0';
                                                                }
                                                                if($row+1 < count($resultToDisplay))
                                                                {
                                                                    echo ',';
                                                                }
                                                            }
                                                        }

                                                    }
                                                ?>],
                                        "fill": true,
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Level compliance",
                                        "data" : [<?php
                                                for ($row=0; $row < count($projects); $row++)
                                                {
                                                    for ($i=0; $i < count($compliance); $i++)
                                                    {
                                                        if($projects[$row]['name_project']==$compliance[$i]['projectName']){
                                                            if(isset($compliance[$row]['complianceLevelSupportRatio'])){
                                                                echo $compliance[$row]['complianceLevelSupportRatio'];
                                                            }else{
                                                                echo '0';
                                                            }
                                                            if($row+1 < count($resultToDisplay))
                                                            {
                                                                echo ',';
                                                            }
                                                        }
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
                            }, "options":chartOptions
                        });
                    </script>
                    </div>
                </div>
              </div>
              <br>
              <div class="row">
                <div id = "sleeping"  class="col-md-12"> <!-- div sleeping -->
                    <h2 >Final Sleeping Compliance : <?php echo $finalComplianceSleeping;?>%</h2>
                    <div class="canvas-wrapper inline">
                    <canvas id="scenarioSleepingSkillsRadar" style="width: 600px; height: 600px;"></canvas>
                    <script>
                        var sleepingCtxAS = document.getElementById('scenarioSleepingSkillsRadar');
                        sleepingCtxAS.width = 700;
                        sleepingCtxAS.height = 600;
                        var sleepingCtxAS = document.getElementById('scenarioSleepingSkillsRadar').getContext('2d');
                        var sleepingCtxAS = $('#scenarioSleepingSkillsRadar');
                        var sleepingCtxAS = 'scenarioSleepingSkillsRadar';

                        var sleepingRadarChart = new Chart(sleepingCtxAS, {
                            "type": chartType,
                            "data": {
                                "labels":  [<?php
                                            for ($row=0; $row < count($projects); $row++)
                                                {
                                                    echo reduceName($projects[$row]['name_project']);
                                                    if($row+1 < count($projects))
                                                    {
                                                        echo ',';
                                                    }
                                                }
                                            ?>],
                                "datasets": [
                                    {
                                        "label":"Size Compliance",
                                        "data" : [<?php
                                                for ($row=0; $row < count($projects); $row++)
                                                    {
                                                        for ($i=0; $i < count($compliance); $i++)
                                                        {
                                                            if($projects[$row]['name_project']==$compliance[$i]['projectName']){
                                                                if(isset($compliance[$row]['complianceSizeSleepingRatio'])){
                                                                    echo $compliance[$row]['complianceSizeSleepingRatio'];
                                                                }else{
                                                                    echo '0';
                                                                }
                                                                if($row+1 < count($resultToDisplay))
                                                                {
                                                                    echo ',';
                                                                }
                                                            }
                                                        }

                                                    }
                                                ?>],
                                        "fill": true,
                                        "backgroundColor":"rgba(255, 99, 132, 0.3)",
                                        "borderColor":"rgb(255, 99, 132)",
                                        "pointBackgroundColor":"rgb(255, 99, 132)",
                                        "pointBorderColor":"#fff",
                                        "pointHoverBackgroundColor":"#fff",
                                        "pointHoverBorderColor":"rgb(255, 99, 132)"
                                    },
                                    {
                                        "label":"Level compliance",
                                        "data" : [<?php
                                                for ($row=0; $row < count($projects); $row++)
                                                {
                                                    for ($i=0; $i < count($compliance); $i++)
                                                    {
                                                        if($projects[$row]['name_project']==$compliance[$i]['projectName']){
                                                            if(isset($compliance[$row]['complianceLevelSleepingRatio'])){
                                                                echo $compliance[$row]['complianceLevelSleepingRatio'];
                                                            }else{
                                                                echo '0';
                                                            }
                                                            if($row+1 < count($resultToDisplay))
                                                            {
                                                                echo ',';
                                                            }
                                                        }
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
                            }, "options": chartOptions
                        });
                    </script>
                    </div>
                </div>
              </div>
              </div>
              </div>
            </div>

            <?php
            }
            ?>
        </div>

</body>
</html>
