$(function() {

  $('#showfolders').click(function(){
    $('#folderslist').toggle();
    return false;
  });

  $('#componentContainerId').click(function(){
    $('#folderslist').hide();
    return false;
  });

});