/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Add an option to a select
 */
function addOption(optionValue, optionLabel, selectId, selected)
{
  var select = $('#'+selectId);
  var contentLi = '<li value=\''+optionValue+'\'';
      contentLi += ' onClick=\'selectOption(\"'+optionValue+'\", \"'+selectId+'\");\'>'+optionLabel+'</li>';

      console.log(optionLabel);
      console.log(optionValue);
      console.log(contentLi);

  select.children('.group').children('ul').append(contentLi);

  selectOption(optionValue, selectId, '');

  return true;
}

/**
 * Select an option
 */
function selectOption(optionValue, selectId, givenOption)
{
  if ((optionValue.length == 0) || (selectId.length == 0))
  {
    var theOption = givenOption;
    var group = theOption.parent().parent();
    var select = group.parent();
    optionValue = theOption.attr('value');
  }
  else
  {
    var select = $('#'+selectId);
    var group = select.children('.group');
    var theOption = group.children('ul').children('li[value=\''+optionValue+'\']');
  }

  select.siblings('.noRecord').hide();
  select.show();

  var attrSelected = theOption.attr('selected');
  if (attrSelected === undefined || attrSelected.length==0)
  {
    var inputname = select.attr('inputname');

    var attrMultiple = select.attr('multiple');
    var isMultiple = (attrMultiple === 'multiple');

    var optionLabel = theOption.html();

    if (!isMultiple)
    {
      select.children('.sSelectTitle').children('span').html(optionLabel);
      $('input[name='+inputname+']').val(optionValue);
      theOption.siblings('li[selected=selected]').removeAttr('selected');
    }
    else
    {
      var values = $('input[name='+inputname+']').val();
      var aValues = [];
      if (values.length>0)
        aValues = values.split(',');

      aValues.push(optionValue);
      var sNewValues = aValues.join(',');
      $('input[name='+inputname+']').val(sNewValues);

      var liContent = '<li value=\"'+optionValue+'\" onclick=\"removeOption(\''+optionValue+'\', \''+select.attr('id')+'\'); return false;\">'+optionLabel+'<span>X</span></li>';
      select.siblings('.selectedValues').children('ul').append(liContent);
    }

    theOption.attr('selected', 'selected');

    group.siblings('.sSelectTitle').removeClass('clicked');
    group.hide();
  }

  return true;
}


/**
 * Removes a selected option from the list
 */
function removeOption(optionValue, selectId)
{
  var select = $('#'+selectId);
  var inputname = select.attr('inputname');
  select.children('.group').children('li[value='+optionValue+']').removeAttr('selected');

  var values = $('input[name='+inputname+']').val();
  var aValues = values.split(',');

  var nbValues = aValues.length;
  var endLoop = false;

  var nCount = 0;
  while (!endLoop)
  {
    if (aValues[nCount]==optionValue)
    {
      aValues.splice(nCount, 1);
      endLoop=true;
    }
    nCount++;
    if (nCount>nbValues)
    {
      endLoop=true;
    }
  }
  var sNewValues = aValues.join(',');
  $('input[name='+inputname+']').val(sNewValues);

  select.children('.group').children('ul').children('li[value='+optionValue+']').removeAttr('selected');
  $('ul[rel='+selectId+']').children('li[value='+optionValue+']').remove();

  return true;
}

/**
 * Set up some element behaviors
 */
function sSelect()
{

//  $('body').prepend("<div id='sSelectHide' onclick='javascript:$(\".group\").hide(); console.log(\"test\");'></div>");
// TODO : Manage click out of select

  $('.sSelectTitle').click(function(){
    $(this).toggleClass('clicked');
    var siblingGroup = $(this).siblings('.group');

    $('.clicked').not(this).removeClass('clicked');
    $('.group').not(siblingGroup).hide();

    siblingGroup.toggle();

    return false;
  });

  $('.group ul li').click(function(){
    selectOption('','',$(this));
  });

  $('.selectedValues ul li').click(function(){
    removeOption($(this).attr('value'), $(this).parent().parent().siblings('.sSelect').attr('id'));
    return false;
  });
}

$(document).ready(sSelect);