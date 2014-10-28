<?php

class CCustomfieldsModelEx extends CCustomfieldsModel
{

  public function __construct()
  {
    return parent::__construct();
  }

  /*
   * Returns all custom fields
   * @return $oDbresult
   */

  public function getCustomFields()
  {
    $sQuery = 'SELECT *, COUNT(value) AS nbValues';
    $sQuery .= ' FROM customfield AS cf';
    $sQuery .= ' LEFT JOIN customfield_value AS cfv ON cfv.customfieldfk = cf.customfieldpk';
    $sQuery .= ' GROUP BY cf.customfieldpk';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  /*
   * Returns custom fields related to the component, action, type and pk
   * related in $cpValues
   * @param array $cpValues
   * @return $oDbresult
   */

  public function getCustomFieldsFromCpValues($paCpValues, $pnItemPk = 0)
  {

    if(!assert('is_cpValues($paCpValues) && is_integer($pnItemPk)'))
      return new CDbResult();

    $sLinkClause = $this->getComponentSql($paCpValues);

    if(!empty($pnItemPk))
      $sClause = ' AND cfv.itemfk = '.$pnItemPk;
    else
      $sClause = '';

    $sQuery = 'SELECT * FROM customfield as cf ';
    $sQuery.= ' LEFT JOIN customfield_link as cfl ON (cfl.customfieldfk = cf.customfieldpk '.$sLinkClause.')';
    $sQuery.= ' LEFT JOIN customfield_value as cfv ON (cfv.customfieldfk = cf.customfieldpk AND cfv.linkfk = cfl.customfield_linkpk '.$sClause.')';
    $sQuery.= ' WHERE 1 '.$sLinkClause;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }

  /*
   * Returns custom field linked to this this pk
   * @param int $pnPk
   * @return $oDbresult
   */

  public function getByPk($pvPk, $pnItemPk = 0, $pbWithValue = true)
  {
    if(!assert('is_key($pvPk) || is_arrayOfInt($pvPk)'))
      return array('error' => __LINE__.' pk not correct');

    if(!assert('is_integer($pnItemPk) && is_bool($pbWithValue)'))
      return array('error' => __LINE__.' parameters incorrect');

    $sItemClause = '';
    if(!empty($pnItemPk))
      $sItemClause.= ' AND cfl.cp_pk = '.$pnItemPk.' ';

    $sQuery = 'SELECT * FROM customfield as cf ';
    $sQuery.= ' LEFT JOIN customfield_link as cfl ON (cfl.customfieldfk = cf.customfieldpk '.$sItemClause.')';

    if(!$pbWithValue)
      $sQuery.= ' LEFT JOIN customfield_value as cfv ON (cfv.customfieldfk = cf.customfieldpk)';


    if(is_int($pvPk))
      $sQuery.= ' WHERE cf.customfieldpk = '.$this->oDB->dbEscapeString($pvPk);
    else
      $sQuery.= ' WHERE cf.customfieldpk IN ('.implode(',', $pvPk).') ';

    $sQuery.= ' '.$sItemClause;


    //echo $sQuery;
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oDbResult;
  }


  /*
   * Returns custom field for the requested item
   * If no result, send custom field settings without its value
   * @param int $pnItemFk
   * @param int $pnCustomFieldFk
   * @return $oDbresult
   */

  public function getByValuePk($pnItemFk, $pnCustomFieldFk=0)
  {
    if(!assert('is_key($pnItemFk)'))
      return array('error' => __LINE__.' itemFk not correct');

    $sQuery = 'SELECT * FROM customfield as cf ';
    $sQuery.= ' INNER JOIN customfield_value as cfv ON (cfv.customfieldfk = cf.customfieldpk AND cfv.customfield_valuepk = '.$pnItemFk.')';
    $sQuery.= ' INNER JOIN customfield_link as cfl ON (cfl.customfield_linkpk = cfv.linkfk)';
    $sQuery.= ' WHERE cfv.customfield_valuepk = '.$pnItemFk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return $this->getByPk($pnCustomFieldFk);

    return $oDbResult;
  }

  /*
   * Returns options for the value of the field
   * @param int $pnFk
   * @return $oDbresult
   */

  public function getOptionsByFieldFk($pvCustomfieldPk)
  {
    if(!assert('is_key($pvCustomfieldPk) || is_arrayOfInt($pvCustomfieldPk)'))
      return array('error' => __LINE__.' Customfield IDs not correct');

    if(is_integer($pvCustomfieldPk))
      $sQuery = 'SELECT * FROM customfield_option WHERE customfieldfk='.$pvCustomfieldPk;
    else
      $sQuery = 'SELECT * FROM customfield_option WHERE customfieldfk IN ('.implode(',', $pvCustomfieldPk).')';

    $oDbResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  /*
   * ADD a new custom field
   * @param strings $psLabel, $psDescription, $psFieldType, $psDefaultValue
   * @return int
   */

  public function addCustomField($psLabel, $psDescription, $psFieldType, $psDefaultValue='')
  {
    if(!assert('is_string($psFieldType) && !empty($psFieldType)'))
      return new CDbResult;

    $sQuery = 'INSERT INTO customfield (`customfieldpk`, `label`, `description`, `fieldtype`, `defaultvalue`) VALUES ';
    $sQuery.= '(NULL,'.$this->oDB->dbEscapeString($psLabel).','.$this->oDB->dbEscapeString($psDescription);
    $sQuery.= ', '.$this->oDB->dbEscapeString($psFieldType).','.$this->oDB->dbEscapeString($psDefaultValue).');';

    $oDbResult = $this->oDB->executeQuery($sQuery);
    if(!$oDbResult)
      return 0;

    return (int)$oDbResult->getFieldValue('pk');
  }

  /*
   * Link a custom field to a new cp values set
   * @param int $pnFk
   * @param array $paCpValues
   * @param bool $bAllItems
   * @return oDbResult
   */

  public function addCustomFieldLink($pnFk, $paCpValues, $bAllItems=false)
  {
    if(!assert('is_key($pnFk)'))
      return new CDbResult;

    if(!assert('is_cpValues($paCpValues)'))
      return new CDbResult;

    if($bAllItems)
      $paCpValues[CONST_CP_PK]=0;

    $sQuery = 'INSERT INTO customfield_link (`customfieldfk`,`cp_uid`, `cp_action`, `cp_type`, `cp_pk`) VALUES ';
    $sQuery.= '('.$pnFk.','.$this->oDB->dbEscapeString($paCpValues[CONST_CP_UID]).', '.$this->oDB->dbEscapeString($paCpValues[CONST_CP_ACTION]);
    $sQuery.= ', '.$this->oDB->dbEscapeString($paCpValues[CONST_CP_TYPE]).', '.$this->oDB->dbEscapeString($paCpValues[CONST_CP_PK]).')';

    $oDbResult = $this->oDB->executeQuery($sQuery);
    if(!$oDbResult)
      return 0;

    return (int)$oDbResult->getFieldValue('pk');
  }

  /*
   * ADD options to a custom field
   * @param int $pnFk
   * @param array $paOptionsValues
   * @param array $paOptionsLabels
   * @return bool
   */

  public function addCustomFieldOptions($pnFk, $paOptionsValues, $paOptionsLabels)
  {
    if(!assert('is_key($pnFk)'))
      return new CDbResult;

    $sQuery = "INSERT INTO customfield_option (`customfield_optionpk`, `customfieldfk`, `label`, `value`) VALUES (";

    $nCount = 0;
    foreach ($paOptionsValues as $aRow)
    {
      if($nCount>0)
        $sQuery.=', (';

      $sQuery.='NULL, '.$pnFk.', '.$this->oDB->dbEscapeString($paOptionsLabels[$nCount]);
      $sQuery.=', '.$this->oDB->dbEscapeString($aRow).')';

      $nCount++;
    }

    $oDbResult = $this->oDB->executeQuery($sQuery);

    return $oDbResult;
  }

  /*
   * INSERT a new value for an item identified by its CpValues
   * to a custom field of $pnPk
   * @param integer $pnCustomfieldPk
   * @param integer $nLinkPk
   * @param integer $nItemPk
   * @param variant $sNewValue
   * @param integer new row ID
   */

  public function addCustomFieldValue($pnCustomfieldPk, $nLinkPk, $nItemPk, $psValue = '')
  {
    if(!assert('is_key($pnCustomfieldPk) && is_key($nLinkPk) && is_key($nItemPk)'))
      return 0;

    $sQuery = 'INSERT INTO customfield_value (`customfieldfk`,`linkfk`, `itemfk`, `value`) ';
    $sQuery.= 'VALUES ('.$pnCustomfieldPk.', '.$nLinkPk.', '.$nItemPk.', '.$this->oDB->dbEscapeString($psValue).')';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    if(!$oDbResult)
      return 0;

    return (int)$oDbResult->getFieldValue('pk');
  }

  /*
   * UPDATE the value of an item
   * @param int $pnPk
   * @param string $psValue
   */

  public function updateCustomFieldValue($pnPk, $psValue='')
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult;

    if(!assert('is_string($psValue)'))
      return new CDbResult;

    $sQuery = 'UPDATE customfield_value SET value='.$this->oDB->dbEscapeString($psValue);
    $sQuery.= ' WHERE customfield_valuepk='.$pnPk;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    return $oDbResult;
  }

  public function deleteFromCpValues($pasValues)
  {
    if(!assert('is_cpValues($pasValues)'))
      return false;

    $oDbResult = $this->getCustomFieldsFromCpValues($pasValues);
    $bRead = $oDbResult->readFirst();

    while($bRead)
    {
      $nFk = (int)$oDbResult->getFieldValue('customfieldpk');
      $this->deleteByFk($nFk, 'customfield_link', 'customfield');
      $this->deleteByFk($nFk, 'customfield_value', 'customfield');
      $this->deleteByFk($nFk, 'customfield_option', 'customfield');
      $this->deleteByPk($nFk, 'customfield');

      $bRead = $oDbResult->readNext();
    }

    return true;
  }


  /**
   * Return the list of CFlinks matching the compoonent parameters. If $pnCusomfieldPk is passed, it will only bring the link to THE CF
   * @param array $pasCpValues
   * @param integer $pnCusomfieldPk
   * @return array
   */
  public function getCustomfieldValue($pnCFPk, $pnCFLinkPk, $pnItemPk = 0)
  {
    if(!assert('is_key($pnCFPk) && is_key($pnCFLinkPk) && is_integer($pnItemPk)'))
      return array();

    $sQuery = 'SELECT * FROM customfield as cf
                INNER JOIN customfield_link as cfl ON (cfl.customfieldfk = cf.customfieldpk AND cfl.customfield_linkpk = '.$pnCFLinkPk.')
                LEFT JOIN customfield_value as cfv ON (cfv.customfieldfk = cfl.customfieldfk AND cfv.linkfk = cfl.customfield_linkpk AND cfv.itemfk = '.$pnItemPk.')
                WHERE cf.customfieldpk = '.$pnCFPk;

    //echo $sQuery;
    $oDbResult =  $this->oDB->ExecuteQuery($sQuery);
    $oDbResult->readFirst();

    return $oDbResult->getData();
  }

  /**
   * Generate customfield_link sql clause based on component params
   * @param array $pasCpValues
   * @return string sql
   */
  private function getComponentSql($pasCpValues)
  {
    if(!assert('is_cpValues($pasCpValues)'))
      return '';

    $sClause = '';
    if(!empty($pasCpValues[CONST_CP_ACTION]))
      $sClause.= ' AND cfl.cp_action = '.$this->oDB->dbEscapeString($pasCpValues[CONST_CP_ACTION]);

    if(!empty($pasCpValues[CONST_CP_TYPE]))
      $sClause.= ' AND  cfl.cp_type = '.$this->oDB->dbEscapeString($pasCpValues[CONST_CP_TYPE]);

    if($pasCpValues[CONST_CP_PK] > 0)
      $sClause.= ' AND (cfl.cp_pk = '.$pasCpValues[CONST_CP_PK].' OR cfl.cp_pk = 0)';
    else
      $sClause.= ' AND cfl.cp_pk = 0';

    return $sClause;
  }

}