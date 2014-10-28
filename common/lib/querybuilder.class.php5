<?php

class CQueryBuilder
{
  private $casUserRight = array();
  private $cnUserPk = 0;
  private $cbIsAdmin = false;
  private $csSearchTitle = '';
  private $csDataType = '';

  private $casSQL = array('select' => array(), 'inner' => array(), 'left' => array(), 'outer' => array(), 'group' => array(), 'limit' => array());
  private $casAddedRight = array();

  private $cbDebug = false;


  function __construct($pbDebug = false)
  {
    if(!assert('is_bool($pbDebug)'))
      return false;

    $oLogin = CDependency::getCpLogin();
    $oRight = CDependency::getComponentByName('right');
    $this->cnUserPk = $oLogin->getUserPk();
    $this->cbIsAdmin = $oLogin->isAdmin();

    if(!assert('!empty($this->cnUserPk) && !empty($oRight)'))
      return false;

    $this->cbDebug = $pbDebug;

    $this->resetQuery();
    $this->casUserRight = $oRight->getUserDataRights();

  }



  /* ***************************************************************** */
  /* ***************************************************************** */
  /* ***************************************************************** */
  //accessors

  public function getRawSqlData()
  {
    return $this->casSQL;
  }



  /* ***************************************************************** */
  /* ***************************************************************** */
  /* ***************************************************************** */
  //Private methods


  private function _trace($psString)
  {
    if($this->cbDebug)
      dump($psString);

    return true;
  }

  /**
   * check if there are userData rights/restrictions for this table, and add it to the query array
   * !! don't add any right restriction if I'm the admin/cron user
   * @param string $psTableName
   * @return boolean
   */
  private function _addRightFor($psTableName)
  {
    if(!assert('!empty($psTableName)') || $this->cbIsAdmin)
      return false;

    //do nothing if no rights or right already added for this table
    if(!isset($this->casUserRight[$psTableName]) || isset($this->casAddedRight[$psTableName]))
    {
      $this->casAddedRight[$psTableName] = $psTableName;
      return true;
    }

    if(!empty($this->csDataType))
    {
      //If a datatype is specified, we only load the rights for a specific data type
      /*dump($this->csDataType);
      dump($this->casUserRight);
      dump($this->casUserRight[$psTableName]['cp_type']);*/

      if($this->casUserRight[$psTableName]['cp_type'] != $this->csDataType)
        return true;
    }

    //!!!!!! TODO !!!!!!!!!!
    // make sure it mixes clause and not joining multiple times the same table!!!

    $this->casAddedRight[$psTableName] = $psTableName;

    if(!empty($this->casUserRight[$psTableName]['select']))
    {
      //dump($this->casUserRight[$psTableName]['select']);
      $this->casSQL['select'] = array_merge_recursive($this->casSQL['select'], $this->casUserRight[$psTableName]['select']);
    }

    if(!empty($this->casUserRight[$psTableName]['left']))
    {
      //dump($this->casUserRight[$psTableName]);
      // if the join right concern the main table, ignore the join
      if($psTableName!= $this->casSQL['table'] && !empty($this->casUserRight[$psTableName]['left']['where']))
        $this->casSQL['left'] = array_merge_recursive($this->casSQL['left'], $this->casUserRight[$psTableName]['left']);
    }

    if(!empty($this->casUserRight[$psTableName]['inner']) && !empty($this->casUserRight[$psTableName]['inner']['where']))
    {
      if($psTableName != $this->casSQL['table'])
        $this->casSQL['inner'] = array_merge_recursive($this->casSQL['inner'], $this->casUserRight[$psTableName]['inner']);
    }

    if(!empty($this->casUserRight[$psTableName]['outer']) && !empty($this->casUserRight[$psTableName]['outer']['where']))
    {
      if($psTableName != $this->casSQL['table'])
        $this->casSQL['outer'] = array_merge_recursive($this->casSQL['outer'], $this->casUserRight[$psTableName]['outer']);
    }

    if(!empty($this->casUserRight[$psTableName]['where']))
      $this->casSQL['where'] = array_merge_recursive($this->casSQL['where'], (array)$this->casUserRight[$psTableName]['where']);

    if(!empty($this->casUserRight[$psTableName]['group']))
      $this->casSQL['group'] = array_merge_recursive($this->casSQL['group'], $this->casUserRight[$psTableName]['group']);

    if(!empty($this->casUserRight[$psTableName]['limit']))
      $this->casSQL['limit'] = array_merge_recursive((array)$this->casSQL['limit'], $this->casUserRight[$psTableName]['limit']);

    //dump($this->casSQL);
    return true;
  }


