<?php
require_once('component/form/fields/field.class.php5');

class CAutocomplete extends CField
{
  private $casOptionData = array();

  public function __construct($psFieldName, $psFieldType = '', $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);
  }

  public function addOption($pasFieldParams)
  {
    if(!assert('is_array($pasFieldParams)'))
      return null;

    $this->casOptionData[] = $pasFieldParams;
    return $this;
  }

  public function getDisplay()
  {
    //------------------------------------
    //Form component manages country and city selectors, for any other element, components have to provide custom url.
    if(!isset($this->casFieldParams['url']) || empty($this->casFieldParams['url']))
    {
      assert('false; //selector fields need an "url" parameter ');
      return '';
    }

    if($this->casFieldParams['url'] == CONST_FORM_SELECTOR_URL_CITY)
    {
      $oForm = CDependency::getComponentByName('form');
      $this->casFieldParams['url'] = $oForm->getCitySelectorAjaxUrl();
      $this->casFieldParams['addurl'] = $oForm->getCitySelectorAddUrl();
    }
    elseif($this->casFieldParams['url'] == CONST_FORM_SELECTOR_URL_COUNTRY)
    {
      $oForm = CDependency::getComponentByName('form');
      $this->casFieldParams['url'] = $oForm->getCountrySelectorAjaxUrl();
      //$this->casFieldParams['addurl'] = $oForm->getCountrySelectorAddUrl();
    }

    //------------------------------------

    if(!isset($this->casFieldParams['id']))
      $this->casFieldParams['id'] = $this->csFieldName.'Id';

    $this->casFieldParams['id'] = str_replace(array('[', ']'), '', $this->casFieldParams['id']);

    if(!isset($this->casFieldParams['value']))
      $this->casFieldParams['value'] = '';

    if(!isset($this->casFieldParams['nbresult']))
      $this->casFieldParams['nbresult'] = '1';

    if(!isset($this->casFieldParams['class']))
      $this->casFieldParams['class'] = 'autocompleteField';
    else
      $this->casFieldParams['class'].= ' autocompleteField ';

    set_array($this->casFieldParams['nbresult'], 1);
    set_array($this->casFieldParams['onadd'], '');

    if(isset($this->casFieldParams['addurl']) && !empty($this->casFieldParams['addurl']))
    {
      $sAddExtraClass = ' formAutocompleteHasAddLink ';
      $sAddLink = '<a href="javascript:;" onclick="
        var oConf = goPopup.getConfig();
        oConf.width = 500;
        oConf.height = 475;
        goPopup.setLayerFromAjax(oConf, \''.$this->casFieldParams['addurl'].'\', true);"><img src="'.CONST_PICTURE_ADD.'"/></a>';
    }
    else
      $sAddExtraClass = $sAddLink = '';

    $sHTML = '';

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile('/component/form/resources/css/token-input-mac.css');

    if($this->cbFieldInAjax)
      $sJavascript = '$("#'.$this->casFieldParams['id'].'").tokenInput("'.$this->casFieldParams['url'].'"';
     else
       $sJavascript = '$(document).ready(function(){
         $("#'.$this->casFieldParams['id'].'").tokenInput("'.$this->casFieldParams['url'].'" ';

     $sJavascript.= '
       ,{
       noResultsText: "no results found",
       onResult: function(oResult)
       {
         if(oResult.length == 0)
           return [{id: "token_clear", name: "no result found"}]

         var oLast = $(oResult).last();
         if(oLast && oLast[0] && oLast[0].callback)
           eval(oLast[0].callback);

          return oResult;
       },
       onAdd: function(oItem)
       {
         // console.log(oItem);
         if(oItem.id == "token_clear")
           $(this).tokenInput("clear");

         '.$this->casFieldParams['onadd'].'
       },
       tokenFormatter: function(item)
       {
          if(item.label)
            return "<li class=\''.$this->csFieldName.'_item\' title=\'"+item.title+"\'><p>" + item.label + "</p></li>";
          else
            return "<li class=\''.$this->csFieldName.'_item\' title=\'"+item.title+"\'><p>" + item.name + "</p></li>";
       },

       tokenLimit: "'.$this->casFieldParams['nbresult'].'"';

    if(!empty($this->casOptionData))
    {
      //add all the options
      $asOptions = array();
      foreach($this->casOptionData as $asOption)
      {
        if(isset($asOption['label']) && isset($asOption['value']))
          $asOptions[] = '{id:"'.$asOption['value'].'",name:"'.$asOption['label'].'"}';
      }

      $sJavascript.= ', prePopulate:['.implode(',', $asOptions).']';
    }

    if($this->cbFieldInAjax)
      $sJavascript.= '});';
    else
      $sJavascript.= '}); });';

    //form removing the search div... retriggered by field_controls
    $sJavascript.= ' $("#'.$this->casFieldParams['id'].'").on("remove", function(){ $("#'.$this->casFieldParams['id'].' .token-input-dropdown-mac").remove(); }); ';


    $sHTML.= '<script language="javascript">'.$sJavascript.'</script>';

    //------------------------
    //add JScontrol classes
    if(isset($this->casFieldParams['required']) && !empty($this->casFieldParams['required']))
    {
      $sAddExtraClass.= ' formFieldRequired';
      $this->casFieldContol['jsFieldNotEmpty'] = '';
    }



    if(!empty($this->casFieldParams['label']) && $this->isVisible())
    {
      $sHTML.= '<div class="formLabel">'.$this->casFieldParams['label'].'</div>';
      unset($this->casFieldParams['label']);
    }

    $sHTML.= '<div class="formField formAutocompleteContainer '.$sAddExtraClass.'"><input type="text" name="'.$this->csFieldName.'" ';

    foreach($this->casFieldParams as $sKey => $vValue)
      $sHTML.= ' '.$sKey.'="'.$vValue.'" ';

    if(!empty($this->casFieldContol))
    {
      $sHTML.= ' jsControl="';
      foreach($this->casFieldContol as $sKey => $vValue)
        $sHTML.= $sKey.'@'.$vValue.'|';

      $sHTML.= '" ';
    }

    $sHTML.= ' />';
    $sHTML.= $sAddLink;
    $sHTML.= '</div>';

    return $sHTML;
  }
}