<?php

/*
 * Check if there are rights that are link to nothing
 * SELECT *
    FROM `right` AS r
    LEFT JOIN right_tree AS rt ON ( rt.rightfk = r.rightpk )
    WHERE type = 'alias'
    HAVING rt.rightfk IS NULL
 *
 ** Check if there are rights in the tree that doesn't exist
 *  SELECT *
    FROM right_tree as rt
    LEFT JOIN `right` AS r  ON ( r.rightpk = rt.rightfk )
    HAVING r.rightpk IS NULL
 */

require_once('component/right/right.class.php5');

class CRightEx extends CRight
{
  private $casUserRights = array();
  private $casUserDataRights = array();
  private $cnUserPk = 0;
  private $cbIsAdmin = false;

  public function __construct()
  {
    $this->initializeRights();
  }

  public function initializeRights($pbRefresh = false)
  {
    $oLogin = CDependency::getCpLogin();
    $this->cnUserPk = $oLogin->getUserPk();
    $this->cbIsAdmin = $oLogin->isAdmin();

    $bRefreshRights = ($pbRefresh || (bool)getValue('refresh_right', 0));

    if(!$bRefreshRights && isset($_SESSION['user_rights']) && !empty($_SESSION['user_rights']))
    {
      $this->casUserRights = $_SESSION['user_rights'];
      $this->casUserDataRights = $_SESSION['user_data_rights'];
    }
    else
      $this->loadUserRights();

    //dump($this->casUserDataRights);

    if($bRefreshRights)
    {
      //dump('refreshed: '.count($_SESSION['user_rights']).' rights');
      /*dump($_SESSION['user_rights']);
      dump($_SESSION['user_data_rights']);*/
    }

    /*if(getValue('convert-right'))
    {
      $oDbResult = $this->_getModel()->getByWhere('right', ' type="data" ');
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {

        $asData = $oDbResult->getData();
        dump('Looking at right  #'.$asData['rightpk']);

        $asRight = unserialize($asData['data']);
        dump('Is unserialized properly ? '.(int)is_array($asRight));
        //dump($asRight);

        if(!empty($asRight))
        {
          dump('converting #'.$asData['rightpk'].' in json');
          $this->_getModel()->executeQuery('UPDATE `right` SET data = \''.json_encode($asRight).'\' WHERE rightpk = '.$asData['rightpk']);
        }

        $bRead = $oDbResult->readNext();
      }
    }*/
    return true;
  }



  /* ******************************************************************* */
  /* ******************************************************************* */
  /* ******************************************************************* */
  //accessors

  public function getUserDataRights()
  {
    return $this->casUserDataRights;
  }


  /* ******************************************************************* */
  /* ******************************************************************* */
  /* ******************************************************************* */
  //Component interfaces



  /* ******************************************************************* */
  /* ******************************************************************* */
  /* ******************************************************************* */
  //Private methods


  private function _saveRights($pnItemPk, $panRight, $pbIsGroup = false)
  {
    if(!assert('is_key($pnItemPk) && is_array($panRight) && is_bool($pbIsGroup)'))
      return false;

    if(empty($panRight))
      return true;

    if(!assert('is_arrayOfInt($panRight)'))
      return false;

    $oDB = CDependency::getComponentByName('database');


    //create the insert query: 1 query for all rights
    $asInsert = array();
    foreach($panRight as $nRightfk)
    {
      $asInsert[] = '('.$nRightfk.', '.$pnItemPk.')';
    }

    if($pbIsGroup)
      $sQuery = 'DELETE FROM right_user WHERE groupfk = '.$pnItemPk;
    else
      $sQuery = 'DELETE FROM right_user WHERE loginfk = '.$pnItemPk;

    $oDbResult = $oDB->executeQuery($sQuery);
    if(!$oDbResult)
    {
      assert('false; //can not delete rights.');
      return false;
    }

    if($pbIsGroup)
      $sQuery = 'INSERT INTO right_user (rightfk, groupfk) VALUES '.implode(',', $asInsert);
    else
      $sQuery = 'INSERT INTO right_user (rightfk, loginfk) VALUES '.implode(',', $asInsert);

    $oDbResult = $oDB->executeQuery($sQuery);
    if(!$oDbResult)
    {
      assert('false; //can not save rights.');
      return false;
    }

    return true;
  }




