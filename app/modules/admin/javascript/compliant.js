function toggleAdvancedFields(e)
{
   var evt = e || window.event;
   var evtTarget = evt.target || evt.srcElement;
   
   var advancedFeedFields = evtTarget.parentNode.getElementsByTagName('ul');
   if (advancedFeedFields[0]) {
        if (advancedFeedFields[0].className=='advancedFeedFields') {
            if (advancedFeedFields[0].style.display) {
                advancedFeedFields[0].style.display = '';
            } else {
                advancedFeedFields[0].style.display = 'block';
            }            
        }
   }
}
