<?php

class CSlateVars
{
  private $coModel = null;
  private $login_obj = null;

  private $casCurrency = array();

  private $casActiveUsers = array();
  private $casAllUsers = array();


  private $casIndustry = array();
  private $casOccupation = array();
  private $casLocation = array();
  private $casNationality = array();
  private $casLanguage = array();

  //...

  public function __construct()
  {
    if(empty($this->coModel))
      $this->reloadVars();

    $gafCurrencyRate = array();
    require_once $_SERVER['DOCUMENT_ROOT'].'/component/sl_candidate/resources/currency/currency_list.inc.php5';

    if(empty($gafCurrencyRate))
      return false;

    $asAvailable = array('jpy' => 1, 'usd' => 1, 'php' => 1, 'eur' => 1, 'aud' => 1, 'hkd' => 1, 'cad' => 1);
    $gafCurrencyRate = array_intersect_key($gafCurrencyRate, $asAvailable);
    $this->casCurrency = $gafCurrencyRate;

    $this->login_obj = CDependency::getCpLogin();

    return true;
  }


  private function _getModel()
  {
    $oCandidate = CDependency::getComponentByName('sl_candidate');
    return  $oCandidate->getModel();
  }



  public function reloadVars($pbAll = false, $pasListName = array())
  {
    if(empty($pasListName))
      $bAllCommon = true;
    else
      $bAllCommon = false;

    if($pbAll)
      $bAllCommon = true;


    $oLogin = CDependency::getComponentByName('login');

    if($bAllCommon || isset($pasListName['active_users']))
    {
      $this->casActiveUsers = $oLogin->getUserList();
    }

    if($pbAll || isset($pasListName['all_users']))
    {
      $this->casAllUsers = $oLogin->getUserList(0, false, true);
    }

    if($bAllCommon || isset($pasListName['industry']))
      $this->getIndustryList(true);

    if($bAllCommon || isset($pasListName['occupation']))
      $this->getOccupationList(true);

    if($bAllCommon || isset($pasListName['location']))
      $this->getLocationList();

    if($bAllCommon || isset($pasListName['nationality']))
      $this->getNationalityList();

    if($bAllCommon || isset($pasListName['language']))
      $this->getLanguageList();

    if($bAllCommon || isset($pasListName['status']))
      $this->getCandidateStatusList();

    if($bAllCommon || isset($pasListName['grade']))
      $this->getCandidateGradeList();

    return true;
  }



  public function getCurrencies()
  {
    return $this->casCurrency;
  }



