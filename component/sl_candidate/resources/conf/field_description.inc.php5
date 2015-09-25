<?php
/*
    'field_name':  db fieldname or not if sql_fielddefined
     ['display']['type']: type of data used by search form
     ['display']['group']: fields will be displayed grouped. Optional
    */


  $oCandidate = CDependency::getComponentByUid('555-001');
  $oSlateVars = $oCandidate->getVars();

  $oSearch = CDependency::getComponentByName('search');
  $asYesNo = array(array('label' => 'Yes', 'value' => 1), array('label' => 'No', 'value' => 0));


  $sURLAllUser = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1));
  $sToday = date('Y-m-d');


  $oLogin = CDependency::getCpLogin();
  if($oLogin->isAdmin())
  {
    $asFields[CONST_CANDIDATE_TYPE_CANDI]['dba_delete'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'DBA :: Deleted candidates',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('numeric'),
        'default_operator' => 'equal',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldInteger'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan._sys_status',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );
  }


  $asFields[CONST_CANDIDATE_TYPE_CANDI]['grade'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Grade',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('numeric'),
        'default_operator' => 'superior',
        'option' => array(array('label' => 'No grade', 'value' => 0), array('label' => 'Met', 'value' => 1), array('label' => 'Low notable', 'value' => 2),
          array('label' => 'High notable', 'value' => 3), array('label' => 'Top shelf', 'value' => 4)),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scpr.grade',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


  $sIndustryURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_INDUSTRY);
  $asFields[CONST_CANDIDATE_TYPE_CANDI]['industry'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sIndustryURL),
        'label' => 'Industry (list)',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('autocomplete'),
        'default_operator' => 'contains',
        'option' => array($this->_getTreeData('industry')),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,                                     //not multiple, all value concatenated with comma when posted
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => null,
        'join' => array(
            array('type' => 'left', 'table' => 'sl_industry', 'alias' => 'sind', 'clause' => 'sind.sl_industrypk = scpr.industryfk', 'select' => 'sind.label as industry', 'where' => '')),
        'fts' => false,
        'unmanageable' => ' (sind.sl_industrypk <YYY> (XXX) <logic> sind.parentfk <YYY> (XXX)) '
      )
    );


  $sOccupationURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_OCCUPATION);
  $asFields[CONST_CANDIDATE_TYPE_CANDI]['occupation'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sOccupationURL),
        'label' => 'Occupation',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('autocomplete'),
        'default_operator' => 'contains',
        'option' => array($this->_getTreeData('occupation')),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,                                     //not multiple, all value concatenated with comma when posted
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => '',
        'join' => array(array('type' => 'inner', 'table' => 'sl_occupation', 'alias' => 'socc', 'clause' => 'socc.sl_occupationpk = scpr.occupationfk', 'select' => 'socc.label as occupation', 'where' => '')),
        'fts' => false,
        'unmanageable' => ' (socc.sl_occupationpk <YYY> (XXX) <logic> socc.parentfk <YYY> (XXX)) '
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['all_occupation'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sOccupationURL),
        'label' => 'Occupation [main & secondary]',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('autocomplete+'),
        'default_operator' => 'contains',
        'option' => array($this->_getTreeData('occupation')),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,                                     //not multiple, all value concatenated with comma when posted
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => '',
        'join' => array(
           array('type' => 'inner', 'table' => 'sl_occupation', 'alias' => 'socc', 'clause' => 'socc.sl_occupationpk = scpr.occupationfk', 'select' => 'socc.label as occupation', 'where' => ''),
           array('type' => 'left', 'table' => 'sl_attribute', 'alias' => 'satt', 'clause' => 'satt.`type` = "candi_occu" AND satt.itemfk = scan.sl_candidatepk ', 'select' => '', 'where' => '')
        ),
        'fts' => false,
        'unmanageable' => ' (socc.sl_occupationpk <YYY> (XXX) <logic> socc.parentfk <YYY> (XXX) <logic> satt.attributefk <YYY> (XXX)) '
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_CANDI]['status'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Status - candidate',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('status'),
        'default_operator' => 'equal',
        'option' => array(array('label' => ' - ', 'value' => ''), array('label' => 'name collect', 'value' => 1),
                          array('label' => 'contacted', 'value' => 2), array('label' => 'interview set', 'value' => 3),
                          array('label' => 'pre-screened', 'value' => 4), array('label' => 'phone assessed', 'value' => 5),
                          array('label' => 'Assessed in person', 'value' => 6)/*, array('label' => '7', 'value' => 7),
                          array('label' => '8', 'value' => 8), array('label' => '9', 'value' => 9)*/),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.statusfk',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );



   $oPosition = CDependency::getComponentByName('sl_position');
   $asList = $oPosition->getStatusList(false, true);

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['play_status'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Status - activity',
        'group' => 'Pipeline activity',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => $asList,
        'value' => array(),
        'default_value' => array(5),
        'multiple' => 5,
        'param' => array(),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_intList'
      ),
      'sql' => array
      (
        'field' => 'spli.status',
        'join' => array(
            array('type' => 'inner', 'table' => 'sl_position_link', 'alias' => 'spli',
             'clause' => 'spli.candidatefk = scan.sl_candidatepk ', 'select' => 'spli.status as inplay_status', 'where' => '')
            ),
        'fts' => false,
        'unmanageable' => null
      )
    );


   $asFields[CONST_CANDIDATE_TYPE_CANDI]['location'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Location',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => $oSlateVars->getLocationItem(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 1,
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => 'scan.locationfk',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );







   // 6 above are the default in the form
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -














  $asFields[CONST_CANDIDATE_TYPE_CANDI]['sex'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'sex',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('egality'),
        'default_operator' => '=',
        'option' =>  array(array('label' => 'Female', 'value' => 2), array('label' => 'Male', 'value' => 1)),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositiove'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.sex',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['sex'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'sex',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('egality'),
        'default_operator' => '=',
        'option' =>  array(array('label' => 'Female', 'value' => 2), array('label' => 'Male', 'value' => 1)),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositiove'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.sex',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


  //describes how to display the field
  /*$asFields[CONST_CANDIDATE_TYPE_CANDI]['age'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'text'),
        'label' => 'Age',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('numeric'),
        'default_operator' => 'superior',
        'option' => array(),
        'value' => array(),
        'default_value' => array(30),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => ' (DATE_FORMAT(NOW(),"%Y") - YEAR(scan.date_birth)) ',
        'join' => array(),
        'fts' => false,
        'unmanageable' => null
      )
    );*/

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['age'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', ''),
        'label' => 'Age',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('range'),
        'default_operator' => 'between',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => array('range' => '1', 'min' => 14, 'max' => 80, 'value_min' => 25, 'value_max' => 45, 'suffix' => 'yrs', 'display_value' => 1),
        'js_control' => 'jsFieldTypeIntegerRange'
      ),
      'data' => array
      (
        'type' => 'rangeInteger',
        'control' => 'rangeInteger',
        'field' => ''
      ),
      'sql' => array
      (
        'field' => '',
        'join' => array(),
        'fts' => false,
        'unmanageable' => '((DATE_FORMAT(NOW(),"%Y") - YEAR(scan.date_birth)) >= XXX
          AND (DATE_FORMAT(NOW(),"%Y") - YEAR(scan.date_birth)) <= ZZZ ) ',
        'multiplier' => 1
      )
    );



  //describes how to display the field
  $asFields[CONST_CANDIDATE_TYPE_CANDI]['birth_date'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Birth date',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'superior',
        'option' => array(),
        'value' => array(),
        'default_value' => array(date('Y-m-d', strtotime('-30 years'))),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldDate'
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => 'is_date'
      ),
      'sql' => array
      (
        'field' => 'scan.date_birth',
        'join' => array(),
        'fts' => false,
        'unmanageable' => null
      )
    );












  $asFields[CONST_CANDIDATE_TYPE_CANDI]['lastname'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'text'),
        'label' => 'Candidate lastname',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contains',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scan.lastname',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['firstname'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'text'),
        'label' => 'Candidate firstname',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contains',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scan.firstname',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );






  $asFields[CONST_CANDIDATE_TYPE_CANDI]['date_created'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Date created',
        'group' => 'misc',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'superior',
        'option' => array(),
        'value' => array(),
        'default_value' =>array(date('Y-m-d')),
        'multiple' => null,
        'param' => array('range' => '1'),
        'js_control' => 'jsFieldTypeDate'
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => 'is_date',
        'field' => 'scan.date_created',
      ),
      'sql' => array
      (
        'field' => 'scan.date_created',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  //dump($oSlateVars->getLocationItem());




    //dump($oSlateVars->getLanguageItem());

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['language'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Language',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => $oSlateVars->getLanguageItem(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 1,
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => 'scan.languagefk',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );
    $asFields[CONST_CANDIDATE_TYPE_CANDI]['all_language'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Language [main & secondary]',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('select+'),
        'default_operator' => 'in',
        'option' => $oSlateVars->getLanguageItem(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 1,
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => 'scan.languagefk',
        'join' => array(
          array('type' => 'left', 'table' => 'sl_attribute', 'alias' => 'satt', 'clause' => 'satt.`type` = "candi_lang" AND satt.itemfk = scan.sl_candidatepk ', 'select' => '', 'where' => '')
          ),
        'fts' => false,
        'unmanageable' => ' ( (scan.languagefk <<scan.languagefk>>) OR (satt.attributefk <<satt.attributefk>>) ) '
      )
    );
     $asFields[CONST_CANDIDATE_TYPE_CANDI]['sum_language'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Language (multilingual only)',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('select_all'),
        'default_operator' => 'in',
        'option' => $oSlateVars->getLanguageItem(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 1,
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => 'scan.languagefk',
        'join' => array(
          array('type' => 'left', 'table' => 'sl_attribute', 'alias' => 'satt', 'clause' => 'satt.`type` = "candi_lang" AND satt.itemfk = scan.sl_candidatepk ', 'select' => '', 'where' => '')
          ),
        'fts' => false,
        'unmanageable' => ' ( (scan.languagefk <<scan.languagefk>>) AND (satt.attributefk <<satt.attributefk>>) ) '
      )
    );

   //dump($oSlateVars->getNationalityItem());
   $asFields[CONST_CANDIDATE_TYPE_CANDI]['nationality'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Nationality',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => $oSlateVars->getNationalityItem(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 1,
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => 'scan.nationalityfk',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );








  $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_COMP);
  $asFields[CONST_CANDIDATE_TYPE_CANDI]['company'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'Company selector',
        'group' => 'Company',
        'operator' => $oSearch->getFieldOperators('in'),
        'default_operator' => 'equals',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 5,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scpr.companyfk',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['company_name'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Company name',
        'group' => 'Company',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'start',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 5,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scom.name',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['company_prev'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Previous company ',
        'group' => 'Company',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'start',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 5,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'even.content',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scan.sl_candidatepk AND elin.cp_uid = "555-001" AND elin.cp_type = "candi" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => 'even.eventpk = elin.eventfk AND even.type = "cp_hidden" ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['department'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Department',
        'group' => 'Company',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'contains',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scpr.department',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


  $asFields[CONST_CANDIDATE_TYPE_CANDI]['title'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Title',
        'group' => 'Company',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'contains',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scpr.title',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['resume'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Resume',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contain',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'dfil.original',    //original [fts_equalt] or compressed [contains] based on operator
        'join' =>
        array(
            array('type' => 'inner', 'table' => 'document_link', 'alias' => 'dlin',
             'clause' => 'dlin.cp_type = "candi" AND dlin.cp_uid = "555-001" AND dlin.cp_action = "ppav" AND dlin.cp_pk = scan.sl_candidatepk',
                'select' => '', 'where' => ''),
            array('type' => 'inner', 'table' => 'document_file', 'alias' => 'dfil',
             'clause' => 'dfil.documentfk = dlin.documentfk', 'select' => '', 'where' => '')
            ),
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_CANDI]['candidatepk'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'refId',
        'group' => 'misc',
        'operator' => $oSearch->getFieldOperators('egality'),
        'default_operator' => '=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.sl_candidatepk',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['is_client'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'is client',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('egality'),
        'default_operator' => '=',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.is_client',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['is_collaborator'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'is collaborator',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('egality'),
        'default_operator' => '=',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.is_collaborator',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


  //---------------------------------------------------
  //COntact


  $asFields[CONST_CANDIDATE_TYPE_CANDI]['contact_creator'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURLAllUser),
        'label' => 'Contact - created by',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('in'),
        'default_operator' => 'in',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 10,
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.loginfk',
         'join' => array(array('type' => 'inner', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['contact_date'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Contact - date added',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'superior',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 10,
        'param' => array('range' => 1),
        'js_control' => 'jsFieldTypeDate'
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => 'is_date'
      ),
      'sql' => array
      (
        'field' => 'scon.date_create',
         'join' => array(array('type' => 'inner', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['email'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Email',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'inner', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 5 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );


  $asFields[CONST_CANDIDATE_TYPE_CANDI]['fax'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Fax',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 4 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['phone'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Phone (home)',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 1 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['mobile'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Phone mobile',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 6 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['office'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Phone office',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 2 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['website'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Website',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 3 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['facebook'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Facebook',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 7 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['linkedin'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'LinkedIn',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type = 8 ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['all_contact'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'All contact types',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );
    $asFields[CONST_CANDIDATE_TYPE_CANDI]['all_phone'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'All phone',
        'group' => 'contact',
        'operator' => $oSearch->getFieldOperators('text'),
        'default_operator' => 'equal',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scon.value',
         'join' => array(array('type' => 'left', 'table' => 'sl_contact', 'alias' => 'scon',
             'clause' => 'scon.item_type = "candi" AND itemfk = scan.sl_candidatepk AND scon.type IN (1,2,4,6)', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );




    $asFields[CONST_CANDIDATE_TYPE_CANDI]['char_note'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Character note',
        'group' => 'note & resume',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contain',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'text',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'even.content',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scan.sl_candidatepk AND elin.cp_uid = "555-001" AND elin.cp_type = "candi" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => 'even.eventpk = elin.eventfk AND even.type = "character" ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['note'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Notes',
        'group' => 'note & resume',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contain',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'fts',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'even.content',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scan.sl_candidatepk AND elin.cp_uid = "555-001" AND elin.cp_type = "candi" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => 'even.eventpk = elin.eventfk AND even.type <> "character" ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_CANDI]['all_note'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'All note types',
        'group' => 'note & resume',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contain',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'fts',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'even.content',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scan.sl_candidatepk AND elin.cp_uid = "555-001" AND elin.cp_type = "candi" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => ' even.eventpk = elin.eventfk AND even.type NOT IN (\'cp_hidden\', \'cp_history\') ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['note_creator'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURLAllUser),
        'label' => 'Note created by',
        'group' => 'note & resume',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 10,
        'param' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'even.created_by',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scan.sl_candidatepk AND elin.cp_uid = "555-001" AND elin.cp_type = "candi" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => 'even.eventpk = elin.eventfk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['note_created_on'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Note created on',
        'group' => 'note & resume',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'superior',
        'option' => array(),
        'value' => array(),
        'default_value' => array($sToday),
        'multiple' => null,
        'param' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => 'is_date'
      ),
      'sql' => array
      (
        'field' => 'even.date_create',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scan.sl_candidatepk AND elin.cp_uid = "555-001" AND elin.cp_type = "candi" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => 'even.eventpk = elin.eventfk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_CANDI]['resume_all'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Resume (all text documents)',
        'group' => 'note & resume',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contain',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'fts',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'dfil.original',
         'join' => array(
             array('type' => 'inner', 'table' => 'document_link', 'alias' => 'dlin',
             'clause' => 'dlin.cp_pk = scan.sl_candidatepk AND dlin.cp_uid = "555-001" AND dlin.cp_type = "candi" AND dlin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'document_file', 'alias' => 'dfil',
             'clause' => ' dfil.documentfk = dlin.documentfk ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );



    $asFields[CONST_CANDIDATE_TYPE_CANDI]['mba'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'MBA',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('is'),
        'default_operator' => 'contain',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scan.mba',
         'join' => array(),
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_CANDI]['cpa'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'CPA',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('is'),
        'default_operator' => 'contain',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scan.cpa',
         'join' => array(),
        'fts' => false,
        'unmanageable' => null
      )
    );








  //---------------------------------------------------
  //Misc

  $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1));
  $asFields[CONST_CANDIDATE_TYPE_CANDI]['creator'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'Created by',
        'group' => 'misc',
        'operator' => $oSearch->getFieldOperators('in'),
        'default_operator' => 'in',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 6,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scan.created_by',
         'join' => array(),
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_CANDI]['in_play'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'In play',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('is'),
        'default_operator' => 'equal',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scpr._in_play',
        'join' => array(),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['has_doc'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Has a resume',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('is'),
        'default_operator' => 'equal',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scpr._has_doc',
        'join' => array(),
        'fts' => false,
        'unmanageable' => null
      )
    );


    $sURL = $this->_oPage->getAjaxUrl('sl_folder', CONST_ACTION_SEARCH, CONST_FOLDER_TYPE_FOLDER, 0, array('selector' => 1));
    $asFields[CONST_CANDIDATE_TYPE_CANDI]['folder'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'From a folder',
        'group' => 'misc',
        'operator' => $oSearch->getFieldOperators('in'),
        'default_operator' => 'in',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => 6,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'params' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'fite.parentfolderfk',
        'join' => array(array('type' => 'left', 'table' => 'folder_item', 'alias' => 'fite',
             'clause' => 'fite.itemfk = scan.sl_candidatepk', 'select' => '', 'where' => ''))
        ,
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_CANDI]['date_met'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Date met',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'between',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => array('range' => '1'),
        'js_control' => 'jsFieldTypeDateRange'
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => '',
        'field' => 'smee.date_met'    //required for range value
      ),
      'sql' => array
      (
        'field' => 'smee.date_met',
        'join' => array(array('type' => 'inner', 'table' => 'sl_meeting', 'alias' => 'smee',
             'clause' => 'smee.candidatefk = scan.sl_candidatepk ', 'select' => '', 'where' => ''))
        ,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['candidate_met'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Met candidates',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('egality'),
        'default_operator' => 'equal',
        'option' => $asYesNo,
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldTypeInteger'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_int',
      ),
      'sql' => array
      (
        'field' => 'smee.meeting_done',
        'join' => array(array('type' => 'inner', 'table' => 'sl_meeting', 'alias' => 'smee',
             'clause' => 'smee.candidatefk = scan.sl_candidatepk', 'select' => '', 'where' => ''))
        ,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['date_meeting'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Date meeting',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'between',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => array('range' => '1'),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => '',
        'field' => 'smee.date_meeting'    //required for range value
      ),
      'sql' => array
      (
        'field' => 'smee.date_meeting',
        'join' => array(array('type' => 'inner', 'table' => 'sl_meeting', 'alias' => 'smee',
             'clause' => 'smee.candidatefk = scan.sl_candidatepk', 'select' => '', 'where' => ''))
        ,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1));
    $asFields[CONST_CANDIDATE_TYPE_CANDI]['meeting_set_by'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'Meeting set by',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('autocomplete'),
        'default_operator' => 'in',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 5,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listofInt',
      ),
      'sql' => array
      (
        'field' => 'smee.created_by',
        'join' => array(array('type' => 'inner', 'table' => 'sl_meeting', 'alias' => 'smee',
             'clause' => 'smee.candidatefk = scan.sl_candidatepk', 'select' => '', 'where' => ''))
        ,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1));
    $asFields[CONST_CANDIDATE_TYPE_CANDI]['meeting_set_for'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'Meeting set for',
        'group' => 'status',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 5,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listofInt',
      ),
      'sql' => array
      (
        'field' => 'smee.attendeefk',
        'join' => array(array('type' => 'inner', 'table' => 'sl_meeting', 'alias' => 'smee',
             'clause' => 'smee.candidatefk = scan.sl_candidatepk', 'select' => '', 'where' => ''))
        ,
        'fts' => false,
        'unmanageable' => null
      )
    );







  /* ******************************************************************************* */
  /* ******************************************************************************* */
  /* ******************************************************************************* */

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_ag'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'AG',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_ag',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_fx'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'FX',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_fx',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_ap'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'AP',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_ap',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_ap'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'AP',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_ap',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


  $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_ch'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'CH',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_ch',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );



   $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_am'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'AM',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_am',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_ed'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'ED',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_ed',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_mp'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'MP',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_mp',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_pl'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'PL',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_pl',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


   $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_in'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'IN',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_in',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

  $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_e'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'E',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_e',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['skill_ex'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', 'numeric'),
        'label' => 'EX',
        'group' => 'personal_skill',
        'operator' => $oSearch->getFieldOperators('skill'),
        'default_operator' => '>=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array('min' => 1, 'max' => 9, 'legend' => array(1, 2, 3 ,4, 5, 6, 7, 8 ,9)),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scan.skill_ex',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


   $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1));
   $asFields[CONST_CANDIDATE_TYPE_CANDI]['play_by'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'Currently in play - by',
        'group' => 'Pipeline activity',
        'operator' => $oSearch->getFieldOperators('is'),
        'default_operator' => '=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array(),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'spli.created_by',
        'join' => array(
            array('type' => 'inner', 'table' => 'sl_position_link', 'alias' => 'spli',
             'clause' => 'spli.candidatefk = scan.sl_candidatepk AND spli.in_play = 1 AND spli.active = 1 ', 'select' => 'spli.status as inplay_status', 'where' => '')
            ),
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['play_by_history'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'History - in play by',
        'group' => 'Pipeline activity',
        'operator' => $oSearch->getFieldOperators('is'),
        'default_operator' => '=',
        'option' => array(),
        'value' => array(),
        'default_value' => array(5),
        'multiple' => null,
        'param' => array(),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'spli.created_by',
        'join' => array(
            array('type' => 'inner', 'table' => 'sl_position_link', 'alias' => 'spli',
             'clause' => 'spli.candidatefk = scan.sl_candidatepk AND spli.in_play = 1 AND spli.active = 0 ', 'select' => 'spli.status as inplay_status', 'where' => '')
            ),
        'fts' => false,
        'unmanageable' => null
      )
    );




   $asList = $oPosition->getStatusList(false, true, true);
   $asFields[CONST_CANDIDATE_TYPE_CANDI]['play_status_active'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Current pipeline - status',
        'group' => 'Pipeline activity',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => '=',
        'option' => $asList,
        'value' => array(),
        'default_value' => array(5),
        'multiple' => 5,
        'param' => array(),
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listofInt'
      ),
      'sql' => array
      (
        'field' => 'spli.status',
        'join' => array(
            array('type' => 'inner', 'table' => 'sl_position_link', 'alias' => 'spli',
             'clause' => 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1', 'select' => 'spli.status as inplay_status', 'where' => '')
            ),
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['play_expires'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Active - expire date',
        'group' => 'Pipeline activity',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'superior',
        'option' => $asList,
        'value' => array(),
        'default_value' => array(date('Y-m-d')),
        'multiple' => null,
        'param' => array('range' => '1'),
        'js_control' => 'jsFieldTypeDateRange'
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => 'is_date',
        'field' => 'spli.date_expire'
      ),
      'sql' => array
      (
        'field' => 'spli.date_expire',
        'join' => array(
            array('type' => 'inner', 'table' => 'sl_position_link', 'alias' => 'spli',
             'clause' => 'spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.status < 101', 'select' => 'spli.status as inplay_status', 'where' => '')
            ),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['salary'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('slider', ''),
        'label' => 'Salary',
        'group' => 'company',
        'operator' => $oSearch->getFieldOperators('range'),
        'default_operator' => 'between',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => array('range' => '1', 'min' => 0, 'max' => 100, 'value_min' => 5, 'value_max' => 25, 'suffix' => 'M&yen;', 'display_value' => 1),
        'js_control' => 'jsFieldTypeIntegerRange'
      ),
      'data' => array
      (
        'type' => 'rangeInteger',
        'control' => 'rangeInteger',
        'field' => 'scpr.salary'
      ),
      'sql' => array
      (
        'field' => 'scpr.salary',
        'join' => array(),
        'fts' => false,
        'unmanageable' => '( (scpr.salary + scpr.bonus) >= XXX AND (scpr.salary + scpr.bonus) <= ZZZ ) ',
        'multiplier' => 1000000
      )
    );










/* **************************************************************************** */
/* **************************************************************************** */
/* **************************************************************************** */
/* **************************************************************************** */
/* **************************************************************************** */
/* **************************************************************************** */
//Company field



   $asFields[CONST_CANDIDATE_TYPE_COMP]['name'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'text'),
        'label' => 'Company name',
        'group' => 'company_data',
        'operator' => $oSearch->getFieldOperators('string'),
        'default_operator' => 'contains',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => '',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'lastname',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


   $asFields[CONST_CANDIDATE_TYPE_COMP]['level'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Level',
        'group' => 'company_data',
        'operator' => $oSearch->getFieldOperators('egality'),
        'default_operator' => 'equal',
        'option' => array(array('label' => 'A', 'value' => 1), array('label' => 'B', 'value' => 2),
                          array('label' => 'C', 'value' => 3)),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => null,
        'js_control' => 'jsFieldIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'scom.level',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


   $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_INDUSTRY);
   $asFields[CONST_CANDIDATE_TYPE_COMP]['industry'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'Industry (list)',
        'group' => 'company_data',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => array($this->_getTreeData('industry')),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,                                     //not multiple, all value concatenated with comma when posted
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listofInt',
      ),
      'sql' => array
      (
        'field' => 'sind.sl_industrypk',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

   $asFields[CONST_CANDIDATE_TYPE_CANDI]['sec_industry'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sIndustryURL),
        'label' => 'Industry [secondary only] ',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('autocomplete+'),
        'default_operator' => 'contains',
        'option' => array($this->_getTreeData('industry')),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,                                     //not multiple, all value concatenated with comma when posted
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => 'satt.attributefk',
        'join' => array(

            array('type' => 'inner', 'table' => 'sl_attribute', 'alias' => 'satt', 'clause' => 'satt.`type` = "candi_indus" AND satt.itemfk = scan.sl_candidatepk ', 'select' => '', 'where' => ''),
            ),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_CANDI]['all_industry'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sIndustryURL),
        'label' => 'Industry [main & secondary] ',
        'group' => 'personal_data',
        'operator' => $oSearch->getFieldOperators('autocomplete+'),
        'default_operator' => 'contains',
        'option' => array($this->_getTreeData('industry')),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,                                     //not multiple, all value concatenated with comma when posted
        'nbresult' => 20,
        'param' => null,
        'js_control' => 'jsFieldListOfIntegerPositive'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => 'is_listOfInt'
      ),
      'sql' => array
      (
        'field' => null,
        'join' => array(
            array('type' => 'left', 'table' => 'sl_industry', 'alias' => 'sind', 'clause' => 'sind.sl_industrypk = scpr.industryfk', 'select' => 'sind.label as industry', 'where' => ''),
            array('type' => 'left', 'table' => 'sl_attribute', 'alias' => 'satt', 'clause' => 'satt.`type` = "candi_indus" AND satt.itemfk = scan.sl_candidatepk ', 'select' => '', 'where' => '')
            ),
        'fts' => false,
        'unmanageable' => ' (sind.sl_industrypk <YYY> (XXX) <logic> sind.parentfk IN<YYY> (XXX) <logic> satt.attributefk <YYY> (XXX) ) '
      )
    );



    $asFields[CONST_CANDIDATE_TYPE_COMP]['date_created'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Date created',
        'group' => 'company_data',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'superior',
        'option' => array(),
        'value' => array(),
        'default_value' =>array(date('Y-m-d')),
        'multiple' => null,
        'param' => array('range' => '1'),
        'js_control' => 'jsFieldTypeDate'
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => 'is_date',
        'field' => 'scom.date_created',
      ),
      'sql' => array
      (
        'field' => 'scom.date_created',
        'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );

    $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1));
    $asFields[CONST_CANDIDATE_TYPE_COMP]['creator'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURL),
        'label' => 'Created by',
        'group' => 'company_data',
        'operator' => $oSearch->getFieldOperators('in'),
        'default_operator' => 'in',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 6,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'scom.created_by',
         'join' => null,
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_COMP]['all_note'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', ''),
        'label' => 'Notes',
        'group' => 'note & description',
        'operator' => $oSearch->getFieldOperators('fts'),
        'default_operator' => 'contain',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        //'param' => array('onchange' => 'alert(\'gaaa\');'),
        'param' => array(),
        'js_control' => 'jsFieldMinSize@2'
      ),
      'data' => array
      (
        'type' => 'fts',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'even.content',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scom.sl_companypk AND elin.cp_uid = "555-001" AND elin.cp_type = "comp" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => ' even.eventpk = elin.eventfk ', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_COMP]['note_creator'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('selector', $sURLAllUser),
        'label' => 'Note created by',
        'group' => 'note & description',
        'operator' => $oSearch->getFieldOperators('select'),
        'default_operator' => 'in',
        'option' => array(),
        'value' => array(),
        'default_value' => array(),
        'multiple' => 10,
        'param' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'intList',
        'control' => ''
      ),
      'sql' => array
      (
        'field' => 'even.created_by',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scom.sl_companypk AND elin.cp_uid = "555-001" AND elin.cp_type = "comp" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => 'even.eventpk = elin.eventfk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );

    $asFields[CONST_CANDIDATE_TYPE_COMP]['note_created_on'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('input', 'date'),
        'label' => 'Note created on',
        'group' => 'note & description',
        'operator' => $oSearch->getFieldOperators('date'),
        'default_operator' => 'superior',
        'option' => array(),
        'value' => array(),
        'default_value' => array($sToday),
        'multiple' => null,
        'param' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'date',
        'control' => 'is_date'
      ),
      'sql' => array
      (
        'field' => 'even.date_create',
         'join' => array(
             array('type' => 'inner', 'table' => 'event_link', 'alias' => 'elin',
             'clause' => 'elin.cp_pk = scom.sl_companypk AND elin.cp_uid = "555-001" AND elin.cp_type = "comp" AND elin.cp_action = "ppav" ', 'select' => '', 'where' => ''),
             array('type' => 'inner', 'table' => 'event', 'alias' => 'even',
             'clause' => 'even.eventpk = elin.eventfk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );


    $asFields[CONST_CANDIDATE_TYPE_COMP]['has_open_pos'] = array(
      'display' => array
      (
        'fts_type'=> null,
        'type' => array('select', ''),
        'label' => 'Has open position',
        'group' => 'position',
        'operator' => $oSearch->getFieldOperators('is'),
        'default_operator' => 'equal',
        'option' => array(array('label' => 'open', 'value' => '1'), array('label' => 'closed', 'value' => '0')),
        'value' => array(),
        'default_value' => array(),
        'multiple' => null,
        'param' => array(),
        'js_control' => ''
      ),
      'data' => array
      (
        'type' => 'int',
        'control' => 'is_integer'
      ),
      'sql' => array
      (
        'field' => 'spos.status',
         'join' => array(
             array('type' => 'inner', 'table' => 'sl_position', 'alias' => 'spos',
             'clause' => 'spos.companyfk = scom.sl_companypk', 'select' => '', 'where' => '')),
        'fts' => false,
        'unmanageable' => null
      )
    );