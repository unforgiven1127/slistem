<?php
//for sharedspace to work
session_start();



if(substr($_SERVER['DOCUMENT_ROOT'], 0, 4) == '/opt')
{
  //local
  $sConfPath =  $_SERVER['DOCUMENT_ROOT'].'/slistem_doc/';
  $sDbHost = '';
  $sMyPass = 'bcmedia';
}
else
{
  //live
  //phpinfo();
  $sConfPath =  $_SERVER['DOCUMENT_ROOT'].'/common/upload/slistem2_doc/';
  $sDbHost = 'host=10.0.81.110 port=5432 ';
  $sMyPass = 'bcmedia2011';
}

$fStartTime = microtime(true);
$oPGCx = pg_connect($sDbHost."dbname=slystem_live user=slate password=slate");
if(!$oPGCx)
  exit('can not connect to postgresql');


$oMyCx = mysql_connect('localhost', 'bccrm', $sMyPass);
if(!$oMyCx)
{
  echo mysql_error();
  exit('can not connect to mysql');
}

mysql_select_db('slistem', $oMyCx);

if(isset($_GET['pass']) && !empty($_GET['pass']) && is_numeric($_GET['pass']))
{
  $nPass = (int)$_GET['pass'];
}
else
  $nPass = 0;

if(isset($_GET['batch']) && !empty($_GET['batch']) && is_numeric($_GET['batch']))
{
  $nRowsByBatch = (int)$_GET['batch'];
}
else
  $nRowsByBatch = 2000;


$bError = $bDone = false;
$nMaxPass = $nPass+50;

ini_set('include_path', '.;..;/opt/eclipse-workspace/bcm_svn/trunk/');
//echo getcwd() . "<br />";
chdir($_SERVER['DOCUMENT_ROOT']);
//echo getcwd() . "<br />";

include_once($_SERVER['DOCUMENT_ROOT'].'/conf/main_config.inc.php5');
include_once($_SERVER['DOCUMENT_ROOT'].'/common/lib/global_func.inc.php5');
include_once($_SERVER['DOCUMENT_ROOT'].'/component/dependency.inc.php5');

$oSharedSpace = CDependency::getComponentByName('sharedspace');



