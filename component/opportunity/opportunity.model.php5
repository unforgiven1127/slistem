<?php

require_once('common/lib/model.class.php5');

class COpportunityModel extends CModel
{
  public function __construct()
  {
    parent::__construct();
    return $this->_initMap();
  }

  protected function _initMap()
  {
    $this->_tableMap['opportunity']['opportunitypk'] = array ('controls' => array ('is_null(%) || is_numeric(%)'));
    $this->_tableMap['opportunity']['loginfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['opportunity']['title'] = array ('controls' => array ('!empty(%)'));
    $this->_tableMap['opportunity']['description'] = array ('controls' => array ());
    $this->_tableMap['opportunity']['date_added'] = array ('controls' => array ());
    $this->_tableMap['opportunity']['probability'] = array ('controls' => array ('is_int(%)'));
    $this->_tableMap['opportunity']['status'] = array ('controls' => array ('is_string(%)'));
    $this->_tableMap['opportunity']['date_update'] = array ('controls' => array ());


    $this->_tableMap['opportunity_detail']['opportunity_detailpk'] = array ('controls' => array ('is_null(%) || is_numeric(%)'));
    $this->_tableMap['opportunity_detail']['opportunityfk'] = array ('controls' => array('is_numeric(%)'));
    $this->_tableMap['opportunity_detail']['product'] = array ('controls' => array ('!empty(%)', 'is_string(%)'));
    $this->_tableMap['opportunity_detail']['month'] = array ('controls' => array ('is_date(%)'));
    $this->_tableMap['opportunity_detail']['amount'] = array ('controls' => array ('!empty(%)', 'is_integer(%)'));
    $this->_tableMap['opportunity_detail']['delivered'] = array ('controls' => array ('is_integer(%)'));
    $this->_tableMap['opportunity_detail']['invoiced'] = array ('controls' => array ('is_integer(%)'));
    $this->_tableMap['opportunity_detail']['paid'] = array ('controls' => array ('is_integer(%)'));
    $this->_tableMap['opportunity_detail']['booked'] = array ('controls' => array ('is_integer(%)'));


    $this->_tableMap['opportunity_link']['opportunity_linkpk'] = array('controls' => array('is_numeric(%)'));
    $this->_tableMap['opportunity_link']['opportunityfk'] = array('controls' => array('is_numeric(%)'));
    $this->_tableMap['opportunity_link']['cp_uid'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['opportunity_link']['cp_action'] = array('controls' => array());
    $this->_tableMap['opportunity_link']['cp_type'] = array('controls' => array());
    $this->_tableMap['opportunity_link']['cp_pk'] = array('controls' => array('is_integer(%)'));
    $this->_tableMap['opportunity_link']['cp_params'] = array('controls' => array());


    $this->_tableMap['opportunity_history']['opportunity_historypk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['opportunity_history']['opportunityfk'] = array('controls' => array('is_numeric(%)'));
    $this->_tableMap['opportunity_history']['date_added'] = array('controls' => array('isValidDate(%)'));
    $this->_tableMap['opportunity_history']['userfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['opportunity_history']['action'] = array('controls' => array());
    $this->_tableMap['opportunity_history']['comment'] = array('controls' => array());


    return true;
  }

}
