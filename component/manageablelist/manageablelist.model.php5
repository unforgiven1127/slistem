<?php

require_once('common/lib/model.class.php5');

class CManageablelistModel extends CModel
{
  public function __construct()
  {
    parent::__construct();
    return $this->_initMap();
  }

  protected function _initMap()
  {
    $this->_tableMap['manageable_list'] =
            array(
                'manageable_listpk'=>array('controls'=>array('is_integer(%)'),'type'=>'int','index' => 'pk'),
                'shortname'=>array('controls' => array('!empty(%)')),
                'cp_uid'=>array('controls' => array()),
                'cp_action'=>array('controls' => array()),
                'cp_type'=>array('controls' => array()),
                'cp_pk'=>array('controls'=>array(),'type'=>'int','index' => 'pk'),
                'label'=>array('controls' => array('!empty(%)')),
                'description'=>array('controls' => array()),
                'item_type'=>array('controls' => array())
                );

    $this->_tableMap['manageable_list_item'] =
            array(
                'manageable_list_itempk'=>array('controls'=>array('is_key(%)'),'type'=>'int','index' => 'pk'),
                'manageable_listfk'=>array('controls'=>array('is_integer(%)'),'type'=>'int'),
                'label'=>array('controls' => array()),
                'value'=>array('controls' => array())
                );

    return true;
  }

}