while(!$bDone && $nPass < $nMaxPass)
{
  $nLimitOffset = $nPass * $nRowsByBatch;
  $sPgQuery = '

    SELECT * FROM
    (
        SELECT chd.*, doc.*, (candi.candi_lname || \' \' || candi.candi_fname) as item_name,
        cons.consultantpk, cons.cons_name, chd.doc_id as linked_id, chd.candi_rno as itempk,
        doc.doc_id as doc_uid
        FROM doc_tbl as doc
        LEFT JOIN candi_has_doc as chd ON(chd.doc_id = doc.doc_id)

        LEFT JOIN candidate_tbl as candi ON(candi.candi_rno = chd.candi_rno)
        LEFT JOIN cons_tbl as cons ON(cons.cons_cid = doc.doc_owner)
        WHERE doc.doc_filetype = \'candidate\'
        AND doc.doc_added >= \'2014-05-02 00:00:00\'

    UNION

        SELECT chd.*, doc.*, comp.company_name as item_name,
        cons.consultantpk, cons.cons_name, chd.doc_id as linked_id, chd.company_id as itempk,
        (100000 + doc.doc_id) as doc_uid
        FROM doc_tbl as doc
        LEFT JOIN company_has_doc as chd ON(chd.doc_id = doc.doc_id)

        LEFT JOIN company_tbl as comp ON(comp.company_id = chd.company_id)
        LEFT JOIN cons_tbl as cons ON(cons.cons_cid = doc.doc_owner)
        WHERE doc.doc_filetype = \'company\'
        AND doc.doc_added >= \'2014-05-02 00:00:00\'

    ) subU

    ORDER BY subU.doc_filetype DESC, subU.doc_type DESC, subU.doc_filename ASC
    LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset;

  $oPgResult = pg_query($sPgQuery);
  if(!$oPgResult)
  {
    exit('Query error !! ');
    break;
  }

  $asForbidden = array(' ', '%20', '"', '\'', ':', '%', '-', '#', '@', ',', '&',     '+');
  $asReplace =   array('_', '_',   '_', '_',  '_', '_', '_', '_', '_', '_', '_and_', '_');
  $asDocType = array(1 => 'resume', 2 => 'image', 3 => 'file');
  $asPdf = array('application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf', 'text/x-pdf');
  $asAllowedExt = array( 'pdf', 'doc', 'xls', 'jpg', 'png', 'odt', 'txt', 'html', 'ods', 'docx', 'zip', 'sxw', 'gif', 'rtf', 'ppt', 'dot', 'text','rez', 'xml', 'csv', 'tmb', 'sxc', 'odg', 'planner', 'stw');


  $asMyInsert = array();
  $asMyFileInsert = array();
  $asMyLinkInsert = array();
  $nDocCount = 0;
  $nErrorCount = 0;
  $asMoved = array();
  $asMissing = array();
  $anDataError = array();
  $asDataError = array();
  $anCandidate = array();


  $nIndicator = 0;
  while($asData = pg_fetch_assoc($oPgResult))
  {
    //$fFileStartTime = microtime(true);

    $bDataError = false;
    $bError = false;
    // 	indus_id 	industry 	parent_id
    //$asData['doc_id'] = (int)$asData['doc_id'];
    $asData['doc_id'] = (int)$asData['doc_uid'];
    $asData['consultantpk'] = (int)$asData['consultantpk'];

    if(empty($asData['linked_id']))
    {
      $anDataError[] = $asData['doc_id'];
      $bDataError = true;
      $asDataError[] = '<br />doc doesn t have a link;'.$asData['doc_added'].';'.$asData['doc_id'].';'.$asData['doc_filename'];
    }
    if(empty($asData['item_name']))
    {
      $bDataError = true;
      $anDataError[] = $asData['doc_id'];
      $asDataError[] = '<br />doc linked to nothing;'.$asData['doc_added'].';'.$asData['doc_id'].';'.$asData['doc_filename'];
    }
    if(empty($asData['consultantpk']))
    {
      $bDataError = true;
      $anDataError[] = $asData['doc_id'];
      $asDataError[] = '<br />doc created by no one ;'.$asData['doc_added'].';'.$asData['doc_id'].';'.$asData['doc_filename'];
    }



    //now data error have been found, let's treat the files
    //if(!$bDataError)  //error files will be moved in a specific folder
    {

      // slistem_doc is a symlink towards [/data/documents] folder
      if($asData['doc_filetype'] == 'candidate')
      {
        $sPathByType = 'candi';
        $sCpType = 'candi';
        $sFolder = 'candi_';
      }
      else
      {
        $sPathByType = 'company';
        $sCpType = 'comp';
        $sFolder = 'comp_';
      }


      $sPath =  $sConfPath.'/'.$sPathByType.'/';


      if(empty($asData['doc_type']))
      {
        //find the doc_type by looking for the file
        echo '<br/>!!!!!!!! file without docType !!!!!!! ';
        foreach($asDocType as $nKey => $sType)
        {
          $sPath.=  $sType.'/'.$asData['doc_filename'];
          if(file_exists($sPath))
          {
            $asData['doc_type'] = $nKey;
            echo '<br/>!!!!!!!! Found doc in '.$sType.' !!!!!!! ';
            break;
          }
        }
      }
      else
        $sPath.= $asDocType[(int)$asData['doc_type']].'/'.$asData['doc_filename'];


      if(!file_exists($sPath))
      {
        echo ('<div style="color: red;"/>#'.($nErrorCount+1).' - file could not be found</div> -->['.$asData['doc_added'].']  '.$sPath);
        $bError = true;
        $asMissing[] = '\''.trim($asData['doc_filename']).'\'';
      }
      else
      {

        // *********************************************************************
        // *********************************************************************
        //we know slistem file exists, let's figure out what to save in slistem3

        $asData['initial_name'] = $asData['doc_filename'];
        $asData['file_name'] = mb_strtolower(str_replace($asForbidden, $asReplace, trim($asData['doc_filename'])));
        $asData['doc_filetype'] = trim($asData['doc_filetype']);



        // --------------------------------------------------
        //file name fixed, and we know where to save the file

        $sMimeType = mime_content_type($sPath);
        $asFileInfo = pathinfo($asData['file_name']);

        if(!isset($asFileInfo['extension']) || empty($asFileInfo['extension']))
        {
          //$asData['file_name'] = mb_ereg_replace('/[\.]/', '_', $asData['file_name']);
          $asData['file_name'] = str_replace('.', '_', $asData['file_name']);

          if(in_array($sMimeType, $asPdf))
          {
            $asData['file_name'].= '.pdf';
            $asData['initial_name'].= '.pdf';
            echo '<br /><br /> NO Extensionnnnn --> ['.$asData['doc_filename'].' / '.$sMimeType.'] -> pdf ';
          }
          else
          {
            $asData['file_name'].= '.odt';
            $asData['initial_name'].= '.odt';
             echo '<br /><br /> NO Extensionnnnn --> ['.$asData['doc_filename'].' / '.$sMimeType.'] -> odt';
          }
        }
        else
        {
          //remove all dots except extension one
          if(!in_array($asFileInfo['extension'], $asAllowedExt))
          {
            //echo '<br />bad extension '.$asFileInfo['extension'].' ['.$asData['file_name'].'] --> replaced by odt ';

            //keep current ext, remove the dot and stick .odt at the end
            $asData['file_name'] = str_replace('.', '_', $asData['file_name']).'.odt';

            //echo $asData['file_name'];
          }
          else
          {
            //echo '<br />current extension '.$asFileInfo['extension'].' ['.$asData['file_name'].'] ';

            $nExtLength = mb_strlen($asFileInfo['extension']);
            $sFileName = mb_substr($asData['file_name'], 0, (mb_strlen($asData['file_name']) - $nExtLength));

            $asData['file_name'] = str_replace('.', '_', $sFileName).'.'.$asFileInfo['extension'];
            //echo ' --> '.$asData['file_name'];
          }
        }

        //finalize cleaning ... _k_1_k & _co_1__ltd come from a massive import
        $asData['file_name'] = str_replace(array('__', '_.', '_k_1_k', '_co_1__ltd'), array('_', '.', '', '' ), $asData['file_name']);


        if($bDataError)
        {
          $asData['file_path'] = $_SERVER['DOCUMENT_ROOT'].'/common/upload/sharedspace/document/sl2_error';
          $asData['file_fullpath'] = $_SERVER['DOCUMENT_ROOT'].'/common/upload/sharedspace/document/sl2_error/'.$asData['file_name'];
        }
        else
        {
          //create directories with ~250docs inside each
          $nModulo = floor($nDocCount/250) + 1;

          $asData['file_path'] = $_SERVER['DOCUMENT_ROOT'].'/common/upload/sharedspace/document/'.$sFolder.$nModulo;
          $asData['file_fullpath'] = $_SERVER['DOCUMENT_ROOT'].'/common/upload/sharedspace/document/'.$sFolder.$nModulo.'/'.$asData['file_name'];
        }



        if(is_dir($asData['file_path']))
          $bMakeDir = true;
        else
        {
          $bMakeDir = mkdir($asData['file_path'], $nDocCount, true);
          chmod($asData['file_path'], 0750);
        }

        if(!$bMakeDir)
        {
          echo ('<br />file could not create the local path  --> '.$asData['file_path']);
          $bError = true;
        }
        else
        {
          //echo '<br />mv file to '.$asData['file_fullpath'].') <br /><br />';
          if(!copy($sPath, $asData['file_fullpath']))
          {
            echo ('<br />file could not be movedd from S2 file to S3 folder  --> '.$asData['file_path']);
            $bError = true;
          }
          else
          {
            $asMoved[] = ('<div style="font-size: 8px; color: green;">#'.$nDocCount.'==> moved '.$asData['doc_filename'].'</div>');
          }
        }
      }
    }
    //$fFileSavedTime = microtime(true);



    if($bError || $bDataError)
    {
      $nErrorCount++;
    }
    else
    {
      if(empty($asData['doc_title']))
      {
        if((int)$asData['doc_type'] == 1)
        $asData['doc_title'] = $asData['item_name'].' \'s resume ';
      }

      $asData['file_name'] = '"'.mysql_real_escape_string(addslashes(trim($asData['file_name']))).'"';

      $asData['doc_title'] = '"'.mysql_real_escape_string(addslashes(trim($asData['doc_title']))).'"';

      $asData['doc_desc_txt'] = '"'.mysql_real_escape_string(addslashes(strip_tags(trim($asData['doc_desc'])))).'"';
      $asData['doc_desc'] = '"'.mysql_real_escape_string(addslashes(trim($asData['doc_desc']))).'"';

      $asData['language'] = '"'.mysql_real_escape_string(addslashes(trim($asData['language']))).'"';
      $asData['original'] = '"'.mysql_real_escape_string(addslashes(trim($asData['doc_content']))).'"';

      if(empty($asData['doc_ts']) || $asData['doc_ts'] == '.')
        $asData['compressed'] = NULL;
      else
        $asData['compressed'] = '"'.mysql_real_escape_string(addslashes(trim($asData['doc_ts']))).'"';

      $asData['date_creation'] = '"'.mysql_real_escape_string(addslashes(trim(date('Y-m-d H:i:s', strtotime($asData['doc_added']))))).'"';


      $nFileSize = filesize($asData['file_fullpath']);
      $sUnit = 'B';

      //use 1000 instead of 1024 to not display 1008.16B, 1015.01Kb
      if($nFileSize > 1000000000)  //1024*1024*1024
      {
        $sUnit = 'GB';
        $nFileSize = $nFileSize / 1000000000;
      }
      elseif($nFileSize > 1000000)  //1024*1024
      {
        $sUnit = 'MB';
        $nFileSize = $nFileSize / 1000000;
      }
      elseif($nFileSize >= 1000)
      {
        $sUnit = 'KB';
        $nFileSize = $nFileSize / 1000;
      }
      $asData['file_size'] = '"'.mysql_real_escape_string(addslashes(trim(round($nFileSize, 2).$sUnit))).'"';




      $aParsedDocument = $oSharedSpace->getDocumentContent($asData['file_fullpath'], true);
      $asData['original'] = '"'.mysql_real_escape_string(addslashes(trim($aParsedDocument['text']))).'"';
      $asData['compressed'] = '"'.mysql_real_escape_string(addslashes(trim($aParsedDocument['fulltext']))).'"';
      $asData['language'] = '"'.mysql_real_escape_string(addslashes(trim($aParsedDocument['language']))).'"';

      /*$asData['original'] = '"'.mysql_real_escape_string(addslashes(trim(''))).'"';
      $asData['compressed'] = '"'.mysql_real_escape_string(addslashes(trim(''))).'"';
      $asData['language'] = '"'.mysql_real_escape_string(addslashes(trim(''))).'"';*/

      $asData['mime_type'] = '"'.mysql_real_escape_string(addslashes(trim($sMimeType))).'"';


      $asData['initial_name'] = '"'.mysql_real_escape_string(addslashes(trim($asData['initial_name']))).'"';
      $asData['file_fullpath'] = '"'.mysql_real_escape_string(addslashes(trim($asData['file_fullpath']))).'"';



      //`documentpk` , `title` , `doc_type` , `description` ,
      //`description_html` , `creatorfk` , `private` , `date_creation` , `date_update` , `downloads`
      $asMyInsert[] = '('.$asData['doc_id'].', '.$asData['doc_title'].', "'.$asDocType[(int)$asData['doc_type']].'", '.$asData['doc_desc_txt'].',
      '.$asData['doc_desc'].', '.$asData['consultantpk'].', 0, '.$asData['date_creation'].', NULL, '.$asData['doc_view_total'].') ';


      $asMyLinkInsert[] = '('.$asData['doc_id'].', "555-001", "ppav", "'.$sCpType.'", '.(int)$asData['itempk'].') ';

      //we need to update all the candidate profiles
      if($sCpType == 'candi')
      {
        $anCandidate[] = (int)$asData['itempk'];
      }


      /*INSERT INTO `document_file` (
      `documentfk` , `mime_type` , `initial_name` , `file_name` , `file_path` ,
      `file_size` , `creatorfk` , `date_creation` , `original` ,
      `compressed` , `language` , `live` ) VALUES
      */
      $asMyFileInsert[] = '('.$asData['doc_id'].', '.$asData['mime_type'].', '.$asData['initial_name'].', '.$asData['file_name'].', '.$asData['file_fullpath'].',
        '.$asData['file_size'].', '.$asData['consultantpk'].', '.$asData['date_creation'].', '.$asData['original'].',
        '.$asData['compressed'].', '.$asData['language'].', 1) ';


      /*$fFileParsedTime = microtime(true);
      echo 'Tot '.round((($fFileParsedTime - $fFileStartTime)), 4).' - mv '.round((($fFileSavedTime -$fFileStartTime)), 4)
              .' - parse '.round((($fFileParsedTime -$fFileSavedTime)), 4).' <br />'; ob_flush();*/

      $nDocCount++;
    }

    $nIndicator++;
    if(($nIndicator % 100) == 0)
    {
      echo '<br />'.$nIndicator.' doc treated<br />';
      flush();ob_flush();
    }
  }


  $fEndTime = microtime(true);
  echo '<br /><br />Took '.round((($fEndTime-$fStartTime)), 4).'s to parse files and generate sql ['.$nDocCount.' files] !! <br /><br /><pre>';



  $nDocument = count($asMyInsert);
  echo $nDocument.' inserts ready !! [LIMIT '.$nRowsByBatch.' OFFSET '.$nLimitOffset.']<br />';
  /*echo '<pre>'; var_echo ($asMyInsert); echo '</pre><hr />';*/

  if($nDocument > 0)
  {
    $sMyQuery = 'INSERT INTO `document` (`documentpk` , `title` , `doc_type` , `description` , `description_html` , `creatorfk` ,
      `private` , `date_creation` , `date_update` , `downloads`) VALUES '.implode(' ,', $asMyInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      //var_dump ($sMyQuery);
      exit('error inserting locations during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }



    $sMyQuery = 'INSERT INTO `document_link` (`documentfk`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`)VALUES '.implode(' ,', $asMyLinkInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      //var_dump ($sMyQuery);
      exit('error inserting locations during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }


    if(!empty($anCandidate))
    {
      $sMyQuery = 'UPDATE `sl_candidate_profile` SET _has_doc = 1 WHERE candidatefk IN (' .implode(',', $anCandidate).') ';
      $bInserted = mysql_query($sMyQuery);
      if(!$bInserted)
      {
        echo mysql_error();
        //var_dump ($sMyQuery);
        exit('error updating locations during pass #'.$nPass.' / offset: '.$nLimitOffset);
      }
    }



    $sMyQuery = 'INSERT INTO `document_file` (`documentfk`, `mime_type`, `initial_name` , `file_name`, `file_path`, `file_size`,
      `creatorfk`, `date_creation` , `original`, `compressed`, `language`, `live` ) VALUES '.implode(' ,', $asMyFileInsert);
    $bInserted = mysql_query($sMyQuery);
    if(!$bInserted)
    {
      echo mysql_error();
      //var_dump ($sMyQuery);
      exit('error inserting locations during pass #'.$nPass.' / offset: '.$nLimitOffset);
    }
  }


  if($nDocument < $nRowsByBatch)
  {
    echo '<br /><span style="color: green;"> --> treatead '.$nDocument.' on last batch, looks done. span> ';
  }

  echo '<hr /><hr />Pass #'.$nPass.' done<hr /><hr />';
  flush();
  ob_flush();

  //exit('one pass at the time apparently');

  $nPass++;
}


  echo '<hr /><hr />';
  var_dump('errors: '.$nErrorCount);
  var_dump('treated files: '.$nDocCount);
  echo '<hr /><hr /><pre>';


  echo '<hr /><hr />Doc with bad data in the database ('.implode(',', $anDataError).') <br /><br />';
  echo implode('', $asDataError);

  echo '<hr /><hr />Missing files:<br/> find /data/documents -name '.implode(' -o -name ', $asMissing).' <br /><br />';



