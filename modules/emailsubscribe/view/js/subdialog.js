/**
* Отображает диалог подписки на новости
*/
$(function() {
    function openSubscribeWindow() {
        $.openDialog({
          url : global.emailsubscribe_dialog_url
        }); 
    }
   
   if (global.emailsubscribe_dialog_open_delay){
      setTimeout(openSubscribeWindow, global.emailsubscribe_dialog_open_delay * 1000); 
   }
});