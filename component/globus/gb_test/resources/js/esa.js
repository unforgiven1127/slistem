
$(function () {

  var aEsa2 = Array();
  var aSkills = Array();
  var aEsa1 = Array();
  var bIsEsa2 = parseInt($('#skillSets').attr('esa2'), 10);
  var nCount = 0;

  $('.skillSet').each(function(){
    skill = $(this).attr('id');
    aSkills[nCount]=skill.charAt(0).toUpperCase() + skill.slice(1);


    score = $('#'+skill+' .percent').attr('value');
    aEsa2[nCount]=parseInt(score);

    score = $('#'+skill+' .percent').attr('prev_value');
    aEsa1[nCount]=parseInt(score);

    nCount++;
  });


  /*var skillsH = '[\''+aSkills.join('\',\'')+'\']';
  var scoresH = '['+aEsa2.join(',')+']';
  var prevscoresH = '['+aEsa1.join(',')+']';*/

  if(bIsEsa2 > 0)
  {
    //order reversed ---> esa2 on top
    var aSeries = [{
        name: 'Your Results (ESA2)',
        data: aEsa2,
        pointPlacement: 'on'
    },
    {
      name: 'ESA1',
      data: aEsa1,
      pointPlacement: 'on'
    }];
  }
  else
  {
    var aSeries = [
    {
      name: 'ESA1',
      data: aEsa1,
      pointPlacement: 'on'
    }];
  }


  //console.log(skillsH);
  //console.log(scoresH);
  //console.log(aSeries);

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