  /* ******************************************************************* */
  /* ******************************************************************* */
  /* ******************************************************************* */
  //Public methods


  /**
   * Load all the user right from the database. If it's allready in session, we just reuse it.
   *
   * @param type $pnUserfk
   * @return boolean
   */
  public function loadUserRights($pnUserfk = 0)
  {
    if(!assert('is_integer($pnUserfk)'))
      return false;

    if(empty($pnUserfk))
      $nUserPk = $this->cnUserPk;
    else
      $nUserPk = $pnUserfk;

    //fetch user rights (LEFT join to catcup static ones)
    $oDB = CDependency::getComponentByName('database');
    if($this->cbIsAdmin)
      $sQuery = 'SELECT *, "'.$nUserPk.'" as loginfk  FROM  `right` as r ';
    else
    {
      $sQuery = 'SELECT * FROM  `right` as r ';
      //$sQuery.= ' LEFT JOIN right_user as ru  ON (ru.rightfk = r.rightpk AND loginfk = '.$nUserPk.') ';
      //$sQuery.= ' WHERE loginfk = '.$nUserPk.' ';

      //if user is logged, I add in the query all the rights for logged users
      if($nUserPk > 0)
      {
        $asUserData = $_SESSION['userData'];
        $anGroup = array();

        if(!empty($asUserData['group']))
        {
          $anGroup = array_keys($asUserData['group']);
        }

        if(isset($asUserData['login_grouppk']) && !empty($asUserData['login_grouppk']))
          $anGroup[] = $asUserData['login_grouppk'];

        if(!empty($anGroup))
        {
          $sSql = '(ru.loginfk = '.$nUserPk.' OR ru.groupfk IN('.implode(',', $anGroup).') )';
        }
        else
          $sSql = 'ru.loginfk = '.$nUserPk;

        $sQuery.= ' LEFT JOIN right_user as ru  ON (ru.rightfk = r.rightpk AND '.$sSql.')';
        $sQuery.= ' WHERE r.type IN ("logged", "static") OR (r.type IN ("right", "data") AND ';

        $sQuery.= $sSql;

        $sQuery.= ') ';
      }
      else
      {
        //global data right are created linking the right to login -1
        $sQuery.= ' LEFT JOIN right_user as ru  ON (ru.rightfk = r.rightpk AND loginfk = -1) ';
        $sQuery.= ' WHERE r.type IN ("static") OR (r.type = "data" AND loginfk = -1)';
      }
    }

    //dump($sQuery);
    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    //nothing? we've got a problem here
    if(!$bRead)
    {
      $_SESSION['user_rights'] = array();
      return false;
    }


    // Store the "app" rights in an array for later use (add childs, and formating)
    // and save the dataRights in a specific attribute
    $this->casUserDataRights = array();
    $asRight = array();
    while($bRead)
    {
      $nRightPk = (int)$oDbResult->getFieldValue('rightpk');
      //dump('Rightpk: '.$nRightPk.' / type: '.$oDbResult->getFieldValue('type'));

      if($oDbResult->getFieldValue('type') == 'data')
      {

        $asRightDetail = (array)json_decode($oDbResult->getFieldValue('data'));

        if(!assert('!empty($asRightDetail["table"]) /*'.$oDbResult->getFieldValue('rightpk').'*/'))
        {
          return false;
        }

        $asRightDetail['cp_uid'] = $oDbResult->getFieldValue('cp_uid');
        $asRightDetail['cp_action'] = $oDbResult->getFieldValue('cp_action');
        $asRightDetail['cp_type'] = $oDbResult->getFieldValue('cp_type');

        //echo '<pre> db right: '; dump($asRightDetail); echo '</pre>';
        if(isset($this->casUserDataRights[$asRightDetail['table']]))
          //$this->casUserDataRights[$asRightDetail['table']] = array_merge_recursive($this->casUserDataRights[$asRightDetail['table']], $asRightDetail);
          $this->casUserDataRights[$asRightDetail['table']] = array_clean_merge($this->casUserDataRights[$asRightDetail['table']], $asRightDetail);
        else
        {
          $this->casUserDataRights[$asRightDetail['table']] = $asRightDetail;
        }


         //echo '<pre> result'; dump($this->casUserDataRights); echo '</pre><hr />';
      }
      else
      {
        $asRight[$nRightPk]['data'] = $oDbResult->getData();
        $anRight[] = $nRightPk;
      }



      $bRead = $oDbResult->readNext();
    }

    /*dump('--->');
    dump('user db rights');
    dump($asRight);*/

    //get the full list of childs from user rights (recursive, multi level)
    $asChildRight = $this->getChildRights(implode(',', $anRight), array(), true);
    /*dump('___>');
    dump('list of children');
    dump($asChildRight);*/

    $asRight = array_replace_recursive($asRight, $asChildRight);

    /*dump('===>');
    dump('user rights, including child');
    dump($asRight);*/

    foreach($asRight as $nRightPk => $asRight)
    {
      $asRightData = $asRight['data'];

      //check if user has rights to access the page or if there's a static rule
      if($asRightData['type'] == 'static' || !empty($asRightData['rightpk']))
      {
        if(empty($asRightData[CONST_CP_ACTION]))
          $asRightData[CONST_CP_ACTION] = '*';

        if(empty($asRightData[CONST_CP_TYPE]))
          $asRightData[CONST_CP_TYPE] = '*';

        if(empty($asRightData[CONST_CP_PK]))
          $asRightData[CONST_CP_PK] = '*';

        if(!empty($asRightData['callback']))
        {
          $asParams = unserialize($asRightData['callback_params']);
          $this->casUserRights[$asRightData[CONST_CP_UID]][$asRightData[CONST_CP_ACTION]][$asRightData[CONST_CP_TYPE]][$asRightData[CONST_CP_PK]] = array('callback' => $asRightData['callback'], 'callback_params' => $asParams);
        }
        else
          $this->casUserRights[$asRightData[CONST_CP_UID]][$asRightData[CONST_CP_ACTION]][$asRightData[CONST_CP_TYPE]][$asRightData[CONST_CP_PK]] = $asRightData['rightpk'];
      }
    }

    if(empty($this->casUserRights))
    {
      $_SESSION['user_rights'] = array();
      unset($_SESSION);
      return false;
    }

    $_SESSION['user_rights'] = $this->casUserRights;
    $_SESSION['user_data_rights'] = $this->casUserDataRights;
    return true;
  }

