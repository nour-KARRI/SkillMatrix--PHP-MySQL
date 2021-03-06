function getChartOptions(chart_type, type, maxSize) {
  var options = null;
  var sizeFromType = null;
  if(type=="projectSize"){
    sizeFromType = maxSize;
  }
  else if(type == "bundle"){
    sizeFromType = 100;
  }else if(type == "projectLevel"){
    sizeFromType = 4;
  }

  switch (chart_type) {
    case "radar":
      options = {
        "scale": {
            "ticks": {
                "beginAtZero": true,
                "max" : sizeFromType
            }
        },
        "elements":{
            "line":{
                "tension":0,"borderWidth":3
            }
        },
        tooltips: {
            enabled: true,
            callbacks: {
                label: function(tooltipItem, data) {
                    return data.datasets[tooltipItem.datasetIndex].label + ' : ' + data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
                }
            }
        },
        legend: {
            position: "bottom"
        }
      }
      break;
    case "bar":
    options = {
      "scales": {
        yAxes: [{
          "ticks": {
              "beginAtZero": true,
              "max" : sizeFromType
          }
        }]
      },
      "elements":{
          "line":{
              "tension":0,"borderWidth":3
          }
      },
      tooltips: {
          enabled: true,
          callbacks: {
              label: function(tooltipItem, data) {
                  return data.datasets[tooltipItem.datasetIndex].label + ' : ' + data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
              }
          }
      },
      legend: {
          position: "bottom"
      }
    }
      break;
    default:
    options = {
      "scale": {
          "ticks": {
              "beginAtZero": true,
              "max" : sizeFromType
          }
      },
      "elements":{
          "line":{
              "tension":0,"borderWidth":3
          }
      },
      tooltips: {
          enabled: true,
          callbacks: {
              label: function(tooltipItem, data) {
                  return data.datasets[tooltipItem.datasetIndex].label + ' : ' + data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
              }
          }
      },
      legend: {
          position: "bottom"
      }
    }
    break;
  }
return options;
}

function getChartOptionsParameters() {
  param_array = {};
  location.search.substr(1).split("&").forEach(function(item) {
    param_array[item.split("=")[0]] = item.split("=")[1]
  });
  return param_array;
}

function getChartType(chart_type) {
  var return_value = null;
  switch (chart_type) {
    case "radar":
      return_value = "radar";
      break;
    case "bar":
      return_value = "bar";
      break;
    default:
      return_value = "radar";
      break;
  }
  return return_value;
}

//Choisit le type de dataset et son remplissage en fonction du choix de l'utilisateur.

function getDatasetType(chart_type) {
  var return_value = null;
  switch (chart_type) {
    case "radar":
      return_value = [null, true];
      break;
    case "bar":
      return_value = ['line', false];
      break;
    default:
      return_value = [null, true];
      break;
  }
  return return_value;
}
