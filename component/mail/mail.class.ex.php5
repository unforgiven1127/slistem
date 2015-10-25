<?php
/*
 *
 * example of how to use the mail ccomponent:
 *
 *
 * $oMail = CDependency::getComponentByName('mail');

  $oMail->createNewEmail();
  $oMail->setFrom('crm@bulbouscell.com', 'CRM notifyer');

  $oMail->addRecipient('sboudoux@bulbouscell.com', 'stef');
  $oMail->addCCRecipient('sboudoux@gmail.com', 'stef - CC- gmail');
  $oMail->addBCCRecipient('sboudoux@gmail.com', 'stef- bcc - gmail');

  $oResult = $oMail->send(' ohhhh subject', 'ahhhhhhh content', 'hiiiiiiiii text content');

  dump($oMail);

  dump($oResult);
  dump($oMail->getErrors());
 *
 *
 */

require_once('component/mail/mail.class.php5');
require_once('component/mail/phpmailer/class.phpmailer.php');

class CMailEx extends CMail
{
  private $coPhpMailer;
  private $casMailStatus;
  private $casError;

  //manage templates
  private $csTemplate = '';
  private $csHeader = '';
  private $csFooter = '';

  private $casRecipient = array();


  public function __construct()
  {
    $this->coPhpMailer = new PHPMailer(true);

    //true for non required options (can be changed to false if errors)
    $this->casMailStatus = array('hasFrom' => false, 'hasRecipient' => false, 'hasContent' => false, 'hasCC' => true, 'hasBCC' => true);
    $this->casError = array();

    $this->coPhpMailer->IsSMTP();
    $this->coPhpMailer->IsHTML(true);
  }

  public function createNewEmail()
  {
    $this->casMailStatus = array('hasFrom' => false, 'hasRecipient' => false, 'hasContent' => false, 'hasCC' => true, 'hasBCC' => true);
    $this->casError = array();
    $this->coPhpMailer = new PHPMailer();
    $this->coPhpMailer->IsSMTP();
    $this->coPhpMailer->IsHTML(true);

    $this->csTemplate = '';
    $this->csHeader = '';
    $this->csFooter = '';

    $this->casRecipient = array();

    return true;
  }

  public function getErrors($pbAsString = false)
  {
    if($pbAsString)
      return implode(', ', $this->casError);

    return $this->casError;
  }

  public function sendRawEmail($psSender, $psAddress, $psSubject, $psContent,$psCC='',$psBCC='')
  {
    $oHTML = CDependency::getCpHtml();

    $this->createNewEmail();
    $this->addRecipient($psAddress);

    if(!empty($psSender))
      $this->setFrom($psSender);

    if(!empty($psCC))
      $this->addCCRecipient($psCC);

    if(!empty($psBCC))
      $this->addBCCRecipient($psBCC);

    return (bool)$this->send($psSubject, $psContent);
  }

  public function addRecipient($pvRecipient, $psDisplayedName = '')
  {
    if(!assert('!empty($pvRecipient) && is_string($psDisplayedName)'))
      return 0;

    $nAddedRecipient = 0;

    if(is_array($pvRecipient))
    {
      foreach($pvRecipient as $vKey => $asRecipient)
      {
        if(isset($asRecipient['email']) && !empty($asRecipient['email']) && filter_var($asRecipient['email'], FILTER_VALIDATE_EMAIL) !== false)
        {
          if(!isset($asRecipient['name']))
            $asRecipient['name'] = $asRecipient['email'];

          $bAdded = $this->coPhpMailer->AddAddress($asRecipient['email'], $asRecipient['name']);
          if($bAdded)
          {
            $this->casRecipient[] = array($asRecipient['email'], $asRecipient['name']);
            $this->casMailStatus['hasRecipient'] = true;
            $nAddedRecipient++;
          }

        }
      }
    }
    else
    {

      if(filter_var($pvRecipient, FILTER_VALIDATE_EMAIL) === false)
        return 0;

      $bAdded = $this->coPhpMailer->AddAddress($pvRecipient, $psDisplayedName);
      if($bAdded)
      {
        $this->casRecipient[] = array($pvRecipient, $psDisplayedName);
        $nAddedRecipient++;
        $this->casMailStatus['hasRecipient'] = true;
      }
    }
    return $nAddedRecipient;
  }

