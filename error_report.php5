<?php
header("X-XSS-Protection: 0");
//if you want session to be handled in database
require_once('./common/lib/db_session.inc.php5');
session_start();


//receive the ajax dump data
if(!empty($_POST['mail']))
{
  /* send an email with everything we've got:
   * http referer, session (user), dump data received
   */

  $sMessage = 'Informations gathered concerning the crash:'."\n";


  if(isset($_FILES) && !empty($_FILES))
  {
    if(!empty($_FILES['screenshot_1']['tmp_name']))
    {
      $sPath = $_SERVER['DOCUMENT_ROOT'].'/common/upload/error/'.time().'_screebshot_1_'.$_FILES['screenshot_1']['name'];
      $bMoved = move_uploaded_file($_FILES['screenshot_1']['tmp_name'], $sPath);

       //var_dump($_FILES['screenshot_1']['tmp_name']);

      if($bMoved)
        $sMessage.= "\n\n  file uploaded:  ".$sPath;
      else
        $sMessage.= "\n\n  ERROR: could not move the file :  ".$_FILES['screenshot_1']['tmp_name'];
    }

    if(!empty($_FILES['screenshot_2']['tmp_name']))
    {
      $sPath = $_SERVER['DOCUMENT_ROOT'].'/common/upload/error/'.time().'_screebshot_2_'.$_FILES['screenshot_2']['name'];
      $bMoved = move_uploaded_file($_FILES['screenshot_2']['tmp_name'], $sPath);

      //var_dump($_FILES['screenshot_2']['tmp_name']);

      if($bMoved)
        $sMessage.= "\n\n  file uploaded:  ".$sPath;
      else
        $sMessage.= "\n\n  ERROR: could not move the file :  ".$_FILES['screenshot_2']['tmp_name'];
    }

    if(!empty($_FILES['screenshot_3']['tmp_name']))
    {
      $sPath = $_SERVER['DOCUMENT_ROOT'].'/common/upload/error/'.time().'_screebshot_3_'.$_FILES['screenshot_3']['name'];
      $bMoved = move_uploaded_file($_FILES['screenshot_3']['tmp_name'], $sPath);

      //var_dump($_FILES['screenshot_3']['tmp_name']);

      if($bMoved)
        $sMessage.= "\n\n  file uploaded:  ".$sPath;
      else
        $sMessage.= "\n\n  ERROR: could not move the file :  ".$_FILES['screenshot_3']['tmp_name'];
    }

  }

  if(isset($_POST['dump_html']) && !empty($_POST['dump_html']))
  {
    $sFile = '/common/upload/error/'.time().'_pagecontent.html';
    $sPath = $_SERVER['DOCUMENT_ROOT'].$sFile;

    $oFs = fopen($sPath, 'w+');
    @fputs($oFs, $_POST['dump_html']);
    @fclose($oFs);

    $_POST['dump_html'] = 'html file available here : https://'.$_SERVER['SERVER_NAME'].$sFile;
  }


  $sMessage.="------------------------------------------------------------\n\n Post: \n\n";
  $sMessage.= html_entity_decode(var_export($_POST, true));
  $sMessage.= "\n\n";

  $asData = $_SESSION;
  unset($asData['folder_tree']);
  unset($asData['settings']['menunav1']);
  unset($asData['settings']['menunav2']);

  unset($asData['sl_industry_list0']);
  unset($asData['sl_occupation_list0']);
  unset($asData['sl_industry_list1']);
  unset($asData['sl_occupation_list1']);
  unset($asData['HOME_PAGE_CHARTS']);

  $sMessage.="------------------------------------------------------------\n\n Session: \n\n";
  $sMessage.= var_export($asData, true);
  $sMessage.= "\n\n";


  $bSent = mail('dcepulis@slate-ghc.com', 'Error on Slistem ', $sMessage);
  if(!$bSent)
  {
    $oFs = @fopen($_SERVER['DOCUMENT_ROOT'].'/error_report.txt', 'a+');
    if($oFs)
    {
      @fputs($oFs, $sMessage);
      @close($oFs);
    }
  }

  sleep(3);
  //header('location: /index.php5?sent='.(int)$bSent);

}
?>

<html>

<head>
  <title>Slistem Error report</title>

  <link media="screen" type="text/css" href="/common/style/style.css?n=1317368869" rel="stylesheet">
  <script src="/common/js/jquery.js" type="text/javascript"></script>
  <style>
  form
  {
    line-height: 20px;
  }
  input[type=text], textarea
  {
    width: 820px;
  }
  fieldset
  {
    padding: 0 15px;
  }
  .error_report_body{ font-size:12px; background-color: #f0f0f0; }
  .error_main_container{ width: 900px; margin: 25px auto; }
  </style>
</head>

<body class="error_report_body" style="">
  <div class="error_main_container">

<?php

if(!empty($_POST['mail']))
{
  echo 'Error reporting form:<br/>
  Thank you for your report. You will be redirected soon.
  <br />
  <br />
  <br />
  <img style="text-align: center;" src="/common/pictures/loading_8.gif" />
  <br />
  <br />
  <script>setTimeout("document.location.href = \'/index.php5\'", 7000);</script>
  <br />';
}
else
{
  echo '
 <fieldset>
 <legend style="font-size: 18px; font-weight: bold; color: #78A3D1; padding: 5px 10px;">Error report</legend>
 <br />
 Thank you for reporting an issue, we\'ll do our best to treat it as soon as possible.<br />
 Do not hesitate to contact us directly if you are stuck because of this error.<br />
 <br />

 <form name="dumpForm" method="post" enctype="multipart/form-data" action="" >

 <input type="hidden" name="mail" value="'.  uniqid() .'" />';



 if(!empty($_POST['dump']))
 {
   /* send an email with everything we've got:
    * http referer, session (user)
    */
   echo "<input type='hidden' name='dump' id='dumpId' value=\"".$_POST['dump']."\" />";
 }

 if(!empty($_POST['dump_html']))
 {
   echo "<textarea style='display: none;' name='dump_html' id='dump_htmlId'>".htmlentities($_POST['dump_html'])."</textarea>";
 }


  echo '
 <br />
 User name / account: <br/>
 <input type="text" name="user" value=""/> <br /><br />

 Problem description and/or error message: <br />
 <textarea name="description" cols="120" rows="5" ></textarea><br /><br />

 When did it occurred : <br />
 <textarea name="actions" cols="120" rows="5" ></textarea><br /><br />

 Your settings : <br />
 <span style="color: #aaa; font-style: italic; line-height: 22px;">(web-browser used, version...)</span><br />
 <textarea name="actions" cols="120" rows="5" ></textarea><br /><br />

Screenshot : <br /><br />
 #1 <input type="file" name="screenshot_1" value="" /> <br />
 #2 <input type="file" name="screenshot_2" value="" /> <br />
 #3 <input type="file" name="screenshot_3" value="" /> <br />
<br /><br />

 <br />
 <input type="submit" value="Send the error report" />
 <br /><br />


 </form>';
}
?>
  </div>
</body>
</html>