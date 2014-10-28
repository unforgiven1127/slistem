<?php

// TODO: Display span when mouse hover a row.

require_once('component/globus/gb_test/gb_test.class.php5');

class CGbTestEx extends CGbTest
{
  private $_nUserPk;
  private $_nUserGroupPk;
  private $_sUserType;
  private $_oGbuser;
  public $aChaptersData;
  private $_aCommentTypes;
  private $_sOverallComment = array(
      0 => 'Practise by rewriting focusing on',
      1 => 'Good Job ! No need to rewrite.',
      -1 => ''
  );
  private $_aSkillSets = array(
      'logic' => array(
          'display' => 'Logic',
          'desc' => 'Putting information in the correct order, minimizing information/details, and including all necessary information',
          'points' => 400
      ),
      'phrases' => array(
          'display' => 'Phrases',
          'desc' => 'Using appropriate business phrases, terminology and transitions',
          'points' => 200
      ),
      'tone' => array(
          'display' => 'Tone',
          'desc' => 'Using the proper strength and formality for the situation',
          'points' => 200
      ),
      'layout' => array(
          'display' => 'Layout',
          'desc' => 'Correct use of spacing, paragraphing, font size, numbering, bullet points, subject lines, etc.',
          'points' => 100
      ),
      'language' => array(
          'display' => 'Language',
          'desc' => 'Correct sentence length, verb tense, use of articles, prepositions, etc.',
          'points' => 100
      )
  );
  private $_aEmails = array(
      1 => array(
          'title' => 'Title 1',
          'content' => 'content mail 1'
          ),
      2 => array(
          'title' => 'Title 2',
          'content' => 'content mail 2'
          ),
      3 => array(
          'title' => 'Title 3',
          'content' => 'content mail 3'
          )
  );

  public function __construct()
  {
    $this->_oGbuser = CDependency::getComponentByName('gb_user');
    $this->_sUserType = $this->_oGbuser->getUserType();
    $this->_nUserPk = $this->_oGbuser->getUserPk();
    $this->_nUserGroupPk = $this->_oGbuser->getUserGroupPk();

    $this->aChaptersData = $this->_loadChaptersData();
    $this->_aCommentTypes = $this->_getModel()->aCommentTypes;
  }

  private function _loadChaptersData()
  {
    if((!isset($_SESSION['chaptersData'])) || (getValue('refresh')=='chapter'))
    {
      $aChapters = array();
      $oTests = $this->_getModel()->getAll();

      $bRead = $oTests->readFirst();
      if($bRead)
      {
        while($bRead)
        {
          $nChapterPk = $oTests->getFieldValue('chapterpk');

          if(!isset($aChapters[$nChapterPk]))
          {
            $aChapters[$nChapterPk]['tests']=array();
            $aChapters[$nChapterPk]['name']=$oTests->getFieldValue('chaptername');
            $aChapters[$nChapterPk]['rank']=$oTests->getFieldValue('chapterrank');
          }

          $sType = ((bool)$oTests->getFieldValue('esa')) ? 'ESA' : 'Ass.' ;
          $aChapters[$nChapterPk]['tests'][$oTests->getFieldValue('gbtestpk')] = array(
              'name' => $oTests->getFieldValue('name'),
              'rank' => $oTests->getFieldValue('rank'),
              'type' => $sType
          );

          $bRead = $oTests->readNext();
        }
      }

      $_SESSION['chaptersData'] = $aChapters;
    }

    return $_SESSION['chaptersData'];
  }

  public function getGroupSchedule($pnGroupFk)
  {
    if(!assert('is_key($pnGroupFk)'))
      return array();

    $oSchedule = $this->_getModel()->getByFk($pnGroupFk, 'gbtest_chapter_group', 'gbuser_group');
    $bRead = $oSchedule->readFirst();

    $aSchedule = $this->aChaptersData;
    while($bRead)
    {
      $aSchedule[(int)$oSchedule->getFieldValue('gbtest_chapterfk')]['deadline'] = $oSchedule->getFieldValue('deadline');

      $bRead = $oSchedule->readNext();
    }

    return $aSchedule;
  }

  public function deleteGroupSchedule($pnGroupPk)
  {
    return $this->_getModel()->deleteGroupSchedule($pnGroupPk);
  }

  public function addGroupSchedule($paValues)
  {
    return $this->_getModel()->addGroupSchedule($paValues);
  }

  public function getHtml()
  {
    $this->_processUrl();
    $sDataType = getValue('datatype', 'test');
    $sFilter = getValue('filter', 'all');

    $oPage = CDependency::getComponentByName('page');
    $oPage->addCssFile($this->getResourcePath().'css/test.css');

    switch($this->csType)
    {
      case CONST_TYPE_STUDENT:

        switch($this->csAction)
        {
          case CONST_ACTION_SAVEEDIT:
            return $this->_saveCorrectionRed($this->cnPk);
            break;

          case CONST_ACTION_EDIT:

            //fetch all assignments  ORDERED. If the pk is different than the first active assignment, error
            $oDbResult = $this->_getModel()->getStudentSchedule($this->_nUserPk, $this->_nUserGroupPk, 'active', null);
            $bRead = $oDbResult->readFirst();
            if(!$bRead)
            {
              $oHTML = CDependency::getCpHtml();
              return $oHTML->getErrorMessage('Sorry, you can\'t access this assignment.');
            }

            //dump($oDbResult->getFieldValue('status'));
            $bActiveAss = in_array($oDbResult->getFieldValue('status'), array('', 'NULL', 'active', 'draft'));
            if(!$bActiveAss)
            {
              $oHTML = CDependency::getCpHtml();
              return $oHTML->getErrorMessage('Sorry, you can\'t access this assignment. It seems like you\'ve submitted all your assignments.');
            }

            if($this->cnPk != (int)$oDbResult->getFieldValue('gbtestpk'))
            {
              //dump('changed assignment pk to first waiting one... '.(int)$oDbResult->getFieldValue('rank').' -> pk: '.(int)$oDbResult->getFieldValue('gbtestpk'));
              //dump($oDbResult);
              $oHTML = CDependency::getCpHtml();
              $sUrl = $oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_STUDENT, (int)$oDbResult->getFieldValue('gbtestpk'));
               return $oHTML->getRedirection($sUrl);
            }

            $oUnread = $this->_getModel()->getUnreadCorrectedTest($this->_nUserPk);
            $bRead = $oUnread->readFirst();
            if($bRead)
            {
              $oHTML = CDependency::getCpHtml();
              $sUrl = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_STUDENT, (int)$oUnread->getFieldValue('gbtest_answerpk'), array('filter' => 'test', 'alert' => 1));
              return $oHTML->getRedirection($sUrl);
            }

            return $this->_formStudentAnswer($this->cnPk);
            break;

          case CONST_ACTION_LIST:
            return $this->_displayStudentList();
            break;

          case CONST_ACTION_VIEW:
            switch($sFilter)
            {
              case 'esa':
                return $this->_displayStudentEsa($this->cnPk, true);
              break;
              case 'test':
                return $this->_displayStudentTest($this->cnPk, true);
              break;
            }
            break;
        }
        break;

      case CONST_TYPE_TEACHER:
        switch($this->csAction)
        {
          case CONST_ACTION_EDIT:
            // TODO : change that
            $oTest = $this->_getModel()->getTestFromAnswerPk($this->cnPk); $oTest->readFirst();
            $aTest = $oTest->getData();

            if($aTest['esa']==1)
              return $this->_displayTeacherESACorrectionForm($aTest);
            else
              return $this->_displayTeacherCorrectionForm($aTest);
            break;

          case CONST_ACTION_LIST:
            switch($sFilter)
            {
              case 'teacher':
                return $this->_displayTeacherInbox();
                break;

              default:
                $nPk = $this->cnPk;
                return $this->_displaySupervisorList($sFilter, $nPk);
                break;
            }
            break;

          case CONST_ACTION_VIEW:
            switch($sFilter)
            {
              case 'student':
                return $this->_displaySupervisorSheet($this->cnPk);
              break;
              case 'esa':
                  return $this->_displayStudentEsa($this->cnPk);
              break;
              case 'test':
                return $this->_displayStudentTest($this->cnPk);
              break;
            }
            break;
        }
        break;

      case CONST_TYPE_HRMANAGER:

        switch($this->csAction)
        {
          case CONST_ACTION_LIST:

            switch($sDataType)
            {
              default:
                  if($sFilter=='all')
                  {
                    $sFilter='company';
                    $nPk = (int)$this->_oGbuser->aUserData['gbuser_companyfk'];
                  }
                  else
                  {
                    $nPk = $this->cnPk;
                  }
                  return $this->_displaySupervisorList($sFilter, $nPk);
                break;
            }
            break;

          case CONST_ACTION_VIEW:

            switch($sDataType)
            {
              case 'vs':
                $nCompany = (int)$this->_oGbuser->aUserData['gbuser_companyfk'];
                $nGroup = $this->cnPk;
                return $this->_displayStatsVsEsa($nCompany, $nGroup);
                break;

              case 'average':
                $nCompany = (int)$this->_oGbuser->aUserData['gbuser_companyfk'];
                $nGroup = $this->cnPk;
                $nEsa = (int)getValue('esa', 1);
                return $this->_displayStatsAverage($nCompany, $nGroup, $nEsa);
                break;

              default:
                switch($sFilter)
                  {
                    case 'student':
                      return $this->_displaySupervisorSheet($this->cnPk);
                    break;
                    case 'esa':
                      return $this->_displayStudentEsa($this->cnPk);
                    break;
                    case 'test':
                      return $this->_displayStudentTest($this->cnPk);
                    break;
                  }
                break;
            }
            break;
        }
        break;

      case CONST_TYPE_GBADMIN:
        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            $nPk = $this->cnPk;
            return $this->_displaySupervisorList($sFilter, $nPk);
            break;

          case CONST_ACTION_VIEW:
            switch($sFilter)
            {
              case 'student':
                return $this->_displaySupervisorSheet($this->cnPk);
              break;
              case 'esa':
                  return $this->_displayStudentEsa($this->cnPk);
              break;
              case 'test':
                return $this->_displayStudentTest($this->cnPk);
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
    echo 'Globus cron - student notification<br />';

    $oSetting = CDependency::getComponentByName('settings');
    $asSetting = $oSetting->getSystemSettings('globus_daily_cron');

    $bForceCron = (bool)getValue('forcecron');
    $sToday = date('Y-m-d');

    if(!$bForceCron && !empty($asSetting) && $asSetting['globus_daily_cron'] == $sToday)
    {
      echo 'Cron already executed today ['.$asSetting['globus_daily_cron'].'] <br />';
      return 0;
    }

    $day = date('l');
    $time = (int)date('H');

    if(($time == 6) || (getValue('custom_uid') == $this->csUid && $bForceCron))
    {
      echo 'Notify Participants deadlines<br />';
      $nCount = $this->_notifyStudents();
      echo $nCount.' participants were notified.';
    }

    if(($time == 6) || (getValue('custom_uid') == $this->csUid && $bForceCron))
    {
      echo 'Notify Participants ESA2 available<br />';
      $nCount = $this->_notifyEsa2Available();
      echo $nCount.' participants were notified.';
    }

    if(($day=='Sunday' && $time == 6) || (getValue('custom_uid') == $this->csUid && $bForceCron))
    {
      echo 'Send after session Email<br />';
      $nCount = $this->_sendAfterSessionEmails();
      echo $nCount.' people were notified.';
    }

    $oSetting->setSystemSettings('globus_daily_cron', $sToday);
  }

  private function _sendAfterSessionEmails()
  {
    $aEmails = $this->_aEmails;
    $aStudentEmails = array();
    $nWeekCount = count($this->_aEmails);

    $oGroups = $this->_getModel()->getPastGroups($nWeekCount);
    $bRead = $oGroups->readFirst();

    while($bRead)
    {
      $nGroupPk = (int)$oGroups->getFieldValue('gbuser_groupfk');
      $aStudentEmails[$nGroupPk] = $this->_oGbuser->getStudentsEmailsForGroup($nGroupPk);
      $dEndDate = $oGroups->getFieldValue('date_end');
      $tEndDate = strtotime($dEndDate);
      $tToday = time();

      $tDiff = floor(($tToday-$tEndDate)/(60*60*24*7));

      if(!isset($aEmails[$tDiff]['recipients']))
        $aEmails[$tDiff]['recipients'] = $aStudentEmails[$nGroupPk];
      else
        $aEmails[$tDiff]['recipients']=array_merge($aEmails[$tDiff]['recipients'],$aStudentEmails[$nGroupPk]);

      $bRead = $oGroups->readNext();
    }

    $oMail = CDependency::getComponentByName('mail');
    $nCount = 0;
    foreach ($aEmails as $aEmail)
    {
      if(isset($aEmail['recipients']))
      {
        $oMail->createNewEmail();
        $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
        $oMail->addRecipient(CONST_DEV_EMAIL);
        $oMail->addBCCRecipient($aEmail['recipients']);

        $oMail->send($aEmail['title'],$aEmail['content']);
        $nCount++;
      }
    }
    return $nCount;
  }

  // Notifies students that are gonna miss the deadline soon
  private function _notifyStudents()
  {
    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();

    //fetch assignments expiring today
    $oTests = $this->_getModel()->getPendingTests();
    $bRead = $oTests->readFirst();
    if(!$bRead)
      return 0;

    $aStudents = array();
    $aStudentIds = array();
    $aTests = array();

    while($bRead)
    {
      $aData = $oTests->getData();
      $nGroupPk = (int)$aData['gbuser_groupfk'];
      $nTestPk = (int)$aData['gbtestpk'];

      if(!isset($aStudentIds[$nGroupPk]))
        $aStudentIds[$nGroupPk] = $this->_oGbuser->getStudentsIdsForGroup($nGroupPk);

      //$aStudentToRemind = array_diff($this->_getModel()->getStudentsNotToRemind($aStudentIds[$nGroupPk], $nTestPk), $aStudentIds[$nGroupPk]);
      $aStudentToRemind = array_diff($this->_getModel()->getStudentsNotToRemind($nTestPk), $aStudentIds[$nGroupPk]);
      if(!empty($aStudentToRemind))
      {
        foreach($aStudentToRemind as $nStudentPk)
        {
          if(!isset($aTests[$nTestPk]))
          {
            $aDivOptions = array('style' => 'display:inline-block; border-top: 1px solid #FFFFFF; background:#EFF0F0;');
            $sLabelRank = ((int)$aData['esa']==1) ? 'ESA'.$aData['rank'] : 'Ass.'.$aData['rank'];
            $sUrlAnswer = $oPage->getUrl($this->getComponentUid(), CONST_ACTION_EDIT, CONST_TYPE_STUDENT, $aData['gbtestpk']);

            $sContent = $oHTML->getBlocStart('', $aDivOptions);
              $sContent .= $oHTML->getBloc('', $sLabelRank, array('style' => 'display:inline-block; width:50px; padding:5px;'));
              $sContent .= $oHTML->getBloc('', $aData['name'].' - '.$aData['test_name'], array('style' => 'display:inline-block; width:450px; padding:5px;'));
              $sContent .= $oHTML->getBloc('', date('Y-m-d', strtotime($aData['deadline'])), array('style' => 'display:inline-block; width:120px; padding:5px;'));
              $sContent .= $oHTML->getBloc('', $oHTML->getLink('Reply Now', $sUrlAnswer), array('style' => 'display:inline-block; width:120px; padding:5px;'));
            $sContent .= $oHTML->getBlocEnd();

            $aTests[$nTestPk] = $sContent;
          }

          $aStudents[$nStudentPk][] = $aTests[$nTestPk];
        }
      }

      $bRead = $oTests->readNext();
    }

    $sHeader = $oHTML->getBlocStart('', array('style' => 'display:inline-block; background:#2F3036; color:#FFF;'));
      $sHeader .= $oHTML->getBloc('', 'Test', array('style' => 'display:inline-block; width:50px; padding:5px;'));
      $sHeader .= $oHTML->getBloc('', 'Title', array('style' => 'display:inline-block; width:450px; padding:5px;'));
      $sHeader .= $oHTML->getBloc('', 'Deadline', array('style' => 'display:inline-block; width:120px; padding:5px;'));
      $sHeader .= $oHTML->getBloc('', '', array('style' => 'display:inline-block; width:120px; padding:5px;'));
    $sHeader .= $oHTML->getBlocEnd();

    $nCount = 0;

    $oMail = CDependency::getComponentByName('mail');
    // Checks if there are too many mails to send. If yes, cancel sending
    $nMails = count($aStudents);
    if($nMails > 1000)
    {
      assert('false; // Globus Online Coaching: Alert -- Function _notifyStudent() tried to send '.$nMails.' emails. The process has been stopped. Please check the function.');
      return 0;
    }

    //dump($aStudents);

    foreach($aStudents as $nStudentPk => $asContent)
    {
      $aUser = $this->_oGbuser->getUser($nStudentPk);
      $oMail->createNewEmail();
      $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
      $oMail->addRecipient($aUser['email'], $aUser['firstname'].' '.$aUser['lastname']);

      $sContent = 'Dear '.$aUser['firstname'].' '.$aUser['lastname'].','.$oHTML->getCR(2);
      $sContent.= 'You have work to do on Globus Online Coaching Platform!'.$oHTML->getCR(2);
      $sContent.= 'The following tests and/or ESA are close to reach the deadline:'.$oHTML->getCR(1);
      $sContent.= $sHeader.implode('', array_unique($asContent)).$oHTML->getCR(2);
      $sContent.= 'Please reply as soon as you can.'.$oHTML->getCR(2);

      $nMailSent = $oMail->send('Globus Online Coaching: Deadline is soon!', $sContent);
      $nCount+=$nMailSent;
    }
    return $nCount;
  }



