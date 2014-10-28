$(document).ready(function()
{
  var oOppPageHeight  = $(window).height();
  console.log('overall  '+ oOppPageHeight);

  if(oOppPageHeight < 930)
  {
    $(document,body).scroll(function()
    {
      console.log($(this).scrollTop());
      var nScroll = $(this).scrollTop();

      if(nScroll > 225)
      {
        $('#MyStatistics:visible').slideUp(300);
        return true;
      }

      var nScrollRef = 250;
      var nVisible = $('#MyStatistics:visible').length;
      if(!nVisible)
        nScrollRef = 25;

      console.log('scroll ref '+nScrollRef);

      if(nScroll < nScrollRef)
        $('#MyStatistics:not(:visible)').fadeIn(700);

      return true;
    });
  }
});