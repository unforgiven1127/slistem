<?php

require_once('component/portal/portal.class.ex.php5');

class CPortalGlobusEx extends CPortalEx
{

  private $_bIsAdmin;
  private $_aUserData;
  private $_aGroupData;

  public function __construct()
  {
    $oLogin = CDependency::getComponentByName('login');
    $this->_bIsAdmin = $oLogin->isAdmin();

    //load gbUser to get user details loaded in session
    $oUser = CDependency::getComponentByName('gb_user');

    if($oLogin->isLogged())
    {
      $sToggleprofile = getValue('togpro');
      $bRefresh = !empty($sToggleprofile);

      if(!$bRefresh && isset($_SESSION['gb_user_data']))
      {
        $this->_aUserData = $_SESSION['gb_user_data'];
        $this->_aGroupData = $_SESSION['gb_group_data'];
      }
      else
      {
        //dump('Portal: load/refrsh session');
        $this->_aUserData = $oLogin->getUserData();

        //add gbUserData to login profile
        foreach($_SESSION['userData']['gbData'] as $sVar => $vValue)
          $this->_aUserData[$sVar] = $vValue;


        if(isset($this->_aUserData['login_groupfk']))
        {
          $nGroupFk = (int)$this->_aUserData['login_groupfk'];

          if(is_key($nGroupFk))
            $this->_aGroupData = $oLogin->getGroupByPk($nGroupFk);
          else
            $this->_aGroupData = array();
        }

        $_SESSION['gb_user_data'] = $this->_aUserData;
        $_SESSION['gb_group_data'] = $this->_aGroupData;
      }

      //dump($this->_aUserData);
    }
    else
      unset($_SESSION['gb_user_data']);
  }

