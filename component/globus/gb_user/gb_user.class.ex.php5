<?php

require_once('component/globus/gb_user/gb_user.class.php5');

class CGbUserEx extends CGbUser
{
  public $aUserData;
  public $aUsersData;
  public $aGroupsData;
  public $aCompaniesData;
  private $_nMaxNameSize = 20;
  private $_aTypes = array(
      'hrmanager' => 'HR&nbsp;Manager',
      'student' => 'Student',
      'teacher' => 'Trainer'
  );
  public $aUserTypesConst = array(
      'teacher' => CONST_TYPE_TEACHER,
      'student' => CONST_TYPE_STUDENT,
      'gbadmin' => CONST_TYPE_GBADMIN,
      'hrmanager' => CONST_TYPE_HRMANAGER
  );
  private $_aDataTypes = array(
      'company' => 'Company',
      'group' => 'Group',
      'member' => 'Member'
  );

  private $csLoadProfile = '';

  public function __construct()
  {
    if(!CDependency::getCpLogin()->isLogged())
      return true;

    $this->csLoadProfile = getValue('togpro');
    $bRefresh = !empty($this->csLoadProfile);

    $this->aUserData = $this->_loadUserData($bRefresh);

    //used below, if user not logged we init the usertype to ""
    if(!isset($this->aUserData['gbusertype']))
      $this->aUserData['gbusertype'] = '';

    $this->aGroupsData = $this->_loadGroupsData($bRefresh);
    $this->aUsersData = $this->_loadUsersData($bRefresh);
    $this->aCompaniesData = $this->_loadCompaniesData($bRefresh);



    //dump($_SESSION['user_rights']);


/*
    dump('refresh: '.(int)$bRefresh);
    dump('user data: ');
    dump($this->aUserData);

    dump('nb groups: '.count($this->aGroupsData));
    //dump($this->aGroupsData);

    dump('nb users: '.count($this->aUsersData));
    //dump($this->aUsersData);

    dump('nb companies: '.count($this->aCompaniesData));
    //dump($this->aCompaniesData);
*/

    return true;
  }

  public function getHtml()
  {
    $this->_processUrl();
    $sDataType = getValue('datatype','student');
    $sFilter = getValue('filter', 'active');

    switch($this->csType)
    {
      case CONST_TYPE_GBADMIN:
        switch($sDataType)
        {
          case 'hrmanager':
          case 'teacher':
          case 'student':
            switch ($this->csAction)
            {
              case CONST_ACTION_LIST:
                return $this->_displayMembers($sDataType, $this->cnPk, $sFilter);
                break;
              case CONST_ACTION_EDIT:
                return $this->_formMember($sDataType, true);
                break;
              case CONST_ACTION_ADD:
                return $this->_formMember($sDataType, false, false, $this->cnPk);
              break;
            }
            break;
          case 'company':
            switch($this->csAction)
            {
              case CONST_ACTION_LIST:
                return $this->_displayCompanies($sFilter);
                break;
              case CONST_ACTION_EDIT:
              case CONST_ACTION_ADD:
                return $this->_formCompany();
                break;
              case CONST_ACTION_VIEW:
                return $this->_displayCompany();
                break;
            }
            break;
          case 'group':
            switch($this->csAction)
            {
              case CONST_ACTION_LIST:
                return $this->_displayGroups($this->cnPk, $sFilter);
                break;
              case CONST_ACTION_EDIT:
                return $this->_formGroup($this->cnPk);
                break;
              case CONST_ACTION_ADD:
                return $this->_formGroup(0, $this->cnPk);
                break;
            }
            break;
        }
        break;

      case 'help':
        return $this->_getHelpPage();
    }

  }

