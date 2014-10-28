<?php

class CFolderModelEx extends CFolderModel
{

  public function __construct()
  {
    parent::__construct();
    return true;
  }


  protected function _testFields($avFields, $psTablename, $pbAllFieldRequired = true, $pbAllowExtra = true, $psAction = 'add')
  {
    $this->casError = array();

    if($psTablename == 'addressbook_contact')
    {
      if(!isset($avFields['prospect']))
        $bProspect = false;
      else
        $bProspect = (bool)$avFields['prospect'];


      if($psAction != 'add' && (!isset($avFields['addressbook_contactpk']) || !is_integer($avFields['addressbook_contactpk'])))
      {
        $this->casError[] = __LINE__.' - Missing data to save connection';
        return false;
      }

      if($psAction != 'add' && empty($avFields['addressbook_contactpk']))
      {
        $this->casError[] = __LINE__.' - Missing connection id';
        return false;
      }

      if(!isset($avFields['courtesy']) || empty($avFields['courtesy']))
      {
        $this->casError[] = __LINE__.' - Missing courtesy ';
        return false;
      }

      if(!isset($avFields['lastname']) || strlen($avFields['lastname']) < 2)
      {
        $this->casError[] = __LINE__.' - Lastname invalid ';
        return false;
      }

      if(!$bProspect && (!isset($avFields['firstname']) || strlen($avFields['firstname']) < 2))
      {
        $this->casError[] = __LINE__.' - Firstname invalid ';
        return false;
      }

      if(!empty($avFields['email']) && !isValidEmail($avFields['email'], false))
      {
        $this->casError[] = __LINE__.' - Email invalid ';
        return false;
      }

      if($psAction != 'update' && (!isset($avFields['loginfk']) || !is_key($avFields['loginfk'])))
      {
        $this->casError[] = __LINE__.' - User id invalid | '.$psAction;
        return false;
      }

      if($psAction == 'add' && (!isset($avFields['followerfk']) || !is_integer($avFields['followerfk']) || $avFields['followerfk'] < 0))
      {
        $this->casError[] = __LINE__.' - Account manager invalid ';
        return false;
      }

      return true;
    }
    else
      return parent::_testFields($avFields, $psTablename, $pbAllFieldRequired, $pbAllowExtra);
  }


  // ----------------------------------------------
  // Gets subfolders of parentfk folder
  // @param $pnParentFk int
  // ----------------------------------------------

