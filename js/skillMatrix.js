function active(e)
 {
   if(e.checked){
    $('#idSkillProjectTable > thead  > tr > th').each(function() {
        if($(this).text().indexOf('Active') != -1){
            $(this).hide();
        }
    });
    $('#idSkillProjectTable > tbody  > tr > td').each(function() {
        if($(this).attr('class').indexOf('Active') != -1){
            $(this).hide();
        }
    });
   }else{
    $('#idSkillProjectTable > thead  > tr > th').each(function() {
        if($(this).text().indexOf('Active') != -1){
            $(this).show();
        }
    });
    $('#idSkillProjectTable > tbody  > tr > td').each(function() {
        if($(this).attr('class').indexOf('Active') != -1){
            $(this).show();
        }
    });
   }
 }
 function support(e)
 {
   if(e.checked){
    $('#idSkillProjectTable > thead  > tr > th').each(function() {
        if($(this).text().indexOf('Support') != -1){
            $(this).hide();
        }
    });
    $('#idSkillProjectTable > tbody  > tr > td').each(function() {
        if($(this).attr('class').indexOf('Support') != -1){
            $(this).hide();
        }
    });
   }else{
    $('#idSkillProjectTable > thead  > tr > th').each(function() {
        if($(this).text().indexOf('Support') != -1){
            $(this).show();
        }
    });
    $('#idSkillProjectTable > tbody  > tr > td').each(function() {
        if($(this).attr('class').indexOf('Support') != -1){
            $(this).show();
        }
    });
   }
 }
 function sleeping(e)
 {
   if(e.checked){
    $('#idSkillProjectTable > thead  > tr > th').each(function() {
        if($(this).text().indexOf('Sleeping') != -1){
            $(this).hide();
        }
    });
    $('#idSkillProjectTable > tbody  > tr > td').each(function() {
        if($(this).attr('class').indexOf('Sleeping') != -1){
            $(this).hide();
        }
    });
   }else{
    $('#idSkillProjectTable > thead  > tr > th').each(function() {
        if($(this).text().indexOf('Sleeping') != -1){
            $(this).show();
        }
    });
    $('#idSkillProjectTable > tbody  > tr > td').each(function() {
        if($(this).attr('class').indexOf('Sleeping') != -1){
            $(this).show();
        }
    });
   }
 }
 function functional(e)
 {
   if(e.checked){
    $('#idSkillProjectTable > tbody  > tr').each(function() {
        if($(this).find( 'td:first').text().indexOf('Functional') != -1){
            $(this).hide();
        }
    });
        addTableBorder();
   }else{
    $('#idSkillProjectTable > tbody  > tr').each(function() {
        if($(this).find( 'td:first').text().indexOf('Functional') != -1){
            $(this).show();
        }
    });
            addTableBorder();
   }
 }
 function technical(e)
 {
   if(e.checked){
    $('#idSkillProjectTable > tbody  > tr').each(function() {
        if($(this).find( 'td:first').text().indexOf('Technical') != -1){
            $(this).hide();
        }
    });
    addTableBorder();
   }else{
    $('#idSkillProjectTable > tbody  > tr').each(function() {
        if($(this).find( 'td:first').text().indexOf('Technical') != -1){
            $(this).show();
        }
    });
    addTableBorder();
   }
 }

 function showProject(e){
    if(e.checked){}
        console.log(activeRadarChart.data.datasets[0].data[0]);
        console.log(activeRadarChart.data.datasets[1].data[0]);
        console.log(activeRadarChart.data.labels.splice(0,1));

}

function exportImg(){
    html2canvas(document.querySelector('#idSkillProjectTable')).then(function(canvas) {
        saveAs(canvas.toDataURL(), 'ExportProjectTeamSkill.png');
    });
}

function exportImgProjectActive(){
    html2canvas(document.querySelector('#scenarioActive')).then(function(canvas) {
        saveAs(canvas.toDataURL(), 'activeProjectRadar.png');
    });

}

function exportImgProjectSupport(){
    html2canvas(document.querySelector('#scenarioSupport')).then(function(canvas) {
        saveAs(canvas.toDataURL(), 'supportProjectRadar.png');
    });

}

function exportImgProjectSleeping(){
    html2canvas(document.querySelector('#scenarioSleeping')).then(function(canvas) {
        saveAs(canvas.toDataURL(), 'sleepingProjectRadar.png');
    });

}
function exportImgBundleActive(){
    html2canvas(document.querySelector('#active')).then(function(canvas) {
        saveAs(canvas.toDataURL(), 'activeBundleRadar.png');
    });

}

function exportImgBundleSupport(){
    html2canvas(document.querySelector('#support')).then(function(canvas) {
        saveAs(canvas.toDataURL(), 'supportBundleRadar.png');
    });

}

function exportImgBundleSleeping(){
    html2canvas(document.querySelector('#sleeping')).then(function(canvas) {
        saveAs(canvas.toDataURL(), 'sleepingBundleRadar.png');
    });

}

function saveAs(uri, filename) {

    var link = document.createElement('a');

    if (typeof link.download === 'string') {

        link.href = uri;
        link.download = filename;

        //Firefox requires the link to be in the body
        document.body.appendChild(link);

        //simulate click
        link.click();

        //remove the link when done
        document.body.removeChild(link);

    } else {

        window.open(uri);

    }
}

function countRows() {
  var table = document.getElementById('idSkillProjectTable');
  var count = 0;
  var line_number = 0;
  for (var i = 0, row; row = table.rows[i]; i++) {

    if (row.style.display !== "none") {
      count += 1
    }
  }
  return count;
}

function addTableBorder() {
  var table = document.getElementById('idSkillProjectTable');
  var count = 0;
  var line_number = countRows();
  for (var i = 0, row; row = table.rows[i]; i++) {

    if (row.style.display !== "none") {
      count += 1
    }
     for (var j = 0, col; col = row.cells[j]; j++) {
       if (count === line_number && row.style.display != "none") {
         if (!col.className.match("Bottom")) {
         col.className = col.className + "Bottom";
        }
       }
      else if (count !== line_number && row.style.display != "none" && col.className.match("Bottom")) {
        col.className = col.className.slice(0, -6)
      }
     }
  }
}
