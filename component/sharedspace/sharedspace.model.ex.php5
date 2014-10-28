<?php

class CSharedSpaceModelEx extends CSharedSpaceModel
{
  public function __construct()
  {
    parent::__construct();
    return true;
  }


  // Retrieves most recent documents
  // @param int pnDays

  public function getLastDocuments($pnDays = 14, $pnNumber = 0, $pbSharedOnly = true)
  {
    if(!assert('is_numeric($pnDays)'))
      return new CDbResult();

    if(!assert('is_numeric($pnNumber)'))
      return new CDbResult();

    $sQuery = 'SELECT docu.*, dfil.*
               FROM document docu
               LEFT JOIN document_file dfil ON (dfil.documentfk = docu.documentpk) ';

    if($pbSharedOnly)
      $sQuery .= 'LEFT JOIN document_link as dlin ON (dlin.documentfk = docu.documentpk) ';

    $sQuery .= 'WHERE dfil.live = 1 ';

    if($pbSharedOnly)
      $sQuery .= ' AND dlin.document_linkpk IS NULL ';

    if($pnDays > 0)
      $sQuery .= ' AND dfil.date_creation > "'.date('Y-m-d', strtotime('-'.$pnDays.' days')).'" ';

    $sQuery .= ' ORDER BY docu.date_update DESC';

    if($pnNumber > 0)
      $sQuery .= ' LIMIT 1,'.$pnNumber;

    $oResult = $this->oDB->ExecuteQuery($sQuery);

    return $oResult;
  }

  // -------------------------------------------------
  // Returns user custom rights
  // @param $pnUserFk
  // -------------------------------------------------

  public function getUserRights($pnUserFk)
  {
     if(!assert('is_key($pnUserFk)'))
      return array();

     $oRights = $this->getByFk($pnUserFk, 'document_rights', 'login');
     $bRead = $oRights->readFirst();

     $aRights = array();
     while($bRead)
     {
       $aRights[$oRights->getFieldValue('documentfk')][]=$oRights->getFieldValue('rights');

       $bRead = $oRights->readNext();
     }

     return $aRights;
  }

  // -------------------------------------------------
  // Gets the list of users that have rights on a given
  // document and their own rights
  // @param int $pnDocfk
  // @param string $psRight -> to track a specific right
  // -------------------------------------------------

  public function getUsersRightsOnDocument($pnDocfk, $psRight = '')
  {
    if(!assert('is_key($pnDocfk)'))
      return array();

    if(!assert('is_string($psRight)'))
      return array();

    if(!empty($psRight))
      $oUsersRights = $this->getByWhere('document_rights', 'documentfk='.$pnDocfk.' AND rights=\''.$psRight.'\'');
    else
      $oUsersRights = $this->getByFk($pnDocfk, 'document_rights', 'document');

    $asUsersRights = array();

    $bRead = $oUsersRights->readFirst();
    if($bRead)
    {
      while($bRead)
      {
        $asUsersRights[$oUsersRights->getFieldValue('loginfk')][] = $oUsersRights->getFieldValue('rights');
        $bRead = $oUsersRights->readNext();
      }
    }

    return $asUsersRights;
  }

  // Checks if a user has a specific right on a document
  public function hasRights($pnDocFk, $pnUserFk, $psRight)
  {
    if(!assert('is_key($pnDocFk)'))
      return false;

    if(!assert('is_key($pnUserFk)'))
      return false;

    if(!assert('is_string($psRight)'))
      return false;

    $oDocument = $this->getByPk($pnDocFk, 'document');
    if(($oDocument->getFieldValue('creatorfk')==$pnUserFk) || ($oDocument->getFieldValue('private')==0))
      return true;

    $oUserRight = $this->getByWhere('document_rights', 'documentfk='.$pnDocFk.' AND rights=\''.$psRight.'\' AND loginfk='.$pnUserFk);
    return ($oUserRight->getFieldValue('loginfk') == $pnUserFk);
  }

  // Returns a document with its related data