  /**
   * check if the current user can access the request page (defined by uid, action, type, pk)
   * @param type $psUid
   * @param type $psAction
   * @param type $psType
   * @param type $pnPk
   * @param array $psCallback
   * @return boolean
   */
  public function canAccess($psUid, $psAction = '', $psType = '', $pnPk = 0, $pasCallback = array())
  {
    if($this->cbIsAdmin)
      return true;

    if(is_array($psUid))
    {
      if(!is_cpValues($psUid))
      {
        assert('false; // bad parameters');
        return false;
      }

      $psAction = $psUid[CONST_CP_ACTION];
      $psType = $psUid[CONST_CP_TYPE];
      $pnPk = (int)$psUid[CONST_CP_PK];
      $psUid = $psUid[CONST_CP_UID];
    }

    if(empty($pnPk))
      $sPk = '*';
    else
      $sPk = $pnPk;

    if(empty($psType))
      $psType = '*';

    /*dump('is ['."$psUid, $psAction, $psType , $pnPk".'] in the array ');
    dump(@$this->casUserRights[$psUid]);*/

    //check first globals rights (component, action or type)
    if(    isset($this->casUserRights[$psUid]['*']['*']['*'])
        || isset($this->casUserRights[$psUid][$psAction]['*']['*'])
        || isset($this->casUserRights[$psUid]['*'][$psType]['*'])
        || isset($this->casUserRights[$psUid][$psAction][$psType]['*'])
      )
    {
      //dump('Canaccess (*) OK for ['."$psUid, $psAction, $psType , $pnPk".'] - '.__LINE__);
      return true;
    }

    //check then the specific right matching exactly the paramneters
    if(isset($this->casUserRights[$psUid][$psAction][$psType][$sPk]) && !empty($this->casUserRights[$psUid][$psAction][$psType][$sPk]))
    {
      if($this->casUserRights[$psUid][$psAction][$psType][$sPk] === true)
      {
        //dump('Canaccess (pk) OK for ['."$psUid, $psAction, $psType , $pnPk".'] - '.__LINE__);
        return true;
      }
    }



    /* TODO: get the callback (closest from available parameters
     *
     * //in case the right is no
        $oComponent = CDependency::getComponentByUid($psUid);
        $bCallbackResponse = call_user_method($oComponent, $this->casUserRights[$psUid][$psAction][$psType][$sPk]['calback'], $this->casUserRights[$psUid][$psAction][$psType][$sPk]['calback_params']);

        if($bCallbackResponse)
          return true;
     *
     */

    //if the component calling this function specify a specific callback, we try it
    if(empty($pasCallback) || !isset($pasCallback['function']) || empty($pasCallback['function']))
      return false;

    if(!isset($pasCallback['params']))
      $pasCallback['params'] = array();

    $oComponent = CDependency::getComponentByUid($psUid);
    return call_user_method($pasCallback['function'], $oComponent, $pasCallback['params']);
  }