  /* ***************************************************************** */
  /* ***************************************************************** */
  /* ***************************************************************** */
  //Public methods

  public function resetQuery()
  {
    $this->casSQL = array('table' => '', 'alias' => '', 'select' => array(), 'left' => array(), 'inner' => array(), 'outer' => array(),
      'where' => array(), 'order' => array(), 'group' => array('select' => array(), 'count' => array()), 'limit' => array() );

    return true;
  }


  public function setDataType($psType)
  {
    return $this->csDataType = $psType;
  }


  public function setTable($psTable, $psAlias = '')
  {
    if(!assert('!empty($psTable)'))
      return false;

    if(empty($psAlias))
      $psAlias = $this->getAlias($psTable);

    $this->casSQL['alias'] = $psAlias;
    return $this->casSQL['table'] = $psTable;
  }


  public function addSelect($pvSelect)
  {
    if(!assert('!empty($pvSelect)'))
      return false;

    if(is_array($pvSelect))
    {
      $this->casSQL['select'] = array_merge($this->casSQL['select'], $pvSelect);
    }
    else
      $this->casSQL['select'][] = $pvSelect;

    $this->casSQL['select'] = array_unique($this->casSQL['select']);
    return true;
  }

  public function addCountSelect($pvSelect)
  {
    if(!assert('!empty($pvSelect)'))
      return false;

    if(is_array($pvSelect))
    {
      $this->casSQL['count_select'] = array_merge($this->casSQL['count_select'], $pvSelect);
    }
    else
      $this->casSQL['count_select'][] = $pvSelect;

    $this->casSQL['count_select'] = array_unique($this->casSQL['count_select']);
    return true;
  }

  public function addWhere($pvWhere, $psOperator = 'AND')
  {
    if(!assert('!empty($pvWhere)'))
      return false;

    if($psOperator != 'AND' && $psOperator != 'OR' && $psOperator != '')
    {
      assert('false; // operator must be AND, OR or nothing');
      return false;
    }

    if(is_array($pvWhere))
    {
      foreach($pvWhere as $sWhere)
      {
        $this->casSQL['where'][] = ' '.$psOperator.' '.$sWhere;
      }
    }
    else
      $this->casSQL['where'][] = ' '.$psOperator.' '.$pvWhere;

    $this->casSQL['where'] = array_unique($this->casSQL['where']);
    return true;
  }

  public function hasWhere()
  {
    return (bool)count($this->casSQL['where']);
  }

  public function setWhere($pasWhere)
  {
    return $this->casSQL['where'] = (array)$pasWhere;
  }

  public function hasSelect()
  {
    return (bool)count($this->casSQL['select']);
  }

  public function addOrder($pvOrder)
  {
    if(!assert('!empty($pvOrder)'))
      return false;

    if(is_array($pvOrder))
    {
      $this->casSQL['order'] = array_merge($this->casSQL['order'], $pvOrder);
    }
    else
      $this->casSQL['order'][] = $pvOrder;

    $this->casSQL['order'] = array_unique($this->casSQL['order']);
    return true;
  }
  public function setOrder($pvOrder)
  {
    if(!assert('!empty($pvOrder)'))
      return false;

    if(is_array($pvOrder))
    {
      $this->casSQL['order'] = $pvOrder;
    }
    else
      $this->casSQL['order'] = array($pvOrder);

    $this->casSQL['order'] = array_unique($this->casSQL['order']);
    return true;
  }

  public function hasOrder()
  {
    return (bool)count($this->casSQL['order']);
  }

