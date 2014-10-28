<?php

require_once('common/lib/model.class.php5');

class CManageablelistModelEx extends CManageablelistModel
{

  public function __construct()
  {
    parent::__construct();

    return true;
  }

  /**
   *Return an array with the element from the lists.
   * Ele;emnts could be anything since we can save serialized values
   *
   * @param integer $pnListPk ID of the list
   * @param string $psShortname name of the list (working with name may be easier in case of DB migration)
   * @return array of misc elements
   */

  public function getManageableList($psShortname = '')
  {
    if(!assert('is_string($psShortname)'))
      return array();

    $sQuery = 'SELECT * FROM manageable_list as ml ';
    $sQuery.= 'INNER JOIN manageable_list_item as mli ON (mli.manageable_listfk = ml.manageable_listpk) ';

    if(!empty($psShortname))
      $sQuery.= ' WHERE ml.shortname = '.$this->oDB->dbEscapeString($psShortname);

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asList = array();
    while($bRead)
    {
      $vValue = $oDbResult->getFieldValue('value');

      $asList[$oDbResult->getFieldValue('shortname')][$oDbResult->getFieldValue('label')] = $vValue;
      $bRead = $oDbResult->readNext();
    }

    return $asList;
  }

}
