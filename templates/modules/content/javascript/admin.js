$(document).ready(function(){
    $('.content_admin_type').change(function(event) {
        var content_type= $(event.currentTarget).val();
        var item = $(event.currentTarget).closest('.feedItem');
        item.find('.content_admin_optional').hide();
        item.find('.content_admin_optional :input').attr('disabled', true);
        item.find('.content_' + content_type).show();
        item.find('.content_' + content_type+' :input').removeAttr('disabled');
  });
   
   $('.content_admin_type').change();
 });
 