  private function _notifyEsa2Available()
  {
    $oHTML = CDependency::getCpHtml();

    //fetch all the students in a group that has to do the ESA2
    $asGroup = $this->_getModel()->getNbTestForEsaGroup();
    //dump($asGroup);
    $anGroup = array();
    foreach($asGroup as $asTest)
    {
      $anGroup[(int)$asTest['gbuser_groupfk']] = (int)$asTest['nb_test'];
    }


    $sToday = date('Y-m-d');
    $asAnswer = $this->_getModel()->getNbStudentAnswerByGroup(array_keys($anGroup), $sToday);

    //dump($asAnswer);
    $asNotification = array();
    foreach($asAnswer as $asData)
    {
      $nTest = ((int)$anGroup[$asData['gbuser_groupfk']] -2);
      $asData['nb_answer'] = (int)$asData['nb_answer'];

      if($asData['nb_answer'] == $nTest)
      {
        if($asData['deadline'] == $sToday)
        {
          echo ('===> user '.$asData['gbuserfk'].' '.$asData['email'].' grp '.$asData['gbuser_groupfk'].' : has done everything except last 2... ['.$asData['nb_answer'].'/'.$anGroup[$asData['gbuser_groupfk']].']<br />');
          $asNotification[(int)$asData['gbuserfk']] = $asData;
        }
        else
          echo ('--> user '.$asData['gbuserfk'].' '.$asData['email'].' grp '.$asData['gbuser_groupfk'].' :  Is ESA2 READY!! but deadline is not today<br />');

      }
      else
        echo ('--> user '.$asData['gbuserfk'].' '.$asData['email'].' grp '.$asData['gbuser_groupfk'].' :  is not done or has done everything ['.$asData['nb_answer'].'/'.$anGroup[$asData['gbuser_groupfk']].']<br />');

    }


    // Checks if there are too many mails to send. If yes, cancel sending
    $nMails = count($asNotification);
    if($nMails > 1000)
    {
      assert('false; // Globus Online Coaching: Alert -- Function _notifyStudent() tried to send '.$nMails.' emails. The process has been stopped. Please check the function.');
      return 0;
    }

    $oMail = CDependency::getComponentByName('mail');
    $nCount = 0;

    foreach($asNotification as $asData)
    {
      //$aUser = $this->_oGbuser->getUser($nStudentPk);
      $oMail->createNewEmail();
      $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
      $oMail->addRecipient($asData['email'], $asData['firstname'].' '.$asData['lastname']);

      $sContent = 'Dear '.$asData['firstname'].' '.$asData['lastname'].','.$oHTML->getCR(2);
      $sContent.= 'Your Post Course Assessment, "ESA 2 (Email Simulation Assessment)", is now available. Please log in to your account and check the deadline. Make sure to proceed to the assessment and submit by the deadline set.
<br /><br />
事後アセスメント、 "ESA 2 (Email Simulation Assessment)"、に進む事が可能になりました。個人ページにログインし、提出期日を確認して下さい。設定された期日までにESA2を終わらせ、提出して下さい。';
      $sContent.= $oHTML->getCR(2);

      $nMailSent = $oMail->send('Globus Online Coaching: ESA2 available!', $sContent);
      $nCount+=$nMailSent;
    }

    return $nCount;
  }

  public function getAjax()
  {
    $this->_processUrl();
    $sDataType = getValue('datatype', 'test');
    $oPage = CDependency::getComponentByName('page');

    switch($this->csType)
    {
      case CONST_TYPE_STUDENT:
        switch($this->csAction)
        {
        case CONST_ACTION_SAVEEDIT:
          return json_encode($this->_saveStudentAnswer());
          break;
        case CONST_ACTION_SAVEADD:
          return json_encode($this->_saveStudentAnswer());
          break;
        }
        break;

      case CONST_TYPE_TEACHER:

        switch($sDataType)
        {
          case 'comment':
            switch($this->csAction)
            {
              case CONST_ACTION_LIST:
                return json_encode($oPage->getAjaxExtraContent($this->_displayCommentList($this->cnPk)));
                break;

              case CONST_ACTION_ADD:
                return json_encode($oPage->getAjaxExtraContent($this->_formComment($this->cnPk)));
                break;

              case CONST_ACTION_EDIT:
                return json_encode($oPage->getAjaxExtraContent($this->_formComment(0, $this->cnPk)));
                break;

              case CONST_ACTION_SAVEEDIT:
              case CONST_ACTION_SAVEADD:
                return json_encode($this->_saveComment());
                break;

              case CONST_ACTION_DELETE:
                return json_encode($this->_deleteComment($this->cnPk));
                break;

              default:
                break;
            }
          break;

          case 'correction':
            return json_encode($this->_saveCorrection($this->cnPk));
            break;

          case 'esa':
            return json_encode($this->_saveEsa($this->cnPk));
            break;
        }
        break;
    }

  }

  private function _displayStatsAverage($pnCompanyPk=0, $pnGroupPk=0, $pnEsa = 1)
  {
    if(!assert('is_key($pnCompanyPk) || is_key($pnGroupPk)'))
      return '';

    if(!assert('($pnEsa==1) || ($pnEsa==2)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/esa.css');
    $oPage->addCssFile($this->getResourcePath().'css/esaVs.css');
    $oPage->addJsFile($this->getResourcePath().'js/highcharts.js');
    $oPage->addJsFile($this->getResourcePath().'js/highcharts-more.js');
    $oPage->addJsFile($this->getResourcePath().'js/esaVs.js');

    $bIsGroup = (is_key($pnGroupPk));
    $aStudentsIds = ($bIsGroup) ? $this->_oGbuser->getStudentsIdsForGroup($pnGroupPk) : $this->_oGbuser->getStudentsIdsForCompany($pnCompanyPk);
    $aTarget = ($bIsGroup) ? $this->_oGbuser->getGroup($pnGroupPk): $this->_oGbuser->getCompany($pnCompanyPk);

    if(!assert('!empty($aTarget)'))
      return '';

    $sTitle = 'ESA'.$pnEsa.': '.$aTarget['name'].' VS Average';
    $sHTML = '';

    $sHTML .= $oHTML->getTitle($sTitle, 'h1');

    $sHTML .= $oHTML->getBlocStart('statusSwitcher', array('class' => 'statusSwitcher'));
      $sUrlAll = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, 0, array('datatype' => 'average', 'esa' => $pnEsa));
      $sHTML .= $oHTML->getLink('All Company', $sUrlAll);
      foreach($this->_oGbuser->aGroupsData as $nGroupPk => $aGroup)
      {
        $sURL = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, $nGroupPk, array('datatype' => 'average', 'esa' => $pnEsa));
        $sHTML .= $oHTML->getLink($aGroup['name'], $sURL);
      }
    $sHTML.= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getCR(1);

    $sHTML.= $oHTML->getBlocStart('esaContainer');

