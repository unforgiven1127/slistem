<?php

require_once('common/lib/model.class.php5');

class CSl_positionModel extends CModel
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

    $this->_tableMap['sl_position']['sl_positionpk'] = array('controls'=>array('is_null(%) || is_key(%)'),'type'=>'int','index' => 'pk');
    $this->_tableMap['sl_position']['companyfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position']['date_created'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_position']['created_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position']['status'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position']['industryfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position']['age_from'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position']['age_to'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position']['salary_from'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['sl_position']['salary_to'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['sl_position']['lvl_japanese'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position']['lvl_english'] = array('controls'=>array('is_integer(%)'),'type'=>'int');

    $this->_tableMap['sl_position_detail']['sl_position_detailpk'] = array('controls'=>array('is_null(%) || is_key(%)'),'type'=>'int','index' => 'pk');
    $this->_tableMap['sl_position_detail']['positionfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_detail']['date_created'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_position_detail']['created_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_detail']['language'] = array();
    $this->_tableMap['sl_position_detail']['title'] = array();
    $this->_tableMap['sl_position_detail']['career_level'] = array();
    $this->_tableMap['sl_position_detail']['description'] = array();
    $this->_tableMap['sl_position_detail']['requirements'] = array();
    $this->_tableMap['sl_position_detail']['responsabilities'] = array();
    $this->_tableMap['sl_position_detail']['content_html'] = array();
    $this->_tableMap['sl_position_detail']['is_public'] = array('controls'=>array('is_integer(%)'),'type'=>'int');


    $this->_tableMap['sl_position_link']['sl_position_linkpk'] = array('controls'=>array('is_null(%) || is_key(%)'),'type'=>'int','index' => 'pk');
    $this->_tableMap['sl_position_link']['positionfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_link']['candidatefk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_link']['date_created'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_position_link']['created_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_link']['status'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_link']['in_play'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_link']['comment'] = array();
    $this->_tableMap['sl_position_link']['date_expire'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_position_link']['active'] = array('controls'=>array('is_integer(%)'),'type'=>'int');

    $this->_tableMap['sl_position_credit']['sl_position_creditpk'] = array('controls'=>array('is_null(%) || is_key(%)'),'type'=>'int','index' => 'pk');
    $this->_tableMap['sl_position_credit']['positionfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_credit']['candidatefk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_credit']['loginfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_position_credit']['date_created'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_position_credit']['created_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');


    $this->_tableMap['revenue']['id'] = array('controls'=>array('is_null(%) || is_key(%)'),'type'=>'int','index' => '');
    $this->_tableMap['revenue']['date_created'] = array('controls'=>array(),'type'=>'date');
    $this->_tableMap['revenue']['position'] = array('controls'=>array());
    $this->_tableMap['revenue']['candidate'] = array('controls'=>array());
    $this->_tableMap['revenue']['closed_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['revenue']['date_signed'] = array('controls'=>array(),'type'=>'date');
    $this->_tableMap['revenue']['date_start'] = array('controls'=>array(),'type'=>'date');
    $this->_tableMap['revenue']['date_paid'] = array('controls'=>array(),'type'=>'date');
    $this->_tableMap['revenue']['date_due'] = array('controls'=>array(),'type'=>'date');
    $this->_tableMap['revenue']['salary'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['revenue']['salary_rate'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['revenue']['refund_amount'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['revenue']['amount'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['revenue']['comment'] = array();
    $this->_tableMap['revenue']['currency'] = array();
    $this->_tableMap['revenue']['status'] = array();
    $this->_tableMap['revenue']['location'] =  array('controls'=>array('!empty(%)'), 'type'=>'text');

    $this->_tableMap['revenue_member']['id'] = array('controls'=>array('is_null(%) || is_key(%)'),'type'=>'int','index' => '');
    $this->_tableMap['revenue_member']['revenue_id'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['revenue_member']['loginpk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['revenue_member']['user_position'] = array();
    $this->_tableMap['revenue_member']['split_amount'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['revenue_member']['percentage'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');

    return true;
  }
}