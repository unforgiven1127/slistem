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
  },
  colors: ['#2f7ed8','#F4D211','#7AA515','#B51B1B','#1aadce','#492970','#f28f43','#77a1e5','#c42525','#a6c96a',
      '#F2E124', '#DB74C4'],
};

// Apply the theme
var highchartsOptions = Highcharts.setOptions(Highcharts.theme);