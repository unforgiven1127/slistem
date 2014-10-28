/**
* Remove a row from the advance search form
* @param HTMLElemeny poTag a tag in the form
*/
function removeSearchFormRow(poTag)
{
  disactiveForm();

  var bRemoveGroup = false;

  //alert('nb rows in group: '+$(poTag).closest('.advancedSearchFieldGroup').find('.advancedSearchRow').length);


  if($(poTag).closest('.advancedSearchFieldGroup').find('.advancedSearchRow').length == 1)
  {
    //I'm removing the last row of the group, should i remove the group too ?
    //alert('nb groups in form: '+$(poTag).closest('form').find('.advancedSearchFieldGroup').length);

    if($(poTag).closest('form').find('.advancedSearchFieldGroup').length == 1)
    {
      alert('Sorry, you can\'t remove the last field of the form.');
      reactiveForm();
      return false;
    }
    else
      bRemoveGroup = true;
  }

  var oForm = $(poTag).closest('form');

  $(poTag).closest('.advancedSearchRow').fadeOut(function()
  {
    if(bRemoveGroup)
    {
      $(this).closest('.advancedSearchFieldGroup').fadeOut(function()
      {
        $(oForm).find('.advancedSearchFieldGroup:visible:first .advancedSearchFieldOperator').hide(0);
        $(oForm).find('.advancedSearchFieldGroup:visible:first').click();

        $(this).closest('.advancedSearchFieldGroup').remove();
      });
    }
    else
      $(this).remove();

    refreshOperator(oForm);
    reactiveForm();
  });

  //TODO: manageButtons + hidden fields
}

function addSearchFormRow(poTag)
{
  disactiveForm();

  var oForm = $(poTag).closest('form');
  var nRows = $('.advancedSearchRow', oForm).length;

  var oContainer = $('.advancedSearchFieldGroup.selected', oForm);
  var oRow = $('.advancedSearchRow:first', oContainer).clone(true);

  if(!oRow.length)
  {
    oPrevContainer = $(poTag).closest('form').find('.advancedSearchFieldContainer');
    oRow = $('.advancedSearchRow:first', oPrevContainer).clone(true);
  }

  //now we've got a row, we need to edit ids
  var sId = uniqueId();
  nRows = (nRows+100);
  var nGroup = $(oContainer).attr('nb_group');
  $(oRow).attr('group_nb', nGroup).attr('row_nb', nRows).attr('id', 'search_row_'+nRows);

  /*$('.field_selector', oRow).attr('id', 'field_selector'+nRows+'Id').attr('name', 'field_selector['+nRows+']');
  $('.field_operator', oRow).attr('id', 'field_operator'+nRows+'Id').attr('name', 'field_operator['+nRows+']');

  var sFieldname = $('.field_value', oRow).attr('field_name');
  var nMultiple = $('.field_value', oRow).attr('multiple');

  if(nMultiple)
    $('.field_value', oRow).attr('id', 'field_value'+nRows+'Id').attr('name', sFieldname+'['+nRows+'][]');
  else
    $('.field_value', oRow).attr('id', 'field_value'+nRows+'Id').attr('name', sFieldname+'['+nRows+']');
  */

  $('.field_selector', oRow).change();

  oContainer.append(oRow);
  reactiveForm();

  //TODO: manageButtons + hidden fields
}

function refreshOperator(oForm)
{
  if(!oForm)
    oForm = $('#searchFormId:visible');

  $('.advancedSearchFieldGroup', oForm).each(function(nIndex, oTag)
  {
    $('.searchForm_row_operator', oTag).each(function(nItem, oOperator)
    {
      if(nItem == 0)
        $(oOperator).addClass('first');
      else
        $(oOperator).removeClass('first');
    });
  });
}


/**
 * Comment
 */
function addSearchFormGroup(poTag)
{
  //form section adds automaticallyu a span.floatHack, so we insert the var group just before
  var oForm = $(poTag).closest('form');
  var nGroup = $('.advancedSearchFieldGroup', oForm).length;
  var oGroupOperator = $('.row_group_operator:first', oForm).clone();
  $(oGroupOperator).css('display', 'block');

  nGroup++;
  $('select', oGroupOperator).attr('name', 'group_operator['+nGroup+']').attr('id', 'group_operator'+nGroup+'Id');

  $('.advancedSearchFieldContainer > div:last', oForm).before('<div class="advancedSearchFieldGroup" onclick="selectGroup(this);" nb_group="'+ nGroup +'"><div class="floatHack" /></div>');
  $('.advancedSearchFieldGroup:last', oForm).prepend('<div class="advancedSearchFieldOperator"></div>');
  $('.advancedSearchFieldGroup:last .advancedSearchFieldOperator', oForm).append(oGroupOperator);

  //select the group we've just added
  $(poTag).closest('form').find('.advancedSearchFieldGroup:last').click();

  //ajoute une nouvelle row
  addSearchFormRow(poTag);
}

/**
 * disable the form while we add/remove elements
 */
function disactiveForm()
{
  $('#searchFormId .submitBtnClass').hide(0);
}

/**
 * disable the form while we add/remove elements
 */
function reactiveForm()
{
  $('#searchFormId .submitBtnClass').fadeIn();
}


/**
* Select a group of field, un selecting all the other ones
* @param HTMLElement poTag a tag in the form
*/
function selectGroup(poTag)
{
  $(poTag).closest('form').find('.advancedSearchFieldGroup.selected').removeClass('selected');
  $(poTag).addClass('selected');
}