  public function addCCRecipient($pvRecipient, $psDisplayedName = '')
  {
    if(!assert('!empty($pvRecipient) && is_string($psDisplayedName)'))
      return 0;

    $nAddedRecipient = 0;

    if(is_array($pvRecipient))
    {
      foreach($pvRecipient as $vKey => $asRecipient)
      {
        if(isset($asRecipient['email']) && !empty($asRecipient['email']) && filter_var($asRecipient['email'], FILTER_VALIDATE_EMAIL) !== false)
        {
          if(!isset($asRecipient['name']))
            $asRecipient['name'] = $asRecipient['email'];

          $bAdded = $this->coPhpMailer->AddCC($asRecipient['email'], $asRecipient['name']);
          if($bAdded)
          {
            $this->casMailStatus['hasCC'] = true;
            $nAddedRecipient++;
          }
        }
      }
    }
    else
    {
      if(filter_var($pvRecipient, FILTER_VALIDATE_EMAIL) === false)
        return 0;

      $bAdded = $this->coPhpMailer->AddCC($pvRecipient, $psDisplayedName);
      if($bAdded)
      {
        $this->casMailStatus['hasCC'] = true;
        $nAddedRecipient++;
      }
    }

    return $nAddedRecipient;
  }


  public function addBCCRecipient($pvRecipient, $psDisplayedName = '')
  {
    if(!assert('!empty($pvRecipient) && is_string($psDisplayedName)'))
      return 0;

    $nAddedRecipient = 0;

    if(is_array($pvRecipient))
    {
      foreach($pvRecipient as $vKey => $asRecipient)
      {
        if(isset($asRecipient['email']) && !empty($asRecipient['email']) && filter_var($asRecipient['email'], FILTER_VALIDATE_EMAIL) !== false)
        {
          if(!isset($asRecipient['name']))
            $asRecipient['name'] = $asRecipient['email'];

          $bAdded = $this->coPhpMailer->AddBCC($asRecipient['email'], $asRecipient['name']);
          if($bAdded)
          {
            $this->casMailStatus['hasBCC'] = true;
            $nAddedRecipient++;
          }
        }
      }
    }
    else
    {
      if(filter_var($pvRecipient, FILTER_VALIDATE_EMAIL) === false)
        return 0;

      $bAdded = $this->coPhpMailer->AddBCC($pvRecipient, $psDisplayedName);
      if($bAdded)
      {
        $this->casMailStatus['hasBCC'] = true;
        $nAddedRecipient++;
      }
    }

    return $nAddedRecipient;
  }

  public function addAllRecipient($pasRecipient)
  {
    if(!assert('is_array($pasRecipient) && !empty($pasRecipient)'))
      return 0;

    $nAddedRecipient = 0;

    foreach($pasRecipient as $vKey => $asRecipient)
    {
      if(isset($asRecipient['type']) && isset($asRecipient['email']) && !empty($asRecipient['email']) && filter_var($asRecipient['email'], FILTER_VALIDATE_EMAIL) !== false)
      {
        if(!isset($asRecipient['type']) || empty($asRecipient['type']))
          $asRecipient['type'] = 'to';
        else
        {
          if(!in_array($asRecipient['type'], array('to', 'cc', 'bcc', 'ReplyTo')))
            return 0;
        }

        if(!isset($asRecipient['name']))
          $asRecipient['name'] = $asRecipient['email'];

        $bAdded = $this->coPhpMailer->AddAnAddress($asRecipient['type'], $asRecipient['email'], $asRecipient['name']);
        if($bAdded)
        {
          switch($asRecipient['type'])
          {
            case 'to':
              $this->casMailStatus['hasRecipient'] = true;
              $this->casRecipient[] = array($asRecipient['email'], $asRecipient['name']);
              break;
            case 'cc': $this->casMailStatus['hasCC'] = true; break;
            case 'bcc': $this->casMailStatus['hasBCC'] = true; break;
            case 'ReplyTo': $this->casMailStatus['hasReplyTo'] = true; break;
            default:
              $this->casMailStatus['ready'] = false; break;
          }

          $nAddedRecipient++;
        }
      }
    }

    return $nAddedRecipient;
  }

  public function setFrom($psRecipient, $psDisplayedName = '', $pbAndReply = true)
  {
    if(!assert('!empty($psRecipient) && is_string($psDisplayedName) && is_bool($pbAndReply)'))
      return 0;

    if(filter_var($psRecipient, FILTER_VALIDATE_EMAIL) === false)
      return 0;

    $bAdded = $this->coPhpMailer->SetFrom($psRecipient, $psDisplayedName, (int)$pbAndReply);
    if($bAdded)
    {
      $this->casMailStatus['hasFrom'] = true;

      if($pbAndReply)
        $this->casMailStatus['hasReplyTo'] = true;

      return 1;
    }

    return 0;
  }

  public function setReplyTo($psRecipient, $psDisplayedName = '')
  {
    if(!assert('!empty($psRecipient) && is_string($psDisplayedName)'))
      return 0;

    if(filter_var($psRecipient, FILTER_VALIDATE_EMAIL) === false)
      return 0;

    $bAdded = $this->coPhpMailer->AddReplyTo($psRecipient, $psDisplayedName, 1);
    if($bAdded)
    {
      $this->casMailStatus['hasReplyTo'] = true;
      return 1;
    }

    return 0;
  }