  public function getDocument($pnDocPk)
  {
    if(!assert('is_key($pnDocPk)'))
      return new CDbResult();

    $sQuery = 'SELECT d.*, dl.*, df.*,
                GROUP_CONCAT(revision.document_filepk SEPARATOR ",") as rev_filepk
               , GROUP_CONCAT(revision.initial_name SEPARATOR ",") as rev_file
               , GROUP_CONCAT(DATE_FORMAT(revision.date_creation, "%Y-%m-%d") SEPARATOR ",") as rev_date
               , count(revision.document_filepk) as rev_count
               FROM document d
               LEFT JOIN document_link as dl ON (d.documentpk = dl.documentfk)
               LEFT JOIN document_file as df ON (d.documentpk = df.documentfk AND df.live = 1)
               LEFT JOIN document_file as revision ON (d.documentpk = revision.documentfk AND revision.live = 0)
               WHERE d.documentpk = '.$pnDocPk.'
               GROUP BY df.documentfk ';

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  // Selects the last documents that are not linked to any page

  public function getLastDocumentsNotLinked($pnUserPk, $pnNbDocs = 5)
  {
    if(!assert('is_key($pnUserPk)'))
      return array();

    if(!assert('is_key($pnNbDocs)'))
      return array();

    $sQuery = 'SELECT * FROM document d
      LEFT JOIN document_file df ON d.documentpk = df.documentfk
      WHERE
      (
        ( (d.creatorfk='.$pnUserPk.' AND private=1)
          OR d.private=0
          OR (private=2 AND ('.$pnUserPk.' IN (SELECT loginfk FROM document_rights WHERE documentfk=d.documentpk)))
        )
        AND (df.live=1)
        AND (d.documentpk NOT IN (SELECT documentfk FROM document_link))
      )
      ORDER BY df.date_creation DESC LIMIT 0,'.$pnNbDocs;

    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();

    $aOutput= array();

    if(!$bRead)
      return $aOutput;
    else
    {
      while($bRead)
      {
        $aOutput[$oDbResult->getFieldValue('documentpk')] = $oDbResult->getData();
        $bRead = $oDbResult->readNext();
      }
      return $aOutput;
    }
  }

  // Sends documents from the same folder

  public function getDocumentsFromFolder($pnFolderPk, $pnUserPk, $psRight='read')
  {
    if(!assert('is_key($pnFolderPk)'))
      return array();

    if(!assert('is_key($pnUserPk)'))
      return array();

    $sQuery = '
      SELECT *
        FROM shared_document_folder_item si
        WHERE ( (si.creatorfk='.$pnUserPk.' AND si.private=1)
                OR si.private=0
                OR (si.private=2 AND ('.$pnUserPk.' IN (SELECT loginfk FROM document_rights WHERE documentfk=si.documentpk AND rights=\''.$psRight.'\')))
              )
        AND parentfolderfk='.$pnFolderPk.'
        ORDER BY si.date_last_revision DESC';
    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();
    $aOutput= array();

    if(!$bRead)
      return $aOutput;
    else
    {
      while($bRead)
      {
        $aOutput[$oDbResult->getFieldValue('documentpk')] = $oDbResult->getData();
        $bRead = $oDbResult->readNext();
      }
      return $aOutput;
    }

  }

  /**
   * Returns the list of documents with the rights
   * @param type $pnUserPk
   * @param type $psRight
   * @param type $paCpValues
   * @return array
   */

  public function getDocuments($pnUserPk, $psRight='read', $paCpValues = array(), $paPagination = array())
  {

    if(!assert('is_integer($pnUserPk)'))
      return array();

    if(!assert('is_array($paCpValues)'))
      return array();

    if(!assert('is_array($paPagination)'))
      return array();

    if(!empty($paPagination))
    {
      if(!assert('is_key($paPagination[\'nPage\'])'))
        return array();

      if(!assert('is_key($paPagination[\'nNbItems\'])'))
        return array();
    }

    $oLogin = CDependency::getComponentByName('login');
    if($pnUserPk == -1 && $oLogin->isAdmin())
    {
      $sQuery = 'SELECT *
                 FROM document d
                 LEFT JOIN document_link dl ON d.documentpk = dl.documentfk
                 LEFT JOIN document_file df ON d.documentpk = df.documentfk
                 WHERE df.live=1 ';
    }
    else
    {
      $sQuery = 'SELECT *
                  FROM document d
                  LEFT JOIN document_link dl ON d.documentpk = dl.documentfk
                  LEFT JOIN document_file df ON d.documentpk = df.documentfk
                  WHERE
                   (
                     (
                       d.private = 0
                         OR (d.creatorfk='.$pnUserPk.' AND private=1)
                         OR (private=2 AND ('.$pnUserPk.' IN (SELECT loginfk FROM document_rights WHERE documentfk=d.documentpk AND rights=\''.$psRight.'\')))
                     )
                     AND (df.live=1)
                   )';
     }

    if(!empty($paCpValues))
    {
      $sQuery .= ' AND dl.cp_uid='.$this->oDB->dbEscapeString($paCpValues[CONST_CP_UID]);
      $sQuery .= ' AND dl.cp_action='.$this->oDB->dbEscapeString($paCpValues[CONST_CP_ACTION]);
      $sQuery .= ' AND dl.cp_type='.$this->oDB->dbEscapeString($paCpValues[CONST_CP_TYPE]);
      $sQuery .= ' AND dl.cp_pk='.$this->oDB->dbEscapeString($paCpValues[CONST_CP_PK]);
    }

    $sQuery.= ' ORDER BY df.date_creation DESC, d.date_update DESC';

    if(!empty($paPagination))
      $sQuery .= ' LIMIT '.(($paPagination['nPage']-1) * $paPagination['nNbItems']).','.$paPagination['nNbItems'];

    //dump($sQuery);
    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    $aOutput= array();

    if(!$bRead)
      return $aOutput;

    while($bRead)
    {
      $aOutput[$oDbResult->getFieldValue('documentpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $aOutput;
  }

  // Before a new file being inserted, uncheck the 'live' one
  // @param int $pnDocFk

  public function unsetLiveDocument($pnDocFk)
  {
   if(!assert('is_key($pnDocFk)'))
     return false;

   $sQuery = 'UPDATE document_file SET live=0 WHERE (live=1 AND documentfk='.$pnDocFk.')';

   $oResult = $this->oDB->ExecuteQuery($sQuery);

   if($oResult)
     $this->_logChanges(array('live' => 1, 'documentfk' => $pnDocFk), 'document_file', 'update document status');

   return $oResult;
  }

  // Setting a file live
  // @param int $pnDocumentFilePk

  public function setFileLive($pnDocumentFilePk, $pnDocumentFk)
  {
   if(!assert('is_key($pnDocumentFilePk)'))
     return false;

   if(!assert('is_key($pnDocumentFk)'))
     return false;

   $bUnset = $this->unsetLiveDocument($pnDocumentFk);
   if(!$bUnset)
     return false;

   $asAdd = array('document_filepk' => $pnDocumentFilePk, 'live' => 1);
   $bSet = $this->update($asAdd, 'document_file');

   if($bSet)
     $this->_logChanges($asAdd, 'document_file', 'update document status');

   return $bSet;
  }

  // Returns the number of documents related to a page
  // TO DO : place this function in the generic Model.php
  // Opportunity and Event components uses the same

  public function getCountFromCpValues($asValues)
  {
    if(!assert('is_cpValues($asValues)'))
     return 0;

    $sQuery = 'SELECT count(*) as nCount FROM `document_link`';
    $sQuery.= ' WHERE cp_uid ='.$this->oDB->dbEscapeString($asValues[CONST_CP_UID]);
    $sQuery.= ' AND cp_action ='.$this->oDB->dbEscapeString($asValues[CONST_CP_ACTION]);
    $sQuery.= ' AND cp_type='.$this->oDB->dbEscapeString($asValues[CONST_CP_TYPE]).' AND cp_pk='.$asValues[CONST_CP_PK];

    $oResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return 0;

    return $oResult->getFieldValue('nCount', CONST_PHP_VARTYPE_INT);
  }

  public function getCountFromFolder($pnFolderPk, $pnUserPk, $psRight='read')
  {
    if(!assert('is_key($pnFolderPk)'))
      return array();

    if(!assert('is_key($pnUserPk)'))
      return array();

    $sQuery = '
      SELECT COUNT(*) as nb
        FROM shared_document_folder_item si
        WHERE
                  (
                    (si.creatorfk='.$pnUserPk.' AND si.private=1)
                      OR si.private=0
                      OR (si.private=2 AND ('.$pnUserPk.' IN (SELECT loginfk FROM document_rights WHERE documentfk=si.documentpk AND rights=\''.$psRight.'\')))
                  )
        AND parentfolderfk='.$pnFolderPk;
    $oDbResult = $this->oDB->executeQuery($sQuery);

    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return 0;
    else
      return $oDbResult->getFieldValue('nb');
  }

  // Retrieves users who have been notified already

  public function getNotifiedUsers($pnDocumentFk)
  {
    if(!assert('is_key($pnDocumentFk)'))
      return array();

    $oNotified = $this->getByFk($pnDocumentFk, 'document_notification', 'document', 'loginfk');

    $bRead = $oNotified->readFirst();

    $aOutput= array();

    if(!$bRead)
      return $aOutput;
    else
    {
      while($bRead)
      {
        $aOutput[] = $oNotified->getFieldValue('loginfk');

        $bRead = $oNotified->readNext();
      }
      return $aOutput;
    }
  }
}
