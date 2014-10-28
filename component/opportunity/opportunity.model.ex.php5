<?php

require_once('common/lib/model.class.php5');

class COpportunityModelEx extends COpportunityModel
{
  private $_userFk = 0;
  private $_userName = '';
  protected $csComponent = 'opportunity';
  public $aProductStatus = array('booked', 'delivered', 'paid', 'invoiced');

  public function __construct()
  {
    parent::__construct();

    $oLogin = CDependency::getCpLogin();
    if($oLogin->isLogged())
    {
      $this->_userFk = $oLogin->getUserPk();
      $this->_userName = $oLogin->getCurrentUserName();
    }
    return true;
  }


  // ================================================================
  // Redifining methods
  // ================================================================

  protected function _testFields($avFields, $psTablename, $pbAllFieldRequired = true, $pbAllowExtra = true, $psAction = 'add')
  {
    $this->casError = array();

    if($psTablename == 'opportunity')
    {
      if($psAction == 'add')
      {
        $avFields['date_added'] = date('Y-m-d');
        $avFields['opportunitypk'] = null;
      }

      return parent::_testFields($avFields, $psTablename, $pbAllFieldRequired, $pbAllowExtra);
    }

    return parent::_testFields($avFields, $psTablename, $pbAllFieldRequired, $pbAllowExtra);
  }

  public function getStatus()
  {
    // TODO: use a manageable list for that
    $asStatus = array();
    $asStatus['pitched']= array('label' => 'Pitched', 'value' => 'pitched', 'probability' => 15, 'shortname' => 'pitched');
    $asStatus['proposal']= array('label' => 'Proposal', 'value' => 'proposal', 'probability' => 30, 'shortname' => 'proposal');
    $asStatus['verbal_agreement']= array('label' => 'Verbal Agreement', 'value' => 'verbal_agreement', 'probability' => 80, 'shortname' => 'agreement');
    $asStatus['signed']= array('label' => 'Signed', 'value' => 'signed', 'probability' => 100, 'shortname' => 'signed');
    $asStatus['stalled']= array('label' => 'Stalled', 'value' => 'stalled', 'probability' => 5, 'shortname' => 'stalled');
    $asStatus['failed']= array('label' => 'Failed', 'value' => 'failed', 'probability' => 0, 'shortname' => 'failed');

    return $asStatus;
  }

  public function getIssueByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getIssue');

    $oDbResult = $this->getDetailByOpportunityFk($pnPk);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return '';

