<?php

$gasComponentBlackList = array('150-163','talentatlas', '100-603','taaggregator', '153-160','jobboard','459-456','jobboard_user','654-321','socialnetwork',
    '456-789', 'project',
    /*'999-111', 'sharedspace',*/
    '400-650', 'zimbra',
    '555-123', 'opportunity',
    '777-249', 'addressbook',
    '009-724', 'webmail',
    '185-963', 'menu',
    '486-125', 'folder'
    /*'007-770', 'event'*/
    , 'CGbUser', '196-001'
    , 'CGbTest', '196-002'
    );

/* Array of UID to make the system_log not keep trace of specific components */
$gasLogBlackList = array();

/* also contain default log action [query type + table name]
//del later
//add in sl_contact
//upd sl_contact
$gasLogBlackList = array(
    'upd sl_candidate', //manual log
    'upd sl_candidate_profile', //* hidden
    'add document_link', // hidden
    'add document_file', // hidden
    'del sl_contact_visibility' // hidden
    );
 */

?>