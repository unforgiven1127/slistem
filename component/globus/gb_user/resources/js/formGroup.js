
 /**
 * add a zero to one digit numbers
 */
function addZero(number)
{
  return ("0" + number).slice(-2);
}


$(document).ready(function() {

  $('#autofill').click(function(){

    var firstChapter = $('input[chapter=1]').val();
    console.log(firstChapter);

    if (firstChapter!='')
    {
      var chapterCount = ($('.chapter').length-1);

      tabTemp=firstChapter.split("-");

      var oDateMin = new Date();
      oDateMin.setFullYear(tabTemp[0]);
      oDateMin.setMonth((tabTemp[1]-1));
      oDateMin.setDate(tabTemp[2]);

      var oTimeMin = oDateMin.getTime();

      for (i = 1; i <= chapterCount; i++)
      {
        var target = i+1;
        oTime = oTimeMin + (i*(7*24*60*60*1000));
        oDate = new Date(oTime);

        $('input[chapter='+target+']').val(oDate.getFullYear()+'-'+addZero(oDate.getMonth()+1)+'-'+addZero(oDate.getDate()));
      }
    }

    return false;
  });

});