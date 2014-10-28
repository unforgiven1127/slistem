<?php
/**
* Parent class for all templates and sub templates
*/
class CTplConf
{
  protected $casParams = array();

  public function addParam($psParamName, $pvValue)
  {
    $this->casParams[$psParamName] = $pvValue;

    return true;
  }

  public function getParam($psParamName)
  {
    return $this->casParams[$psParamName];
  }
}
?>
