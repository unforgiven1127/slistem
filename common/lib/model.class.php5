<?php
class CModel
{
  //==============================================
  //==============================================
  // Generic class for database managment

  public $oDB = null;
  public $cnUserPk = 0;

  protected $csTableName = '';
  protected $csComponent = '';

  protected $_dbMap = array();
  protected $_tableMap = array();
  protected $casFieldToLog = array();

  protected $cbGlobal = false;
  protected $casError = array();

  public function __construct($pbGlobal = false)
  {
    //$this->_dbMap = loadDbMap();
    $this->oDB = CDependency::getComponentByName('database');
    $this->_initMap();
    $this->cbGlobal = $pbGlobal;
  }

  // ================================================================
  // Accessors
  // ================================================================

  public function getErrors($pbAsString = false)
  {
    if($pbAsString)
      return implode(', ', $this->casError);

    return $this->casError;
  }

  protected function _testFields($avFields, $psTablename, $pbAllFieldRequired = true, $pbAllowExtra = true, $psAction = 'add')
  {

    if(!assert('!empty($psTablename)'))
      return false;

    $asTreated = array();

    //for every field passed in $avFields, i apply a control (if exists)
    foreach ($avFields as $sFieldName => $avFieldValue)
    {
      if(isset($this->_tableMap[$psTablename][$sFieldName]))
      {
        if(is_array($avFieldValue))
        {
          foreach($avFieldValue as $vKey => $sValue)
          {
            //apply all the controls defined
            foreach($this->_tableMap[$psTablename][$sFieldName]['controls'] as $sControl)
            {
              //dump('$avFieldValue["'.$vKey.'"]');
              //dump($avFieldValue["$vKey"]. ' || '.$sControl);

              $sAssert = str_replace('%', '$avFieldValue["'.$vKey.'"]', $sControl);
              if(!assert($sAssert.'; /* field: '.$sFieldName.' - control: '.$sControl.' - value: '.var_export($avFieldValue[$vKey], true).' */ ', '<pre>'.var_export($avFields, true).'</pre>'))
                return false;
            }

            $asTreated[] = $sFieldName;
          }
        }
        elseif(isset($this->_tableMap[$psTablename][$sFieldName]['controls']))
        {
          foreach($this->_tableMap[$psTablename][$sFieldName]['controls'] as $sControl)
          {
            $sAssert = str_replace('%', '$avFieldValue', $sControl);
            // var_export($sControl); echo "\n";
            if(!assert($sAssert.'; // field: '.$sFieldName.' - control: '.$sControl.' - value: '.var_export($avFieldValue, true)))
            {
              //dump($avFields[$sFieldName]);
              return false;
            }
          }

          $asTreated[] = $sFieldName;
        }
      }
    }

    //control that all the fields in the db column are in the $avFields array
    if($pbAllFieldRequired)
    {
      $asFieldsKey = array_keys($this->_tableMap[$psTablename]);
      $asFields = array_intersect($asFieldsKey, $asTreated);

      if(count($asFields) < count($this->_tableMap[$psTablename]))
      {
        assert('false; // Missing fields [ '.$psTablename.' / required: '.implode(', ', $asFieldsKey).' || provided: '.implode(', ', $asFields).']');
        return false;
      }
    }

    //if the array is supposed to match exactly the database fields, we add another control
    if(!$pbAllowExtra && count(array_diff($asTreated, array_keys($avFields)) > 0))
    {
      assert('false; // There are more fields in the query than in the database. [ '.$psTablename.' /'.implode(', ', array_keys($avFields)).']');
      return false;
    }

    return true;
  }


  protected function _initMap()
  {
    return true;
  }


