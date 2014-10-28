<?php

// PHP setup

//define assert statments
assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_BAIL, false);
assert_options(ASSERT_WARNING, false);
assert_options(ASSERT_QUIET_EVAL, false);

//safety for cronjobs
if(empty($_SERVER['DOCUMENT_ROOT']))
  $_SERVER['DOCUMENT_ROOT'] = '/';

define('CONST_DEBUG_ASSERT_LOG_PATH', $_SERVER['DOCUMENT_ROOT'].'/assert.log');

// ==============================================
// PHP constants

//---------------------------------------
//---------------------------------------
// WEBSITE CONFIG

switch(trim($_SERVER['SERVER_NAME']))
{

  case 'job.slate.co.jp':
  case 'jobs.slate.co.jp':

    define('DB_NAME', 'jobboard');
    define('DB_SERVER', '127.0.0.1');
    define('DB_USER', '');
    define('DB_PASSWORD', ''); //local
    //define('DB_PASSWORD', 'KCd7C56XJ8Nud7uF');  //online

    define('CONST_WEBSITE', 'jobboard');
    define('CONST_APP_NAME', 'Slate Jobboard');

    define('CONST_CRM_HOST', $_SERVER['SERVER_NAME'].'');
    define('CONST_CRM_DOMAIN', 'http://'.$_SERVER['SERVER_NAME'].'');
    define('CONST_CRM_MAIL_SENDER', 'no-reply@slate.co.jp');
    define('CONST_DEV_SERVER', 1);
    define('CONST_SQL_PROFILING', 0);
    define('CONST_DEV_EMAIL', 'sboudoux@bulbouscell.com');
    define('CONST_DEV_EMAIL_2', 'sboudoux@bulbouscell.com');
    define('CONST_SHOW_MENU_BAR', 1);
    define('CONST_VERSION', '0.1');
    define('CONST_DISPLAY_VERSION', 0);

    //---------------------------------------
    //Specific environment variables
    define('CONST_PHPMAILER_SMTP_DEBUG', false);
    define('CONST_PHPMAILER_EMAIL', 'no-reply@slate.co.jp');
    define('CONST_PHPMAILER_DEFAULT_FROM', 'Slate Consulting');
    define('CONST_PHPMAILER_ATTACHMENT_SIZE', 10485760);
    /*define('CONST_PHPMAILER_SMTP_HOST', 'mail.slate.co.jp');
    define('CONST_PHPMAILER_SMTP_PORT', 465);
    define('CONST_PHPMAILER_SMTP_LOGIN', 'no_reply');
    define('CONST_PHPMAILER_SMTP_PASSWORD', 'No_Reply\'sPassword1');*/
    define('CONST_PHPMAILER_SMTP_HOST', 'mail.bulbouscell.com');
    define('CONST_PHPMAILER_SMTP_PORT', 465);
    define('CONST_PHPMAILER_SMTP_LOGIN', 'bcm@bulbouscell.com');
    define('CONST_PHPMAILER_SMTP_PASSWORD', 'AB1gOne!');

    define('CONST_MAIL_IMAP_SEND', false);
    define('CONST_MAIL_IMAP_LOG_SENT', false);
    //--------------------------------------
    //required if of of the above are true
    define('CONST_MAIL_IMAP_PORT', 0);  //imap
    define('CONST_MAIL_IMAP_LOG_PARAM_INBOX', '');
    define('CONST_MAIL_IMAP_LOG_PARAM_SENT', '');




    define('CONST_AVAILABLE_LANGUAGE', 'en,jp');
    define('CONST_DEFAULT_LANGUAGE', 'en');
    define('CONST_PAGE_USE_WINDOW_SIZE', false);
    define('CONST_SS_MAX_DOCUMENT_SIZE', 20485760); //20MB
    define('CONST_SS_MAX_PROCESSABLE_SIZE', 1048576); //1MB

    define('CONST_NOTIFY_DEFAULT_RECIPIENTPK', 0);
    define('CONST_LOGIN_DEFAULT_LIST_GRP', -1);
    define('CONST_LOGIN_DISPLAYED_GRP', '');


    assert_options(ASSERT_CALLBACK, 'mailAssert');
    break;

    //through loadbalancer
    case 'slistem.slate.co.jp':
    case 'beta.slate.co.jp':

    //direct server access
    case 'slistem1.slate.co.jp':
    case 'slistem2.slate.co.jp':
    case 'beta1.slate.co.jp':
    case 'beta2.slate.co.jp':

    //previous domain s
    //case 'slistem3.slate.co.jp':
    //case 'squirrel.slate.co.jp':
    //case 'beta3.slate.co.jp':



    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    // manage loadbalancer non transparency. I might have an environment var from tyhe VHOST, or a HTTP_X_FORWARDED_FOR entry

    if(isset($_SERVER['REAL_IP']) && !empty($_SERVER['REAL_IP']))
    {
      $_SERVER['REMOTE_ADDR'] = $_SERVER['REAL_IP'];
    }
    elseif($_SERVER['REMOTE_ADDR'] == '172.31.28.127' && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
      $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-

    define('DB_NAME', 'slistem');
    define('DB_SERVER', '127.0.0.1');
    //define('DB_SERVER', '172.31.29.60');
    define('DB_USER', 'slistem');
    define('DB_PASSWORD', 'THWj8YerbMWfK3yW');

    define('CONST_WEBSITE', 'slistem');
    define('CONST_APP_NAME', 'Sl[i]stem');

    define('CONST_CRM_HOST', $_SERVER['SERVER_NAME'].'');
    define('CONST_CRM_DOMAIN', 'https://'.$_SERVER['SERVER_NAME']);
    define('CONST_CRM_MAIL_SENDER', 'slistem@slate.co.jp');
    define('CONST_DEV_SERVER', 0);  //debug bar, mail to developer, and other developer features
    define('CONST_SQL_PROFILING', 0);
    define('CONST_DEV_EMAIL', 'sboudoux@bulbouscell.com');
    define('CONST_DEV_EMAIL_2', 'sboudoux@bulbouscell.com');
    define('CONST_SHOW_MENU_BAR', 1);
    define('CONST_VERSION', '-1.0');
    define('CONST_DISPLAY_VERSION', 0);
 //---------------------------------------
    //Specific environment variables

    define('CONST_PHPMAILER_SMTP_DEBUG', false);
    define('CONST_PHPMAILER_DOMAIN', 'slate.co.jp');
    define('CONST_PHPMAILER_EMAIL', 'slistem@slate.co.jp');
    define('CONST_PHPMAILER_DEFAULT_FROM', 'Slistem');
    define('CONST_PHPMAILER_ATTACHMENT_SIZE', 10485760);

    define('CONST_PHPMAILER_SMTP_PORT', 465); //smtp
    define('CONST_PHPMAILER_SMTP_HOST', 'mail.slate.co.jp');
    define('CONST_PHPMAILER_SMTP_LOGIN', 'slistem@slate.co.jp');
    //define('CONST_PHPMAILER_SMTP_LOGIN', 'catchall@slistem.slate.co.jp');
    //define('CONST_PHPMAILER_SMTP_HOST', 'imap.slistem.slate.co.jp');

    define('CONST_PHPMAILER_SMTP_PASSWORD', 'Slate!7000ics');

    //to send emails using IMAP instead of smtp
    define('CONST_MAIL_IMAP_SEND', false);
    //Log a copy of all emails sent by the platform a in the sent folder
    define('CONST_MAIL_IMAP_LOG_SENT', true);
    //--------------------------------------
    //required if of of the above are true
    define('CONST_MAIL_IMAP_PORT', 7993);  //imap
    define('CONST_MAIL_IMAP_LOG_PARAM_INBOX', '{'.CONST_PHPMAILER_SMTP_HOST.':'.CONST_MAIL_IMAP_PORT.'/debug/readonly/imap/ssl/novalidate-cert}inbox');
    define('CONST_MAIL_IMAP_LOG_PARAM_SENT', '{'.CONST_PHPMAILER_SMTP_HOST.':'.CONST_MAIL_IMAP_PORT.'/imap/ssl/novalidate-cert}sent');
    define('CONST_MAIL_IMAP_CATCHALL_ADDRESS', 'slistem@imap.slistem.slate.co.jp'); //use the domain to look into mail header


    define('CONST_AVAILABLE_LANGUAGE', 'en,jp');
    define('CONST_DEFAULT_LANGUAGE', 'en');
    define('CONST_PAGE_USE_WINDOW_SIZE', true);
    define('CONST_SS_MAX_DOCUMENT_SIZE', 5242880); //5MB
    define('CONST_SS_MAX_PROCESSABLE_SIZE', 2097152); //2MB

    define('CONST_FOLDER_LOADING_MODE', 1);
    define('CONST_SAVE_BANDWIDTH', 0);

    //assert_options(ASSERT_CALLBACK, 'displayAssert');
    assert_options(ASSERT_CALLBACK, 'mailAssert');

    define('CONST_NOTIFY_DEFAULT_RECIPIENTPK', 260);  //Mitchmallo dba
    define('CONST_LOGIN_DEFAULT_LIST_GRP', 116);
    define('CONST_LOGIN_DISPLAYED_GRP', '2,3,4,6,7,103,105,106,107,108,109,110,111,112,113,114,116');

    break;


    case 'slistem.devserv.com':
    case 'slistem.bulbouscell.com':
    case 'stephane.slate.co.jp':


    define('DB_NAME', 'slistem');
    define('DB_SERVER', '127.0.0.1');
    define('DB_USER', 'slistem');
    define('DB_PASSWORD', 'THWj8YerbMWfK3yW');

    define('CONST_WEBSITE', 'slistem');
    define('CONST_APP_NAME', 'Sl[i]stem');
    define('CONST_MAINTENANCE', 0);

    define('CONST_CRM_HOST', $_SERVER['SERVER_NAME'].'');
    define('CONST_CRM_DOMAIN', 'https://'.$_SERVER['SERVER_NAME']);
    define('CONST_CRM_MAIL_SENDER', 'slistem@slate.co.jp');
    define('CONST_DEV_SERVER', 1);  //debug bar, mail to developer, and other developer features
    define('CONST_SQL_PROFILING', 0);
    define('CONST_DEV_EMAIL', 'sboudoux@bulbouscell.com');
    define('CONST_DEV_EMAIL_2', 'sboudoux@bulbouscell.com');
    define('CONST_SHOW_MENU_BAR', 1);
    define('CONST_VERSION', '-1.0');
    define('CONST_DISPLAY_VERSION', 0);

    //---------------------------------------
    //Specific environment variables

    define('CONST_PHPMAILER_SMTP_DEBUG', false);
    define('CONST_PHPMAILER_DOMAIN', 'slate.co.jp');
    define('CONST_PHPMAILER_EMAIL', 'slistem@slate.co.jp');
    define('CONST_PHPMAILER_DEFAULT_FROM', 'Slistem');
    define('CONST_PHPMAILER_ATTACHMENT_SIZE', 10485760);

    define('CONST_PHPMAILER_SMTP_PORT', 465); //smtp
    define('CONST_PHPMAILER_SMTP_HOST', 'mail.slate.co.jp');
    define('CONST_PHPMAILER_SMTP_LOGIN', 'slistem@slate.co.jp');
    //define('CONST_PHPMAILER_SMTP_LOGIN', 'catchall@slistem.slate.co.jp');
    //define('CONST_PHPMAILER_SMTP_HOST', 'imap.slistem.slate.co.jp');

    define('CONST_PHPMAILER_SMTP_PASSWORD', 'Slate!7000ics');

    //to send emails using IMAP instead of smtp
    define('CONST_MAIL_IMAP_SEND', true);
    //Log a copy of all emails sent by the platform a in the sent folder
    define('CONST_MAIL_IMAP_LOG_SENT', true);

    //--------------------------------------
    //required parameters if one of the above are true
    define('CONST_MAIL_IMAP_PORT', 7993);  //imap
    define('CONST_MAIL_IMAP_LOG_PARAM_INBOX', '{'.CONST_PHPMAILER_SMTP_HOST.':'.CONST_MAIL_IMAP_PORT.'/debug/readonly/imap/ssl/novalidate-cert}inbox');
    define('CONST_MAIL_IMAP_LOG_PARAM_SENT', '{'.CONST_PHPMAILER_SMTP_HOST.':'.CONST_MAIL_IMAP_PORT.'/imap/ssl/novalidate-cert}sent');
    define('CONST_MAIL_IMAP_CATCHALL_ADDRESS', 'slistem@imap.slistem.slate.co.jp'); //use the domain to look into mail header


    define('CONST_AVAILABLE_LANGUAGE', 'en,jp');
    define('CONST_DEFAULT_LANGUAGE', 'en');
    define('CONST_PAGE_USE_WINDOW_SIZE', true);
    define('CONST_SS_MAX_DOCUMENT_SIZE', 5242880); //5MB
    define('CONST_SS_MAX_PROCESSABLE_SIZE', 2097152); //2MB

    define('CONST_FOLDER_LOADING_MODE', 1);
    define('CONST_SAVE_BANDWIDTH', 0);

    assert_options(ASSERT_CALLBACK, 'displayAssert');
    define('CONST_NOTIFY_DEFAULT_RECIPIENTPK', 260);  //Mitchmallo dba
    define('CONST_LOGIN_DEFAULT_LIST_GRP', 116);
    define('CONST_LOGIN_DISPLAYED_GRP', '2,3,4,6,7,103,106,107,108,109,110,111,112,113,114,116');
    break;

  default:
    exit($_SERVER['SERVER_NAME'].' -> error in website parameters');
}


if(!defined('CONST_MAINTENANCE'))
  define('CONST_MAINTENANCE', 0);

if(!defined('CONST_LOGIN_USER_MANAGE_ACCOUNT'))
  define('CONST_LOGIN_USER_MANAGE_ACCOUNT', 0);

if(!defined('CONST_SAVE_BANDWIDTH'))
  define('CONST_SAVE_BANDWIDTH', 0);

//---------------------------------------
//url constants
define('CONST_URL_UID', 'uid');
define('CONST_URL_ACTION', 'ppa');
define('CONST_URL_ACTION_RETURN', 'ppar');
define('CONST_URL_TYPE', 'ppt');
define('CONST_URL_PK', 'ppk');
define('CONST_URL_MODE', 'pg');
define('CONST_URL_EMBED', 'embed');

define('CONST_URL_PARAM_PAGE_AJAX', 'ajx');
define('CONST_URL_PARAM_PAGE_NORMAL', 'pn');
define('CONST_URL_PARAM_PAGE_EMBED', 'emb');
define('CONST_URL_PARAM_PAGE_CRON', 'cron');
define('CONST_ACTION_FULL_LIST', 'ppafl');
define('CONST_ACTION_LIST', 'ppal');
define('CONST_ACTION_VIEW', 'ppav');
define('CONST_ACTION_EMAIL','ppaem');
define('CONST_ACTION_VIEW_DETAILED', 'ppavd');
define('CONST_ACTION_FASTEDIT', 'ppafe');
define('CONST_ACTION_EDIT', 'ppae');
define('CONST_ACTION_ADD', 'ppaa');
define('CONST_ACTION_SAVEADD', 'ppasa');
define('CONST_ACTION_SAVEEDIT', 'ppase');
define('CONST_ACTION_VALIDATE', 'ppava');
define('CONST_ACTION_DELETE', 'ppad');
define('CONST_ACTION_RESET', 'ppares');
define('CONST_ACTION_REFRESH', 'pparef');
define('CONST_ACTION_SEND', 'ppasen');
define('CONST_ACTION_DONE', 'ppado');
define('CONST_ACTION_DOWNLOAD', 'ppadown');
define('CONST_ACTION_SEARCH', 'ppasea');
define('CONST_ACTION_RESULTS', 'ppares');
define('CONST_ACTION_MANAGE', 'ppam');
define('CONST_ACTION_TRANSFER','ppat');
define('CONST_ACTION_SAVETRANSFER','ppast');
define('CONST_ACTION_SAVEMANAGE','ppasm');
define('CONST_ACTION_SAVECOMPANY_RELATION','ppacpr');
define('CONST_ACTION_SAVE_CONFIG','ppasc');
define('CONST_ACTION_LOGOUT','ppalgt');
define('CONST_ACTION_APPLY','ppaly');
define('CONST_ACTION_LOG','ppalog');
define('CONST_ACTION_SEARCHDUPLICATES','srcdp');

define('CONST_PAGE_DEVICE_TYPE_PHONE', 'page_phone');
define('CONST_PAGE_DEVICE_TYPE_PC', 'page_pc');
define('CONST_PAGE_DEVICE_TYPE_TABLET', 'page_tablet');
define('CONST_PAGE_NO_LOGGEDIN_CSS', 'ffpcss');
define('CONST_PAGE_TYPE_SETTING', 'pset');


define('CONST_PHP_NOVARTYPE','notype');
define('CONST_PHP_VARTYPE_INT','int');
define('CONST_PHP_VARTYPE_FLOAT','float');
define('CONST_PHP_VARTYPE_BOOL','bool');
define('CONST_PHP_VARTYPE_ARRAY','array');
define('CONST_PHP_VARTYPE_SERIALIZED','serial');
define('CONST_PHP_VARTYPE_JSON','json');
define('CONST_PHP_VARTYPE_STR','str');

//define('CONST_PATH_JS_DATEPICKER', '/common/js/jquery-ui/datepicker-redmond/js/jquery-ui-1.8.16.custom.min.js');
define('CONST_PATH_JS_DATEPICKER', '/common/js/jquery-ui.js');

//define('CONST_PATH_JS_TIMEPICKER', '/common/js/jquery-ui/timepicker-redmond/js/jquery-ui-1.8.16.custom.min.js');
define('CONST_PATH_JS_TIMEPICKER', '/common/js/jquery-ui/timepicker-redmond/js/timepicker.js');


define('CONST_PATH_JS_DRAGDROP', '/common/js/jquery-ui/dragdrop-redmond/jquery-ui.min.js');
define('CONST_PATH_JS_MULTIDRAG', '/common/js/jquery-ui/dargdrop-redmond/ui.multidraggable.js');
define('CONST_PATH_JS', '/common/js/');

if(CONST_DEV_SERVER)
{
  define('CONST_PATH_JS_JQUERY', '/common/js/jquery.js');
  define('CONST_PATH_JS_JQUERYUI', '/common/js/jquery-ui.js');
  define('CONST_PATH_JS_POPUP', '/common/js/popup.class.js');
  define('CONST_PATH_JS_COMMON', '/common/js/common.js');
}
else
{
  define('CONST_PATH_JS_JQUERY', '/common/js/jquery.min.js');
  define('CONST_PATH_JS_JQUERYUI', '/common/js/jquery-ui.min.js');
  define('CONST_PATH_JS_POPUP', '/common/js/popup.class.min.js');
  define('CONST_PATH_JS_COMMON', '/common/js/common.min.js');
}

define('CONST_PATH_JS_SELECT2', '/common/js/select2.min.js');

define('CONST_PATH_CSS_JQUERYUI', '/common/style/jquery-ui/jquery-ui.min.css');
define('CONST_PATH_CSS_SELECT2', '/common/style/select2.css');
define('CONST_PATH_CSS_TIMEPICKER', '/common/style/timepicker.css');
define('CONST_PATH_CSS_COMMON', '/common/style/');
define('CONST_PATH_PICTURE_COMMON', '/common/pictures/');
define('CONST_PATH_UPLOAD_DIR', '/common/upload/');
define('CONST_PATH_ROOT', dirname(dirname(__FILE__)));
define('CONST_PATH_HTACCESS', '.htaccess');

define('CONST_FORM_SELECTOR_URL_COUNTRY', 'fsuco');
define('CONST_FORM_SELECTOR_URL_CITY', 'fsuci');

define('CONST_PICTURE_FILE', '/component/sharedspace/resources/pictures/mime/unknow.png');
define('CONST_PICTURE_FOLDER', '/component/folder/resources/img/folder_32.png');
define('CONST_PICTURE_COMING', CONST_PATH_PICTURE_COMMON.'cominglater_64.png');
define('CONST_PICTURE_COMPLETE', CONST_PATH_PICTURE_COMMON.'complete_64.png');
define('CONST_PICTURE_IMPORTANT', CONST_PATH_PICTURE_COMMON.'important_64.png');
define('CONST_PICTURE_EXPAND', CONST_PATH_PICTURE_COMMON.'expanded.png');
define('CONST_PICTURE_NORMAL', CONST_PATH_PICTURE_COMMON.'grey.png');
define('CONST_PICTURE_OPPORTUNITY', CONST_PATH_PICTURE_COMMON.'coins_16.png');
define('CONST_PICTURE_ADD', CONST_PATH_PICTURE_COMMON.'add_16.png');
define('CONST_PICTURE_DELETE', CONST_PATH_PICTURE_COMMON.'delete_16.png');
define('CONST_PICTURE_REACTIVATE', CONST_PATH_PICTURE_COMMON.'activate_16.png');
define('CONST_PICTURE_EDIT', CONST_PATH_PICTURE_COMMON.'edit_16.png');
define('CONST_PICTURE_VIEW', CONST_PATH_PICTURE_COMMON.'view_16.png');
define('CONST_PICTURE_LINK', CONST_PATH_PICTURE_COMMON.'link_16.png');
define('CONST_PICTURE_NULL', CONST_PATH_PICTURE_COMMON.'null_16.png');
define('CONST_PICTURE_LOADING', CONST_PATH_PICTURE_COMMON.'loading.gif');
define('CONST_PICTURE_SMALL_LOADING', CONST_PATH_PICTURE_COMMON.'loading_new.gif');
define('CONST_PICTURE_DOWNLOAD', CONST_PATH_PICTURE_COMMON.'download_24.png');
define('CONST_PICTURE_SAVE', CONST_PATH_PICTURE_COMMON.'save_16.png');
define('CONST_PICTURE_LOCK', CONST_PATH_PICTURE_COMMON.'lock_32.png');
define('CONST_PICTURE_CHECK_OK', CONST_PATH_PICTURE_COMMON.'check_ok_16.png');
define('CONST_PICTURE_CHECK_NOT_OK', CONST_PATH_PICTURE_COMMON.'check_nok_16.png');
define('CONST_PICTURE_CHECK_INACTIVE', CONST_PATH_PICTURE_COMMON.'check_inactive_16.png');
define('CONST_PICTURE_SORT', CONST_PATH_PICTURE_COMMON.'sort_16.png');
define('CONST_PICTURE_UPLOAD', CONST_PATH_PICTURE_COMMON.'upload_26.png');

// Product managment
define('CONST_PICTURE_DELIVER', CONST_PATH_PICTURE_COMMON.'product/delivered.png');
define('CONST_PICTURE_BOOK', CONST_PATH_PICTURE_COMMON.'product/book.png');
define('CONST_PICTURE_PAY', CONST_PATH_PICTURE_COMMON.'product/payed.png');
define('CONST_PICTURE_INVOICE', CONST_PATH_PICTURE_COMMON.'product/invoiced.png');

define('CONST_PICTURE_MENU_ADD', CONST_PATH_PICTURE_COMMON.'menu/add.png');
define('CONST_PICTURE_MENU_EDIT', CONST_PATH_PICTURE_COMMON.'menu/edit.png');
define('CONST_PICTURE_MENU_DELETE', CONST_PATH_PICTURE_COMMON.'menu/delete.png');
define('CONST_PICTURE_MENU_VIEW', CONST_PATH_PICTURE_COMMON.'menu/view.png');
define('CONST_PICTURE_MENU_LIST', CONST_PATH_PICTURE_COMMON.'menu/list.png');

define('CONST_PICTURE_MENU_SEARCH', CONST_PATH_PICTURE_COMMON.'search_32.png');
define('CONST_PICTURE_MENU_SEPARATOR', CONST_PATH_PICTURE_COMMON.'menu/separator.png');
define('CONST_PICTURE_MENU_MULTIPLE', CONST_PATH_PICTURE_COMMON.'menu/extend_arrow.png');
define('CONST_PICTURE_MENU_FAVORITE', CONST_PATH_PICTURE_COMMON.'menu/favorite.png');

//--------------------------------------------------------------------------------
//Component related constants

//addressbook / event
define('CONST_ACTION_ITEMTYPE', 'ppaty');
define('CONST_ACTION_ITEMID', 'ppaid');

//Talentatlas
define('CONST_TALENT_HOME_PAGE','ppah');
define('CONST_TA_TYPE_LIST_JOB','ppaj');
define('CONST_LIST_COMPANY','cmpl');


//---------------------------------------
//TYPE of element of evey component

define('CONST_AB_TYPE_COMPANY', 'cp');
define('CONST_AB_TYPE_COMPANY_RELATION', 'cpr');
define('CONST_AB_TYPE_CONTACT', 'ct');
define('CONST_AB_TYPE_EVENT', 'evt');
define('CONST_AB_TYPE_DOCUMENT', 'doc'); // To remove when document component will be ok
define('CONST_TYPE_DOCUMENT', 'doc');
define('CONST_AB_PROSPECT_PK', 6);
define('CONST_CF_TYPE_CUSTOMFIELD','csm');
define('CONST_EVENT_TYPE_REMINDER', 'evtrem');


define('CONST_TYPE_SETTINGS', 'stg');
define('CONST_TYPE_SETTING_USER','stgusr');
define('CONST_TYPE_SETTING_GROUP','stggrp');
define('CONST_TYPE_SETTING_USRIGHT','stgusrt');
define('CONST_TYPE_SETTING_RIGHTUSR','stgusrht');
define('CONST_TYPE_SETTING_RIGHTGRP','stggrpht');
define('CONST_TYPE_SETTING_MENU','stgmnu');
define('CONST_TYPE_SETTING_FOOTER','stgft');
define('CONST_TYPE_SETTING_BLACKLIST','stgblk');
define('CONST_TYPE_SETTING_CRON','stgcrn');
define('CONST_TYPE_SYSTEM_SETTINGS','stgsys');
define('CONST_TYPE_IP', 'alwip');
define('CONST_TYPE_MANAGEABLELIST', 'mngl');
define('CONST_TYPE_USERPREFERENCE', 'usrprf');
define('CONST_ACTION_RELOG', 'relog');

define('CONST_LOGIN_TYPE_USER', 'usr');
define('CONST_LOGIN_TYPE_EXTERNAL_USER', 'exusr');
define('CONST_LOGIN_TYPE_PASSWORD', 'pswd');
define('CONST_LOGIN_TYPE_GROUP', 'grp');

define('CONST_PROJECT_TYPE_PROJECT', 'prj');
define('CONST_PROJECT_TYPE_TASK', 'task');
define('CONST_PROJECT_TYPE_ACTOR', 'prjacr');
define('CONST_PROJECT_TYPE_ATTACHMENT', 'attch');
define('CONST_PROJECT_ACTION_UPDATE', 'ppaupd');
define('CONST_PROJECT_TASK_SORT_PARAM', 'tsksort');


define('CONST_FOLDER_TYPE_FOLDER', 'fol');
define('CONST_FOLDER_TYPE_ITEM', 'folitm');

if(!defined('CONST_FOLDER_LOADING_MODE'))
  define('CONST_FOLDER_LOADING_MODE', 2);


define('CONST_NOTIFY_TYPE_NOTIFICATION', 'not');
define('CONST_NOTIFY_TYPE_MESSAGE', 'msg');
define('CONST_NOTIFY_TYPE_NAG', 'nag');


// PLUG A COMPONENT ON ANOTHER
define('CONST_CP_UID', 'cp_uid');
define('CONST_CP_ACTION', 'cp_action');
define('CONST_CP_TYPE', 'cp_type');
define('CONST_CP_PK', 'cp_pk');

//Talentatlas Component, specific parameters
define('CONST_TA_TYPE_JOB', 'job');
define('CONST_TA_TYPE_JOB_RSS', 'jrss');
define('CONST_TA_TYPE_SHARE_JOB', 'shjob');

//Custom field Component,specific parameters
define('CONST_ACTION_UPDATE','ppau');

//Portal
define('CONST_PORTAL_OPP_STAT','portoppstat');
define('CONST_PORTAL_OPP_USER_STAT','portoppuserstat');
define('CONST_PORTAL_STAT','portstat');
define('CONST_PORTAL_CALENDAR','portcal');

//Zimbra
define('CONST_ZCAL_EVENT','zcalevt');

//opportunity
define('CONST_OPPORTUNITY','opp');
define('CONST_OPPORTUNITY_DETAIL','oppd');

//addressbook
define('CONST_TAB_CP_DETAIL','cp_tab_detail');
define('CONST_TAB_CP_EVENT','cp_tab_event');
define('CONST_TAB_CP_DOCUMENT','cp_tab_document');
define('CONST_TAB_CP_EMPLOYEES','cp_tab_employee');
define('CONST_TAB_CP_OPPORTUNITY','cp_tab_opportunity');

define('CONST_TAB_CT_DETAIL','ct_tab_detail');
define('CONST_TAB_CT_COWORKERS','ct_tab_coworkers');
define('CONST_TAB_CT_DOCUMENT','ct_tab_document');
define('CONST_TAB_CT_EVENT','ct_tab_event');
define('CONST_TAB_CT_PROFILE','ct_tab_profile');
define('CONST_TAB_CT_OPPORTUNITY','ct_tab_opp');

define('CONST_FORM_TYPE_CITY', 'fcity');
define('CONST_FORM_TYPE_COUNTRY', 'fcountry');
define('CONST_SS_TYPE_DOCUMENT', 'shdoc');
define('CONST_SEARCH_TYPE_SEARCH', 'search');
define('CONST_EVENT_TYPE_EVENT', 'event');

define('CONST_WEBMAIL', 'webmail');
define('BCMAIL_HOST','mail.bulbouscell.com');
define('BCMAIL_PORT','143');
define('DEFAULT_WEBMAIL_ADDRESS','crm@bulbouscell.com');
