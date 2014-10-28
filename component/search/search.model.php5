<?php

require_once('common/lib/model.class.php5');

class CSearchModel extends CModel
{
  public function __construct()
  {
    $this->oDB = CDependency::getComponentByName('database');
    return true;
  }

  protected function _initMap()
  {
  }
}