  // ----------------------------------------------------------------------------
  // Builds Sql UPDATE request
  // $pasValues must be a two dimension array of key => value
  // The WHERE clause is set by default to tablepk = $pasValues['tablepk']
  // @param $pasValues array
  // @param $psTable string
  // @param $psWhere string
  // ----------------------------------------------------------------------------
  public function update($pasValues, $psTable, $psWhere = '', $pbReturnRes = false)
  {
    if(!assert('is_array($pasValues)'))
      return false;

    if(!assert('is_string($psTable) && !empty($psTable)'))
      return false;

    if(!$this->cbGlobal)
    {
      if(!$this->_testFields($pasValues, $psTable, false, true, 'update'))
        return false;
    }

    if(trim($psWhere) == '' && (!isset($pasValues[$psTable.'pk']) || empty($pasValues[$psTable.'pk'])))
    {
      assert('false; // trying to update without pk');
      return false;
    }

    $asSql = array();

    foreach($pasValues as $sAttribute => $sValue)
    {
      if($this->cbGlobal || isset($this->_tableMap[$psTable][$sAttribute]))
      {
        if($sValue === null)
          $asSql[] = $sAttribute."=NULL";
        else
          $asSql[] = $sAttribute."=".$this->oDB->dbEscapeString($sValue);
      }
    }

    if(!assert('!empty($asSql)'))
      return false;


    $sQuery = 'UPDATE `'.$psTable.'` SET '. implode(', ', $asSql);

    if(trim($psWhere) == '')
      $sQuery.= ' WHERE `'.$psTable.'pk` = '.$pasValues[$psTable.'pk'];
    else
      $sQuery.= ' WHERE '.$psWhere;

    //echo $sQuery;
    $oDBResult = $this->oDB->ExecuteQuery($sQuery);
    if(!$oDBResult)
      return false;

    $this->_logChanges($pasValues, $psTable, 'upd '.$psTable);

    if($pbReturnRes)
      return $oDBResult;

    return true;
  }


  // ----------------------------------------------------------------------------
  // Increments a field in the specified table
  // ----------------------------------------------------------------------------

  public function increment($pnPk, $psTable, $psField)
  {
    if(!assert('is_key($pnPk)'))
      return false;

    if(!assert('is_string($psTable) && !empty($psTable)'))
      return false;

    if(!assert('is_string($psField) && !empty($psField)'))
      return false;

    $sQuery = 'UPDATE `'.$psTable.'` SET `'.$psField.'` = ('.$psField.'+1)
                WHERE `'.$psTable.'pk` = '.$pnPk;

    $oDBResult = $this->oDB->ExecuteQuery($sQuery);
    if(!$oDBResult)
      return false;

    return true;
  }

  // ----------------------------------------------------------------------------
  // Builds Sql INSERT request
  // Allows multiple values, $pasValues can be set up in the two following ways :
  // $pasValues['attribute'] = 'value';
  // $pasValues['attribute'] = array ('value 1', 'value 2')
  // ----------------------------------------------------------------------------

  public function add($pasValues, $psTable)
  {
    if(!assert('is_array($pasValues)'))
      return 0;

    if(!assert('is_string($psTable) && !empty($psTable)'))
      return 0;

    if(!$this->_testFields($pasValues, $psTable, false, true, 'add'))
      return 0;

    $sQuery= 'INSERT INTO `'.$psTable.'` ';

    $sAttributesSql = $sValuesSql = '';
    $aAttributesTab = $aValuesTab = array();

    $nCount=0;
    //dump('----');
    //dump($this->_tableMap[$psTable]);

    foreach($pasValues as $sAttribute => $aValues)
    {
      if(isset($this->_tableMap[$psTable][$sAttribute]))
      {
        $aAttributesTab[] = $sAttribute;

        /*if($aValues === null)
        {
          $aValuesTab[0][$nCount] = 'NULL';
        }
        else
        {*/

          if(!is_array($aValues))
          {
            $aValuesTab[0][$nCount] = $this->oDB->dbEscapeString($aValues);
          }
          else
          {
            $nCountb = 0;
            foreach($aValues as $sValue)
            {
              $aValuesTab[$nCountb][$nCount] = $this->oDB->dbEscapeString($sValue);
              $nCountb++;
            }
          }
        //}

        $nCount++;
      }
    }

    //dump($aValuesTab);

    if(empty($aValuesTab))
    {
      assert('false; // no data to put in add query ');
      return 0;
    }

    $aValuesRowTab = array();
    foreach($aValuesTab as $aValuesRow)
      $aValuesRowTab[] = '('.implode(',', $aValuesRow).')';

    $sValuesSql = implode(',',$aValuesRowTab);
    $sAttributesSql = '('.implode(',',$aAttributesTab).')';
    $sQuery.= $sAttributesSql." VALUES ".$sValuesSql;

    //echo $sQuery;
    $oDBResult = $this->oDB->ExecuteQuery($sQuery);
    if(!$oDBResult)
      return 0;

    $this->_logChanges($pasValues, $psTable, 'add '.$psTable);

    if(is_object($oDBResult))
    {
      $pasValues['pk'] = (int)$oDBResult->getFieldValue('pk');
      return (int)$oDBResult->getFieldValue('pk');
    }

    return true;
  }

