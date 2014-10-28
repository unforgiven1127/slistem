Highcharts.theme =
{
   plotOptions:
   {
      series: {
         shadow: true
      },
      line: {
         dataLabels: {
            color: '#CCC'
         },
         marker: {
            lineColor: '#333'
         }
      },
      spline: {
         marker: {
            lineColor: '#333'
         }
      },
      scatter: {
         marker: {
            lineColor: '#333'
         }
      },
      candlestick: {
         lineColor: 'white'
      }
   },
   credits: {
      enabled: false
  }
};

// Apply the theme
var highchartsOptions = Highcharts.setOptions(Highcharts.theme);