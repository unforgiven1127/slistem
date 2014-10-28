<?php

require_once('common/lib/model.class.php5');

class CNotificationModel extends CModel
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

    $this->_tableMap['notification']['notificationpk'] = array ('controls' => array('is_key(%) || is_null(%)'));
    $this->_tableMap['notification']['date_created'] = array ('controls' => array('is_datetime(%)'));
    $this->_tableMap['notification']['creatorfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['notification']['date_notification'] = array ('controls' => array('is_datetime(%)'));
    $this->_tableMap['notification']['title'] = array ('controls' => array());
    $this->_tableMap['notification']['content'] = array ('controls' => array());
    $this->_tableMap['notification']['message'] = array ('controls' => array());
    $this->_tableMap['notification']['title'] = array ('controls' => array());
    $this->_tableMap['notification']['message_format'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['notification']['type'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['notification']['delivered'] = array ('controls' => array('is_integer(%)'));


    $this->_tableMap['notification_link']['notification_linkpk'] = array ('controls' => array('is_key(%) || is_null(%)'));
    $this->_tableMap['notification_link']['notificationfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['notification_link']['linked_to'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['notification_link']['cp_uid'] = array ('controls' => array());
    $this->_tableMap['notification_link']['cp_action'] = array ('controls' => array());
    $this->_tableMap['notification_link']['cp_type'] = array ('controls' => array());
    $this->_tableMap['notification_link']['cp_pk'] = array ('controls' => array('is_integer(%)'));

    $this->_tableMap['notification_action']['notification_actionpk'] = array ('controls' => array('is_key(%) || is_null(%)'));
    $this->_tableMap['notification_action']['notificationfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['notification_action']['type'] = array ('controls' => array('!empty(%)'));
    $this->_tableMap['notification_action']['naggy'] = array ('controls' => array('is_integer(%)'));
    $this->_tableMap['notification_action']['naggy_frequency'] = array ('controls' => array());
    $this->_tableMap['notification_action']['naggy_confirmed'] = array ('controls' => array('is_integer(%) || is_null(%)'));
    $this->_tableMap['notification_action']['number_sent'] = array ('controls' => array('is_integer(%)'));
    $this->_tableMap['notification_action']['date_last_action'] = array ('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['notification_action']['status'] = array ('controls' => array('is_integer(%)'));


    $this->_tableMap['notification_recipient']['notification_recipientpk'] = array ('controls' => array('is_key(%) || is_null(%)'));
    $this->_tableMap['notification_recipient']['notificationfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['notification_recipient']['loginfk'] = array ('controls' => array('is_key(%)'));
    $this->_tableMap['notification_recipient']['email'] = array ('controls' => array('isValidEmail(%)'));

    return true;
  }
}