  /**
   * return an array of the group rights
   * @param integer $pnPk
   * @param bool $pbGetRight
   * @param bool $pbGetAlias
   * @param bool $pbGetStatic
   *
   * @return an array containing rights and aliases of a specific or all groups
   */
  public function getGroupRights($pvPk = 0, $pbGetRight = true, $pbGetAlias = false, $pbGetStatic = false)
  {
    if(!assert('empty($pvPk) || is_integer($pvPk) || is_arrayOfInt($pvPk)') )
      return array();

    if(is_integer($pvPk))
      $bSingleGrp = true;
    else
      $bSingleGrp = false;

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM right_user as ru ';
    $sQuery.= ' INNER JOIN `right` as r ON (r.rightpk = ru.rightfk ';

    if(!$pbGetRight)
      $sWhere = ' AND r.type <> "right" ';

    if(!$pbGetAlias)
      $sWhere = ' AND r.type <> "alias" ';

    if(!$pbGetStatic)
      $sWhere = ' AND r.type <> "static" ';

     $sQuery.= ' )';

    if(!empty($pvPk))
    {
      if($bSingleGrp)
        $sQuery.= ' WHERE ru.groupfk = '.$pvPk;
      else
        $sQuery.= ' WHERE ru.groupfk IN ('.implode(',', $pvPk).') ';
    }

    $sQuery.= ' ORDER BY ru.groupfk ASC, cp_uid, cp_action, cp_type, cp_pk ';

    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    $asRightData = array();
    while($bRead)
    {
      if($bSingleGrp)
        $asRightData[$oDbResult->getFieldValue('rightfk', CONST_PHP_VARTYPE_INT)] = $oDbResult->getData();
      else
        $asRightData[$oDbResult->getFieldValue('groupfk', CONST_PHP_VARTYPE_INT)][$oDbResult->getFieldValue('rightfk', CONST_PHP_VARTYPE_INT)] = $oDbResult->getData();

      $bRead = $oDbResult->readNext();
    }

    return $asRightData;
  }
  /**
   * return an array of the user rights
   * @param integer $pnPk
   * @param bool $pbGetRight
   * @param bool $pbGetAlias
   * @param bool $pbGetStatic
   *
   * @return an array containing rights and aliases of a specific or all users
   */
  public function getUserRights($pnPk = 0, $pbGetRight = true, $pbGetAlias = false, $pbGetStatic = false)
  {
    if(!assert('is_integer($pnPk)'))
      return array();

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM right_user as ru ';
    $sQuery.= ' INNER JOIN `right` as r ON (r.rightpk = ru.rightfk ';

    if(!$pbGetRight)
      $sWhere = ' AND r.type NOT IN("right", "data") ';

    if(!$pbGetAlias)
      $sWhere = ' AND r.type <> "alias" ';

    if(!$pbGetStatic)
      $sWhere = ' AND r.type <> "static" ';

     $sQuery.= ' )';

    if(!empty($pnPk))
      $sQuery.= ' WHERE ru.loginfk = '.$pnPk;

    $sQuery.= ' ORDER BY loginfk ASC, cp_uid, cp_action, cp_type, cp_pk ';

    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    $asRightData = array();
    while($bRead)
    {
      $asRightData[$oDbResult->getFieldValue('loginfk', CONST_PHP_VARTYPE_INT)][] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asRightData;
  }

  /**
   * return an array with user rightpk
   *
   * @param integer $pnPk
   * @param bool $pbGetRight
   * @param bool $pbGetAlias
   * @param bool $pbGetStatic
   *
   * @return an array of rightpk and aliases of a specific or all users
   */

   public function getUserRightsPk($pnPk = 0, $pbGetRight = true, $pbGetAlias = false, $pbGetStatic = false)
   {
     $asRights = $this->getUserRights($pnPk, $pbGetRight, $pbGetAlias, $pbGetStatic);

     $asRightData = array();
     foreach($asRights as $nUserfk => $asUserRights)
     {
         foreach($asUserRights as $nKey => $asRight)
          $asRightData[$asRight['rightfk']] = $asRight['rightfk'];
     }
     return $asRightData;
   }

  /**
   * return an array of the request rights
   *
   * @param bool $pbGetRight
   * @param bool $pbGetAlias
   * @param bool $pbGetStatic
   *
   * @return array containing rights data
   */
  public function getRightList($pbGetRight = true, $pbGetAlias = true, $pbGetStatic = false, $pbGetLogged = false)
  {
    if(!assert('is_bool($pbGetRight) && is_bool($pbGetAlias) && is_bool($pbGetStatic)'))
      return array();

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM `right` as r ';
    $sQuery.= ' LEFT JOIN `right_tree` as rt ON (rt.parentfk = r.rightpk) ';
    $sWhere = '';

    if(!$pbGetRight)
      $sWhere.= ' AND r.type <> "right" ';

    if(!$pbGetAlias)
      $sWhere.= ' AND r.type <> "alias" ';

    if(!$pbGetStatic)
      $sWhere.= ' AND r.type <> "static" ';

    if(!$pbGetLogged)
      $sWhere.= ' AND r.type <> "logged" ';

    if(!empty($sWhere))
      $sQuery.= ' WHERE 1 '.$sWhere;

    $sQuery.= ' ORDER BY cp_uid, parentfk, label';

    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    $asRightData = array();
    while($bRead)
    {
      $asRightData[$oDbResult->getFieldValue('rightpk', CONST_PHP_VARTYPE_INT)] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asRightData;
  }

  /**
   * Function to get child rights of parent
   * @param integer $pnRightPk
   * @return array
   */

  public function getChildRights($pvRightPk, $panTreated = array(), $pbAllData = false)
   {
     if(!assert('is_key($pvRightPk) || is_listOfInt($pvRightPk)'))
       return array();

    if(!assert('is_array($panTreated) && is_bool($pbAllData)'))
       return array();

    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'SELECT r.*, rt.rightfk, rt.parentfk, childOfChild.rightfk as childParentOf ';
    $sQuery.= ' FROM `right` as r ';
    $sQuery.= ' INNER JOIN `right_tree` as rt ON (rt.rightfk = r.rightpk) ';
    $sQuery.= ' LEFT JOIN `right` as child ON (child.rightpk = rt.rightfk) ';
    $sQuery.= ' LEFT JOIN `right_tree` as childOfChild ON (childOfChild.parentfk = child.rightpk) ';
    $sQuery.= ' WHERE rt.parentfk IN ('.$pvRightPk.') ';
    $sQuery.= ' ORDER BY r.label ';


    $oDbResult = $oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return array();

    $anTreated = $panTreated;
    $asRightChildData = array();
    $asChildParent = array();

    while($bRead)
    {
      $nRightFk = (int)$oDbResult->getFieldValue('rightfk');
      $nParentFK = (int)$oDbResult->getFieldValue('parentfk');

      $asRightChildData[$nRightFk]['pk'] = $nRightFk;
      $asRightChildData[$nRightFk]['label']= $oDbResult->getFieldValue('label');
      $asRightChildData[$nRightFk]['type']= $oDbResult->getFieldValue('type');
      $asRightChildData[$nRightFk]['parent']= $nParentFK;

      if($pbAllData)
        $asRightChildData[$nRightFk]['data']= $oDbResult->getData();

      $anTreated[$nParentFK] = $nParentFK;
      $anTreated[$nRightFk] = $nRightFk;

      //check if the child right is the parent of other sub rights. Log it to launch a second search on those
      $nChildParent = (int)$oDbResult->getFieldValue('childParentOf');
      if(!empty($nChildParent))
      {
        $asChildParent[$nRightFk] = $nRightFk;
        @$asRightChildData[$nRightFk]['hasChild'].= ','.$nChildParent;
      }

      $bRead = $oDbResult->readNext();
    }

    //check if there are child rights parenting other rights that have not been fecthed
    if(!empty($asChildParent))
    {
      //echo(' = = = = == = = = = = = = == == == = = = = = == = = <br />');
      //echo(' = = = = == = = = = = = = == == == = = = = = == = = <br />');
      $asNextLevelRight = $this->getChildRights(implode(',', $asChildParent), $anTreated, $pbAllData);
      //echo('<hr/>');
      //echo('<hr/>Search next level with: '.implode(',', $asUnlisted).'<br />');
      //dump($asNextLevelRight);

      $asRightChildData = array_replace_recursive($asRightChildData, $asNextLevelRight);
    }


    return $asRightChildData;
  }

  /**
   * Function to save the user rights
   * @return boolean
   */

  public function getUserRightSave()
  {
    $oDB = CDependency::getComponentByName('database');
    $asRights = getValue('usrRight');
    $nUserfk = getValue('userfk');

    $sQuery = 'DELETE FROM right_user WHERE loginfk = '.$nUserfk;
    $oDB->ExecuteQuery($sQuery);

    if(!empty($asRights))
    {
      //link the selected rights to the user
      $asMysqlQuery = array();
      foreach($asRights as $sKey => $nRight)
      {
        //affect the current right to user
        $asMysqlQuery[] = '('.$nRight.', '.$nUserfk.')';
      }

      if(!empty($asMysqlQuery))
      {
        $sQuery = 'INSERT INTO right_user (`rightfk`,`loginfk`) VALUES ';
        $sQuery.= implode(',',$asMysqlQuery);
        $oDbResult = $oDB->ExecuteQuery($sQuery);
      }
    }
    return true;
  }

  /**
   * Save the rights of a specific user
   * Requires post or get values: loginfk user to set the rights for, anRight array of the rights pk
   * @return boolean
   */
  public function saveUserRights()
  {
    $nLoginfk = (int)getValue('loginfk', 0);
    if(!assert('is_key($nLoginfk)'))
      return false;

    $anRights = getValue('anRight', array());

    $bSaved = $this->_saveRights($nLoginfk, $anRights);

    if(!$bSaved)
      return false;

    //if changing current user rights, we reload it
    if($nLoginfk == $this->cnUserPk)
      $this->loadUserRights();

    return true;
  }

  /**
   * Save the rights of a specific group
   * @param integer $pnGroupPk
   * @return boolean
  */
  public function saveGroupRights($pnGroupPk)
  {
    if(!assert('is_key($pnGroupPk)'))
      return false;

    $anRights = getValue('usrRight', array());
    return $this->_saveRights($pnGroupPk, $anRights, true);
  }

}