  public function getFoldersByParentFk($pnParentFk = 0, $pnUserPk, $psRight='read')
  {
    if(!assert('is_integer($pnParentFk)'))
      return new CDbResult;

    if(!assert('is_key($pnUserPk)'))
      return new CDbResult;

    if(!assert('is_string($psRight)'))
      return new CDbResult();

    $sQuery = 'SELECT f.*, COUNT(fi.folder_itempk) as nbitems
                FROM folder f LEFT JOIN folder_item fi ON fi.parentfolderfk = f.folderpk
                WHERE f.parentfolderfk='.$pnParentFk.'
                AND
                (
                  (f.ownerloginfk='.$pnUserPk.' AND f.private=1)
                    OR f.private=0
                    OR (f.private=2 AND ('.$pnUserPk.' IN (SELECT loginfk FROM folder_rights WHERE folderfk=f.folderpk AND rights=\''.$psRight.'\')))
                )
                GROUP BY f.folderpk
                ORDER BY f.rank, f.label';

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  // ----------------------------------------------
  // Gets pages recorded in parentfk folder
  // @param $pnParentFk int
  // ----------------------------------------------

  public function getPagesByParentFk($pnParentFk)
  {
    if(!assert('is_integer($pnParentFk)'))
      return new CDbResult;

    $sQuery = 'SELECT * FROM folder_item fp INNER JOIN folder_link fl ON fp.folderfk = fl.folderfk';
    $sQuery .= ' WHERE fp.folderfk='.$pnParentFk.' ORDER BY fp.rank, fp.label';
    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  // ----------------------------------------------
  // Returns rights of a user on folders
  // ----------------------------------------------

  public function getUserRights($pnUserPk)
  {
    if(!assert('is_integer($pnUserPk)') || $pnUserPk < 1)
      return array();

    $sQuery = 'SELECT *
               FROM folder f
               LEFT JOIN folder_rights fr ON f.folderpk = fr.folderfk
               WHERE fr.loginfk='.$pnUserPk;

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();

    $aRights = array();

    if(!$bRead)
      return $aRights;
    else
    {
      while($bRead)
      {
        $aRights[$oDbResult->getFieldValue('folderpk')][] = $oDbResult->getFieldValue('rights');

        $bRead = $oDbResult->readNext();
      }
      return $aRights;
    }
  }

  // ----------------------------------------------
  // Returns a folder and its associated settings
  // ----------------------------------------------

  public function getFolder($pnFolderPk)
  {
    if(!assert('is_key($pnFolderPk)'))
      return new CDbResult();

    $sQuery = 'SELECT *
               FROM folder f
               LEFT JOIN folder_link fl ON f.folderpk = fl.folderfk
               WHERE f.folderpk='.$pnFolderPk;

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  // ----------------------------------------------
  // Returns all folders a user has right to see
  // or manage
  // @param $psRight filters a specific right
  // @param $paLink filters a specific type of folder
  // ----------------------------------------------

  public function getFolders($pnUserPk, $psRight='read', $paLink = array())
  {
    if(!assert('is_integer($pnUserPk)') || $pnUserPk < 1)
      return new CDbResult();

    if(!assert('is_string($psRight)'))
      return new CDbResult();

    $sQuery = 'SELECT f.* , SUM(IF(fi.folder_itempk IS NULL, 0, 1)) as nb_item, fl.cp_uid, fl.cp_action, fl.cp_type
               FROM folder f
               LEFT JOIN folder_link fl ON f.folderpk = fl.folderfk
               LEFT JOIN folder_rights fr ON f.folderpk = fr.folderfk
               LEFT JOIN folder_item fi ON (f.folderpk = fi.parentfolderfk)
               WHERE
                (
                  (f.ownerloginfk='.$pnUserPk.' AND f.private=1)
                    OR f.private=0
                    OR (f.private=2 AND ('.$pnUserPk.' IN (SELECT loginfk FROM folder_rights WHERE folderfk=f.folderpk AND rights=\''.$psRight.'\')))
                )';

    if(!empty($paLink))
      $sQuery .= ' AND fl.cp_uid=\''.$paLink['cp_uid'].'\' AND fl.cp_action=\''.$paLink['cp_action'].'\' AND cp_type=\''.$paLink['cp_type'].'\'';

    $sQuery .= ' GROUP BY f.folderpk
      ORDER BY f.label';
    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  public function getFolderOwners($pnUserPk, $psRight='read', $pasLink = array(), $pbIncludeUser = false)
  {
    if(!assert('is_integer($pnUserPk)') || $pnUserPk < 1)
      return new CDbResult();

    if(!assert('is_string($psRight)'))
      return new CDbResult();

    $sQuery = 'SELECT DISTINCT(fold.ownerloginfk), slog.*
               FROM folder as fold
               LEFT JOIN folder_rights frig ON (fold.folderpk = frig.folderfk)
               LEFT JOIN shared_login as slog ON (fold.ownerloginfk = slog.loginpk) ';

    if(!empty($pasLink))
      $sQuery.= 'LEFT JOIN folder_link flin ON (fold.folderpk = flin.folderfk) ';

    if($pbIncludeUser)
      $sQuery.= ' WHERE fold.system_folder = 0 AND (fold.ownerloginfk = '.$pnUserPk.' OR fold.private=0 OR (frig.loginfk='.$pnUserPk.' AND frig.rights="'.$psRight.'"))';
    else
      $sQuery.= ' WHERE fold.system_folder = 0 AND (fold.ownerloginfk <> '.$pnUserPk.' AND (fold.private=0 || (frig.loginfk='.$pnUserPk.' AND frig.rights="'.$psRight.'")))';

    if(!empty($pasLink))
      $sQuery .= ' AND flin.cp_uid=\''.$pasLink['cp_uid'].'\' AND flin.cp_action=\''.$pasLink['cp_action'].'\' AND flin.cp_type=\''.$pasLink['cp_type'].'\'';


    $sQuery.= ' ORDER BY slog.firstname, slog.lastname ';
    //echo $sQuery;
    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }



  // ----------------------------------------------
  // Returns all pages / folder items a user has
  // right on
  // @param int $pnUserPk
  // @param string $psRight
  // ----------------------------------------------

  public function getFolderItems($pnUserPk, $psRight='read', $paLink = array())
  {
    if(!assert('is_key($pnUserPk)'))
      return new CDbResult();

    if(!assert('is_string($psRight)'))
      return new CDbResult();


    //fetch all items details
    $sQuery = 'SELECT fi.*
               FROM folder f
               INNER JOIN folder_item fi ON f.folderpk = fi.parentfolderfk
               LEFT JOIN folder_rights fr ON f.folderpk = fr.folderfk
               LEFT JOIN folder_link fl ON f.folderpk = fl.folderfk
               WHERE
                (
                  (f.ownerloginfk='.$pnUserPk.' AND f.private=1)
                    OR f.private=0
                    OR (f.private=2 AND ('.$pnUserPk.' IN (SELECT loginfk FROM folder_rights WHERE folderfk=f.folderpk AND rights=\''.$psRight.'\')))
                )';


    if(!empty($paLink))
      $sQuery .= ' AND fl.cp_uid=\''.$paLink['cp_uid'].'\' AND fl.cp_action=\''.$paLink['cp_action'].'\' AND cp_type=\''.$paLink['cp_type'].'\'';

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  // Looks for an item between folders of same type

  public function getItemFromType($pnItemPk, $paLink)
  {
    if(!assert('is_key($pnItemPk)'))
      return new CDbResult();

    $sQuery = 'SELECT *
                FROM folder_item fi
                INNER JOIN folder_link fl ON (fl.folderfk = fi.parentfolderfk)
                INNER JOIN folder fold ON (fold.folderpk = fl.folderfk)
                WHERE fi.itemfk='.$pnItemPk.' AND fl.cp_uid=\''.$paLink['cp_uid'].'\' AND fl.cp_action=\''.$paLink['cp_action'].'\' AND cp_type=\''.$paLink['cp_type'].'\'';

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  // ----------------------------------------------
  // Returns the highest rank of a subfolder of
  // folder $pnParentFk
  // @param int $pnParentFk
  // ----------------------------------------------

  public function getHighestRank($pnParentFk, $psTable = 'folder')
  {
    if(!assert('!empty($psTable)'))
      return 0;

    if(!assert('is_integer($pnParentFk)'))
      return 0;

    $sQuery = 'SELECT MAX(rank) as rank FROM '.$psTable.' WHERE parentfolderfk='.$pnParentFk;

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return 0;

    return (int)$oDbResult->getFieldValue('rank');
  }

  // ----------------------------------------------
  // Gets folders in which an item can be added
  // @param $paCpValues array
  // @param $pnUserPk int
  // ----------------------------------------------

  public function getFoldersByLink($paCpValues, $pnUserPk)
  {
    if(!assert('is_cpValues($paCpValues)'))
      return new CDbResult;

    if(!assert('is_key($pnUserPk)'))
      return new CDbResult;

    $sQuery = 'SELECT f.* FROM folder f
              INNER JOIN folder_link fl ON fl.folderfk=f.folderpk
              LEFT JOIN folder_rights fr ON (fr.folderfk=f.folderpk AND fr.rights="add_item" AND fr.loginfk='.$pnUserPk.')
              WHERE fl.cp_uid='.$this->oDB->dbEscapeString($paCpValues['cp_uid']).'
                AND fl.cp_action='.$this->oDB->dbEscapeString($paCpValues['cp_action']).'
                AND fl.cp_type='.$this->oDB->dbEscapeString($paCpValues['cp_type']).'
                AND (f.ownerloginfk='.$pnUserPk.' OR f.private=0 OR (fr.loginfk='.$pnUserPk.' AND fr.rights="add_item"))
                ORDER BY f.rank, f.label';

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  // -------------------------------------------------
  // Checks if an item has already been added to
  // a folder
  // @param int $pnFolderPk
  // @param int $pnItemFk
  // -------------------------------------------------

  public function itemInFolder($pnFolderPk, $pnItemFk)
  {
    if(!assert('is_key($pnFolderPk)'))
      return new CDbResult;

    if(!assert('is_key($pnItemFk)'))
      return new CDbResult;

    $sQuery = 'SELECT * FROM folder_item WHERE parentfolderfk='.$pnFolderPk.' AND itemfk='.$pnItemFk;

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    return $bRead;
  }

  // ------------------------------------------------
  // Delete items from a given folder
  // @param int $pnParentFolderFk
  // ------------------------------------------------

  public function deleteItemFromParentFk($pnParentFolderFk)
  {
    if(!assert(is_key($pnParentFolderFk)))
      return false;

    $sQuery = 'DELETE FROM `folder_item` WHERE parentfolderfk='.$pnParentFolderFk;
    $this->oDB->ExecuteQuery($sQuery);

    return true;
  }

  // -------------------------------------------------
  // Gets the list of users that have rights on a given
  // folder
  // @param int $pnFolderfk
  // -------------------------------------------------

  public function getUserRightsOnFolder($pnFolderfk)
  {
    if(!assert(is_key($pnFolderfk)))
      return array();

    $oUserRights = $this->getByFk($pnFolderfk, 'folder_rights', 'folder');

    $asUserRights = array();
    $bRead = $oUserRights->readFirst();
    while($bRead)
    {
      $asUserRights[$oUserRights->getFieldValue('loginfk')][] = $oUserRights->getFieldValue('rights');
      $bRead = $oUserRights->readNext();
    }

    return $asUserRights;
  }


  public function searchFolders($pnUserPk, $pnOwnerPk=0, $psSearchString='', $pbRemoveUserFolder = true, $pbDetailRights = false, $pbCountItems = false)
  {
    if(!assert('is_key($pnUserPk) && is_integer($pnOwnerPk) && is_bool($pbRemoveUserFolder) && is_bool($pbCountItems)'))
      return array();

    if(!empty($pnOwnerPk))
      $sUserSql = ' AND (fold.ownerloginfk = '.$pnOwnerPk.')  ';
    else
      $sUserSql = '';

    if($pbRemoveUserFolder)
      $sExcludeSql = ' AND (fold.ownerloginfk <> '.$pnUserPk.') ';
    else
      $sExcludeSql = '';

    if(!$pbDetailRights)
      $sSelectSql = ' , GROUP_CONCAT(frig.rights) as right_list ';
    else
      $sSelectSql = '';

    $psSearchString = str_replace('_', '\_', $psSearchString);

    $sQuery = 'SELECT fold.*, frig.*, slog.friendly, slog.firstname, slog.lastname '.$sSelectSql.' FROM folder as fold
      LEFT JOIN folder_rights as frig ON (frig.folderfk = fold.folderpk AND fold.private = 2 AND frig.loginfk = '.$pnUserPk.' '.$sUserSql.' '.$sExcludeSql.')
      LEFT JOIN shared_login as slog ON (slog.loginpk = fold.ownerloginfk)
      WHERE (fold.private = 0 OR (fold.private = 2 AND frig.loginfk IS NOT NULL)) '.$sUserSql.' '.$sExcludeSql;

    if(!empty($psSearchString))
      $sQuery.= ' AND (fold.label LIKE '.$this->oDB->dbEscapeString('%'.$psSearchString.'%').')  ';

    if(!$pbDetailRights)
      $sQuery.= ' GROUP BY fold.folderpk, frig.loginfk  ';


    $sQuery.= ' ORDER BY fold.label ';

    //echo $sQuery;
    $oDbResult = $this->oDB->executeQuery($sQuery);
    if(!$oDbResult)
      return array();

    $asFolders = array();
    $anFolderPks = array();

    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $nFolderPk = $oDbResult->getFieldValue('folderpk', CONST_PHP_VARTYPE_INT);

      $anFolderPks[] = $nFolderPk;
      $asFolders[$nFolderPk] = $oDbResult->getData();
      $asFolders[$nFolderPk]['nb_items'] = 0;

      $bRead = $oDbResult->readNext();
    }

    if(empty($anFolderPks) || !$pbCountItems)
      return $asFolders;

    $oDbResult = $this->countFolderItems($anFolderPks);
    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $nFolderPk = $oDbResult->getFieldValue('folderfk', CONST_PHP_VARTYPE_INT);
      $asFolders[$nFolderPk]['nb_items'] = $oDbResult->getFieldValue('nb_items', CONST_PHP_VARTYPE_INT);

      $bRead = $oDbResult->readNext();
    }


    return $asFolders;
  }


  public function countFolderItems($pvFolderPk)
  {
    if(!assert('is_key($pvFolderPk) || is_arrayOfInt($pvFolderPk)'))
      return new CDbResult();

    $sQuery = 'SELECT parentfolderfk as folderfk, count(*) as nb_items	FROM folder_item WHERE parentfolderfk ';

    if(is_integer($pvFolderPk))
      $sQuery.= ' = '.$pvFolderPk;
    else
      $sQuery.= ' IN ('.implode(',', $pvFolderPk).') ';

    $sQuery.= ' GROUP BY parentfolderfk ';

    return $this->oDB->executeQuery($sQuery);
  }

  // Returns pk of the root folder of a given type of data

  public function getRootFolderPk($paCpValues)
  {
    if(!is_cpValues($paCpValues))
      return 0;

    $sQuery = 'SELECT f.folderpk
                FROM folder f
                LEFT JOIN folder_link fl ON f.folderpk=fl.folderfk
                WHERE f.parentfolderfk=0
                  AND fl.cp_uid='.$this->oDB->dbEscapeString($paCpValues['cp_uid']).'
                  AND fl.cp_action='.$this->oDB->dbEscapeString($paCpValues['cp_action']).'
                  AND fl.cp_type='.$this->oDB->dbEscapeString($paCpValues['cp_type']);

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return 0;
    else
      return (int)$oDbResult->getFieldValue('folderpk');

  }


  public function checkItemExist($pnFolderFk, $panItemFk)
  {

    if(!assert('is_key($pnFolderFk) || is_arrayOfInt($panItemFk)'))
      return array();

    $sQuery = 'SELECT fold.folderpk, fite.itemfk	FROM folder as fold ';
    $sQuery.= ' LEFT JOIN  folder_item as fite ON (fite.parentfolderfk	= fold.folderpk AND fite.itemfk IN ('.implode(',', $panItemFk).'))';
    $sQuery.= ' WHERE fold.folderpk = '.$pnFolderFk;

    return $this->oDB->executeQuery($sQuery);
  }

}