  public function getIndustryList($pbIncludeCategory = true, $pbIgnoreRights = true, $ignore_session = false)
  {
    if(!assert('is_bool($pbIncludeCategory)'))
      return array();

    $nIndex = (int)$pbIncludeCategory;

    if(isset($_SESSION['sl_industry_list'.$nIndex]) && !getValue('refresh_industry', false) && !$ignore_session)
      return $_SESSION['sl_industry_list'.$nIndex];

    $oDbResult = $this->_getModel()->getIndustry($pbIncludeCategory, $pbIgnoreRights);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asIndustry = array();
    while($bRead)
    {
      $asIndustry[(int)$oDbResult->getFieldValue('sl_industrypk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    $_SESSION['sl_industry_list'.$nIndex] = $asIndustry;
    return $asIndustry;
  }

  public function getOccupationList($pbIncludeCategory = true, $pbIgnoreRights = false)
  {
    if(!assert('is_bool($pbIncludeCategory)'))
      return array();

    $nIndex = (int)$pbIncludeCategory;

    if(isset($_SESSION['sl_occupation_list'.$nIndex]) && !getValue('refresh_occupation', false))
      return $_SESSION['sl_occupation_list'.$nIndex];

    $oDbResult = $this->_getModel()->getOccupation($pbIncludeCategory, $pbIgnoreRights);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asOccupation = array();
    while($bRead)
    {
      $asOccupation[(int)$oDbResult->getFieldValue('sl_occupationpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    $_SESSION['sl_occupation_list'.$nIndex] = $asOccupation;
    return $asOccupation;
  }



  public function getLocationList()
  {
    if(isset($_SESSION['sl_location_list']))
      return $_SESSION['sl_location_list'];

    $oDb = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM sl_location ORDER BY location ';
    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asLocation = array();
    while($bRead)
    {
      $asLocation[$oDbResult->getFieldValue('sl_locationpk')] = $oDbResult->getFieldValue('location');
      $bRead = $oDbResult->readNext();
    }

    $_SESSION['sl_location_list'] = $asLocation;
    return $asLocation;
  }
  public function getLocationOption($psValue = '')
  {
    $asList = $this->getLocationList();

    $sOption = '<option value=""> - </option>';
    foreach($asList as $sValue => $sLabel)
    {
      if($sValue == $psValue)
        $sOption.= '<option value="'.$sValue.'" selected="selected">'.$sLabel.'</option>';
      else
        $sOption.= '<option value="'.$sValue.'">'.$sLabel.'</option>';
    }

    return $sOption;
  }

  public function getLocationItem()
  {
    $asList = $this->getLocationList();

    $asOption = array();
    foreach($asList as $sValue => $sLabel)
    {
      $asOption[] = array('label' => $sLabel, 'value' => $sValue);
    }

    return $asOption;
  }




  public function getNationalityList()
  {
    if(isset($_SESSION['sl_nationality_list']))
      return $_SESSION['sl_nationality_list'];

    $oDb = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM sl_nationality ORDER BY nationality ';
    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asNationality = array();
    while($bRead)
    {
      $asNationality[$oDbResult->getFieldValue('sl_nationalitypk')] = $oDbResult->getFieldValue('nationality');
      $bRead = $oDbResult->readNext();
    }

    $_SESSION['sl_nationality_list'] = $asNationality;
    return $asNationality;
  }
  public function getNationalityOption($psValue = '')
  {
    $asList = $this->getNationalityList();

    $sOption = '<option value=""> - </option>';
    foreach($asList as $sValue => $sLabel)
    {
      if($sValue == $psValue)
        $sOption.= '<option value="'.$sValue.'" selected="selected">'.$sLabel.'</option>';
      else
        $sOption.= '<option value="'.$sValue.'">'.$sLabel.'</option>';
    }

    return $sOption;
  }

  public function getNationalityItem()
  {
    $asList = $this->getNationalityList();

    $asOption = array();
    foreach($asList as $sValue => $sLabel)
    {
      $asOption[] = array('label' => $sLabel, 'value' => $sValue);
    }

    return $asOption;
  }



  public function getLanguageList()
  {
    if(isset($_SESSION['sl_language_list']))
      return $_SESSION['sl_language_list'];


    $oDb = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM sl_language ORDER BY language ';
    $oDbResult = $oDb->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $asLanguage = array();
    while($bRead)
    {
      $asLanguage[$oDbResult->getFieldValue('sl_languagepk')] = $oDbResult->getFieldValue('language');
      $bRead = $oDbResult->readNext();
    }

    $_SESSION['sl_language_list'] = $asLanguage;
    return $asLanguage;
  }
  public function getLanguageOption($pvValue = '')
  {
    $asList = $this->getLanguageList();
    $pvValue = (array)$pvValue;

    $sOption = '<option value=""> - </option>';
    foreach($asList as $sValue => $sLabel)
    {
      if(in_array($sValue, $pvValue))
        $sOption.= '<option value="'.$sValue.'" selected="selected">'.$sLabel.'</option>';
      else
        $sOption.= '<option value="'.$sValue.'">'.$sLabel.'</option>';
    }

    return $sOption;
  }

  public function getLanguageItem()
  {
    $asList = $this->getLanguageList();

    $asOption = array();
    foreach($asList as $sValue => $sLabel)
    {
      $asOption[] = array('label' => $sLabel, 'value' => $sValue);
    }

    return $asOption;
  }



  public function getCandidateStatusList($pbAll = false)
  {
    if(isset($_SESSION['sl_candidate_status_list']))
      return $_SESSION['sl_candidate_status_list'];

   //NOT yet imported
    $asStatus = array();
    $asStatus[0] = ' - ';
    $asStatus[1] = 'Name collect';
    $asStatus[2] = 'Contacted';
    $asStatus[3] = 'Interview set';
    $asStatus[4] = 'Met'; //Pre-screened
    $asStatus[5] = 'Phone assessed';
    $asStatus[6] = 'Assessed in person';
    $asStatus[7] = 'Placed';
    $asStatus[8] = 'Lost';

    $asStatus[100] = 'Deleted';
    $asStatus[101] = 'Merged';

    $_SESSION['sl_candidate_status_list'] = $asStatus;
    return $asStatus;
  }

  public function getCandidateStatusOption($pnCurrentStatus = 0, $pbAll = false, $pbDisplayAll = false)
  {
    $asList = $this->getCandidateStatusList($pbDisplayAll);
    $sOption = '';

    if(empty($pnCurrentStatus))
      $nMaxStatus = 3;
    else
      $nMaxStatus = 6;


    foreach($asList as $nValue => $sLabel)
    {
      if($pbAll || $nValue <= $nMaxStatus)
      {
        if($nValue == $pnCurrentStatus)
          $sOption.= '<option value="'.$nValue.'" selected="selected">'.$sLabel.'</option>';
        else
          $sOption.= '<option value="'.$nValue.'">'.$sLabel.'</option>';
      }
      elseif($pbDisplayAll)
      {
        $sOption.= '<option value="'.$nValue.'" disabled="disabled" title="unavailable at this stage" class="optionDisabled">'.$sLabel.'</option>';
      }
    }

    return $sOption;
  }



  public function getCandidateGradeList()
  {
    if(isset($_SESSION['sl_grade_list']))
      return $_SESSION['sl_grade_list'];

   //NOT yet imported
    $asGrade = array();
    $asGrade[0] = 'No grade';
    $asGrade[1] = 'Met';
    $asGrade[2] = 'Low notable';
    $asGrade[3] = 'High notable';
    $asGrade[4] = 'Top shelf';


    $_SESSION['sl_grade_list'] = $asGrade;
    return $asGrade;
  }
  public function getCandidateGradeOption($psValue = '')
  {
    $asList = $this->getCandidateGradeList();

    $sOption = '<option value=""> - </option>';
    foreach($asList as $sValue => $sLabel)
    {
      if($sValue == $psValue)
        $sOption.= '<option value="'.$sValue.'" selected="selected">'.$sLabel.'</option>';
      else
        $sOption.= '<option value="'.$sValue.'">'.$sLabel.'</option>';
    }

    return $sOption;
  }

  public function get_var_info_by_label($label, $variable)
  {
    $info = '';
    $temp = array();

    if (isset($variable))
    {
      switch ($label)
      {
        case 'salary':
          $info = $variable.'M';
          break;

        case 'nationality':
          $temp = $this->getNationalityList();
          $info = $temp[$variable];
          break;

        case 'industry':
        case 'sec_industry':
        case 'all_industry':
          $temp = $this->getIndustryList(true, false, true);
          $info = $temp[$variable];
          break;

        case 'occupation':
        case 'all_occupation':
          $temp = $this->getOccupationList(true, true);
          $info = $temp[$variable]['label'];
          break;

        case 'sex':
        case 'gender':
          $temp = array(1 => 'male', 2 => 'female');
          $info = $temp[$variable];
          break;

        case 'is_client':
        case 'is_collaborator':
        case 'in_play':
        case 'has_doc':
        case 'candidate_met':
        case 'mba':
        case 'cpa':
        case 'dba_delete':
          $temp = array(0 => 'No', 1 => 'Yes');
          $info = $temp[$variable];
          break;

        case 'grade':
          $temp = $this->getCandidateGradeList();
          $info = $temp[$variable];
          break;

        case 'status':
          $temp = $this->getCandidateStatusList();
          $info = $temp[$variable];
          break;

        case 'language':
        case 'all_language':
        case 'sum_language':
          $temp = $this->getLanguageList();
          $info = $temp[$variable];
          break;

        case 'location':
          $temp = $this->getLocationList();
          $info = $temp[$variable];
          break;

        case 'meeting_set_by':
        case 'meeting_set_for':
        case 'creator':
        case 'contact_creator':
        case 'note_creator':
          $info = $this->login_obj->getUserName((int)$variable);
          break;

        case 'play_status':
        case 'play_status_active':
          $activity[1] = 'Pitched';
          $activity[2] = 'Resume sent';

          for($count = 51; $count < 61; $count++)
            $activity[$count] = 'CCM'.($count-50);

          $activity[100] = 'Offer';
          $activity[101] = 'Placed';
          $activity[150] = 'Stalled';
          $activity[200] = 'Fallen off';
          $activity[201] = 'Not interested';
          $activity[251] = 'Position filled';

          $info = $activity[$variable];
          break;
      }
    }

    return $info;
  }

}
