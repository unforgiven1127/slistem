<?php
require_once('component/form/fields/field.class.php5');

class CItemSelector extends CField
{
  private $casOptionData = array();

  public function __construct($psFieldName, $pasFieldParams = array())
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
    if(!isset($this->casFieldParams['interface']) || empty($this->casFieldParams['interface']))
    {
      assert('false; //cp_item_selector fields need an "interface" parameter ');
      return '';
    }

    $asAllItems = array();
    $sFirstUrl = '';
    foreach((array)$this->casFieldParams['interface'] as $sInterface)
    {
      if(!empty($sInterface))
      {
        $asComponent = CDependency::getComponentUidByInterface($sInterface);
        if(!empty($asComponent))
        {
          foreach($asComponent as $sUid)
          {
            $oComponent = CDependency::getComponentByUid($sUid);
            $asItem = $oComponent->getComponentPublicItems('notification_item');

            if(!empty($asItem))
            {
              $asAllItems = array_merge_recursive($asAllItems, $asItem);

              if(empty($sFirstUrl))
                $sFirstUrl = $asItem[0]['search_url'];
            }
          }
        }
      }
    }

    if(!assert('is_array($asAllItems) && !empty($asAllItems)'))
      return '';

    set_array($this->casFieldParams['value'], '');
    $asValue = explode('|@|', $this->casFieldParams['value']);
    if(count($asValue) == 5)
    {
      $sItemValue = $asValue[0].'|@|'.$asValue[1].'|@|'.$asValue[2];
      $sItemPk = $asValue[3];
      $sItemLabel = $asValue[4];
    }
    else
      $sItemValue = $sItemPk = $sItemLabel = '';


    set_array($this->casFieldParams['nbresult'], 1);
    $sHTML = '';

    if(!empty($this->casFieldParams['label']) && $this->isVisible())
      $sHTML.= '<div class="formLabel">'.$this->casFieldParams['label'].'</div>';

    $sHTML.= '<div class="formField cp_item_selector">';


    if(!isset($this->casFieldParams['id']) || empty($this->casFieldParams['id']))
      $this->casFieldParams['id'] = $this->csFieldName.'Id';

    $sJavascript = 'var sValue = $(\'> :selected\', this).attr(\'data-url\');
     initAutoComplete(\''.$this->casFieldParams['id'].'\', sValue); ';



    $sHTML.= '<select id="cp_item_'.$this->casFieldParams['id'].'" name="cp_item_'.$this->csFieldName.'" onchange="'.$sJavascript.'" class="cp_item_selector_select">';
    foreach($asAllItems as $asItemDetail)
    {
      $sValue = $asItemDetail[CONST_CP_UID].'|@|'.$asItemDetail[CONST_CP_ACTION].'|@|'.$asItemDetail[CONST_CP_TYPE];
      if($sItemValue == $sValue)
        $sHTML.= '<option data-url="'.$asItemDetail['search_url'].'" value="'.$sValue.'" selected="selected">'.$asItemDetail['label'].'</option>';
      else
        $sHTML.= '<option data-url="'.$asItemDetail['search_url'].'" value="'.$sValue.'">'.$asItemDetail['label'].'</option>';
    }
    $sHTML.= '</select>';


    $sHTML.= '<input type="text" id="'.$this->casFieldParams['id'].'" name="'.$this->csFieldName.'"  class="cp_item_selector_autocomplete"/>
      </div>';


    $sJavascript = 'function initAutoComplete(psFieldId, psUrl)
    {
      $("#"+psFieldId).parent().find(\'ul\').remove();
      $("#"+psFieldId).tokenInput(psUrl
      ,{
         onResult: function(oResult)
         {
           var oLast = $(oResult).last();
           if(oLast && oLast[0] && oLast[0].callback)
             eval(oLast[0].callback);

            return oResult;
         },
         onAdd: function(oItem)
         {
           console.log(oItem);
           if(oItem.id == "token_clear")
             $(this).tokenInput("clear");
         },
         tokenFormatter: function(item){ return "<li class=\''.$this->csFieldName.'_item\'><p>" + item.name + "</p></li>" },
         tokenLimit: "'.$this->casFieldParams['nbresult'].'" ';

      if(!empty($sItemPk))
      {
        $sJavascript.= ', prePopulate:[{id:"'.$sItemPk.'",name:"'.$sItemLabel.'"}]';
      }

    $sJavascript.= '});
    }
    initAutoComplete("'.$this->casFieldParams['id'].'", "'.$sFirstUrl.'"); ';


    //form removing the search div... retriggered by field_controls
    $sJavascript.= ' $("#'.$this->casFieldParams['id'].'").on("remove", function(){ $(".token-input-dropdown-mac").remove(); });   ';


    $sHTML.= '<script language="javascript">'.$sJavascript.'</script>';

    return $sHTML;
  }

  public function getPostedItemData($psFieldName)
  {
    $nItemPk = (int)getValue($psFieldName, 0);
    $sComponent = getValue('cp_item_'.$psFieldName, 0);
    $asComponent = explode('|@|', $sComponent);

    if(empty($sComponent) || count($asComponent) <> 3)
      return array();

    return array(CONST_CP_UID => $asComponent[0], CONST_CP_ACTION => $asComponent[1], CONST_CP_TYPE => $asComponent[2], CONST_CP_PK => $nItemPk);
  }
}