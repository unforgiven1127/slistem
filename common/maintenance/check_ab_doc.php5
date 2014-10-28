<?php

// uncomment the include in the index file to use this file

if(getValue('checkFile'))
{

  //from php.net, a bit adapted
  function listFolderFiles($dir, $asFileToCheck)
  {
    $asFound = array();
    $ffs = scandir($dir);
    foreach($ffs as $ff)
    {
        if($ff != '.' && $ff != '..' && $ff != '.svn')
        {
          if(!is_dir($dir.'/'.$ff))
          {
            echo '<br /><br />check this file '.$dir.''.$ff;

            foreach($asFileToCheck as $sFilePath)
            {
              echo '<br />against '.$sFilePath;

              if($sFilePath == $dir.''.$ff)
              {
                $asFound['exact'][] = $dir.''.$ff;
                break;
              }
              else
              {
                $sFilename = basename($dir.''.$ff);
                if(stripos($sFilePath, $sFilename) !== false)
                {
                  $asFound['name_only'][] = $sFilename.' in '.$dir.''.$ff;
                }
              }
            }
            /*else
              echo '<br />'.$dir.'/'.$ff;*/
          }
          /*else
          {
            echo '<br />'.$ff;
          }*/
          flush();
          ob_flush();

          if(is_dir($dir.'/'.$ff))
            $asFound = array_merge($asFound, listFolderFiles($dir.'/'.$ff, $asFileToCheck));
        }
    }
    return $asFound;
  }


  $oDb = CDependency::getComponentByName('database');
  /*$sQuery = 'SELECT doc.*, lo.lastname , cp.company_name, CONCAT(ct.lastname, " ", ct.firstname) as name

    FROM addressbook_document as doc
    INNER JOIN login as lo ON (lo.loginpk = doc.loginfk)

    LEFT JOIN  addressbook_document_info as adi ON (adi.docfk = doc.addressbook_documentpk)

    LEFT JOIN company as cp ON (cp.companypk = adi.itemfk AND adi.type="cp")
    LEFT JOIN contact as ct ON (ct.contactpk = adi.itemfk AND adi.type="ct")

    ORDER BY lo.lastname, doc.date_create ';*/

  $sQuery = 'SELECT dfil.*, dlin.cp_type, dlin.cp_pk

    FROM document_file as dfil
    INNER JOIN document_link as dlin ON (dlin.documentfk = dfil.documentfk AND cp_uid = "777-249")
    INNER JOIN login as lo ON (lo.loginpk = dfil.creatorfk)

    ORDER BY dlin.documentfk ';


  $oDbResult = $oDb->executeQuery($sQuery);
  $bRead = $oDbResult->readFirst();
  $asFilePath = array();
  $asFileMissing = array();
  $asItem = array();

  $nCount = 0;
  while($bRead)
  {
    $sPath = $oDbResult->getFieldValue('file_path');
    $asFilePath[] = $sPath;

    if(!empty($sPath) && !file_exists($sPath))
    {
      echo '<br /><span style="color: red;">File ['.$oDbResult->getFieldValue('file_path').']</span>';
      $asFileMissing[$oDbResult->getFieldValue('cp_type').'_'.$oDbResult->getFieldValue('cp_pk').'_'.$nCount] = $sPath;

    }
    /*else
    {
      echo '<br /> File found ['.$sPath.']';
    }*/

    $nCount++;
    $bRead = $oDbResult->readNext();
  }

 echo 'Missing '.count($asFileMissing).' files<br />';





 foreach($asFileMissing as $sFilePath)
 {
   $sFile = basename($sFilePath);
   $asFile = explode('_', $sFile);

   unset($asFile[0]);
   unset($asFile[1]);
   unset($asFile[2]);
   unset($asFile[3]);

   $sFile = implode('_', $asFile);

   $sCmd = '/usr/bin/locate "'. escapeshellcmd($sFile).'" ';
   $sResult = exec($sCmd, $asResult, $nResult);

   $asResult = array();

   if(!empty($asResult))
   {
     echo('<hr /> search for '.$sFile.'<br/><br/>'.$sCmd);
     dump($asResult);
     echo('<br />');
   }
   else
     echo('<br />nothing for '.$sFile.' // '.$sCmd);


   usleep(250);
 }

  exit('stop here for now');

  /*
  echo '<hr /><br />';
  $asMissing = listFolderFiles($_SERVER['DOCUMENT_ROOT'].'/common/upload/sharedspace/', $asFilePath);
  dump($asMissing);



  exit('stop here for now');


  echo '<hr /><hr /><hr /><span style="color: red;">folder path:  /home/BCAdmin/public_html/bc_crm/common/upload/addressbook/document/...</span>';
  //sort($asFileMissing);
  //dump($asFileMissing);
  foreach($asFileMissing as $sUser => $asFilePath)
  {
    echo '<hr />'.$sUser.'<br /><br />';

    foreach($asFilePath as $sFilePath)
    {
      $sFileName = basename($sFilePath);
      $asFile = explode('_', $sFileName);

      $sDate = $asFile[0];
      $sDate = substr($sDate, 0, 4).'-'.substr($sDate, 4, 2).'-'.substr($sDate, 6, 2).' '.substr($sDate, 8, 2).':'.substr($sDate, 10, 2).'';
      unset($asFile[0]);
      unset($asFile[1]);
      unset($asFile[2]);
      unset($asFile[3]);



      $sFile = implode('_', $asFile);
      echo '<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;date: '.$sDate.'&nbsp;&nbsp;->&nbsp;&nbsp;'.$sFile.'&nbsp;&nbsp;&nbsp;--->&nbsp;&nbsp;&nbsp;'.str_replace('/home/BCAdmin/public_html/bc_crm/common/upload/addressbook/', '...', $sFilePath);
      echo ' -----> linked to: '.@$asItem[$sFilePath];

    }

  }

  dump($asMissing);*/

}
?>