  public function isReady()
  {
    if(!$this->casMailStatus['hasFrom'])
    {
      $oLogin = CDependency::getCpLogin();
      $this->setFrom($oLogin->getUserEmail(), $oLogin->getCurrentUserName());

      $this->casMailStatus['hasFrom'] = true;
    }

    foreach($this->casMailStatus as $bStatus)
    {
      if(!$bStatus)
        return false;
    }

    return true;
  }

  /*
   * Will return the email pk after email sent and logged in DB
  */
  public function send($psSubject, $psContent, $psTextContent = '', $pasAttachement = array(), $psTemplate = '', $pasTemplateVar = array())
  {
    if(!assert('!empty($psSubject) && !empty($psContent)'))
     return 0;

    $sEncoding = mb_check_encoding($psSubject);
    if($sEncoding != 'UTF8')
      $this->coPhpMailer->Subject = mb_convert_encoding($psSubject, 'utf8');
    else
      $this->coPhpMailer->Subject = $psSubject;

    $this->casMailStatus['hasSubject'] = true;


    //------------------------------------------------
    // apply a template if none have been set
    if(empty($this->csTemplate))
    {
      $this->loadTemplate($psTemplate, $pasTemplateVar);
    }

    $psContent = $this->csHeader . $psContent . $this->csFooter;


    $sEncoding = mb_check_encoding($psSubject);
    if($sEncoding != 'UTF8')
      $this->coPhpMailer->Body = mb_convert_encoding($psContent, 'utf8');
    else
     $this->coPhpMailer->Body = $psContent;

    $this->casMailStatus['hasContent'] = true;


    //manage the default replyTo: no-reply address
    if(!isset($this->casMailStatus['hasReplyTo']))
    {
      $this->setReplyTo('no-reply@'.CONST_PHPMAILER_DOMAIN, '');
    }


    if(empty($psTextContent))
    {
      $psContent = str_ireplace(array('<br>','<br >','<br/>','<br />','</p>'), PHP_EOL, $psContent);
      $this->coPhpMailer->AltBody = strip_tags($psContent);
    }
    else
      $this->coPhpMailer->AltBody = strip_tags($psTextContent);


    if(!$this->isReady())
    {
      $this->casError[] = __LINE__.' - Mail is not setup properly. (isReady() = false ['.serialize($this->casMailStatus).']) ';
      return 0;
    }

    if(!empty($pasAttachement))
    {
      foreach($pasAttachement as $sFilePath)
      {
        if(!file_exists($sFilePath))
        {
          $this->casError[] = __LINE__.' - Can\'t find mail attachement file ['.$sFilePath.'] ';
          return 0;
        }

        $nFileSize = filesize($sFilePath);
        if( $nFileSize > CONST_PHPMAILER_ATTACHMENT_SIZE)
        {
          $this->casError[] = __LINE__.' - Attachement size too big. ('.$nFileSize.' > '.CONST_PHPMAILER_ATTACHMENT_SIZE.') ';
          return 0;
        }

        $this->coPhpMailer->AddAttachment($sFilePath);
      }
    }

    if(!$this->_send())
    {
      $this->casError[] = __LINE__.' - Error sending email [ imap:'.(int)CONST_MAIL_IMAP_SEND.' / log:'.CONST_MAIL_IMAP_LOG_SENT.'] ';
      return 0;
    }


    //TODO: log mail in DB
    return 1;
  }