  public function getHomePage()
  {
    $oPage = CDependency::getComponentByName('page');
    $sUrl = '';

    if($this->_bIsAdmin)
    {
      $sUrl = $oPage->getUrl('665-544', CONST_ACTION_ADD, CONST_TYPE_SETTINGS, 0);
    }
    else
    {
      if(empty($this->_aGroupData))
        return 'You don\'t belong to any existing group. Please contact the software administrator.';

      //switch($this->_aUserData['shortname'])
      switch($this->_aUserData['gbusertype'])
      {
        case 'student':
          $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('datatype' => 'test'));
          break;
        case 'hrmanager':
          $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_HRMANAGER, 0);
          break;
        case 'teacher':
          $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_TEACHER, 0, array('filter' => 'teacher'));
          break;
        case 'gbadmin':
          $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0);
          break;
      }
    }

    if(empty($sUrl))
      return 'No homepage was found. Please contact your administrator';

    return $oPage->redirect($sUrl);
  }

  public function getPageHTML($psComponentHtml, $pbIsLogged)
  {
    if(!assert('is_string($psComponentHtml) && !empty($psComponentHtml)'))
      return '';

    if(!assert('is_bool($pbIsLogged)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oPage->addCssFile($this->getResourcePath().'css/globus.css');

    $oHTML = CDependency::getComponentByName('display');
    $sHTML = '';

    $sHTML .= $this->_displayHeader($pbIsLogged);
    $sHTML .= $oHTML->getBlocStart('mainBlocId');
      $sHTML.= $this->_displayMenu($pbIsLogged);
      $sHTML.= $psComponentHtml;
    $sHTML .= $oHTML->getBlocEnd();
    $sHTML.= $oHTML->getFooter();

    return $sHTML;
  }

  public function _displayHeader($pbIsLogged = false)
  {
    if(!assert('is_bool($pbIsLogged)'))
      return '';

    $sHTML = '';

    if($pbIsLogged)
    {
      $oHTML = CDependency::getComponentByInterface('do_html');
      $oPage = CDependency::getCpPage();

      $sHTML.= $oHTML->getBlocStart('gbHeaderId');
      $sHTML .= $oHTML->getPicture($this->getResourcePath().'pictures/globus/logo_white_small.png', CONST_WEBSITE, '/', array('id' => 'logoHeader'));
      $sHTML .= $oHTML->getUserMenuBloc($pbIsLogged);


      if(count($this->_aUserData['group']) > 1)
      {
        if($this->_aUserData['gbusertype'] == 'student')
        {
          $sSwitchTo = 'hrmanager';
          $sSwitchLabel = 'HR manager';
          //uid=196-002&ppa=ppal&ppt=hrmn&ppk=5&datatype=all&filter=company
          $sURL = $oPage->getURL('196-002', CONST_ACTION_LIST, CONST_TYPE_HRMANAGER, $this->_aUserData['gbuser_companyfk'], array('togpro' => $sSwitchTo, 'datatype' => 'all', 'filter' => 'company'));
        }
        else
        {
          $sSwitchTo = 'student';
          $sSwitchLabel = 'student';
          $sURL = $oPage->getURL('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('togpro' => $sSwitchTo, 'datatype' => 'test'));
        }

        $sHTML.= '<div class="switch_profile">
          <a href="'.$sURL.'" style="color: #fff;">
            <img src="/component/globus/gb_user/resources/pictures/switch_profile.png" /> switch to your '.$sSwitchLabel.' profile</a>
         </div>';
      }

      $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getFloatHack();
    }

    return $sHTML;
  }

  // Prints menu HTML

  private function _displayMenu($pbIsLogged)
  {
    if(!assert('is_bool($pbIsLogged)'))
      return '';

    $sHTML = '';
    if($pbIsLogged)
    {
      $aMenuData = $this->_getMenuData();
      $oHTML = CDependency::getComponentByInterface('do_html');

      $sHTML .= $oHTML->getBlocStart('menuLeftId');

      /*  $sHTML .= $oHTML->getBlocStart('logoLeft');
        $sHTML .= $oHTML->getPicture($this->getResourcePath().'pictures/globus/logo_white_small.png', CONST_WEBSITE, '/', array('id' => 'logoHeader'));
        $sHTML .= $oHTML->getBlocEnd();
        */
        $sHTML .= $oHTML->getBlocStart('menusContainerId');

        if(!empty($aMenuData))
        {
          $sHTML .= $oHTML->getListStart('Menu', array('class' => 'menuLeft'));
          foreach ($aMenuData as $aListItem)
          {
            $sHTML .= $oHTML->getListItemStart('', $aListItem['params']);
            $sHTML .= $oHTML->getLink($aListItem['label'], $aListItem['link']);
            $sHTML .= $oHTML->getListItemEnd();
          }
          $sHTML .= $oHTML->getListEnd();
        }

        $sHTML .= $oHTML->getBlocEnd();
   //   $sHTML .= $oHTML->getUserMenuBloc($pbIsLogged);
      $sHTML .= $oHTML->getBlocEnd();
    }

    return $sHTML;
  }

  // Retrieves menu data from component that declare some

  private function _getMenuData()
  {
    $oPage = CDependency::getCpPage();

    $sDataType = getValue('datatype', 'all');
    $sFilter = getValue('filter', 'all');

    $aMenu = array();

    if(!$this->_bIsAdmin)
    {
      //dump($this->_aUserData);
      //_live_dump($this->_aUserData['gbusertype']);
      switch($this->_aUserData['gbusertype'])
      {
        case 'student':
          $aMenu = $this->_getStudentMenu();
          break;
        case 'teacher':
          $aMenu = $this->_getTeacherMenu();
          break;
        case 'hrmanager':
          $aMenu = $this->_getHrManagerMenu();
          break;
        case 'gbadmin':
          $aMenu = $this->_getGbAdminMenu();
          break;
      }

      /*$sURL = $oPage->getUrl('196-001', CONST_ACTION_VIEW, 'help');
      $asURL = $oPage->getUrlDetail();
      if($asURL['query'] == 'uid=196-001&ppa=ppav&ppt=help&ppk=0')
        $sClass = 'selected';
      else
        $sClass = '';

      $aMenu['196-002_'.CONST_ACTION_VIEW.'_HELP'] = array(
            'label' => 'Instructions',
            'link' => $sURL,
            'params' => array('class' => 'top icohelp '.$sClass)
            );*/

      $sSelect1 = $oPage->getUid().'_'.$oPage->getAction().'_'.$sDataType;
      $sSelect2 = $sSelect1.'_'.$oPage->getPk();
      $sSelect3 = $sSelect2.'_'.$sFilter;

      if (isset($aMenu[$sSelect3]))
        $aMenu[$sSelect3]['params']['class'].=' selected';
      else
      {
        if (isset($aMenu[$sSelect2]))
          $aMenu[$sSelect2]['params']['class'].=' selected';
        else
        {
          if (isset($aMenu[$sSelect1]))
            $aMenu[$sSelect1]['params']['class'].=' selected';
        }
      }

    }
    else
    {
//      TODO: Menu for admins
    }

    return $aMenu;
  }

  private function _getStudentMenu()
  {
    $oTest = CDependency::getComponentByName('gb_test');
    $oUser = CDependency::getComponentByName('gb_user');
    $aNbTests = $oTest->getNbTestsForUser((int)$oUser->aUserData['gbuserpk'], (int)$oUser->aUserData['gbuser_grouppk']);
    $oPage = CDependency::getComponentByName('page');

    $sLabelTest = 'Assignments';
    if($aNbTests[0]>0)
      $sLabelTest .=' <span>'.$aNbTests[0].'</span>';

    $sLabelEsa = 'Assessments';
    if($aNbTests[1]>0)
      $sLabelEsa .=' <span>'.$aNbTests[1].'</span>';

    $aMenu=array(
        '196-002_'.CONST_ACTION_LIST.'_test' => array(
            'label' => $sLabelTest,
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('datatype' => 'test')),
            'params' => array('class' => 'top icotest')
            ),
        '196-002_'.CONST_ACTION_LIST.'_test_0_active' => array(
            'label' => 'Active assignments',
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('filter' => 'active', 'datatype' => 'test')),
            'params' => array('class' => 'sub')
        ),
        '196-002_'.CONST_ACTION_LIST.'_test_0_completed' => array(
            'label' => 'Completed assignments',
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('filter' => 'completed', 'datatype' => 'test')),
            'params' => array('class' => 'sub')
        ),
        '196-002_'.CONST_ACTION_LIST.'_test_0_returned' => array(
            'label' => 'Returned assignments',
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('filter' => 'returned', 'datatype' => 'test')),
            'params' => array('class' => 'sub')
        ),
        '196-002_'.CONST_ACTION_LIST.'_esa' => array(
            'label' => $sLabelEsa,
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0, array('datatype' => 'esa')),
            'params' => array('class' => 'top icoesa')
            )
    );

    return $aMenu;
  }

  private function _getTeacherMenu()
  {
    $oUser = CDependency::getComponentByName('gb_user');
    $oTest = CDependency::getComponentByName('gb_test');
    $aStudentIds = array_keys($oUser->aUsersData);

    //_live_dump($aStudentIds, 'Users data');
    //_live_dump($oUser->aUsersData, 'student ids');

    $nNbTests = 0;
    if(!empty($aStudentIds))
      $nNbTests = $oTest->getNbTestsForTeacher($aStudentIds);

    //_live_dump($nNbTests, 'nb inbox');
    $oPage = CDependency::getComponentByName('page');

    $sLabel = 'Inbox';
    if($nNbTests>0)
      $sLabel .=' <span>'.$nNbTests.'</span>';

    $aMenu=array(
        '196-002_'.CONST_ACTION_LIST.'_all_0_teacher' => array(
            'label' => $sLabel,
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_TEACHER, 0, array('filter' => 'teacher')),
            'params' => array('class' => 'top icomail')
            ),
        '196-002_'.CONST_ACTION_LIST.'_test_0_teacher' => array(
            'label' => 'Assignments',
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_TEACHER, 0, array('datatype' => 'test', 'filter' => 'teacher')),
            'params' => array('class' => 'sub')
        ),
        '196-002_'.CONST_ACTION_LIST.'_esa_0_teacher' => array(
            'label' => 'Assessments',
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_TEACHER, 0, array('datatype' => 'esa', 'filter' => 'teacher')),
            'params' => array('class' => 'sub')
        )
    );

    $aMenub = $this->_getSupervisorTestsMenu(CONST_TYPE_TEACHER);

    return array_merge($aMenu, $aMenub);
  }

  private function _getHrManagerMenu()
  {
    $oPage = CDependency::getComponentByName('page');
    $oUser = CDependency::getComponentByName('gb_user');

    $nNbStudents = count($oUser->aUsersData);
    $aGroups = $oUser->aGroupsData;
    $nCompanyPk = (int)$oUser->aUserData['gbuser_companyfk'];


    if(!is_key($nCompanyPk))
      return array();

    /*if(empty($aGroups))
      return array();*/

    $aMenu=array(
        '196-002_'.CONST_ACTION_LIST.'_all' => array(
            'label' => 'Participants <span>'.$nNbStudents.'</span>',
            'link' => $oUser->displayCompanyLink($nCompanyPk, true, 'tests'),
            'params' => array('class' => 'top icostudent')
            )
    );

    foreach($aGroups as $aGroup)
    {
      $aMenu['196-002_'.CONST_ACTION_LIST.'_all_'.$aGroup['gbuser_grouppk'].'_group'] = array(
          'label' => $aGroup['name'],
          'link' => $oUser->displayGroupLink((int)$aGroup['gbuser_grouppk'], true),
          'params' => array('class' => 'sub')
      );

      $bIsStudentTests = $oPage->isPage('196-002', CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, 0, array('filter' => 'student'));

      if($bIsStudentTests)
      {
        $nStudentPk = $oPage->getPk();

        if($oUser->getUserGroupPkByPk($nStudentPk) == $aGroup['gbuser_grouppk'])
        {
          $aMenu['student_'.$nStudentPk] = array(
              'label' => $oUser->getName($nStudentPk, true),
              'link' => $oUser->displayMemberLink((int)$nStudentPk, 'student', true),
              'params' => array('class' => 'sub second-level selected')
          );
        }
      }
    }

    $aMenu['196-002_'.CONST_ACTION_VIEW.'_stats'] = array(
      'label' => 'Statistics',
      'link' => $oPage->getUrl('196-002', CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, 0, array('datatype' => 'stats')),
      'params' => array('class' => 'top icostats')
    );
    $aMenu['196-002_'.CONST_ACTION_VIEW.'_vs'] = array(
      'label' => 'ESA1 vs ESA2',
      'link' => $oPage->getUrl('196-002', CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, 0, array('datatype' => 'vs')),
      'params' => array('class' => 'sub second-level')
    );
    $nEsa = getValue('esa', 0);
    $sClass1 = ($nEsa==1) ? ' selected' : '';
    $aMenu['196-002_'.CONST_ACTION_VIEW.'_average_esa1'] = array(
      'label' => 'ESA1: You vs Average',
      'link' => $oPage->getUrl('196-002', CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, 0, array('datatype' => 'average', 'esa' => 1)),
      'params' => array('class' => 'sub second-level'.$sClass1)
    );
    $sClass2 = ($nEsa==2) ? ' selected' : '';
    $aMenu['196-002_'.CONST_ACTION_VIEW.'_average_esa2'] = array(
      'label' => 'ESA2: You vs Average',
      'link' => $oPage->getUrl('196-002', CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, 0, array('datatype' => 'average', 'esa' => 2)),
      'params' => array('class' => 'sub second-level'.$sClass2)
    );
    return $aMenu;
  }

  private function _getSupervisorTestsMenu($psUserType)
  {
    $oPage = CDependency::getComponentByName('page');
    $nPagePk = $oPage->getPk();
    $oUser = CDependency::getComponentByName('gb_user');

    $sUserType = $psUserType;
    $aMenua = array(
        '196-002_'.CONST_ACTION_LIST.'_all_0' => array(
            'label' => 'Participants',
            'link' => $oPage->getUrl('196-002', CONST_ACTION_LIST, $sUserType, 0, array('datatype' => 'all')),
            'params' =>  array('class' => 'top icostudent')
        ));

    $bIsCompanyTests = $oPage->isPage('196-002', CONST_ACTION_LIST, $sUserType, 0, array('filter' => 'company'));
    $bIsGroupTests = $oPage->isPage('196-002', CONST_ACTION_LIST, $sUserType, 0, array('filter' => 'group'));
    $bIsStudentTests = $oPage->isPage('196-002', CONST_ACTION_VIEW, $sUserType, 0, array('filter' => 'student'));
    $bHighlighted = ($bIsCompanyTests || $bIsGroupTests || $bIsStudentTests);

    if($bHighlighted)
    {
      if($bIsStudentTests)
      {
        $aStudent = $oUser->getUser($nPagePk);
        $nCompanyPk = (int)$aStudent['gbuser_companyfk'];
        $aGroup = $oUser->getGroup((int)$aStudent['gbuser_groupfk']);
        $nGroupPk = (int)$aGroup['gbuser_grouppk'];
        $nStudentPk = $nPagePk;
      }
      elseif($bIsGroupTests)
      {
        $aGroup = $oUser->getGroup($nPagePk);
        $nCompanyPk = (int)$aGroup['gbuser_companyfk'];
        $nGroupPk = $nPagePk;
      }
      else
      {
        $nCompanyPk = $nPagePk;
      }
      $aCompany = $oUser->getCompany($nCompanyPk);

      $aMenua['196-002_'.CONST_ACTION_LIST.'_all_'.$nCompanyPk.'_company'] = array(
          'label' => $aCompany['name'],
          'link' => $oUser->displayCompanyLink($nCompanyPk, true, 'tests'),
          'params' => array('class' => 'sub', 'id' => 'linkCompany_'.$nCompanyPk)
      );

      if($bIsGroupTests || $bIsStudentTests)
      {
        $aMenua['196-002_'.CONST_ACTION_LIST.'_all_'.$nGroupPk.'_group'] = array(
            'label' => $aGroup['name'],
            'link' => $oUser->displayGroupLink($nGroupPk, true, 'tests'),
            'params' => array('class' => 'sub second-level', 'id' => 'linkCompany_'.$nCompanyPk)
        );

        if($bIsStudentTests)
        {
          $aMenua['196-002_'.CONST_ACTION_VIEW.'_all_'.$nStudentPk.'_student'] = array(
            'label' => $oUser->getName($nStudentPk),
            'link' => $oUser->displayMemberLink($nStudentPk, 'student', true),
            'params' => array('class' => 'sub third-level', 'id' => 'linkCompany_'.$nCompanyPk)
          );
        }
      }
    }

    return $aMenua;
  }

  private function _getGbAdminMenu()
  {
    $oPage = CDependency::getComponentByName('page');
    $oUser = CDependency::getComponentByName('gb_user');

    $nPagePk = $oPage->getPk();

    $bHighlighted = false;
    $aMenua = $this->_getSupervisorTestsMenu(CONST_TYPE_GBADMIN, $bHighlighted);

    $aMenub['196-001_'.CONST_ACTION_LIST.'_company'] = array(
            'label' => 'Companies',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0, array('datatype' => 'company')),
            'params' =>  array('class' => 'top icocompany')
        );

    if(!$bHighlighted)
    {
      $bIsCompanyView = $oPage->isPage('196-001', CONST_ACTION_VIEW, CONST_TYPE_GBADMIN, 0, array('datatype' => 'company'));
      $bIsGroupView = $oPage->isPage('196-001', CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, 0, array('datatype' => 'group'));
      $bIsGroupList = $oPage->isPage('196-001', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0, array('datatype' => 'group'));
      $bIsStudentList = $oPage->isPage('196-001', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0, array('datatype' => 'student'));
      $bIsHrmList = $oPage->isPage('196-001', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0, array('datatype' => 'hrmanager'));
      $bIsMemberList = ($bIsStudentList || $bIsHrmList);
      $bIsCompanyAddGroup = $oPage->isPage('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, 0, array('datatype' => 'group'));
      $bIsCompanyAddHrManager = $oPage->isPage('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, 0, array('datatype' => 'hrmanager'));
      $bIsCompanyAddStudent = $oPage->isPage('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, 0, array('datatype' => 'student'));

      if($bIsCompanyView || $bIsCompanyAddGroup || $bIsGroupList || $bIsCompanyAddHrManager || $bIsCompanyAddStudent || $bIsGroupView || $bIsMemberList)
      {
        if($bIsGroupView)
        {
          $aCompany = $oUser->getCompanyFromGroupPk($nPagePk);
          $aGroup = $oUser->getGroup($nPagePk);
          $nCompanyPk = $aGroup['gbuser_companyfk'];
        }
        else
        {
          $nCompanyPk = $nPagePk;
          $aCompany = $oUser->getCompany($nCompanyPk);
        }

        $sClass = ($bIsCompanyView) ? 'sub selected' : 'sub';
        $aMenub['196-001_'.CONST_ACTION_VIEW.'_company_'.$nCompanyPk] = array(
            'label' => $aCompany['name'],
            'link' => $oPage->getUrl('196-001', CONST_ACTION_VIEW, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'company')),
            'params' => array('class' => $sClass, 'id' => 'linkCompany_'.$nCompanyPk)
        );

        if($bIsGroupView)
        {
          $aMenub['196-001_'.CONST_ACTION_EDIT.'_group_'.$nPagePk] = array(
            'label' => $aGroup['name'],
            'link' => $oPage->getUrl('196-001', CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, $nPagePk, array('datatype' => 'group')),
            'params' => array('class' => 'sub second-level', 'id' => 'linkGroup_'.$nPagePk)
          );
        }

        if($bIsGroupList)
        {
          $aMenub['196-001_'.CONST_ACTION_LIST.'_group_'.$nPagePk] = array(
            'label' => 'Groups',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $nPagePk, array('datatype' => 'group')),
            'params' => array('class' => 'sub second-level')
          );
        }

        if($bIsMemberList)
        {
          $sDataType = getValue('datatype');
          $aMenub['196-001_'.CONST_ACTION_LIST.'_'.$sDataType.'_'.$nPagePk] = array(
            'label' => ucfirst($sDataType).'s',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, $nPagePk, array('datatype' => $sDataType)),
            'params' => array('class' => 'sub second-level')
          );
        }

        $aMenub['196-001_'.CONST_ACTION_ADD.'_group_'.$nCompanyPk] = array(
            'label' => '+ Add Group',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'group')),
            'params' => array('class' => 'sub second-level')
        );

        $aMenub['196-001_'.CONST_ACTION_ADD.'_hrmanager_'.$nCompanyPk] = array(
            'label' => '+ Add HR Manager',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'hrmanager')),
            'params' => array('class' => 'sub second-level')
        );

        $aMenub['196-001_'.CONST_ACTION_ADD.'_student_'.$nCompanyPk] = array(
            'label' => '+ Add Student',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, $nCompanyPk, array('datatype' => 'student')),
            'params' => array('class' => 'sub second-level')
        );
      }
    }

    $aMenub ['196-001_'.CONST_ACTION_LIST.'_teacher'] = array(
            'label' => 'Trainers',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0, array('datatype' => 'teacher')),
            'params' =>  array('class' => 'top icoteacher')
    );

    $bIsTeacherView = $oPage->isPage('196-001', CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, 0, array('datatype' => 'teacher'));
    if($bIsTeacherView)
    {
      $sName = $oUser->getName($nPagePk, true);
      $aMenub['196-001_'.CONST_ACTION_EDIT.'_teacher_'.$nPagePk] = array(
        'label' => $sName,
        'link' => $oPage->getUrl('196-001', CONST_ACTION_EDIT, CONST_TYPE_GBADMIN, $nPagePk, array('datatype' => 'teacher')),
        'params' => array('class' => 'sub second-level')
      );
    }

    $aMenuc = array(
        '196-001_'.CONST_ACTION_ADD.'_company' => array(
            'label' => '+ Add new company',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, 0, array('datatype' => 'company')),
            'params' =>  array('class' => 'sub')
        ),
        '196-001_'.CONST_ACTION_ADD.'_teacher' => array(
            'label' => '+ Add new trainer',
            'link' => $oPage->getUrl('196-001', CONST_ACTION_ADD, CONST_TYPE_GBADMIN, 0, array('datatype' => 'teacher')),
            'params' =>  array('class' => 'sub')
        )
    );

    $aMenu = array_merge($aMenua, $aMenub);

    return $aMenu;
  }


}