if($nPass >= $nMaxPass)
{
  echo '<br /><span style="color: red;"> ==> ran out of passes, may not be fully done.</span> ';
}







/*
doc doesn t have a link;2008-02-13 10:54:35;268;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2008-02-13 10:51:53;262;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2008-02-13 10:54:01;266;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2008-02-13 10:52:23;263;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2008-02-13 10:51:46;261;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2008-02-13 10:52:32;264;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2008-02-13 10:54:12;267;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2008-02-13 10:53:01;265;Solvay Membrane Sales Febr 2008-1.doc
doc doesn t have a link;2012-10-17 11:56:12;11012;000052431-1.pdf
doc linked to nothing;2012-03-21 11:21:19;10056;20120317_resume(Daisuke Kumagai)-1.doc
doc linked to nothing;2012-03-21 11:21:48;10057;20120317_職務経歴書（熊谷大佑）-1.doc
doc linked to nothing;2009-11-26 02:19:54;5495;299760 Naoki Sato original-1.pdf
doc linked to nothing;2009-11-20 06:41:28;5473;300302 Kazunori Hirata-1.doc
doc doesn t have a link;2011-09-14 05:33:36;9200;318916 Tsuguhito Shimada-1.doc
doc doesn t have a link;2008-02-27 08:57:28;462;Akiba-1.doc
doc linked to nothing;2008-03-03 11:24:53;511;Baba Hajime OriginalE-1.doc
doc doesn t have a link;2012-02-29 12:18:27;9913;Charmaine Luna-1.doc
doc linked to nothing;2009-02-12 10:05:46;4529;christian-1.doc
doc doesn t have a link;2011-01-27 03:01:54;7646;Churyon Ryu-1.odt
doc doesn t have a link;2008-06-20 12:28:03;2117;CV-Penny from ZARA-1.doc
doc doesn t have a link;2010-02-19 11:40:28;5750;Daisuke Imoto-1.odt
doc doesn t have a link;2011-11-24 02:08:22;9472;Date Eimi 2011 CV-1.pdf
doc doesn t have a link;2012-02-20 02:39:32;9851;Elsie Paras revised2012-1.doc
doc doesn t have a link;2012-10-26 03:59:15;11041;firstname_lastname_resume.html
doc linked to nothing;2010-03-15 04:37:00;5896;Fumie Ishizaki-1.odt
doc doesn t have a link;2010-07-15 03:27:48;6608;Fumio Komatsu-1.pdf
doc linked to nothing;2011-06-06 10:04:53;8548;Gaku Watanabe - HN-1.doc
doc doesn t have a link;2010-05-20 02:00:58;6214;Haraguchi_Megumi_eng_res_05_20-1.pdf
doc doesn t have a link;2010-06-30 02:27:02;6455;Harue Ito-1.doc
doc linked to nothing;2010-08-10 03:25:53;6759;Haruna Fukui-1.odt
doc doesn t have a link;2012-07-02 12:41:39;10691;Hiroaki-Suzuki(Actelion)-1.odt
doc doesn t have a link;2012-03-22 12:12:20;10071;Ishimura resume 2012 Mar 21-2-1.doc
doc doesn t have a link;2012-03-26 10:43:29;10084;Ishimura, Slate Mar.26 2012-1.odt
doc doesn t have a link;2012-05-10 10:51:10;10363;Kenichi Ueshima-1.doc
doc doesn t have a link;2012-01-31 11:16:42;9706;Kenji Mochizuki RESUME-1.doc
doc linked to nothing;2009-12-01 02:55:57;5509;KiyokoYoshida-E-1.doc
doc linked to nothing;2009-12-01 02:56:08;5510;KiyokoYoshida-J-1.doc
doc doesn t have a link;2011-07-27 04:37:47;8884;Manabu Tooriyama-1.doc
doc doesn t have a link;2010-06-25 03:40:38;6386; Masahiro Kusunoki for JTT Controller Mar 2010-1.pdf
doc linked to nothing;2008-04-18 01:52:59;1265;Matsuzaka 212210 resume-1.doc
doc doesn t have a link;2010-03-26 11:15:49;5958;Mika Asahi-1.odt
doc doesn t have a link;2010-07-12 08:00:44;6583;narumi-1.okazaki
doc doesn t have a link;2012-07-24 02:30:23;10965;Nobuhisa Suzaki-1.odt
doc doesn t have a link;2010-02-18 07:40:01;5749;Pawank Mandia-1.odt
doc doesn t have a link;2011-08-05 03:45:29;8942;Performance_Toshi Shirataki-1.pdf
doc doesn t have a link;2011-08-22 02:20:42;9044;resume-1-1.doc
doc doesn t have a link;2012-02-01 06:40:57;9732;Resume_E_111231-1.pdf
doc doesn t have a link;2012-07-13 04:20:14;10826;Resume Harunobu Yauchi 0522_12-1.odt
doc doesn t have a link;2008-08-06 03:57:43;2672;Resume of Tetsuro Matsumoto of Fleg International-1.doc
doc doesn t have a link;2009-09-11 10:07:17;5292;Ryosuke Hata Resume-1.doc
doc linked to nothing;2012-02-02 10:10:41;9734;Shigeki Mori CV Jan 2012-1.doc
doc doesn t have a link;2012-04-27 05:32:00;10305;SusumuMatsuzaki-1.pdf
doc linked to nothing;2010-08-17 10:46:30;6788;Suzuki Junko Original-1.doc
doc doesn t have a link;2011-08-04 11:19:49;8925;takiba_resume_20110803-1.doc
doc doesn t have a link;2008-02-26 06:07:48;429;TomoyukiWatanabe-1.doc
doc doesn t have a link;2008-02-26 06:07:59;430;TomoyukiWatanabe-1.doc
doc doesn t have a link;2012-04-25 03:41:30;10295;TomoyukiWatanabe-2.doc
doc doesn t have a link;2010-04-23 10:26:26;6090;Vincent_Poirier-1.doc
doc doesn t have a link;2010-07-27 06:07:29;6709;William DiGiorgio-1.docstopped at end of loop

 */

