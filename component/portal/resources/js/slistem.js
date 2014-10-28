

function scrollHome(poTag, psDirection, pnItemVisible)
{
  if(!pnItemVisible || isNaN(pnItemVisible))
    pnItemVisible = 2;

  var nAnimationTime = 250;
  var nDelay = 150;
  var nTreated = 0;
  var oContainer = $(poTag).closest('.slider_mode').find('.slider_mode_inner');
  var nItem = $('> div', oContainer).length;
  var nCurrent = parseInt($(oContainer).attr('current-position'));
  if(!nCurrent)
    nCurrent = 0;

  if(psDirection == 'top')
  {
    nStart = nCurrent-1;
    nToDisplay = (nStart+(pnItemVisible-1));

    if(nStart < 0)
      return true;

    $(oContainer).attr('current-position', nStart);
    $('> div', oContainer).hide(0);

    $('> div', oContainer).each(function(nIndex)
    {
      if(nIndex >= nStart && nIndex <= nToDisplay)
      {
        $(this).delay((nTreated*nDelay)).show('slide', {direction: "up", duration: nAnimationTime});
        nTreated++;
      }
    });
  }
  else
  {
    nStart = nCurrent+1;

    //if 1 item displayed, we need to stop just AT the last one.
    if(pnItemVisible == 1)
      nToDisplay = (nStart+(pnItemVisible));
    else
      nToDisplay = (nStart+(pnItemVisible-1));

    if(nToDisplay > nItem)
      return true;

    $('> div', oContainer).hide(0);
    $(oContainer).attr('current-position', nStart);

    $('> div', oContainer).each(function(nIndex)
    {
      if(nIndex >= nStart && nIndex <= nToDisplay)
      {
        $(this).delay((nTreated*nDelay)).show('slide', {direction: "down", duration: nAnimationTime});
        nTreated++;
      }
    });
  }

  return true;
}