<?php 
session_start();
if(isset($_POST["scenario_search"])){
    $_SESSION["scenario_search"] = $_POST["scenario_search"];
}
if(isset($_POST["skill_search"])){
    $_SESSION["skill_search"] = $_POST["skill_search"];
}
if(isset($_POST["project_search"])){
    $_SESSION["project_search"] = $_POST["project_search"];
}
?>