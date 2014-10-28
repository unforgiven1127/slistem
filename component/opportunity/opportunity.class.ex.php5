<?php

require_once('component/opportunity/opportunity.class.php5');

class COpportunityEx extends COpportunity
{
  private $_userPk;
  private $_rightManage;
  private $_rightAdmin;
  private $_rightPay;
  private $_rightProductSupervisor;
  private $casStatusColors = array('ongoing' => '#4572a7', 'proposal' => '#4572a7', 'pitched' => '#4572a7', 'verbal_agreement' => '#4572a7',
      'failed' => '#777777', 'stalled' => '#BA4341', 'signed' => '#89a54e', 'projected' => '#9982B4', 'paid' => '#F4C802');


  public function __construct()
  {
    $oLogin = CDependency::getCpLogin();
    $this->_userPk = $oLogin->getUserPk();
    $this->_rightAdmin = $oLogin->isAdmin();

    if($this->_rightAdmin)
    {
      $this->_rightAccess = true;
      $this->_rightManage = true;

      //allow to change status to paid
      $this->_rightPay = true;

      //allow to set status to booked and?or delivered
      $this->_rightProductSupervisor = true;
    }
    else
    {
      //Admin --> Pay --> Supervisor --> manager
      $oRight = CDependency::getComponentByName('right');
      $this->_rightAccess = $oRight->canAccess('555-123', 'ppav', 'opp', 0);
      $this->_rightManage = $oRight->canAccess('555-123', 'right_op_managment', '', 0);
      $this->_rightPay = $oRight->canAccess('555-123', 'right_op_paiement', '', 0);
      $this->_rightProductSupervisor = $oRight->canAccess('555-123', 'right_op_supervisor', '', 0);
    }

    return true;
  }


