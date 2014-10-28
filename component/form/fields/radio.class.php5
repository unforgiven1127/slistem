<?php

require_once('component/form/fields/field.class.php5');

class CRadio extends CField
{
  private $casOptionData = array();

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);

    $this->casOptionData[] = $pasFieldParams;
  }

  public function addOption($pasFieldParams)
  {
    $this->casOptionData[] = $pasFieldParams;
    return $this;
  }


  public function getDisplay()
  {
    $sHTML = '';

    foreach($this->casOptionData as $nKey => $asOption)
    {
      if(isset($asOption['label']))
      {
        $sLabel = $asOption['label'];
        unset($asOption['label']);
      }
      else
        $sLabel = '';

      if(isset($asOption['id']))
      {
        $sId = $asOption['id'];
        unset($asOption['id']);
      }
      else
        $sId = $this->csFieldName.'_'.$nKey.'_'.'Id';

      set_array($asOption['legend'], '');

      if(!$this->isVisible())
        $sClass = ' hidden ';
      else
        $sClass = '';

      //set a label before first radio button
      if(empty($sHTML) && isset($asOption['textbefore']))
      {
        $sHTML.=' <div class="formLabel '.$sClass.'">';

        if(!empty($asOption['legend']))
          $sHTML.='<label for="'.$sId.'" >'.$asOption['legend'].'</label>';
        else
          $sHTML.='&nbsp;';

        $sHTML.='</div>';
      }


      $sHTML.= ' <div class="formField '.$sClass.'"><input type="radio" id="'.$sId.'" name = "'.$this->csFieldName.'"';

      foreach($asOption as $sKey => $vValue)
        $sHTML.= ' '.$sKey.'="'.$vValue.'" ';

      $sHTML.= '/>';

      if(isset($asOption['legend']))
        $sHTML.='<label for="'.$sId.'" >'.$sLabel.'</label>&nbsp;&nbsp;';

      $sHTML.= '</div>';
    }


    return $sHTML;
  }

}