
function validateNewMemberForm() {
  var error_object = {
    errorMessage : "Warning : <br>"
  }

  const project = document.forms['addMemberToProject']['project'].value;
  const user = document.forms['addMemberToProject']['user'].value;
  const scenario = document.forms['addMemberToProject'].elements['scenario[]'];
  const role = document.forms['addMemberToProject']['role'].value;
  const core_team = document.forms['addMemberToProject'].elements['core_team'];

  if (!project || project === "" || !scenario || (!scenario[0].checked && !scenario[1].checked && !scenario[2].checked)
      || !role || role === "" || !core_team) {
        $('.form_error').css("display", "block");
        $('.form_error').html("Warning : Please fill all the form fields.");
        return false;
      }

  const isProjectInputOk = validateProjectForm(false, error_object, 'addMemberToProject');
  const isUserInputOk = validateUserForm(false, error_object, 'addMemberToProject');

  if (isProjectInputOk === false || isUserInputOk === false) {
    $('.form_error').css("display", "block");
    $('.form_error').html(error_object.errorMessage);
    return false;
  }
  return true;
}


function validateSkillRequirementForm() {
  var error_object = {
    errorMessage : "Warning : <br>"
  }

  const project = document.forms['skillForm']['project'].value;
  const scenario = document.forms['skillForm'].elements['scenario[]'];
  const skill = document.forms['skillForm']['skill'].value;
  const level = document.forms['skillForm']['level'].value;
  const size = document.forms['skillForm']['size'].value;
  const skill_criticality = document.forms['skillForm']['crit'].value;

  if (!project || project === "" || !scenario || (!scenario[0].checked && !scenario[1].checked && !scenario[2].checked) || !skill || skill === "" || !level || level === ""
      || !size || size === "" || !skill_criticality || skill_criticality === "") {

        $('.form_error').css("display", "block");
        $('.form_error').html("Warning : Please fill all the form fields.");
        return false;
  }

  const isSkillInputOk = validateSkillForm(false, error_object);
  const isProjectInputOk = validateProjectForm(false, error_object, 'skillForm');

  if (isSkillInputOk === false || isProjectInputOk === false) {
    $('.form_error').css("display", "block");
    $('.form_error').html(error_object.errorMessage);
    return false;
  }
  return true;
}

function validateUserForm(display, error_object, formName) {
  const user = document.forms[formName]['user'].value;

  if (!user || user === "") {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : Please fill all the form fields.");
    }
    return false;
  }

  var string_array = user.split('-');
  if (string_array.length < 2) {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : There is a problem in the user field. Please verify that the ID is present, as long with the user's name. An hyphen between the ID and the name is also necessary.");
    }
    else {
      error_object.errorMessage += "- There is a problem in the user field. Please verify that the ID is present, as long with the user's name. An hyphen between the ID and the name is also necessary.<br>";
    }
    return false;
  }

  var user_id = string_array[0];
  var user_name = string_array[1];

  if (isNaN(parseInt(user_id))) {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : User ID is missing.");
    }
    else {
      error_object.errorMessage += "- User ID is missing.<br>"
    }
    return false;
  }

  if (!user_name || user_name.trim() === "") {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : Wrong or empty user name.");
    }
    else {
      error_object.errorMessage += "- Wrong or empty user name.\n"
    }
    return false;
  }

  return true;
}

function validateProjectForm(display, error_object, formName) {
  const project = document.forms[formName]['project'].value;

  if (!project || project === "") {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : Please fill all the form fields.");
    }
    return false;
  }

  var string_array = project.split('-');
  if (string_array.length != 2) {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : There is a problem in the project field. Please verify that the ID is present, as long with the project's name. An hyphen between the ID and the name is also necessary. Hyphens are not allowed in a project name.");
    }
    else {
      error_object.errorMessage += "- There is a problem in the project field. Please verify that the ID is present, as long with the project's name. An hyphen between the ID and the name is also necessary. Hyphens are not allowed in a project name.<br>";
    }
    return false;
  }

  var project_id = string_array[0];
  var project_name = string_array[1];

  if (isNaN(parseInt(project_id))) {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : Project ID is missing.");
    }
    else {
      error_object.errorMessage += "- Project ID is missing.<br>"
    }
    return false;
  }

  if (!project_name || project_name.trim() === "") {
    if (display) {
      $('.form_error').css("display", "block");
      $('.form_error').html("Warning : Wrong project name.");
    }
    else {
      error_object.errorMessage += "- Wrong project name.\n"
    }
    return false;
  }

  return true;
}

function validateSkillForm(display, error_object) {
    const skill = document.forms['skillForm']['skill'].value;
    const level = document.forms['skillForm']['level'].value;

    if (!skill || !level || skill === "" || level === "") {

      if (display) {
        $('.form_error').css("display", "block");
        $('.form_error').html("Warning : Please fill all the form fields.");
      }
      return false;
    }

    var string_array = skill.split(':');
    if (string_array.length < 2) {
      if (display) {
        $('.form_error').css("display", "block");
        $('.form_error').html("Warning : There is a problem in the skill field. Please verify that the ID is present, as long with the skill's category and name. An hyphen between the ID and the skill category is also necessary.");
      }
      else {
        error_object.errorMessage += "- There is a problem in the skill field. Please verify that the ID is present, as long with the skill's category and name. An hyphen between the ID and the skill category is also necessary.<br>"
      }
      return false;
    }
    var project_id = string_array[0].split('-')[0];
    var skill_type = string_array[0].split('-')[1];
    var skill_name = string_array[1];

    if (isNaN(parseInt(project_id))) {
      if (display) {
        $('.form_error').css("display", "block");
        $('.form_error').html("Warning : Skill ID is missing.");
      }
      else {
        error_object.errorMessage += "- Skill ID is missing.<br>"
      }
      return false;
    }

    if (!skill_type || (!skill_type.match('Functional') && !skill_type.match('Technical'))) {
      if (display) {
        $('.form_error').css("display", "block");
        $('.form_error').html("Warning : Skill category is missing.");
        return false;
      }
      else {
        error_object.errorMessage += "- Skill category is missing.<br>";
      }
    }

    if (!skill_name || skill_name.trim() === "") {
      if (display) {
        $('.form_error').css("display", "block");
        $('.form_error').html("Warning : Wrong skill name.");
      }
      else {
        error_object.errorMessage += "- Wrong skill name.<br>"
      }
      return false;
    }

    $('.form_error').css("display", "none");
    if (display) {
      alert(" Your request is sent for approval by one of the managers, Thank you");
    }
    return true;
}
