<?php

require_once('component/charts/charts.class.php5');

class CChartsEx extends CCharts
{

  private $casAvailableType = array('line', 'bar', 'column', 'pie' /*'arearange', 'areasplinerange', 'columnrange', 'pie'*/);
  private $csChartType = '';
  private $csChartTitle = '';
  private $csAxisTitleX = '';
  private $csAxisTitleY = '';
  private $csToolTip = '';
  private $cnLegendPosX = '';
  private $cnLegendPosY = '';
  private $csWidth = '100%';
  private $csHeight = '100%';

  private $casAxis = array();
  private $casChartData = array();
  private $casChartRender = array();

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    return $asActions;
  }

  // Normal functions
  public function getHtml()
  {
    $this->_processUrl();

    switch($this->csAction)
    {
      case CONST_ACTION_VIEW:

        if($this->_allowedIP())
          return $this->_getPublicCharts();
        break;
    }
  }

  //Ajax function

  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
    }
  }

  public function setChartSize($psWidth,$psHeight)
  {
    if(!assert('is_string($psWidth) && is_string($psHeight)'))
      return false;

    $this->csWidth = $psWidth;
    $this->csHeight = $psHeight;

    return true;
  }

  public function setChartRender($psElementToRender, $psRenderType, $psCustom = '')
  {
    if(!assert('is_string($psElementToRender) && !empty($psElementToRender)'))
      return false;

    if(!assert('is_string($psRenderType) && !empty($psRenderType)'))
      return false;

    if(!in_array($psElementToRender, array('plot', 'tooltip')))
    {
      assert('false; // render option is only available for plot and tooltip');
      return false;
    }

    if($psRenderType == 'custom' && !empty($psCustom))
      $this->casChartRender[$psElementToRender] = $psCustom;
    else
      $this->casChartRender[$psElementToRender] = $psRenderType;

    return true;
  }

  public function createPie($psChartTitle = '', $paData)
  {
    $this->csChartType = 'pie';
    $this->csChartTitle = $psChartTitle;


    if(is_array($paData))
      $this->casChartData = $paData;
    else
      $this->casChartData = array();

    return true;
  }

  public function createChart($psType, $psChartTitle = '', $psAxisTitleY = '', $psAxisTitleX = '')
  {
    if(!in_array($psType, $this->casAvailableType))
    {
      assert('false; // chart type does not exist');
      return false;
    }

    $this->csChartType = $psType;
    $this->csChartTitle = $psChartTitle;
    $this->csAxisTitleX = $psAxisTitleX;
    $this->csAxisTitleY = $psAxisTitleY;

    $this->csLegendDirection = 'horizontal';
    $this->cnLegendPosX = 0;
    $this->cnLegendPosY = -10;
    $this->csToolTip = '';
    $this->casAxis = array();
    $this->casChartData = array();

    return true;
  }

  public function setChartLegendPosition($psDirection, $pnPosX = 0, $pnPosY = 0)
  {
    if(!assert('is_numeric($pnPosX) && is_numeric($pnPosY)'))
      return false;

    if(strtolower($psDirection) == 'vertical')
      $this->csLegendDirection = 'vertical';
    else
      $this->csLegendDirection = 'horizontal';

    $this->cnLegendPosX = $pnPosX;
    $this->cnLegendPosY = $pnPosY;
    return false;
  }

  public function setToolTip($psJs)
  {
    if(!assert('!empty($psJs)'))
      return false;

    $this->csToolTip = $psJs;
    return true;
  }


  public function setChartAxis($pasData)
  {
    if(!assert('is_array($pasData)') || empty($pasData))
      return false;

    $this->casAxis = $pasData;
    return true;
  }

  public function setChartData($psStreamName, $pasData, $psColor = '')
  {
    if(!assert('is_array($pasData)') || empty($pasData))
      return false;

    $nValues = count($this->casAxis);
    if(count($pasData) != $nValues)
    {
      assert('false; // nb of elements are different between axis and this set of data');
      return false;
    }

    foreach($pasData as $vValue)
    {
      if(!is_numeric($vValue))
      {
        assert('false; // a value in this set of data is not a number');
        return false;
      }
    }

    $this->casChartData[] = array('label' => $psStreamName, 'data' => $pasData, 'color' => $psColor);
    return true;
  }

  public function getChartDisplay($pbAjax = false, $psId = '', $pnMarginRight = 0, $pnMarginBottom = 25)
  {
    if(!assert('is_numeric($pnMarginRight) && is_numeric($pnMarginBottom)'))
      return '';

    //check if everythings is good:
    if(empty($this->casAxis))
    {
      //assert('false; // chart need a axis set of values ');
      return '';
    }

    $sHTML = '';
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    if(!$pbAjax)
    {
      $oPage->addJsFile($this->getResourcePath().'js/highcharts.js');
      $oPage->addJsFile($this->getResourcePath().'js/themes/bcm_theme.js'); //common style for all charts
    }

    if(!empty($psId))
      $sChartId = $psId;
    else
      $sChartId = uniqid();

    $asSeries = array();
    foreach($this->casChartData as $asSerie)
    {
      if(isset($asSerie['color']) && !empty($asSerie['color']))
        $asSeries[] = "{name: '".addslashes($asSerie['label'])."', data: [".implode(',', $asSerie['data'])."], color: '".$asSerie['color']."'}";
      else
        $asSeries[] = "{name: '".addslashes($asSerie['label'])."', data: [".implode(',', $asSerie['data'])."]}";
    }

    //TODO: add plot option and implement setChartRender for this type of chart
    $sJavascript = "

      var chart1; // globally available
      $(document).ready(function()
      {
        chart1 = new Highcharts.Chart({
         credits: {
            enabled: false
         },
         chart: {
            renderTo: '".$sChartId."',
            type: '".$this->csChartType."',
            marginRight: ".$pnMarginRight.",
            marginBottom: ".$pnMarginBottom."
         }, ";

        if($this->csChartTitle)
        {
          $sJavascript.= " title: {  text: '".$this->csChartTitle."' }, ";
        }

        $sJavascript.= "

         xAxis: {
           title: {text: '".$this->csAxisTitleX."'},
            categories: ['".implode("', '", $this->casAxis)."']

         },
         yAxis: {
            title: {text: '".$this->csAxisTitleY."'}
         },
         /*tooltip: {
                formatter: function() { return ''+ this.series.name +'<br/>'+ this.x +': '+ this.y +'°C'; }
         },*/
         legend:
         {
            layout: '".$this->csLegendDirection."',
            align: 'right',
            verticalAlign: 'top',
            x: ".$this->cnLegendPosX.",
            y: ".$this->cnLegendPosY.",
            borderWidth: 0,
            backgroundColor: '#fff',
            floating: true,
            shadow: true,
            padding: 3,
            margin: 0,
            itemDistance: 15
         },
         series: [".implode(',', $asSeries)." ]
        });
      });
      ";

    if(!$pbAjax)
      $oPage->addCustomJs($sJavascript);
    else
    {
      $sHTML.= '<script>function initChart(){ '.$sJavascript.' } </script>';
    }

    $sHTML.= $oHTML->getBlocStart($sChartId, array('style' => 'height:'.$this->csHeight.'; width:'.$this->csWidth.';'));
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }


  public function displayPie($pbAjax = false, $psId = '')
  {

    $sHTML = '';
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    if(!$pbAjax)
    {
      $oPage->addJsFile($this->getResourcePath().'js/highcharts.js');
      $oPage->addJsFile($this->getResourcePath().'js/themes/bcm_theme.js'); //common style for all charts
    }

    if(!empty($psId))
      $sChartId = $psId;
    else
      $sChartId = uniqid();

    $asSeries = array();
    foreach($this->casChartData as $vLabel => $vValue)
    {
        if(is_array($vValue))
        {
          foreach ($vValue as $sLabel => $sValue)
            $asSeriesb[] = $sLabel." : ".$sValue;

          $asSeries[] = '{'.implode(',',$asSeriesb).'}';
        }
        else
        {
          $asSeries[] = "['".$vLabel."', ".$vValue."]";
        }
      }

      $sJavascript = "
       var ".$sChartId."; // globally available
       $(document).ready(function() {
            ".$sChartId."= new Highcharts.Chart({
            credits: {
              enabled: false
            },
            chart: {
                renderTo: '".$sChartId."',
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false
            },
            title: {
                text: '".$this->csChartTitle."'
            },
            tooltip: {";

      if(!isset($this->casChartRender['tooltip']))
        $sJavascript.= "pointFormat: '{series.name}: <b>{point.y} JPY</b>',	percentageDecimals: 1 ";
      else
      {
        switch($this->casChartRender['tooltip'])
        {
          case  'value':    $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.point.y, 0,'',',') +'¥'; } "; break;
          case  'value_%':  $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.point.y, 0,'',',') +'¥' + '<br />'+this.percentage.toFixed(2) +' %'; } "; break;
          case  '%_value':  $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ this.percentage.toFixed(2) +' %' + '<br />' + Highcharts.numberFormat(this.point.y, 0,'',',') +'¥'; } "; break;
          case  'custom':   $sJavascript.= "formatter: ".$this->casChartRender['tooltip']; break;
          default:
            $sJavascript.= "pointFormat: '{series.name}: <b>{point.y} JPY</b>',	percentageDecimals: 1 ";
            break;
        }
      }

      $sJavascript.= "
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        color: '#000000',
                        connectorColor: '#000000',
                         ";

      if(!isset($this->casChartRender['plot']))
        $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ this.percentage.toFixed(2) +' %'; } ";
      else
      {
        switch($this->casChartRender['plot'])
        {
          case  'value':    $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.point.y, 0,'',',') +'¥'; } "; break;
          case  'value_%':  $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.point.y, 0,'',',') +'¥' + '<br />'+this.percentage.toFixed(2) +' %'; } "; break;
          case  '%_value':  $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ this.percentage.toFixed(2) +' %' + '<br />' + Highcharts.numberFormat(this.point.y, 0,'',',') +'¥'; } "; break;
          case  'custom':   $sJavascript.= "formatter: ".$this->casChartRender['plot']; break;
          default:
            $sJavascript.= "formatter: function(){ return '<b>'+ this.point.name +'</b>: '+ this.percentage.toFixed(2) +' %'; } ";
            break;
        }
      }

      $sJavascript.= "
                    }
                }
            },
            /*plotOptions:
            {
              series:
              {
                dataLabels:
                {
                    enabled: true,
                    formatter: function() {
                        return Math.round(this.percentage*100)/100 + ' %';
                    },
                    distance: -30,
                    color:'white'
                  }
                }
             },*/
           series: [{
                type: 'pie',
                name: '".$this->csChartTitle."',
                data: [".implode(',', $asSeries)." ]
           }]
        });
        });
      ";

    if(!$pbAjax)
      $oPage->addCustomJs($sJavascript);
    else
    {
      $sHTML.= '<script>function initChart(){ '.$sJavascript.' } </script>';
    }

    $sHTML.= $oHTML->getBlocStart($sChartId, array('style' => 'height:'.$this->csHeight.'; width:'.$this->csWidth.';'));
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  public function includeChartsJs($pbAddGroupPlugin = false, $pbAddFunnel = false)
  {
    $oPage = CDependency::getCpPage();


    //$oPage->addJsFile($this->getResourcePath().'js/highstock.js');

    //highcharts v4+ --> custom package inclucing selected modules
    $oPage->addJsFile($this->getResourcePath().'js/highcharts.js');

    $oPage->addJsFile($this->getResourcePath().'js/themes/bcm_theme.js'); //common style for all charts

    if($pbAddFunnel)
      $oPage->addJsFile($this->getResourcePath().'js/modules/funnel.js');

    if($pbAddGroupPlugin)
      $oPage->addJsFile($this->getResourcePath().'js/highcharts_group_category.js');

    return true;
  }

  private function _getPublicCharts()
  {
    $this->includeChartsJs();
    $oOpportunity = CDependency::getComponentByName('opportunity');

    $asStats = $oOpportunity->getMonthlyUsersStat();

    $this->createChart('column', '', 'Users');
    $this->setChartLegendPosition('horizontal', 0, -5);
    $this->setChartAxis($asStats['asAxis']);
    $this->setChartSize('100%','600px');
    $this->setChartData('On going', $asStats['ongoing']);
    $this->setChartData('Failed', $asStats['failed']);
    $this->setChartData('Signed', $asStats['signed']);
    $this->setChartData('Projected', $asStats['projected']);
    $sHTML = $this->getChartDisplay();

    return $sHTML;
  }

  private function _allowedIP()
  {
    $avAllowedIp = array('183.77.248.83','127.0.0.1');

    return (in_array($_SERVER["REMOTE_ADDR"],$avAllowedIp));
  }

}
