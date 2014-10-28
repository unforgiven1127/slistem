
$(function () {

  var aScores1 = Array();
  var aScores2 = Array();
  var aSkills = Array();
  var nCount = 0;

  $('.skillSet').each(function(){
    skill = $(this).attr('id');
    aSkills[nCount]=skill.charAt(0).toUpperCase() + skill.slice(1);

    score1 = $('#'+skill+' .percent1').attr('value');
    aScores1[nCount]=parseInt(score1);

    score2 = $('#'+skill+' .percent2').attr('value');
    aScores2[nCount]=parseInt(score2);

    nCount++;
  });

  var name1 = $('#name1').html();
  var name2 = $('#name2').html();

  var aSeries = [{'name': name1, 'color': '#5198CE', data: aScores1},
                 {'name': name2, 'color': '#54B854', data: aScores2}];

  var aToolTipb = aToolTip = {
          headerFormat: '<strong>{point.key}</strong>',
          pointFormat: '<br/><span style="color:{series.color};">{series.name}: </span>' +
              '<b>{point.y:.1f} %</b>',
          shared: true,
          useHTML: true
      };

  delete aToolTipb['shared'];

  $('#esaCols').highcharts({
     chart: {
	        type: 'bar'
	    },
	    title: {
	        text: null,
	    },
      credits: {
          enabled: false
      },
	    xAxis: {
	        categories: aSkills
	    },
	    yAxis: {
          tickInterval: 20,
	        min: 0,
          max: 100,
          title: {
              text: 'Score (%)'
          }
	    },
      tooltip : aToolTipb,
	    series: aSeries
  });

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
      tooltip: aToolTip,
	    series: aSeries
	});
});