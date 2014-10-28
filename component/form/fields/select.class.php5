<?php
require_once('component/form/fields/field.class.php5');

class CSelect extends CField
{
  private $casOptionData = array();
  private $casOptionHtml = array();

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);
  }

  public function addOption($pasFieldParams, $pbSelected = false)
  {
    if(!assert('is_array($pasFieldParams)'))
      return null;

    if($pbSelected)
      $pasFieldParams['selected'] = 'selected';

    $this->casOptionData[] = $pasFieldParams;
    return $this;
  }

  public function addOptionHtml($psOptions)
  {
    if(!assert('!empty($psOptions)'))
      return null;

    $this->casOptionHtml[] = $psOptions;
    return $this;
  }

  public function getDisplay()
  {
    $sHTML = '';

    if(!isset($this->casFieldParams['id']))
    {
      $this->casFieldParams['id'] = str_replace('[', '', $this->csFieldName.'Id');
      $this->casFieldParams['id'] = str_replace(']', '', $this->casFieldParams['id']);
    }

    //------------------------
    //add JScontrol classes
    if(isset($this->casFieldParams['required']) && !empty($this->casFieldParams['required']))
      $this->casFieldContol['jsFieldNotEmpty'] = '';

    if(!empty($this->casFieldParams['label']) && $this->isVisible())
      $sHTML.= '<div class="formLabel">'.$this->casFieldParams['label'].'</div>';

    $sHTML.= '<div class="formField">';

    if(isset($this->casFieldParams['allNoneLink']) && !empty($this->casFieldParams['allNoneLink']))
    {
      $sHTML.= '<div style="float: right;"> select <a href="javascript:;" onclick="$(\'#'.$this->casFieldParams['id'].'\').children().prop(\'selected\', \'selected\').end().change();">all</a> ';
      $sHTML.= '/ <a href="javascript:;" onclick="$(\'#'.$this->casFieldParams['id'].'\').children().removeProp(\'selected\').end().change();">none</a></div>';
    }

    $sHTML.= '<select name="'.$this->csFieldName.'" ';


    if(!empty($this->casFieldContol))
    {
      $sHTML.= ' jsControl="';
      foreach($this->casFieldContol as $sKey => $vValue)
        $sHTML.= $sKey.'@'.$vValue.'|';

      $sHTML.= '" ';

      if(isset($this->casFieldContol[$this->csFieldRequired]))
      {
        set_array($this->casFieldParams['class'], 'formFieldRequired', ' formFieldRequired');
        set_array($this->casFieldParams['title'], ' Field required', ' Field required');
      }
    }


    $sExtraJs = '';
    $bIsMultiple = false;
    if(isset($this->casFieldParams['multiple']))
    {
      $bIsMultiple = true;
      $oPage = CDependency::getCpPage();
      $oPage->addJsFile(array('/component/form/resources/js/jquery.bsmselect.js', '/component/form/resources/js/jquery.bsmselect.sortable.js','/component/form/resources/js/jquery.bsmselect.compatibility.js'));
      $oPage->addCssFile('/component/form/resources/css/jquery.bsmselect.css');
      $sExtraJs = "<script> jQuery('#".$this->casFieldParams['id']."').bsmSelect(
              {
                animate: true,
                highlight: true,
                showEffect: function(jQueryel)
                {
                  var sText = jQueryel.text();
                  sText = sText.substr(0, sText.length-1).trim();

                  var oOriginal = jQuery('#".$this->casFieldParams['id']." option:contains('+sText+')');
                  if(oOriginal)
                    jQueryel.addClass(oOriginal.attr('class'));

                  jQueryel.fadeIn();
                },
                hideEffect: function(jQueryel){ jQueryel.fadeOut(function(){ jQuery(this).remove(); }); },";


      if(!empty($this->casFieldParams['class']))
        $sExtraJs .= " selectClass: '".$this->casFieldParams['class']."', ";

      if (in_array('sortable', $this->casFieldParams))
        $sExtraJs .= "plugins: [jQuery.bsmSelect.plugins.sortable()], ";


      $sExtraJs .= "highlight: 'highlight',   removeLabel: '<strong>X</strong>'   }).change(); </script>";

      $sHTML.= ' multiple="multiple" size="15" ';
      unset($this->casFieldParams['multiple']);
      unset($this->casFieldParams['title']);
    }


    if(!empty($this->casFieldParams['value']))
      $sExtraJs.= '<script>
        $(document).ready(function()
        {
          $(\'#'.$this->casFieldParams['id'].'\').val(\''.$this->casFieldParams['value'].'\').change();
        });
        </script>';


    foreach($this->casFieldParams as $sKey => $vValue)
    {
      $sHTML.= ' '.$sKey.'="'.$vValue.'" ';
    }


    $sHTML.= '>';


    //add all the options
    $asOptions = array();
    foreach($this->casOptionData as $asOption)
    {
      if(isset($asOption['label']))
      {
        $sLabel = $asOption['label'];
        unset($asOption['label']);
      }
      else
        $sLabel = '';

      if(isset($asOption['group']))
      {
        $sGroupOption = $asOption['group'];
        unset($asOption['group']);
      }
      else
        $sGroupOption = '';

      $sOptionHtml = '<option ';

      foreach($asOption as $sKey => $vValue)
        $sOptionHtml.= ' '.$sKey.'="'.$vValue.'" ';

      $sOptionHtml.= '>';

      if(!empty($sLabel))
      $sOptionHtml.= $sLabel;

      $sOptionHtml.= '</option>';
      $asOptions[$sGroupOption][] = $sOptionHtml;
    }

    $sCurrentGroup = '';
    foreach($asOptions as $sGroup => $asOption)
    {
      if($sCurrentGroup != $sGroup)
      {
        $sHTML.= '<OPTGROUP LABEL="'.$sGroup.'">';
      }

      $sHTML.= implode('', $asOption);
    }

    //
    if(!empty($this->casOptionHtml))
    {
      $sHTML.= implode('', $this->casOptionHtml);
    }


    $sHTML.= '</select>'.$sExtraJs;
    $sHTML.= '</div>';
    return $sHTML;
  }

}
