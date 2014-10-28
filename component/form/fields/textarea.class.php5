<?php

require_once('component/form/fields/field.class.php5');

class CTextarea extends CField
{
  protected $cbIsTinymce;
  protected $cbAllowTinymce;

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);

    if(isset($pasFieldParams['isTinymce']) && $pasFieldParams['isTinymce'])
      $this->cbIsTinymce = true;
    else
      $this->cbIsTinymce = false;

    if(isset($pasFieldParams['allowTinymce']) && $pasFieldParams['allowTinymce'])
      $this->cbAllowTinymce = true;
    else
      $this->cbAllowTinymce = false;
  }


  public function getDisplay()
  {

    //--------------------------------
    //fetching field parameters

    if(!isset($this->casFieldParams['id']))
      $this->casFieldParams['id'] = $this->csFieldName.'Id';

    //------------------------
    //add JScontrol classes
    if(isset($this->casFieldParams['required']) && !empty($this->casFieldParams['required']))
    {
       $this->casFieldContol['jsFieldNotEmpty'] = '';
       set_array($this->casFieldParams['class'], 'formFieldRequired', ' formFieldRequired');
    }
    elseif(isset($this->casFieldContol['jsFieldNotEmpty']))
    {
      set_array($this->casFieldParams['class'], 'formFieldRequired', ' formFieldRequired');
    }


    if(isset($this->casFieldParams['label']))
    {
      $sLabel = $this->casFieldParams['label'];
      unset($this->casFieldParams['label']);
    }
    else
      $sLabel = '';


    if(isset($this->casFieldParams['value']))
    {
      $sValue = $this->casFieldParams['value'];
      unset($this->casFieldParams['value']);
    }
    else
      $sValue = '';

    //--------------------------------

    $sHTML = '';


    if(!empty($sLabel) && $this->isVisible())
      $sHTML.= '<div class="formLabel">'.$sLabel.'</div>';


    $sHTML.= '<div class="formField">';

    if($this->cbIsTinymce)
    {
      //$sHTML.= '<script>initMce("'.$this->csFieldName.'"); </script>';
      $sHTML.= '<script>initMce("'.$this->casFieldParams['id'].'"); </script>';

      if(isset($this->casFieldParams['class']))
        $this->casFieldParams['class'].= ' tinymce hidden ';
      else
        $this->casFieldParams['class'] = ' tinymce hidden ';
    }




    $sHTML.= '<textarea name="'.$this->csFieldName.'" ';

    foreach($this->casFieldParams as $sKey => $vValue)
      $sHTML.= ' '.$sKey.'="'.$vValue.'" ';

    if(!empty($this->casFieldContol))
    {
      $sHTML.= ' jsControl="';
      foreach($this->casFieldContol as $sKey => $vValue)
        $sHTML.= $sKey.'@'.$vValue.'|';

      $sHTML.= '" ';
    }

    $sHTML.= ' >'.$sValue.'</textarea>';


    if($this->cbAllowTinymce)
    {
      $sHTML.= '<a href="javascript:;" class="toggleMce" onclick=" initMce(\''.$this->casFieldParams['id'].'\'); $(this).hide(0);">adv. mode</a>';
    }

    $sHTML.= '</div>';

    return $sHTML;
  }

}