  // ----------------------------------------------------------------------------
  // Various Sql SELECT requests
  // ----------------------------------------------------------------------------

  public function getByFk($pnFk, $psTable, $psTableFk, $psField='*', $psOrderBy = '')
  {
    if(!assert('is_key($pnFk)'))
      return new CDbResult;

    if(!assert('is_string($psTable) && !empty($psTable)'))
      return new CDbResult;

    if(!assert('is_string($psTableFk) && !empty($psTableFk)'))
      return new CDbResult;

    if(!assert('is_string($psField) && !empty($psField)'))
      return new CDbResult;

    if(!assert('is_string($psOrderBy)'))
      return new CDbResult;

    $sQuery= 'SELECT '.$psField.' FROM `'.$psTable.'` WHERE '.$psTableFk.'fk ='.$pnFk;
    if(!empty($psOrderBy))
      $sQuery.= ' ORDER BY '.$psOrderBy;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  public function getByPk($pnPk, $psTable, $psField='*')
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult;

    if(!assert('is_string($psTable) && !empty($psTable)'))
      return new CDbResult;

    if(!assert('is_string($psField) && !empty($psField)'))
      return new CDbResult;

    $sQuery= 'SELECT '.$psField.' FROM `'.$psTable.'` WHERE '.$psTable.'pk ='.$pnPk;
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  public function getByWhere($psTable, $psWhere = '1', $psField = '*', $psOrder = '', $psLimit = '')
  {
    if(!assert('is_string($psTable) && !empty($psTable)'))
      return new CDbResult;

    if(!assert('is_string($psField) && !empty($psField)'))
      return new CDbResult;

    if(!assert('is_string($psWhere) && !empty($psWhere)'))
      return new CDbResult;

    if(!assert('empty($psOrder) || is_string($psOrder)'))
      return new CDbResult;

    $sQuery= 'SELECT '.$psField.' FROM `'.$psTable.'` WHERE '.$psWhere;

    if (!empty($psOrder))
      $sQuery.=' ORDER BY '.$psOrder;

    if (!empty($psLimit))
      $sQuery.=' LIMIT '.$psLimit;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
  }

  public function getList($psTable, $psField='*')
  {
    if(!assert('is_string($psTable) && !empty($psTable)'))
      return new CDbResult;

    if(!assert('is_string($psField) && !empty($psField)'))
      return new CDbResult;

    $sQuery= 'SELECT '.$psField.' FROM `'.$psTable.'`';
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    $bRead = $oDbResult->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oDbResult;
   }

  // ----------------------------------------------------------------------------
  // Various Sql DELETE requests
  // ----------------------------------------------------------------------------

  public function deleteByPk($pnPk, $psTable)
  {
    if(!assert('is_key($pnPk)'))
      return new CDbResult;

    if(!assert('is_string($psTable) && !empty($psTable)'))
      return new CDbResult;

    $sQuery = 'DELETE FROM `'.$psTable.'` WHERE '.$psTable.'pk='.$pnPk;
    $this->oDB->ExecuteQuery($sQuery);

    $this->_logChanges(array('pk' => $pnPk), $psTable, 'del '.$psTable);

    return true;
  }

  public function deleteByFk($pnFk, $psTable, $psTableFk)
  {
    if(!assert('is_key($pnFk)'))
      return false;

    if(!assert('is_string($psTable) && !empty($psTable)'))
      return false;

    if(!assert('is_string($psTableFk) && !empty($psTableFk)'))
      return false;

    if(strtolower(substr($psTableFk, -2, 2)) == 'fk')
      $sQuery = 'DELETE FROM `'.$psTable.'` WHERE '.$psTableFk.' = '.$pnFk;
    else
      $sQuery = 'DELETE FROM `'.$psTable.'` WHERE '.$psTableFk.'fk = '.$pnFk;

    $this->_logChanges(array('fk' => $pnFk), $psTable, 'del '.$psTable);

    $this->oDB->ExecuteQuery($sQuery);

    return true;
  }


  public function deleteByWhere($psTable, $psWhere)
  {
    if(!assert('is_string($psTable) && !empty($psTable)'))
      return false;

    if(!assert('is_string($psWhere) && !empty($psWhere)'))
      return false;

    $sQuery= 'DELETE FROM '.$psTable.' WHERE '.$psWhere;
    /*if(!$this->oDB->ExecuteQuery($sQuery))
      return false;

    return true;
     * */
     return $this->oDB->ExecuteQuery($sQuery);
  }


  public function _logChanges($pasData, $psTable = '', $psAction = '', $psShortId = '', $pasComponent = array())
  {
    global $gasLogBlackList;

    if(!CONST_SYTEM_LOG_ACTIVE)
      return true;

    $oLogin = CDependency::getCpLogin();
    $nUserPk = $oLogin->getUserPk();

    if(!assert('!empty($pasData) && !empty($nUserPk)'))
      return false;

    $oPage = CDependency::getCpPage();

    if(empty($pasComponent))
      $pasComponent = $oPage->getRequestedComponent();

    if(in_array($pasComponent[CONST_CP_UID], $gasLogBlackList))
      return true;

    if(empty($psShortId))
      $psShortId = implode('_', $pasComponent);


    // DAL level logs, we log everything
    // Check if there's a recent and identical entry
    $sQuery = 'SELECT login_system_historypk FROM login_system_history WHERE ';
    $sQuery.= '`userfk` =  "'.$nUserPk.'" AND `action` = '.$this->oDB->dbEscapeString($psAction);
    $sQuery.= ' AND `component` = '.$this->oDB->dbEscapeString($psShortId).' AND `cp_uid` = '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_UID]).' ';
    $sQuery.= ' AND `cp_action` = '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_ACTION]).' AND `cp_type` = '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_TYPE]).' ';
    $sQuery.= ' AND `cp_pk` = '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_PK]).' AND `uri` = '.$this->oDB->dbEscapeString($oPage->getRequestedUrl()).' ';
    $sQuery.= ' AND `date` > "'.date('Y-m-d H:i:s', strtotime('-2 minutes')).'" ';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    if($oDbResult->numRows() > 0)
    {
      return true;
    }

    if(!isset($pasData['log_detail']))
      $pasData['log_detail'] = 'null';
    else
      $pasData['log_detail'] = $this->oDB->dbEscapeString($pasData['log_detail']);


    $sQuery = 'INSERT INTO `login_system_history` (`date` ,`userfk` ,`action` , `table`, `component`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `uri` ,`value`, `description`)
    VALUES ("'.date('Y-m-d H:i:s').'", "'.$nUserPk.'", '.$this->oDB->dbEscapeString($psAction).', '.$this->oDB->dbEscapeString($psTable).',
    '.$this->oDB->dbEscapeString($psShortId).', '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_UID]).',
    '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_ACTION]).', '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_TYPE]).',
    '.$this->oDB->dbEscapeString($pasComponent[CONST_CP_PK]).', '.$this->oDB->dbEscapeString($oPage->getRequestedUrl()).',
    '.$this->oDB->dbEscapeString(var_export($pasData, true)).', '.$pasData['log_detail'].') ';

    $this->oDB->ExecuteQuery($sQuery);
    return true;
  }


  public function loadQueryBuilderClass()
  {
    require_once(__DIR__.'/querybuilder.class.php5');
  }
  public function getQueryBuilder($pbDebug = false)
  {
    require_once(__DIR__.'/querybuilder.class.php5');
    return new CQueryBuilder($pbDebug);
  }

  public function ExecuteQuery($psQuery)
  {
    return $this->oDB->ExecuteQuery($psQuery);
  }

  public function dbEscapeString($pvValue, $pvDefault = '', $pbNoQuotes = false)
  {
    return $this->oDB->dbEscapeString($pvValue, $pvDefault, $pbNoQuotes);
  }

  public function formatOdbResult($poDbResult, $psKey = '')
  {
    if(!assert('is_object($poDbResult)'))
      return array();

    if(!assert('is_string($psKey)'))
      return array();

    $aData = array();

    $bRead = $poDbResult->readFirst();
    if(!$bRead)
      return array();
    else
    {
      while($bRead)
      {
        if(empty($psKey))
          $aData[]=$poDbResult->getData();
        else
          $aData[$poDbResult->getFieldValue($psKey)] = $poDbResult->getData();

        $bRead = $poDbResult->readNext();
      }
    }
    return $aData;
  }

}