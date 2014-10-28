/**
 * Adds an option in a manageable list
 */
function addMnlListOption()
{
  console.log('Mnl List');
    var sValue = $("#valueadd").val();
    var sLabel = $("#labeladd").val();

    if(!sValue || sValue.trim == '')
    {
      goPopup.setPopupMessage('"Label" and "value"  are required fields.', true, 'input Error');
      return false;
    }

    var sHtml = "<div class='mngListRowSection'>";
      sHtml += "<input type='hidden' value='"+sValue+"' name='value[]'>";
      sHtml += "<input type='hidden' value='"+sLabel+"' name='label[]'>";

      sHtml += "<div class='formFieldContainer formFieldWidth1 ' style='width:100px; margin-right:10px;' keepnextinline='1'>";
      sHtml += "<span>"+sLabel+"</span>";
      sHtml += "<div class='floatHack'></div>";
      sHtml += "</div>";

      sHtml += "<div class='formFieldContainer formFieldWidth1 ' style='width:200px; margin-right:10px;' keepnextinline='1'>";
      sHtml += "<span>"+sValue+"</span>";
      sHtml += "<div class='floatHack'></div>";
      sHtml += "</div>";

      sHtml += "<div class='formFieldContainer formFieldWidth1 ' style='width:40px;' keepnextinline='1'>";
        sHtml += "<a class='deletedetail' onclick='$(this).parent().parent().remove(); return false;' href='#'>";
        sHtml += "<img src='/common/pictures/delete_16.png'>";
        sHtml += "</a>";
      sHtml += "</div>";

      sHtml += "<div class='floatHack'></div>";

    sHtml += "</div>";
    console.log($("#list_detail").length);
    $("#list_detail").append(sHtml);

    return false;
}