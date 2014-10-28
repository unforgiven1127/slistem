
$(function () {

  var aScores = Array();
  var aSkills = Array();
  var aPrevScores = Array();

  var nCount = 0;
  $('.skillSet').each(function(){
    skill = $(this).attr('id');
    aSkills[nCount]=skill.charAt(0).toUpperCase() + skill.slice(1);

    score = $('#'+skill+' .percent').attr('value');
    aScores[nCount]=parseInt(score);

    score = $('#'+skill+' .percent').attr('prev_value');
    aPrevScores[nCount]=parseInt(score);
    nCount++;
  });


  /*var skillsH = '[\''+aSkills.join('\',\'')+'\']';
  var scoresH = '['+aScores.join(',')+']';
  var prevscoresH = '['+aPrevScores.join(',')+']';*/

//order reversed ---> esa2 on top
  var aSeries = [{
      name: 'Your Results (ESA2)',
      data: aScores,
      pointPlacement: 'on'
  },
  {
    name: 'ESA1',
    data: aPrevScores,
    pointPlacement: 'on'
  }];

  //console.log(skillsH);
  //console.log(scoresH);
  console.log(aSeries);

	$('#esaChart').highcharts({

	    chart: {
	        polar: true,
	        type: 'area'
	    },

	    title: {
	        text: null,
	    },

      credits: {
          enabled: false
      },

      legend: {
          enabled: true
      },

	    xAxis: {
	        categories: aSkills,
	        tickmarkPlacement: 'on',
	        lineWidth: 0
	    },

	    yAxis: {
	        gridLineInterpolation: 'polygon',
          tickInterval: 20,
	        lineWidth: 0,
	        min: 0,
          max: 100
	    },
      plotOptions: {
          series: {
              enableMouseTracking:false,
              dataLabels: {
                  enabled: false
              }
          }
      },
	    /*series: [{
          'name': 'Your Results',
	        data: aScores,
	        pointPlacement: 'on'
	    }]*/
      series: aSeries

	});
});