      $oGroups = $this->_oGbuser->getGroupsForCompany($pnCompanyPk);
      $bRead = $oGroups->readFirst();
      $aACValues = array();
      while($bRead)
      {
        $aGroup = $oGroups->getData();
        $aACValues[]=array(
            'label' => $aGroup['name'],
            'category' => ((int)$aGroup['active']==1) ? 'Active groups' : 'Inactive groups',
            'url' => $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, (int)$aGroup['gbuser_grouppk'], array('datatype' => 'average', 'esa' => $pnEsa))
        );
        $bRead = $oGroups->readNext();
      }
      $sArray = json_encode($aACValues);
      $oPage->addJsFile($this->getResourcePath().'js/search.js');
      $sHTML.= $oHTML->getBloc('searchDiv', '<script>var aSearchValues = '.$sArray.';</script><input id=\'search\' type=\'text\' placeholder=\'Search All Groups\'>', array('class' => 'formField'));

      $aEsa1 = $this->_getModel()->getEsaResults($pnEsa);
      $aEsa2 = $this->_getModel()->getEsaResults($pnEsa, $aStudentsIds);

      if(!empty($aEsa1['tone']) && !empty($aEsa2['tone']))
      {
        $sHTML .= $oHTML->getTitle('Global View', 'h4');
        $sHTML.= $oHTML->getBlocStart('global');
          $sHTML .= $oHTML->getBloc('esaChart');
          $sHTML .= $oHTML->getBloc('esaCols');
        $sHTML.= $oHTML->getBlocEnd();

        $nSpeed1 = (int)$aEsa1['speed']; $nSpeed2 = (int)$aEsa2['speed'];
        $nSpeedDifference = $nSpeed2-$nSpeed1;
        $nSpeedEvol = round($nSpeedDifference/$nSpeed1*100);
        $sClass = ($nSpeedDifference<=0) ? 'positive' : 'negative';
        $nSpeedEvol.='%';
        $sHTML .= $oHTML->getTitle('Speed', 'h4');
        $sHTML.= $oHTML->getBlocStart('speed');
          $sHTML.= $oHTML->getBloc('speedEsa1', 'Average Global Time: '.$oHTML->getNiceDuration($nSpeed1));
          $sHTML.= $oHTML->getBloc('speedEsa2', 'Average '.$aTarget.' Time: '.$oHTML->getNiceDuration($nSpeed2));
          $sHTML.= $oHTML->getBloc('speedEvol', 'Time Faster: '.$oHTML->getSpan('', $oHTML->getNiceDuration(abs($nSpeedDifference)).' ('.$nSpeedEvol.')', array('class' => $sClass)));
        $sHTML.= $oHTML->getBlocEnd();

        $sHTML .= $oHTML->getTitle('Details', 'h4');
        $sHTML .= $this->_displayStatsVsEsaTable($aEsa1, $aEsa2, $aTarget['name'], 'Average');
      }
      else
        return $sHTML.= $oHTML->getBloc('noStat', 'Sorry, this page is not available yet.<br /> At least one ESA need to be corrected for this page to display statistics.');

    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }


  private function _displayStatsVsEsa($pnCompanyPk=0, $pnGroupPk=0)
  {
    if(!assert('is_key($pnCompanyPk) || is_key($pnGroupPk)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/esa.css');
    $oPage->addCssFile($this->getResourcePath().'css/esaVs.css');
    $oPage->addJsFile($this->getResourcePath().'js/esaVs.js');
    $oPage->addJsFile($this->getResourcePath().'js/highcharts.js');
    $oPage->addJsFile($this->getResourcePath().'js/highcharts-more.js');

    $bIsGroup = (is_key($pnGroupPk));
    $aStudentsIds = ($bIsGroup) ? $this->_oGbuser->getStudentsIdsForGroup($pnGroupPk) : $this->_oGbuser->getStudentsIdsForCompany($pnCompanyPk);
    $aTarget = ($bIsGroup) ? $this->_oGbuser->getGroup($pnGroupPk): $this->_oGbuser->getCompany($pnCompanyPk);

    if(!assert('!empty($aTarget)'))
      return '';

    $sTitle = 'ESA1 VS ESA2 > '.$aTarget['name'];
    $sHTML = '';
    $aEsa1 = $this->_getModel()->getEsaResults(1, $aStudentsIds);
    $aEsa2 = $this->_getModel()->getEsaResults(2, $aStudentsIds);

    $sHTML .= $oHTML->getTitle($sTitle, 'h1');

    $sHTML .= $oHTML->getBlocStart('statusSwitcher', array('class' => 'statusSwitcher'));
      $sUrlAll = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, 0, array('datatype' => 'vs'));
      $sHTML .= $oHTML->getLink('All Company', $sUrlAll);
      foreach($this->_oGbuser->aGroupsData as $nGroupPk => $aGroup)
      {
        $sURL = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, $nGroupPk, array('datatype' => 'vs'));
        $sHTML .= $oHTML->getLink($aGroup['name'], $sURL);
      }
    $sHTML.= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getCR(1);

    if(empty($aEsa1['tone']) || empty($aEsa2['tone']))
      return $sHTML.= $oHTML->getBloc('noStat', 'Sorry, this page is not available yet.<br /> The two ESA need to be corrected for this page to display statistics.');

    $sHTML.= $oHTML->getBlocStart('esaContainer');

      $oGroups = $this->_oGbuser->getGroupsForCompany($pnCompanyPk);
      $bRead = $oGroups->readFirst();
      $aACValues = array();
      while($bRead)
      {
        $aGroup = $oGroups->getData();
        $aACValues[]=array(
            'label' => $aGroup['name'],
            'category' => ((int)$aGroup['active']==1) ? 'Active groups' : 'Inactive groups',
            'url' => $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_HRMANAGER, (int)$aGroup['gbuser_grouppk'], array('datatype' => 'vs'))
        );
        $bRead = $oGroups->readNext();
      }
      $sArray = json_encode($aACValues);
      $oPage->addJsFile($this->getResourcePath().'js/search.js');
      $sHTML.= $oHTML->getBloc('searchDiv', '<script>var aSearchValues = '.$sArray.';</script><input id=\'search\' type=\'text\' placeholder=\'Search All Groups\'>', array('class' => 'formField'));

      $sHTML .= $oHTML->getTitle('Global View', 'h4');
      $sHTML.= $oHTML->getBlocStart('global');
        $sHTML .= $oHTML->getBloc('esaChart');
        $sHTML .= $oHTML->getBloc('esaCols');
      $sHTML.= $oHTML->getBlocEnd();

      $nSpeed1 = (int)$aEsa1['speed']; $nSpeed2 = (int)$aEsa2['speed'];
      $nSpeedDifference = $nSpeed2-$nSpeed1;
      $nSpeedEvol = round($nSpeedDifference/$nSpeed1*100);
      $sClass = ($nSpeedDifference<0) ? 'positive' : 'negative';
      $nSpeedEvol.='%';
      $sHTML .= $oHTML->getTitle('Speed', 'h4');
      $sHTML.= $oHTML->getBlocStart('speed');
        $sHTML.= $oHTML->getBloc('speedEsa1', 'Average Time for ESA 1: '.$oHTML->getNiceDuration($nSpeed1));
        $sHTML.= $oHTML->getBloc('speedEsa2', 'Average Time for ESA 2: '.$oHTML->getNiceDuration($nSpeed2));
        $sHTML.= $oHTML->getBloc('speedEvol', 'Time Saved: '.$oHTML->getSpan('', $oHTML->getNiceDuration(abs($nSpeedDifference)).' ('.$nSpeedEvol.')', array('class' => $sClass)));
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML .= $oHTML->getTitle('Details', 'h4');
      $sHTML .= $this->_displayStatsVsEsaTable($aEsa1, $aEsa2);
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayStatsVsEsaTable($paEsa1, $paEsa2, $psName1 = 'ESA1', $psName2 = 'ESA2')
  {
    $oHTML = CDependency::getCpHtml();

    $sHTML = '';

    $sHTML.= $oHTML->getBlocStart('', array('class' => 'esaVsScreen'));

      $sHTML .= $oHTML->getBlocStart('skillSetsHeader');
        $sHTML .= $oHTML->getBloc('', 'Skill', array('class' => 'skill'));
        $sHTML .= $oHTML->getBloc('name1', $psName1, array('class' => 'points'));
        $sHTML .= $oHTML->getBloc('name2', $psName2, array('class' => 'points'));
        $sHTML .= $oHTML->getBloc('', 'Evol.', array('class' => 'points'));
      $sHTML .= $oHTML->getBlocEnd();

      $nTotal1 = $nTotal2 = 0;
      foreach($this->_aSkillSets as $sShortname => $aSkillSet)
      {
        $nPercentage1 = round($paEsa1[$sShortname]);
        $nPoints1 = ($aSkillSet['points']/100)*$nPercentage1;
        $nPercentage2 = round($paEsa2[$sShortname]);
        $nPoints2 = ($aSkillSet['points']/100)*$nPercentage2;
        $nTotal1 += $nPoints1;
        $nTotal2 += $nPoints2;
        $nEvolution = round(($nPoints2-$nPoints1)/$nPoints1*100);
        if($nEvolution>0)
        {
          $sClass = 'positive';
          $nEvolution='+'.$nEvolution;
        }
        else
          $sClass = 'negative';

        $nEvolutionTotal = round(($nTotal2-$nTotal1)/$nTotal1*100);
        if($nEvolutionTotal>0)
          $nEvolutionTotal='+'.$nEvolutionTotal;
        $sHTML .= $oHTML->getBlocStart($sShortname, array('class' => 'skillSet'));
          $sHTML .= $oHTML->getBlocStart('', array('class' => 'skillText'));
            $sHTML .= $oHTML->getBloc('', $aSkillSet['display'].' ('.$aSkillSet['points'].' points)', array('class' => 'skillname'));
            $sHTML .= $oHTML->getBloc('', $aSkillSet['desc'], array('class' => 'skilldesc'));
          $sHTML .= $oHTML->getBlocEnd();
          $sHTML .= $oHTML->getBlocStart('', array('class' => 'scores'));
            $sHTML .= $oHTML->getBloc('', $nPercentage1.'%', array('class' => 'percent1', 'value' => $nPercentage1));
            $sHTML .= $oHTML->getBloc('', $nPercentage2.'%', array('class' => 'percent2', 'value' => $nPercentage2));
            $sHTML .= $oHTML->getBloc('', 'ESA1', array('class' => 'header'));
            $sHTML .= $oHTML->getBloc('', 'ESA2', array('class' => 'header'));
            $sHTML .= $oHTML->getBloc('', 'Evolution', array('class' => 'header'));
            $sHTML .= $oHTML->getBloc('', $nPoints1);
            $sHTML .= $oHTML->getBloc('', $nPoints2);
            $sHTML .= $oHTML->getBloc('', $nEvolution.'%', array('class' => $sClass));
          $sHTML .= $oHTML->getBlocEnd();
        $sHTML .= $oHTML->getBlocEnd();
      }

      $sClassTotal = ($nEvolutionTotal>0) ? 'positive' : 'negative';
      $sHTML .= $oHTML->getBlocStart('skillSetsFooter');
        $sHTML .= $oHTML->getBloc('', 'Total', array('class' => 'skill'));
        $sHTML .= $oHTML->getBloc('', $nTotal1, array('class' => 'points'));
        $sHTML .= $oHTML->getBloc('', $nTotal2, array('class' => 'points'));
        $sHTML .= $oHTML->getBloc('', $nEvolutionTotal.'%', array('class' => 'points '.$sClassTotal));
      $sHTML .= $oHTML->getBlocEnd();

  $sHTML.=$oHTML->getBlocEnd();

  $sHTML .= $oHTML->getBloc('', 'native > 900  / advanced 800-900 / high intermediate 700-800 / intermediate 600-700 / low intermediate 500-600 / high basic 400-500 / basic 300-400 / low basic < 300', array('class' => 'desc'));

  return $sHTML;

  }

  // Returns pending tests for a list of students

  public function getTestsToCorrect($paIds)
  {
    $aOutPut = array('nCount' => 0, 'sHtml' => '');

    if(!assert('is_array($paIds)'))
      return $aOutPut;

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPendingTests = $this->_getModel()->getTestsForTeacher($paIds, '', 'sent');
    $aOutPut['nCount'] = (int)$oPendingTests->numRows();

    if($aOutPut['nCount']> 0)
    {
      $sHTML = '';
      $sHTML .= $oHTML->getBlocStart('');
      $bRead = $oPendingTests->readFirst();

      $sHTML .= $oHTML->getBlocStart('', array('style' => 'display:inline-block; background:#2F3036; color:#FFF;'));
        $sHTML .= $oHTML->getBloc('', '', array('style' => 'display:inline-block; width:50px; padding:5px;'));
        $sHTML .= $oHTML->getBloc('', 'Student', array('style' => 'display:inline-block; width:150px; padding:5px;'));
        $sHTML .= $oHTML->getBloc('', 'Company / Group', array('style' => 'display:inline-block; width:300px; padding:5px;'));
        $sHTML .= $oHTML->getBloc('', 'Received', array('style' => 'display:inline-block; width:120px; padding:5px;'));
        $sHTML .= $oHTML->getBloc('', '', array('style' => 'display:inline-block; width:120px; padding:5px;'));
      $sHTML .= $oHTML->getBlocEnd();

      while($bRead)
      {
        $aRow = $oPendingTests->getData();
        $sLabelRank = $aRow['rank'];
        if((int)$aRow['esa']==1)
          $sLabelRank = 'ESA'.$sLabelRank;
        else
          $sLabelRank = 'Ass.'.$sLabelRank;

        $aUser = $this->_oGbuser->getUser((int)$aRow['gbuserfk']);

        $sUrlAnswer = $oPage->getUrl($this->getComponentUid(), CONST_ACTION_EDIT, CONST_TYPE_TEACHER, $aRow['gbtest_answerpk'], array('datatype' => 'answer'));
        $aDivOptions = array(
            'style' => 'display:inline-block; border-top: 1px solid #FFFFFF; background:#EFF0F0;'
            );

        $sHTML .= $oHTML->getBlocStart('', $aDivOptions);
          $sHTML .= $oHTML->getBloc('', $sLabelRank, array('style' => 'display:inline-block; width:50px; padding:5px;'));
          $sHTML .= $oHTML->getBloc('', $this->_oGbuser->getName(0, true, $aUser), array('style' => 'display:inline-block; width:150px; padding:5px;'));
          $sHTML .= $oHTML->getBloc('', $aUser['company_name'].' / '.$aUser['group_name'], array('style' => 'display:inline-block; width:300px; padding:5px;'));
          $sHTML .= $oHTML->getBloc('', date('Y-m-d', strtotime($aRow['date_submitted'])), array('style' => 'display:inline-block; width:120px; padding:5px;'));
          $sHTML .= $oHTML->getBloc('', $oHTML->getLink('Correct Now', $sUrlAnswer), array('style' => 'display:inline-block; width:120px; padding:5px;'));
        $sHTML .= $oHTML->getBlocEnd();

        $bRead = $oPendingTests->readNext();
      }
      $aOutPut['sHtml']=$sHTML;
    }
    return $aOutPut;
  }

  // Returns results for a list of students

  public function getStudentResults($paIds)
  {
    if(!assert('is_array($paIds)'))
      return array();

    return $this->_getModel()->getStudentResults($paIds);
  }

  public function getNbTestsForTeacher($paStudentIds)
  {
    if(!assert('is_array($paStudentIds)'))
      return 0;

    return $this->_getModel()->getNbTestsForTeacher($paStudentIds);
  }

  public function getNbTestsForUser($pnUserPk, $pnGroupPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return 0;

    if(!assert('is_integer($pnGroupPk)'))
      return 0;

    if(empty($pnGroupPk))
      return 0;

    $aResult = $this->_getModel()->getNbTestsForUser($pnUserPk, $pnGroupPk);
    return $aResult[0]+$aResult[1];
  }

  private function _displayCommentList($pnCorrectionPk, $pbDisplayAction = true, $pbDisplayBoxes = false)
  {
    if(!assert('is_key($pnCorrectionPk)'))
      return '';

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');
    $sHTML = '';
    $sHTML .= '<script>removePins();</script>';

    if ($this->_sUserType=='student')
      $oPage->addJsFile($this->getResourcePath().'js/checkStudentComments.js');

    $sOrderBy = ($this->_sUserType=='student') ? 'importance DESC' : '' ;
    $oComments = $this->_getModel()->getByFk($pnCorrectionPk, 'gbtest_correction_point', 'gbtest_correction', '*', $sOrderBy);
    $bRead = $oComments->readFirst();
    $nCount = 0;

    $sActions = 'toggleSendButton(); checkComments();';

    if($bRead)
    {
      $nCount = 1; $sActions = '';
      $sHTML .= $oHTML->getListStart('listComments', array('class' => 'dlist'));
      while($bRead)
      {
        $aComment = $oComments->getData();
        $sHTML .= $this->_displayCommentListItem($aComment, $nCount, $pbDisplayAction, $pbDisplayBoxes);
        $sActions.= ' placePin('.$aComment['start'].', '.$nCount.', '.$aComment['gbtest_correction_pointpk'].');';
        $nCount++;
        $bRead = $oComments->readNext();
      }
      $sHTML .= $oHTML->getListEnd();
    }

    if($this->_sUserType=='student')
    {
      if($pbDisplayBoxes)
      {
        $sUrlConfirm = $oPage->getUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_TYPE_STUDENT, $pnCorrectionPk, array('datatype' => 'correction'));
        $oForm = $oHTML->initForm('confirm');
        $oForm->setFormParams('confirm', false, array('action' => $sUrlConfirm, 'noCancelButton' => 'noCancelButton', 'submitLabel' => 'I read all comments', 'id' => 'confirm'));
        $oForm->addField('input', 'status', array('type' => 'hidden', 'value' => 'read'));
        $sHTML.= $oForm->getDisplay();
      }
      else
      {
        $sHTML .= $oHTML->getBloc('commentsRead', $oHTML->getPicture(CONST_PICTURE_TESTOK).' Comments Read');
      }
    }
    return array('data' => $sHTML, 'action' => $sActions, 'nb_comment' => ($nCount-1));
  }

  private function _displayCommentListItem($paComment, $pnCount, $pbDisplayAction=true, $pbRed = false)
  {
    if(!assert('is_key($pnCount)'))
      return '';

    if(!assert('is_array($paComment) && !empty($paComment)'))
      return '';

    if(!assert('is_bool($pbDisplayAction)'))
      return '';

    $nPk = $paComment['gbtest_correction_pointpk'];
    $oPage = CDependency::getComponentByName('page');

    $oHTML = CDependency::getComponentByName('display');
    $sHTML = '';
    $sHTML .= $oHTML->getListItemStart('comment_'.$nPk, array('pk' => $nPk, 'class' => 'dlist-item', 'stars' => $paComment['importance'], 'start' => $paComment['start'], 'end' => $paComment['end']));
    $sHTML .= $oHTML->getLink($pnCount.'. '.$this->_aCommentTypes[$paComment['type']].': '.$paComment['comment'], 'javascript:;', array( 'onClick' => 'highlightComment('.$nPk.'); '));

    if (($this->_sUserType=='teacher') && ($pbDisplayAction))
    {
      $sUrlEdit = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_TEACHER, $nPk, array('datatype' => 'comment'));
      $sUrlDelete = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_TYPE_TEACHER, $nPk, array('datatype' => 'comment'));
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'actions'));
        $sHTML .= $oHTML->getLink('Edit', 'javascript:;', array('class' => 'manage', 'onclick' => 'showCommentForm(0,0,\''.$sUrlEdit.'\');'));
        $sHTML .= $oHTML->getLink('Delete', 'javascript:;', array('class' => 'delete', 'onclick' => 'AjaxRequest(\''.$sUrlDelete.'\');'));
      $sHTML .= $oHTML->getBlocEnd();
    }
    elseif (($this->_sUserType=='student') && ($pbRed))
    {
      $sHTML .= $oHTML->getBloc('', 'I understand: <input type=\'checkbox\' pk=\''.$paComment['gbtest_correction_pointpk'].'\'>', array('class' => 'actions'));
    }

    $sHTML .= $oHTML->getListItemEnd();

    return $sHTML;
  }

  private function _formComment($pnCorrectionPk, $pnCommentPk = 0)
  {
    if(!assert('is_numeric($pnCorrectionPk)'))
      return array('error' => 'Error loading the comment form. Please contact your administrator.');

    if(!assert('is_numeric($pnCommentPk)'))
      return array('error' => 'Error loading the comment form. Please contact your administrator.');

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');
    $bIsEdition = (is_key($pnCommentPk));

    $oForm = $oHTML->initForm('formComment');
    if($bIsEdition)
    {
      $oComment = $this->_getModel()->getByPk($pnCommentPk, 'gbtest_correction_point');
      $sUrlSave = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_TYPE_TEACHER, $pnCommentPk, array('datatype' => 'comment'));
      $nCorrectionPk = $oComment->getFieldValue('gbtest_correctionfk');

      $nStart = $oComment->getFieldValue('start');
      $nEnd = $oComment->getFieldValue('end');
    }
    else
    {
      $nStart = (int)getValue('start', 0);
      $nEnd = (int)getValue('end', 0);
      if(!assert('is_key($nStart) && is_key($nEnd)'))
        return array('error'=>'We could not read the selected text. Impossible to add comment. Please contact your administrator.');

      $oComment = new CDbResult();
      $sUrlSave = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_TYPE_TEACHER, 0, array('datatype' => 'comment'));
      $nCorrectionPk = $pnCorrectionPk;
      $oForm->addField('input', 'start', array('type' => 'hidden', 'value' => $nStart));
      $oForm->addField('input', 'end', array('type' => 'hidden', 'value' => $nEnd));
    }

    $oForm->addField('input', 'correctionfk', array('type' => 'hidden', 'value' => $pnCorrectionPk));

    $oForm->setFormParams('formComment', true, array('action' => $sUrlSave, 'noCancelButton' => 'noCancelButton', 'submitLabel' => 'Save Comment'));
    $oForm->addField('sselect', 'type', array('label' => 'Type:'));
    foreach ($this->_aCommentTypes as $sValue => $sLabel)
    {
      $aOptions = array('value' => $sValue, 'label' => $sLabel);
      if($sValue==$oComment->getFieldValue('type'))
        $aOptions['selected']='selected';
      $oForm->addOption('type', $aOptions);
    }
    $oForm->setFieldControl('type', array('jsFieldNotEmpty' => 'jsFieldNotEmpty'));

    $oForm->addField('input', 'comment', array('label' => 'Comment:', 'type'=>'text', 'value' => $oComment->getFieldValue('comment'), 'id' => 'comment'));
    $oForm->setFieldControl('comment', array('jsFieldNotEmpty' => 'jsFieldNotEmpty'));

    $aSearchValues = $this->_getTrainerComments();
    $sArray = json_encode($aSearchValues);

    $nSelectedImportance = ($bIsEdition) ? (int)$oComment->getFieldValue('importance') : 1;
    for ($i=1; $i<=3; $i++)
    {
      $sPicture = $oHTML->getPicture($this->getResourcePath().'pictures/'.$i.'stars.gif');
      $aOptions = array('value' => $i, 'label' => $sPicture);
      if($i==$nSelectedImportance)
        $aOptions['checked']='checked';
      $oForm->addField('radio', 'importance', $aOptions);
    }
    $oForm->setFieldControl('importance', array('jsFieldNotEmpty' => 'jsFieldNotEmpty'));

    return array(
        'data' => $oForm->getDisplay().'<span id=\'darrow\' class=\'darrow\'></span>',
        'action' => '$( "#comment" ).autocomplete({ source: '.$sArray.' }); sSelect(); moveAddForm('.$nStart.'); selectText('.$nStart.', '.$nEnd.'); '
        );
  }

  // Gets the Trainer's comment, used for autocompletion comment field
  private function _getTrainerComments()
  {

    $aComments = array(
      'SUBJECT LINES: Limit your Subject Lines to 4 words or fewer, and at the same time take care to make them specific (to the topic)
        and clean (thus possibly giving your message a slight advantage over others around it in your reader’s inbox).',
      'Using the phrase “Ahead of …” helps you head straight to the topic and avoid including longer details which are not appropriate
         before your Request.',
      'Use softeners (“Sorry for the rush but”) when requesting tight deadlines, especially to more senior people.',
      'THANK: Don’t forget to say thank you after you make a request.',
      'FORMALITY: “Regards” is fine for most internal messages.',
      'ORDER: Even for very short messages like this, you must follow the correct global logic order.
        That means you must give your REQUEST before your REASON',
      'UNNECESSARY DETAILS: This message really only needs 2 steps: REQUEST + THANK.  Omit all others.',
      'Where possible, avoid using expressions like “you forgot”, which sound unnecessarily accusatory.
        Instead, you can say something more neutral like “There was no attachment.”',
        'DETAILS: Include all necessary (useful) details.',
        'Indent important details',
        'SUBJECT LINE: More important item should always be first.',
        'ORDER: More important information (request for spreadsheet) first.',
        'Use a stronger tone for a second request.  In this case, that means using a non question  (“Please send …”) question format,
          and avoiding softeners (“I know you are busy”) and misleadingly light and friendly Closings (“Thanks a lot for your help!”)',
        'Don\'t say “Thanks in advance for your help” in cases where there is a time pressure which has been caused by the reader.',
        'Use bullets or numbers to make multiple requests easy for your reader.',
        'VOCABULARY: It is confusing to use the word “cancelled” here because the meeting has not been cancelled; it has been “postponed”.
          Be careful that you don’t confuse your readers with incorrect vocabulary like this.',
        'SUBJECT LINE: Focus on your Alternative if it is good enough.',
        'WORDINESS: Reduce all steps, particularly Consideration, if your alternative is good.',
        'PHRASING: Choose phrasing which minimizes any potential shock to the reader.
          Instead of “We can’t provide it today” it’s better (less shocking) to say “We can’t provide it until tomorrow”.',
        'ALTERNATIVE: Just attach the non confidential information now, as it will save both you and Kim some time.',
        'Avoid “Best regards” if your message content is even slightly negative.',
        'SUBJECT LINE: Focus on the fact that the original request was achieved.',
        'Minimize Consideration if your Alternative is good.  In this case, your Alternative is almost the same as the original
          request, so you should not include any Consideration words (such as “Unfortunately” or “Regrettably”) which might sound misleadingly negative.',
        'COMMUNICATION: You need to include this step here because circumstances are different from the reader’s original expectations.',
        'Remember that in general the TONE of a Problem message should be distinguishably heavier than the TONE of a Request message.',
        'SUBJECT LINE: Include your clear Solution for a stronger tone',
        'Use a stronger tone when you make your requests; No softeners',
        'SOLUTION: Include a clear statement of BOTH solutions (immediate and ongoing)',
        'RISK: If you include a Risk, you should connect it to the nearer of the Solutions (the immediate request for the answers today).',
        'You should minimize the sense of the problem here because it may have been caused by your side.
        Avoid unnecessary use of words like Problem, and avoid excessive apologizing.',
        'SPELLING: Always double check that people’s names and company names are spelt correctly.',
        'SUBJECT LINE: Focus on your Solution (not your Apology) in your Subject Line',
        'If you can, use clear strong language to describe your SOLUTION (no weak words like “I think” “might” “maybe” etc.)
           because your reader is wanting as much reassurance as possible.',
        'PHRASING: READER-SPECIFIC SOLUTION: Your solution should be written so that it looks as attractive as possible for your reader
          (reports on time); rather than focusing on what you did (request your staff)',
        'WORDINESS: Keep Reasons brief (unless they are connected to your Prevention)',
        'ORDER: Even for very short messages like this, you must follow the correct global logic order.
         That means you must give your SOLUTION before your APOLOGY',
        'UNNECESSARY DETAILS: This message really only needs 2 steps: SOLUTION + APOLOGY.  You can omit all others.',
        'Match the level of the Apology with the level of the Problem.  This was a very minor problem, so your Apology should be correspondingly short.',
        'Remember that you must use very strong adjectives and adverbs in positive Goodwill situations like this. Otherwise you risk sounding insincere.',
        'PHRASING: Giving a hopeful statement about the future is a good way to finish this kind of message,
          even if you’re not sure you’ll be able even to see the person again.',
        'DETAILS: Maximize details in Goodwill situations.',
        'PYRAMID LOGIC: This case is best handled using Chapter 2 Information (with auxiliary requests included as details).
         The main point is the announcement of your boss’s absence because that information is the only part relevant to ALL of the readers of the message.',
        'Use bullets or numbers to make multiple requests easy for your reader.',
        'To maximize results, you should use your boss’s authority (“She has requested”) rather than your own (“Please …”)',
        'CLOSING: You can use “Regards” here, but the preferred technique is to omit the Closing.
         This is reasonable when your message is going out to many people.
         Omitting the Closing conveys an official sense, as would be appropriate for a memo.',
        'DETAILS: Include your boss’s name here; if you say only “my boss” it risks confusing some of the readers.',
        'GRAMMAR: Note that “software” is uncountable.  So you have to say “some software” or “a software tool”.',
        'OBJECTIONS / COUNTERS: Sometimes creating an equation is a persuasive way to overcome your Objections, as below:
          You may think at USD 2,500 the software seems expensive especially with our tighter budget.  But if we consider the time saved,
          we see that it will pay for itself within 18 months (1 hour/report x 85 reports/ year x USD 20 /employee hour).',
        'ACTION: You should include an Action step in this message:
        I would be happy to arrange everything if you just give me the go-ahead.  Or, if you would prefer, I could organize a demo.',
        'SUBJECT LINE: You should focus on your Alternative in your Subject Line here.
          And in this case, you can present the Alternative as a Request (which is softer) rather than as a Command (which is rather strong).',
        'CONSIDERATION: It is preferred to include Consideration in this case because you are in fact rejecting a staff member who is making
          a request that you yourself encouraged just a month ago!',
        'VOCABULARY: Avoid using the word “difficult” in Rejection situations.  Use direct language like “I can’t permit …” instead.',
        'Note that it is softer to reject “anyone” than it is to reject “you”.  This is a good technique to remember to use, when it is appropriate.',
        'ALTERNATIVE: I have made your alternative more realistic. For a manager, it is probably not prudent to make a promise six months into the future.',
        'COMMUNICATION: You should include this step to show you are open to discussion in case your staff needs it.',
        'CLOSING: Avoid “Best regards” if your message content, like this one, is a little negative.',
        'CLOSING: Avoid “Kind regards” if your message content, like this one, is a little negative.',
        'PUNCTUATION: If you type a comma after your greeting, then you must type a comma after you closing salutation (“Regards,”).  If you don’t type a comma after your greeting, then, keeping consistent, you should not type a comma after your closing salutation.',
        'This case can be handled as a Request situation, or as Persuasion.  But it is probably likely to get the fastest response if it is handled as a simple Rejection.',
        'Even though you are Rejecting someone superior to you, you must use a strong tone here.  The strong tone is not disrespectful; rather it conveys the fact that
          the situation isn’t really negotiable due to the unreasonable demands.',
        'Make sure to provide 2 clear outcome situations: 1. What is needed to meet the new deadline;
          2. What should be expected if no changes are made to the current situation.
          This will accentuate the necessity of taking action.',
        'Make sure you include a clear Communication statement which unmistakably “puts the ball in the reader’s court”
          (thus enforcing that the next action be taken by the reader).',
        'Use a phrase to highlight that there is a discrepancy between the figure Sandra mentioned, and the correct figure which you are providing.',
        'Present the key information as a list of points.  This is easiest for your reader.  Of course the Amount should be first because it is the most important in this case.',
        'SUBJECT LINE: It is helpful for you reader if you include the number of requests (3) in your Subject Line.',
        'GROUPING: Group related requests under their respective dates, to make it even easier for your reader.',
        'DETAILS: You should state clearly that you will pay for the tennis.',
        '“Regards” feels a little cold to your old friend June.  It’s better to use something like “Best regards”, etc.',
        'SUBJECT LINE: Including the information the reader has requested in the Subject Line (if it is short enough,
          which it is in this case) speeds up the communication.',
        'PHRASING: Use short phrases because the information that Glenn has asked for is simple.',
        'TIMING: Remember that simple emails can provide you with opportunities to include other information added as an extra item.',
        'Minimize text in messages like this. You need to present the data so it is easiest for your reader, and you also need to consider
          that the reader may need to refer to the message more than once.',
        'GROUPING: Group the information as much as possible. If you find that you have written something twice, like a date, then it may mean you could group the data even more efficiently.',
        'Just a standard “Thanks” is enough here, even though the request is a little complex.
          This is because this request appears to be a standard sort of request to the reader in this case.',
        'Maximize details in Goodwill cases.  Challenge yourself to add a couple of extra sentences.',
        'Remember to use strong adjectives and adverbs in Goodwill cases',
        'DETAILS: Make sure to state clearly that you will pay for the tennis.',
        'PERSUASION: Maximize your persuasiveness by using the tools.  Please review the charts on page 10 of Chapter 7 so that you understand the Feature → Benefit → Example system.
          Then think about how you would expand this persuasion, using the below to help you and filling in the necessary information:
          The benefits are that it is:
          (1) Fast: Finalysis is 30% faster than the current software which means it will save us about one hour per report.  This will free us up to work on other things.
          (2) Automatic: …
          (3) …:  …
          (4) …:  …'
    );

    return $aComments;


    /*
     * ANOTHER VERSION: Giving comments that have been written before
     *
    $oComments = $this->_getModel()->getTrainerComments($this->_nUserPk);
    $bRead = $oComments->readFirst();

    if(!$bRead)
      return array();
    else
    {
      $aComments = array();

      while($bRead)
      {
        $aComments[] = $oComments->getFieldValue('comment');

        $bRead = $oComments->readNext();
      }

      return $aComments;
    } */
  }

  private function _displayStudentEsa($pnAnswerPk, $pbIsStudentView = false)
  {
    if(!assert('is_key($pnAnswerPk)'))
      return '';

    if(!assert('is_bool($pbIsStudentView)'))
      return '';

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');
    $oPage->addCssFile($this->getResourcePath().'css/esa.css');
    $oPage->addJsFile($this->getResourcePath().'js/esa.js');
    $oPage->addJsFile($this->getResourcePath().'js/highcharts.js');
    $oPage->addJsFile($this->getResourcePath().'js/highcharts-more.js');

    $oTest = $this->_getModel()->getEsaFromAnswerPk($pnAnswerPk);
    $oTest->readFirst();
    $aTest = $oTest->getData();

    $bIsEsa2 = false;
    if($aTest['chapter_name'] == 'ESA2')
    {
      $bIsEsa2 = true;
      //fetch data from esa1
      $sQuery = 'SELECT gbtest_answerpk
        FROM gbtest_answer
        INNER JOIN gbtest as gtest ON (gtest.gbtestpk = gbtestfk AND gtest.esa = 1 AND gtest.rank = 1)
        WHERE gbUserfk = '.$aTest['gbuserfk'];

      $oDbResult = $this->_getModel()->executeQuery($sQuery);
      if($oDbResult->readFirst())
      {
        $nEsa1Answer = (int)$oDbResult->getFieldvalue('gbtest_answerpk');
        $oDbResult = $this->_getModel()->getEsaFromAnswerPk($nEsa1Answer);
        $oDbResult->readFirst();
        $aEsa1Test = $oDbResult->getData();
      }
      else
        $aEsa1Test = array();
    }

    $sHTML = '';

    if(!$pbIsStudentView)
    {
      $nStudentPk = (int)$aTest['gbuserfk'];
      $sTitle = $this->_oGbuser->displayMemberLink($nStudentPk).' > ';
      $sTitle.= $oTest->getFieldValue('chapter_name');
    }
    else
    {
      $sTitle = $oTest->getFieldValue('chapter_name');
    }

    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR(1);

    $sHTML .= $oHTML->getBlocStart('esaReport');
      $sHTML.= $oHTML->getBlocStart('stats');

      $sHTML.= $oHTML->getBlocStart('leftCol');

        $sHTML.= $oHTML->getBlocStart('skillSets');

        $sHTML .= $oHTML->getBlocStart('skillSetsHeader');
          $sHTML .= $oHTML->getBloc('', 'Skill', array('class' => 'skill'));
          $sHTML .= $oHTML->getBloc('', '%', array('class' => 'percent'));
          $sHTML .= $oHTML->getBloc('', 'Points', array('class' => 'points'));
        $sHTML .= $oHTML->getBlocEnd();

        $nTotal = 0;
        foreach($this->_aSkillSets as $sShortname => $aSkillSet)
        {
          $nPercentage = round($aTest[$sShortname]);
          $nPoints = ($aSkillSet['points']/100)*$nPercentage;
          $nTotal+= $nPoints;

          if(!$bIsEsa2)
            $nEsa1 = 0;
          else
          {
            if(isset($aEsa1Test[$sShortname]))
              $nEsa1 = round($aEsa1Test[$sShortname]);
            else
              $nEsa1 = 0;
          }

          $sHTML .= $oHTML->getBlocStart($sShortname, array('class' => 'skillSet'));
            $sHTML .= $oHTML->getBlocStart('', array('class' => 'skillText'));
              $sHTML .= $oHTML->getBloc('', $aSkillSet['display'].' ('.$aSkillSet['points'].' points)', array('class' => 'skillname'));
              $sHTML .= $oHTML->getBloc('', $aSkillSet['desc'], array('class' => 'skilldesc'));
            $sHTML .= $oHTML->getBlocEnd();
            $sHTML .= $oHTML->getBlocStart('', array('class' => 'scores'));
              $sHTML .= $oHTML->getBloc('', $nPercentage.'%', array('class' => 'percent', 'value' => $nPercentage, 'prev_value' => $nEsa1));
              $sHTML .= $oHTML->getBloc('', $nPoints);
            $sHTML .= $oHTML->getBlocEnd();
          $sHTML .= $oHTML->getBlocEnd();
        }

        $sHTML .= $oHTML->getBlocStart('skillSetsFooter');
          $sHTML .= $oHTML->getBloc('', 'Total Score', array('class' => 'text'));
          $sHTML .= $oHTML->getBloc('', $nTotal, array('class' => 'total'));
        $sHTML .= $oHTML->getBlocEnd();
        $sHTML.= $oHTML->getBlocEnd();
        $sHTML .= $oHTML->getBloc('', 'native > 900  / advanced 800-900 / high intermediate 700-800 / intermediate 600-700 / low intermediate 500-600 / high basic 400-500 / basic 300-400 / low basic < 300', array('class' => 'desc'));

      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocStart('rightCol');
        $sHTML .= $oHTML->getTitle('Graph','h4');
        $sHTML .= $oHTML->getBloc('esaChart');
        $sHTML .= $oHTML->getCR(1);
        $sHTML .= $oHTML->getBlocStart('speed');
        $sHTML .= $oHTML->getTitle('Speed','h4');
        $nAverageTime = 15*60;
        $nTime = strtotime($aTest['date_submitted'])-strtotime($aTest['date_create']);
        $nTimePercent = round(($nAverageTime/$nTime)*100);
        if($nTimePercent>100)
          $nTimePercent=100;
        $nWidth = (394*$nTimePercent/100);
        $sHTML .= $oHTML->getBloc('speedBar', '<span style="width:'.$nWidth.'px;">'.$nTimePercent.'%</span>');
        $sHTML .= $oHTML->getBloc('', 'Your speed percentage is calculated by dividing your time of '.  round($nTime/60, 1).' minute(s) by the average native completion time of 15 minutes.', array('class' => 'desc'));
        $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getBlocEnd();

    $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getCR(2);

    $sHTML .= $oHTML->getTitle('Trainer\'s recommandations','h1');
    $sHTML .= $oHTML->getBlocStart('esaRecommandations');
    $oEsaScores = $this->_getModel()->getEsaScoresFromAnswerPk($pnAnswerPk);
    $bRead = $oEsaScores->readFirst();
    $sCurrentCategory='';
    while($bRead)
    {
      $aData = $oEsaScores->getData();
      $sShow = '';
      switch($aData['score'])
      {
        case $aData['valmin']:
          $sShow = 'text_bad';
          break;
        case $aData['valmax']:
          $sShow = 'text_good';
          break;
        default:
          $sShow = 'text_average';
          break;
      }
      if($aData['category']!=$sCurrentCategory)
      {
        $sCurrentCategory= $aData['category'];
        $sHTML .= $oHTML->getCR(1);
        $sHTML .= $oHTML->getTitle($this->_aSkillSets[$sCurrentCategory]['display'],'h4');
      }
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'skillComment '.$sShow));
        $sHTML .= $oHTML->getBloc('', $aData['skill'], array('class' => 'skill'));
        $sHTML .= $oHTML->getBloc('', $aData[$sShow], array('class' => 'comment'));
      $sHTML .= $oHTML->getBlocEnd();

      $bRead = $oEsaScores->readNext();
    }
    $sHTML .= $oHTML->getBlocEnd();

    $sHTML .= $oHTML->getBlocEnd();


    return $sHTML;
  }

  private function _displayStudentTest($pnAnswerPk, $pbIsStudentView = false)
  {
    if(!assert('is_key($pnAnswerPk)'))
      return '';

    if(!assert('is_bool($pbIsStudentView)'))
      return '';

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');
    $oLogin = CDependency::getComponentByName('login');
    $oPage->addCssFile($this->getResourcePath().'css/testCorrection.css');
    $oPage->addJsFile($this->getResourcePath().'js/testCorrection.js');
    $aUser = $oLogin->getUserData();
    $bAlert = (bool)getValue('alert', 0);

    $oTest = $this->_getModel()->getTestFromAnswerPk($pnAnswerPk);
    $oTest->readFirst();
    $aTest = $oTest->getData();

    $sHTML = '';

    if(!$pbIsStudentView)
    {
      $nStudentPk = (int)$aTest['gbuserfk'];
      $sTitle = $this->_oGbuser->displayMemberLink($nStudentPk).' > ';
      $sTitle.= $oTest->getFieldValue('rank').'. '.$oTest->getFieldValue('name');
    }
    else
    {
      $sTitle = $oTest->getFieldValue('rank').'. '.$oTest->getFieldValue('name');
    }

    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR(1);

    if($bAlert)
      $sHTML .= $oHTML->getErrorMessage('Before replying a new assignment, please read the comments of your trainer on this page and click on \'I read all comments\' ').$oHTML->getCR(1);

    $sHTML .= $oHTML->getBlocStart();
    $sHTML .= $this->_displayAnswer($aTest, $aUser);
      $sHTML .= $oHTML->getBlocStart('teacherBloc', array('class' => 'bloc'));
      $nTeacherPk = (int)$aTest['corrected_by'];
      if(is_key($nTeacherPk))
        $sTeacher = $this->_oGbuser->getName($nTeacherPk);

      if(empty($sTeacher))
        $sTeacher = 'Trainer';

      $sHTML .= $oHTML->getBloc('', $sTeacher.'\'s Comments', array('class' => 'bloc-header'));
      $nCorrectionPk = (int)$aTest['gbtest_correctionpk'];
      if(!is_key($nCorrectionPk))
        $sHTML .= '<i>No correction has been given yet.</i>';
      else
      {
        $sHTML .= $oHTML->getBlocStart('', array('class' => 'bloc-content'));
          $sHTML .= $oHTML->getBloc('overallComment', $this->_sOverallComment[$aTest['good']]);
          $bShowBoxes = ($aTest['correction_status']!='read');
          $aCommentList = $this->_displayCommentList($nCorrectionPk, false, $bShowBoxes);
          $sHTML .= $oHTML->getBloc('commentList', $aCommentList['data']);
        $sHTML.= $oHTML->getBlocEnd();

        if($aCommentList['nb_comment'] > 0)
          $sHTML.= $this->_displayLegend();
      }
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $this->_displayModel($aTest, $aUser);

    $sHTML.= $oHTML->getBlocEnd();

    $sJavascript = '$(document).ready(function() {'.$aCommentList['action'].'});';
    $oPage->addCustomJs($sJavascript);

    return $sHTML;
  }

  private function _displayModel($paTest, $paStudent)
  {
    if(!assert('is_array($paTest) && !empty($paTest)'))
      return '';

    if(!assert('is_array($paStudent) && !empty($paStudent)'))
      return '';

    $sHTML = '';
    $oHTML = CDependency::getComponentByName('display');

    $sHTML .= $oHTML->getBlocStart('modelEmail', array('class' => 'bloc'));
        $sHTML .= $oHTML->getBloc('', 'Model Answer', array('class' => 'bloc-header'));
        $sHTML .= $oHTML->getBlocStart('mailContent', array('class' => 'bloc-content'));
          $sHTML .= $oHTML->getBloc('mail_from', '<span>From: </span>'.$paStudent['firstname'].' '.$paStudent['lastname']);
          $sHTML .= $oHTML->getBloc('mail_to', '<span>To: </span>'.$paTest['mail_to']);
          $sHTML .= $oHTML->getBloc('mail_title', '<span>Subject: </span>'.$paTest['model_title']);
          $sHTML .= $oHTML->getBloc('mail_content', $paTest['model_content']);
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayTeacherESACorrectionForm($paTest)
  {
    if(!assert('is_array($paTest) && !empty($paTest)'))
      return '';

    //dump($paTest);
    $nAnswerPk = (int)$paTest['gbtest_answerpk'];

    if(!assert('is_key($nAnswerPk)'))
      return '';

    //fetch both ssignment for this ESA.
    //If the result is not 2, it means the assignments were not all done
    $sQuery = 'SELECT t.*, ta.*, tcha.name as chapter_name
                FROM gbtest_answer ta

                INNER JOIN gbtest as t ON (t.gbtestpk = ta.gbtestfk)
                INNER JOIN gbtest_chapter as tcha ON (tcha.gbtest_chapterpk = t.gbtest_chapterfk)

                WHERE ta.gbuserfk = '.(int)$paTest["gbuserfk"].'
                AND ta.status = "sent"
                AND t.esa = 1

                AND t.gbtest_chapterfk = (SELECT gbtest_chapterfk FROM gbtest WHERE gbtestpk = '.(int)$paTest['gbtestfk'].')

                ORDER BY gbtest_answerpk ASC ';


    $oDbAnswer = $this->_getModel()->executeQuery($sQuery);
    $bRead = $oDbAnswer->readFirst();
    $anAnwser = array();
    $anAnwserPk = array();
    while($bRead)
    {
      $anAnwser[] = $oDbAnswer->getData();
      $anAnwserPk[] = (int)$oDbAnswer->getFieldValue('gbtest_answerpk');
      $bRead = $oDbAnswer->readNext();
    }

    /*dump($paTest);
    dump($sQuery);
    dump($anAnwser);*/
    if(!assert('count($anAnwser) == 2'))
      return 'Only 1 assigmnets has been done. (temporary issue)';



    $oCorrection = $this->_getModel()->getByWhere('gbtest_esa_score', '(gbtest_answerfk='.$nAnswerPk.' && corrected_by='.$this->_nUserPk.')');
    $bRead = $oCorrection->readFirst();
    if($bRead)
    {
      $nEsaScorePk = (int)$oCorrection->getFieldValue('gbtest_esa_scorepk');
      $bIsEdition = true;
    }
    else
    {
      $bIsEdition = false;
      $nEsaScorePk = $this->_getModel()->add(array(
          'corrected_by' => $this->_nUserPk,
          'gbtest_answerfk' => $nAnswerPk,
          'status' => 'draft'
      ), 'gbtest_esa_score');

      if (!assert('is_key($nEsaScorePk)'))
        return 'A problem occured. The ESA correction form could not be loaded. Please contact your administrator.';

      $oCorrection = $this->_getModel()->getByPk($nEsaScorePk, 'gbtest_esa_score');
    }

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $sHTML = '';
    $sHTML .= $oHTML->getBloc('commentForm');

    $nStudentPk = (int)$paTest['gbuserfk'];
    $aStudent = $this->_oGbuser->getUser($nStudentPk);

    $oPage->addCssFile($this->getResourcePath().'css/testCorrection.css');
    $oPage->addCssFile($this->getResourcePath().'css/esaCorrection.css');
    $oPage->addJsFile($this->getResourcePath().'js/esaCorrection.js');

    $sTitle = $this->_oGbuser->displayMemberLink($nStudentPk).' > ';
    $sTitle.= $anAnwser[0]['chapter_name'];
    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR();

    $sHTML .= $oHTML->getBlocStart('esaCorrectionForm');

      $sHTML .= $oHTML->getBlocStart('', array('class' => 'stud_answer_block', 'style' => 'width: 49.5%; float: left; position: relative;'));

      $sHTML .= $oHTML->getTitle($anAnwser[0]['name']);
      $sHTML .= $this->_displayAnswer($anAnwser[0], $aStudent, $nEsaScorePk);

      $sHTML .= $oHTML->getCR(2);

      $sHTML .= $oHTML->getTitle($anAnwser[1]['name']);
      $sHTML .= $this->_displayAnswer($anAnwser[1], $aStudent);
      $sHTML .= $oHTML->getBlocEnd();

      $sHTML .= $oHTML->getBlocStart('teacherBloc');

        $sHTML .= $oHTML->getBlocStart('', array('class' => 'bloc'));
          $sHTML .= $oHTML->getBloc('', 'Take Action', array('class' => 'bloc-header'));
          $sHTML .= $oHTML->getBlocStart('actions', array('class' => 'bloc-content'));

          // ****************************//
          // START ESA Correction FORM
          // ****************************//
          $sUrlSave = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_TYPE_TEACHER, $nEsaScorePk, array('datatype' => 'esa'));
          $oForm = $oHTML->initForm('esaForm');
          $oForm->setFormParams('esaForm', true, array('action' => $sUrlSave, 'noCancelButton' => 'noCancelButton', 'submitLabel' => 'Save Assessment'));

          $sCurrentCategory = '';
          $oScores = $this->_getModel()->getEsaScores($nEsaScorePk);
          $aScores = $this->_getModel()->formatOdbResult($oScores, 'gbtest_esa_skillpk');
          $oForm->addField('input', 'skillIds', array('type' => 'hidden', 'value' => implode(',', array_keys($aScores))));
          $oForm->addField('input', 'status', array('type' => 'hidden', 'value' => $oCorrection->getFieldValue('status')));
          $oForm->addField('input', 'gbtest_answerfk', array('type' => 'hidden', 'value' => $oCorrection->getFieldValue('gbtest_answerfk')));

          foreach ($aScores as $nSkillPk => $aData)
          {
            if($aData['category']!=$sCurrentCategory)
            {
              if($sCurrentCategory!='')
                $oForm->closeSection();

              $sCurrentCategory = $aData['category'];
              $oForm->addField('misc', $sCurrentCategory.'Title', array('type' => 'text', 'text' => ucfirst($sCurrentCategory), 'class' => 'sectionTitle'));
              $oForm->addSection($sCurrentCategory.'s', array('class' => 'skillBox'));
            }

            $oForm->addField('input', 'skill_'.$nSkillPk, array('label'=>$aData['skill'].' ('.$aData['valmin'].'-'.$aData['valmax'].'): ', 'value' => $aData['score'], 'type' => 'spinner', 'valMax' => $aData['valmax'], 'valMin' => $aData['valmin']));
            $oForm->addField('input', 'skill_importance_'.$nSkillPk, array('type'=>'hidden', 'value' => $aData['importance']));
            $oForm->setFieldControl('skill_'.$nSkillPk, array('jsFieldMinValue' => $aData['valmin'], 'jsFieldMaxValue' => $aData['valmax'], 'jsFieldTypeInteger' => 'jsFieldTypeInteger', 'jsFieldNotEmpty' => 'jsFieldNotEmpty'));
          }
          $oForm->closeSection();
          $sButton = $oHTML->getLink('Send Correction', 'javascript;', array('class'=>'button-like', 'id' =>'sendForm'));


          $oForm->addField('input', 'ass_list', array('type'=>'hidden', 'value' => implode(',', $anAnwserPk)));

          $sHTML .= $oForm->getDisplay().$sButton;
          // ****************************//
          // END ESA Correction FORM
          // ****************************//

        $sHTML .= $oHTML->getBlocEnd(); // end Actions

        $sHTML .= $oHTML->getBlocEnd(); // end Bloc
      $sHTML .= $oHTML->getBlocEnd(); // end teacherBloc

    $sHTML .= $oHTML->getBlocEnd(); // end esaCorrectionForm

    return $sHTML;
  }

  private function _displayTeacherCorrectionForm($paTest)
  {
    //TODO : normalize fields in database : created_by, date_create

    if(!assert('is_array($paTest) && !empty($paTest)'))
      return '';

    $nAnswerPk = (int)$paTest['gbtest_answerpk'];

    if(!assert('is_key($nAnswerPk)'))
      return '';

    $oCorrection = $this->_getModel()->getByWhere('gbtest_correction', '(gbtest_answerfk='.$nAnswerPk.' && corrected_by='.$this->_nUserPk.')');
    $bRead = $oCorrection->readFirst();
    if($bRead)
    {
      $nCorrectionPk = (int)$oCorrection->getFieldValue('gbtest_correctionpk');
      $bIsEdition = true;
    }
    else
    {
      $bIsEdition = false;
      $nCorrectionPk = $this->_getModel()->add(array(
          'corrected_by' => $this->_nUserPk,
          'gbtest_answerfk' => $nAnswerPk,
          'status' => 'draft'
      ), 'gbtest_correction');

      if (!assert('is_key($nCorrectionPk)'))
        return 'A problem occured. The correction form could not be loaded. Please contact your administrator.';

      $oCorrection = $this->_getModel()->getByPk($nCorrectionPk, 'gbtest_correction');
    }

    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $sHTML = '';
    $sHTML .= $oHTML->getBloc('commentForm');
    $bIsReturned = ($paTest['status']=='returned');

    $nStudentPk = (int)$paTest['gbuserfk'];
    $aStudent = $this->_oGbuser->getUser($nStudentPk);
    $sListCommentUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_TEACHER, $nCorrectionPk, array('datatype' => 'comment'));

    //TODO : handle redirection when the correction status is not sent

    $oPage->addCssFile($this->getResourcePath().'css/testCorrection.css');
    $oPage->addJsFile($this->getResourcePath().'js/testCorrection.js');

    $sTitle = $this->_oGbuser->displayMemberLink($nStudentPk).' > ';
    $sTitle.= $paTest['rank'].'. '.$paTest['name'];
    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR(1);

    $sHTML .= $oHTML->getBlocStart();
      $sHTML .= $this->_displayAnswer($paTest, $aStudent, $nCorrectionPk);

      $sHTML .= $oHTML->getBlocStart('teacherBloc');

        $sHTML .= $oHTML->getBlocStart('', array('class' => 'bloc'));
          $sSubTitle = ($bIsReturned) ? 'Trainer\'s Answer' : 'Take Action';
          $sHTML .= $oHTML->getBloc('', $sSubTitle, array('class' => 'bloc-header'));
          $sHTML .= $oHTML->getBlocStart('actions', array('class' => 'bloc-content'));

          if(!$bIsReturned)
          {
            $sHTML .= $oHTML->getBlocStart('firstAction', array('class' => 'firstAction'));
              $sHTML .= $oHTML->getLink($this->_sOverallComment[1], 'javascript:;', array('class' => 'teacherAction good'));
              $sHTML .= $oHTML->getLink($this->_sOverallComment[0], 'javascript:;', array('class' => 'teacherAction bad'));
            $sHTML .= $oHTML->getBlocEnd();
          }

          $sClass = ($bIsEdition) ? ' edition' : '';
          $sHTML .= $oHTML->getBlocStart('secondAction', array('class' => 'bloc secondAction'.$sClass));
            $sHTML .= $oHTML->getTitle('Your Answer', 'h4');
            $sHTML .= $oHTML->getBlocStart('yourAnswer');
              $sHTML .= $oHTML->getBloc('overallComment', '');
              $aCommentList = $this->_displayCommentList($nCorrectionPk, !$bIsReturned);
              $sHTML .= $oHTML->getBloc('commentList', $aCommentList['data'], array('refreshWith' => $sListCommentUrl));
            $sHTML .= $oHTML->getBlocEnd();
            if(!$bIsReturned)
              $sHTML .= $this->_formCorrection($oCorrection);
          $sHTML .= $oHTML->getBlocEnd(); // end second action

        $sHTML .= $oHTML->getBlocEnd(); // end bloc

        $sHTML .= $oHTML->getBlocEnd(); // end teacher bloc

      $sHTML .= $oHTML->getBlocEnd(); // end main div

    $sJavascript = '$(document).ready(function() {
                    clickAction('.$oCorrection->getFieldValue('good').');
                      '.$aCommentList['action'].'
                  });';
    $oPage->addCustomJs($sJavascript);

    return $sHTML;
  }

  private function _formCorrection($poCorrection)
  {
    if(!assert('is_object($poCorrection)'))
      return '';

    $oPage = CDependency::getComponentByName('page');
    $oHTML = CDependency::getComponentByName('display');

    $sUrlSave = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_TYPE_TEACHER, (int)$poCorrection->getFieldValue('gbtest_correctionpk'), array('datatype' => 'correction'));
    $oForm = $oHTML->initForm('correctionForm');
    $oForm->setFormParams('correctionForm', true, array('action' => $sUrlSave, 'noCancelButton' => 'noCancelButton', 'submitLabel' => 'Save Correction'));
    $oForm->addField('input', 'gbtest_correctionpk', array('type' => 'hidden', 'value' => $poCorrection->getFieldValue('gbtest_correctionpk')));
    $oForm->addField('input', 'good', array('type' => 'hidden', 'value' => $poCorrection->getFieldValue('good')));
    $oForm->addField('input', 'gbtest_answerfk', array('type' => 'hidden', 'value' => $poCorrection->getFieldValue('gbtest_answerfk')));
    $oForm->addField('input', 'status', array('type' => 'hidden', 'value' => $poCorrection->getFieldValue('status')));
    $sButton = $oHTML->getLink('Send Correction', 'javascript;', array('class'=>'button-like', 'id' =>'sendForm'));
    return $oForm->getDisplay().$sButton;
  }

  private function _displayAnswer($paTest, $paStudent, $pnCorrectionPk = 0)
  {
    if(!assert('is_array($paTest) && !empty($paTest)'))
      return '';

    if(!assert('is_array($paStudent) && !empty($paStudent)'))
      return '';

    $bIsTeacher = ($this->_oGbuser->aUserData['gbusertype']=='teacher');
    $bIsCorrecting = (is_key($pnCorrectionPk));

    $sHTML = '';
    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $sHTML .= $oHTML->getBlocStart('studentBloc', array('class' => 'bloc studentBloc'));
        $sTitle = ($bIsTeacher) ? 'Student Original Email' : 'Your Answer';
        $sHTML .= $oHTML->getBloc('', $sTitle, array('class' => 'bloc-header'));
        $sHTML .= $oHTML->getBlocStart('mailContent', array('class' => 'bloc-content'));
        if($bIsTeacher && $bIsCorrecting)
        {
          $sAddCommentUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_TYPE_TEACHER, $pnCorrectionPk, array('datatype' => 'comment'));
          $sHTML .= $oHTML->getLink('Add Comment', 'javascript:getSelectionHtml();', array('class' => 'button-like', 'id' => 'addCommentLink', 'formUrl' => $sAddCommentUrl));
        }
        $sHTML .= $oHTML->getBloc('noSelection', 'Please select text first.');
        $sHTML .= $oHTML->getBloc('mail_from', '<span>From: </span>'.$paStudent['firstname'].' '.$paStudent['lastname']);
        $sHTML .= $oHTML->getBloc('mail_to', '<span>To: </span>'.$paTest['mail_to']);
        $sHTML .= $oHTML->getBloc('mail_title', '<span>Subject: </span><mail_title>'.$paTest['mail_title_html'].'<mail_title>');
        $sHTML .= $oHTML->getBloc('mail_content', '<mail_content>'.$paTest['mail_content_html'].'</mail_content>');
      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  /*
  private function _displayBreadCrumb($pnPk, $psType = 'user')
  {
    // TODO : use ONE function to generate breadcrumb

    $nStudentPk = (int)$aAnswer['gbuserfk'];
    $aStudent = $this->_oGbuser->getUser($nStudentPk);
    $nGroupPk = (int)$aStudent['gbuser_groupfk'];
    $aGroup = $this->_oGbuser->getGroup($nGroupPk);
    $nCompanyPk = (int)$aGroup['gbuser_companyfk'];
    $oTest = $this->_getModel()->getByPk((int)$aAnswer['gbtestfk'], 'gbtest');

    $sTitle = $this->_oGbuser->displayCompanyLink($nCompanyPk).' > ';
    $sTitle.= $this->_oGbuser->displayGroupLink($nGroupPk).' > ';
    $sTitle.= $this->_oGbuser->displayMemberLink($nStudentPk, 'tests').' > ';
  }*/

  // Displays a report of the student activity to supervisors
  // Trainers, Admins, HR

  private function _displaySupervisorSheet($pnStudentPk)
  {
    if(!assert('is_key($pnStudentPk)'))
      return '';

    $sHTML = '';
    $oHTML = CDependency::getComponentByName('display');
    $oLogin = CDependency::getComponentByName('login');
    $oPage = CDependency::getComponentByName('page');

    $aStudent = $this->_oGbuser->getUser($pnStudentPk);
    $nGroupFk = (int)$aStudent['gbuser_groupfk'];
    $aGroup = $this->_oGbuser->getGroup($nGroupFk);
    $aCompany = $this->_oGbuser->getCompany((int)$aGroup['gbuser_companyfk']);
    $nCompanyFk = (int)$aCompany['gbuser_companypk'];

    $sGroup = $this->_oGbuser->displayGroupLink($nGroupFk);
    $sCompany = $this->_oGbuser->displayCompanyLink($nCompanyFk, false, 'tests');
    $sStudentName = $oLogin->getUserNameFromData($aStudent);
    $sTitle = $sCompany.' > '.$sGroup.' > '.$sStudentName;
    $sUserType = $this->_oGbuser->aUserTypesConst[$this->_sUserType];

    $sHTML .= $oHTML->getTitle($sTitle,'h1');
    $sHTML .= $oHTML->getCR(1);

    $sHTML .= $oHTML->getBlocStart('studentProfile', array('class' => 'dlist-item'));
    $sHTML .= $sStudentName.' - '.$aStudent['email'];
    $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getCR(1);

    $aTests = $this->_getModel()->getStudentSheet($pnStudentPk, (int)$aStudent['gbuser_groupfk']);

    $sHTML .= $oHTML->getBlocStart('studentSheet', array('class' => 'dlist hrmanagerList testsList'));
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-header'));
        $sHTML .= $oHTML->getBloc('', 'Chapter', array('class' => 'chapter'));
        $sHTML .= $oHTML->getBloc('', 'Assignments', array('class' => 'name'));
        $sHTML .= $oHTML->getBloc('', 'Submitted on', array('class' => 'submitted'));
        $sHTML .= $oHTML->getBloc('', 'Returned on', array('class' => 'status'));
      $sHTML .= $oHTML->getBlocEnd();

      $bEsa = false;
      foreach ($aTests as $aTest)
      {
        $sDataType = ($aTest['esa']=='1') ? 'esa' : 'test';
        if($aTest['esa']=='1' && !$bEsa)
        {
          $sHTML .= $oHTML->getCR(1);
          $bEsa = true;
          $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-header'));
            $sHTML .= $oHTML->getBloc('', 'Chapter', array('class' => 'chapter'));
            $sHTML .= $oHTML->getBloc('', 'Assessments', array('class' => 'name'));
            $sHTML .= $oHTML->getBloc('', 'Submitted on', array('class' => 'submitted'));
            $sHTML .= $oHTML->getBloc('', 'Returned on', array('class' => 'status'));
          $sHTML .= $oHTML->getBlocEnd();
        }

        $aDivOptions = array('class' => 'dlist-item');

        $sSubmitted = '';
        if(is_datetime($aTest['date_submitted']))
        {
          $dSubmitted = date('Y-m-d', strtotime($aTest['date_submitted']));
          $sSubmitted .= $dSubmitted.' ';
          if($this->_sUserType!='teacher')
            $sSubmitted .= ($dSubmitted>$aTest['deadline']) ? $oHTML->getPicture(CONST_PICTURE_TIMEOVER) : $oHTML->getPicture(CONST_PICTURE_TESTOK);
        }
        else
          $sSubmitted .= ($this->_sUserType=='teacher') ? '-' : $oHTML->getPicture(CONST_PICTURE_TESTNOTOK);

        $sReturned = '';
        $sUrlViewTest = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, $sUserType, $aTest['gbtest_answerpk'], array('filter' => $sDataType));
        $sUrlEditTest = $oPage->getUrl($this->csUid, CONST_ACTION_EDIT, $sUserType, $aTest['gbtest_answerpk'], array('filter' => $sDataType));

        if($aTest['status']==='returned')
        {
          $sReturned .= date('Y-m-d', strtotime($aTest['date_returned']));
          if($sUserType==CONST_TYPE_TEACHER)
            $aDivOptions['class'].= ' returned';
          $aDivOptions['onClick']='javascript:window.location.href=\''.$sUrlViewTest.'\';';
        }
        elseif($aTest['status']==='sent')
        {
          $sReturned .= '-';
          if($sUserType==CONST_TYPE_TEACHER)
          {
            $aDivOptions['class'].= ' active';
            $sUrl = $sUrlEditTest;
          }
          else
            $sUrl = $sUrlViewTest;

          $aDivOptions['onClick']='javascript:window.location.href=\''.$sUrl.'\';';
        }
        else
        {
          $sReturned .= '-';
        }

        $sHTML .= $oHTML->getBlocStart('', $aDivOptions);
          $sHTML .= $oHTML->getBloc('', $aTest['chaptername'], array('class' => 'chapter'));
          $sHTML .= $oHTML->getBloc('', $aTest['rank'].'. '.$aTest['name'], array('class' => 'name'));
          $sHTML .= $oHTML->getBloc('', $sSubmitted, array('class' => 'submitted'));
          $sHTML .= $oHTML->getBloc('', $sReturned, array('class' => 'status'));
        $sHTML .= $oHTML->getBlocEnd();
      }
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displaySupervisorList($psFilter, $pnPk=0)
  {
    if(!assert('is_string($psFilter) && !empty($psFilter)'))
      return '';

    if(!assert('is_numeric($pnPk)'))
      return '';

    $sHTML = '';
    $oHTML = CDependency::getComponentByName('display');
    $oPage = CDependency::getComponentByName('page');

    $aStudentIds = array();
    $nFilterGroup = 0;

    if($psFilter == 'group')
    {
      if(!assert('is_key($this->cnPk)'))
        return '';

      $aGroup = $this->_oGbuser->getGroup($this->cnPk);
      $nFilterGroup = $aGroup['gbuser_grouppk'];

      $sTitle = $this->_oGbuser->displayCompanyLink((int)$aGroup['gbuser_companyfk'], false, 'tests').' > '.$aGroup['name'];
      if($this->_oGbuser->aUserData['gbusertype'] == 'gbadmin')
      {
        $sBaseUrl = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_TYPE_GBADMIN, 0);
        $sTitle = $oHTML->getLink('All participants', $sBaseUrl).' > '.$sTitle;
      }
      $aStudentIds = $this->_oGbuser->getStudentsIdsForGroup($this->cnPk);

      $oDbGroup = $this->_getModel()->getByWhere('gbuser_group', 'gbuser_grouppk = '.$this->cnPk);
    }
    elseif ($psFilter=='company')
    {
      if(!assert('is_key($pnPk)'))
        return '';

      $aCompany = $this->_oGbuser->getCompany($pnPk);
      $sTitle = $aCompany['name'];
      $sUserType = $this->_oGbuser->aUserData['gbusertype'];
      if(($sUserType=='gbadmin') || ($sUserType=='teacher'))
      {
        $sBaseUrl = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, $this->_oGbuser->aUserTypesConst[$sUserType], 0, array('datatype' => 'all'));
        $sTitle = $oHTML->getLink('All Participants', $sBaseUrl).' > '.$sTitle;
        $aStudentIds = array_values($this->_oGbuser->getStudentsIdsForCompany($pnPk));
      }
      else
      {
        $aStudentIds = array_keys($this->_oGbuser->aUsersData);
      }

      //fetch the list of groups
      $oDbGroup = $this->_getModel()->getByWhere('gbuser_group', 'gbuser_companyfk = '.$pnPk);
    }
    else
    {
      $sTitle = 'All Participants';
      $aStudentIds = array_keys($this->_oGbuser->aUsersData);
      $oDbGroup = $this->_getModel()->getByWhere('gbuser_group');
    }


    $asGroup = array();
    $bRead = $oDbGroup->readFirst();
    while($bRead)
    {
      $asGroup[$oDbGroup->getFieldValue('gbuser_grouppk')] = $oDbGroup->getFieldValue('name');
      $bRead = $oDbGroup->readNext();
    }


    $oPage->addJsFile($this->getResourcePath().'js/search.js');

    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR(1);

    if(empty($aStudentIds))
      return $sHTML.'No result was found.';

    $aTestResults = $this->_getModel()->getStudentResults($aStudentIds, $nFilterGroup);

    $sHTML .= $oHTML->getBlocStart('hrStudentsList', array('class' => 'dlist hrmanagerList'));

      $aSearchValues = $this->_oGbuser->getAutocompletionData();
      $sArray = json_encode($aSearchValues);
      $sHTML.= $oHTML->getBloc('searchDiv', '<script>var aSearchValues = '.$sArray.';</script><input id=\'search\' type=\'text\'>', array('class' => 'formField'));

        $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-header'));
          $sHTML .= $oHTML->getBloc('', 'Name', array('class' => 'name'));

          if($psFilter == 'company')
            $sHTML .= $oHTML->getBloc('', 'Group', array('class' => 'group'));
          elseif($psFilter=='all')
            $sHTML .= $oHTML->getBloc('', 'Company', array('class' => 'group'));

          $sHTML .= $oHTML->getBlocStart('', array('class' => 'test'));
          $sHTML .= $oHTML->getBlocStart('', array('class' => 'tests'));

          $asDisplayed = array();
          foreach($aTestResults as $nTestPk => $asGroupTest)
          {
            foreach($asGroupTest['data'] as $aTest)
            {
              if(!in_array($aTest['rank'], $asDisplayed))
              {
                if($aTest['esa']==1)
                  $sContent = $aTest['rank'].'*';
                else
                  $sContent = $aTest['rank'];

                $sHTML .= $oHTML->getBloc($nTestPk, $sContent, array('title' => $aTest['chapter_name'], 'class' => 'mark'));
                $asDisplayed[] = $aTest['rank'];
              }
            }
          }

          $sHTML .= $oHTML->getBlocEnd();
          $sHTML .= $oHTML->getBlocEnd();
        $sHTML .= $oHTML->getBlocEnd();


        foreach($aStudentIds as $nStudentId)
        {
          $nStudentId = (int)$nStudentId;

          $aStudent = $this->_oGbuser->getUser($nStudentId);
          $sStudent = $this->_oGbuser->displayMemberLink($nStudentId, 'student');

          $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-item'));
            $sHTML .= $oHTML->getBloc('', $sStudent, array('class' => 'name'));
            if($psFilter=='company')
            {
              $nGroup = 0;
              foreach($aTestResults as $nTestPk => $asGroupTest)
              {
                if(in_array($nStudentId, $asGroupTest['members']))
                {
                  $nGroup++;
                }
              }
              if($nGroup <= 1)
                $sGroup = $this->_oGbuser->displayGroupLink((int)$aStudent['gbuser_groupfk']);
              else
                $sGroup = 'multiple groups ('.$nGroup.')';

              $sHTML .= $oHTML->getBloc('', $sGroup, array('class' => 'group'));
            }
            elseif ($psFilter=='all')
            {
              $nGroupPk = (int)$aStudent['gbuser_groupfk'];
              $aGroup = $this->_oGbuser->getGroup($nGroupPk);
              $sCompany = $this->_oGbuser->displayCompanyLink((int)$aGroup['gbuser_companyfk'], false, 'tests');
              $sHTML .= $oHTML->getBloc('', $sCompany, array('class' => 'group'));
            }

            $sHTML .= $oHTML->getBlocStart('', array('class' => 'test'));
              $sHTML .= $oHTML->getBlocStart('', array('class' => 'tests'));

              $asAssigRow = array();
              foreach($aTestResults as $nTestPk => $asGroupTest)
              {
                //dump($asGroupTest);
                //dump($asGroupTest['members']);

                  foreach($asGroupTest['data'] as $nGroupPk => $aTest)
                  {


            /*dump('START !! * * ** * ** * * *  *');
            dump($aTest);
            dump(' is student '.$nStudentId.' in the group ('.$nGroupPk.') member array ?? ['. implode(',',$asGroupTest['members'][$nGroupPk]) .']');
            */
            if(in_array($nStudentId, (array)$asGroupTest['members'][$nGroupPk]))
            {
              /*dump(' * * ** * ** * * *  *');
              dump('student '.$nStudentId.'   ___ in ___ this group');
              */

                    //student is in this group, if there's not answwer --> 0
                    if(!isset($aTest['results'][$nStudentId]))
                      $aTest['results'][$nStudentId] = 0;


                    if($aTest['active'] > 0)
                    {
                      //dump('thhis asigment is active');
                      //dump($aTest['results']);

                      $sTitle = 'Assig #'.$nTestPk.' '.$aTest['chapter_name'].' / grouppk '.$nGroupPk.' / student '.$nStudentId.' ';
                      if($aTest['results'][$nStudentId] != $aTest['nbtests'])
                      {
                        $sContent = $oHTML->getPicture(CONST_PICTURE_TESTNOTOK, $sTitle);
                      }
                      else
                        $sContent = $oHTML->getPicture(CONST_PICTURE_TESTOK, $sTitle);

                      $asAssigRow[$nGroupPk][$nTestPk.'_'.$nGroupPk] = $oHTML->getBloc($nTestPk.'_'.$aStudent['gbuserpk'], $sContent, array('class' => 'mark'));
                    }
                    else
                    {
                      //dump('thhis asigment is NOT active');

                      /*if($aTest['active'] == 0)
                      {
                        $sContent = '';
                      }
                      else
                        $sContent = $oHTML->getPicture(CONST_PICTURE_TESTNOTOK);*/

                      $sContent = '<span style="opacity: 0.25;" title="Chapter not available for this group">-</span>';
                      $asAssigRow[$nGroupPk][$nTestPk.'_'.$nGroupPk] = $oHTML->getBloc($nTestPk.'_'.$aStudent['gbuserpk'], $sContent, array('class' => 'mark'));
                    }

                    //$sHTML .= $oHTML->getBloc($nTestPk.'_'.$aStudent['gbuserpk'], $sContent);

                    }
            /*else
            {
              dump(' student '.$nStudentId.' not in this group');
              dump(' END chapter '.$nTestPk.' * * ** * ** * * *  *');
            }*/
                  }

              }

              //dump($asAssigRow);
              $nGroups = count($asAssigRow);
              foreach($asAssigRow as $nGroup => $asHTML)
              {
                $sHTML.= '<div style="float: left;">'.implode('', $asHTML);

                if($nGroups > 1 && isset($asGroup[$nGroup]))
                  $sHTML.= '<div style="float: right; width: 150px; font-size: 10px; text-align: right; font-style: italic; color: #999;">('.$asGroup[$nGroup].')</div>';

                $sHTML.= '</div>';
              }



            $sHTML.= $oHTML->getBlocEnd();
          $sHTML.= $oHTML->getBlocEnd();
          $sHTML.= $oHTML->getBlocEnd();
        }

    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _formStudentAnswer($pnPk)
  {
    // TODO: replace ugly alert messages
    // TODO: Automatic saving in draft
    if(!assert('is_key($pnPk)'))
      return '';

    $oHTML = CDependency::getComponentByInterface('do_html');
    $oPage = CDependency::getComponentByName('page');
    $oLogin = CDependency::getComponentByName('login');
    $sHTML = '';

    $oPage->addJsFile($this->getResourcePath().'js/test.js');

    $oTest = $this->_getModel()->getTestForStudent($pnPk, $this->_nUserPk);
    $bRead = $oTest->readFirst();
    if(!$bRead)
      return 'Assignment not found. Please contact your administrator.';

    $aTest = $oTest->getData();
    $bIsESA = ((bool)$oTest->getFieldValue('esa'));

    $sStatus = $oTest->getFieldValue('status');
    if(empty($sStatus))
      $sStatus = 'draft';
    $nAnswerPk = (int)$oTest->getFieldValue('gbtest_answerpk');

    $sHTML .= $oHTML->getTitle('Assignment '.$oTest->getFieldValue('rank').': '.$oTest->getFieldValue('name'), 'h1');
    $sHTML .= $oHTML->getCR(1);

    // Opening a confirmation popup if it's a ESAs
    if($bIsESA && (empty($aTest['date_create']) || $aTest['date_create']=='0000-00-00 00:00:00'))
    {
      if((int)$aTest['t_rank'] == 1)
      {
        $sMessage = '<strong>Are you sure you want to answer now?</strong>';
        $sMessage.= '<br /><br /> You are about to start answering '.$aTest['chapter_name'].': '.$aTest['name'];
        $sMessage.= '<br /> A timer will start after you click \"START TEST\". It is not possible to step back then.';
        $sMessage.= '<br /> Are you sure you want to proceed answering?';
        $sButtonLabel = 'START TEST';
      }
      else
      {
        $sMessage = 'You have completed the first part of '.$aTest['chapter_name'].'.<br />';
        $sMessage.= 'To continue to the second part of the assessment please click on the continue button.';
        $sButtonLabel = 'CONTINUE TEST';
      }

      $sAjaxUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_TYPE_STUDENT, 0, array('gbtestpk' => $aTest['gbtestpk'], 'startEsa' => 1));
      $sJavascript = '$(document).ready(function() {';
      $sJavascript .= 'goPopup.setPopupConfirm(\''.$sMessage.'\', \'AjaxRequest(\"'.$sAjaxUrl.'\")\', \'window.location.href=\"/\"\', \''.$sButtonLabel.'\', \'Cancel\', \'\', 600, 200);';
      $sJavascript .= '});';
      $oPage->addCustomJs($sJavascript);

      return $sHTML;
    }

    if($bIsESA)
      $sHTML .= $oHTML->getBloc('timer', '<span>Started:</span> '.$aTest['date_create']);

    if($sStatus!=='draft')
    {
       $sHTML .= $oHTML->getErrorMessage('You already sent your answer to that assignment. It is impossible to send it twice. Please keep in mind that your answer is definitive when you click on the \'SEND\' button of this form.');
       $sHTML .= $oHTML->getCR(1);
       $sHTML .= $oHTML->getLink('Back to Assignment List', $oPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0), array('class' => 'button-like'));
       return $sHTML;
    }

    $sHTML .= $oHTML->getBlocStart('test_answer_form_'.$pnPk, array('class' => 'test-answer-form'));

      $sHTML .= $oHTML->getBlocStart('test_description', array('class' => 'bloc test-description'));
        $sHTML .= $oHTML->getBloc('', 'Assignment brief', array('class' => 'bloc-header'));
        $sHTML .= $oHTML->getBlocStart('', array('class' => 'bloc-content'));
        $sHTML .= $oTest->getFieldValue('content');
        $sHTML .= $oHTML->getBlocEnd();
      $sHTML .= $oHTML->getBlocEnd();

      $sHTML .= $oHTML->getBlocStart('test_answer', array('class' => 'bloc test-answer'));
        $sHTML .= $oHTML->getBloc('', 'Your reply', array('class' => 'bloc-header'));

        $sUrlSave = $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_SAVEEDIT, CONST_TYPE_STUDENT, $pnPk);
        $oForm = $oHTML->initform('testAnswer');
        $oForm->setFormParams('testAnswer', true, array('action' => $sUrlSave, 'noCancelButton' => 'noCancelButton', 'submitLabel' => 'Save Answer'));

        $oForm->addField('misc', 'mail_from', array('label' => 'From:', 'type' => 'text', 'text' => $oLogin->getCurrentUserName()));
        $sMailTo = $oTest->getFieldValue('mail_to');
        if(!empty($sMailTo))
          $oForm->addField('misc', 'mail_to', array('label' => 'To:', 'type' => 'text', 'text' => $sMailTo));

        $oForm->addField('input', 'mail_title', array('label' => 'Subject:', 'value' => $oTest->getFieldValue('mail_title'), 'placeholder' => 'Subject Line', 'class' => 'noCopyPaste'));
        $oForm->setFieldControl('mail_title', array('jsFieldNotEmpty' => '1'));

        $oForm->addField('textarea', 'mail_content', array('label' => '', 'value' => $oTest->getFieldValue('mail_content'), 'placeholder' => 'Type your email here...', 'class' => 'noCopyPaste'));
        $oForm->setFieldControl('mail_content', array('jsFieldNotEmpty' => '1'));

        if($sStatus==='draft')
        {
          $oForm->addSection('info'); $sLast_Update = $oTest->getFieldValue('last_update');
          $sInfoText = 'This is a draft version of your answer, last updated '.$oHTML->getNiceTime($sLast_Update).'. <span id=\'clickSend\'>Click the \'Send Answer\' button to submit it definitely.</span>';
          $oForm->addField('misc', 'infotext', array('type' => 'text', 'text' => $sInfoText));
          $oForm->closeSection();
        }

        if(is_key($nAnswerPk))
          $oForm->addField('input', 'gbtest_answerpk', array('type'=> 'hidden', 'value' => $nAnswerPk));

        $oForm->addField('input', 'status', array('type'=> 'hidden', 'value' => $sStatus));
        $oForm->addField('input', 'gbtestpk', array('type'=> 'hidden', 'value' => $oTest->getFieldValue('gbtestpk')));
        $sButton = $oHTML->getLink('Send Answer', 'javascript;', array('class'=>'button-like', 'id' =>'sendForm'));
        $sForm = $oForm->getDisplay().$sButton;

        $sHTML .= $oHTML->getBloc('answer_'.$pnPk, $sForm, array('class' => 'bloc-content'));
      $sHTML .= $oHTML->getBlocEnd();

    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayStudentList()
  {
    $sHTML = '';
    $sFilter = getValue('filter', 'all');
    $sDataType = getValue('datatype', 'test');

    if($sDataType == 'esa')
      $bEsa = true;
    else
    {
      if($sFilter == 'all')
        $bEsa = null;
      else
        $bEsa = false;
    }

    //dump($sFilter.' // '.$sDataType);
    $oDbResult = $this->_getModel()->getStudentSchedule($this->_nUserPk, $this->_nUserGroupPk, $sFilter, $bEsa);

    $oHTML = CDependency::getComponentByInterface('do_html');
    $oPage = CDependency::getComponentByName('page');

    if($sDataType=='esa')
      $sTitle = 'Assessments';
    else
      $sTitle = $sFilter.' assignments';

    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR(1);

    $bRead = $oDbResult->readFirst();

    if(!$bRead)
    {
      $sHTML .= 'No assignment found.';
    }
    else
    {
      //fetch the unread corrections, getting only one is good enough
      $oDbCorrection = $this->_getModel()->getUnreadCorrectedTest($this->_nUserPk);
      $bUnredCorrection = $oDbCorrection->readFirst();
      if($bUnredCorrection)
      {
        $nUnReadCorrection = (int)$oDbCorrection->getFieldValue('gbtestpk');
        $nUnReadRank = (int)$oDbCorrection->getFieldValue('rank');
      }
      else
      {
        $nUnReadCorrection = $nUnReadRank = 0;
      }

      //dump($nUnReadCorrection.' // '.$nUnReadRank);

      $aData = $this->_getModel()->formatOdbResult($oDbResult);
      //dump($aData);

      $sHTML .= $oHTML->getBlocStart('studentSchedule', array('class' => 'dlist testsList'));
        $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-header'));
          $sHTML .= $oHTML->getBloc('', 'Chapter', array('class' => 'chapter'));
          $sHTML .= $oHTML->getBloc('', 'Assignment', array('class' => 'test'));
          $sHTML .= $oHTML->getBloc('', 'Status', array('class' => 'status'));
          $sHTML .= $oHTML->getBloc('', 'Deadline', array('class' => 'deadline'));
          $sHTML .= $oHTML->getBloc('', 'Submitted', array('class' => 'submitted'));
        $sHTML .= $oHTML->getBlocEnd();


      $sToday = date('Y-m-d');
      $bFlag = true;
      $nNextAssig = 0;
      $bEsa11 = $bEsa21 = false;

      foreach($aData as $aRow)
      {
        $bDisplayRow = true;

        if(empty($aRow['deadline']) || $aRow['deadline'] == '000-00-00')
          $bFlag = false;

        if($bFlag)
        {
          $sStatus = (empty($aRow['status']) || ($aRow['status']=='draft')) ? 'active' : $aRow['status'];
          $aRow['deadline_title'] = '';

          $bEsa = ((int)$aRow['esa']==1);
          if($bEsa && $aRow['rank'] == 1)
          {
            if($aRow['chaptername'] == 'ESA1')
              $bEsa11 = true;
            else
              $bEsa21 = true;
          }

          $aDivOptions = array('class' => 'dlist-item '.$sStatus);
          switch($sStatus)
          {
            case 'active':
            {
              if(empty($nNextAssig))
              {
                $sUrlAnswer = $oPage->getUrl($this->getComponentUid(), CONST_ACTION_EDIT, CONST_TYPE_STUDENT, $aRow['gbtestpk']);
                $aDivOptions['onClick']='javascript:window.location.href=\''.$sUrlAnswer.'\';';
                $nNextAssig = $aRow['rank'];
              }
              else
              {
                $sStatus = 'Locked';
                $aDivOptions['class'] = 'dlist-item locked';
                $aDivOptions['title'] = 'Assignments need to be done in order. Please submit assignment #'.$nNextAssig.'.';
                $aDivOptions['onClick']= '$(this).tooltip().blur().mouseenter();';
              }

              if($aRow['deadline'] < $sToday)
              {
                $aDivOptions['class'].= ' deadline_past ';
                $aRow['deadline_title'] = 'The deadline is past, you are late for this assignment';
              }

              //dump($bEsa);
              if($bEsa)
              {
                if($aRow['rank'] != 1)
                {
                  //ESA 1 or 2 with Rank == 2
                  //if the ESAX rank 1 row is displayed, we hide this one
                  if($aRow['chaptername'] == 'ESA1' && $bEsa11)
                    $bDisplayRow = false;

                  if($aRow['chaptername'] == 'ESA2' && $bEsa21)
                    $bDisplayRow = false;
                }
              }
            }
            break;
            case 'returned':
            {
              if($bEsa)
              {
                $sFilter = 'esa';
                if($aRow['rank'] != 1)
                {
                  //ESA 1 or 2 with Rank == 2
                  //if the ESAX rank 1 row is displayed, we hide this one
                  if($aRow['chaptername'] == 'ESA1' && $bEsa11)
                    $bDisplayRow = false;

                  if($aRow['chaptername'] == 'ESA2' && $bEsa21)
                    $bDisplayRow = false;
                }
              }
              else
              {
                $sFilter = 'test';
              }

              $sUrlView = $oPage->getUrl($this->getComponentUid(), CONST_ACTION_VIEW, CONST_TYPE_STUDENT, $aRow['gbtest_answerpk'], array('filter' => $sFilter));
              $aDivOptions['onClick'] = 'javascript:window.location.href=\''.$sUrlView.'\';';

            }
            break;
            case 'sent':
            {
              $aDivOptions['title'] = 'Assignment #'.$aRow['rank'].': waiting for evaluation...';
              $aDivOptions['onClick'] = '$(this).tooltip().blur().mouseenter();';
            }
          }

          //if tehre are unread crrections, we overwrite all onclick to tell user to read the correction
          if($nUnReadCorrection > 0)
          {
            //if($nUnReadCorrection == $aRow['rank'])
            if($nUnReadCorrection == $aRow['gbtestpk'])
            {
              $aDivOptions['class'].= ' new_correction';
              $aRow['name'].= '<span style="float: right; padding-right: 50px; font-weight: bold;"> check assignment</span>';
            }
            elseif($sStatus != 'returned')
            {
              $aDivOptions['title'] = 'You need to read the correction given for assignment #'.$nUnReadRank.' before answering any other assignments.';
              $aDivOptions['onClick'] = '$(this).tooltip().blur().mouseenter();';
            }
          }

          if($bDisplayRow)
          {
            $sHTML .= $oHTML->getBlocStart('test_'.$aRow['gbtestpk'], $aDivOptions);

              $sHTML .= $oHTML->getBloc('chapter_'.$aRow['gbtestpk'], $aRow['chaptername'], array('class' => 'chapter'));
              $sHTML .= $oHTML->getBloc('test_'.$aRow['gbtestpk'], $aRow['rank'].'. '.$aRow['name'], array('class' => 'test'));
              $sHTML .= $oHTML->getBloc('status_'.$aRow['gbtestpk'], ucfirst($sStatus), array('class' => 'status'));
              $sHTML .= $oHTML->getBloc('date_deadline_'.$aRow['gbtestpk'], $aRow['deadline'], array('class' => 'deadline', 'title' => $aRow['deadline_title']));
              $sHTML .= $oHTML->getBloc('date_submitted_'.$aRow['gbtestpk'], $oHTML->getNiceTime($aRow['date_submitted']), array('class' => 'submitted'));

            $sHTML .= $oHTML->getBlocEnd();
          }
        }
      }
      $sHTML .= $oHTML->getBlocEnd();
    }

    return $sHTML;
  }


  // TODO: Filter inbox per company, group, student, add a pager

  private function _displayTeacherInbox()
  {
    $aStudentIds = array_keys($this->_oGbuser->aUsersData);
    $sHTML = '';
    $sFilter = getValue('filter','all');
    $sDataType = getValue('datatype','all');

    $oHTML = CDependency::getComponentByInterface('do_html');
    $oPage = CDependency::getComponentByName('page');

    switch($sDataType)
    {
      case 'all':
        $sTitle = 'Inbox';
        $sLabelTest = 'Test';
        break;

      case 'esa':
        $sTitle = 'Assessments';
        $sLabelTest = 'ESA';
        break;

      default:
        $sTitle = 'Assignments';
        $sLabelTest = 'Ass.';
        break;
    }

    $sHTML .= $oHTML->getTitle($sTitle, 'h1');
    $sHTML .= $oHTML->getCR(1);

    if(empty($aStudentIds))
    {
      $sHTML .= 'No record found.';
      return $sHTML;
    }

    $oDbResult = $this->_getModel()->getTestsForTeacher($aStudentIds, $sDataType);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
    {
      $sHTML .= 'No record found.';
    }
    else
    {
      $aData = $this->_getModel()->formatOdbResult($oDbResult);


      $sHTML .= $oHTML->getBlocStart('teacherTests', array('class' => 'dlist testsList'));
        $sHTML .= $oHTML->getBlocStart('', array('class' => 'dlist-header'));
          $sHTML .= $oHTML->getBloc('', $sLabelTest, array('class' => 'test'));
          $sHTML .= $oHTML->getBloc('', 'Student', array('class' => 'name'));
          $sHTML .= $oHTML->getBloc('', 'Company', array('class' => 'company'));
          $sHTML .= $oHTML->getBloc('', 'Group', array('class' => 'group'));
          $sHTML .= $oHTML->getBloc('', 'Received', array('class' => 'received'));
          $sHTML .= $oHTML->getBloc('', '', array('class' => 'status'));
        $sHTML .= $oHTML->getBlocEnd();


        //check if there are ESAs to corrct in the list:
        //display row ranked 1 if both ESA assessments have been sent
        $anEsaReady = array();
        foreach($aData as $aRow)
        {
          $bEsa = ((int)$aRow['esa'] == 1);
          if($bEsa)
          {
            if(isset($anEsaReady[$aRow['gbuserfk']][$aRow['chapter_name']]))
              $anEsaReady[$aRow['gbuserfk']][$aRow['chapter_name']]++;
            else
              $anEsaReady[$aRow['gbuserfk']][$aRow['chapter_name']] = 1;
          }
        }

        //dump($anEsaReady);
        foreach($aData as $aRow)
        {
          $sStatus = ($aRow['status']=='sent') ? 'active' : $aRow['status'];
          $bEsa = ((int)$aRow['esa'] == 1);
          $bDisplay = true;

          if($bEsa)
          {
            if(isset($anEsaReady[$aRow['gbuserfk']][$aRow['chapter_name']]) && $anEsaReady[$aRow['gbuserfk']][$aRow['chapter_name']] >= 2)
            {
              //this ESAx needs to be corrected, we display only rank 1 row, rank2 hidden
              if((int)$aRow['rank'] != 1)
                $bDisplay = false;
            }
            else
            {
              //hidden
              $bDisplay = false;
            }
          }


          $aDivOptions = array('class' => 'dlist-item '.$sStatus);
          if($sStatus == 'active')
          {
            $sUrlAnswer = $oPage->getUrl($this->getComponentUid(), CONST_ACTION_EDIT, CONST_TYPE_TEACHER, $aRow['gbtest_answerpk'], array('datatype' => 'answer'));
            $aDivOptions['onClick']='javascript:window.location.href=\''.$sUrlAnswer.'\';';
          }
          if($sStatus == 'returned')
          {
            $sFilter = ((bool)$aRow['esa']) ? 'esa' : 'test';
            $sUrlView = $oPage->getUrl($this->getComponentUid(), CONST_ACTION_VIEW, CONST_TYPE_TEACHER, $aRow['gbtest_answerpk'], array('datatype' => 'answer', 'filter' => $sFilter));
            $aDivOptions['onClick']='javascript:window.location.href=\''.$sUrlView.'\';';
          }

          if($bDisplay)
          {
            $sHTML .= $oHTML->getBlocStart('test_'.$aRow['gbtestpk'], $aDivOptions);

              $sLabelRank = $aRow['rank'];
              if($sDataType == 'all')
              {
                if($bEsa)
                  $sLabelRank = $aRow['chapter_name'];
                else
                  $sLabelRank = 'Ass.'.$sLabelRank;
              }
              $sHTML .= $oHTML->getBloc('rank_'.$aRow['gbtestpk'], $sLabelRank, array('class' => 'test'));
              $sHTML .= $oHTML->getBloc('name_'.$aRow['gbtestpk'], $this->_oGbuser->getName((int)$aRow['gbuserfk'], true), array('class' => 'name'));
              $sHTML .= $oHTML->getBloc('company_'.$aRow['gbtestpk'], $this->_oGbuser->getCompanyName((int)$aRow['gbuserfk']), array('class' => 'company'));
              $sHTML .= $oHTML->getBloc('group_'.$aRow['gbtestpk'], $this->_oGbuser->getGroupName((int)$aRow['gbuserfk']), array('class' => 'group'));
              $sHTML .= $oHTML->getBloc('date_submitted_'.$aRow['gbtestpk'], date('Y-m-d', strtotime($aRow['date_submitted'])), array('class' => 'received'));
              $sHTML .= $oHTML->getBloc('status_'.$aRow['gbtestpk'], ($sStatus=='returned') ? $oHTML->getPicture(CONST_PICTURE_TESTOK) : 'Active', array('class' => 'status'));

            $sHTML .= $oHTML->getBlocEnd();
          }
        }
      $sHTML .= $oHTML->getBlocEnd();
    }

    return $sHTML;
  }

  private function _saveComment()
  {
    $bIsEdition = (is_key($this->cnPk));

    $nStart = (int)getValue('start', 0);
    $nEnd = (int)getValue('end', 0);
    $nCorrectionFk = (int)getValue('correctionfk', 0);
    $sComment = getValue('comment', '');
    $sType = getValue('type', '');

    if(empty($sType))
      return array('error' => 'Please chose a type of comment within the list.');

    $nImportance = (int)getValue('importance',1);

    $aData = array(
        'comment' => $sComment,
        'type' => $sType,
        'importance' => $nImportance
    );

    if(!$bIsEdition)
    {
      $aData ['gbtest_correctionfk'] = $nCorrectionFk;
      $aData ['end'] = $nEnd;
      $aData ['start'] = $nStart;
      $nCommentPk = $this->_getModel()->add($aData, 'gbtest_correction_point');

      if(!assert('is_key($nCommentPk)'))
        return array('error' => 'Sorry. We could not save your comment. Please contact your administrator.');
    }
    else
    {
      $aData['gbtest_correction_pointpk']=$this->cnPk;

      $bUpdated = $this->_getModel()->update($aData, 'gbtest_correction_point');

      if(!assert('$bUpdated'))
        return array('error' => 'Sorry. We could not update your comment. Please contact your administrator.');
    }

    return array('action' => 'refreshCommentList();');
  }


  private function _saveEsa($pnEsaScorePk)
  {

    $sStatus = getValue('status', 'draft');
    $sAssigList = getValue('ass_list');

    if(!assert('is_key($pnEsaScorePk)'))
      return array('error' => 'Sorry. We could not save your ESA Assessment. ID was missing. Please contact your administrator.');

    $asAssignList = explode(',', $sAssigList);
    if(count($asAssignList) != 2 || !is_arrayOfInt($asAssignList))
      return array('error' => 'Sorry. Could not fetch the 2 assignments.');

    $sSkillIds = getValue('skillIds', '');
    if(empty($sSkillIds))
      return array('error' => 'Sorry. We could not save your ESA Assessment. Skill Ids were missing. Please contact your administrator.');

    $aSkillIds = explode(',', $sSkillIds);

    $aScores = array(
          'gbtest_esa_scorefk' => array(),
          'gbtest_esa_skillfk' => array(),
          'score' => array()
        );

    foreach($aSkillIds as $sSkillId)
    {
      $sScore = getValue('skill_'.$sSkillId);
      $aScores['gbtest_esa_scorefk'][] = (int)$pnEsaScorePk;
      $aScores['gbtest_esa_skillfk'][] = (int)$sSkillId;
      $aScores['score'][] = (int)$sScore;
    }

    $this->_getModel()->deleteByFk($pnEsaScorePk, 'gbtest_esa_score_detail', 'gbtest_esa_score');
    $nScoresPk = $this->_getModel()->add($aScores, 'gbtest_esa_score_detail');

    if(!is_key($nScoresPk))
      return array('error' => 'Sorry. We could not save your ESA Assessment. Impossible to save scores. Please contact your administrator.');

    if($sStatus=='sent')
    {
      $oAnswer = $this->_getModel()->getAnswerFromEsaScore($pnEsaScorePk);
      $oAnswer->readFirst();
      $nSpeed = strtotime($oAnswer->getFieldValue('date_create'))-strtotime($oAnswer->getFieldValue('date_send'));
      $aTotalScores = $this->_getModel()->getEsaTotalScore($pnEsaScorePk);
      $aTotalScores['speed']=$nSpeed;

      $aDatab = array(
          'gbtest_esa_scorepk' => $pnEsaScorePk,
          'status' => $sStatus,
          'date_send' => date('Y-m-d H:d:s')
      );

      $aData = array_merge($aDatab, $aTotalScores);

      $bUpdated = $this->_getModel()->update($aData, 'gbtest_esa_score');

      if(!$bUpdated)
        return array('error' => 'Sorry. We could not send your ESA Assessment. There was a technical issue. Please contact your administrator.');

      //$nAnswerFk = (int)getValue('gbtest_answerfk', 0);
      $aAnswerData = array(
          /*'gbtest_answerpk' => $nAnswerFk,*/
          'status' => 'returned',
          'date_returned' => $aData['date_send']
      );

      $bUpdated = $this->_getModel()->update($aAnswerData, 'gbtest_answer', 'gbtest_answerpk IN ('.$sAssigList.')');

      if(!$bUpdated)
        return array('error' => 'Sorry. We could not notify the student your assessment has been posted. There was a technical issue. Please contact your administrator.');
    }

    $sText = ($sStatus == 'draft') ? 'sent' : 'saved';
    $oPage = CDependency::getComponentByName('page');
    $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_TEACHER, 0, array('filter' => 'teacher'));
    return array('notice' => 'Your ESA assessment has been '.$sText.' successfully.', 'timedUrl' => $sUrl);
  }

  private function _saveCorrectionRed($pnCorrectionPk)
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    if(!assert('is_key($pnCorrectionPk)'))
      return $oHTML->getErrorMessage('Sorry. We could not save your correction. Correction ID was missing. Please contact your administrator.');

    $sStatus = getValue('status');
    $aData = array(
        'status' => $sStatus,
        'gbtest_correctionpk' => $pnCorrectionPk
    );

    $bUpdated = $this->_getModel()->update($aData, 'gbtest_correction');
    if(!$bUpdated)
      return $oHTML->getErrorMessage('Sorry. We could not send your correction. There was a technical issue. Please contact your administrator.');

    // - - - - - - -- - - - - - -- - - - - - -- - - - -
    //All saved, let's figure out where to send the user

    //1. is there another unread correction ?
    $oReturned = $this->_getModel()->getUnreadCorrectedTest($this->_nUserPk);
    $bRead = $oReturned->readFirst();
    if($bRead)
    {
      $nTestAnswerPk = (int)$oReturned->getFieldValue('gbtest_answerpk');
      $sUrl = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_STUDENT, $nTestAnswerPk, array('filter' => 'test'));
      return $oHTML->getRedirection($sUrl.'&next_correction');
    }

    //2. is there another test to do
    $oActiveTests = $this->_getModel()->getStudentSchedule($this->_nUserPk, $this->_nUserGroupPk, 'active', null);
    $bRead = $oActiveTests->readFirst();
    if($bRead)
    {
      $nTestPk = $oActiveTests->getFieldValue('gbtestpk');
      $sUrl = $oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_STUDENT, $nTestPk);
      return $oHTML->getRedirection($sUrl.'&next_test');
    }

    return $oHTML->getRedirection('/');
  }

  private function _saveCorrection($pnCorrectionPk)
  {
    $nGood = (int)getValue('good', -1);
    $sStatus = getValue('status', 'draft');

    if(!assert('is_key($pnCorrectionPk)'))
      return array('error' => 'Sorry. We could not save your correction. Correction ID was missing. Please contact your administrator.');

    if(!assert('is_numeric($nGood)'))
      return array('error' => 'Sorry. We could not save your correction. Overall comment was missing. Please contact your administrator.');

    $aData = array(
        'gbtest_correctionpk' => $pnCorrectionPk,
        'good' => $nGood,
        'status' => $sStatus
    );

    if($sStatus=='sent')
      $aData['date_send']=date('Y-m-d H:d:s');

    $bUpdated = $this->_getModel()->update($aData, 'gbtest_correction');

    if(!$bUpdated)
      return array('error' => 'Sorry. We could not send your correction. There was a technical issue. Please contact your administrator.');

    if($sStatus=='sent')
    {
      $nAnswerFk = (int)getValue('gbtest_answerfk', 0);
      $aAnswerData = array(
          'gbtest_answerpk' => $nAnswerFk,
          'status' => 'returned',
          'date_returned' => $aData['date_send']
      );

      $bUpdated = $this->_getModel()->update($aAnswerData, 'gbtest_answer');

      if(!$bUpdated)
        return array('error' => 'Sorry. We could not notify the student your answer has been posted. There was a technical issue. Please contact your administrator.');

      $nMail = $this->_notifyStudent($nAnswerFk);
    }

    $sText = ($sStatus == 'draft') ? 'saved' : 'sent';
    $oPage = CDependency::getComponentByName('page');
    $sUrl = $oPage->getUrl('196-002', CONST_ACTION_LIST, CONST_TYPE_TEACHER, 0, array('filter' => 'teacher'));
    return array('notice' => 'Your correction has been '.$sText.' successfully.', 'timedUrl' => $sUrl);
  }

  // Notifies a student that he received a correction from a trainer

  private function _notifyStudent($pnAnswerFk)
  {
    if(!assert('is_key($pnAnswerFk)'))
      return 0;

    $oTest = $this->_getModel()->getTestFromAnswerPk($pnAnswerFk);
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $bRead = $oTest->readFirst();

    if(!$bRead)
      return 0;

    $aTest = $oTest->getData();
    $nStudentPk = (int)$oTest->getFieldValue('gbuserfk');
    $aData = $this->_oGbuser->getUser($nStudentPk);

    $oMail = CDependency::getComponentByName('mail');
    $oMail->createNewEmail();
    $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
    $oMail->addRecipient($aData['email'], $aData['firstname'].' '.$aData['lastname']);

    $sType = ((bool)$aTest['esa']) ? 'ESA' : 'Ass.' ;
    $sFilter = ((bool)$aTest['esa']) ? 'esa' : 'test' ;
    $sUrl = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_TYPE_STUDENT, $pnAnswerFk, array('filter' => $sFilter));
    $sContent = 'Dear '.$aData['firstname'].' '.$aData['lastname'].','.$oHTML->getCR(2);
    $sContent .= 'You receveid a correction from a trainer on Globus Online Coaching for the following test:'.$oHTML->getCR(1);
    $sContent .= $sType.$aTest['rank'].': '.$aTest['name'].$oHTML->getCR(2);
    $sContent .= 'Please '.$oHTML->getLink('check it out', $sUrl).' as soon as you can.'.$oHTML->getCR(1);

    return $oMail->send('Globus Online Coaching: You received a trainer\'s correction', $sContent);
  }

  private function _saveStudentAnswer()
  {
    $sTitle = getValue('mail_title','');
    $sContent = getValue('mail_content','');
    $nTestPk = (int)getValue('gbtestpk',0);
    $sStatus = getValue('status', 'draft');
    $nAnswerPk = (int)getValue('gbtest_answerpk', 0);
    $bStartEsa = (bool)getValue('startEsa', 0);

    $bIsEdition = (is_key($nAnswerPk));

    if(!assert('is_key($nTestPk)'))
      return array('error' => 'Sorry. We could not save your answer. Test ID was missing. Please contact your administrator.');

    $aData = array(
        'mail_content' => $sContent,
        'mail_title' => $sTitle,
        'gbtestfk' => $nTestPk,
        'status' => $sStatus,
        'gbuserfk' => $this->_nUserPk,
        'last_update' => date('Y-m-d H:i:s')
    );

    if($sStatus=='sent')
    {
      if(!assert('!empty($sTitle)'))
        return array('error' => 'Sorry. We could not send your answer. Mail title was missing. Please contact your administrator.');

      if(!assert('!empty($sContent)'))
        return array('error' => 'Sorry. We could not send your answer. Mail content was missing. Please contact your administrator.');

      $aData['date_submitted']=date('Y-m-d H:i:s');

      // Saving the answer in an HTML form that will be used on the teacher correction form
      // Title
      $nLenghtTitle = strlen($sTitle);
      $sMailTitleHtml = '';
      $nCount=1;
      for ($i=0; $i<$nLenghtTitle; $i++)
      {
        $sMailTitleHtml .= '<z id="'.$nCount.'">'.$sTitle[$i].'</z>';
        $nCount++;
      }

      $aContent = explode("\n", $sContent);
      $aContentFinal = array();
      foreach ($aContent as $sContent)
      {
        $nLenght = strlen($sContent);
        $sNewString = '';
        for ($i=0; $i<$nLenght; $i++)
        {
          $sNewString .= '<z id="'.$nCount.'">'.$sContent[$i].'</z>';
          $nCount++;
        }
        $aContentFinal[] = $sNewString;
      }

      $sContentHtml = implode('<br />', $aContentFinal);
      $aData['mail_title_html']=$sMailTitleHtml;
      $aData['mail_content_html']=$sContentHtml;
    }

    if($bIsEdition)
    {
      $aData['gbtest_answerpk'] = $nAnswerPk;
      $bUpdated = $this->_getModel()->update($aData, 'gbtest_answer');

      if(!$bUpdated)
        return array('error' => 'Sorry. We could not update your answer. There was a technical issue. Please contact your administrator.');
    }
    else
    {
      $nAnswerPk = $this->_getModel()->add($aData, 'gbtest_answer');

      if(!is_key($nAnswerPk))
        return array('error' => 'Sorry. We could not save your answer. There was a technical issue. Please contact your administrator.');
    }

    $sText = ($sStatus == 'draft') ? 'saved' : 'sent';
    $oPage = CDependency::getComponentByName('page');

    if($bStartEsa)
    {
      $sUrlEsa = $oPage->getUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_STUDENT, $nTestPk);
      return array('action' => 'window.location.href=\''.$sUrlEsa.'\';');
    }

    $sUrl = $oPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_TYPE_STUDENT, 0);
    if($sStatus=='sent')
    {
      $oTest=$this->_getModel()->getByPk($nTestPk, 'gbtest');
      $nChapterPk = (int)$oTest->getFieldValue('gbtest_chapterfk');
      $nNextTestPk = $this->_getModel()->getActiveTestForChapter($nChapterPk, $this->_nUserPk);
      if(is_key($nNextTestPk))
        $sUrl = $oPage->getUrl($this->_getUid(), CONST_ACTION_EDIT, CONST_TYPE_STUDENT, $nNextTestPk);
    }
    return array('notice' => 'Your answer has been '.$sText.' successfully.', 'timedUrl' => $sUrl);
  }

  private function _deleteComment($pnCommentPk)
  {
    if(!assert('is_key($pnCommentPk)'))
      return array('error' => 'Your comment could not be deleted. Id was not found. Please contact your administrator.');

    $this->_getModel()->deleteByPk($pnCommentPk, 'gbtest_correction_point');

    return array('action' => 'refreshCommentList();');
  }

  public function deleteCorrections($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return false;

    $this->_getModel()->deleteCorrections($pnUserPk);
    return true;
  }

  public function deleteTests($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return false;

    $this->_getModel()->deleteTests($pnUserPk);
    return true;
  }

  public function getNbAnsweredTestsForStudent($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return 0;

    $oResult = $this->_getModel()->getAnsweredTestsForStudent($pnUserPk, true);
    return (int)$oResult->getFieldValue('nb');
  }

  public function getNbReturnedTestsForTeacher($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return 0;

    $oResult = $this->_getModel()->getReturnedTestsForTeacher($pnUserPk, true);
    return (int)$oResult->getFieldValue('nb');
  }


  private function _displayLegend()
  {
    $oHTML = CDependency::getCpHtml();

    $sHTML = $oHTML->getBlocStart('', array('class' => 'grade_legend'));

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'row'));
      $sHTML.= '<div class="star">&nbsp;</div><div class="star">&nbsp;</div><div class="star">&nbsp;</div><div class="text">really important</div><div class="floatHack"></div>';
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'row'));
      $sHTML.= '<div class="star">&nbsp;</div><div class="star">&nbsp;</div><div class="text">quite important</div><div class="floatHack"></div>';
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'row'));
      $sHTML.= '<div class="star">&nbsp;</div><div class="text">important</div><div class="floatHack"></div>';
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocEnd();
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }
}
