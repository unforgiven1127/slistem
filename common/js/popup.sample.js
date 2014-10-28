/*
 * TODO:
 * Next steps:
 *
 *  -> integration with form:
 *    - manage submit button: create buttons (helpers) that automatically submit form in the popup, and manage the field controle result
 *
 *  -> more options
 *    - add settings to manage positionning: when instanciating the class at first, we could define notice bottom right ...
 *
 *  -> features:
 *    - allow to define group or parent popups. So we could close a full set of child popups by closing the parent
 *
 SAMPLE:


<script>

var goPopup = new CPopup();
console.log(goPopup);


//======================================================
//Messages

sPopupId = goPopup.setPopupMessage("1111");                               //most basic
sPopupId = goPopup.setPopupMessage("22222", true);                      //basic with bg
sPopupId = goPopup.setPopupMessage("33333333", false, "tiiiiiitle");    //title instead of button
sPopupId = goPopup.setPopupMessage("4444444", true, null, 600, 500);    //change size



//alert(goPopup.getActive());                                             //get active popup
//popupRemove(sPopupId);                                                  //remove one popup

// a confirm box
sPopupId = goPopup.setPopupConfirm("55555555555", " alert(\'gaaaaa\'); ", " alert(\'guuuuu\'); ", "whadup", "booooo");


//======================================================
//Notices

sPopupId = goPopup.setNotice("66666");                                                          //basic, clear automatically after 2500ms
sPopupId = goPopup.setNotice("77777   disapear 13500ms", {delay: 13500, url: \'#aaa\'});        //with action
sPopupId = goPopup.setNotice("88888   disapear 9500ms", {delay: 9500, url: \'#aaa\'}, false);    //with animation remove animation
sPopupId = goPopup.setNotice("99999   disapear 6500ms", {delay: 6500, url: \'#aaa\'}, true, true);     //with modal
sPopupId = goPopup.setNotice("aaaaa   disapear 2500ms", \'\', false, false, 800, 65);            //change size



//======================================================
//Layers
//set a simple layer (big window)
sPopupId = goPopup.setLayer("layerIDNonExisting", "bbbbb", "title", true, true);

//re-open persistent layer
//setTimeout( \' goPopup.setLayer(sPopupId, "", true, true); \', 6000);



var oConf = goPopup.getConfig();
oConf.width = 850;
oConf.height = 150;
oConf.dialogClass = \'noTitle\';
oConf.buttons = [goPopup.addButton(\'ok\', \' alert("puuuuu"); \', true), goPopup.addButton(\'cancel\') ];
sPopupId = goPopup.setLayerByConfig("", oConf, "ertertfsfsdfsdfs");



$(body).append(\'<div id="testTagId" data-title=\"tiiiiiiittttttlllleeeee\"  data-height=\"300\"  data-modal=\"true\">xxxxxxxxxxxxxxx</div>\');
goPopup.setLayerFromTag("testTagId");



var oConf = goPopup.getConfig();
oConf.width = 950;
oConf.height = 600;
oConf.dialogClass = \' noTitle shadow \';
oConf.buttons = [goPopup.addButton(\'ok\', \' alert("puuuuu"); \', true), goPopup.addButton(\'cancel\') ];
goPopup.setLayerFromAjax(oConf, "https://slistem.devserv.com/index.php5?uid=665-544&ppa=ppaa&ppt=stgusr&ppk=0&pg=ajx");


//change config of a popup (displayed or closed-persistent)
var oConf = goPopup.getConfig();
oConf.persistent = true;
oConf.title = "title 111111";
sPopupId = goPopup.setLayerByConfig("gaaaaa", oConf, "uuuuuuuuuuuuuu");


setTimeout( \'goPopup.changeConfig("gaaaaa", "title", "title 2222222"); goPopup.setLayer("gaaaaa");\', 7000);




//removing by popup type


$sHTML.= '<script> goPopup.setLayerFromAjax(null, "https://slistem.devserv.com/index.php5?uid=665-544&ppa=ppaa&ppt=stgusr&ppk=0&pg=ajx"); </script>';
$sHTML.= '<script> goPopup.setPopupMessage("fsdfsdfsdf"); </script>';
$sHTML.= '<script> goPopup.setPopupMessage("sdfew wer we rwe"); </script>';
$sHTML.= '<script> goPopup.setPopupMessage("fsdfe rwer htyi oloup sdfsdf"); </script>';

$sHTML.= '<script> goPopup.setNotice("fsdfe rwer htyi oloup sdfsdf", {delay: 95000}); </script>';

$sHTML.= '<script> setTimeout(\' console.log("remove layers ..."); goPopup.removeByType("layer"); \', 5000); </script>';
$sHTML.= '<script> setTimeout(\' console.log("remove layers ..."); goPopup.removeByType("msg"); \', 12000); </script>';
$sHTML.= '<script> setTimeout(\' console.log("remove notice ..."); goPopup.removeByType("notice"); \', 20000); </script>';

</script>

*/