  public function getAjax()
  {
    $this->_processUrl();
    $sDataType = getValue('datatype', 'student');
    $sFilter = getValue('filter', 'all');
    $oPage = CDependency::getComponentByName('page');

    switch($this->csType)
    {
      case CONST_TYPE_GBADMIN:
        switch($sDataType)
        {
          case 'group':
            switch($this->csAction)
            {
              case CONST_ACTION_LIST:
                return json_encode(array('data' => $this->_displayGroupsList($this->cnPk, $sFilter)));
                break;
              case CONST_ACTION_SAVEEDIT:
                return json_encode($this->_saveGroup($this->cnPk));
                break;
              case CONST_ACTION_SAVEADD:
                return json_encode($this->_saveGroup(0, $this->cnPk));
                break;
            }
          break;
          case 'company':
            switch($this->csAction)
            {
              case CONST_ACTION_SAVEEDIT:
                return json_encode($this->_saveCompany(true,$this->cnPk));
                break;
              case CONST_ACTION_SAVEADD:
                return json_encode($this->_saveCompany());
              break;
              case CONST_ACTION_EDIT:
                $sContent = $this->_formCompany(true);
                return json_encode($oPage->getAjaxExtraContent(array('data' => $sContent, 'action' => 'sSelect();')));
                break;
              case CONST_ACTION_VIEW:
                $sContent = $this->_displayCompanyDetails($this->cnPk);
                return json_encode($oPage->getAjaxExtraContent(array('data' => $sContent)));
                break;
            }
          break;

          case 'hrmanager':
          case 'teacher':
          case 'student':
            switch($this->csAction)
            {
              case CONST_ACTION_LIST:
                return json_encode(array('data' => $this->_displayMembersList($sDataType, $this->cnPk, $sFilter)));
                break;
              case CONST_ACTION_SAVEEDIT:
                return json_encode($this->_saveMember(true, $sDataType));
                break;
              case CONST_ACTION_SAVEADD:
                return json_encode($this->_saveMember(false, $sDataType));
                break;
              case CONST_ACTION_ADD:
                $nGroupPk = (int)getValue('onGroup', 0);
                return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_formMember($sDataType, false, true, $this->cnPk, $nGroupPk))) );
                break;
              case CONST_ACTION_DELETE:
                return json_encode($this->_deleteMember($this->cnPk, $sDataType));
                break;
            }
          break;
        }
        break;
    }
  }

  public function getCronJob()
  {
    $this->_processUrl();
    echo 'Globus Cron - trainer notifications<br />';

    $day = date('l');
    $time = (int)date('H');

    if(($day=='Sunday' && $time == 5) || (getValue('custom_uid') == $this->csUid && getValue('forcecron')))
    {
      echo 'Notify Trainers<br />';
      $this->_notifyTrainers();
    }
    else
      echo 'active only between 5 and 6 am<br />';
  }

  // Notifies trainers that they have pending assignments to correct
  private function _notifyTrainers()
  {
    $oTest = CDependency::getComponentByName('gb_test');
    $oHTML = CDependency::getCpHtml();
    $oTrainers = $this->_getModel()->getTeachers();
    $bRead = $oTrainers->readFirst();

    $nCount = 0;
    while($bRead)
    {
      $nTeacherPk = (int)$oTrainers->getFieldValue('gbuserpk');
      $aStudents = $this->_getModel()->getStudentsForTeacher($nTeacherPk);
      $aStudentIds = array_keys($aStudents);

      $aPendingTests = $oTest->getTestsToCorrect($aStudentIds);

      if($aPendingTests['nCount']>0)
      {
        // There are pending tests for this trainer, let's create a reminder
        $oMail = CDependency::getComponentByName('mail');
        $oMail->createNewEmail();
        $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
        $oMail->addRecipient($oTrainers->getFieldValue('email'), $oTrainers->getFieldValue('firstname').' '.$oTrainers->getFieldValue('lastname'));
        $oMail->addBCCRecipient(CONST_DEV_EMAIL, 'BC Media Support');

        $sTitle = 'Globus Online Coaching : you have '.$aPendingTests['nCount'].' test(s) waiting for correction';
        $sContent = 'Hello '.$oTrainers->getFieldValue('firstname').','.$oHTML->getCR(2);
        $sContent .= 'We would like to remind you there are '.$aPendingTests['nCount'].' assigments waiting for correction.<br />Participants are waiting, so please take action as soon as possible.'.$oHTML->getCR(2);
        $sContent .= $aPendingTests['sHtml'].$oHTML->getCR(2);;
        $nMail = $oMail->send($sTitle, $sContent);

        if(!is_key($nMail))
          echo 'Reminder for teacher '.$oTrainers->getFieldValue('firstname').' failed.<br />';
        else
          echo 'Reminder for teacher '.$oTrainers->getFieldValue('firstname').' sent.<br />'; $nCount++;
      }

      $bRead = $oTrainers->readNext();
    }
    echo $nCount.' mail sent<br />End Cron';
  }

  private function _loadUserData($pbForceRefresh = false)
  {
    if($pbForceRefresh || !isset($_SESSION['userData']['gbData']) || (getValue('refresh')=='user'))
    {
      $oLogin = CDependency::getComponentByName('login');
      $aUserData = $oLogin->getUserData();

      if(!empty($aUserData))
      {
        if($aUserData['shortname'] == 'gbadmin')
          $aGbUserData['gbusertype'] = 'gbadmin';
        else
        {
          $oDbResult = $this->_getModel()->getUserData();
          //$aGbUserData = $oDbResult->getData();
          $aGbUserData = $oDbResult->getAll();

          //select a specific profile
          if(count($aGbUserData) > 1)
          {
            $bFound = false;
            foreach($aGbUserData as $asProfile)
            {
              if($asProfile['gbusertype'] == $this->csLoadProfile)
              {
                //dump('The current user IS '.$asProfile['gbusertype']);
               // dump($asProfile);
                $aGbUserData = $asProfile;
                $bFound = true;
                break;
              }
            }

            //no specific profile requested --> pickup the last one
            if(!$bFound)
            {
              $aGbUserData = $asProfile;
              //dump('The current user IS (default one)');
              //dump($asProfile);
            }
          }
          else
          {
            $aGbUserData = array_first($aGbUserData);
            //dump('The current user IS (single profile) ');
            //dump($asProfile);
          }
        }

        $_SESSION['userData']['gbData'] = $aGbUserData;
      }
    }

    if(isset($_SESSION['userData']['gbData']))
      return $_SESSION['userData']['gbData'];

    return array();
  }

  private function _loadGroupsData($pbForceRefresh = false)
  {
    if($pbForceRefresh || (!isset($_SESSION['groupsDataGb'])) || (getValue('refresh')=='groups'))
    {
      $aGroupsData = array();
      switch(@$this->aUserData['gbusertype'])
      {
        case 'teacher' :
        case 'hrmanager':
          $aGroupsData = $this->_getModel()->getGroups(0, 'active', '', false, (int)$this->aUserData['gbuserpk']);
          break;

        case 'gbadmin':
        default:
          $oGroups = $this->_getModel()->getByWhere('gbuser_group', 'active=1');
          $aGroupsData = $this->_getModel()->formatOdbResult($oGroups, 'gbuser_grouppk');
          break;
      }
      $_SESSION['groupsDataGb'] = $aGroupsData;
    }
    return $_SESSION['groupsDataGb'];
  }

  private function _loadCompaniesData($pbForceRefresh = false)
  {
    if((!isset($_SESSION['companiesDataGb'])) || (getValue('refresh')=='companies') || $pbForceRefresh)
    {
      $aCompaniesData = array();
      switch($this->aUserData['gbusertype'])
      {
        case 'hrmanager':
          $oCompanies = $this->_getModel()->getByPk((int)$this->aUserData['gbuser_companyfk'], 'gbuser_company');
          $aCompaniesData = $this->_getModel()->formatOdbResult($oCompanies, 'gbuser_companypk');

          break;
        case 'gbadmin':
          $aCompaniesData =  $this->_getModel()->getActiveCompanies();
          break;
      }
      $_SESSION['companiesDataGb'] = $aCompaniesData;
    }
    return $_SESSION['companiesDataGb'];
  }

  private function _loadUsersData($pbForceRefresh = false)
  {
    //_live_dump($this->aUserData['gbusertype'], 'Need to load user data for ?? ');

    if($pbForceRefresh || (!isset($_SESSION['usersDataGb'])) || (getValue('refresh')=='users'))
    {
      switch(@$this->aUserData['gbusertype'])
      {
        case 'teacher':
        case 'hrmanager':
          $_SESSION['usersDataGb'] = $this->_getModel()->getUsersDataForSupervisor((int)$this->aUserData['gbuserpk']);
          //_live_dump($_SESSION['usersDataGb']);
          break;
        case 'gbadmin':
          $_SESSION['usersDataGb'] = $this->_getModel()->getActiveUsersData();
          break;

        default:
          $_SESSION['usersDataGb'] = array();
          break;
      }
    }

    return $_SESSION['usersDataGb'];
  }

  // Creates the array of values for the autocompletion search field
  public function getAutocompletionData()
  {
    if((!isset($_SESSION['searchData'])) || (getValue('refresh')=='search'))
    {
      $aValues = array();
      $aUserIds = array_keys($this->aUsersData);
      foreach ($aUserIds as $nPk)
      {
        $aValue = array(
            'label' => $this->getName($nPk),
            'category' => 'Participants',
            'url' => $this->displayMemberLink($nPk, 'student', true)
        );
        $aValues[]=$aValue;
      }

      foreach ($this->aGroupsData as $nPk => $aGroup)
      {
        $aValue = array(
            'label' => $aGroup['name'],
            'category' => 'Groups',
            'url' => $this->displayGroupLink($nPk, true)
        );
        $aValues[]=$aValue;
      }

      foreach ($this->aCompaniesData as $nPk => $aCompany)
      {
        $aValue = array(
            'label' => $aCompany['name'],
            'category' => 'Companies',
            'url' => $this->displayCompanyLink($nPk, true, 'tests')
        );
        $aValues[]=$aValue;
      }

      $_SESSION['searchData']=$aValues;
    }

    return $_SESSION['searchData'];
  }

  private function _isDataType($psDataType)
  {
    $aDataTypes = array_keys($this->_aDataTypes);

    return in_array($psDataType, $aDataTypes);
  }

  private function _isType($psType)
  {
    $aTypes = array_keys($this->_aTypes);

    return in_array($psType, $aTypes);
  }

  public function getCompanyFromGroupPk($pnGroupPk)
  {
    $oCompany = $this->_getModel()->getCompanyFromGroupPk($pnGroupPk);

    return $oCompany->getData();
  }

  private function _displayCompany()
  {
    if(!is_key($this->cnPk))
      return '';

    $oHTML = CDependency::getComponentByInterface('do_html');
    $oPage = CDependency::getComponentByName('page');

    $oPage->addCssFile($this->getResourcePath().'css/list.css');
    $oPage->addCssFile($this->getResourcePath().'css/viewCompany.css');

    $sHTML = '';

    $sHTML .= $oHTML->getBlocStart('company_'.$this->cnPk);
    $sHTML .= $this->_displayCompanyDetails($this->cnPk);
    $sHTML .= $oHTML->getBlocEnd();

    $sHTML .= $oHTML->getCR(1);

    $sHTML .= $oHTML->getBlocStart('company_groups_'.$this->cnPk, array('class' => 'listContainer'));
      $sHTML .= $oHTML->getTitle('Groups', 'h4', false, array('class' => 'groups'));
      $sHTML .= $this->_displayStatusSwitcher('group', 'active', $this->cnPk);
      $sHTML .= $this->_displayAddLink('group', $this->cnPk);
      $sHTML .= $oHTML->getBlocStart('company_groups_'.$this->cnPk, array('class' => 'dlist groupList'));
      $aGroups = $this->_getModel()->getGroups($this->cnPk, 'active');
      foreach ($aGroups as $nGroupPk => $aGroup)
        $sHTML .= $this->_displayGroupRow($nGroupPk, $aGroup);
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    $aActiveGroupIds = array_keys($aGroups);
    $sHTML .= $oHTML->getCR(2);
    $sHTML .= $this->_displayCompanyMembers('student', 'Student', $this->cnPk, $aActiveGroupIds);
    $sHTML .= $oHTML->getCR(2);
    $sHTML .= $this->_displayCompanyMembers('hrmanager', 'HR Manager', $this->cnPk, $aActiveGroupIds);

    return $sHTML;
  }

  private function _displayCompanyMembers($psType = 'student', $psShowType = 'Student', $pnCompanyPk=0, $paActiveGroupIds = array())
  {
    if(!assert('$this->_isType($psType)'))
      return '';

    if(!assert('is_key($pnCompanyPk)'))
      return '';

    $oMembers = $this->_getModel()->getMembers($pnCompanyPk, $psType, 'active', false, '', $paActiveGroupIds);
    $aMembers = $this->_getModel()->formatOdbResult($oMembers, 'gbuserpk');

    $oHTML = CDependency::getComponentByInterface('do_html');
    $oPage = CDependency::getComponentByName('page');
    $oPage->addCssFile($this->getResourcePath().'css/list.css');

    $sHTML = '';

    $sHTML .= $oHTML->getBlocStart('', array('class' => 'listContainer'));
      $sHTML .= $oHTML->getTitle($psShowType.'s', 'h4', false, array('class' => $psType.'s'));
      $sHTML .= $this->_displayStatusSwitcher($psType, 'active', $this->cnPk);
      $sHTML .= $this->_displayAddLink($psType, $pnCompanyPk);

      if(empty($aMembers))
      {
        $sHTML .= '<i>No Active '.$this->_aTypes[$psType].' in the company.</i>';
      }
      else
      {

      $sHTML .= $oHTML->getBlocStart($psType.'s_'.$this->cnPk, array('class' => 'member-box'));

        $sHTML .= $oHTML->getListStart($psType.'_list', array('class' => 'member-list'));
        foreach ($aMembers as $nMemberPk => $aMember)
        {
          $sUrlEdit = $oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, $nMemberPk, array('datatype' => $psType));
          $sUrlDelete = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_TYPE_GBADMIN, $nMemberPk, array('datatype' => $psType));
          $sLink = $oHTML->getLink($this->getName(0, true, $aMember), $sUrlEdit);

          $sHTML .= $oHTML->getListItemStart($psType.'_'.$nMemberPk);
            $sHTML .= $sLink;
            $sHTML .= $oHTML->getBlocStart('', array('class' => 'actions'));
              if($psType=='student')
              {
                $sUrlViewTests = $oPage->getUrl('196-002', CONST_ACTION_VIEW, CONST_TYPE_GBADMIN, $nMemberPk, array('datatype' => 'student'));
                $sHTML .= $oHTML->getPicture(CONST_PICTURE_TEST, 'View Profile', $sUrlViewTests);
              }
              $sHTML .= $oHTML->getLink($oHTML->getPicture(CONST_PICTURE_SETTINGS), $sUrlEdit);
              $sHTML .= $oHTML->getLink($oHTML->getPicture(CONST_PICTURE_GBDELETE), $sUrlDelete);
            $sHTML .= $oHTML->getBlocEnd();
          $sHTML .= $oHTML->getListItemEnd();
        }
        $sHTML .= $oHTML->getListEnd();
        $sHTML .= $oHTML->getFloatHack();
      }

      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayGroups($pnCompanyPk=0, $psFilter = 'active')
  {
    // TODO : Handle user setting for number of rows on one page

    if(!assert('is_numeric($pnCompanyPk)'))
      return '';

    if(!assert('in_array($psFilter, $this->_getModel()->aFilters)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');
    $oPage->addCssFile($this->getResourcePath().'css/list.css');

    $sHTML = $sTitle = '';
    if(is_key($pnCompanyPk))
      $sTitle .= $this->displayCompanyLink($pnCompanyPk).' > ';

    $sTitle .= 'Groups';

    $sHTML .= $oHTML->getBlocStart('', array('class' => 'listContainer'));
    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $this->_displayStatusSwitcher('group', $psFilter, $pnCompanyPk);
    $sHTML .= $oHTML->getCR(1);
    $sHTML .= $this->_displayAddLink('group', $pnCompanyPk);

    $sHTML .= $oHTML->getBlocStart('groupListContainer', array('class'=>'groupList dlist'));
      $sHTML .= $this->_displayGroupsList($pnCompanyPk, $psFilter);
    $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayGroupsList($pnCompanyPk=0, $psFilter = 'active')
  {
    if(!assert('is_numeric($pnCompanyPk)'))
      return '';

    if(!assert('in_array($psFilter, $this->_getModel()->aFilters)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');
    $oPager = CDependency::getComponentByName('pager');
    $oPager->initPager();
    $nOffset = $oPager->getSqlOffset();
    $sLimit = $nOffset.', '.$oPager->getLimit();

    $sHTML = '';
    $sHTML .= $oHTML->getBlocStart('group_header', array('class' => 'dlist-header'));
      $sHTML .= $oHTML->getBloc('', 'Name', array('class' => 'name'));
      $sHTML .= $oHTML->getBloc('', 'Created', array('class' => 'created_on'));
      $sHTML .= $oHTML->getBloc('', 'Participants', array('class' => 'nbstudents'));
      $sHTML .= $oHTML->getBloc('', '' , array('class' => 'actions'));
    $sHTML .= $oHTML->getBlocEnd();

    $aGroups = $this->_getModel()->getGroups($pnCompanyPk, $psFilter, $sLimit);

    if(empty($aGroups))
      return 'No Group Found';

    foreach ($aGroups as $nGroupPk => $aGroup)
      $sHTML .= $this->_displayGroupRow($nGroupPk, $aGroup);

    $nTotalCount = $this->_getModel()->getGroupsCount($pnCompanyPk, $psFilter);

    if($nTotalCount>0)
    {
      $sUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $this->cnPk, array('datatype' => 'group'));
      $asPagerUrlOption = array('ajaxTarget' => 'groupListContainer');
      $sHTML.= $oPager->getCompactDisplay($nTotalCount, $sUrl, $asPagerUrlOption);
    }

    return $sHTML;
  }

  private function _displayGroupRow($pnGroupPk, $paGroup)
  {
    $oHTML = CDependency::getComponentByInterface('do_html');

    $sUrlTests = $this->displayGroupLink($pnGroupPk, true);
    $sLinkTests = $oHTML->getLink('View Assignments', $sUrlTests, array('class' => 'seeTest'));

    $sUrlEdit = $this->displayGroupLink($pnGroupPk, true, 'manage');
    $sLinkEdit = $oHTML->getLink('Manage Group', $sUrlEdit, array('class' => 'manage'));

//    $sUrlRemove = $oPage->getUrl($this->csUid, CONST_ACTION_DELETE, CONST_TYPE_GBADMIN, $pnGroupPk, array('datatype' => 'group'));
//    $sLinkRemove = $oHTML->getLink('Delete', $sUrlRemove, array('class' => 'delete'));

    $sHTML = '';
    $sHTML .= $oHTML->getBlocStart('group_'.$pnGroupPk, array('class' => 'dlist-item'));
      $sHTML .= $oHTML->getBloc('', $this->displayGroupLink($pnGroupPk, false, 'manage'), array('class' => 'name'));
      $sHTML .= $oHTML->getBloc('', $oHTML->getNiceTime($paGroup['created_on']), array('class' => 'created_on'));
      $sHTML .= $oHTML->getBloc('', $paGroup['nbStudents'].' student(s)', array('class' => 'nbstudents'));
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'actions'));
        $sHTML .= $sLinkTests.$sLinkEdit;
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }


  private function _displayMembers($psMemberType, $pnCompanyPk=0, $psFilter = 'active')
  {
    if(!assert('$this->_isType($psMemberType)'))
      return '';

    if(!assert('is_numeric($pnCompanyPk)'))
      return '';

    if(!assert('in_array($psFilter, $this->_getModel()->aFilters)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');
    $oPage->addCssFile($this->getResourcePath().'css/list.css');

    $sHTML = $sTitle = '';
    if(is_key($pnCompanyPk))
      $sTitle .= $this->displayCompanyLink($pnCompanyPk).' > ';

    $sTitle .= $this->_aTypes[$psMemberType].'s';

    $sHTML .= $oHTML->getBlocStart('', array('class' => 'listContainer'));
    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $this->_displayStatusSwitcher($psMemberType, $psFilter, $pnCompanyPk);
    $sHTML .= $oHTML->getCR(1);
    $sHTML .= $this->_displayAddLink($psMemberType, $pnCompanyPk);

    $sHTML .= $oHTML->getBlocStart('memberListContainer', array('class' => 'memberList dlist'));
      $sHTML .= $this->_displayMembersList($psMemberType, $pnCompanyPk, $psFilter);
    $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayMembersList($psMemberType, $pnCompanyPk=0, $psFilter = 'active')
  {
    if(!assert('$this->_isType($psMemberType)'))
      return '';

    if(!assert('is_numeric($pnCompanyPk)'))
      return '';

    if(!assert('in_array($psFilter, $this->_getModel()->aFilters)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');
    $oPager = CDependency::getComponentByName('pager');
    $sHTML = '';

    $oPager->initPager();
    $nOffset = $oPager->getSqlOffset();
    $sLimit = $nOffset.', '.$oPager->getLimit();

    $nTotalCount = $this->_getModel()->getMembersCount($pnCompanyPk, $psMemberType, $psFilter);
    if($nTotalCount > 10)
    {
      $sUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $this->cnPk, array('datatype' => $psMemberType));
      $asPagerUrlOption = array('ajaxTarget' => 'memberListContainer');
      $sHTML.=  $oPager->getCompactDisplay($nTotalCount, $sUrl, $asPagerUrlOption);
    }


    $sHTML .= $oHTML->getBlocStart($psMemberType.'_header', array('class' => 'dlist-header'));
      $sHTML .= $oHTML->getBloc('', 'Name', array('class' => 'name'));

      if($psMemberType=='student')
        $sHTML .= $oHTML->getBloc('', 'Group', array('class' => 'group'));

      $sHTML .= $oHTML->getBloc('', 'Created', array('class' => 'created_on'));
      $sHTML .= $oHTML->getBloc('', '', array('class' => 'actions'));
    $sHTML .= $oHTML->getBlocEnd();

    $oMembers = $this->_getModel()->getMembers($pnCompanyPk, $psMemberType, $psFilter, false, $sLimit);
    $bRead = $oMembers->readFirst();

    if(!$bRead)
      return 'No '.$this->_aTypes[$psMemberType].' Found';

    while($bRead)
    {
      $aData = $oMembers->getData(); $nMemberPk = (int)$oMembers->getFieldValue('gbuserpk');
      $sHTML .= $this->_displayMemberRow($nMemberPk, $aData, $psMemberType);

      $bRead = $oMembers->readNext();
    }

    $nTotalCount = $this->_getModel()->getMembersCount($pnCompanyPk, $psMemberType, $psFilter);
    if($nTotalCount > 10)
    {
      $sUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $this->cnPk, array('datatype' => $psMemberType));
      $asPagerUrlOption = array('ajaxTarget' => 'memberListContainer');
      $sHTML.= $oPager->getCompactDisplay($nTotalCount, $sUrl, $asPagerUrlOption);
    }

    return $sHTML;
  }

  private function _displayMemberRow($pnMemberPk, $paMember, $psMemberType)
  {
    if(!assert('is_key($pnMemberPk)'))
      return '';

    if(!assert('is_array($paMember) && !empty($paMember)'))
      return '';

    if(!assert('$this->_isType($psMemberType)'))
      return '';

    $oHTML = CDependency::getComponentByInterface('do_html');

    $sUrlEdit = $this->displayMemberLink($pnMemberPk, $psMemberType, true, 'manage');
    $sUrlRemove = $this->displayMemberLink($pnMemberPk, $psMemberType, true, 'remove');

    $sLinkEdit = $oHTML->getLink('Manage', $sUrlEdit, array('class' => 'manage'));
    $sLinkRemove = $oHTML->getLink('Remove', $sUrlRemove, array('class' => 'delete'));

    $sHTML = '';
    $sHTML .= $oHTML->getBlocStart($psMemberType.'_'.$pnMemberPk, array('class' => 'dlist-item'));
      $sHTML .= $oHTML->getBloc('', $this->displayMemberLink((int)$pnMemberPk, $psMemberType, false, 'manage'), array('class' => 'name'));
      if($psMemberType=='student')
        $sHTML .= $oHTML->getBloc('', $paMember['group_name'], array('class' => 'group'));
      $sHTML .= $oHTML->getBloc('', $oHTML->getNiceTime($paMember['created_on']), array('class' => 'created_on'));
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'actions'));
        $sHTML .= $sLinkEdit.$sLinkRemove;
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayStatusSwitcher($psDataType, $psStatus = 'all', $pnPk = 0)
  {
    if(!assert('is_numeric($pnPk)'))
      return '';

    if(!assert('in_array($psStatus, $this->_getModel()->aFilters)'))
      return  '';

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');
    $sHTML = '';

    $aUrl['inactive'] = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $pnPk, array('datatype' => $psDataType, 'filter' => 'inactive'));
    $aUrl['active'] = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $pnPk, array('datatype' => $psDataType, 'filter' => 'active'));
    $aUrl['all'] = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $pnPk, array('datatype' => $psDataType, 'filter' => 'all'));

    $sHTML .= $oHTML->getBlocStart('statusSwitcher', array('class' => 'statusSwitcher'));
    foreach ($this->_getModel()->aFilters as $sStatus)
    {
      $aOptions = array();
      if($sStatus==$psStatus)
        $aOptions['class'] = 'selected';
      $sHTML .= $oHTML->getLink(ucfirst($sStatus), $aUrl[$sStatus], $aOptions);
    }
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayCompanyDetails($pnCompanyPk)
  {
    if(!assert('is_key($pnCompanyPk)'))
      return '';

    $oHTML = CDependency::getComponentByInterface('do_html');
    $oPage = CDependency::getComponentByName('page');

    $aCompany = $this->_getModel()->getCompany($pnCompanyPk);

    $sTitle = $aCompany['name'];
    $sUrlEdit = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, $pnCompanyPk, array('datatype' => 'company'));
    $sGoPopup = $this->_getPopupUrl($sUrlEdit);
    $sLinkEdit = $oHTML->getLink('Edit', 'javascript:;', array('class' => 'edit', 'onClick' => $sGoPopup));

    $sHTML = '';
    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR(1);

    $sHTML .= $oHTML->getBlocStart('cDetails', array('class' => 'companyDetails'));
      $sHTML .= $oHTML->getBloc('name', '<span>Name:</span> '.$aCompany['name']);
      $sHTML .= $oHTML->getBloc('industry', '<span>Industry:</span> '.$aCompany['industry_name']);
      $sHTML .= $oHTML->getBloc('country', '<span>Country:</span> '.$aCompany['nationality_name']);
      $sActive = ((bool)$aCompany['active']) ? 'Yes' : 'No';
      $sHTML .= $oHTML->getBloc('active', '<span>Active:</span> '.$sActive);
      $sHTML .= $oHTML->getBloc('actions', $sLinkEdit, array('class' => 'actions'));
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayAddLink($psType, $pnPk = 0)
  {
    if(!assert('is_numeric($pnPk)'))
      return '';

    if(!assert('$this->_isDataType($psType) || $this->_isType($psType)'))
      return '';

    $aTypes = array_merge($this->_aDataTypes, $this->_aTypes);

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $sUrlAdd = $oPage->getUrl($this->csUid, CONST_ACTION_ADD, CONST_TYPE_GBADMIN, $pnPk, array('datatype' => $psType));
    $sLinkAdd = $oHTML->getLink('Add&nbsp;New&nbsp;'.$aTypes[$psType], $sUrlAdd, array('class' => 'button-like add-button'));

    return $sLinkAdd;
  }

  private function _displayCompanies($psFilter = 'active')
  {
    $sHTML = '';
    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $oPage->addCssFile($this->getResourcePath().'css/list.css');

    $sHTML .= $oHTML->getBlocStart('companyListContainer', array('class' => 'listContainer'));

      $sHTML .= $oHTML->getTitle('Companies', 'h1');
      $sHTML .= $this->_displayStatusSwitcher('company', $psFilter);
      $sHTML .= $oHTML->getCR(1);

      $sHTML .= $this->_displayAddLink('company');

      $oDbResult = $this->_getModel()->getCompanies(0, $psFilter);
      $bRead = $oDbResult->readFirst();

      $sHTML .= $oHTML->getBlocStart('companyList');
      if(!$bRead)
        $sHTML .= 'No Company was found.';
      else
      {
        $sHTML .= $oHTML->getBlocStart('companies', array('class' => 'dlist companyList'));
          $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-header'));
            $sHTML .= $oHTML->getBloc('', 'Name', array('class' => 'name'));
            $sHTML .= $oHTML->getBloc('', '', array('class' => 'groups'));
            $sHTML .= $oHTML->getBloc('', 'Actions', array('class' => 'actions'));
          $sHTML .= $oHTML->getBlocEnd();
          while($bRead)
          {
            $nCompanyPk = (int)$oDbResult->getFieldValue('gbuser_companypk');
            $nbGroups = $oDbResult->getFieldValue('nbActiveGroups');
            $sContent = ($nbGroups==0) ? '-' : $nbGroups.' group(s)';

            $sTestsUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, $this->csType, $nCompanyPk, array('filter' => 'company'));
            $sTestsLink = ($nbGroups==0) ? '' : $oHTML->getLink('View Assignments', $sTestsUrl, array('class' => 'seeTest'));

            $sManageUrl = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, $this->csType, $nCompanyPk, array('datatype' => 'company'));
            $sManageLink = $oHTML->getLink('Manage Company', $sManageUrl, array('class' => 'manageGroups'));

            $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-item'));
              $sHTML .= $oHTML->getBloc('', $this->displayCompanyLink($nCompanyPk, false, 'manage'), array('class' => 'name'));
              $sHTML .= $oHTML->getBloc('', $sContent, array('class' => 'groups'));
              $sHTML .= $oHTML->getBlocStart('', array('class' => 'actions'));
              $sHTML .= $sTestsLink.$sManageLink;
              $sHTML .= $oHTML->getBlocEnd();
            $sHTML .= $oHTML->getBlocEnd();

            $bRead = $oDbResult->readNext();
          }
        $sHTML .= $oHTML->getBlocEnd();
      }
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displaySelectOption($psLabel, $psValue = '')
  {
    if(!assert('!empty($psLabel)'))
      return '';

    $sHTML = '<option value=\''.$psValue.'\'>';
    $sHTML .= $psLabel;
    $sHTML .= '</option>';

    return $sHTML;
  }

  // Displays the select dropdown

  private function _displayGroupsSelect($pnCompanyPk)
  {
    if(!assert('is_numeric($pnCompanyPk)'))
      return '';

    if(!is_key($pnCompanyPk))
      return array('data' => '<i>None.</i>');

    $oGroups = $this->_getModel()->getByFk($pnCompanyPk, 'gbuser_group', 'gbuser_company');
    $bRead = $oGroups->readFirst();

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');
    $sUrlAddGroup = $oPage->getUrl($this->_getUid(), CONST_ACTION_ADD, CONST_TYPE_GBADMIN, $pnCompanyPk);

    if(!$bRead)
      return array('data' => '<i>No group for this company.</i> '.$oHTML->getLink('Create one', $sUrlAddGroup));

    $sHTML = '<select id=\'groupfk\'>';
    while($bRead)
    {
      $sHTML .= $this->_displaySelectOption($oGroups->getFieldValue('name'), $oGroups->getFieldValue('gbuser_grouppk'));

      $bRead = $oGroups->readNext();
    }
    $sHTML .= '</select>';

    return array('data' => $sHTML, 'action' => 'showGroupSelect();');
  }


  private function _formMember($psType, $pbIsEdition, $pbInAjax=false, $pnCompanyPk = 0, $pnGroupPk = 0)
  {
    // TODO: Replace ugly checkboxes
    if(!assert('is_bool($pbIsEdition)'))
      return '';

    if(!assert('is_numeric($pnCompanyPk)'))
      return '';

    if(!assert('is_numeric($pnGroupPk)'))
      return '';

    if(!assert('is_string($psType) && !empty($psType) && $this->_isType($psType)'))
      return '';

    $sShowType = $this->_aTypes[$psType];
    $bAddedOnGroup = is_key($pnGroupPk);
    $bAddedOnCompany = (is_key($pnCompanyPk)) && !$bAddedOnGroup;

    $sRefreshWith = getValue('refreshWith','');
    $sDivToRefresh = getValue('divToRefresh','');

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $oPage->addCssFile($this->getResourcePath().'css/formMember.css');

    $sHTML = '';


    // - ---------------------------------------------
    // FORM SETUP
    if($pbIsEdition)
    {
      $oStudent = $this->_getModel()->getUser($this->cnPk);
      $nCompany = (int)$oStudent->getFieldValue('gbuser_companyfk');
      $sTitle = 'Edit '.$sShowType;
      $sUrlSave = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEEDIT, $oPage->getType(), $this->cnPk, array('datatype' => $psType));
    }
    else
    {
      $nCompany = $pnCompanyPk;
      $oStudent = new CDbResult();
      $sTitle = 'Add New '.$sShowType;
      $sUrlSave = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEADD, $oPage->getType(), 0, array('datatype' => $psType));
    }

    $sFormName = $psType.'Form';
    $aFormOptions = array('action' => $sUrlSave, 'submitLabel' => 'Save '.$sShowType, 'noCancelButton' => 'noCamcelButton');
    if($pbInAjax)
      $aFormOptions['inajax']='inajax';

    $oForm = $oHTML->initForm($sFormName);
    $oForm->setFormParams($sFormName, true, $aFormOptions);

    if(!empty($sDivToRefresh))
      $oForm->addField('input', 'divToRefresh', array('type' => 'hidden', 'value' => $sDivToRefresh));

    if(!empty($sRefreshWith))
      $oForm->addField('input', 'refreshWith', array('type' => 'hidden', 'value' => $sRefreshWith));

    $oForm->addField('misc', 'personnalDetailsTitle', array('type' => 'text', 'text' => 'Personal Details', 'class' => 'sectionTitle'));

    $oForm->addSection('personnalDetails');

      $oForm->addField('input', 'firstname', array('type' => 'text', 'label' => 'First Name', 'value' => $oStudent->getFieldValue('firstname')));
      $oForm->setFieldControl('firstname', array('jsFieldNotEmpty' => '1'));

      $oForm->addField('input', 'lastname', array('type' => 'text', 'label' => 'Last Name', 'value' => $oStudent->getFieldValue('lastname')));
      $oForm->setFieldControl('lastname', array('jsFieldNotEmpty' => '1', 'jsFieldMinSize' => 2));

      $oForm->addField('input', 'email', array('type' => 'text', 'label' => 'Email', 'value' => $oStudent->getFieldValue('email')));
      $oForm->setFieldControl('email', array('jsFieldNotEmpty' => '1', 'jsFieldTypeEmail' => '1'));

      if($pbIsEdition)
      {
        $oForm->addField('checkbox', 'status', array('label' => 'Active', 'value' => $oStudent->getFieldValue('status')));

        $oForm->addField('misc', '', array('type' => 'text', 'text' => '&nbsp;'));
        $oForm->addField('checkbox', 'notify_again', array('label' => 'Send credentials again ?'));
      }

    $oForm->closeSection();

    if((($bAddedOnCompany) || ($pbIsEdition)) && ($psType!='teacher'))
    {
      $oForm->addField('input', 'companyfk', array('type' => 'hidden', 'value' => $nCompany));

      $aGroups = $this->_getModel()->getGroups($nCompany);
      $nStudentGroupFk = (int)$oStudent->getFieldValue('gbuser_groupfk');

      if(!empty($aGroups) && !$pbInAjax)
      {
        $oForm->addField('misc', 'companyAndGroupsTitle', array('type' => 'text', 'text' => 'Save In Group', 'class' => 'sectionTitle'));

        $oForm->addSection('companyAndGroups');

        $oForm->addField('sselect', 'groupfk', array('label' => 'Group'));
        $oForm->addOption('groupfk', array('label' => 'No Group', 'value' => 0));

        foreach ($aGroups as $nGroupFk => $aGroupData)
        {
          $aOptions = array('label' => $aGroupData['name'], 'value' => $nGroupFk);
          if($nGroupFk==$nStudentGroupFk)
            $aOptions['selected'] = 'selected';
          $oForm->addOption('groupfk', $aOptions);
        }

        $oForm->closeSection();
      }
    }
    elseif($bAddedOnGroup)
    {
      $oForm->addField('input', 'companyfk', array('type' => 'hidden', 'value' => $nCompany));
      $oForm->addField('input', 'groupfk', array('type' => 'hidden', 'value' => $pnGroupPk));
    }

    // MAIN PAGE HTML

    $sHTML .= $oHTML->getBlocStart('formMember', array('class' => 'bloc'));

      $sHTML .= $oHTML->getBloc('', $sTitle, array('class' => 'bloc-header'));
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'bloc-content'));

      $sHTML .= $oForm->getDisplay();

      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }


  private function _formCompany($pbInAjax = false)
  {
    // TODO : Get goPopup look nicer

    if(!assert('is_bool($pbInAjax)'))
      return '';

    $bIsEdition = (is_key($this->cnPk));

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $oPage->addCssFile($this->getResourcePath().'css/formCompany.css');

    $sHTML = '';

    // FORM SETUP

    if($bIsEdition)
    {
      $oCompany = $this->_getModel()->getByPk($this->cnPk, 'gbuser_company');

      $sTitle = 'Edit '.$oCompany->getFieldValue('name');
      $sUrlSave = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEEDIT, $oPage->getType(), $this->cnPk, array('datatype' => 'company'));
    }
    else
    {
      $oCompany = new CDbResult();
      $sTitle = 'Add New Company';
      $sUrlSave = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEADD, $oPage->getType(), 0, array('datatype' => 'company'));
    }
    $aFormOptions =  array('action' => $sUrlSave, 'submitLabel' => 'Save Company', 'noCancelButton' => 'noCamcelButton');
    if($pbInAjax)
      $aFormOptions['inajax']='inajax';

    $oForm = $oHTML->initForm('companyForm');
    $oForm->setFormParams('companyForm', true, $aFormOptions);

    $oForm->addField('input', 'isEdition', array('type' => 'hidden', 'value' => (int)$bIsEdition));

    $oForm->addField('input', 'name', array('type' => 'text', 'label' => 'Name', 'value' => $oCompany->getFieldValue('name')));
    $oForm->setFieldControl('name', array('jsFieldNotEmpty' => '1', 'jsFieldMinSize' => 2));

    $sActive = ($bIsEdition) ? $oCompany->getFieldValue('active') : 1;
    $oForm->addField('checkbox', 'active', array('label' => 'Active', 'value' => $sActive));

    $oForm->addField('sselect', 'industryfk', array('label' => 'Industry'));
    $oIndustries = $this->_getModel()->getList('industry');
    $bRead = $oIndustries->readFirst();

    while($bRead)
    {
      $nIndPk = $oIndustries->getFieldValue('industrypk');
      $aOption = array('value' => $nIndPk, 'label' => $oIndustries->getFieldValue('industry_name'));

      if($nIndPk==$oCompany->getFieldValue('industryfk'))
        $aOption['selected'] = 'selected';

      $oForm->addOption('industryfk', $aOption);

      $bRead = $oIndustries->readNext();
    }
    $oForm->setFieldControl('industryfk', array('jsFieldNotEmpty' => '1'));

    $oForm->addField('sselect', 'nationalityfk', array('label' => 'Country'));
    $oNations = $this->_getModel()->getList('nationality');
    $bRead = $oNations->readFirst();

    while($bRead)
    {
      $nNationPk = $oNations->getFieldValue('nationalitypk');
      $aOption = array('value' => $nNationPk, 'label' => $oNations->getFieldValue('nationality_name'));

      if($nNationPk==$oCompany->getFieldValue('nationalityfk'))
        $aOption['selected'] = 'selected';

      $oForm->addOption('nationalityfk', $aOption);

      $bRead = $oNations->readNext();
    }
    $oForm->setFieldControl('nationalityfk', array('jsFieldNotEmpty' => '1'));

    // MAIN PAGE HTML

    $sHTML .= $oHTML->getBlocStart('formCompany', array('class' => 'bloc'));

      $sHTML .= $oHTML->getBloc('', $sTitle, array('class' => 'bloc-header'));
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'bloc-content'));
      $sHTML .= $oForm->getDisplay();
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _getPopupUrl($psUrl)
  {
    if(!assert('is_string($psUrl) && !empty($psUrl)'))
      return '';

    $sPopupUrl =
          ' var oConf = goPopup.getConfig();
            oConf.height = 560;
            oConf.width = 980;
            oConf.modal = true;
            oConf.draggable = false;
            oConf.resizable = false;
            goPopup.setLayerFromAjax(oConf, \''.$psUrl.'\'); ';

    return $sPopupUrl;
  }

  private function _formGroup($pnGroupPk = 0, $pnCompanyPk=0)
  {
    // TODO : Handle multi creation from a CSV upload
    // TODO : Custom Selected Member UL for students

    if(!assert('is_key($pnCompanyPk) || is_key($pnGroupPk)'))
      return '';

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $oPage->addCssFile($this->getResourcePath().'css/formGroup.css');
    $oPage->addJsFile($this->getResourcePath().'js/formGroup.js');

    $bIsEdition = is_key($pnGroupPk);

    if(!$bIsEdition)
    {
      if(!assert('is_key($pnCompanyPk)'))
        return '';

      $nCompanyPk = $pnCompanyPk;

      $oCompany = $this->_getModel()->getByPk($pnCompanyPk, 'gbuser_company');
      $oGroup = new CDbResult();
      $sTitle = 'Add Group';
      $sUrlSave = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEADD, $oPage->getType(), $this->cnPk, array('datatype' => 'group'));
    }
    else
    {
      $oCompany = $this->_getModel()->getCompanyFromGroupPk($pnGroupPk);
      $oGroup = $this->_getModel()->getByPk($pnGroupPk, 'gbuser_group');
      $nCompanyPk = (int)$oCompany->getFieldValue('gbuser_companypk');
      $sTitle = 'Edit Group > '.$oGroup->getFieldValue('name');
      $sUrlSave = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEEDIT, $oPage->getType(), $this->cnPk, array('datatype' => 'group'));
    }

    $sFullTitle = $this->displayCompanyLink($nCompanyPk, false, 'manage').' > '.$sTitle;
   // $sSkipUrl = $this->displayCompanyLink($nCompanyPk, true, '196-001', 'manage);

    $oForm = $oHTML->initForm('groupForm');
    $oForm->setFormParams('groupForm', true, array('action' => $sUrlSave, 'submitLabel' => 'Save Group', 'noCancelButton' => 'noCamcelButton'));

    $oForm->addField('input', 'isEdition', array('type' => 'hidden', 'value' => (int)$bIsEdition));

    $oForm->addSection('mainSettings');
      $oForm->addField('input', 'name', array('type' => 'text', 'label' => 'Name', 'value' => $oGroup->getFieldValue('name')));
      $oForm->setFieldControl('name', array('jsFieldNotEmpty' => '1', 'jsFieldMinSize' => 2));

      $aOptionsActive =  array('label' => 'Active', 'display_type' => 'full');
      if((bool)$oGroup->getFieldValue('active'))
        $aOptionsActive['checked']='checked';
      $oForm->addField('checkbox', 'active',$aOptionsActive);
    $oForm->closeSection();

    $oForm->addField('misc', 'supervisorsTitle', array('type' => 'text', 'text' => 'Supervisors', 'class' => 'sectionTitle'));
    $oForm->addSection('groupSupervisors', array('class' => 'membersBox'));
      $this->_formAddMemberSection($oForm, 'hrmanager', 'HR Manager', $nCompanyPk, $pnGroupPk);
      $this->_formAddMemberSection($oForm, 'teacher', 'Trainer', $nCompanyPk, $pnGroupPk);
    $oForm->closeSection();

    $oForm->addField('misc', 'studentsTitle', array('type' => 'text', 'text' => 'Participants', 'class' => 'sectionTitle'));
    $oForm->addSection('groupStudents', array('class' => 'membersBox'));
      $this->_formAddMemberSection($oForm, 'student', 'Student', $nCompanyPk, $pnGroupPk);
    $oForm->closeSection();

    $oForm->addField('misc', 'scheduleTitle', array('type' => 'text', 'text' => 'Deadlines', 'class' => 'sectionTitle'));
    $oForm->addSection('schedule', array('id' => 'schedule'));
    $oTest = CDependency::getComponentByName('gb_test');

    if($bIsEdition)
      $aSchedule = $oTest->getGroupSchedule($pnGroupPk);
    else
      $aSchedule = $oTest->aChaptersData;

    $nCount = 0;

    foreach ($aSchedule as $nChapterPk => $aChapter)
    {
      $nCount++;
      $aOptions = array('type' => 'date', 'label' => $aChapter['rank'].'. '.$aChapter['name'], 'class' => 'chapter', 'chapter' => $aChapter['rank']);
      if($bIsEdition && (isset($aChapter['deadline'])))
        $aOptions['value']=$aChapter['deadline'];
      $oForm->addField('input', $nChapterPk.'_deadline', $aOptions);

      if($nCount==1)
        $oForm->addField('misc', 'autoFill', array('type'=>'text', 'text'=> $oHTML->getLink('Auto Fill', 'javascript:;', array('id' => 'autofill'))));
    }
    $oForm->closeSection();


    if (!$bIsEdition)
      $aOptionsCheckbox = array('label' => 'Notify Participants. (<i>Sends an email to every student with assignment schedule and trainer\'s name</i>)', 'checked' => 'checked');
    else
      $aOptionsCheckbox = array('label' => 'Notify Participants. (<i>Sends an email to students that haven\'t been notified yet</i>)');

    $oForm->addField('checkbox', 'notifyUsers', $aOptionsCheckbox);

    // MAIN PAGE HTML
    $sHTML = '';
    $sHTML .= $oHTML->getBlocStart('formGroup', array('class' => 'bloc'));

      $sHTML .= $oHTML->getTitle($sFullTitle, 'h1');
      $sHTML .= $oHTML->getCR(1);
      if(!$bIsEdition)
        $sTitle .= ' to '.$oCompany->getFieldValue('name');
      $sHTML .= $oHTML->getBloc('', $sTitle, array('class' => 'bloc-header'));

      $sHTML .= $oHTML->getBlocStart('', array('class' => 'bloc-content'));
      $sHTML .= $oForm->getDisplay();
      $sHTML .= $oHTML->getBlocEnd();

    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _formAddMemberSection(&$poForm, $psType, $psShowType, $pnCompanyPk, $pnGroupPk)
  {
    if(!assert('is_object($poForm)'))
      return new CFormEx();

    if(!assert('is_string($psType) && !empty($psType)'))
      return new CFormEx();

    if(!assert('is_string($psShowType) && !empty($psShowType)'))
      return new CFormEx();

    if(!assert('is_key($pnCompanyPk)'))
      return new CFormEx();

    if(!assert('is_numeric($pnGroupPk)'))
      return new CFormEx();

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByInterface('do_html');
    $anSelected = array();

    if($psType=='teacher')
    {
      $oCompanyMembers = $this->_getModel()->getTeachers();
    }
    else
    {
      $oCompanyMembers = $this->_getModel()->getMembers($pnCompanyPk, $psType);
    }
    $bRead = $oCompanyMembers->readFirst();


    $sClass = (!$bRead) ? 'displayNoSelect' : 'displaySelect';
    $poForm->addSection($psType.'s', array('class' => $sClass));

      $sFieldName = $psType.'Ids';
      $poForm->addField('sselect', $sFieldName, array('label' => $psShowType.'s', 'multiple' => 'multiple'));
      $poForm->setFieldControl($sFieldName, array('jsFieldNotEmpty' => ''));

      if(!$bRead)
      {
        // No member recorded for this company. The select field is hidden and we display a link to add new ones.
        $poForm->addField('misc', 'no'.$psType, array('type' => 'text', 'text' => 'There is no '.$psShowType.' recorded for this company.'));
      }
      else
      {
        // Populating the select field
        if(is_key($pnGroupPk))
        {
          $oMembers = $this->_getModel()->getGroupMembers($pnGroupPk, $psType, true);
          $aMembers = $this->_getModel()->formatOdbResult($oMembers, 'gbuserpk');
          $aMembersIds = array_keys($aMembers);
        }
        else
        {
          $aMembersIds = array();
        }

        while($bRead)
        {
          $aMember = $oCompanyMembers->getData();

          if(in_array($aMember['gbuserpk'], $aMembersIds))
          {
            $anSelected[] = (int)$aMember['gbuserpk'];
            $poForm->addOption($sFieldName, array('value'=> $aMember['gbuserpk'], 'label' => $aMember['firstname'].' '.$aMember['lastname'], 'selected' => 'selected'));
          }
          else
            $poForm->addOption($sFieldName, array('value'=> $aMember['gbuserpk'], 'label' => $aMember['firstname'].' '.$aMember['lastname']));

          $bRead = $oCompanyMembers->readNext();
        }
      }

    if(getValue('sendCred') && !empty($anSelected))
    {
      $oUsers = $this->_getModel()->getByWhere('gbuser');
      $bRead = $oUsers->readFirst();
      $asUsers = array();
      while($bRead)
      {
        $asUsers = $oUsers->getData();
        if(in_array($asUsers['gbuserpk'], $anSelected))
        {
          dump('send credentials to gbuser #'.$asUsers['gbuserpk'].' --> loginpk: '.$asUsers['loginfk']);
          $this->_notifyUser((int)$asUsers['loginfk']);
        }

        $bRead = $oUsers->readNext();
      }
    }

    $sCreateOneUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_TYPE_GBADMIN, $pnCompanyPk, array('datatype' => $psType, 'divToRefresh' => 'sSelect'.$psType.'Ids', 'refreshWith' => 'selectOption', 'onGroup' => $pnGroupPk));
    $sAjaxCreateOneUrl = $this->_getPopupUrl($sCreateOneUrl);

    $sCreateOne = $oHTML->getLink('+ Create New '.$psShowType, 'javascript:;', array('class' => 'goPopup', 'onClick' => $sAjaxCreateOneUrl));
    $poForm->addField('misc', 'create'.$psType, array('type' => 'text', 'text' => $sCreateOne));

    $poForm->closeSection();

    return $poForm;
  }

  private function _saveGroup($pnGroupPk, $pnCompanyPk=0)
  {
    $bIsEdition = (is_key((int)$pnGroupPk));

    if($bIsEdition)
    {
      if(!assert('is_key($pnGroupPk)'))
        return array('error' => 'We could not save the group. Id is missing. Please contact your admibistrator.');

      $oGroup = $this->_getModel()->getByPk($pnGroupPk, 'gbuser_group');
      $nCompanyPk = (int)$oGroup->getFieldValue('gbuser_companyfk');
    }
    else
    {
      if(!assert('is_key($pnCompanyPk)'))
        return array('error' => 'We could not save the group. Id is missing. Please contact your admibistrator.');

      $nCompanyPk = $pnCompanyPk;
    }

    $oPage = CDependency::getComponentByName('page');

    $sName = getValue('name', '');
    $sActive = getValue('active', '0');

    if($sActive=='on')
      $sActive='1';

    $nActive = (int)$sActive;

    if(!assert('!empty($sName)'))
      return array('error' => 'We could not save the group. Name is missing. Please contact your admibistrator.');

    $aData = array(
        'name' => $sName,
        'active' => $nActive
        );

    // ----------------------------
    // Editing Main Group Table
    // ----------------------------

    if($bIsEdition)
    {
      $aData['gbuser_grouppk'] = $pnGroupPk;

      $bUpdated = $this->_getModel()->update($aData, 'gbuser_group');
      if(!assert('($bUpdated)'))
        return array('error' => 'Group information could not be saved. Updating group name failed. Please contact your administrator.');

      $nGroupPk = (int)$pnGroupPk;
    }
    else
    {
      $aData['gbuser_companyfk']=$nCompanyPk;

      $nGroupPk = (int)$this->_getModel()->add($aData, 'gbuser_group');
      if(!assert('is_key($nGroupPk)'))
        return array('error' => 'Group information could not be saved. Adding new group. Please contact your administrator.');
    }

    // ----------------------------
    // Adding Users
    // ----------------------------

    //fetch existing users
    $asExistingMembers = array();
    if($bIsEdition)
    {
      $oStudents = $this->_getModel()->getMembers(0, 'student', 'active', false, '', array($pnGroupPk));
      $bRead = $oStudents->readFirst();
      while($bRead)
      {
        $sDate = $oStudents->getFieldValue('date_notified');
        if(!empty($sDate) && $sDate != '0000-00-00 00:00:00')
          $asExistingMembers[(int)$oStudents->getFieldValue('gbuserfk')] = $sDate;

        $bRead = $oStudents->readNext();
      }
      //dump('existing members...');
      //dump($asExistingMembers);
    }

    $aMemberData = array();
    foreach (array_keys($this->_aTypes) as $sType)
    {
      $sIds = getValue($sType.'Ids','');
      $aPostedData = array();
      if(!empty($sIds))
        $aPostedData = explode(',', $sIds);

      if(!empty($aPostedData))
      {
        foreach ($aPostedData as $sMemberId)
        {
          $aMemberData['gbuserfk'][]=(int)$sMemberId;
          $aMemberData['gbuser_groupfk'][]=(int)$nGroupPk;

          //saved previous notification date
          if(isset($asExistingMembers[(int)$sMemberId]))
            $aMemberData['date_notified'][] = $asExistingMembers[(int)$sMemberId];
          else
            $aMemberData['date_notified'][] = null;
        }
      }
    }

    if($bIsEdition)
      $this->_getModel()->deleteByFk((int)$nGroupPk, 'gbuser_group_member', 'gbuser_groupfk');

    if(!empty($aMemberData))
    {
      //dump('to be added');
      //dump($aMemberData);
      $nMembersPk = $this->_getModel()->add($aMemberData, 'gbuser_group_member');

      if(!assert('is_key($nMembersPk)'))
        return array('error' => 'An error occured. Members could not be saved. Please contact your administrator.');
    }


    // ----------------------------
    // Adding Chapter Schedule
    // ----------------------------
    $oTest = CDependency::getComponentByName('gb_test');

    if($bIsEdition)
      $oTest->deleteGroupSchedule($nGroupPk);

    $aChapters = array();
    foreach ($oTest->aChaptersData as $nChapterPk => $aChapter)
    {
      $aChapters['gbtest_chapterfk'][] = $nChapterPk;
      $aChapters['gbuser_groupfk'][] = (int)$nGroupPk;
      $aChapters['deadline'][] = getValue($nChapterPk.'_deadline', '');
    }
    $nSchedulePk = $oTest->addGroupSchedule($aChapters);

    if(!assert('is_key($nSchedulePk)'))
      return array('error' => 'An error occured. Deadlines for chapters could not be saved. Please contact your administrator.');

    $bNotify = (bool)getValue('notifyUsers', 0);

    if($bNotify)
      $nMails = $this->_notifyGroupMembers($nGroupPk, false);

    if(isset($_SESSION['groupsDataGb']))
      unset($_SESSION['groupsDataGb']);

    $sCompanyUrl = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'company'));
    $sNotice = 'Group has been saved successfully. ';

    if($bNotify)
      $sNotice .= ($nMails> 0) ? $nMails.' student(s) have been notified of the schedule.' : 'No student could be notified. Please try again.';

    return array('notice' => $sNotice, 'timedUrl' => $sCompanyUrl);
  }



  private function _notifyGroupMembers($nGroupPk, $pbAll = false)
  {
    if(!assert('is_key($nGroupPk)'))
      return 0;

    $oPage = CDependency::getCpPage();
    $oTest = CDependency::getComponentByName('gb_test');
    $oHTML = CDependency::getCpHtml();

    $oStudents = $this->_getModel()->getMembers(0, 'student', 'active', false, '', array($nGroupPk));
    $bRead = $oStudents->readFirst();
    $aSchedule = $oTest->getGroupSchedule($nGroupPk);

    //dump($aSchedule);

    $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('datatype' => 'test'));

    $nCount = 0;
    if($bRead)
    {
      $sSchedule = '';
      $sToday = date('Y-m-d');

      foreach($aSchedule as $aChapter)
      {
        //dump($aChapter);
        //if( !empty($aChapter['deadline']) && ($aChapter['deadline'] != '0000-00-00') && ($aChapter['deadline'] > date('Y-m-d')))
        if( !empty($aChapter['deadline']) && ($aChapter['deadline'] != '0000-00-00'))
        {
          $sSchedule .= $oHTML->getCR(1).'<strong>Chapter '.$aChapter['rank'].': '.$aChapter['name'].'</strong>'.$oHTML->getCR(1);

          foreach($aChapter['tests'] as $aTest)
          {
            if($aChapter['name'] == 'ESA1' || $aChapter['name'] == 'ESA2')
              $sSchedule .= 'Part '.$aTest['rank'].': '.$aTest['name'].$oHTML->getCR();
            else
              $sSchedule .= $aTest['type'].$aTest['rank'].': '.$aTest['name'].$oHTML->getCR();
          }

          if($aChapter['deadline'] < $sToday)
            $sSchedule .= '<span style="text-decoration: line-through; color: red;">
              <strong style="color: #555555;">Deadline: '.$aChapter['deadline'].'</strong></span>'.$oHTML->getCR(1);
          else
            $sSchedule .= '<strong style="color: #555555;">Deadline: '.$aChapter['deadline'].'</strong>'.$oHTML->getCR(1);
        }
      }

      $asNotified = array();

      while($bRead)
      {
        $aData = $oStudents->getData();
        //dump($aData);

        if($pbAll || empty($aData['date_notified']) || $aData['date_notified']== '0000-00-00 00:00:00')
        {
          $sContent = 'Hello '.$aData['firstname'].' '.$aData['lastname'].','.$oHTML->getCR(2);
          $sContent .= 'Please find bellow your Globus Online Coaching Schedule.'.$oHTML->getCR();
          $sContent .= 'You can log in the '.$oHTML->getLink('Globus Online Coaching', $sUrl).' platform to access all your assigments. '.$oHTML->getCR(2);
          $sContent .= $sSchedule.$oHTML->getCR(2);
          $sContent .= 'I am looking forward to working with you! '.$oHTML->getCR(3);

          $sContent .= '<span style="font-weight: bold; font-size: 13px; color:#4785C0; ">Tips</span><br />
          <span style="color: #666666; font-size: 13px;">
          You need to complete an assigment to be able to access the next one.<br />
          Make sure you submit your assignments clicking on "send answer" button. The "save answer" button only saves a draft.
          </span>'.$oHTML->getCR(3);

          //dump('send email to '.$aData['email']);

          $oMail = CDependency::getComponentByName('mail');
          $oMail->createNewEmail();
          $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
          $oMail->addRecipient($aData['email'], $aData['firstname'].' '.$aData['lastname']);
          $nMail = $oMail->send('Globus Online Coaching: Your Training Schedule', $sContent);

          if($nMail)
            $asNotified[] = (int)$aData['gbuserpk'];

          $nCount+= $nMail;
        }

        $bRead = $oStudents->readNext();
      }

      if(!empty($asNotified))
      {
        $sQuery = 'UPDATE gbuser_group_member SET date_notified = "'.date('Y-m-d H:i:s').'"
          WHERE gbuser_groupfk = '.$nGroupPk.' AND gbuserfk IN ('.implode(',', $asNotified).') ';

        $this->_getModel()->executeQuery($sQuery);
      }
    }

    return $nCount;
  }

  // Returns an HTML string of a confirmation form to delete member
  // and an empty array if it is allowed to delete the member

  private function _confirmDeleteMember($pnUserPk, $psType)
  {
    if(!assert('is_key($pnUserPk)'))
      return array('error' => 'An error occured. We could not check if this member was allowed to be deleted. Please contact your administrator.');

    if(!assert('$this->_isType($psType)'))
      return array('error' => 'An error occured. We could not check if this member was allowed to be deleted. Please contact your administrator.');

    if($psType=='hrmanager')
      return array();

    $oTest = CDependency::getComponentByName('gb_test');
    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');
    $oMember = $this->_getModel()->getUser($pnUserPk);

    $sHTML = '';
    switch ($psType)
    {
      case 'student':
        $nbPastTests = $oTest->getNbAnsweredTestsForStudent($pnUserPk);
        $nGroupFk = (int)$oMember->getFieldValue('gbuser_groupfk');
        $nbActiveTests = (is_key($nGroupFk)) ? $oTest->getNbTestsForUser($pnUserPk, (int)$oMember->getFieldValue('gbuser_groupfk')) : 0;

        if(($nbPastTests==0) && ($nbActiveTests==0))
          return array();

        $sMemberName = $oMember->getFieldValue('firstname').' '.$oMember->getFieldValue('lastname');
        $sTitle = 'Delete '.$sMemberName.'?';
        $sHTML .= $oHTML->getCR(1);
        $sContent = 'Are you sure you want to delete <strong>'.$oMember->getFieldValue('firstname').' '.$oMember->getFieldValue('lastname').'</strong>?';
        $sContent .= '<br /><br />This student has taken <strong>'.$nbPastTests.'</strong> test(s) so far and has <strong>'.$nbActiveTests.'</strong> pending test(s).';
        $sContent .= '<br /><br />If you confirm, all his/her data will be deleted from database and his/her results wont be considered for statistic pages anymore.<br /><br />';
        $sHTML .= $oHTML->getBloc('', $sContent);
        break;
      case 'teacher':
        $nbPastTests = $oTest->getNbReturnedTestsForTeacher($pnUserPk);
        $aGroupsIds = $this->_getModel()->getGroupsIdsForSupervisor($pnUserPk);
        $nbActiveGroups = count($aGroupsIds);

        if(($nbActiveGroups==0) && ($nbPastTests==0))
          return array();

        $sMemberName = $oMember->getFieldValue('firstname').' '.$oMember->getFieldValue('lastname');
        $sTitle = 'Delete '.$sMemberName.'?';
        $sHTML .= $oHTML->getCR(1);
        $sContent = 'Are you sure you want to delete <strong>'.$oMember->getFieldValue('firstname').' '.$oMember->getFieldValue('lastname').'</strong>?';
        $sContent .= '<br /><br />This trainer corrected <strong>'.$nbPastTests.'</strong> test(s) so far and is responsible of <strong>'.$nbActiveGroups.'</strong> active(s) group(s).';
        $sContent .= '<br /><br />If you confirm, all his/her corrections will be deleted from database and wont be considered for statistic pages anymore.';
        if($nbActiveGroups>0)
          $sContent .= '<br /><br />You would also need to reassign his/her group(s) to another trainer.<br /><br />';
        $sHTML .= $oHTML->getBloc('', $sContent);

        break;
    }

      $sConfirmLink = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_TYPE_GBADMIN, $this->cnPk, array('datatype' => $psType, 'confirmDelete' => '1'));
      $sAjaxRequest= 'AjaxRequest(\"'.$sConfirmLink.'\");';
      $sAction = 'goPopup.setPopupConfirm("'.$sHTML.'", "'.$sAjaxRequest.'", "", "Delete '.$sMemberName.'", "Cancel", "'.$sTitle.'", 600, 350);';

      return array('action' => $sAction);
  }


  private function _deleteMember($pnUserPk, $psType)
  {
    // TODO: test member suppression again. Handle group and company suppression

    if(!assert('is_key($pnUserPk)'))
      return array('error' => 'An error occured. User could not be deleted. Please contact your administrator.');

    if(!assert('$this->_isType($psType)'))
       return array('error' => 'An error occured. User could not be deleted. Please contact your administrator.');

    $bConfirmation = (bool)getValue('confirmDelete', 0);

    if(!$bConfirmation)
    {
      $aConfirm = $this->_confirmDeleteMember($pnUserPk, $psType);

      if(!empty($aConfirm))
      {
        return $aConfirm;
      }
    }

    if($psType=='student')
    {
      // Cleaning assessment from database
      $oTest = CDependency::getComponentByName('gb_test');
      $oTest->deleteTests($pnUserPk);
    }
    elseif ($psType=='teacher')
    {
      // Cleaning correction from database
      $oTest = CDependency::getComponentByName('gb_test');
      $oTest->deleteCorrections($pnUserPk);
    }

    $oUser = $this->_getModel()->getUser($pnUserPk);
    $oLogin = CDependency::getComponentByName('login');
    $oLogin->deleteFromExternalComponent((int)$oUser->getFieldValue('loginfk'));
    $this->_getModel()->deleteByFk($pnUserPk, 'gbuser_group_member', 'gbuser');
    $this->_getModel()->deleteByPk($pnUserPk, 'gbuser');

    return array('notice' => 'User has been removed successfully.', 'reload' => 1);
  }

  private function _saveMember($pbIsEdition, $psType='student')
  {

    $sShowType = $this->_aTypes[$psType];

    if(!assert('is_bool($pbIsEdition)'))
      return array('error' => 'An error occured. The '.$sShowType.' could not be saved. Please contact your administrator.');

    if(!assert('$this->_isType($psType)'))
      return array('error' => 'An error occured. The '.$sShowType.' could not be saved. Please contact your administrator.');

    $oLogin = CDependency::getComponentByName('login');

    $sFirstName = getValue('firstname', '');
    $sLastName = getValue('lastname', '');
    $sEmail = getValue('email', '');
    $nCompanyfk = (int)getValue('companyfk', 0);
    $nGroupfk = (int)getValue('groupfk', 0);
    $bNotify = (getValue('notify_again') == 'on');


    if($pbIsEdition)
    {
      $sStatus = getValue('status','0');
      if($sStatus=='on')
        $sStatus = '1';
      $nStatus = (int)$sStatus;
    }
    else
    {
      $nStatus = 1;
    }

    $sDivToRefresh = getValue('divToRefresh', '');
    $sRefreshWith = getValue('refreshWith', '');
    $nLoginFk = 0;
    $bMultiProfile = false;


    //************************************************************
    //check email address

    //if((!$pbIsEdition) && ($oLogin->exists('email', $sEmail, $this->cnPk)))
    //  return array('error' => 'A user using this email address already exists. Please choose another one, or edit this user.');
    if((!$pbIsEdition))
    {
      //fetch other "profiles" linked to this email address
      $asRole = $this->_getModel()->getUserByMail($sEmail);

      if(!empty($asRole))
      {
        if(isset($asRole[$psType]))
          return array('error' => 'This email address is already in use for a ['.$psType.']. Please input a different one, or edit the other account.');

        $nLoginFk = array_first($asRole);
        $bMultiProfile = true;
      }
    }

    if(!assert('!empty($sLastName)'))
      return array('error' => 'User information could not be saved. Last name is missing.');

    if(!assert('!empty($sFirstName)'))
      return array('error' => 'User information could not be saved. First name is missing.');



    // ***************************
    // Editing LOGIN TABLE
    // ***************************
    $aDataForLogin = array(
        'email' => $sEmail,
        'firstname' => $sFirstName,
        'lastname' => $sLastName,
        'position' => ucfirst($psType),
        'status' => $nStatus,
        'loginpk' => $nLoginFk
    );

    if($pbIsEdition)
    {
      $oUser = $this->_getModel()->getByPk($this->cnPk, 'gbuser');
      $nLoginFk = (int)$oUser->getFieldValue('loginfk');

      if(!assert('is_key($nLoginFk)'))
        return array('error' => 'We could not find the existing credentials for this user. Please contact your administrator.');

      $aDataForLogin['loginpk'] = $nLoginFk;
    }

    //if(empty($nLoginFk))
      $nLoginFk = (int)$oLogin->saveFromExternalComponent($aDataForLogin, $pbIsEdition);

    if($nLoginFk == -1)
      return array('error' => 'A member using this email address already exists.');

    if(!assert('is_key($nLoginFk)'))
      return array('error' => 'Member information could not be saved. Saving credentials failed. Please contact your administrator.');

    if(!$pbIsEdition || $bMultiProfile)
    {
      $nGroupMemberFk = $oLogin->saveGroupFromExternalComponent($psType, $nLoginFk);

      if(!assert('is_key($nGroupMemberFk)'))
        return array('error' => 'Member information could not be saved. Saving group failed. Please contact your administrator.');
    }


    // ***************************
    // Editing GBUSER main table
    // ***************************
    $aData = array(
        'loginfk' => $nLoginFk,
        'gbuser_companyfk' => $nCompanyfk,
        'type' => $psType
    );

    if(!$pbIsEdition)
    {
      $nGbUserPk = $this->_getModel()->add($aData, 'gbuser');
      if(!assert('is_key($nGbUserPk)'))
        return array('error' => 'Student information could not be saved. Creating account failed. Please contact your administrator.');
    }
    else
    {
      $nGbUserPk = $this->cnPk;
      $aData['gbuserpk'] = $nGbUserPk;
      $bUserUpdated = $this->_getModel()->update($aData, 'gbuser');

      if(!assert('($bUserUpdated)'))
        return array('error' => 'Student information could not be saved. Update account failed. Please contact your administrator.');
    }


    // ***************************
    // Editing GROUP table
    // ***************************

    if(is_key($nGroupfk))
    {
      $aDataGroupMember = array(
          'gbuserfk' => $nGbUserPk,
          'gbuser_groupfk' => $nGroupfk
      );

      if(!$pbIsEdition)
      {
        $nGroupMemberPk = $this->_getModel()->add($aDataGroupMember, 'gbuser_group_member');

        if(!assert('is_key($nGroupMemberPk)'))
          return array('error' => 'User group could not be saved. It might have been deleted while you were filling the form. Try again and contact your administrator if the problem persists.');
      }
      else
      {
        $bGroupMemberUpdated = $this->_getModel()->update($aDataGroupMember, 'gbuser_group_member', 'gbuserfk='.$nGbUserPk);

        if(!assert('($bGroupMemberUpdated)'))
          return array('error' => 'User group could not be saved. Update failed. Please contact your administrator.');
      }
    }
    else
    {
      $this->_getModel()->deleteByFk($nGbUserPk, 'gbuser_group_member', 'gbuser');
    }

    if(isset($_SESSION['usersDataGb']))
      unset($_SESSION['usersDataGb']);


    if(!$pbIsEdition || $bNotify)
      $nMail = $this->_notifyUser($nLoginFk);

    if((!empty($sDivToRefresh)) && ($sRefreshWith=='selectOption'))
    {
      $sAction = "addOption('".$nGbUserPk."', '".$sFirstName." ".$sLastName."', '".$sDivToRefresh."', true);  goPopup.removeByType('layer');";
      return array('action' => $sAction);
    }


    $oPage = CDependency::getComponentByName('page');
    if(is_key($nCompanyfk))
      $sRedirectUrl = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_GBADMIN, $nCompanyfk, array('datatype' => 'company'));
    else
      $sRedirectUrl = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0, array('datatype' => $psType));

    if(!$pbIsEdition)
    {
      if(!is_key($nMail))
       return array('error' => $sFirstName.' '.$sLastName.' has been saved successfully. But he/she could not be notified by email.', 'timedUrl' => $sRedirectUrl);
    }

    return array('notice' => $sFirstName.' '.$sLastName.' has been saved successfully.', 'timedUrl' => $sRedirectUrl);
  }

  //Sends a user his credentials
  private function _notifyUser($pnLoginFk)
  {
    if(!assert('is_key($pnLoginFk)'))
      return '';

    $oLogin = CDependency::getComponentByName('login');
    $aUserData = $oLogin->getUserDataByPk($pnLoginFk);

    $oMail = CDependency::getComponentByName('mail');
    $oMail->createNewEmail();
    $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
    $oMail->addRecipient($aUserData['email'], $aUserData['firstname'].' '.$aUserData['lastname']);
    $oMail->addBCCRecipient(CONST_DEV_EMAIL, 'BC Media Support');
    $oMail->addBCCRecipient('admin@globusjapan.com', 'admin@globusjapan.com');

    $sTitle = 'Globus Account details';

   /*$sContent = 'Hello '.$aUserData['firstname'].' '.$aUserData['lastname'].','.$oHTML->getCR(2);
    $sContent.= 'Please use below user name and password to login to your <a href="'.CONST_CRM_DOMAIN.'" target="_blank">Globus Online Coaching</a> account. Your schedule and instruction will be sent to you shortly. '.$oHTML->getCR(2);

    $sContent.= 'Login: '.$aUserData['id'].'<br />Password: '.$aUserData['password'].$oHTML->getCR(2);
    $sContent.= 'Platform url: <a href="'.CONST_CRM_DOMAIN.'" target="_blank">'.CONST_CRM_DOMAIN.'</a>'.$oHTML->getCR(2);*/
    $sContent = '<strong>WELCOME TO GLOBUS ESA & ONLINE COACHING SYSTEM!</strong><br />
<br />
Please use below Username and Password to login to your account.<br />
Log In URL : <a href="https://online.globusjapan.com">https://online.globusjapan.com</a><br />
<br />
下記のユーザーネームとパスワードで自分のアカウントにログインして下さい。<br />
ログインURL: <a href="https://online.globusjapan.com">https://online.globusjapan.com</a><br />
<br />
Login: '.$aUserData['id'].'<br />Password: '.$aUserData['password'].'<br />
<br /><br />
Any system issues, please contact Globus Consulting: <a href="mailto:contact@globusjapan.com">contact@globusjapan.com</a><br />
システムに問題がありましたら、Globus Consultingまでご連絡下さい：<a href="mailto:contact@globusjapan.com">contact@globusjapan.com</a><br /><br />';

    return $oMail->send($sTitle, $sContent);
  }

  private function _saveCompany($pbInAjax = false, $pnCompanyPk = 0)
  {
    if(!assert('is_bool($pbInAjax)'))
      return array('error' => 'Company could not be saved. Please contact your administrator.');

    if(!assert('is_numeric($pnCompanyPk)'))
      return array();

    $sName = getValue('name', '');
    $nIndustryfk = (int)getValue('industryfk', 0);
    $nNationFk = (int)getValue('nationalityfk', 0);
    $bIsEdition = (bool)getValue('isEdition', false);

    if(!is_key($nIndustryfk))
      return array('error' => 'Company could not be saved. Please choose an industry.');

    if(!is_key($nNationFk))
      return array('error' => 'Company could not be saved. Please choose a country.');

    if($bIsEdition)
    {
      if(!assert('is_key($pnCompanyPk)'))
        return array();
        $nCompanyPk = $pnCompanyPk;
    }

    $oPage = CDependency::getComponentByName('page');

    if(!assert('is_string($sName) && !empty($sName)'))
      return array('error' => 'Company could not be saved. Name is missing.');

    $aData = array(
      'name' => $sName,
      'industryfk' => $nIndustryfk,
      'nationalityfk' => $nNationFk
    );

    if($bIsEdition)
    {
      $sActive = getValue('active', '0');

      if($sActive=='on')
        $sActive='1';

      $nActive = (int)$sActive;

      $aData['gbuser_companypk'] = $pnCompanyPk;
      $aData['active'] = $nActive;
      $bUpdated = $this->_getModel()->update($aData, 'gbuser_company');

      if(!assert('$bUpdated'))
        return array('error' => 'Company could not be updated. Please contact your administrator.');

      $sUrlRedirect = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'company'));
    }
    else
    {
      $nCompanyPk = $this->_getModel()->add($aData, 'gbuser_company');

      if(!assert('is_key($nCompanyPk)'))
        return array('error' => 'Company could not be added. Please contact your administrator.');

      $sUrlRedirect = $oPage->getUrl($this->csUid, CONST_ACTION_ADD, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'group'));
    }

    $this->_loadCompaniesData(true);

    if($pbInAjax && $bIsEdition)
    {
      $sRefreshUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'company'));
      $sAjaxRequest = " $('#linkCompany_".$nCompanyPk." > a').html('".$sName."'); goPopup.removeByType('layer'); AjaxRequest('".$sRefreshUrl."', true, '', 'company_".$nCompanyPk."'); ";
      return array('action' => $sAjaxRequest);
    }

    return array('notice' =>  'Company has been saved successfully.', 'timedUrl' => $sUrlRedirect);
  }

  public function displayCompanyLink($pnCompanyPk, $pbOnlyUrl = false, $psView = 'manage')
  {
    $aViews = array('manage', 'tests');
    if(!assert('in_array($psView, $aViews)'))
      return '';

    if(!assert('is_bool($pbOnlyUrl)'))
      return '';

    if(!assert('is_key($pnCompanyPk)'))
      return '';

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    switch($psView)
    {
      case 'manage':
        $sUrl = $oPage->getUrl('196-001', CONST_ACTION_VIEW, $oPage->getType(), $pnCompanyPk, array('datatype' => 'company'));
      break;
      case 'tests':
        $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, $oPage->getType(), $pnCompanyPk, array('datatype' => 'all', 'filter' => 'company'));
      break;
    }

    if($pbOnlyUrl)
      return $sUrl;

    if(!empty($this->aCompaniesData[$pnCompanyPk]))
    {
      $sLabel = $this->aCompaniesData[$pnCompanyPk]['name'];
    }
    else
    {
      $aCompany = $this->getCompany($pnCompanyPk);
      $sLabel = $aCompany['name'];
    }
    $sLink = $oHTML->getLink($sLabel, $sUrl);

    return $sLink;
  }

  public function displayGroupLink($pnGroupPk, $pbOnlyUrl = false, $psView = 'tests')
  {
    $aViews = array('tests', 'manage');
    if(!assert('in_array($psView, $aViews)'))
      return '';

    if(!assert('is_key($pnGroupPk)'))
      return '';

    if(!assert('is_bool($pbOnlyUrl)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');

    switch($psView)
    {
      case 'tests':
        $sGroupUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, $oPage->getType(), $pnGroupPk, array('datatype' => 'all', 'filter' => 'group'));
      break;
      case 'manage':
        $sGroupUrl = $oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, $pnGroupPk, array('datatype' => 'group'));
      break;
    }

    if($pbOnlyUrl)
      return $sGroupUrl;

    if(isset($this->aGroupsData[$pnGroupPk]))
      $sGroupName = $this->aGroupsData[$pnGroupPk]['name'];
    else
    {
      $oGroup = $this->_getModel()->getByPk($pnGroupPk, 'gbuser_group');
      $sGroupName = $oGroup->getFieldValue('name');
    }

    if(strlen($sGroupName)> $this->_nMaxNameSize)
      $sGroupName = substr($sGroupName, 0, strlen($this->_nMaxNameSize)-3).'...';

    $sGroup = $oHTML->getLink($sGroupName, $sGroupUrl);
    return $sGroup;
  }

  public function displayMemberLink($pnMemberPk, $psMemberType = 'student', $pbOnlyUrl = false, $psView = 'tests')
  {
    $aViews = array('tests', 'manage', 'remove');
    if(!assert('in_array($psView, $aViews)'))
      return '';

    if(!assert('is_key($pnMemberPk)'))
      return '';

    if(!assert('is_bool($pbOnlyUrl)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');

    switch($psView)
    {
      case 'tests':
        $sStudentUrl = $oPage->getUrl('196-002', CONST_ACTION_VIEW, $oPage->getType(), $pnMemberPk, array('datatype' => 'all', 'filter' => $psMemberType));
      break;
      case 'manage':
        $sStudentUrl = $oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, $pnMemberPk, array('datatype' => $psMemberType));
      break;
      case 'remove':
        $sStudentUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_TYPE_GBADMIN, $pnMemberPk, array('datatype' => $psMemberType));
        break;
    }

    if($pbOnlyUrl)
      return $sStudentUrl;

    $sName = $this->getName($pnMemberPk, true);

    $sStudent = $oHTML->getLink($sName, $sStudentUrl);
    return $sStudent;
  }

  public function getUserPk()
  {
    // BC Media admin or Globus Admins dont have a gbuserpk, just a loginpk
    if(isset($this->aUserData['gbuserpk']))
      return (int)$this->aUserData['gbuserpk'];
    else
      return 0;
  }

  public function getUser($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return array();

    if(isset($this->aUsersData[$pnUserPk]))
      return $this->aUsersData[$pnUserPk];
    else
    {
      $oUser = $this->_getModel()->getUser($pnUserPk);
      $aUser = $oUser->getData();
      return $aUser;
    }
  }

  public function getCompany($pnCompanyPk)
  {
    if(!assert('is_key($pnCompanyPk)'))
      return array();

    if(isset($this->aCompaniesData[$pnCompanyPk]))
      return $this->aCompaniesData[$pnCompanyPk];
    else
      return $this->_getModel()->getCompany($pnCompanyPk);
  }

  public function getGroup($pnGroupPk)
  {
    if(!assert('is_key($pnGroupPk)'))
      return array();

    if(isset($this->aGroupsData[$pnGroupPk]))
      return $this->aGroupsData[$pnGroupPk];
    else
    {
      $oGroup = $this->_getModel()->getByPk($pnGroupPk, 'gbuser_group');
      $oGroup->readFirst();
      $aGroup = $oGroup->getData();
      return $aGroup;
    }
  }

  public function getName($pnUserPk, $pbShortName = false, $paGivenName = array())
  {
    if(empty($paGivenName))
    {
      if(!assert('is_key($pnUserPk)'))
        return '';

      if(isset($this->aUsersData[$pnUserPk]))
      {
        $sFirstName = $this->aUsersData[$pnUserPk]['firstname'];
        $sLastName = $this->aUsersData[$pnUserPk]['lastname'];
      }
      else
      {
        $oMember = $this->_getModel()->getUser($pnUserPk);
        $sFirstName = $oMember->getFieldValue('firstname');
        $sLastName = $oMember->getFieldValue('lastname');
      }
    }
    else
    {
      $sFirstName = $paGivenName['firstname'];
      $sLastName = $paGivenName['lastname'];
    }

    if(empty($sFirstName) && empty($sLastName))
    {
      $sFirstName = $this->aUserData['firstname'];
      $sLastName = $this->aUserData['lastname'];
    }

    if(!assert('!empty($sFirstName) && !empty($sLastName)'))
      return '';

    if(!$pbShortName)
      return $sFirstName.' '.$sLastName;
    else
    {
      $oLogin = CDependency::getComponentByName('login');
      return $oLogin->getFormatedUserNameFromData($sFirstName, $sLastName, $this->_nMaxNameSize);
    }
  }

  public function getGroupName($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return '';

    return $this->aUsersData[$pnUserPk]['group_name'];
  }

  public function getCompanyName($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return '';

    return $this->aUsersData[$pnUserPk]['company_name'];
  }

  public function getUserType()
  {
    if(isset($this->aUserData['gbusertype']))
      return $this->aUserData['gbusertype'];

    if($this->csMode == 'cron')
      return 'gbadmin';

      return '';
  }

  public function getUserGroupPkByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return 0;

    $aUser = $this->getUser($pnPk);

    if(isset($aUser['gbuser_groupfk']) && is_key((int)$aUser['gbuser_groupfk']))
      return (int)$this->aUsersData[$pnPk]['gbuser_groupfk'];
    else
      return 0;
  }

  public function getUserGroupPk()
  {
    if(isset($this->aUserData['gbuser_grouppk']) && is_key((int)$this->aUserData['gbuser_grouppk']))
      return (int)$this->aUserData['gbuser_grouppk'];
    else
      return 0;
  }

  public function getStudentsForTeacher($pnTeacherFk)
  {
    if(!assert('is_key($pnTeacherFk)'))
      return array();

    return $this->_getModel()->getStudentsForTeacher($pnTeacherFk);
  }

  public function getStudentsEmailsForGroup($pnGroupFk)
  {
    return $this->_getModel()->getStudentsEmailsForGroup($pnGroupFk);
  }

  public function getStudentsIdsForGroup($pnGroupFk)
  {
    return $this->_getModel()->getStudentsIdsForGroup($pnGroupFk);
  }

  public function getStudentsIdsForCompany($pnCompanyPk)
  {
    return $this->_getModel()->getStudentsIdsForCompany($pnCompanyPk);
  }

  public function getGroupsForCompany($pnCompanyFk)
  {
    if(!assert('is_key($pnCompanyFk)'))
      return array();

    return $this->_getModel()->getByFk($pnCompanyFk, 'gbuser_group', 'gbuser_company');
  }

  public function getGroupsForTeacher($pnTeacherFk)
  {
    if(!assert('is_key($pnTeacherFk)'))
      return array();

    return $this->_getModel()->getGroupsForTeacher($pnTeacherFk);
  }

  private function _getHelpPage()
  {
    $oHTML = CDependency::getCpHtml();
    $sHTML= $oHTML->getTitle('WELCOME TO GLOBUS ONLINE COACHING !');

    $sHTML.= $oHTML->getBloc('', '<br />
オンラインコーチングはEmail Logic+3の教材に沿った添削学習コースです。下記の流れで進めて下さい。<br />
<br />
ESA & オンラインコーチングの流れ：<br />
・本メールに記載されているユーザーネームとパスワードを使用してログインしてください。<br />
・自分のアカウントページで課題と期日の確認をして下さい。<br />
・事前アセスメント(ESA1)が設定されている場合にはコーチングの課題に進む前にESA1から始めて下さい。<br />
・1週間に2つの課題を期限までに提出して下さい。<br />
・提出された課題は講師の添削・フイードバックを受けて、次の提出期日まで返信されます。フィードバックを踏まえて、次の週の課題に取り組んで下さい。<br />
・設定された全部の課題提出・添削受信後に事後アセスメント(ESA2)がリリースされます。自分のアカウントページで期日を確認し、期日までに提出して下さい。<br />
<br />
ESA (Email Skills Assessment) について：<br />
ESAはグローバルeメールライティングスキルを計るツールです。<br />
設定された2つのシナリオに対してメールを英語で書いて下さい。スタートした時間から送信ボタンを押すまでの時間をタイマーで計っています。時間制限はありませんが、2つのメールを1度で書き上げるようにして下さい。提出してから1週間〜10日でESAの結果が出ます。自分のアカウントページからアクセスして結果を確かめて下さい。特に2ページ目のRecommendationをしっかりと読み、その後の研修／実務に活かして下さい。<br />
<br />
システムに問題、または不明な点がありましたら、Globus Consultingまでご連絡ください：<br />
<br />
contact@globusjapan.com<br />
03 5467 7701<br /><br />', array('style' => 'padding: 10px;  margin: 15px 0;'));

    return $sHTML;
  }

}
