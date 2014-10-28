<?php
require_once('component/form/fields/field.class.php5');

class CSection extends CField
{
  private $casOptionData = array();
  private $cbVisible = true;

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    if(empty($psFieldName))
      $psFieldName = uniqid('formSection_');

    parent::__construct($psFieldName, $pasFieldParams);

    $this->casOptionData = $pasFieldParams;

    if(isset($this->casOptionData['hidden']) && !empty($this->casOptionData['hidden']))
      $this->cbVisible = false;

    if(isset($this->casOptionData['folded']) && !empty($this->casOptionData['folded']))
      $this->cbVisible = false;
  }

  public function addOption($pasFieldParams)
  {
    $this->casOptionData = $pasFieldParams;
    return $this;
  }

  public function isVisible()
  {
    return true;
  }

  public function getDisplay()
  {
    if($this->isSectionStart())
    {

      set_array($this->casOptionData['class'], 'formSection', ' formSection');
      if(!$this->cbVisible)
        $this->casOptionData['class'].= ' hidden';

      if(!isset($this->casOptionData['id']))
        $this->casOptionData['id'] = $this->csFieldName;

      //$sHTML = '<div class="floatHack"></div><div id="'.$this->csFieldName.'" ';
      $sHTML = '<div id="'.$this->casOptionData['id'].'" ';
      foreach($this->casOptionData as $sKey => $vValue)
        $sHTML.= ' '.$sKey.'="'.$vValue.'" ';

     $sHTML.= '>';
    }

    if($this->isSectionEnd())
      $sHTML = '<div class="floatHack"></div></div>';

    return $sHTML;
  }

  public function isSectionStart()
  {
    if(isset($this->casOptionData['type']) && $this->casOptionData['type'] == 'open')
      return true;
  }

  public function isSectionEnd()
  {
    if(isset($this->casOptionData['type']) && $this->casOptionData['type'] == 'close')
     return true;
  }

  public function isSectionVisible()
  {
    return $this->cbVisible;
  }

}