  private function _send()
  {
    if(CONST_DEV_SERVER == 1)
    {
      //dump($this->coPhpMailer);
      //replace all recipeints by DEV_EMAIL
      $this->coPhpMailer->to = array(array(CONST_DEV_EMAIL, 'slistem dev'));
      $this->coPhpMailer->cc = array();
      $this->coPhpMailer->bcc = array();
      $this->coPhpMailer->all_recipients = array();
      //$this->coPhpMailer->ReplyTo = array(array(CONST_DEV_EMAIL));
    }

    //==========================================================================
    //==========================================================================
    //2 ways of sending emails
    if(CONST_MAIL_IMAP_SEND)
    {
      //manage only 1 to
      $sHeader = $this->coPhpMailer->CreateHeader();

      $nTimeout = imap_timeout(IMAP_READTIMEOUT, 5);
      $bSent = imap_mail($this->_stringifyEmail($this->coPhpMailer->to),
              $this->coPhpMailer->Subject,
              $this->coPhpMailer->Body,
              $sHeader,
              $this->_stringifyEmail($this->coPhpMailer->cc),
              $this->_stringifyEmail($this->coPhpMailer->bcc));

      $sError = 'php_imap_error: '.(string)imap_last_error();
    }
    else
    {
      //Default case, use PHPmailer
      $sError = '';
      try
      {
        $bSent = (bool)$this->coPhpMailer->Send();
      }
      catch (phpmailerException $e)
      {
        $sError .= $e->errorMessage().' ';
      }
      $sError .= 'smtp_error: '.$this->coPhpMailer->ErrorInfo;
    }

    if(!$bSent)
    {
      $this->casError[] = __LINE__.' - Error sending email ['.$sError.']';
      return false;
    }

    //if we need to log the mail history somewhere
    if(CONST_MAIL_IMAP_LOG_SENT)
    {
      $nTimeout = imap_timeout(IMAP_OPENTIMEOUT, 3);
      $oMailBox = imap_open(CONST_MAIL_IMAP_LOG_PARAM_INBOX, CONST_PHPMAILER_SMTP_LOGIN, CONST_PHPMAILER_SMTP_PASSWORD);

      if($oMailBox === false)
      {
        assert('false; // could not connect to '.CONST_MAIL_IMAP_LOG_PARAM_INBOX);
        return false;
      }

      $nTimeout = imap_timeout(IMAP_WRITETIMEOUT, 3);
      imap_append($oMailBox, CONST_MAIL_IMAP_LOG_PARAM_SENT,
     "From: slistem@slate.co.jp\r\n" . "To: ".$this->_stringifyEmail($this->coPhpMailer->to)."\r\n" .
     "Subject: ".$this->coPhpMailer->Subject."\r\n" . "Date: ".date("r", strtotime("now"))."\r\n" .
     'Content-Type: text/html; charset=utf-8' . "\r\n" . "Content-Transfer-Encoding: 8bit \r\n" .
     "\r\n".$this->coPhpMailer->Body."\r\n", '\\Seen' );

      @imap_close($oMailBox);
    }


    return $bSent;
  }



  private function _stringifyEmail($pasEmail)
  {
    $asTo = array();
    foreach($pasEmail as $asRecipient)
    {
      if(!empty($asRecipient))
      {
        $sTo = $asRecipient[0];
        if(count($asRecipient) == 2)
          $sTo.= ' <'.$asRecipient[0].'>';

        $asTo[] = $sTo;
      }
    }


    return implode(',', $asTo);
  }


  // ================================================================================
  // ================================================================================
  // template management

  public function loadTemplate($psTemplateName, $pasTemplateVars=array())
  {
    //TODO: Save templates in the database + add vars

    if($psTemplateName == 'raw_content')
      return $this->setRawEmail();

    //---------------------------------------------------
    //look if there's a template defined in the config files
    $sHeader = $sFooter = '';

    if(function_exists('getMailHeader'))
    {
      $sHeader = getMailHeader($psTemplateName, $pasTemplateVars);
    }

    if(function_exists('getMailFooter'))
    {
      $sFooter = getMailFooter($psTemplateName, $pasTemplateVars);
    }

    //if none we load the default one
    if(empty($sHeader) && empty($sFooter))
      return $this->loadDefaultTemplate();

    $this->csTemplate = $psTemplateName;
    $this->csHeader = $sHeader;
    $this->csFooter = $sFooter;

    return true;
  }

  /**
   * Remove template and send the email raw
   * @return true
   */
  public function setRawEmail()
  {
    $this->csTemplate = 'raw_content';
    $this->csHeader = '';
    $this->csFooter = '';

    return true;
  }


  function loadDefaultTemplate()
  {
    $oHTML = CDependency::getCpHtml();
    $this->csTemplate = 'default';

    $sContent = "<html><body style='font-family: Verdana, Arial; font-size: 11px;'>";
    $sContent.= $oHTML->getBlocStart();

    if(CONST_MAIL_HEADER_PICTURE)
    {
      $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:400px; height: 40px; background-color: #ececec; margin: 5px 0;'));
      $sContent.= $oHTML->getPicture(CONST_MAIL_HEADER_PICTURE, '', CONST_CRM_DOMAIN, array('alt' => CONST_CRM_DOMAIN, 'style' => 'height: 40px;'));
      $sContent.= ' '.CONST_APP_NAME.' notifier';
      $sContent.= $oHTML->getBlocEnd();
    }

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:400px;  border-left: 2px solid #999; min-height:300px; margin: 8px 0; padding: 5px 0;'));
    $sContent.= $oHTML->getBlocStart('', array('style' => 'margin:15px;'));
    $this->csHeader = $sContent;



    $sContent = $oHTML->getBlocEnd();
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocStart('', array('style' => 'min-width:680px; padding: 2px 15px; font-size: 10px; font-style: italic; color: #444444; height: 18px; background-color: #ececec;'));
    $sContent.= 'Sent by '.CONST_APP_NAME.' © '.date('Y').'';
    $sContent.= $oHTML->getBlocEnd();

    $sContent.= $oHTML->getBlocEnd();
    $sContent.= '</body></html>';
    $this->csFooter = $sContent;

    return true;
  }


}