<?php

require_once('common/lib/model.class.php5');

class CSl_candidateModel extends CModel
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

    $this->_tableMap['sl_candidate']['sl_candidatepk'] = array('controls' => array('is_key(%) || is_null(%)'));
    $this->_tableMap['sl_candidate']['date_created'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_candidate']['created_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['statusfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['sex'] = array('controls'=> array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['firstname'] =  array('controls'=> array('!empty(%)'));
    $this->_tableMap['sl_candidate']['lastname'] =  array('controls'=> array('!empty(%)'));
    $this->_tableMap['sl_candidate']['nationalityfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['languagefk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['locationfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['play_date'] = array('controls'=>array('empty(%) || is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_candidate']['play_for'] = array('controls'=>array('is_null(%) || is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['rating'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['sl_candidate']['date_birth'] = array('controls'=>array('(% == "NULL") || is_date(%)'),'type'=>'date');
    $this->_tableMap['sl_candidate']['is_birth_estimation'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['is_client'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['cpa'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['mba'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_ag'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_ap'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_am'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_mp'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_in'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_ex'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_fx'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_ch'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_ed'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_pl'] = array('controls'=>array(),'type'=>'int');
    $this->_tableMap['sl_candidate']['skill_e'] = array('controls'=>array(),'type'=>'int');
    //sys fields
    $this->_tableMap['sl_candidate']['_sys_status'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate']['_sys_redirect'] = array('controls'=>array('is_key(%) || is_null(%)'),'type'=>'int');


    $this->_tableMap['sl_candidate_profile']['sl_candidate_profilepk'] = array('controls'=>array('is_key(%)'),'type'=>'int','index' => 'pk');
    $this->_tableMap['sl_candidate_profile']['candidatefk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['companyfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['date_created'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_candidate_profile']['created_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['date_updated'] = array('controls'=>array('is_datetime(%)'),'type'=>'datetime');
    $this->_tableMap['sl_candidate_profile']['updated_by'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['managerfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['industryfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['occupationfk'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['title'] = array('controls' => array());
    $this->_tableMap['sl_candidate_profile']['department'] = array('controls' => array());
    $this->_tableMap['sl_candidate_profile']['keyword'] = array('controls' => array());
    $this->_tableMap['sl_candidate_profile']['grade'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['salary'] = array('controls'=>array('is_numeric(%) '),'type'=>'float');
    $this->_tableMap['sl_candidate_profile']['bonus'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['sl_candidate_profile']['profile_rating'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['sl_candidate_profile']['currency'] = array('controls' => array());
    $this->_tableMap['sl_candidate_profile']['currency_rate'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['sl_candidate_profile']['salary_search'] = array('controls'=>array('is_integer(%)'),'type'=>'int');
    $this->_tableMap['sl_candidate_profile']['target_low'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');
    $this->_tableMap['sl_candidate_profile']['target_high'] = array('controls'=>array('is_numeric(%)'),'type'=>'float');

    $this->_tableMap['sl_candidate_profile']['_has_doc'] = array('controls'=>array());
    $this->_tableMap['sl_candidate_profile']['_in_play'] = array('controls'=>array());
    $this->_tableMap['sl_candidate_profile']['_pos_status'] = array('controls'=>array());
    $this->_tableMap['sl_candidate_profile']['uid'] = array('controls'=>array('!empty(%)'));
    $this->_tableMap['sl_candidate_profile']['_date_updated'] = array('controls'=>array());



    $this->_tableMap['sl_candidate_rm']['sl_candidate_rmpk'] = array('controls' => array('is_key(%) || is_null(%)'));
    $this->_tableMap['sl_candidate_rm']['loginfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_candidate_rm']['candidatefk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_candidate_rm']['date_started'] = array('controls' => array('is_datetime(%)'));
    $this->_tableMap['sl_candidate_rm']['date_ended'] = array('controls' => array('is_datetime(%)'));
    $this->_tableMap['sl_candidate_rm']['date_expired'] = array('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['sl_candidate_rm']['nb_extended'] = array('controls' => array());




    $this->_tableMap['sl_company_rss']['sl_company_rsspk'] = array('controls' => array('is_key(%) || is_null(%)'));
    $this->_tableMap['sl_company_rss']['companyfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_company_rss']['date_created'] = array('controls' => array('is_datetime(%)'));
    $this->_tableMap['sl_company_rss']['nb_news'] = array('controls' => array());
    $this->_tableMap['sl_company_rss']['url'] = array('controls' => array());
    $this->_tableMap['sl_company_rss']['content'] = array('controls' => array());


    $this->_tableMap['sl_contact']['sl_contactpk'] = array('controls' => array());
    $this->_tableMap['sl_contact']['type'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_contact']['item_type'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['sl_contact']['itemfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_contact']['date_create'] = array('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['sl_contact']['date_update'] = array('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['sl_contact']['loginfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_contact']['updated_by'] = array('controls' => array('is_null(%) || is_key(%)'));
    $this->_tableMap['sl_contact']['value'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['sl_contact']['description'] = array('controls' => array());
    $this->_tableMap['sl_contact']['visibility'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_contact']['groupfk'] = array('controls' => array('is_integer(%)'));


    $this->_tableMap['sl_contact_visibility']['sl_contact_visibilitypk'] = array('controls' => array());
    $this->_tableMap['sl_contact_visibility']['sl_contactfk'] = array('controls' => array('is_integer(%)'));
    $this->_tableMap['sl_contact_visibility']['loginfk'] = array('controls' => array('is_integer(%)'));

    $this->_tableMap['sl_meeting']['sl_meetingpk'] = array('controls' => array());
    $this->_tableMap['sl_meeting']['date_created'] = array('controls' => array('is_datetime(%)'));
    $this->_tableMap['sl_meeting']['date_updated'] = array('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['sl_meeting']['date_meeting'] = array('controls' => array('is_datetime(%)'));
    $this->_tableMap['sl_meeting']['created_by'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_meeting']['candidatefk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_meeting']['attendeefk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_meeting']['type'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_meeting']['location'] = array('controls' => array());
    $this->_tableMap['sl_meeting']['description'] = array('controls' => array());
    $this->_tableMap['sl_meeting']['date_reminder1'] = array('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['sl_meeting']['date_reminder2'] = array('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['sl_meeting']['reminder_update'] = array('controls' => array('is_null(%) || is_datetime(%)'));
    $this->_tableMap['sl_meeting']['meeting_done'] = array('controls' => array('is_integer(%)'));
    $this->_tableMap['sl_meeting']['date_met'] = array('controls' => array());


    $this->_tableMap['sl_company']['sl_companypk'] = array('controls' => array());
    $this->_tableMap['sl_company']['date_created'] = array('controls' => array());
    $this->_tableMap['sl_company']['created_by'] = array('controls' => array());
    $this->_tableMap['sl_company']['date_updated'] = array('controls' => array());
    $this->_tableMap['sl_company']['updated_by'] = array('controls' => array());

    $this->_tableMap['sl_company']['level'] = array('controls' => array());
    $this->_tableMap['sl_company']['name'] = array('controls' => array());
    $this->_tableMap['sl_company']['corporate_name'] = array('controls' => array());
    $this->_tableMap['sl_company']['description'] = array('controls' => array());
    $this->_tableMap['sl_company']['address'] = array('controls' => array());
    $this->_tableMap['sl_company']['phone'] = array('controls' => array());
    $this->_tableMap['sl_company']['fax'] = array('controls' => array());
    $this->_tableMap['sl_company']['email'] = array('controls' => array());
    $this->_tableMap['sl_company']['website'] = array('controls' => array());

    $this->_tableMap['sl_company']['is_client'] = array('controls' => array());
    $this->_tableMap['sl_company']['is_nc_ok'] = array('controls' => array());
    $this->_tableMap['sl_company']['num_employee'] = array('controls' => array());

    $this->_tableMap['sl_company']['num_employee_japan'] = array('controls' => array());
    $this->_tableMap['sl_company']['num_employee_world'] = array('controls' => array());
    $this->_tableMap['sl_company']['num_branch_japan'] = array('controls' => array());
    $this->_tableMap['sl_company']['num_branch_world'] = array('controls' => array());
    $this->_tableMap['sl_company']['revenue'] = array('controls' => array());
    $this->_tableMap['sl_company']['hq'] = array('controls' => array());
    $this->_tableMap['sl_company']['hq_japan'] = array('controls' => array());


    $this->_tableMap['sl_attribute']['sl_attributepk'] = array('controls' => array());
    $this->_tableMap['sl_attribute']['type'] = array('controls' => array('!empty(%)'));
    $this->_tableMap['sl_attribute']['itemfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_attribute']['attributefk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_attribute']['loginfk'] = array('controls' => array('is_key(%)'));
    $this->_tableMap['sl_attribute']['date_created'] = array('controls' => array('is_datetime(%)'));


    return true;
  }
}