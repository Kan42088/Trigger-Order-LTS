jQuery(document).ready( function(){
    // Some event will trigger the ajax call, you can push whatever data to the server, 
    // simply passing it to the "data" object in ajax call

  jQuery("#board").change(function(){
    var boardID = jQuery(this).children(":selected").attr("value");
    $("#board option:selected").each(function () {
        $(this).removeAttr('selected'); 
    });
    $('#board').val(boardID);

    $.ajax({
        url: ajax_object.ajaxurl, // this is the object instantiated in wp_localize_script function
        type: 'POST',
        cache: false,
        data:{ 
          action: 'myaction', // this is the function in your functions.php that will be triggered
          boardID: boardID,
        },
        success: function( data ){
          //Do something with the result from server
          //console.log( data );
          location.reload();
        }
    });
  });
  
});