  public function addGroup($pvGroup, $pbGrpCountQuery = true)
  {
    if(!assert('!empty($pvGroup)'))
      return false;

    if(is_array($pvGroup))
    {
      $this->casSQL['group']['select'] = array_merge($this->casSQL['group']['select'], $pvGroup);
    }
    else
      $this->casSQL['group']['select'][] = $pvGroup;

    $this->casSQL['group']['select'] = array_unique($this->casSQL['group']['select']);

    if($pbGrpCountQuery)
    {
      if(is_array($pvGroup))
      {
        $this->casSQL['group']['count'] = array_merge($this->casSQL['group']['count'], $pvGroup);
      }
      else
        $this->casSQL['group']['count'][] = $pvGroup;

      $this->casSQL['group']['count'] = array_unique($this->casSQL['group']['count']);
    }

    return true;
  }

  public function addLimit($psLimit)
  {
    if(!assert('!empty($psLimit) && (is_string($psLimit) || is_integer($psLimit))'))
      return false;

    $this->casSQL['limit'] = $psLimit;
    return true;
  }

  public function getLimit()
  {
    return $this->casSQL['limit'];
  }
  public function hasLimit()
  {
    return (bool)count($this->casSQL['limit']);
  }

  public function addJoin($psType, $psTable, $psAlias, $psJoin)
  {
    if(!assert('($psType == "left" || $psType == "inner" || $psType == "outer")'))
      return false;

    if(!assert('!empty($psTable) && !empty($psJoin)'))
      return false;

    if(empty($psAlias))
      $psAlias = $this->getAlias($psTable);

    $sSql = strtoupper($psType).' JOIN '.strtolower($psTable).' as '.$psAlias.' ON ('.$psJoin.') ';

    $asJoin = array('table' => $psTable, 'alias' => $psAlias, 'clause' => $psJoin, 'sql' => $sSql);
    $this->casSQL[$psType][] = $asJoin;

    return true;
  }


  /**
   * Generate standardized alias based on table name (unique so far for all existing tables)
   *
   * @param string $psTableName
   * @return string alias
   */
  public function getAlias($psTableName)
  {
    if(!assert('!empty($psTableName)'))
      return '';

    $asName = explode('_', $psTableName);
    $nWords = count($asName);

    if($nWords >= 3)
      return substr($asName[0], 0, 1).substr($asName[1], 0, 1).substr($asName[2], 0, 2);

    if($nWords == 2)
      return substr($asName[0], 0,1).substr($asName[1], 0, 3);

    return substr($asName[0], 0, 4);
  }


  public function getSqlArray()
  {
    return $this->casSQL;
  }