  public function getDefaultType()
  {
    return CONST_OPPORTUNITY;
  }

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    return $asActions;
  }

  public function getStatusColor()
  {
    return $this->casStatusColors;
  }

  public function getHtml()
  {
    $this->_processUrl();
    if(!$this->_rightAccess)
      return '';

    switch($this->csAction)
    {
       case CONST_ACTION_LIST:
         if(getValue('display') == 'bbook')
           return $this->_displayBBook();

         return $this->_displayList();
         break;

       case CONST_ACTION_FULL_LIST:
         return $this->_displayFullList();
         break;

      case CONST_ACTION_ADD:
      case CONST_ACTION_EDIT:
        return $this->_form();
         break;
    }
  }

  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csAction)
    {
      case CONST_ACTION_VIEW :
        return $this->_displaySingleRow($this->cnPk);
        break;

      case CONST_ACTION_SAVEADD :
       return json_encode($this->_saveAddOpportunity());
        break;
      case CONST_ACTION_SAVEEDIT :
        if($this->csType == CONST_OPPORTUNITY)
          return json_encode($this->_saveEditOpportunity());

        return json_encode($this->_saveEditOpportunityDetail());
        break;
      case CONST_ACTION_FASTEDIT :
        return json_encode($this->_saveFastEditOpportunity());
        break;

      case CONST_ACTION_DELETE :
        return json_encode($this->_deleteOpportunity($this->cnPk));
        break;

      case CONST_ACTION_ADD:
      case CONST_ACTION_EDIT:

        $oPage = CDependency::getCpPage();
        return json_encode($oPage->getAjaxExtraContent($this->_form(true)));
         break;
    }
  }

  public function getCronJob()
  {
    $this->_processUrl();
    echo 'Opportunity cron <br />';

    //notify users a document has been shared with them
    $day = date('l');
    $time = (int)date('H');

    if(($day=='Monday' && $time == 6) || (getValue('custom_uid') == '555-123' && getValue('forcecron')))
    {
      echo 'Notify users<br />';
      $this->_getCronOpportunity();
    }
  }





  // ***************************** //
  //
  // Change the above functions after
  // updating the _aProducts array
  //
  // ***************************** //

  private $_aProducts = array(
      'TW Magazine content' => array(
          'Advertorial', 'Other page/article'
          ),
      'TW Magazine advertisement' => array(
          'Cover', 'Spread', '1 page', 'Half page', 'Quarter page'
          ),
      'TW Website' => array(
          'Banner', 'Post', 'Adv. feature (page/category)'
          ),
      'Media' => array(
          'Website Building','Brochure Design','Video Production','Web Marketing','Newsletter','Animation','Web Design','Digital content'
          ),
      'IT' => array(
          'Consulting', 'Helpdesk/support', 'Infrastructure', 'Software development', 'Outsourcing'
          )
  );

  private function _getTWProducts()
  {
    return array_merge($this->_aProducts['TW Magazine advertisement'], $this->_aProducts['TW Magazine content']);
  }

  private function _getProducts($psProductType)
  {
    switch($psProductType)
    {
      case 'production':
        return $this->_aProducts['Media'];
        break;

      case 'promotion':
        return array_merge($this->_aProducts['TW Magazine advertisement'], $this->_aProducts['TW Magazine content'], $this->_aProducts['TW Website']);
        break;

      case 'IT':
        return $this->_aProducts['IT'];
        break;

      default:
        break;
    }
  }

  private function _isProduction($pnProduct)  {    return (in_array($pnProduct, $this->_getProductionProducts()));  }

  private function _isPromotion($pnProduct)  {    return (in_array($pnProduct, $this->_getPromotionProducts()));  }

  private function _isIT($pnProduct)  {    return (in_array($pnProduct, $this->_getItProducts()));  }

  private function _isTw($pnProduct)  {    return (in_array($pnProduct, $this->_getTWProducts()));  }

  // ***************************** //
  //    END OF FUNCTIONS
  // ***************************** //

  /**
   * Function to send the notification email once a week ro remind people their unclosed opportunities
   * @return boolean value
   */

  private function _getCronOpportunity()
  {
    $oLogin = CDependency::getCpLogin();
    $oMail = CDependency::getComponentByName('mail');
    $oSettings = CDependency::getComponentByName('settings');

    $aLastReminder = $oSettings->getSystemSettings('oppcron');
    $nLastReminder = $aLastReminder['oppcron'];
    if(empty($nLastReminder))
      $nLastReminder = 0;
    $nNow = time();
    $nInterval = 24*60*60;

    if(($nNow-$nLastReminder)>$nInterval)
    {
      echo 'Start Cron';
      $asActiveUsers = $oLogin->getUserList(0, true);

      $nMonth = (int)date('m');
      $sTimeNow = mktime(0, 0, 0, $nMonth, 1, date('Y'));
      $sTimeMax = strtotime('+1 month', $sTimeNow);
      $sTimeMin = strtotime('-1 month', $sTimeNow);

      $sDateMin = date('Y-m-d', $sTimeMin);
      $sDateMax = date('Y-m-d', $sTimeMax);

      foreach ($asActiveUsers as $nLoginPk => $aUserData)
      {
        $sHTML = '';
        $oOpp = $this->_getModel()->getOpportunitiesByUserPk($nLoginPk, $sDateMin, $sDateMax, '', array('pitched', 'proposal', 'stalled'));
        $bRead = $oOpp->readFirst();

        if($bRead)
        {
          $sHTML .= 'Hi!<br/>Here is a reminder of your pending opportunities.<br/><br/>';
          while($bRead)
          {
            $sHTML .=$this->_displayRow($oOpp);
            $bRead = $oOpp->readNext();
          }
          $sHTML .= '<br /><br />Please update the status if needed.<br/>Enjoy BCM.';

          if(CONST_DEV_SERVER)
            $bSent = $oMail->sendRawEmail('BCM reminder', CONST_DEV_EMAIL, 'BCM - Notifier: your pending opportunities.', $sHTML);
          else
            $bSent = $oMail->sendRawEmail('BCM reminder', $aUserData['email'], 'BCM - Notifier: your pending opportunities.', $sHTML);

          if($bSent)
            echo $aUserData['firstname'].' has been notified<br />';
        }
      }

      $oSettings->setSystemSettings('oppcron', $nNow);
      echo 'Cron done on '.date('y-m-d H:m:s', $nNow).'('.$nNow.')<br />';
      echo 'Next cron on '.date('y-m-d H:m:s', ($nNow+(24*60*60))).'<br />';
    }
    else
    {
      echo 'No Cron. It is too early.<br/>';
      echo 'Last cron on '.date('y-m-d H:m:s', $nLastReminder).'('.$nLastReminder.')<br />';
      echo 'Next cron on '.date('y-m-d H:m:s', ($nLastReminder+(24*60*60))).'<br />';
    }
    echo 'End Cron';

    return true;
  }

  // ===================================
  // DISPLAY FUNCTIONS
  // ===================================

  private function _displayPie($psTarget, $paData, $pfTotal = 0)
  {
    $oPie = CDependency::getComponentByName('charts');

    //remove empty data stream to simplify graphs
    foreach($paData as $sKey => $asData)
    {
      if(empty($asData['y']))
        unset($paData[$sKey]);
    }

    if($pfTotal)
      $oPie->createPie('Total amount : '.$pfTotal, $paData);

    $oPie->setChartRender('plot', 'value_%');
    $oPie->setChartRender('tooltip', 'value_%');

    $oPie->setChartSize('530px','350px');

    return $oPie->displayPie(false, $psTarget);
  }

  // Shows title and links to various type of display
  private function _displayListHeader($pbGlobalStat, $pnUserPk, $psDateMin, $psDateMax, $paUserData, $paProducts, $psAction = CONST_ACTION_LIST)
  {
    if(!assert('is_bool($pbGlobalStat)'))
      return '';

    if(!assert('is_key($pnUserPk)'))
      return '';

    if(!assert('is_date($psDateMin)'))
      return '';

    if(!assert('is_date($psDateMax)'))
      return '';

    if(!assert('is_string($psAction)'))
      return '';

    if(!assert('is_array($paProducts) && !empty($paProducts)'))
      return '';

    if(!assert('is_array($paUserData) && !empty($paUserData)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oRight = CDependency::getComponentByName('right');

    $sHTML = '';
    $sHTML .= $oHTML->getBlocStart('list-header', array('style' => 'margin-bottom:20px;'));

      $aOptions = array('userpk' => $pnUserPk, 'datemin' => $psDateMin, 'datemax' => $psDateMax, 'action' => $psAction);
      if($pbGlobalStat)
      {
        $aOptions['globalstat'] = 0;
        $sTitleLabel = 'Overvall sales opportunities';
        $sLinkLabel = 'My sales opportunities';
      }
      else
      {
        $aOptions['globalstat'] = 1;
        if($pnUserPk == $this->_userPk)
          $sTitleLabel = 'My sales opportunities';
        else
          $sTitleLabel = $paUserData[$pnUserPk]['firstname'].'\'s sales opportunities';

        $sLinkLabel = 'Overvall sales opportunities';
      }


      $bCanSeeAll = $oRight->canAccess($this->csUid, 'view-all', CONST_OPPORTUNITY);

      $sMenu = '';
      $nSelected = 0;
      if($bCanSeeAll)
      {
        if(!$pbGlobalStat)
          $nSelected = $pnUserPk;

        $sUrlSwitch = $oPage->getUrl($this->getComponentUid(), $psAction, $this->getDefaultType(), 0, array_merge($aOptions, $paProducts));
        $sLinkMenu = $oHTML->getLink($sLinkLabel, $sUrlSwitch);
        $sMenu = $pbGlobalStat ? $sLinkMenu.=' | Overall sales opportunities' : 'My sales opportunities | '.$sLinkMenu;

        $sMenu.= $oHTML->getSpace(6);
      }


      $aOptions['globalstat'] = (int)$pbGlobalStat;
      if($psAction == CONST_ACTION_FULL_LIST)
      {
        $sUrl = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_OPPORTUNITY,0,array_merge($aOptions, $paProducts));
        $sMenu.= $oHTML->getLink('Statistic View', $sUrl).' | History View';
      }
      else
      {
        $sUrl = $oPage->getUrl($this->csUid, CONST_ACTION_FULL_LIST, CONST_OPPORTUNITY,0,array_merge($aOptions, $paProducts));
        $sMenu.= 'Statistic View | '.$oHTML->getLink('History View', $sUrl);
      }

      $sUid = CDependency::getComponentUidByName('addressbook');

      //link to create opportunity on companies
      $avCpValuesAddOpp = array(CONST_CP_UID => $sUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_COMPANY, CONST_CP_PK => 0);
      $sUrlAddOpp = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, $avCpValuesAddOpp);
      $sAjax = 'var oConf = goPopup.getConfig();
                  oConf.height = 660;
                  oConf.width = 980;
                  oConf.modal = true;
                  goPopup.setLayerFromAjax(oConf, \''.$sUrlAddOpp.'\'); ';

      $asAction[] = array('label' => 'to a company', 'onclick' => $sAjax);

      //link to create opportunity on connections
      $avCpValuesEditOpp = array(CONST_CP_UID => $sUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_AB_TYPE_CONTACT, CONST_CP_PK => 0);
      $sUrlEditOpp = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, $avCpValuesEditOpp);
      $sAjax = 'var oConf = goPopup.getConfig();
                  oConf.height = 660;
                  oConf.width = 980;
                  oConf.modal = true;
                  goPopup.setLayerFromAjax(oConf, \''.$sUrlEditOpp.'\'); ';

      $asAction[] = array('label' => 'to a connection', 'onclick' => $sAjax);


      $sHTML.= $oHTML->getBlocStart('', array('style' => 'position: relative;'));
      $sHTML.= $oHTML->getTitleLine($sTitleLabel, $this->getResourcePath().'pictures/opportunity_48.png', array(), $sMenu, '');
      $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getActionButtons($asAction, 1, 'Add opportunity...', array('width' => 135, 'class' => 'oppListMainAction'));

      $sUrl = $oPage->getUrl($this->csUid, $psAction, CONST_OPPORTUNITY,0);
      $oForm = $oHTML->initform('switchDate');
      $nStartYear = 2012;
      $nFinalYear = date('Y') +1;

      $oForm->setFormParams('switchDate', false, array('action' => $sUrl, 'noCancelButton' => 1, 'submitLabel' => 'Change interval', 'fullFloating' => 1));
      $oForm->setFormDisplayParams(array('noButton' => 1));

      if($bCanSeeAll)
      {
        $aUserDataSelect = array();
        $aDefaultOptions = array_merge($paProducts, array('datemin' => $psDateMin, 'datemax' => $psDateMax));
        $aAllUsersOptions = array_merge($aDefaultOptions, array('globalstat' => 1));
        $aUserDataSelect[0]= array('label' => 'All users', 'value' => $oPage->getUrl($this->getComponentUid(), $psAction, $this->getDefaultType(), 0, $aAllUsersOptions));
        foreach($paUserData as $aUser)
        {
          $aUserOption = array_merge($aDefaultOptions, array('userpk' => $aUser['loginpk']));
          $sUrl = $oPage->getUrl($this->getComponentUid(), $psAction, $this->getDefaultType(), 0, $aUserOption);
          $aUserDataSelect[$aUser['loginpk']] = array('label' => $aUser['firstname'], 'value' => $sUrl);
        }
        $oForm->addField('misc', '', array('type' => 'text', 'text' => $oHTML->getSelectMenu('switch_user', $aUserDataSelect, $nSelected), 'class' => 'opp_user'));
      }
      else
      {
        $oForm->addField('misc', '', array('type' => 'text', 'text' => 'My opportunities', 'style' => 'display: block; text-align: center;'));
      }



      $nSelected = 0;
      foreach($paProducts as $sProduct => $bDisplay)
      {
        if($bDisplay)
          $nSelected++;
      }

      $sOnchange = '
             var aoBoxes = $(this).closest(\'form\').find(\'.prod_box\');
             if($(this).prop(\'checked\'))
             {
               $(aoBoxes).fadeOut();
               $(this).closest(\'form\').find(\'.prod_box input\').removeAttr(\'checked\');
             }
             else
               $(aoBoxes).fadeIn();
              ';

      if($nSelected == 0)
      {
        $sClass = 'hidden';
        $oForm->addField('checkbox', 'all_product', array('label' => 'All products', 'checked' => 'checked', 'onchange' => $sOnchange));
      }
      else
      {
        $sClass = '';
        $oForm->addField('checkbox', 'all_product', array('label' => 'All products', 'onchange' => $sOnchange));
      }
      $oForm->setFieldDisplayParams('all_product', array('style' => 'width: 120px;'));

      foreach($paProducts as $sProduct => $bDisplay)
      {
        $aOptions = array('label' => ucfirst($sProduct));
        if($bDisplay && empty($sClass))
          $aOptions['checked'] = 'checked';

        $oForm->addField('checkbox', $sProduct, $aOptions);
        $oForm->setFieldDisplayParams($sProduct, array('class' => 'prod_box '.$sClass));
      }

      $oForm->addField('input', 'datemin', array('value' => $psDateMin, 'type' => 'month', 'data-start-year' => $nStartYear, 'data-final-year' => $nFinalYear, 'class' => 'month_range', 'style' => 'width:100px;'));
      $oForm->addField('input', 'datemax', array('value' => $psDateMax, 'type' => 'month', 'data-start-year' => $nStartYear, 'data-final-year' => $nFinalYear, 'class' => 'month_range', 'style' => 'width:100px;'));

      /*$oPage->addCustomJs('$(document).ready(function()
      {
        $(\'.month_range\').monthpicker().bind(\'monthpicker-hide\', function()
        {
          if(typeof checkDateInterval == \'function\')
          { checkDateInterval($(this).attr(\'name\')); }
        })
        / *$(\'.month_range\').bind(\'monthpicker-show\', function(event, oObj, other)
        {
          if(!$("div.mtz-monthpicker:visible .mtz-previous").length)
          {
            $("div.mtz-monthpicker:visible:first").parent().append("<span class=\'ui-widget-header mtz-previous\' onclick=\'previousYear(this);\'><</span><span class=\'ui-widget-header mtz-next\' onclick=\'nextYear(this);\'>></span>");
          }
        });* /
      });
      ');*/

      $oForm->addField('input', 'button', array('type' => 'button', 'value' => 'Refresh list', 'onclick' => 'this.form.submit();'));
      $oForm->setFieldDisplayParams('button', array('style' => 'padding-top: 5px;  float: right; margin-right: 10px; '));

      $oForm->addField('input', 'globalstat', array('value' => $pbGlobalStat, 'type' => 'hidden'));
      $oForm->addField('input', 'userpk', array('value' => $pnUserPk, 'type' => 'hidden'));
      $oPage->addJsFile($this->getResourcePath().'js/opportunity.js');

      $sHTML .= $oForm->getDisplay();

    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }


  private function _displayBBook()
  {
    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/opportunity.css');

    $sHTML = '';
    $asUserData = $oLogin->getUserList(0, false, true);
    $sDefault = date('Y-m', strtotime('+1 month')).'-01';
    $dSelected = getValue('month', $sDefault);
    $tSelected = strtotime($dSelected);
    $tMax = strtotime('+5 months', $tSelected);
    $tMin = strtotime('-5 months', $tSelected);


    $sHTML .= $oHTML->getTitle('Tokyo Weekender Magazine - '.$dSelected,'h1');

    $sHTML .= "<select onchange='window.location.href = $(this).children(\"option:selected\").val();'>";
    for ($tMonth=$tMin; $tMonth<=$tMax; $tMonth = strtotime('+1 month', $tMonth))
    {
      $sURL = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_OPPORTUNITY, 0, array('display' => 'bbook', 'month' => date('Y-m-01', $tMonth)));
      $sSelected = ($tMonth==$tSelected) ? ' selected=\'selected\'' : '';
      $sHTML.=  '<option value=\''.$sURL.'\''.$sSelected.'>'.date('M Y', $tMonth).'</option>';
    }
    $sHTML .= '</select>';

    $sHTML .= $oHTML->getBlocStart('BBook', array('class' => 'divlist'));

      $aProducts = $this->_getTWProducts();
      $aOpps = $this->_getModel()->getOpportunitiesByUserPk(0, $dSelected, $dSelected, 'status', array(), $aProducts);
      $sHTML .= $this->_displayOppHistory($aOpps, true, $asUserData);

    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _getProductsValues(&$aProductList)
  {
    // No value has been specified by user, we want default value to be set to display all
    if((empty($_POST) || !isset($_POST)) && (!getValue('production') && !getValue('promotion') && !getValue('IT')))
    {
      $bProduction = $bPromotion = $bIT = false;
    }
    else
    {
      $sProduction = getValue('production',''); $bProduction = (($sProduction =='on') || ($sProduction=='1'));
      $sPromotion = getValue('promotion',''); $bPromotion = (($sPromotion=='on') || ($sPromotion=='1'));
      $sIT = getValue('IT',''); $bIT = (($sIT=='on') || ($sIT=='1'));
    }

    $aProducts = array('production' => $bProduction, 'promotion' => $bPromotion, 'IT' => $bIT);

    foreach($aProducts as $sProductType => $bDisplay)
    {
      if($bDisplay)
        $aProductList = array_merge($aProductList, $this->_getProducts($sProductType));
    }

    return $aProducts;
  }

  private function _displayFullList()
  {
    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/opportunity.css');

    $sHTML = '';
    $nUserPk = (int)getValue('userpk', $this->_userPk);
    $bGlobal = (bool)getValue('globalstat', false);
    $asUserData = $oLogin->getUserList(0, false, true);
    $sDateMin = getValue('datemin', false);
    $sDateMax = getValue('datemax', false);

    // Type of products you want to display
    $aProductList = array();
    $aProducts = $this->_getProductsValues($aProductList);
    $bAllProd = (bool)getValue('all_product');
    if($bAllProd)
      $aProductList = array();


    // When sent via POST by monthpicker plugin thoses values are of type YYYY-MM
    if(strlen($sDateMax)==7)
      $sDateMax.='-01';
    if(strlen($sDateMin)==7)
      $sDateMin.='-01';

    $nMonth = (int)date('m');
    $sTimeMax = mktime(0, 0, 0, $nMonth, 1, date('Y'));
    $sTimeMin = strtotime('-3 month', $sTimeMax);

    if(!$sDateMin)
      $sDateMin = date('Y-m-d', $sTimeMin);

    if(!$sDateMax)
      $sDateMax = date('Y-m-d', $sTimeMax);

    if(!assert('is_date($sDateMin)'))
      return '';

    if(!assert('is_date($sDateMax)'))
      return '';

    if(!assert('is_key($nUserPk)'))
      return '';

    if(!assert('is_bool($bGlobal)'))
      return '';

    $sHTML .= $this->_displayListHeader($bGlobal, $nUserPk, $sDateMin, $sDateMax, $asUserData, $aProducts, CONST_ACTION_FULL_LIST);

    $sHTML .= $oHTML->getBlocStart('OpportunitiesFullList', array('class' => 'divlist'));

    if($bGlobal)
      $aOpps = $this->_getModel()->getOpportunitiesByUserPk(0, $sDateMin, $sDateMax, 'op.status, opd.month DESC', array(), $aProductList);
    else
      $aOpps = $this->_getModel()->getOpportunitiesByUserPk($nUserPk, $sDateMin, $sDateMax, 'op.date_update DESC, opd.month DESC', array(), $aProductList);

    $sHTML .= $this->_displayOppHistory($aOpps, $bGlobal, $asUserData);

    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displayOppHistory($paOpps, $pbGlobal, $paUserData)
  {
    if(!assert('is_array($paOpps)'))
      return '';

    if(!assert('is_bool($pbGlobal)'))
      return '';

    if(!assert('(is_array($paUserData) && !empty($paUserData))'))
      return '';

    $oHTML = CDependency::getCpHtml();

    $sHTML = '';

    if(empty($paOpps))
      $sHTML .= 'No opportunity was found.';
    else
    {
      $asMonth = array();
      $anMonth = array();
      $asLink = array();

      foreach ($paOpps as $nPk => $aOpp)
      {
        //dump($oDbResult->getData());
        $nPaid = (int)($aOpp['nb_paid']==$aOpp['nb_products']);

        foreach ($aOpp['details'] as $aDetail)
        {
          $sMonth = $aDetail['month'];

          set_array($anMonth[$sMonth]['paid'], 0);
          set_array($anMonth[$sMonth]['amount'], 0);

          $anMonth[$sMonth]['paid']+= $nPaid;

          if((int)$aDetail['paid']==1)
            $anMonth[$sMonth]['amount']+= $aDetail['amount'];
          else
            $anMonth[$sMonth]['amount']+= $aDetail['amount'] * $aOpp['probability'] / 100;

          if(!isset($asMonth[$sMonth][$nPk]))
            $asMonth[$sMonth][$nPk] = $this->_displayRow($aOpp, $pbGlobal, $paUserData, '', $sMonth);

          $asLink[$sMonth] = ' <a href="#month_'.$sMonth.'">'.date('F-Y', strtotime($sMonth)).'<a> ';
        }
      }

      asort($asLink);

      if(count($asLink) > 2)
      {
        $sHTML.= 'Shortcut to : '.implode(' / ', $asLink);
        $sHTML.= $oHTML->getCR();
      }

      foreach($asLink as $sMonth => $sMonthDisplay)
      {
        if(isset($asMonth[$sMonth]))
        {
          $asRows = $asMonth[$sMonth];
          $sHTML.= $oHTML->getBlocStart('month_'.$sMonth, array('class' => 'monthly_opp'));

            $sStat = $oHTML->getSpace(5);
            $sStat.= $oHTML->getSpan('', (int)$anMonth[$sMonth]['paid'].' paid - projected amount: '.formatNumber($anMonth[$sMonth]['amount'], '', ',', '¥'), array('class' => 'titleStat'));

            $sHTML.= $oHTML->getTitle($sMonthDisplay.' - '.count($asRows).' opportunities '.$sStat, 'h2', true);
            $sHTML.= $oHTML->getFloatHack();
            $sHTML.= implode('', $asRows);

          $sHTML.= $oHTML->getBlocEnd();
          $sHTML.= $oHTML->getFloatHack();
        }
      }
    }

    return $sHTML;
  }


  private function _displayList()
  {
    $oHTML = CDependency::getCpHtml();
    $oChart = CDependency::getComponentByName('charts');
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getCpLogin();
    $oRight = CDependency::getComponentByName('right');

    $oPage->addCssFile($this->getResourcePath().'css/opportunity.css');
    $oPage->addJsFile($this->getResourcePath().'js/chart_toggle.js');

    $bGlobalStat = (bool)getValue('globalstat');
    if(!$oRight->canAccess($this->csUid, 'view-all', CONST_OPPORTUNITY))
      $bGlobalStat = false;

    $aTabContent = array();
    $nUserPk = (int)getValue('userpk', $this->_userPk);
    $sDateMin = getValue('datemin', false);
    $sDateMax = getValue('datemax', false);
    $sMonthSelected = getValue('selected');
    $sSelected = (!empty($sMonthSelected)) ? strtotime($sMonthSelected) : '';
    $asUser = $oLogin->getUserList(0, false, true);
    $bDefaultDates = false;

    $aProductList = array();
    $aProducts = $this->_getProductsValues($aProductList);
    $bAllProd = (bool)getValue('all_product');
    if($bAllProd)
      $aProductList = array();

    //hack so admin can access the page
    if($this->_rightAdmin && $nUserPk == -1)
      $nUserPk = 1;

    if(!assert('is_integer($nUserPk)'))
      return '';

    if(!$sDateMin || !$sDateMax)
    {
      $bDefaultDates = true;
      $nMonthMin = (int)date('m');
      $sTimeMin = mktime(0, 0, 0, $nMonthMin, 1, date('Y'));
      $sTimeMax = strtotime('+6 months', $sTimeMin);
    }
    else
    {
      // When sent via POST by monthpicker plugin thoses values are of type YYYY-MM
      if(strlen($sDateMax) == 7)
        $sDateMax.='-01';

      if(strlen($sDateMin) == 7)
        $sDateMin.='-01';

      if($sDateMin >= $sDateMax)
        $sDateMax = date('Y-m-d', strtotime('+1 month', strtotime($sDateMin)));

      $sTimeMin = strtotime($sDateMin);
      $sTimeMax = strtotime($sDateMax);
    }

    $sDateMin = date('Y-m-d', $sTimeMin);
    $sDateMax = date('Y-m-d', $sTimeMax);

    if(!assert('is_date($sDateMin) && is_date($sDateMax)'))
      return '';

    $sHTML = '';
    $sHTML .= $this->_displayListHeader($bGlobalStat, $nUserPk, $sDateMin, $sDateMax, $asUser, $aProducts, CONST_ACTION_LIST);

    // Dashboard
    if($bGlobalStat)
      $aMyStats = $this->getMonthlyStat(0, $sDateMin, $sDateMax, $aProductList);
    else
      $aMyStats = $this->getMonthlyStat($nUserPk, $sDateMin, $sDateMax, $aProductList);


    //adjusting the width based of the number of months to display
    $nMonth = count($aMyStats['asAxis']);
    if($nMonth < 1)
      return 'Date interval too small ['.$sDateMin.' / '.$sDateMax.']';

    //$sWidth = ($nMonth <= 3) ? ($nMonth*400).'px' : ($nMonth*175).'px';
    $sTabsExtraClass = '';
    if($nMonth <= 4)
    {
      $sWidth = ($nMonth*295) + (pow((4-$nMonth), 2)*100).'px';
    }
    elseif($nMonth <= 7)
    {
      $sWidth = ($nMonth*170) + (pow((7-$nMonth), 2)*40).'px';
    }
    else
    {
      //$sWidth = ($nMonth*110) + (pow((7-$nMonth), 2)*20).'px';
      $sWidth = '1200px';
      $sTabsExtraClass = 'small';
    }

    //create the bar charts containing every month data
    $oChart->createChart('column', 'Sales pipeline ('.count($aMyStats['asAxis']).' months)');
    $oChart->setChartLegendPosition('horizontal', 0, -5);
    $oChart->setChartAxis($aMyStats['asAxis']);
    $oChart->setChartSize($sWidth, '275px');
    $oChart->setChartData('On going', $aMyStats['ongoing'], $this->casStatusColors['ongoing']);
    $oChart->setChartData('Failed', $aMyStats['failed'], $this->casStatusColors['failed']);
    $oChart->setChartData('Stalled', $aMyStats['stalled'], $this->casStatusColors['stalled']);
    $oChart->setChartData('Signed', $aMyStats['signed'], $this->casStatusColors['signed']);
    $oChart->setChartData('Projected', $aMyStats['projected'], $this->casStatusColors['projected']);

    $sHTML .= $oHTML->getBloc('MyStatistics', $oChart->getChartDisplay());
    $sHTML .= $oHTML->getBlocStart('MyOpportunitiesList', array('class' => $sTabsExtraClass));


    // Opportunity listing, sorted by month using getTabs() function
    for($sCurrentTime = $sTimeMin; $sCurrentTime <= $sTimeMax; $sCurrentTime = strtotime('+1 month', $sCurrentTime))
    {
      $sDate = date('Y-m-d', $sCurrentTime);
      $aRows[$sDate] = '';
      $aChart[$sDate] = array('ongoing' => 0, 'signed' => 0, 'stalled' => 0, 'failed' => 0, 'total' => 0);
      $aTabContent[$sDate]['title'] = date('M-Y', $sCurrentTime);
      $aTabContent[$sDate]['label'] = $sCurrentTime;
    }

    if($bGlobalStat)
      $aOpps = $this->_getModel()->getOpportunitiesByUserPk(0, $sDateMin, $sDateMax, '', array(), $aProductList);
    else
      $aOpps = $this->_getModel()->getOpportunitiesByUserPk($nUserPk, $sDateMin, $sDateMax, '', array(), $aProductList);


    //dev_dump($aOpps);

    $aUrlCallback = array();
    foreach($aOpps as $aOpp)
    {
      foreach($aOpp['details'] as $aDetail)
      {
        $sMonth = $aDetail['month'];
        $nAmount = (int)$aDetail['amount'];

        if(!isset($aUrlCallback[$sMonth]))
          $aUrlCallback[$sMonth] = urlencode($oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_OPPORTUNITY, 0, array('datemin' => $sDateMin, 'datemax' => $sDateMax, 'selected' => $sMonth, 'globalstat' => (int)$bGlobalStat, 'userpk' => $nUserPk)));

        if(!isset($aRows[$sMonth][$aOpp['opportunitypk']]))
          $aRows[$sMonth][$aOpp['opportunitypk']] = $this->_displayRow($aOpp, $bGlobalStat, $asUser, $aUrlCallback[$sMonth], $sMonth);

        $aChart[$sMonth]['total']+= $nAmount;

        switch($aOpp['status'])
        {
          case 'pitched' :
          case 'proposal' :
          case 'verbal_agreement' :
            $aChart[$sMonth]['ongoing'] += $nAmount;
            break;
          case 'failed' :
            $aChart[$sMonth]['failed'] += $nAmount;
            break;
          case 'stalled' :
            $aChart[$sMonth]['stalled'] += $nAmount;
            break;
          default :
            $aChart[$sMonth]['signed'] += $nAmount;
            break;
        }
      }
    }

    //echo '<hr />';
    //dump($aChart);

    $sCurrentTime = $sTimeMin;
    $aTabs = array();
    while($sCurrentTime <= $sTimeMax)
    {
      $sMonth = date('Y-m-d', $sCurrentTime);

      if(empty($aRows[$sMonth]))
      {
        $aTabContent[$sMonth]['content'] ='No opportunity listed this month';
      }
      else
      {
        $aChart[$sMonth][] = array('name' => '\'Ongoing\'', 'color' => '\''.$this->casStatusColors['ongoing'].'\'', 'y' => $aChart[$sMonth]['ongoing']);
        unset($aChart[$sMonth]['ongoing']);

        $aChart[$sMonth][] = array('name' => '\'Failed\'', 'color' => '\''.$this->casStatusColors['failed'].'\'', 'y' => $aChart[$sMonth]['failed']);
        unset($aChart[$sMonth]['failed']);

        $aChart[$sMonth][] = array('name' => '\'Stalled\'', 'color' => '\''.$this->casStatusColors['stalled'].'\'', 'y' => $aChart[$sMonth]['stalled']);
        unset($aChart[$sMonth]['stalled']);

        $aChart[$sMonth][] = array('name' => '\'Signed\'', 'color' => '\''.$this->casStatusColors['signed'].'\'', 'y' => $aChart[$sMonth]['signed'], 'sliced' => 'true', 'selected' => 'true');
        unset($aChart[$sMonth]['signed']);

        /*dump('month '.$sMonth);*/
        //dump($aChart[$sMonth]);

        $sPie = $oHTML->getBlocStart('', array('class' => 'list_filter left'));
        $sPie.= 'Opportunities breakdown';
        $sPie.= $oHTML->getBlocEnd();
        $sPie.= $this->_displayPie('chart_'.$sCurrentTime, $aChart[$sMonth], formatNumber($aChart[$sMonth]['total']).'¥');
        $sContent = $oHTML->getBloc('', $sPie, array('class' => 'opp_pie_container', 'style' => 'height:350px;'));

        $sContent.= $oHTML->getBlocStart('', array('class' => 'list_filter'));
        $sContent.= 'Sort by:&nbsp;&nbsp;
          <a href="javascript:;" onclick="sortOppList(\'#list_'.$sCurrentTime.'\', \'date\');" >date</a>
          &nbsp;|&nbsp;
          <a href="javascript:;" onclick="sortOppList(\'#list_'.$sCurrentTime.'\', \'status\');" >status</a>';
        $sContent.= $oHTML->getBlocEnd();

        $sContent.= $oHTML->getBlocStart('list_'.$sCurrentTime, array('class' => 'divlist oppMonthList'));





        foreach($aRows[$sMonth] as $sRow)
          $sContent.= $sRow;

        $sContent.= $oHTML->getBlocEnd();
        $aTabContent[$sMonth]['content'] = $sContent;
      }

      $aTabs[] = $aTabContent[$sMonth];
      $sCurrentTime = strtotime('+1 month', $sCurrentTime);
    }

    //print deadline is the last friday of the month. Sales should switch to next month a week before.
    //$nSwitchingDay = (int)date('d', strtotime('last friday of '.date('F Y'))) - 7;
    $nSwitchingDay = 31;     // cod ebelow useless then but some people change their minds endlessly

    if($bDefaultDates)
    {
      if(date('d') >= $nSwitchingDay)
        $sSelected = strtotime(date('Y-m-01', strtotime('+2 month')));
      else
        $sSelected = strtotime(date('Y-m-01', strtotime('+1 month')));
    }

    $sHTML.= $oHTML->getFloatHack().$oHTML->getCR();
    $sHTML.= $oHTML->getTabs('my_opp', $aTabs, $sSelected);
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _displaySingleRow($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array();

    $oLogin = CDependency::getComponentByName('login');

    $oOpportunity = $this->_getModel()->getOpportunityByPk($pnPk);
    $asUserData = $oLogin->getUserList(0, false, true);

    return $this->_displayRow($oOpportunity, true, $asUserData);
  }

  private function _displayEditOppLink($pnPk, $pbPaid, $psStatus, $psTitle = '', $pbDisplayIcon = true, $pnCreatorPk = 0, $psUrlCallBack='')
  {
    if(!assert('is_key($pnPk)'))
      return '';

    if(!assert('is_bool($pbPaid)'))
      return '';

    if(!assert('is_string($psStatus) && !empty($psStatus)'))
      return '';

    if(!assert('is_string($psTitle)'))
      return '';

    if(!assert('$pbDisplayIcon || (!empty($psTitle))'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $bIsCreator = ($pnCreatorPk == $this->_userPk);

    $sHTML = '';

    $aLinkOptions = array();
    if(!empty($psUrlCallBack))
      $aLinkOptions[CONST_URL_ACTION_RETURN]=$psUrlCallBack;



    // Right button
    if($this->_rightManage || $bIsCreator)
    {
      $sUrlEdit = $oPage->getAjaxUrl('opportunity', CONST_ACTION_EDIT, CONST_OPPORTUNITY, $pnPk, $aLinkOptions);
      $sAjaxEdit = 'var oConf = goPopup.getConfig();
                    oConf.height = 660;
                    oConf.width = 980;
                    oConf.modal = true;
                    goPopup.setLayerFromAjax(oConf, \''.$sUrlEdit.'\'); ';

      if($pbPaid && !$this->_rightPay)
      {
        $sPic = $this->getResourcePath().'/pictures/paid_24.png';
        $sMessage = 'Only BC Administrator can edit paid opportunity';

        if($pbDisplayIcon)
          $sHTML .= $oHTML->getPicture($sPic, $sMessage);
        else
           $sHTML .= $oHTML->getLink($psTitle, 'javascript:;', array('style' => 'cursor: help;', 'title' => $sMessage));
      }
      elseif($pbPaid && $this->_rightPay)
      {
        if($pbDisplayIcon)
        {
          $sPic = $this->getResourcePath().'/pictures/paid_24.png';
          $sHTML.= $oHTML->getPicture($sPic, 'Paid opportunity');

          $sPic = $this->getResourcePath().'/pictures/edit_danger_16.png';
          $sAjaxEdit = 'if(window.confirm(\'This opportunity has already been set as signed. Are you sure you want to change it ?\')){ '.$sAjaxEdit.'}';
          $sHTML.='&nbsp;&nbsp;'.$oHTML->getLink($oHTML->getPicture($sPic, 'Edit opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
         }
         else
           $sHTML .= $psTitle;
      }
      elseif($psStatus == 'signed')
      {
        if($this->_rightManage)
        {
          $sAjaxEdit = 'if(window.confirm(\'This opportunity has already been set as signed. Are you sure you want to change it ?\')){ '.$sAjaxEdit.'}';
          $sPic = $this->getResourcePath().'/pictures/edit_danger_16.png';

          if($pbDisplayIcon)
            $sHTML.= $oHTML->getLink($oHTML->getPicture($sPic, 'Edit opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
          else
            $sHTML.= $oHTML->getLink($psTitle, 'javascript:;', array('onclick' => $sAjaxEdit, 'title' => 'Edit opportunity'));
        }
        else
        {
          if($pbDisplayIcon)
            $sHTML.= $oHTML->getPicture($this->getResourcePath().'/pictures/locked_24.png', 'The opportunity is signed. Only the administrator can change it.');
          else
            $sHTML.= '<span title="The opportunity is signed. Only the administrator can change it.">'.$psTitle.'</span>';
        }
      }
      else
      {
        if($pbDisplayIcon)
          $sHTML.= $oHTML->getLink($oHTML->getPicture(CONST_PICTURE_EDIT, 'Edit opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
        else
          $sHTML.= $oHTML->getLink($psTitle, 'javascript:;', array('onclick' => $sAjaxEdit, 'title' => 'Edit this opportunity'));
      }
    }
    else
    {
      if(!$pbDisplayIcon)
        $sHTML.= $psTitle;
    }

    return $sHTML;
  }

  private function _displayDeleteOppLink($pnPk, $pbPaid, $psStatus, $psTitle = '', $pnCreatorPk = 0, $psUrlCallBack='')
  {
    if(!assert('is_key($pnPk)'))
      return '';

    if(!assert('is_bool($pbPaid)'))
      return '';

    if(!assert('is_string($psStatus) && !empty($psStatus)'))
      return '';

    if(!assert('is_string($psTitle)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $bIsCreator = ($pnCreatorPk == $this->_userPk);

    $sHTML = '';

    $aLinkOptions = array();
    if(!empty($psUrlCallBack))
      $aLinkOptions[CONST_URL_ACTION_RETURN] = $psUrlCallBack;

    //can't delete a paid opportunity. Admin can edit and set values to 0
    if(!$pbPaid)
    {
      //now: if the user is opp admin (pay) or if the user manage his opportunities
      if($this->_rightPay || ($psStatus != 'signed' && $bIsCreator))
      {
        //psRefusedAction, psConfirmedBtn, psRefusedBtn, psTitle, pnWidth, pnHeight
        $sURL = $oPage->getAjaxUrl('opportunity', CONST_ACTION_DELETE, CONST_OPPORTUNITY, $pnPk);

        $psTitle = addslashes(htmlentities($psTitle));
        $sAjaxEdit = 'goPopup.setPopupConfirm(\'Are you sure you want to delete this opportunity [ '.$psTitle.' ] ?\',
          \'AjaxRequest(\\\''.$sURL.'\\\'); \', \'\', \'\',\'\', \'Deleting an opportunity...\', 350, 175); ';

        $sHTML .= ' '.$oHTML->getLink($oHTML->getPicture(CONST_PICTURE_DELETE, 'Delete opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
      }
    }

    return $sHTML;
  }

  private function _displayRow($paOpportunity, $pbGlobalStat = false, $pasUserData = array(), $psUrlCallBack = '', $psMonth = '', $pbShowItem = true)
  {

    if(!assert('is_array($paOpportunity) && !empty($paOpportunity)'))
      return '';

    if(!assert('is_string($psUrlCallBack)'))
      return '';

    if(!assert('is_string($psMonth)'))
      return '';

    $nPk = (int)$paOpportunity['opportunitypk'];
    $bPayed = ($paOpportunity['nb_paid'] == $paOpportunity['nb_products']);

    if(!assert('is_key($nPk)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getCpLogin();

    $aBlocOptions = array(
      'class' => 'columns divlist-item',
      'opportunitypk' => $nPk,
      'opp_status' => $paOpportunity['status'],
      'opp_date' => $paOpportunity['date_last_action']
    );

    $sHTML = $oHTML->getBlocStart('', $aBlocOptions);

      $sHTML.= $oHTML->getBlocStart('',  array('class' => 'opp_status'));

        $sStatus = $paOpportunity['status'];
        $sHTML .= '<br /><strong style="color: '.$this->casStatusColors[$sStatus].'">'.implode(' ', explode('_', $sStatus)).'</strong>';

        if($bPayed)
          $sHTML .= '<br /><strong style="font-size: 0.85em; color: '.$this->casStatusColors['paid'].'"> & paid</strong>';

      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'opp_detail'));

        if($pbShowItem)
        {
          $sItemType = $paOpportunity['cp_type'];
          if($sItemType == 'cp')
          {
            $sURL = $oPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_COMPANY, (int)$paOpportunity['addressbook_companypk']);
            $sPic = $oHTML->getPicture('/common/pictures/items/cp_16.png');
            $sItemLabel = $oHTML->getLink($sPic.' '.$paOpportunity['company_name'], $sURL);
          }
          elseif($sItemType=='ct')
          {
            $sURL = $oPage->getUrl('addressbook', CONST_ACTION_VIEW, CONST_AB_TYPE_CONTACT, (int)$paOpportunity['addressbook_contactpk']);
            $sPic = $oHTML->getPicture('/common/pictures/items/ct_16.png');
            $sItemLabel = $oHTML->getLink($sPic.' '.$paOpportunity['contact_name'], $sURL);
          }
        }

        $sTitle = cutString($paOpportunity['title'], 75);

        $sLinkTitle = $this->_displayEditOppLink($nPk, $bPayed, $sStatus, $sTitle, false, $paOpportunity['loginfk'], $psUrlCallBack);

        $sHTML.= $oHTML->getBlocStart('', array('style' => '', 'class' => 'item-title', 'title' => str_replace('"', '\'', strip_tags($paOpportunity['description']))));
        $sHTML.= ($pbShowItem) ? $sItemLabel.' : '.$sLinkTitle : $sLinkTitle;
        $sHTML.= $oHTML->getBlocEnd();

        $sHTML.= $oHTML->getFloatHack();

        // Content
        $sHTML.= $oHTML->getBlocStart('', array('style' => 'padding:0 10px 0 10px; margin-bottom:10px;'));


          if($pbGlobalStat && !empty($pasUserData))
            $sHTML.= 'By : <strong>'.$oLogin->getUserNameFromData($pasUserData[$paOpportunity['loginfk']], true).'</strong> | ';

          if(!empty($psMonth))
            $sAmount = formatNumber((int)$paOpportunity['months'][$psMonth]['amount'], '', ',', '¥');
          else
            $sAmount = formatNumber((int)$paOpportunity['total'], '', ',', '¥');

          $sHTML .= 'Amount : <strong>'.$sAmount.'</strong>';
          if($paOpportunity['status'] != 'signed')
            $sHTML .= '  |  Probability : <strong>'.$paOpportunity['probability'].'%</strong>';
          else
            $sHTML .= '  |  Probability : <a href="javascript:;" title="The opportunity is signed, the probability is not relevant anymore (100%)">--</a> ';


          if(isset($paOpportunity['months'][$psMonth]['nb_products']))
            $nNbProducts = $paOpportunity['months'][$psMonth]['nb_products'];
          else
            $nNbProducts = $paOpportunity['nb_products'];

          $sProducts = '';
          foreach($paOpportunity['details'] as $aProduct)
          {
            if(empty($psMonth) || $psMonth == $aProduct['month'])
              $sProducts.= $this->_formDetail($paOpportunity['status'], $aProduct, $psMonth, $nNbProducts);
          }

          if($nNbProducts > 1 || $this->_rightPay || $this->_rightProductSupervisor)
          {
            if($nNbProducts > 1)
            {
              if($nNbProducts == $paOpportunity['overall_nb_products'])
              {
                $sHTML.= ' | the '.$paOpportunity['overall_nb_products'].' items';
              }
              else
              {
                if($nNbProducts == 1)
                  $sHTML.= ' | 1 of the '.$paOpportunity['overall_nb_products'].' items';
                else
                  $sHTML.= ' | '.$nNbProducts.' of the '.$paOpportunity['overall_nb_products'].' items';
              }
            }

            $sHTML.= $oHTML->getCR(2).$oHTML->getBlocStart('products'.$nPk, array('class' => 'list-products'));
            $sHTML.= $sProducts;
            $sHTML.= $oHTML->getBlocEnd();
          }

        $sHTML.= $oHTML->getBlocEnd();

        // Right buttons: edit and delete my opportunities or all if I'm manager
        if($this->_rightManage || $this->_userPk == $paOpportunity['loginfk'])
        {
          $sHTML .= $oHTML->getBlocStart('', array('style' => 'position: absolute; top: 25%; right: 5px;  height:20px;'));
            $sHTML.= $this->_displayEditOppLink($nPk, $bPayed, $sStatus, $sTitle, true, $paOpportunity['loginfk'], $psUrlCallBack);
            $sHTML.= $this->_displayDeleteOppLink($nPk, $bPayed, $sStatus, $paOpportunity['title'], $this->_userPk, $psUrlCallBack);
          $sHTML.= $oHTML->getBlocEnd();
        }

        $sHTML.= $oHTML->getFloatHack();

      $sHTML.= $oHTML->getBlocEnd();

    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }


  // ===================================
  // FORM FUNCTIONS
  // ===================================

  private function _form($paAjax = false)
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getCpLogin();

    $oPage->addCssFile($this->getResourcePath().'/css/opportunity.css');

    $asStatus = $this->_getModel()->getStatus();

    // Setting different values if it's an edition or addition form
    if(!empty($this->cnPk))
    {
      $oDbResult = $this->_getModel()->getOpportunityByPk($this->cnPk);
      if(!$oDbResult->readFirst())
      {
        if($paAjax)
          return json_encode(array('error' => __LINE__.' - The opportunity doesn\'t exist.'));
        else
          return  __LINE__.' - The opportunity doesn\'t exist.';
      }

      $oForm = $oHTML->initForm('oppAddForm');
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT);
      $oForm->setFormParams('addopp', true, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel'=>'Save Changes'));
      $oForm->addField('input', 'opportunitypk', array('type' => 'hidden','value'=> $this->cnPk));
      $nOpportunityDetailPk = (int)getValue('opportunity_detailpk', 0);
      if(is_key($nOpportunityDetailPk))
        $oForm->addField('input', 'opportunity_detailpk', array('type' => 'hidden','value'=> $nOpportunityDetailPk));

      //if pay, a field will be displayed instead
      if(!$this->_rightPay)
        $oForm->addField('input', 'loginfk', array('type' => 'hidden', 'value' => $oDbResult->getFieldValue('loginfk')));

      $bCreateActivityField = true;
      $nProba = $oDbResult->getFieldValue('probability');
      $nItemPk = $oDbResult->getFieldValue('cp_pk', CONST_PHP_VARTYPE_INT);
      $sItemType = $oDbResult->getFieldValue('cp_type');
    }
    else
    {
      //Fetch the data from the calling component
      $sCp_Uid = getValue(CONST_CP_UID);
      if(empty($sCp_Uid))
        return $oHTML->getBlocMessage(__LINE__.' - Oops, missing some informations to create an opportunity.');

      $oDbResult = new CDbResult();
      $oForm = $oHTML->initForm('oppAddForm');
      $sURL = $oPage->getAjaxUrl($this->csUid,CONST_ACTION_SAVEADD);
      $oForm->setFormParams('addopp', true, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel'=>'Save Opportunity', 'noCancelButton' => 'noCancelButton'));

      $oForm->addField('input', CONST_CP_UID, array('type' => 'hidden', 'value' => $sCp_Uid));
      $oForm->addField('input', CONST_CP_ACTION, array('type' => 'hidden', 'value' => getValue(CONST_CP_ACTION)));
      $oForm->addField('input', CONST_CP_TYPE, array('type' => 'hidden', 'value' => getValue(CONST_CP_TYPE)));

      //if pay, a field will be displayed instead
      if(!$this->_rightPay)
        $oForm->addField('input', 'loginfk', array('type' => 'hidden', 'value' => $this->_userPk));

      $bCreateActivityField = true;
      $asCurrentStatus = current($asStatus);
      $nProba = $asCurrentStatus['probability'];
      $nItemPk = (int)getValue('cp_pk', 0);
      $sItemType = getValue('cp_type');
    }

    $sReturnUrl = getValue(CONST_URL_ACTION_RETURN);
    if(!empty($sReturnUrl))
      $oForm->addField('input', CONST_URL_ACTION_RETURN, array('type' => 'hidden', 'value' => urldecode($sReturnUrl.'&relact=none')));

    $oForm->setFormDisplayParams(array('noCancelButton' => 'noCancelButton'));

    // Description fields
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Opportunity description'));

    if($this->_rightManage || empty($this->cnPk))
    {
      $oAB = CDependency::getComponentByName('addressbook');
      $sURL = $oPage->getAjaxUrl('addressbook', CONST_ACTION_SEARCH, $sItemType);

      if($sItemType == 'cp')
      {
        $oForm->addField('selector', CONST_CP_PK, array('label'=> 'Select company', 'url' => $sURL, 'nbresult' => 1));
        if(!empty($nItemPk))
        {
          $asData = $oAB->getCompanyDataByPk($nItemPk);
          $oForm->addOption(CONST_CP_PK, array('label' => $asData['company_name'], 'value' => $nItemPk));
        }
      }
      elseif ($sItemType == 'ct')
      {
        if(!empty($nItemPk))
        {
          $asData = $oAB->getContactDataByPk($nItemPk);
          if($asData['companyfk']==0)
          {
            $oForm->addField('selector', CONST_CP_PK, array('label'=> 'Select connection', 'url' => $sURL, 'nbresult' => 1));
            $oForm->addOption(CONST_CP_PK, array('label' => $oAB->getContactName($asData), 'value' => $nItemPk));
          }
          else
          {
            $oForm->addField('select', 'cp_values', array('label'=> 'Link to'));

            $aOptionCompany = array(
                'label' => $oAB->getItemName(CONST_AB_TYPE_COMPANY, (int)$asData['companyfk']),
                'value' => urlencode(serialize(array(
                    CONST_CP_UID => '777-249',
                    CONST_CP_ACTION => CONST_ACTION_VIEW,
                    CONST_CP_TYPE => CONST_AB_TYPE_COMPANY,
                    CONST_CP_PK => (int)$asData['companyfk']
                ))),
                'selected' => 'selected'
            );
            $oForm->addOption('cp_values', $aOptionCompany);

            $aOptionConnection = array(
                'label' => $asData['firstname'].' '.$asData['lastname'],
                'value' => urlencode(serialize(array(
                    CONST_CP_UID => '777-249',
                    CONST_CP_ACTION => CONST_ACTION_VIEW,
                    CONST_CP_TYPE => CONST_AB_TYPE_CONTACT,
                    CONST_CP_PK => $nItemPk
                )))
            );
            $oForm->addOption('cp_values', $aOptionConnection);

          }
        }
        else
        {
          $oForm->addField('selector', CONST_CP_PK, array('label'=> 'Select connection', 'url' => $sURL, 'nbresult' => 1));
        }

//      $oForm->addField('selector', CONST_CP_PK, array('label'=> 'Select connection', 'url' => $sURL, 'nbresult' => 1));
      }
      else
        return assert('false; // Error: type of item unknown');
    }
    else
    {
      $oForm->addField('input', CONST_CP_PK, array('type' => 'hidden', 'value' => $nItemPk));
    }


    //WHY ???  item pk as opportunity pk ?? OO__OO
    /*if(empty($this->cnPk))
      $this->cnPk = (int)getValue(CONST_CP_PK, 0);*/

    if($this->_rightPay)
    {
      $sURL = $oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1, 'friendly' => 1));
      $oForm->addField('selector', 'loginfk', array('label' => 'Created by', 'url' => $sURL));
      $oForm->setFieldControl('loginfk', array('jsFieldNotEmpty' => '1'));

      //$aResultUsers = $oLogin->getUserList();
      //TODO: temporary solution until we get proper group management, allow to get a list of users (active or inactive) filtered by team
      //$aResultUsers = $oLogin->getUserByTeam(-1, '', false);
      $nOwner = (int)$oDbResult->getFieldValue('loginfk');
      if(empty($nOwner))
      {
        $nOwner = $oLogin->getUserPk();
        $asUser = $oLogin->getUserData();
      }
      else
      {
        $asUser = $oLogin->getUserList($nOwner, false, true);
        $asUser = $asUser[$nOwner];
      }


      $oForm->addOption('loginfk', array('value'=> $nOwner, 'label' => $asUser['firstname'].' '.$asUser['lastname'] ));


      /*if($oDbResult->getFieldValue('loginfk'))
        $sJavascript = "<script>$('select[name=\"loginfk\"] option[value=\"".$oDbResult->getFieldValue('loginfk')."\"]').attr('selected','selected');</script>";
      else
        $sJavascript = "<script>$('select[name=\"loginfk\"] option[value=\"".$oLogin->getUserPk()."\"]').attr('selected','selected');</script>";

      $oForm->addField('misc', 'selectoption', array('text' => $sJavascript, 'type' => 'text'));*/
    }

    $oForm->addField('input', 'title', array('type' => 'text', 'label'=>'Title', 'value' => $oDbResult->getFieldValue('title')));
    $oForm->setFieldControl('title', array('jsFieldNotEmpty' => '', 'jsFieldMinSize' => '1','jsFieldMaxSize' => 255));

    $oForm->addField('textarea', 'description', array('label'=>'Additional information (optional)', 'value' => $oDbResult->getFieldValue('description')));
    $oForm->setFieldControl('description', array('jsFieldMaxSize' => 4096));

    $nStatusId = uniqid();
    $sProbaJs = '
      var nProbSetByUser = $(\'input[name=probability]\').attr(\'set\');
      var nLength = $(\'input[name=probability]\').val().length;
      if(nProbSetByUser != 1 || !nLength)
      {
        var nProb = $(\'option:selected\', this).attr(\'probability\');
        $(\'input[name=probability]\').val(nProb);
        $(\'input[name=probability]\').attr(\'set\', 0);
      }';
    $oForm->addField('select', 'status', array('id' => $nStatusId, 'label' => 'Status', 'onchange' => $sProbaJs));
    $oForm->setFieldControl('status', array('jsFieldNotEmpty' => ''));

    $sStatus = $oDbResult->getFieldValue('status');
    foreach($asStatus as $asRow)
    {
      if($sStatus == $asRow['value'])
        $asRow['selected'] = 'selected';

      $oForm->addOption('status', $asRow);
    }

    // This field just tells function _save if the status has been updated
    $oForm->addField('input', 'initial_status', array('type' => 'hidden', 'value' => $sStatus));

    $sProbaJs = '$(this).attr(\'set\', 1);';

    $oForm->addField('input', 'probability', array('label'=>'Probability', 'type' => 'text', 'value' => $nProba, 'style' => 'width:50px;', 'onfocus' => $sProbaJs));
    $oForm->setFieldControl('probability', array('jsFieldNotEmpty' => '', 'jsFieldTypeIntegerPositive' => '', 'jsFieldSmallerThan' => '101'));

    $oForm->addField('misc', 'javascript', array('type' => 'text','text'=> '<script>$(\'#'.$nStatusId.' option[value='.$oDbResult->getFieldValue('status').']\').attr(\'selected\',\'selected\')</script>'));

    if($bCreateActivityField)
    {
      if(!$this->_rightAdmin)
        $sDefault = 'checked';
      else
        $sDefault = '';

      //$oForm->addField('checkbox', 'addactivity', array('label'=>'Add activity ?', 'value' => 1, 'checked' => $sDefault, 'textbefore' => 1, 'legend' => 'Log change in the activity feed'));
      $oForm->addField('checkbox', 'addactivity', array('value' => 1, 'checked' => $sDefault, 'label' => 'Log change in the activity feed'));
    }


    //------------------------------------------------------------------
    //Will start managing the products
    // Detail opporunity fields
    $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Opportunity detail'));

    $bHaveDetails = false;
    if(is_key($this->cnPk))
    {
      $oDbDetails = $this->_getModel()->getDetailByOpportunityFk($this->cnPk);
      $bHaveDetails = $oDbDetails->readFirst();
    }

    if($bHaveDetails)
      $sFieldClass = 'hidden';
    else
      $sFieldClass = '';


    // Manage product lines -- ADD DETAIL LIGNE
    //if we have products, we hide and disable the first row
    $oForm->addSection('duplicate', array('keepNextInline' => 1, 'id' => 'duplicate'));

      // Month
      $aMonthOptions = array ('label' => 'Month', 'class' => 'month');
      if($bHaveDetails)
        $aMonthOptions['disabled'] = 'disabled';

      $oForm->addField('select', 'month[]', $aMonthOptions);
      $oForm->setFieldControl('month[]', array('jsFieldNotEmpty' => ''));
      $oForm->setFieldDisplayParams('month[]', array('keepNextInline' => 1, 'style' => 'width:30%; float:left;', 'class' => $sFieldClass));

      $nCurrentDay = date('d');
      $nCurrentMonth = date('m')+1;
      $nCurrentYear = date('Y');

      if($nCurrentDay > 11)
        $sDefaultMonth = date('Y-m-01', strtotime('+1 month'));
      else
        $sDefaultMonth = date('Y-m-01');

      $sReferal = $nCurrentMonth;
      $FieldYear = $nCurrentYear;
      for($nCountMonths = -6; $nCountMonths < 19; $nCountMonths++)
      {
        $nComputedMonth = $sReferal+$nCountMonths;

        $ndMonth = mktime(0, 0, 0, $nComputedMonth, 1, $FieldYear);
        $nMonth = date('Y-m-01', $ndMonth);
        $sMonth = date('F Y', $ndMonth);

        if($sReferal == $nComputedMonth)
          $oForm->addOption('month[]', array('value'=> $nMonth, 'label' => $sMonth, 'selected' => 'selected'));
        else
          $oForm->addOption('month[]', array('value'=> $nMonth, 'label' => $sMonth));
      }

      // Amount
      $aAmountOptions = array('label' => 'Amount', 'type' => 'text', 'value' => '');
      if($bHaveDetails)
        $aAmountOptions['disabled'] = 'disabled';

      $oForm->addField('input', 'amount[]', $aAmountOptions);
      $oForm->setFieldDisplayParams('amount[]', array('keepNextInline' => 1, 'style' => 'width:27%; float:left;', 'class' => $sFieldClass));
      $oForm->setFieldControl('amount[]', array('jsFieldTypeIntegerPositive' => '1'));

      // Products
      $aSelectOptions = array('label' => 'Product');
      if($bHaveDetails)
        $aSelectOptions['disabled'] = 'disabled';

      $oForm->addField('select', 'product[]', $aSelectOptions);
      $oForm->setFieldDisplayParams('product[]', array('keepNextInline' => 1, 'style' => 'width:28%; float:left;', 'class' => $sFieldClass));
      $oForm->setFieldControl('product[]', array('jsFieldNotEmpty' => ''));
      $oForm->addOption('product[]', array('label' => 'Select a product', 'disabled' => 'disabled'));

      foreach ($this->_aProducts as $sSection => $aProducts)
      {
        foreach ($aProducts as $sProduct)
        {
          $aOption = array('value' => $sProduct, 'label' => $sProduct, 'group' => $sSection);
          $oForm->addOption('product[]', $aOption);
        }
      }

      // Bouton delete
      $sTextDelete = '<a href=\'#\' class=\'deletedetail\' onclick=\'$(this).parent().parent().parent().remove(); return false;\'><img src='.CONST_PICTURE_DELETE.'></a>';
      $oForm->addField('misc', 'link', array('text' => $sTextDelete, 'type' => 'text'));
      $oForm->setFieldDisplayParams('link', array('keepNextInline' => 1, 'style' => 'width:35px; float:left;'));

      $sAddButton = '<a id=\'add\' href=\'#\' style=\'float:left; display:block;\'><img src='.CONST_PICTURE_ADD.'>Add prod.</a>';
      $oForm->addField('misc', 'linkadd', array('text' => $sAddButton, 'type' => 'text'));
      $oForm->setFieldDisplayParams('linkadd', array('keepNextInline' => 1, 'style' => 'float:left; display:block; width:80px;'));

    $oForm->closeSection();




    // EXISTING DETAIL FIELDS
    $oForm->addSection('formcont', array('id' => 'formcont'));
    if($bHaveDetails)
    {
      $bRead = $oDbDetails->readFirst();
      $bBooked = (bool)$oDbDetails->getFieldValue('booked');

      $nCount = 0;
      while($bRead)
      {
        $nCount++;
        //$bBooked = (bool)$oDbResult->getFieldValue('booked') && !$this->_rightProductSupervisor;
        $bBooked = (bool)$oDbResult->getFieldValue('booked');
        $bPaid = (bool)$oDbResult->getFieldValue('paid');
        $bDelivered = (bool)$oDbResult->getFieldValue('delivered');
        $bInvoiced = (bool)$oDbResult->getFieldValue('invoiced');

        $bLocked = $bBooked || $bPaid || $bDelivered || $bInvoiced;

        $oForm->addSection('sectiond'.$nCount, array('keepNextInline' => 1));

          // Month
          $aMonthOptions =array ('label' => 'Month', 'class' => 'month');
          if($bLocked)
          {
            $aMonthOptions['disabled'] = 'disabled';
            $aMonthOptions['class'].= 'product_disabled';
          }

          $oForm->addField('select', 'emonth-'.$nCount, $aMonthOptions);
          $oForm->setFieldControl('emonth-'.$nCount, array('jsFieldNotEmpty' => ''));
          $oForm->setFieldDisplayParams('emonth-'.$nCount, array('keepNextInline' => 1, 'style' => 'width:30%; float:left;'));

          $sReferal = date("m", strtotime($oDbResult->getFieldValue('month')));
          $FieldYear  = date('Y', strtotime($oDbResult->getFieldValue('month')));

          for($nCountMonths = -12; $nCountMonths < 19; $nCountMonths++)
          {
            $ndMonth = mktime(0, 0, 0, $sReferal+$nCountMonths, 1, $FieldYear);
            $nMonth = date('Y-m-01', $ndMonth);
            $sMonth = date('F Y', $ndMonth);

            if($nMonth == $oDbResult->getFieldValue('month'))
              $oForm->addOption('emonth-'.$nCount, array('selected' => 'selected', 'value'=> $nMonth, 'label' => $sMonth));
            else
              $oForm->addOption('emonth-'.$nCount, array('value' => $nMonth, 'label' => $sMonth));
          }

          // Amount
          $aAmountOptions = array('label' => 'Amount', 'type' => 'text', 'value' => $oDbResult->getFieldValue("amount"));
          if($bLocked)
          {
            $aAmountOptions['disabled'] = 'disabled';
            $aAmountOptions['class'] = 'product_disabled';
          }

          $oForm->addField('input', 'eamount-'.$nCount, $aAmountOptions);
          $oForm->setFieldControl('eamount-'.$nCount, array('jsFieldTypeIntegerPositive' => '1'));
          $oForm->setFieldDisplayParams('eamount-'.$nCount, array('keepNextInline' => 1, 'style' => 'width:27%; float:left;'));

          $aSelectOptions = array('label' => 'Product', 'title' => 'Previous value: '.$oDbResult->getFieldValue('product').' / ');
          if($bLocked)
          {
            $aSelectOptions['disabled'] = 'disabled';
            $aSelectOptions['class'] = 'product_disabled';
          }

          $oForm->addField('select', 'eproduct-'.$nCount, $aSelectOptions);
          $oForm->setFieldDisplayParams('eproduct-'.$nCount, array('keepNextInline' => 1, 'style' => 'width:28%; float:left;'));
          $oForm->setFieldControl('eproduct-'.$nCount, array('jsFieldNotEmpty' => ''));
          $oForm->addOption('eproduct-'.$nCount, array('label' => 'Select a product', 'disabled' => 'disabled'));

          foreach ($this->_aProducts as $sSection => $aProducts)
          {
            $oForm->addOption('eproduct-'.$nCount, array('label' => $sSection, 'disabled' => 'disabled'));
            foreach ($aProducts as $sProduct)
            {
              $aOption = array('value' => $sProduct, 'label' => '--'.$sProduct);
              if($oDbResult->getFieldValue('product')==$sProduct)
                $aOption['selected'] = 'selected';

              $oForm->addOption('eproduct-'.$nCount, $aOption);
            }
          }

          // Delete button
          if(!$bLocked)
            $oForm->addField('misc', 'link-'.$nCount, array('text' => '<a href=\'#\' class=\'deletedetail\' onclick=\'$(this).parent().parent().parent().remove(); return false;\'><img src='.CONST_PICTURE_DELETE.'></a>', 'type' => 'text'));
          else
          {
            if($bPaid)
              $oForm->addField('misc', 'link-'.$nCount, array('text' => 'Paid', 'type' => 'text', 'class' => 'product_locked'));
            elseif($bInvoiced)
              $oForm->addField('misc', 'link-'.$nCount, array('text' => 'Invoiced', 'type' => 'text', 'class' => 'product_locked'));
            elseif($bBooked)
              $oForm->addField('misc', 'link-'.$nCount, array('text' => 'Delivered', 'type' => 'text', 'class' => 'product_locked'));
            else
              $oForm->addField('misc', 'link-'.$nCount, array('text' => 'Booked', 'type' => 'text', 'class' => 'product_locked'));
          }
          $oForm->setFieldDisplayParams('link-'.$nCount, array('keepNextInline' => 1, 'style' => 'width:40px; float:left;'));

        $oForm->closeSection();

        $nCount++;
        $bRead = $oDbResult->readNext();
      }

    }
    $oForm->closeSection();



    $sJsAddButton = '
      <script>
        $(\'#duplicate .deletedetail\').hide();
        $(function() {
          $(\'#add\').click(function()
          {
            var sValue = $(\'#addoppId .month:last option:selected\').val();

            var oTemplateRow = $(\'#duplicate\').clone();

            $(\'input, select\', oTemplateRow).removeAttr(\'disabled\');
            $(\'.formFieldContainer\', oTemplateRow).removeClass(\'hidden\');
            $(oTemplateRow).appendTo(\'div#formcont\');

            $(\'div#formcont .deletedetail\').show();
            $(\'div#formcont #add\').remove();

            console.log(\'last saved value: \'+sValue);
            console.log(\'last value now: \'+ $(\'.month:last option:selected\').val() );

            $(\'.month:last option[value=\'+sValue+\']\').next().prop(\'selected\', \'selected\');

            return false;
          });
        });
      </script>';
     $oForm->addField('misc', 'linkjsadd', array('text' => $sJsAddButton, 'type' => 'text'));

    $sHTML= $oHTML->getBlocStart();
      $sHTML.= $oForm->getDisplay();
    $sHTML.= $oHTML->getBlocEnd();

    if($paAjax)
      return $oPage->getAjaxExtraContent(array('data'=>$sHTML));
    else
      return $sHTML;
  }

  // ----------------------------------
  // RECEIVING AND MANAGING $_POST DATA
  // ----------------------------------

  private function _saveAddOpportunity()
  {
    if(!assert('is_cpValues($this->casCpValues)'))
      return array('notice' => 'Could not save opportunity. Wrong values posted.');

    $asStatus = $this->_getModel()->getStatus();
    $sStatus = getValue('status');
    if(!isset($asStatus[$sStatus]))
      return array('error' =>  __LINE__.' - Opportunity status incorrect. ['.$sStatus.']');

    $nItemPk = $this->_getModel()->addOpportunity(
            array ( 'loginfk' => (int)getValue('loginfk'),
                    'title' => getValue('title'),
                    'description' => getValue('description'),
                    'status' => $sStatus,
                    'probability' => (int)getValue('probability'),
                    'date_update' => date('Y-m-d H:i:s')
                  )
            );

    $avValues = array();
    $nTotal = 0;
    for($nCount = 0; $nCount < count($_POST['amount']); $nCount++)
    {
      $sAmount = $_POST['amount'][$nCount];
      $sAmount = str_replace(array(' ', ','), '', $sAmount);

      $nAmount = (int)$sAmount;
      $sProduct = $_POST['product'][$nCount];
      $sDate = $_POST['month'][$nCount];

      if(!is_date($sDate))
        return array('error' => 'Could not save opportunity. Incorrect date on line #'.($nCount+1).'.');

      if($nAmount > 0 && ($nAmount < 1000 || $nAmount != $sAmount))
        return array('error' => 'Could not save opportunity. Incorrect amount on line #'.($nCount+1).'.');

      if(empty($sProduct))
        return array('error' => 'Could not save opportunity. Incorrect product name on line #'.($nCount+1).'.');

      $avValues[] = array('month' => $sDate, 'amount' => $nAmount, 'product' => $sProduct);
      $nTotal+= $nAmount;
    }

    $aCpValues = $this->casCpValues;
    $vPostCpValues = getValue('cp_values', false);
    if($vPostCpValues)
      $aCpValues = unserialize(urldecode($vPostCpValues));


    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    //check the item exists and is available for user
    $oComponent = CDependency::getComponentByUid($aCpValues[CONST_CP_UID]);
    if(!$oComponent)
      return array('error' =>  __LINE__.' - bad parameters. Can not load item data.');

    $nCpPk = $aCpValues[CONST_CP_PK];
    $asDescription =  $oComponent->getItemDescription($nCpPk, $aCpValues[CONST_CP_ACTION], $aCpValues[CONST_CP_TYPE]);
    if(empty($asDescription))
      return array('error' =>  __LINE__.' - bad parameters. Can not load item data.');


    $nResult = $this->_getModel()->addDetail($avValues, $nItemPk);
    if(empty($nResult))
      return array('error' =>  __LINE__.' - An error has occured, we could not save opportunity.');



    $nResult = $this->_getModel()->addLink($aCpValues, $nItemPk);
    if(empty($nResult))
      return array('error' => __LINE__.' - An error has occured, we could not save opportunity.');

    $oEvent = CDependency::getComponentByName('event');
    if($oEvent)
    {
      $sUid = getValue(CONST_CP_UID);
      $sAction = getValue(CONST_CP_ACTION);
      $sType = getValue(CONST_CP_TYPE);
      $nPk = (int)getValue(CONST_CP_PK);

      $sContent = getValue('title');
      $sContent.= ' | status: '.$asStatus[$sStatus]['label'].' | amount: '.formatNumber($nTotal, '', ',', '¥').' ('.getValue('probability').'%)';
      $oEvent->quickAddEvent('opp-'.$asStatus[$sStatus]['shortname'], 'New business opportunity created', $sContent, $sUid, $sType, $sAction, $nPk, true);
    }

    $oPage = CDependency::getCpPage();
    $sUrlView = $oPage->getUrl($aCpValues[CONST_CP_UID], $aCpValues[CONST_CP_ACTION], $aCpValues[CONST_CP_TYPE], $aCpValues[CONST_CP_PK]);

    $oLogin = CDependency::getComponentByName('login');
    $oLogin->logUserActivity($oLogin->getUserPk(), $this->csUid, $this->getAction(), CONST_OPPORTUNITY, $nItemPk, 'New opportunity ['.$asStatus[$sStatus]['label'].']', $asDescription[$nCpPk]['label'], $sUrlView);

    $sUrl = getValue(CONST_URL_ACTION_RETURN);
    if(empty($sUrl))
      return array('notice' => 'Opportunity added successfully.', 'reload' => 1);

    //In case we 're refreshing the current page, i add the time to make sure it won't be cache
    return array('notice' => 'Opportunity added successfully.', 'url' => $sUrl.'&time='.time());
  }

  private function _saveEditOpportunity()
  {
    $nOppPk = (int)getValue('opportunitypk');
    $nCompanyPk = (int)getValue(CONST_CP_PK, 0);
    $nLoginFk = (int)getValue('loginfk',0);
    $sTitle = getValue('title');
    $sDescription = getValue('description');
    $nProbability = (int)getValue('probability');

    if($nOppPk < 0 || $nCompanyPk < 0)
      return array('error' => __LINE__.' - Bad parameters.');

    $oDbResult = $this->_getModel()->getByPk($nOppPk, 'opportunity');
    if(!$oDbResult)
      return array('error' => 'Could not find the opportunity.');

    $asStatus = $this->_getModel()->getStatus();
    $sStatus = getValue('status', '');
    if(!isset($asStatus[$sStatus]))
      return array('error' =>  __LINE__.' - Opportunity status incorrect. ['.$sStatus.']');

    $sInitialStatus = getValue('initial_status');

    $asPrevious = $oDbResult->getData();

    $aData = array (
                  'loginfk' => $nLoginFk,
                  'title' => $sTitle,
                  'description' => $sDescription,
                  'status' => $sStatus,
                  'probability' => $nProbability,
                  'opportunitypk' => $nOppPk,
                  'date_update' => date('Y-m-d H:i:s')
                );

    $this->_getModel()->updateOpportunity($aData);

    $this->_getModel()->deleteByWhere('opportunity_detail', 'opportunityfk = '.$nOppPk.' AND delivered = 0 AND paid = 0 AND invoiced = 0 AND booked = 0 ');

    $avValues = array();
    if(isset($_POST['amount']))
    {
      for($nCount=0; $nCount < count($_POST['amount']); $nCount++)
      {
        $nAmount = (int)$_POST['amount'][$nCount];
        $sDate = $_POST['month'][$nCount];

        if(!is_date($sDate))
          return array('error' => 'Could not save opportunity. Date is incorrect on details #'.($nCount+1).'.');

        if(!empty($nAmount) && $nAmount < 10000)
          return array('error' => 'Could not save opportunity. Amount is incorrect on details #'.($nCount+1).'.');

        $avValues[] = array('month' => $_POST['month'][$nCount], 'amount' => $_POST['amount'][$nCount], 'product' => $_POST['product'][$nCount]);
      }
    }

    for($nCount=0; $nCount<50; $nCount++)
    {
      if(isset($_POST['emonth-'.$nCount]))
      {
        if(!assert('is_date($_POST[\'emonth-\'.$nCount]) && is_integer((int)$_POST[\'eamount-\'.$nCount]) && (int)($_POST[\'eamount-\'.$nCount]>=0)'))
          return array('notice' => 'Could not save opportunity. Wrong values posted.');

        $avValues[]= array('month' => $_POST['emonth-'.$nCount], 'amount' => $_POST['eamount-'.$nCount], 'product' => $_POST['eproduct-'.$nCount]);
      }
    }

    $this->_getModel()->addDetail($avValues,$nOppPk);
    $oLink = $this->_getModel()->getLinkByPk($nOppPk);

    //admin moving an opportunity
    if( $this->_rightManage && !empty($nCompanyPk))
    {
      $asLink = $oLink->getData();
      $asLink['cp_pk'] = $nCompanyPk;

      $this->_getModel()->update($asLink, 'opportunity_link');
    }

    $nAddActivity = (int)getValue('addactivity');
    $oEvent = CDependency::getComponentByName('event');
    if($nAddActivity > 0 && $oEvent)
    {
      $sContent = getValue('title').' | ';

      if($asPrevious['status'] != $sStatus)
        $sContent.= 'status changed to <strong>'.$asStatus[$sStatus]['label'].'</strong>';
      else
        $sContent.= 'status: '.$sStatus;

      $sContent.= ' | amount: '.formatNumber($this->_getModel()->getTotalAmountByPk($nOppPk), '', ',', '¥').' ('.getValue('probability').'%)';

      $oEvent->quickAddEvent('opp-'.$asStatus[$sStatus]['shortname'], 'Opportunity update ', $sContent, $oLink->getFieldValue(CONST_CP_UID), $oLink->getFieldValue(CONST_CP_TYPE), $oLink->getFieldValue(CONST_CP_ACTION), (int)$oLink->getFieldValue(CONST_CP_PK), true);
    }

    //Everything saved...
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getComponentByName('login');
    $sUrlView = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_OPPORTUNITY, $nOppPk);
    $oLogin->logUserActivity($oLogin->getUserPk(), $this->csUid, $this->getAction(), CONST_OPPORTUNITY, $nOppPk, 'Updated opportunity ['.$asStatus[$sStatus]['label'].']', getValue('title'), $sUrlView);


    if(($sInitialStatus != $sStatus) && ($sStatus == 'signed'))
    {
      $oDbResult = $this->_getModel()->getByFk($nOppPk, 'opportunity_link', 'opportunity');
      $bRead = $oDbResult->readFirst();
      if(assert('$bRead === true'))
      {
        $aData['cp_uid'] = $oDbResult->getFieldValue('cp_uid');
        $aData['cp_action'] = $oDbResult->getFieldValue('cp_action');
        $aData['cp_type'] = $oDbResult->getFieldValue('cp_type');
        $aData['cp_pk'] = (int)$oDbResult->getFieldValue('cp_pk');

        $this->_notifyManager($aData, $avValues, $oLink->getData());
      }
    }

    $sUrlCallBack = getValue(CONST_URL_ACTION_RETURN, '');
    if(!empty($sUrlCallBack))
      return array('notice' => 'Opportunity updated successfully.', 'timedUrl' => $sUrlCallBack);
    else
      return array('notice' => 'Opportunity updated successfully.', 'reload' => 1);
  }

  // Notify Asi when an opportunity is signed
  // TODO : use notification component for that
  private function _notifyManager($paOpp, $paOppDetails, $paLink)
  {
    if(!assert('is_array($paOpp) && !empty($paOpp)'))
      return false;

    if(!assert('is_array($paOppDetails) && !empty($paOppDetails)'))
      return false;

    if(!assert('is_array($paLink) && !empty($paLink)'))
      return false;


    //fetch item description
    $oComponent = CDependency::getComponentByUid($paOpp['cp_uid']);
    if(!$oComponent)
      return false;

    $paOpp['cp_pk'] = (int)$paOpp['cp_pk'];
    $asItemDesc = $oComponent->getItemDescription($paOpp['cp_pk'], $paOpp['cp_action'], $paOpp['cp_type']);
    if(empty($asItemDesc))
      return false;

    $oMail = CDependency::getComponentByName('mail');
    $paOpp['details'] = $paOppDetails;
    $paOpp['link'] = $paLink;

    $sHTML = '';
    $sHTML .= 'Hi!<br/>The following opportunity has been signed.<br/><br/>';
    $sHTML .= $this->_displayEmailOpportunity($paOpp);
    $sHTML .= '<br/><br/>Thank you for using BCM.';

    $sEmail = ((bool)CONST_DEV_SERVER) ? CONST_DEV_EMAIL : 'arinestine@bulbouscell.com';

    return $oMail->sendRawEmail(CONST_PHPMAILER_EMAIL, $sEmail, 'BCM (OPP/'.$paOpp['status'].') - '.$asItemDesc[$paOpp['cp_pk']]['label'], $sHTML);
  }

  // A product has been booked
  private function _notifyTwTeam($pnOppDetailPk)
  {
    if(!assert('is_key($pnOppDetailPk)'))
      return false;

    $oMail = CDependency::getComponentByName('mail');
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $aOpps = $this->_getModel()->getOpportunityByDetailPk($pnOppDetailPk);
    $aOpp = current($aOpps);

    //fetch item description
    $oComponent = CDependency::getComponentByUid($aOpp['cp_uid']);
    if(!$oComponent)
      return false;

    $aOpp['cp_pk'] = (int)$aOpp['cp_pk'];
    $asItemDesc = $oComponent->getItemDescription($aOpp['cp_pk'], $aOpp['cp_action'], $aOpp['cp_type']);
    if(empty($asItemDesc))
      return false;

    $sHTML = '';
    $sHTML .= 'Hi!<br/>The following product has been booked.<br/><br/>';
    $sHTML .= $this->_displayEmailOpportunity($aOpp, $pnOppDetailPk);
    $sURL = $oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_OPPORTUNITY, 0, array('display' => 'bbook'));
    $sHTML .= '<br/><br/>Please consult '.$oHTML->getLink('Tokyo Weekender Magazine', $sURL);
    $sHTML .= '<br/><br/>Thank you for using BCM.';

    $sEmail = ((bool)CONST_DEV_SERVER) ? CONST_DEV_EMAIL : 'ajordan@bulbouscell.com; lramshaw@bulbouscell.com';

    return $oMail->sendRawEmail(CONST_PHPMAILER_EMAIL, $sEmail, 'BCM (OPP/Booked) - '.$asItemDesc[$aOpp['cp_pk']]['label'], $sHTML);
  }

  // Email formating, to display only one product, set up $pnOppDetailPk
  private function _displayEmailOpportunity($paOpp, $pnOppDetailPk = 0)
  {
    if(!assert('is_numeric($pnOppDetailPk)'))
      return '';

    if(!assert('is_array($paOpp) && !empty($paOpp)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $asUserData = $oLogin->getUserList(0, false, true);

    $sURL = $oPage->getUrl($paOpp['cp_uid'], $paOpp['cp_action'], $paOpp['cp_type'], (int)$paOpp['cp_pk']);
    $sPic = $oHTML->getPicture('/common/pictures/items/'.$paOpp['cp_type'].'_16.png');

    $oCompItem = CDependency::getComponentByUid($paOpp['cp_uid']);
    $sItemName = ($oCompItem) ? $oCompItem->getItemName($paOpp['cp_type'], (int)$paOpp['cp_pk']) : 'Related Item';
    $sItemLabel = $oHTML->getLink($sPic.' '.$sItemName, $sURL);

    $sHTML = '';
    $sBr = $oHTML->getCR(1);

    $sHTML.= $oHTML->getBlocStart('', array('style' => 'padding:0 10px 0 10px; margin-bottom:10px; border-left:1px solid #e5eaec;'));

    $sHTML.= $oHTML->getBloc('', $paOpp['title'], array('style' => 'font-size:18px;')).$sBr;
    $sHTML.= $oHTML->getBloc('', $sItemLabel).$sBr;
    $sHTML.= $oHTML->getBloc('', 'By : <strong>'.$oLogin->getUserNameFromData($asUserData[$paOpp['loginfk']], true).'</strong>').$sBr;

    foreach($paOpp['details'] as $aDetail)
    {
      if(($pnOppDetailPk==0) || ($pnOppDetailPk==(int)$aDetail['opportunity_detailpk']))
        $sHTML.= $oHTML->getBloc('', date('M-Y', strtotime($aDetail['month'])).': '.$aDetail['product'].', '.formatNumber((int)$aDetail['amount'], '', ',', '¥')).$sBr;
    }

    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _saveFastEditOpportunity()
  {
    $sStatus = getValue('status', '');
    if(!assert('in_array($sStatus, $this->_getModel()->aProductStatus)'))
      return array('error' => 'Could not edit opportunity, wrong status given');

    if(!assert('is_key($this->cnPk)'))
      return array('error' => 'Could not edit opportunity, wrong pk given');

    $nNewStatus = $this->_getModel()->switchProductStatus($this->cnPk, $sStatus);
    if($nNewStatus==-1)
      return array('error' => 'There has been an unknown error.');

    $sLabel = ($nNewStatus == 0) ? 'click to set '.$sStatus : 'click to remove '.$sStatus;
    $sOpacity = ($nNewStatus == 0) ? '0.3' : '1';

    $sAction = '$(\'#'.$sStatus.'_'.$this->cnPk.'\').css(\'opacity\', \''.$sOpacity.'\').attr(\'title\', \''.$sLabel.'\');';

    if(($nNewStatus==1) && ($sStatus=='booked'))
      $this->_notifyTwTeam($this->cnPk);

    return array('action' => $sAction);
  }

  // --------------------------------------------------
  // EXTERNAL USE - Generating content to be used by
  // Address book component
  // --------------------------------------------------

  public function getCount($paValues)
  {
    return $this->_getModel()->getCountFromCpValues($paValues);
  }

  public function getTabContent($asValues)
  {
    if(!assert('is_cpValues($asValues)'))
      return '';

    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();

    $oPage->addCssFile($this->getResourcePath().'css/opportunity.css');

    $asUserData = $oLogin->getUserList(0, false, true);

    $sUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, $asValues);
    $sAjax = 'var oConf = goPopup.getConfig();
              oConf.height = 660;
              oConf.width = 980;
              oConf.modal = true;
              goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ';

    $sHTML= $oHTML->getBlocStart();
    $sHTML.= $oHTML->getActionButton('Add a new opportunity', '', CONST_PICTURE_ADD, array('onclick' => $sAjax));
    $sHTML.= $oHTML->getCR(2);

    // Initializing tab content
    $asTabs = array();

    $aOpportunities = $this->_getModel()->getDetailedOpportunitiesByLink($asValues);

    if(empty($aOpportunities))
    {
      $sHTML.= $oHTML->getBlocMessage('No opportunity was found.');
      return $sHTML . $oHTML->getBlocEnd();
    }

    $asContent = array();
    $asAllContent = array();

    foreach($aOpportunities as $aOpportunity)
    {
      $sStatus = $aOpportunity['status'];

      if(!isset($asContent[$sStatus]))
        $asContent[$sStatus] = array();

      $sLine = $this->_displayRow($aOpportunity, true, $asUserData, '', '', false);
      $asContent[$sStatus][] = $sLine;

      $asAllContent[$aOpportunity['date_added'].uniqid()] = $sLine;
    }

    foreach($asContent as $sStatus => $asRows)
      $asTabs[] = array ('label' => $sStatus, 'title' => $sStatus.' ('.count($asRows).')', 'content' => implode('', $asRows));


    if(count($asContent) > 1)
    {
    //  krsort($asAllContent); Why ???
      array_unshift($asTabs, array('label' => 'all', 'title' => 'all'.' ('.count($asAllContent).')', 'content' => implode('', $asAllContent)));
      $sDefaultTab = 'all';
    }
    else
      $sDefaultTab = $sStatus;

    $sHTML.= $oHTML->getTabs('opplist_tabs', $asTabs, $sDefaultTab, 'vertical divlist');
    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  public function displayAddLink($paCpValues, $bDisplayText = true)
  {
    if(!$this->_rightManage)
      return '';

    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();
    $sUrl = $oPage->getAjaxUrl('opportunity', CONST_ACTION_ADD, CONST_OPPORTUNITY, 0, $paCpValues);
    $sAjax = 'var oConf = goPopup.getConfig();
                oConf.height = 660;
                oConf.width = 980;
                oConf.modal = true;
                goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ';

    $sText ='';
    if($bDisplayText)
      $sText = ' Add a business opportunity';

    $sHTML = $oHTML->getLink($oHTML->getPicture(CONST_PICTURE_OPPORTUNITY, 'Add opportunity').$sText, 'javascript:;', array('onclick' => $sAjax));
    return $sHTML;
  }

  // Deprecated: use _displayRow() instead
  private function _displayTabRow($pnPk, $paOpportunity)
  {
    if(!assert('is_array($paOpportunity) && !empty($paOpportunity)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getCpLogin();

    $psStatus = $paOpportunity['status'];

    $sHTML = $oHTML->getBlocStart('', array('class' => 'columns divlist-item'));
        $sTitle = $paOpportunity['title'];

        $sUrlEdit = $oPage->getAjaxUrl('opportunity', CONST_ACTION_EDIT, CONST_OPPORTUNITY, $pnPk);
        $sAjaxEdit = 'var oConf = goPopup.getConfig();
                      oConf.height = 660;
                      oConf.width = 980;
                      oConf.modal = true;
                      goPopup.setLayerFromAjax(oConf, \''.$sUrlEdit.'\'); ';

        if(($psStatus != 'signed') && $this->_rightManage)
          $sTitle = $oHTML->getLink($sTitle,'javascript:;', array('onclick' => $sAjaxEdit));

        $sHTML.= $oHTML->getBlocStart('', array('style' => 'padding: 0 0 0 10px;', 'class' => 'item-title', 'title' => strip_tags($paOpportunity['description'])));
        $sHTML.= $sTitle;
        $sHTML.= $oHTML->getBlocEnd();


        $sShortContent = strip_tags($paOpportunity['description']);
        if(strlen($sShortContent) > 250)
        {
          $sShortContent = substr($sShortContent, 0, 247).'...';
          $sContent = $oHTML->getTogglingText($sShortContent, $paOpportunity['description']);
        }
        else
        {
          $sContent = $sShortContent;
        }

        $sHTML.= $oHTML->getBlocStart('', array('style' => 'padding: 0 0 10px 10px; width:100%;', 'class' => 'item-description'));
        $sHTML.= $sContent;
        $sHTML.= $oHTML->getBlocEnd();

        // Date and Creator
        $sHTML.= $oHTML->getBlocStart('', array('style' => 'padding:0 10px 0 10px; width:150px; min-height:40px; display:block;'));
        $sHTML.= $oHTML->getText($paOpportunity['date_added']);
        $sHTML.= $oHTML->getCR();
        $sHTML.= $oHTML->getText('by '.$paOpportunity['created_by']);
        $sHTML.= $oHTML->getBlocEnd();

        // Content
        $sHTML.= $oHTML->getBlocStart('', array('style' => 'padding:0 10px 0 10px; margin-bottom:10px; min-height:40px; border-left:1px solid #e5eaec;'));

          $sHTML .= 'Issue : <strong>'.$paOpportunity['issue'].'</strong>';
          $sHTML .= ' | Total Amount : <strong>'.formatNumber((int)$paOpportunity['total'], '', ',', '¥').'</strong>';
          $sHTML .= ' | Total Projected : <strong>'.formatNumber((int)$paOpportunity['projected'], '', ',', '¥').'</strong>';
          $sHTML .= '<br />'.$oHTML->getSeeMoreLink('+ see detail', 'sm_'.$pnPk).'<br />';

          $sSeeMoreContent = $oHTML->getText('Detail :<br />').$this->_formDetail($psStatus,$paOpportunity['details']);
          $sHTML .= $oHTML->getSeeMoreContent($sSeeMoreContent, 'sm_'.$pnPk, array('style' => 'margin-top:20px;'));

        $sHTML.= $oHTML->getBlocEnd();

        // Right button
        if($this->_rightManage || $this->_rightPay)
        {
          if((int)$paOpportunity['nb_paid'] > 0 && !$this->_rightPay)
          {
            $sPic = $this->getResourcePath().'/pictures/paid_24.png';
            $sLink = $oHTML->getPicture($sPic, 'Only admin can edit paid opportunity');
          }
          elseif((int)$paOpportunity['nb_paid'] > 0 && $this->_rightPay)
          {
            $sPic = $this->getResourcePath().'/pictures/paid_24.png';
            $sLink = $oHTML->getPicture($sPic, 'Totally or partially paid opportunity');

            $sPic = $this->getResourcePath().'/pictures/edit_danger_16.png';
            $sAjaxEdit = 'if(window.confirm(\'This opportunity has already been set as signed. Are you sure you want to change it ?\')){ '.$sAjaxEdit.'}';
            $sLink.= '&nbsp;&nbsp;'.$oHTML->getLink($oHTML->getPicture($sPic, 'Edit opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
          }
          elseif($psStatus == 'signed')
          {
            $sAjaxEdit = 'if(window.confirm(\'This opportunity has already been set as signed. Are you sure you want to change it ?\')){ '.$sAjaxEdit.'}';
            $sPic = $this->getResourcePath().'/pictures/edit_danger_16.png';
            $sLink = $oHTML->getLink($oHTML->getPicture($sPic, 'Edit opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
          }
          else
          {
            $sLink = $oHTML->getLink($oHTML->getPicture(CONST_PICTURE_EDIT, 'Edit opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
          }

          //can't delete opportunity paied. Admin can edit and set values to 0
          if((int)$paOpportunity['nb_paid'] == 0)
          {

            //now: if the user is opp admin (pay) or if the user manage his opportunities
            if($this->_rightPay || ($psStatus != 'signed' && $paOpportunity['created_by'] == $oLogin->getUserPk()))
            {
              //psRefusedAction, psConfirmedBtn, psRefusedBtn, psTitle, pnWidth, pnHeight
              $sURL = $oPage->getAjaxUrl('opportunity', CONST_ACTION_DELETE, CONST_OPPORTUNITY, $pnPk);
              $sTitle = addslashes(cutString($paOpportunity['title'], 20));

              $sAjaxEdit = 'goPopup.setPopupConfirm(\'Are you sure you want to delete this opportunity [ '.$sTitle.' ] ?\',
                \'AjaxRequest(\\\''.$sURL.'\\\'); \', \'\', \'\',\'\', \'Deleting an opportunity...\', 350, 175); ';

              $sLink.= ' '.$oHTML->getLink($oHTML->getPicture(CONST_PICTURE_DELETE, 'Edit opportunity'),'javascript:;', array('onclick' => $sAjaxEdit));
            }
          }

          $sHTML .= $oHTML->getBlocStart('', array('style' => 'margin:-10px 10px; float:right; height:20px;'));
          $sHTML .= $sLink;
          $sHTML .= $oHTML->getSpace(2);
          $sHTML .= $oHTML->getBlocEnd();
        }
        $sHTML.= $oHTML->getFloatHack();

    $sHTML.= $oHTML->getBlocEnd();

    return $sHTML;
  }

  private function _formDetail($psStatus, $paDetail, $psMonth = '')
  {
    if(!assert('is_string($psStatus)'))
      return '';

    if(!assert('is_string($psMonth)'))
      return '';

    if(!assert('is_array($paDetail)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $bShowMonth = (empty($psMonth));
    if($bShowMonth)
      $sClass = 'product';
    else
      $sClass = 'product_full';


    $sHTML = '';
    $sHTML.= $oHTML->getBlocStart('', array('class' => 'detail-row'));

      $nDetailPk = (int)$paDetail['opportunity_detailpk'];
      if($bShowMonth)
        $sHTML.= $oHTML->getBloc('', date('M-Y', strtotime($paDetail['month'])), array('class' => 'month'));

      $sHTML.= $oHTML->getBloc('', $paDetail['product'], array('class' => $sClass));
      $sHTML.= $oHTML->getBloc('', formatNumber((int)$paDetail['amount'], '', ',', '¥'), array('class' => 'amount'));

      if($psStatus != 'failed' && $this->_rightProductSupervisor)
      {
        $aButtons = array(
            'booked' => array('img' => CONST_PICTURE_BOOK, 'right' => $this->_rightProductSupervisor),
            'delivered' => array('img' => CONST_PICTURE_DELIVER, 'right' => $this->_rightProductSupervisor),
            'invoiced' => array('img' => CONST_PICTURE_INVOICE, 'right' => $this->_rightPay),
            'paid' => array('img' => CONST_PICTURE_PAY, 'right' => $this->_rightPay)
            );

        foreach($aButtons as $sStatus => $aStatus)
        {
          $sImg = $aStatus['img'];
          $bRight = $aStatus['right'];

          $sHTML .= $oHTML->getBlocStart();

            $sIdName = $sStatus.'_'.$nDetailPk;
            $sRightLabel = ($paDetail[$sStatus]==0) ? 'click to set '.$sStatus : 'click to remove '.$sStatus;
            $sLabel = ($paDetail[$sStatus]==0) ? 'non '.$sStatus : $sStatus;

            $sUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_FASTEDIT, '', $nDetailPk, array('status' => $sStatus));

            if($bRight)
            {
              $sOpacity = ($paDetail[$sStatus]==1) ? '1' : '0.4';
              $sHTML .= $oHTML->getLink($oHTML->getPicture($sImg, $sRightLabel, $sUrl, array('style' => 'opacity:'.$sOpacity.';', 'id'=> $sIdName)));
            }
            else
            {
              $sOpacity = ($paDetail[$sStatus]==1) ? '1' : '0.15';
              $sHTML .= $oHTML->getPicture($sImg, $sLabel, '', array('style' => 'opacity:'.$sOpacity.';', 'id'=> $sIdName));
            }

          $sHTML .= $oHTML->getBlocEnd();
        }
      }

    $sHTML .= $oHTML->getBlocEnd();
    return $sHTML;
  }

  public function getMonthlyStat($pnPk = 0, $psDateMin = null, $psDateMax = null, $paProducts = array())
  {
    if(!assert('is_integer($pnPk)'))
      return array();

    if($pnPk === -1)
      $pnPk = 1;

    $anResult = array('ongoing' => array(),'signed' => array(), 'stalled' => array(),'failed' => array(),'projected' => array(),'asAxis' => array());

    if($psDateMin == null)
      $psDateMin = date('Y-m-d', mktime(0, 0, 0, ((int)date('m'))-1, 1, date('Y')));

    if($psDateMax == null)
      $psDateMax = date('Y-m-d', mktime(0, 0, 0, ((int)date('m'))+1, 1, date('Y')));

    //initialize the array with 0 (having continuous charts now we can pickup dates)
    $sStartingYear = date('Y', strtotime($psDateMin));
    $sEndingYear = date('Y', strtotime($psDateMax));

    $sStartingMonth = date('m', strtotime($psDateMin));
    $sEndingMonth = date('m', strtotime($psDateMax));
    $nYearDiff = ($sEndingYear - $sStartingYear);
    if($nYearDiff > 0)
      $sDateFormat = 'M-y';
    else
      $sDateFormat = 'M';

    //initialize the result array with a value for every month to display on the chart
    for($nCount = 0; $nCount <= (($sEndingMonth - $sStartingMonth) + (12 * $nYearDiff)); $nCount++)
    {
      $sMonth = date($sDateFormat, mktime(0, 0, 0, ($sStartingMonth+$nCount), 1, $sStartingYear));

      $anResult['asAxis'][] = $sMonth;
      $anResult['ongoing'][$sMonth] = 0;
      $anResult['signed'][$sMonth] = 0;
      $anResult['projected'][$sMonth] = 0;
      $anResult['failed'][$sMonth] = 0;
      $anResult['stalled'][$sMonth] = 0;
    }

    $oDbResult = $this->_getModel()->getMonthlyOpportunityStats($psDateMin, $psDateMax, $pnPk, $paProducts);
    $bRead = $oDbResult->readFirst();



    while($bRead)
    {
      $sMonth = date($sDateFormat, strtotime($oDbResult->getFieldValue('month')));

      switch ($oDbResult->getFieldValue('status'))
      {
        case 'pitched' :
        case 'proposal' :
        case 'verbal_agreement' :
          $anResult['ongoing'][$sMonth] += $oDbResult->getFieldValue('total');
          break;
        case 'failed' :
          $anResult['failed'][$sMonth] += $oDbResult->getFieldValue('total');
          break;

        case 'stalled' :
          $anResult['stalled'][$sMonth] += $oDbResult->getFieldValue('total');
          break;

        default :
          $anResult[$oDbResult->getFieldValue('status')][$sMonth] += $oDbResult->getFieldValue('total');
          break;
      }
      $anResult['projected'][$sMonth] += $oDbResult->getFieldValue('projected');


      $bRead = $oDbResult->readNext();
    }

    return $anResult;
  }

  public function getMonthlyUsersStat($psDatemin = '', $psDatemax = '')
  {
    $anResult = array();

    if(empty($psDatemin) || empty($psDatemax))
    {
      $sDatemin =  date('Y-m-d', mktime(0, 0, 0, ((int)date('m')), 1, date('Y')));
      $sDatemax = date('Y-m-d', mktime(0, 0, 0, ((int)date('m'))+1, 1, date('Y')));
    }
    else
    {
      $sDatemin = $psDatemin;
      $sDatemax = $psDatemax;
    }

    $oDbResult = $this->_getModel()->getMonthlyOpportunityStats($sDatemin, $sDatemax);
    $anResult['asAxis'] = array();

    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $sUserName = $oDbResult->getFieldValue('firstname');
      if(!in_array($sUserName, $anResult['asAxis']))
        $anResult['asAxis'][] = $sUserName;

      if(!isset($anResult['ongoing'][$sUserName]))
        $anResult['ongoing'][$sUserName] = 0;

      if(!isset($anResult['signed'][$sUserName]))
        $anResult['signed'][$sUserName] = 0;

      if(!isset($anResult['projected'][$sUserName]))
        $anResult['projected'][$sUserName] = 0;

      if(!isset($anResult['failed'][$sUserName]))
        $anResult['failed'][$sUserName] = 0;

      if(!isset($anResult['stalled'][$sUserName]))
        $anResult['stalled'][$sUserName] = 0;

      switch ($oDbResult->getFieldValue('status'))
      {
        case 'pitched' :
        case 'proposal' :
        case 'verbal_agreement' :
          $anResult['ongoing'][$sUserName] += $oDbResult->getFieldValue('total');
          break;
        case 'failed' :
          case 'stalled' :
          $anResult['failed'][$sUserName] += $oDbResult->getFieldValue('total');
          break;

        case 'stalled' :
          $anResult['stalled'][$sUserName] += $oDbResult->getFieldValue('total');
          break;

        case 'signed' :
          $anResult['signed'][$sUserName] += $oDbResult->getFieldValue('total');
          break;
      }
      $anResult['projected'][$sUserName] += $oDbResult->getFieldValue('projected');
      $bRead = $oDbResult->readNext();
    }

    return $anResult;
  }

  public function listenerNotification($psUid, $psAction, $psType, $pnPk, $psActionToDo)
  {
    $avCpValues = array(CONST_CP_UID => $psUid, CONST_CP_ACTION => $psAction, CONST_CP_TYPE => $psType, CONST_CP_PK => $pnPk);

    if(!assert('is_CpValues($avCpValues)'))
      return false;

    switch($psActionToDo)
    {
      case CONST_ACTION_DELETE :
        $this->_getModel()->deleteFromCpValues($avCpValues);
        break;
    }
    return true;
  }

  private function _deleteOpportunity($pnOpportunnityPk)
  {
    if(!assert('is_key($pnOpportunnityPk)'))
      return array();

    $oDbResult = $this->_getModel()->getByPk($pnOpportunnityPk, 'opportunity');
    if(!$oDbResult)
      return array('error' => 'Could not find the opportunity.');


    if(!$this->_getModel()->deleteOpportunityByPk($pnOpportunnityPk))
      return array('error' => 'Could not delete the opportunity.');

    return array('notice' => 'Opportunity deleted.', 'reload' => 1);
  }
}
