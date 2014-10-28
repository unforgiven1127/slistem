
$.widget( "custom.catcomplete", $.ui.autocomplete, {
  _renderMenu: function( ul, items ) {
    var that = this,
    currentCategory = "";
    $.each( items, function( index, item ) {
      if ( item.category != currentCategory ) {
        ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
        currentCategory = item.category;
      }
      that._renderItemData( ul, item );
    });
  },
  _renderItem: function( ul, item ) {
    var sLink = $("<a>")
                .attr('href', item.url)
                .text( item.label );
    var sLi = $( "<li>" )
      .attr( "data-value", item.value )
      .append( sLink )
      .appendTo( ul );
    return sLi;
  }
});

$(document).ready(function() {

  $('#search a.show').click(function(){
    $('#search div.fields').toggle();
  });

  $( "#search" ).catcomplete({
    delay: 0,
    source: aSearchValues
  });

});