  /**
   *
   * @return string The query including conditions and join related to users access rights
   */
  public function getSql($pbCountQuery = false)
  {
    if(!assert('!empty($this->casSQL["table"]) && !empty($this->casSQL["alias"]) && !empty($this->casSQL["select"])'))
      return '';


    //fetch right conditions for main table
    $this->_addRightFor($this->casSQL['table'], $this->casSQL['alias']);


    //fetch && add to the array the right conditions for all left joined table
    if(!empty($this->casSQL['left']))
    {
      foreach($this->casSQL['left'] as $nkey => $asJoinData)
      {
        if(!empty($asJoinData['table']))
        {
          $this->_addRightFor($asJoinData['table']);
        }
      }
    }

    ///fetch && add to the array the right conditions for all inner joined table
    if(!empty($this->casSQL['inner']))
    {
      foreach($this->casSQL['inner'] as $nkey => $asJoinData)
      {
        if(!empty($asJoinData['table']))
          $this->_addRightFor($asJoinData['table']);
      }
    }

    //fetch && add to the array the right conditions for all inner joined table
    if(!empty($this->casSQL['outer']))
    {
      foreach($this->casSQL['outer'] as $nkey => $asJoinData)
      {
        if(!empty($asJoinData['table']))
          $this->_addRightFor($asJoinData['table']);
      }
    }

    //----------------------------------------
    // Do the loops again because the rights may have added left, inner outer and where clause everywhere
    // Order is important!!! if a table has to be join with different types of join, inner has priority.
    // outer has the lowest since it's rarely used
    $asJoin = array();

    foreach($this->casSQL['outer'] as $asJoinData)
    {
      if(!empty($asJoinData))
      {
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['type'] = 'outer';
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['alias'] = $asJoinData['alias'];
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['clause'][] = $asJoinData['clause'];
      }
    }

    foreach($this->casSQL['left'] as $asJoinData)
    {
      if(!empty($asJoinData))
      {
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['type'] = 'left';
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['alias'] = $asJoinData['alias'];
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['clause'][] = $asJoinData['clause'];
      }
    }

    foreach($this->casSQL['inner'] as $asJoinData)
    {
      if(!empty($asJoinData))
      {
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['type'] = 'inner';
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['alias'] = $asJoinData['alias'];
        $asJoin[$asJoinData['table'].' as '.$asJoinData['alias']]['clause'][] = $asJoinData['clause'];
      }
    }


    if($pbCountQuery)
    {
      $this->casSQL['count_select'] = array_unique($this->casSQL['count_select']);
      $sQuery = 'SELECT '.implode(', ', $this->casSQL['count_select']).' FROM `'.$this->casSQL['table'].'` as '.$this->casSQL['alias'].' ';
    }
    else
    {
      $this->casSQL['select'] = array_unique($this->casSQL['select']);
      $sQuery = 'SELECT '.implode(', ', $this->casSQL['select']).' FROM `'.$this->casSQL['table'].'` as '.$this->casSQL['alias'].' ';
    }

    if(!empty($asJoin))
    {
      // !!!!!!!!!
      // !!!!!!!!! CANT DO IT... mixing up joins using fields that are potentially not joined yet
      //reverse array to put inner join first and improve query efficiency
      //$asJoin = array_reverse($asJoin);
      //Need a smarter way to sort those joins

      // ====================================================================
      //treat all joins to generate sql and manage dependencies

      $asFullJoin = $asDependency = $asRank = array();
      foreach($asJoin as $sTable => $asCondition)
      {
        $asMatch = array();
        $sKey = $asCondition['alias'];
        if($asCondition['type'] == 'inner')
          $nWeight = 10;
        else
          $nWeight = 1;

        //- - - - - - - - - - - - - - - - - - - - - - - - - - - -
        //detect all the aliases used to be able to list join dependencies
        //- - - - - - - - - - - - - - - - - - - - - - - - - - - -
        //detect all the aliases used to be able to list join dependencies
        if(!empty($asCondition['clause']))
        {
          $asCondition['clause'] = array_unique($asCondition['clause']);
          $sJoinSql = ' '.implode(' ', $asCondition['clause']).' ';
        }
        else
          $sJoinSql = '';

        preg_match_all('/([a-z]{4})\.[a-z_]{3,}[^a-zA-Z]{1}/', $sJoinSql, $asMatch);

        $asMatch[1] = array_unique($asMatch[1]);
        foreach($asMatch[1] as $nKey => $sValue)
        {
          if($sValue == $asCondition['alias'])
            unset($asMatch[1][$nKey]);
          else
          {
            if(isset($asRank[$sValue]))
              $asRank[$sValue]+= $nWeight;
            else
              $asRank[$sValue] = $nWeight;
          }
        }

        //- - - - - - - - - - - - - - - - - - - - - - - - - - - -
        //save the sql, the depenedencies and the rank to be able to sort and rebuild the query in the right order
        $asFullJoin[$sKey] = strtoupper($asCondition['type']).' JOIN '.$sTable.' ON (('.implode(' ) AND ( ', $asCondition['clause']).')) ';
        $asDependency[$sKey] = $asMatch[1];

        if(isset($asRank[$sKey]))
          $asRank[$sKey]+= $nWeight;
        else
          $asRank[$sKey] = $nWeight;

        // version 1: works but query with mixed inner / left joins
        //$asJoinSql[] = strtoupper($asCondition['type']).' JOIN '.$sTable.' ON (('.implode(' ) AND ( ', $asCondition['clause']).')) ';
      }

      $this->_trace($asFullJoin);
      //$this->_trace($asDependency);
      $this->_trace($asRank);

      //- - - - - - - - - - - - - - - - - - - - - - - - - - - -
      // adjust rank of joins if there are cross join between equally ranked clauses
      arsort($asRank);
      $this->_updateRank($asRank, $asDependency);
      //$this->_trace($asRank);

      foreach($asRank as $sAlias => $sValue)
      {
        if(isset($asFullJoin[$sAlias]))
          $asRank[$sAlias] = $asFullJoin[$sAlias];
        else
          unset($asRank[$sAlias]);
      }
      $this->_trace($asRank);
      $this->_trace('----- ------ ------ -------');

      $sQuery.= implode(' ', $asRank);
    }


    if(!empty($this->casSQL['where']))
    {
      $this->casSQL['where'] = array_unique($this->casSQL['where']);
      $sQuery.= ' WHERE 1 '.implode(' ', $this->casSQL['where']);
    }

    //we may have group by not applying on count queries

    if($pbCountQuery && isset($this->casSQL['group']['count']))
    {
      $asGroup = array_unique($this->casSQL['group']['count']);
    }
    elseif(isset($this->casSQL['group']['select']))
    {
      $asGroup = array_unique($this->casSQL['group']['select']);
    }
    else
      $asGroup = array();

    if(!empty($asGroup))
    {
      $sQuery.= ' GROUP BY '.implode(', ', $asGroup);
    }

    if(!$pbCountQuery && !empty($this->casSQL['order']))
    {
      $this->casSQL['order'] = array_unique($this->casSQL['order']);
      $sQuery.= ' ORDER BY '.implode(', ', $this->casSQL['order']);
    }

    if(!$pbCountQuery && !empty($this->casSQL['limit']))
    {
      $sQuery.= ' LIMIT '.$this->casSQL['limit'];
    }

    return $sQuery;
  }


