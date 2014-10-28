<?php

require_once('common/lib/model.class.php5');

class CSl_statModel extends CModel
{
  public function __construct()
  {
    parent::__construct();
    return $this->_initMap();
  }

  protected function _initMap()
  {
    // create table in DB then use the script in admin section to generate field map from database:
    // admin >> system settings >> cron & urls >> map database fields

    //$this->_tableMap['sl_stat']['xxxxxxxx'] = array();


    return true;
  }
}