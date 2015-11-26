<?php
/*
 * Just for it to not be in the main component
 *
 */


class CQuickSearch
{

   private $coQb = null;

   public function __construct(&$poQueryBuilder)
   {
     $this->coQb = $poQueryBuilder;
   }

  /**
   * Check quick search form, treat the parameters and build a oQB
   * @return CQueryBuilder object or an array containing the errors
   */
  public function buildQuickSearch($psDataType = '')
  {
    if(empty($psDataType))
      $sDataType = getValue('data_type');
    else
      $sDataType = $psDataType;

    if(!in_array($sDataType, array('candi', 'comp', 'jd')))
    {
      assert('false; // data_type empty in query builder.');
      return 'Data type unknown.';
    }

    $bStrictSearch = !(bool)getValue('qs_super_wide', 0);

    switch($sDataType)
    {
      case 'candi': return $this->_buildCandidateQuickSearch($bStrictSearch); break;
      case 'comp':  return $this->_buildCompanyQuickSearch($bStrictSearch); break;
      case 'jd':
        $oJd = CDependency::getComponentByName('sl_position');
        return $oJd->buildPositionQuickSearch($bStrictSearch);
        break;
    }
  }

  public function _buildCandidateQuickSearch($pbStrict = true)
  {
    if($pbStrict)
      $sOperator = ' AND ';
    else
      $sOperator = ' OR ';

    $asTitle = array();
    $bWide = (bool)getValue('qs_wide', 0);
    $sNameFormat = getValue('qs_name_format');
    $sSearchId = getValue('searchId');

    if($bWide)
      $sWildcard = '%';
    else
      $sWildcard = '';

    switch($sNameFormat)
    {
      case 'none':
        $sFirstField = 'lastname';
        $sSecondField = 'firstname';
        $bReverse = true;
        break;

      case 'firstname':
        $sFirstField = 'firstname';
        $sSecondField = 'lastname';
        $bReverse = false;
        break;

      case 'lastname':
      default:
        $sFirstField = 'lastname';
        $sSecondField = 'firstname';
        $bReverse = false;
        break;
    }

    //$sOperator = ' OR ';

    //if there's a ref id, no need for any other search parameter
    $sCandidate = strtolower(trim(getValue('candidate')));

    $sRefId = preg_replace('/[^0-9]/', '', $sCandidate);


    /*dump($sCandidate);
    dump($sNameFormat);
    dump($sFirstField);
    dump($sSecondField);*/

    if(!empty($sRefId) && is_numeric($sRefId))
    {
      $nRefId = (int)$sRefId;
      if($nRefId != $sRefId || $nRefId < 1)
        return 'The refId must be a positive integer.';

      $this->coQb->addWhere('sl_candidatepk = '.$nRefId);
      $asTitle[] = ' refId = '.$nRefId;
    }
    else
    {
      if(!empty($sCandidate))
      {
        //check if it's a comma separated sting
        $asWords = explode(',', $sCandidate);
        $this->_cleanArray($asWords);
        $nWord = count($asWords);
        if($nWord > 2)
          return 'Only one comma is allowed to separated the lastname and firstname.';

        //comma separated
        if($nWord == 2)
        {
          $asWords[0] = trim($asWords[0]);
          $asWords[1] = trim($asWords[1]);

          $this->coQb->addSelect(' 100-(levenshtein("'.($asWords[0].$asWords[1]).'", LOWER(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.')))*100/LENGTH(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.'))) AS ratio ');

          if($bReverse)
          {
            $this->coQb->addSelect(' 100-(levenshtein("'.($asWords[1].$asWords[0]).'", LOWER(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.')))*100/LENGTH(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.'))) AS ratio_rev ');

            $this->coQb->addWhere('( (scan.'.$sFirstField.' LIKE "'.$asWords[0].'%" '.$sOperator.' scan.'.$sSecondField.' LIKE "'.$sWildcard.$asWords[1].'%")
              OR (scan.'.$sSecondField.' LIKE "'.$sWildcard.$asWords[0].'%" '.$sOperator.' scan.'.$sFirstField.' LIKE "'.$sWildcard.$asWords[1].'%") )');

            $this->coQb->addOrder(' IF(MAX(ratio) >= MAX(ratio_rev), ratio, ratio_rev) DESC ');
          }
          else
          {
            $this->coQb->addWhere(' scan.'.$sFirstField.' LIKE "'.$sWildcard.$asWords[0].'%" '.$sOperator.' scan.'.$sSecondField.' LIKE "'.$sWildcard.$asWords[1].'%" ');

            $this->coQb->addOrder(' ratio DESC ');
          }
        }
        else
        {
          //no comma, we split the string on space
          $asWords = explode(' ', $sCandidate);
          $nWord = count($asWords);
          $this->_cleanArray($asWords);

          if($nWord == 1)
          {
            $asWords[0] = trim($asWords[0]);

            $this->coQb->addSelect(' levenshtein("'.$asWords[0].'", LOWER(scan.lastname)) AS lastname_lev ');
            $this->coQb->addSelect(' levenshtein("'.$asWords[0].'", LOWER(scan.firstname)) AS firstname_lev ');
            $this->coQb->addWhere('( scan.lastname LIKE "'.$sWildcard.$asWords[0].'%" OR  scan.firstname LIKE "'.$sWildcard.$asWords[0].'%" ) ');

            $this->coQb->addOrder(' lastname_lev ASC, firstname_lev ASC ');
          }
          elseif($nWord == 2)
          {
            $asWords[0] = trim($asWords[0]);
            $asWords[1] = trim($asWords[1]);

            $this->coQb->addSelect(' 100-(levenshtein("'.($asWords[0].$asWords[1]).'", LOWER(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.')))*100/LENGTH(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.'))) AS ratio ');

            if($bReverse)
            {
              $this->coQb->addSelect(' 100-(levenshtein("'.($asWords[1].$asWords[0]).'", LOWER(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.')))*100/LENGTH(CONCAT(scan.'.$sFirstField.', scan.'.$sSecondField.'))) AS ratio_rev ');

              $this->coQb->addWhere('( (scan.'.$sFirstField.' LIKE "'.$sWildcard.$asWords[1].'%" '.$sOperator.' scan.'.$sSecondField.' LIKE "'.$sWildcard.$asWords[0].'%")
              OR (scan.'.$sSecondField.' LIKE "'.$sWildcard.$asWords[1].'%" '.$sOperator.' scan.'.$sFirstField.' LIKE "'.$sWildcard.$asWords[0].'%") )');

              $this->coQb->addOrder(' IF(MAX(ratio) >= MAX(ratio_rev), ratio, ratio_rev) DESC ');
            }
            else
            {
              $this->coQb->addWhere(' scan.'.$sFirstField.' LIKE "'.$sWildcard.$asWords[1].'%" '.$sOperator.' scan.'.$sSecondField.' LIKE "'.$sWildcard.$asWords[0].'%" ');

              $this->coQb->addOrder(' ratio DESC ');
            }
          }
          else
          {
            foreach($asWords as $sWord)
            {
              $this->coQb->addWhere(' scan.firstname LIKE "'.$sWildcard.trim($sWord).'%" '.$sOperator.' scan.lastname LIKE "'.$sWildcard.trim($sWord).'%" ');
            }
          }
        }
        $asTitle[] = ' candidate = '.$sCandidate;
      }


      $sCompany = trim(getValue('company'));
      if($sCompany == 'Company')
        $sCompany = '';
      else
        $sCompany = strtolower($sCompany);

      if(!empty($sCompany))
      {
        $asTitle[] = ' company = '.$sCompany;

        $bXCompany = (substr($sCompany, 0, 2) == 'x-');
        if($bXCompany)
        {
          $sCompany = trim(substr($sCompany, 2));
          //$this->coQb->addJoin('left', 'event_link', 'elin', 'elin.cp_pk = scan.sl_candidatepk AND elin.cp_uid = "555-001" AND elin.cp_type = "candi" AND elin.cp_action = "ppav"');
          $this->coQb->addJoin('left', 'event', 'even', 'even.eventpk = elin.eventfk AND even.type = "cp_hidden"');

          $asWords = explode(' ', $sCompany);
          foreach($asWords as $sWord)
            $this->coQb->addWhere(' even.content LIKE "%'.$sWord.'%" ');
        }
        else
        {
          $this->coQb->addJoin('left', 'sl_company', 'scom', 'scom.sl_companypk = scpr.companyfk');

          //Try to find a refId in the search string
          $nCompanyPk = $this->_fetchRefIdFromString($sCompany);
          if((string)$nCompanyPk == $sCompany || ('#' . $nCompanyPk) == $sCompany)
          {
            $this->coQb->addWhere('scpr.companyfk = '.$nCompanyPk);
          }
          else
          {
            //Not a ref id, we treat the string as a name
            $this->coQb->addSelect(' IF(scom.name LIKE "'.$sCompany.'", 3, IF(scom.name LIKE "'.$sCompany.'%", 2, 1)) as match_order ');
            $this->coQb->addOrder('match_order DESC, scan.sl_candidatepk');

            $asWords = explode(' ', $sCompany);
            foreach($asWords as $sWord)
              $this->coQb->addWhere(' scom.name LIKE "%'.$sWord.'%" ');
          }
        }
      }

      $sContact = trim(getValue('contact'));
      if($sContact == 'Contact')
        $sContact = '';

      if(!empty($sContact))
      {
        $sContact = trim(str_replace(';', '', $sContact));
        $this->coQb->addJoin('left', 'sl_contact', 'scon', 'scon.itemfk = scan.sl_candidatepk AND scon.item_type = "candi"');

        if($this->_lookLikePhone($sContact))
        {
          $sNumeric = preg_replace('/[^0-9]/', '', $sContact);
          //$this->coQb->addWhere(' scon.type IN (1,2,4,6) AND ( scon.value LIKE "'.$sContact.'%" OR  (scon.value REGEXP "[^0-9]") LIKE "'.$sNumeric.'%" )');
          $this->coQb->addWhere(' scon.type IN (1,2,4,6) AND ( scon.value LIKE "'.$sContact.'%" OR scon.value LIKE "'.$sNumeric.'%" )');
          $asTitle[] = ' phone = '.$sContact;
        }
        else
        {
          //if we find an @, and even if it's not a properly formated email adreesss we give a shot
          $nMatchEmail = $this->_lookLikeEmail($sContact);
          if($nMatchEmail == 2)
          {
            $this->coQb->addWhere(' scon.type = 5 AND scon.value LIKE "'.$sContact.'" ');
            $asTitle[] = ' email = '.$sContact;
          }
          elseif($nMatchEmail == 1)
          {
            $this->coQb->addWhere(' scon.type = 5 AND scon.value LIKE "%'.$sContact.'%" ');
            $asTitle[] = ' email = '.$sContact;
          }
          else
          {
            if($this->_lookLikeUrl($sContact))
            {
              $this->coQb->addWhere(' scon.type IN(3,7,8) AND scon.value LIKE "'.$sContact.'%" ');
              $asTitle[] = ' url = '.$sContact;
            }
            else
            {
              $this->coQb->addWhere(' scon.value LIKE "'.$sContact.'%" ');
              $asTitle[] = ' contact = '.$sContact;
            }
          }
        }
      }

      $sDepartment = trim(getValue('department'));
      if($sDepartment == 'Department')
        $sDepartment = '';

      if(!empty($sDepartment))
      {
        if($sDepartment == '__no_department__')
        {
          $this->coQb->addWhere(' (scpr.department IS NULL OR scpr.department = "") ');
          $asTitle[] = ' department is empty';
        }
        else
        {
          $bExactMatch = (bool)getValue('qs_exact_match', 0);
          if($bExactMatch)
            $this->coQb->addWhere(' scpr.department LIKE "'.$sDepartment.'" ');
          else
            $this->coQb->addWhere(' scpr.department LIKE "'.$sDepartment.'%" ');

          $asTitle[] = ' department = '.$sDepartment;
        }
      }

      $sPosition = trim(getValue('position'));
      if($sPosition == 'Position ID or title')
        $sPosition = '';

      if(!empty($sPosition))
      {
        $nPositionPk = (int)$this->_fetchRefIdFromString($sPosition);

        if(!empty($nPositionPk))
        {
          $this->coQb->addJoin('inner', 'sl_position_link', 'spli', ' spli.candidatefk = scan.sl_candidatepk AND spli.active = 1 AND spli.positionfk = "'.$nPositionPk.'" ');
          $asTitle[] = ' position ID = #'.$nPositionPk;
        }
        else
        {

          $sCleanPosition = addslashes($sPosition);

          $this->coQb->addJoin('inner', 'sl_position_link', 'spli', ' spli.candidatefk = scan.sl_candidatepk AND spli.active = 1');
          $this->coQb->addJoin('inner', 'sl_position_detail', 'spde', ' spde.positionfk = spli.positionfk
            AND (spde.title LIKE "%'.$sCleanPosition.'%" OR spde.description LIKE "%'.$sCleanPosition.'%" ) ');

          $asTitle[] = ' position = '.$sPosition;
        }

        $sStatus = getValue('position_status');
        if(!empty($sStatus))
        {
          $sStart = substr($sStatus, 0, 1);
          if($sStart == '+')
          {
            $this->coQb->addWhere(' spli.status >= '.(int)substr($sStatus, 1) );
          }
          elseif($sStart == '-')
          {
            $this->coQb->addWhere(' spli.status <= '.(int)substr($sStatus, 1) );
          }
          else
           $this->coQb->addWhere(' spli.status = '.(int)$sStatus);
        }
      }
    }

    //if search Id, i may just be filtering or sorting the results... no need to check params
    if(empty($sSearchId) && empty($sRefId) && empty($sCandidate) && empty($sContact) && empty($sDepartment) && empty($sCompany) && empty($sPosition))
      return 'You need to input a refId, a name, a contact detail or a company.';

    $this->coQb->setTitle('QuickSearch: '.implode(' , ', $asTitle));

    return '';
  }



  public function _buildCompanyQuickSearch($pbStrict = true)
  {
    if($pbStrict)
      $sOperator = ' AND ';
    else
      $sOperator = ' OR ';

    $asTitle = array();

    //if there's a ref id, no need for any other search parameter
    $sCompany = strtolower(trim(getValue('company')));
    $sIndustry = trim(getValue('industry'));
    $sContact = trim(getValue('contact'));

    if($sCompany == 'Company')
      $sCompany = '';

    if(!empty($sCompany))
    {
      $nRefId = $this->_fetchRefIdFromString($sCompany);

      if((string)$nRefId == $sCompany || ('#' . $nRefId) == $sCompany)
      {
        if($nRefId < 1)
          return 'The refId must be a positive integer.';

        $this->coQb->addWhere('scom.sl_companypk = '.$nRefId);
        $asTitle[] = ' refId = '.$nRefId;
      }
      else
      {
        $no_spaces_company = str_replace(' ', '', $sCompany);

        $this->coQb->addSelect('*, 100-(levenshtein("'.$sCompany.'", LOWER(scom.name))*100/LENGTH(scom.name)) AS ratio');

        $this->coQb->addWhere('scom.name LIKE "%'.$sCompany.'%" OR scom.corporate_name LIKE "'.$sCompany.'%" OR scom.name LIKE "%'.$no_spaces_company.'%"');
        $this->coQb->addOrder(' ratio DESC ');

        $asTitle[] = ' company name = '.$sCompany;
      }
    }



    if($sContact == 'Contact')
      $sContact = '';

    if(!empty($sContact))
    {

      if($this->_lookLikePhone($sContact))
      {
        $this->coQb->addWhere(' scom.phone LIKE "'.$sContact.'%" OR scom.fax LIKE "'.$sContact.'%" ');
        $asTitle[] = ' phone = '.$sContact;
      }
      else
      {
        //if we find an @, and even if it's not a properly formated email adreesss we give a shot
        if($this->_lookLikeEmail($sContact))
        {
          $this->coQb->addWhere(' scom.email LIKE "'.$sContact.'" ');
          $asTitle[] = ' email = '.$sContact;
        }
        else
        {
          if($this->_lookLikeUrl($sContact))
          {
            $this->coQb->addWhere(' scom.website LIKE "'.$sContact.'%" ');
            $asTitle[] = ' url = '.$sContact;
          }
          else
          {
            $this->coQb->addWhere('  scom.phone LIKE "'.$sContact.'%" OR scom.fax LIKE "'.$sContact.'%"
              OR scom.email LIKE "'.$sContact.'" OR scom.website LIKE "'.$sContact.'%" ');
             $asTitle[] = ' contact = '.$sContact;
          }
        }
      }
    }

    $sIndustry = trim(getValue('industry'));
    if($sIndustry == 'industry')
      $sIndustry = '';

    if(!empty($sIndustry))
    {
      if(is_numeric($sIndustry))
      {
        $this->coQb->addWhere(' sind.sl_industrypk = "'.(int)$sIndustry.'" OR sind.parentfk = "'.(int)$sIndustry.'" ');
        $asTitle[] = ' industry ID = '.$sIndustry;
      }
      else
      {
        $this->coQb->addWhere(' sind.label LIKE "%'.$sIndustry.'%" ');
        $asTitle[] = ' industry = '.$sIndustry;
      }
    }


    $nFolder = (int)getValue('folderpk', 0);
    if(!empty($nFolder))
    {
      $oFolder = CDependency::getComponentByUid('555-002');
      $oFolderData = $oFolder->getFolder($nFolder);
      $bRead = $oFolderData->readFirst();

      if(!$bRead)
      {
        $asTitle[] = ' folder # '.$nFolder.' does not exist. Might have been deleted.';
        $this->coQb->addWhere(' scom.sl_companypk = 0 ');
      }
      else
      {
        $asTitle[] = ' folder # '.$nFolder.' - '.$oFolderData->getFieldValue('label');
        $this->coQb->addJoin('inner', 'folder_item', 'fite', 'fite.itemfk = scom.sl_companypk AND fite.parentfolderfk = '.$nFolder);
      }
    }

    $this->coQb->setTitle(implode(' , ', $asTitle));

    return '';
  }




  /* ********************************************************************************************* */
  /* ********************************************************************************************* */
  //String processing functions

  static function _fetchRefIdFromString($psString)
  {
    //second attempt: looking for # + number)
    $sFirstChar = substr($psString, 0, 1);
    $sRest = substr($psString, 1);

    if($sFirstChar == '#' && is_integerString($sRest, 1, 7))
    {
      if((int)$sRest < 1)
        return 0;

      return filter_var($sRest, FILTER_SANITIZE_NUMBER_INT);
    }
    else if(is_integerString($psString, 1, 7))
    {
      $cleaned_string = filter_var($psString, FILTER_SANITIZE_NUMBER_INT);

      if((int)$cleaned_string < 1)
        return 0;

      return (int)$cleaned_string;
    }

    return 0;
  }


  /**
   *String to search a phone number in
   * @param type $psString
   * @return integer
   */
  private function _lookLikePhone($psString)
  {
    $psString = trim($psString);
    $nOriginal = mb_strlen($psString);

    $psString = preg_replace('/[^0-9]/', '', $psString);
    if(empty($psString))
      return 0;

    $nNumeric = mb_strlen($psString);

    //if string are clearly different sizes or not a numeric value ... not a phone number
    if( ($nOriginal - $nNumeric) > 2 || !is_integerString($psString, 2, 12))
      return 0;

    return 1;
  }

  private function _lookLikeEmail($psString)
  {
    if(isValidEmail($psString))
      return 2;

    /*dump(preg_match('/^[0-9a-z_\-\.]{1,50}@[0-9a-z_\-\.]{1,50}/i', $psString));
    dump(preg_match('/^@[0-9a-z_\-]{1,50}[\.][0-9a-z_\-\.]{2,50}/i',  $psString));*/

    return (int)preg_match('/^([0-9a-z_\-\.]{1,50}@[0-9a-z_\-\.]{1,50})|(@[0-9a-z_\-\.]{1,50}\.[0-9a-z_\-\.]{2,5})/i', $psString);
  }

  private function _lookLikeUrl($psString)
  {
    if(isValidUrl($psString))
      return 2;

    return (int)(bool)preg_match('/^(www|http).{6,}/i', $psString);
  }


  private function _cleanArray(&$pasString, $pnMinLength = 2)
  {
    foreach($pasString as $vKey => $sValue)
    {
      $sValue = trim($sValue);
      if(empty($sValue) || mb_strlen($sValue) < $pnMinLength)
        unset($pasString[$vKey]);
      else
        $pasString[$vKey] = $sValue;
    }

    return true;
  }

}