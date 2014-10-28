function showHideUserList(val)
{
  if(val==2)
    $('.userList').show();
  else
   $('.userList').hide();
}


/* ********************************************* */
//specific to month picker
function checkDateInterval(fieldName)
{
  // Change this params to adjust the maximum interval allowed
  var intervalMaxInMonths = 8;

  var datemin = $('input[name=datemin]').val();
  var datemax = $('input[name=datemax]').val();

  var tempMin=datemin.split("-");
  var tempMax=datemax.split("-");

  var oDateMin = new Date(datemin);
  var oDateMax = new Date(datemax);

  oDateMin.setFullYear(tempMin[0]);
  oDateMax.setFullYear(tempMax[0]);

  oDateMin.setMonth(tempMin[1]);
  oDateMax.setMonth(tempMax[1]);

  oDateMin.setDate(1);
  oDateMax.setDate(1);

  var intervalMaxInMs = intervalMaxInMonths*30*24*60*60*1000;
  var oTimeMax = oDateMax.getTime();
  var oTimeMin = oDateMin.getTime();
  var intervalInMs = oTimeMax-oTimeMin;

  if ((intervalMaxInMs < intervalInMs) || (intervalInMs < 0))
  {
    if (fieldName == 'datemin')
    {
      var newMonth = oDateMin.getMonth()+intervalMaxInMonths;
      var newYear = oDateMin.getFullYear();

      if (newMonth > 12)
      {
        newMonth -= 12;
        newYear += 1;
      }

      newMonth = newMonth.toString();
      if (newMonth.length==1)
        newMonth = '0'+newMonth;

      $('input[name=datemax]').val(newYear+'-'+newMonth);
    }
    else
    {
      var newMonth = oDateMax.getMonth()-intervalMaxInMonths;
      var newYear = oDateMax.getFullYear();

      if (newMonth < 1)
      {
        newMonth += 12;
        newYear -= 1;
      }

      newMonth = newMonth.toString();
      if (newMonth.length==1)
        newMonth = '0'+newMonth;

      $('input[name=datemin]').val(newYear+'-'+newMonth);
    }
  }
}


function previousMonth(poTag)
{
  var oSelect = $(poTag).parent().find('select');
  var nextElement = $('> option:selected', poTag).next('option');
  console.log(oSelect);
  console.log(nextElement);

  if (nextElement.length > 0)
  {
    $('> option:selected', oSelect).removeAttr('selected').next('option').attr('selected', 'selected');
    $(oSelect).change();
  }
}

function nextMonth(poTag)
{
  var oSelect = $(poTag).parent().find('select');
  var prevElement = $('> option:selected', poTag).prev('option');

  if (prevElement.length > 0)
  {
    $('> option:selected', oSelect).removeAttr('selected').prev('option').attr('selected', 'selected');
    $(oSelect).change();
  }
  else
  {
    $(poTag).addClass('inactive');
  }
}

/**
 * Refresh a single opportunity row
 */
function reloadOpportunity(pnOpportunityPk)
{
  var divToRefresh = $('div[opportunitypk=\''+pnOpportunityPk+'\']');

  if (divToRefresh.length>0)
  {
    var refreshWith = divToRefresh.attr('refreshWith');

    $.get( refreshWith, function( data ) {
      $('div[opportunitypk=\''+pnOpportunityPk+'\']').html( data );
    });
  }

  return true;
}


function sortOppList(pvList, psType)
{
  console.log(pvList);
  var oList = $(pvList);

  var psWay = 'down';
  console.log(oList);

  var listItems = $('.divlist-item', oList).get();
  console.log(listItems);

  $(oList).animate({opacity: '0.2'}, function()
  {
      switch(psType)
      {
        case 'date':
        {
          listItems.sort(function(a,b)
          {
            compA = parseInt($(a).attr('opp_date'));
            compB = parseInt($(b).attr('opp_date'));

            if(isNaN(compA))
              compA = 0;

            if(isNaN(compB))
              compB = 0;

            //console.log('mode 3 [.'+sColumn+'] ==> a: '+compA+' / b: '+compB);

             if(psWay == 'up')
               return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;

            return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
          });
        }
        break;

        case 'status':
        {
          listItems.sort(function(a,b)
          {
            compA = $(a).attr('opp_status');
            compB = $(b).attr('opp_status');

            if(psWay == 'up')
              return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;

            return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
          });
        }
        break;
      }

    $(oList).append(listItems);
    $(oList).css('opacity', 1);
  });

}