/*100 of local missing files are online */
/*
/data/documents/candi/file/Yoshimatsu_KunioAVOA での改善点-1-1.doc
/data/documents/candi/file/Horiuchi_Chieko_appliedlist-1.doc
/data/documents/candi/file/DrTakeshiKimuraFoodSafety080301-1.pdf
/data/documents/candi/file/004-007_haiti-1.pdf
/data/documents/candi/file/Notes from 228555-1.doc
/data/documents/candi/file/Donaldson Filtration Finance ManagerJD Jan 2008-1.pdf
/data/documents/candi/file/Soichi_Ota_2012-1.pdf
/data/documents/candi/file/採用通知　福盛 for Slate2-1.doc
/data/documents/candi/file/Ulf Schroder resume-1.pdf
/data/documents/candi/file/Marie Isomura summery-1.doc
/data/documents/candi/file/採用通知　福盛 for Slate-1.doc
/data/documents/candi/file/rbb compen matrix5-1.xls
/data/documents/candi/file/Computation Sheet - elizabeth Esquibel-1.xls
/data/documents/candi/file/Hiroaki Matsudaira 022708-1.xls
/data/documents/candi/file/Seki_Alex_cover-1.doc
/data/documents/candi/file/Cole Haan Japan-1.PDF
/data/documents/candi/file/Comp Form_Jay Ar GARCIA-1.docx
/data/documents/candi/file/Company visit questionnaire-1.doc
/data/documents/candi/file/8 Six Sigma Training-1.odt
/data/documents/candi/file/Reyes, Jayne_Slate_Comp-1.doc
/data/documents/candi/file/Ed Martin Sianghio Compensation and Benefits File-1.doc
/data/documents/candi/file/Aprilyn Escucha_Compensation Form-1.docx
/data/documents/candi/file/nakagiri PWC arata-1.pdf
/data/documents/candi/file/Call Log Project Description-1.doc
/data/documents/candi/file/Naoko_Hiramatsu_Recommendation_LetterNew-1.pdf
/data/documents/candi/file/Hideo Ishii PPT-1.pdf
/data/documents/candi/file/Liang_Yan_Diploma-1.pdf
/data/documents/candi/file/Slate 2007 BUsiness Plan Proposal-1.pdf
/data/documents/candi/file/Reyes, Teresa_Slate_Comp-1.doc
/data/documents/candi/file/Satoshi Suzuki - KCC Offer-1.doc
/data/documents/candi/file/Job Description of Mari Noda from Wyeth K-1.K
/data/documents/candi/file/Fukumori_Daisuke3-1.doc
/data/documents/candi/file/WESTMONT HOSPITALITY GROUP OCT 2007-1.pdf
/data/documents/candi/file/BelMontesCV-1.doc
/data/documents/candi/file/GAUTAM MISRA-1.doc
/data/documents/candi/file/Ayase-1.pdf
/data/documents/candi/file/Ed Sianghio Payslip-1.pdf
/data/documents/candi/file/Buensuceso, Ma-1. Kristine_Slate_Comp
/data/documents/candi/file/候補者様へのご案内　ジョンソンコントロールズへのアクセス2008-1.doc
/data/documents/candi/file/Bonifacio, Sheena_Slate_Comp-1.doc
/data/documents/candi/file/285818 - Michael Green job list-1.xls
/data/documents/candi/file/Westmont_Pres_Short_Nov2007_J-1.pdf
/data/documents/candi/file/Salary details.Manuel Argell III-1.doc
/data/documents/candi/file/2008-1.11
/data/documents/candi/file/cargillmap_en-1.doc
/data/documents/candi/file/Liezl NC-1.xls
/data/documents/candi/file/SHYU JESSALYN TENG_slate_COMP-1.pdf
/data/documents/candi/file/Astra Seneka_09_28_2009-1.PDF
/data/documents/candi/file/Mr-1.Mugikura
/data/documents/candi/file/Shimazu_Hidekazu_businessrelations-1.pdf
/data/documents/candi/file/Personal History-1.doc
/data/documents/candi/file/業務委託契約-1.doc
/data/documents/candi/file/Jason Salas-1.pdf
/data/documents/candi/file/cv_20120403(KONDO)_imsjapan-1.doc
/data/documents/candi/file/Slate continental contract-1.doc
/data/documents/candi/file/Salary Expectations-1.xls
/data/documents/candi/file/Trades Immigration to Canada March 2008-1.odt
/data/documents/candi/file/Roger Enyart - MEMC work summary-1.txt
/data/documents/candi/file/BeiGene welcomes the addition of George Chen-1.pdf
/data/documents/candi/file/printer@slate.co.jp_20110207_100940-1.pdf
/data/documents/candi/file/my.document.for.today-1.doc
/data/documents/candi/file/DAWN BARELLANO_COMPENSATION-1.docx
/data/documents/candi/image/Ding-1.jpg
/data/documents/candi/image/Ryoichi Kuramuchi-1.jpg
/data/documents/candi/image/Kenny Pan image-1.doc
/data/documents/candi/image/shark_hd_wallpaper-1.jpg
/data/documents/candi/image/DSC01518-1.jpg
/data/documents/candi/image/8655451614474l-1.jpg
/data/documents/candi/image/oka-1.jpg
/data/documents/candi/image/Firefox_wallpaper-1.png
/data/documents/candi/image/final-1.jpg
/data/documents/candi/image/simons wife-2.jpg
/data/documents/candi/image/David Yates Passport Photo March 08-1.jpg
/data/documents/candi/image/Yuka Taba Photo-1.JPG
/data/documents/candi/image/Slystem Users (inactive)-1.xls
/data/documents/candi/image/map1-b-1.jpg
/data/documents/candi/image/C.V. Eng_Dec10-1.doc
/data/documents/candi/image/1349074149.8344_soap-mobile.txt
/data/documents/candi/image/1351069375.2374_default_25.jpg
/data/documents/candi/image/cargillmap_en-1.doc
/data/documents/candi/image/250c5b2-1.jpg
/data/documents/candi/image/pic-1.aymblentbangandsomeone
/data/documents/candi/image/Merck Serono-1.jpg
/data/documents/candi/image/Accept-icon-1.png
/data/documents/candi/image/MihoYokoyama-1.jpg
/data/documents/candi/image/Tongco, Dale PS-1.jpg
/data/documents/candi/image/sakurai-1.jpg
/data/documents/candi/image/Marc Fong Pic-1.doc
/data/documents/candi/resume/004-007_haiti-1.pdf
/data/documents/candi/resume/採用通知　福盛 for Slate2-1.doc
/data/documents/candi/resume/20110929-1.doc
/data/documents/candi/resume/Call Log Project Description-1.doc
/data/documents/candi/resume/Slystem Users (inactive)-1.xls
/data/documents/candi/resume/C.V. Eng_Dec10-1.doc
/data/documents/candi/resume/Merck Serono-1.jpg
/data/documents/candi/resume/my.document.for.today-1.doc
/data/documents/company/file/C.V. Eng_Dec10-1.doc
/data/documents/company/file/cargillmap_en-1.doc
/data/documents/company/file/Astra Seneka_09_28_2009-1.PDF
/data/documents/company/image/Firefox_wallpaper-1.png
/data/documents/company/image/Accept-icon-1.png
*/


//to run from live slistem
// scp -r /data/documents/* root@192.168.10.115:/home/slate/public_html/slistem/__shared_upload__/slistem2_doc

// From aws
// scp -r -q root@203.167.38.10:/data/documents/* /hdd/www/slistem/__upload__/slistem2_doc