    return date('F Y',strtotime($oDbResult->getFieldValue('month')));
  }

  public function getTotalProjectedByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getTotalAmountByPk');

    $nTotal = $this->getTotalAmountByPk($pnPk);
    $nProbability = $this->getByPk($pnPk, 'opportunity')->getFieldValue('probability');

    return (int)$nTotal*$nProbability/100;
  }

  public function getTotalAmountByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getTotalAmountByPk');

    $sQuery = 'SELECT SUM(amount) as total_amount FROM opportunity_detail WHERE opportunityfk ='.$pnPk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return 0;

    return (int)$oDbResult->getFieldValue('total_amount');
  }

  public function getLinkByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getLinkByPk');

    $sQuery = 'SELECT * FROM opportunity_link WHERE opportunityfk ='.$pnPk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  public function getDetailByOpportunityFk($pnFk)
  {
    if(!assert('is_key($pnFk)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getDetailByOpportunityFk');

    $sQuery = 'SELECT * FROM opportunity_detail WHERE opportunityfk ='.$pnFk;
    $sQuery .= ' ORDER BY month';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  public function getOpportunitiesCountByLink($asValues)
  {
    if(!assert('is_array($asValues)'))
      return array('error' => __LINE__.' - Wrong Values assigned to getOpportunitiesCount');

    $sQuery = 'SELECT COUNT(*) as total, op.status FROM opportunity op ';
    $sQuery .= ' JOIN opportunity_link opl (ON opl.opportunityfk = op.opportunitypk )';
    $sQuery .= ' WHERE opl.cp_uid='.$this->oDB->dbEscapeString($asValues[CONST_CP_UID]);
    $sQuery .= ' AND opl.cp_action='.$this->oDB->dbEscapeString($asValues[CONST_CP_ACTION]);
    $sQuery .= ' AND opl.cp_type='.$this->oDB->dbEscapeString($asValues[CONST_CP_TYPE]);
    $sQuery .= ' AND opl.cp_pk='.$this->oDB->dbEscapeString($asValues[CONST_CP_PK]);
    $sQuery .= ' GROUP BY op.status';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
       return new CDbResult();

    return $oDbResult;
  }

  public function getDetailedOpportunitiesByLink($asValues)
  {
    if(!assert('is_array($asValues)'))
      return array('error' => __LINE__.' - Wrong Values assigned to _insert');

    $sQuery = 'SELECT DISTINCT(opd.opportunity_detailpk), sl.fullname, op.*, opd.*, opl.* ';
    $sQuery .= ' , (SELECT SUM(opd.amount) FROM opportunity_detail opd WHERE opd.opportunityfk=opl.opportunityfk) as total ';
    $sQuery .= ' , (SELECT count(opportunity_detailpk) FROM opportunity_detail as opd WHERE opd.opportunityfk=opl.opportunityfk) as overall_nb_products ';
    $sQuery .= ' , (SELECT (SUM(opd.amount)*op.probability/100) FROM opportunity_detail opd WHERE opd.opportunityfk=opl.opportunityfk) as projected ';

    $sQuery .= ' FROM opportunity op';
    $sQuery .= ' LEFT JOIN opportunity_link opl ON opl.opportunityfk = op.opportunitypk';
    $sQuery .= ' LEFT JOIN opportunity_detail opd ON opd.opportunityfk = opl.opportunityfk';
    $sQuery .= ' LEFT JOIN shared_login sl ON op.loginfk = sl.loginpk';
    $sQuery .= ' WHERE opl.cp_uid='.$this->oDB->dbEscapeString($asValues[CONST_CP_UID]);
    $sQuery .= ' AND opl.cp_action='.$this->oDB->dbEscapeString($asValues[CONST_CP_ACTION]);
    $sQuery .= ' AND opl.cp_type='.$this->oDB->dbEscapeString($asValues[CONST_CP_TYPE]);
    $sQuery .= ' AND opl.cp_pk='.$this->oDB->dbEscapeString($asValues[CONST_CP_PK]);

    $sQuery .= ' ORDER BY op.date_update DESC, op.date_added DESC';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    return $this->_formatOpportunities($oDbResult);
  }

  private function _formatOpportunities($poDbResult)
  {
    if(!assert('is_object($poDbResult)'))
      return array();

    $bRead = $poDbResult->readFirst();
    if(!$bRead)
       return array();

     $aOutput = array();
     while($bRead)
     {
       $nOpportunityPk = (int)$poDbResult->getFieldValue('opportunitypk');
       $sMonth = $poDbResult->getFieldValue('month');
       $nPaid = (int)$poDbResult->getFieldValue('paid');

       if(!isset($aOutput[$nOpportunityPk]))
       {
         $aOutput[$nOpportunityPk] =
         array(
          'opportunitypk' => $nOpportunityPk,
          'title' => $poDbResult->getFieldValue('title'),
          'total' => $poDbResult->getFieldValue('total'),
          'created_by' => $poDbResult->getFieldValue('fullname'),
          'projected' => $poDbResult->getFieldValue('projected'),
          'date_added' => $poDbResult->getFieldValue('date_added'),
          'description' => $poDbResult->getFieldValue('description'),
          'status' => $poDbResult->getFieldValue('status'),
          'probability' => $poDbResult->getFieldValue('probability'),
          'issue' => $sMonth,
          'nb_paid' => 0,
          'nb_products' => 0,
          'overall_nb_products' => $poDbResult->getFieldValue('overall_nb_products'),
          'cp_uid' => $poDbResult->getFieldValue('cp_uid'),
          'cp_action' => $poDbResult->getFieldValue('cp_action'),
          'cp_type' => $poDbResult->getFieldValue('cp_type'),
          'cp_pk' => $poDbResult->getFieldValue('cp_pk'),
          'addressbook_contactpk' => $poDbResult->getFieldValue('addressbook_contactpk'),
          'addressbook_companypk' => $poDbResult->getFieldValue('addressbook_companypk'),
          'contact_name' => $poDbResult->getFieldValue('firstname').' '.$poDbResult->getFieldValue('lastname'),
          'company_name' => $poDbResult->getFieldValue('company_name'),
          'loginfk' => $poDbResult->getFieldValue('loginfk'),
          'date_last_action' => $poDbResult->getFieldValue('date_last_action')
        );
       }

       $aOutput[$nOpportunityPk]['details'][]=
        array(
          'amount' => $poDbResult->getFieldValue('amount'),
          'month' => $sMonth,
          'paid' => $nPaid,
          'booked' => $poDbResult->getFieldValue('booked'),
          'product' => $poDbResult->getFieldValue('product'),
          'delivered' => $poDbResult->getFieldValue('delivered'),
          'invoiced' => $poDbResult->getFieldValue('invoiced'),
          'opportunity_detailpk' => (int)$poDbResult->getFieldValue('opportunity_detailpk'),
          'date_last_action' => 0
        );

       if(!isset($aOutput[$nOpportunityPk]['months'][$sMonth]))
        $aOutput[$nOpportunityPk]['months'][$sMonth] = array('amount' => 0, 'nb_products' => 0, 'nb_paid' => 0);

       $aOutput[$nOpportunityPk]['months'][$sMonth]['amount']+=$poDbResult->getFieldValue('amount');
       $aOutput[$nOpportunityPk]['months'][$sMonth]['nb_products']++;
       $aOutput[$nOpportunityPk]['months'][$sMonth]['nb_paid'] += $nPaid;

       $aOutput[$nOpportunityPk]['nb_paid'] += $nPaid;
       $aOutput[$nOpportunityPk]['nb_products']++;

       $bRead = $poDbResult->readNext();
     }

    return $aOutput;
  }

  public function getOpportunitiesByLink($asValues, $sStatus = '')
  {
    if(!assert('is_array($asValues)'))
      return array('error' => __LINE__.' - Wrong Values assigned to _insert');

    $sQuery = 'SELECT * FROM opportunity op ';
    $sQuery .= ' LEFT JOIN opportunity_link opl ON opl.opportunityfk = op.opportunitypk';
    $sQuery .= ' WHERE opl.cp_uid='.$this->oDB->dbEscapeString($asValues[CONST_CP_UID]);
    $sQuery .= ' AND opl.cp_action='.$this->oDB->dbEscapeString($asValues[CONST_CP_ACTION]);
    $sQuery .= ' AND opl.cp_type='.$this->oDB->dbEscapeString($asValues[CONST_CP_TYPE]);
    $sQuery .= ' AND opl.cp_pk='.$this->oDB->dbEscapeString($asValues[CONST_CP_PK]);

    if(!empty($sStatus))
      $sQuery .= ' AND op.status='.$this->oDB->dbEscapeString($sStatus);

    $sQuery .= ' ORDER BY op.status, op.date_added';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
       return new CDbResult();

    return $oDbResult;
  }

  public function getOpportunityByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => __LINE__.' - Wrong parameter assigned to getOpportunityByPk');

    $oAB = CDependency::getComponentByName('addressbook');
    $asJoin = $oAB->getSharedSQL('opp_list');
    set_array($asJoin['select'], '', ', ');

    $sQuery = 'SELECT '.$asJoin['select'].' opd.*, op.*, opl.* FROM opportunity as op';
    $sQuery.= ' LEFT JOIN opportunity_link as opl ON (opl.opportunityfk = op.opportunitypk)';
    $sQuery.= ' LEFT JOIN opportunity_detail opd ON (opd.opportunityfk = op.opportunitypk)';
    $sQuery.= $asJoin['join'];
    $sQuery.= ' WHERE opportunitypk='.$pnPk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $oDbResult->readFirst();

    return $oDbResult;
  }

  public function addOpportunity($asValues)
  {
    if(!assert('is_array($asValues)'))
      return array('error' => __LINE__.' - Wrong Values assigned to addOpportunity');

    if(!$this->_testFields($asValues, 'opportunity'))
      return 0;

    $sQuery= 'INSERT INTO `opportunity` (`loginfk` ,`title` , `description` , `date_added` ,`probability` ,`status`)';
    $sQuery.= ' VALUES ('.$this->oDB->dbEscapeString($asValues['loginfk']).', '.$this->oDB->dbEscapeString($asValues['title']);
    $sQuery.= ', '.$this->oDB->dbEscapeString($asValues['description']).', NOW(), '.$asValues['probability'].', '.$this->oDB->dbEscapeString($asValues['status']).');';
    $oDBResult = $this->oDB->ExecuteQuery($sQuery);

    $pnPk = (int)$oDBResult->getFieldValue('pk');
    $this->_addHistory(array('action' => 'Creation', 'comment' => 'Opportunity added by '.$this->_userName), $pnPk);

    $this->_logChanges($asValues, 'opportunity', 'add opportunity');

    return $pnPk;
  }

  public function updateOpportunity($asValues)
  {
    if(!assert('is_array($asValues)'))
      return array('error' => __LINE__.' - Wrong Values assigned to updateOpportunity');

    if(!$this->_testFields($asValues, 'opportunity'))
      return 0;

    $bUpdated = $this->update($asValues, 'opportunity');

    if($bUpdated)
    {
      $this->_logChanges($asValues, 'opportunity', 'update opportunity');
      $this->_addHistory(array('action' => 'Edition', 'comment' => 'Opportunity edited by '.$this->_userName), $asValues['opportunitypk']);
      return $asValues['opportunitypk'];
    }

    return 0;
  }

  public function switchProductStatus($pnPk, $psStatus)
  {
    if(!assert('in_array($psStatus, $this->aProductStatus)'))
      return -1;

    if(!assert('is_key($pnPk)'))
      return -1;

    $oDetail = $this->getByPk($pnPk, 'opportunity_detail');

    $nNewValue = ((int)$oDetail->getFieldValue($psStatus)==0) ? 1 : 0;
    $sLabel = ($oDetail->getFieldValue($psStatus)==0) ? 'not '.$psStatus : $psStatus;

    $sQuery= 'UPDATE `opportunity_detail` SET '.$psStatus.'='.$nNewValue.' WHERE `opportunity_detailpk`='.$pnPk;
    $oDBResult = $this->oDB->ExecuteQuery($sQuery);

    if($oDBResult)
    {
      $this->_logChanges($pnPk, 'opportunity_detail', 'opportunity product status set '.$sLabel);

      $this->_addHistory(array('action' => 'Product update', 'comment' => 'Product \''.$oDetail->getFieldValue('product').'\' on '.$oDetail->getFieldValue('month').' set to '.$sLabel.' by '.$this->_userName), (int)$oDetail->getFieldValue('opportunityfk'));
      return $nNewValue;
    }

    return -1;
  }

  public function addDetail($avValues, $pnFk)
  {
    if(!assert('is_key($pnFk)'))
      return 0;

    if(!assert('is_array($avValues[0])'))
      return 0;

    $sQuery= 'INSERT INTO `opportunity_detail`
              (`opportunity_detailpk` ,`opportunityfk` ,`product` , `month` , `amount` ,`delivered` ,`paid`)
               VALUES (';

    $bFirst = true;
    foreach($avValues as $avRow)
    {
      if(!$bFirst)
        $sQuery.=', (';

      $avRow['amount'] = str_replace(',', '', $avRow['amount']);
      $avRow['amount'] = str_replace(' ', '', $avRow['amount']);

      $sQuery.='NULL, '.$pnFk.', '.$this->oDB->dbEscapeString($avRow['product']);
      $sQuery.=', '.$this->oDB->dbEscapeString($avRow['month']).', '.$this->oDB->dbEscapeString($avRow['amount']).', false, false)';

      $bFirst = false;
    }

    $oDBResult = $this->oDB->ExecuteQuery($sQuery);

    if($oDBResult->getFieldValue('pk'))
      return (int)$oDBResult->getFieldValue('pk');

    return 0;
  }

  public function addLink($asValues, $pnFk)
  {
    if(!assert('is_key($pnFk)'))
      return 0;

    if(!assert('is_cpValues($asValues)'))
      return 0;

    $sQuery= 'INSERT INTO `opportunity_link` (`opportunity_linkpk` ,`opportunityfk` ,`cp_uid` , `cp_action` , `cp_type` ,`cp_pk`)';
    $sQuery.= ' VALUES (NULL, '.$pnFk.', '.$this->oDB->dbEscapeString($asValues[CONST_CP_UID]);
    $sQuery.= ', '.$this->oDB->dbEscapeString($asValues[CONST_CP_ACTION]).', '.$this->oDB->dbEscapeString($asValues[CONST_CP_TYPE]);
    $sQuery.= ', '.$this->oDB->dbEscapeString($asValues[CONST_CP_PK]).');';

    $oDBResult = $this->oDB->ExecuteQuery($sQuery);
    if(!$oDBResult)
      return 0;

    return (int)$oDBResult->getFieldValue('pk');
  }

  public function getMonthlyOpportunityStats($pdDatemin, $pdDatemax, $pnPk=0, $paProducts = array())
  {
    if(!assert('is_integer($pnPk) && $pnPk>=0'))
      return array('error' => __LINE__.' - Wrong Pk assigned to getMonthlyOpportunityStats');

    if(!assert('is_date($pdDatemin) && is_date($pdDatemax)'))
      return array('error' => __LINE__.' - Wrong date values assigned to getMonthlyOpportunityStats');

    if(!assert('is_array($paProducts)'))
      return array('error' => 'Wrong products assigned to getMonthlyOpportunityStats');

    $sQuery = 'SELECT SUM(opd.amount) as total,
                      SUM(opd.amount*op.probability/100) as projected,
                      op.status, opd.month, op.loginfk, l.firstname
               FROM `opportunity_detail` opd
                INNER JOIN `opportunity` op ON opd.opportunityfk=op.opportunitypk
                INNER JOIN `login` l ON op.loginfk=l.loginpk
                  WHERE opd.month >='.$this->oDB->dbEscapeString($pdDatemin).'
                  AND opd.month <= '.$this->oDB->dbEscapeString($pdDatemax);

    if(!empty($paProducts))
      $sQuery .= ' AND opd.product IN (\''.implode('\',\'', $paProducts).'\')';

    if($pnPk!=0)
      $sQuery .= ' AND op.loginfk='.$pnPk;

    $sQuery .= ' GROUP BY op.status, opd.month';

    if($pnPk==0)
      $sQuery .= ', op.loginfk';

    $sQuery .= ' ORDER BY opd.month ASC';

    $oDBResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDBResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDBResult;
  }

  private function _addHistory($asValues, $pnFk)
  {
    if(!assert('is_key($pnFk)'))
      return array('error' => __LINE__.' - Wrong Fk assigned to _addHistory');

    if(!assert('is_array($asValues)'))
      return array('error' => __LINE__.' - Wrong Values assigned to _addHistory');

    $sQuery= 'INSERT INTO `opportunity_history` (`opportunity_historypk` ,`opportunityfk` ,`date_added` , `userfk` , `action` ,`comment`)';
    $sQuery.= ' VALUES (NULL, '.$pnFk.', NOW(), '.$this->_userFk.', '.$this->oDB->dbEscapeString($asValues['action']);
    $sQuery.= ', '.$this->oDB->dbEscapeString($asValues['comment']).');';

    $oDBResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDBResult->readFirst();

    if(!$bRead)
      return 0;

    return (int)$oDBResult->getFieldValue('pk');
  }

  public function getCountFromCpValues($asValues)
  {
    if(!assert('is_cpValues($asValues)'))
     return 0;

    $sQuery = 'SELECT count(*) as nCount FROM `opportunity_link`';
    $sQuery.= ' WHERE cp_uid ='.$this->oDB->dbEscapeString($asValues[CONST_CP_UID]);
    $sQuery.= ' AND cp_action ='.$this->oDB->dbEscapeString($asValues[CONST_CP_ACTION]);
    $sQuery.= ' AND cp_type='.$this->oDB->dbEscapeString($asValues[CONST_CP_TYPE]).' AND cp_pk='.$asValues[CONST_CP_PK];

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return 0;

    return $oResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);
  }

  public function deleteFromCpValues($pasValues)
  {
    if(!assert('is_cpValues($pasValues)'))
     return false;

    $oDbResult = $this->getOpportunitiesByLink($pasValues);
    $bRead = $oDbResult->readFirst();

    while($bRead)
    {
      $nFk = (int)$oDbResult->getFieldValue('opportunitypk');
      $this->deleteOpportunityByPk($nFk);

      $bRead = $oDbResult->readNext();
    }

    return true;
  }

  public function deleteOpportunityByPk($pnPk)
  {
    if(!assert('is_key($pnPk)'))
     return false;

    $bError = false;

    if(!$this->deleteByFk($pnPk, 'opportunity_detail', 'opportunity'))
      $bError = true;

    if(!$this->deleteByFk($pnPk, 'opportunity_history', 'opportunity'))
      $bError = true;

    if(!$this->deleteByFk($pnPk, 'opportunity_link', 'opportunity'))
      $bError = true;

    if(!$this->deleteByPk($pnPk, 'opportunity'))
      $bError = true;

    return !$bError;
  }

  public function getOpportunityByDetailPk($pnOpportunityDetail)
  {
    if(!assert('is_key($pnOpportunityDetail)'))
      return array('error' => __LINE__.' - Wrong pk values assigned to getOpportunityByDetailPk');

    $oAB = CDependency::getComponentByName('addressbook');
    $asJoin = $oAB->getSharedSQL('opp_list');
    set_array($asJoin['select'], '', ', ');

    $sQuery = 'SELECT '.$asJoin['select'].' sl.fullname, op.*, opd.*, opl.* ';
    $sQuery .= ' , (SELECT SUM(opd.amount) FROM opportunity_detail opd WHERE opd.opportunityfk=opl.opportunityfk) as total
                  FROM `opportunity` op
                    LEFT JOIN `opportunity_detail` opd ON (opd.opportunityfk = op.opportunitypk AND opd.opportunity_detailpk='.$pnOpportunityDetail.')
                    LEFT JOIN `shared_login` sl ON op.loginfk = sl.loginpk
                    INNER JOIN `opportunity_link` opl ON opl.opportunityfk = op.opportunitypk
                    ';

    $sQuery .= $asJoin['join'];

    $sQuery .= ' WHERE opd.opportunity_detailpk='.$pnOpportunityDetail;
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    return $this->_formatOpportunities($oDbResult);
  }

  public function getOpportunitiesByUserPk($pnPk = 0, $pdDatemin = '', $pdDatemax = '', $psOrder = '', $paStatus = array(), $paProducts = array())
  {
    if(!assert('is_integer($pnPk)'))
      return array('error' => __LINE__.' - Wrong pk values assigned to getOpportunitiesByUserPk');

    if(!assert('is_date($pdDatemin) && is_date($pdDatemax)'))
      return array('error' => __LINE__.' - Wrong date values assigned to getOpportunitiesByUserPk');

    if(!assert('is_string($psOrder)'))
      return array();

    if(!assert('is_array($paStatus)'))
      return array();

    if(!assert('is_array($paProducts)'))
      return array();

    $oAB = CDependency::getComponentByName('addressbook');
    $asJoin = $oAB->getSharedSQL('opp_list');
    set_array($asJoin['select'], '', ', ');

    $sQuery = 'SELECT DISTINCT(opd.opportunity_detailpk) as uniqPk, '.$asJoin['select'].' sl.fullname, op.*, opd.*, opl.*
      , UNIX_TIMESTAMP(IF(op.date_update > op.date_added, op.date_update, op.date_added)) as date_last_action
      , (SELECT SUM(opd.amount) FROM opportunity_detail opd WHERE opd.opportunityfk=opl.opportunityfk) as total
      , (SELECT count(opportunity_detailpk) FROM opportunity_detail as opd WHERE opd.opportunityfk=opl.opportunityfk) as overall_nb_products
      , (SELECT (SUM(opd.amount)*op.probability/100) FROM opportunity_detail opd WHERE opd.opportunityfk=opl.opportunityfk) as projected
      , IF(op.date_update > op.date_added, op.date_update, op.date_added) as order_date

      FROM `opportunity` op
      LEFT JOIN `opportunity_detail` opd ON opd.opportunityfk = op.opportunitypk
      LEFT JOIN `shared_login` sl ON op.loginfk = sl.loginpk
      INNER JOIN `opportunity_link` opl ON opl.opportunityfk = op.opportunitypk ';

    $sQuery .= $asJoin['join'];

    $sQuery .= ' WHERE opd.month >= '.$this->oDB->dbEscapeString($pdDatemin).'
                  AND opd.month <= '.$this->oDB->dbEscapeString($pdDatemax);

    if(!empty($paStatus))
    {
      $sQuery .= ' AND (';
      $nCount = 0;
      foreach($paStatus as $sStatus)
      {
        if($nCount > 0)
          $sQuery .= ' OR ';

        $sQuery .= 'op.status=\''.$sStatus.'\'';
        $nCount++;
      }
      $sQuery .= ')';
    }

    if(!empty($paProducts))
      $sQuery .= ' AND opd.product IN (\''.implode('\',\'', $paProducts).'\')';

    if(!empty($pnPk))
      $sQuery .= ' AND op.loginfk='.$pnPk;

    //need a group ?
    //$sQuery .= ' GROUP BY  opd.month';

    if(!empty($psOrder))
      $sQuery .= ' ORDER BY '.$psOrder;
    else
      $sQuery .= ' ORDER BY order_date DESC';

    //dev_dump($sQuery);

    $oDBResult = $this->oDB->ExecuteQuery($sQuery);
    return $this->_formatOpportunities($oDBResult);
  }

}