  public function getWhere()
  {
    if(!empty($this->casSQL['where']))
    {
      $this->casSQL['where'] = array_unique($this->casSQL['where']);
      return implode(' ', $this->casSQL['where']);
    }

    return '';
  }



  /**
   *
   * @return string The query including conditions and join related to users access rights
   */
  public function getCountSql()
  {

    if(empty($this->casSQL['count_select']))
    {
      $this->casSQL['count_select'][] = 'count(*) as nCount ';
    }

    return $this->getSql(true);
  }

  /**
   * We've got a pre-ordered list of joins. We just need to treat the case where join has same rank, and have cross dependencies
   *
   * @param type $pasRank
   * @param type $pasDependency
   * @return boolean
   */
  private function _updateRank(&$pasRank, $pasDependency, $pnIteration = 0)
  {
    if(empty($pasRank) || empty($pasDependency) || $pnIteration >= 30)
      return true;



    $bOrderChanged = false;

    //full walk of the array for every row
    foreach($pasRank as $sKeyToCheck => $nRankToCheck)
    {

      foreach($pasRank as $sAlias => $nRank)
      {
        //exclude the current checked row
        if($sAlias != $sKeyToCheck )
        {

          //--> by Type
          //search for join with same rank
          if($nRank == $nRankToCheck)
          {
            //echo 'Treating comparing ['.$sKeyToCheck.'] ['.$nRankToCheck.'] to  ['.$sAlias.'] ['.$nRank.']<br />';
            if(isset($pasDependency[$sAlias]) && in_array($sKeyToCheck, $pasDependency[$sAlias]))
            {
              //echo '['.$sAlias.'] ['.$nRank.'] is using '.$sKeyToCheck.' => downgrade all the rest of arrays (-1) <br />';
              foreach($pasRank as $sKey => $nRankToDowngrade)
              {
                if($nRankToDowngrade < $nRankToCheck)
                  $pasRank[$sKey]-= 0.1;
              }

              $pasRank[$sAlias]-= 0.01;
              $bOrderChanged = true;
            }
          }
          //search for higher ranked joins having the current $sAlias in their dependencies... ==> move current above
          elseif($nRank > $nRankToCheck)
          {
            if(isset($pasDependency[$sAlias]) && in_array($sKeyToCheck, $pasDependency[$sAlias]))
            {
              $pasRank[$sAlias] = $nRankToCheck++;
              $bOrderChanged = true;
            }
          }
          /*else ($nRank < $nRankToCheck) so nothing... toCheck is above, the dependency is respected */

          //--> by Type
        }
      }
    }

    arsort($pasRank);
    $this->_trace(' - - - -- - - - - - - -- - - - - - -- ');
    $this->_trace($pnIteration);

    if($bOrderChanged)
    {
      $this->_trace($pasRank);
      return $this->_updateRank($pasRank, $pasDependency, ++$pnIteration);
    }

    return true;
  }

  public function setTitle($psTitle)
  {
    if(!empty($psTitle))
      $this->csSearchTitle = $psTitle;
  }
  public function getTitle()
  {
    return $this->csSearchTitle;
  }
}
