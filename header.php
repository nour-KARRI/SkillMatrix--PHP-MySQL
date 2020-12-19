<?php
?>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="css/skillMatrix.css" />
<script>

$(document).ready(function() { //fonction d envoies des ID au lieu des noms

    $('#submitSearch').click(function()
    {
        var value = $('#search').val();
        window.location ="viewUser.php?id_user="+$('#users [value="' + value + '"]').data('value')
    });
});
</script>

<div class="navbar navbar-expand-lg navbar-dark bg-primary">
  <a class="navbar-brand" href="#">Skill Matrix</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <div>
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                  <a class="nav-link" href="home.php">Home</a>
                </li>
                <li class="nav-item"><a class="nav-link"href="myskills.php">My Skills</a></li>
                <?php if ($_SESSION['rights'] != 10) { ?>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Projects
                  </a>
                    <ul class="dropdown-menu">
                        <li>
                          <a class="dropdown-item" href="projectDashboard.php">Project Dashboard</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="projectRequirement.php" >Project Requirement</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="projectStaff.php" >Staff Project</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="teamSkill.php" >Team Skill</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="projectSummary.php">Project Summary</a>
                        </li>
                        <li><a class="dropdown-item" href="prmView.php">PRM View</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Bundle
                  </a>
                    <ul class="dropdown-menu">
                        <li>
                          <?php if ($_SESSION['rights'] == 12 || $_SESSION['rights'] == 13 || $_SESSION['rights'] == 14) { ?>
                          <a class="dropdown-item" href="viewBundles.php">View Bundles</a>
                        </li>
                        <?php } ?>
                        <li>
                          <a class="dropdown-item" href="viewProjects.php">View Projects</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="viewCompliance.php">View Compliance</a>
                        </li>
                    </ul>
                </li>
                <?php if ($_SESSION['rights'] == 11 || $_SESSION['rights'] == 12 || $_SESSION['rights'] == 13 || $_SESSION['rights'] == 14) { ?>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Admin Project
                  </a>
                    <ul class="dropdown-menu">
                        <li>
                          <a class="dropdown-item" href="adminProjectSkills.php" >Skills & Category skills</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="approval.php" >Waiting for approval</a>
                        </li>
                        <?php if ($_SESSION['rights'] == 13 || $_SESSION['rights'] == 12) { ?>
                        <li>
                          <a class="dropdown-item" href="criticalityEdit.php">Edit criticality</a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="editPRM.php">Edit PRMS</a>
                        </li>
                        <?php }?>
                    </ul>
                </li>
                <?php } ?>
              <?php } ?>
            </ul>
        </div>
            <div class="navbar-nav ml-auto">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <div class="form-inline">
                            <input type ="text" class="radius" list="users" placeholder="Search.." id="search" name="search" value="">
                                <datalist id=users>
                                    <?php
                                    if(!isset($user[0]["name_user"])){
                                        $req = $bdd->prepare('SELECT * FROM user');
                                        $req->execute();
                                        $user = $req->fetchAll();
                                    }

                                    for($a=0; $a<sizeof($user);$a++){

                                        echo '<option data-value="'.$user[$a]['ID_user'].'" value="'.$user[$a]["name_user"].' '.$user[$a]['family_name_user'].'" >';
                                    }
                                    ?>
                                </datalist>
                            <button id="submitSearch" class="pure-button pure-button-blue"><img class="icon" src="img/wen.png"></button>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo $_SESSION['name'];?>
                      </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="account.php">My Account</a></li> <!--  Vide pour le moment -->
                        </ul>
                    </li>
                </ul>
                <form method="POST" action="disconnect.php" >
                 <button type="submit" class="pure-button button-error">Logout &nbsp&nbsp<img style="margin-bottom:-3px" width="18px" height="18px" src="img\logout.svg" alt="Déconnexion"></button>
              </form>
            </div>



    </div>
</div>
