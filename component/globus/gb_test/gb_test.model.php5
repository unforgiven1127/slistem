<?php

require_once('common/lib/model.class.php5');

class CGbTestModel extends CModel
{

  public function __construct()
  {
    parent::__construct();
    $this->_initMap();
    return true;
  }

  public $aCorrectionStatus = array('sent', 'draft', 'read');
  public $aCommentTypes = array(
      'tone' => 'TONE',
      'logic' => 'LOGIC',
      'phrases' => 'PHRASES',
      'language' => 'LANGUAGE',
      'layout' => 'LAYOUT'
  );

  protected function _initMap()
  {
    $this->_tableMap['gbtest']['gbtestpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest']['rank'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest']['name'] = array ('controls' => array ());
    $this->_tableMap['gbtest']['content'] = array ('controls' => array ());
    $this->_tableMap['gbtest']['gbtest_chapterfk'] = array ('controls' => array ('is_key(%)'));

    $this->_tableMap['gbtest_answer']['gbtest_answerpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_answer']['gbtestfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_answer']['mail_title'] = array ('controls' => array ());
    $this->_tableMap['gbtest_answer']['mail_title_html'] = array ('controls' => array ());
    $this->_tableMap['gbtest_answer']['mail_content'] = array ('controls' => array ());
    $this->_tableMap['gbtest_answer']['mail_content_html'] = array ('controls' => array ());
    $this->_tableMap['gbtest_answer']['gbuserfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_answer']['status'] = array ('controls' => array ('is_string(%)'));
    $this->_tableMap['gbtest_answer']['date_create'] = array ('controls' => array ());
    $this->_tableMap['gbtest_answer']['date_submitted'] = array ('controls' => array ());
    $this->_tableMap['gbtest_answer']['last_update'] = array ('controls' => array ());
    $this->_tableMap['gbtest_answer']['date_returned'] = array ('controls' => array ());

    $this->_tableMap['gbtest_chapter']['gbtest_chapterpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_chapter']['rank'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_chapter']['name'] = array ('controls' => array ());

    $this->_tableMap['gbtest_chapter_group']['gbtest_chapter_grouppk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_chapter_group']['gbtest_chapterfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_chapter_group']['gbuser_groupfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_chapter_group']['deadline'] = array ('controls' => array ());

    $this->_tableMap['gbtest_correction']['gbtest_correctionpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_correction']['gbtest_answerfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_correction']['corrected_by'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_correction']['date_send'] = array ('controls' => array ());
    $this->_tableMap['gbtest_correction']['date_create'] = array ('controls' => array ());
    $this->_tableMap['gbtest_correction']['good'] = array ('controls' => array ('is_numeric(%)'));
    $this->_tableMap['gbtest_correction']['status'] = array ('controls' => array ('in_array(%, $this->aCorrectionStatus)'));

    $this->_tableMap['gbtest_correction_point']['gbtest_correction_pointpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_correction_point']['gbtest_correctionfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_correction_point']['comment'] = array ('controls' => array ('!empty(%)'));
    $this->_tableMap['gbtest_correction_point']['importance'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_correction_point']['type'] = array ('controls' => array ('in_array(%, array_keys($this->aCommentTypes))'));
    $this->_tableMap['gbtest_correction_point']['start'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_correction_point']['end'] = array ('controls' => array ('is_key(%)'));

    $this->_tableMap['gbtest_esa_score']['gbtest_esa_scorepk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_esa_score']['gbtest_answerfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_esa_score']['corrected_by'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_esa_score']['date_send'] = array ('controls' => array ());
    $this->_tableMap['gbtest_esa_score']['date_create'] = array ('controls' => array ());
    $this->_tableMap['gbtest_esa_score']['status'] = array ('controls' => array ('in_array(%, $this->aCorrectionStatus)'));
    $this->_tableMap['gbtest_esa_score']['tone'] = array ('controls' => array ('is_numeric(%)'));
    $this->_tableMap['gbtest_esa_score']['phrases'] = array ('controls' => array ('is_numeric(%)'));
    $this->_tableMap['gbtest_esa_score']['language'] = array ('controls' => array ('is_numeric(%)'));
    $this->_tableMap['gbtest_esa_score']['logic'] = array ('controls' => array ('is_numeric(%)'));
    $this->_tableMap['gbtest_esa_score']['layout'] = array ('controls' => array ('is_numeric(%)'));
    $this->_tableMap['gbtest_esa_score']['speed'] = array ('controls' => array ('is_numeric(%)'));

    $this->_tableMap['gbtest_esa_score_detail']['gbtest_esa_score_detailpk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_esa_score_detail']['gbtest_esa_scorefk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_esa_score_detail']['gbtest_esa_skillfk'] = array ('controls' => array ('is_key(%)'));
    $this->_tableMap['gbtest_esa_score_detail']['score'] = array ('controls' => array ('is_numeric(%)'));

  }
}