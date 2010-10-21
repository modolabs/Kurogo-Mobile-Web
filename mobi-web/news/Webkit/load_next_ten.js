function loadMoreNewsItems(next_or_previous) {

   var load_button_id = next_or_previous ? 'load-next-button' : 'load-previous-button';
   var load_busy_id = next_or_previous ? 'load-next-busy' : 'load-previous-busy';
   var load_button = document.getElementById(load_button_id);
   var load_busy = document.getElementById(load_busy_id);

   var xhr = new XMLHttpRequest();

   var params = next_or_previous ? next_params : previous_params;
   var query = "ajax=True";

   query += "&channel_id=" + params.channel_id;
   if(params.seek_story_id) {
     query += "&seek_story_id=" + params.seek_story_id;
   }

   if(next_or_previous) {
     query += "&next=1";
   } else {
     query += "&next=0";
   }

   // if we are search we need to include these extra parameters
   if(params.query) {
     query += "&query=" + params.query + "&seek_search_id=" + params.seek_search_id;
   }

   xhr.open('GET', "./?" + query, true);
   xhr.send(null);

   // turn on busy box
   load_button.style.display = "none";
   load_busy.style.display = "";

   setTimeout(timeoutComplete, 20 * 1000);
   
   /**
    * the call back to process the request
    */
   xhr.onreadystatechange = function() {
      if(xhr.readyState == 4) { 

        // turn off busy box
        load_button.style.display = "";
        load_busy.style.display = "none";

        if(xhr.status == 200) {
          // parse response
          var response_object = eval('(' + xhr.responseText + ')');
          requestSucceededHandler(response_object, next_or_previous);
        } else {
          requestFailedHandler(next_or_previous);
        }
      }
   }

   function timeoutComplete() {
     if(xhr.readyState < 4) {
       xhr.abort();
       requestFailedHandler();
     }
   }
}

function requestSucceededHandler(response_object, next_or_previous) {
        // request succeeded

        var load_button_id = next_or_previous ? 'load-next-button' : 'load-previous-button';
        var load_button = document.getElementById(load_button_id);

        // process response
        var news_items = document.getElementById('news-items');

        // a temporary div to use while parsing the incoming html fragment
        var temp_ul = document.createElement('ul');
        temp_ul.innerHTML = response_object.items_html;
        var children_count = temp_ul.children.length;
        
        for(var node_index = 0; node_index < children_count; node_index++) {
          var first_or_last_index = next_or_previous ? 0 : temp_ul.children.length-1;
          var node = temp_ul.removeChild(temp_ul.children[first_or_last_index]);
          if(next_or_previous) {
            news_items.insertBefore(node, load_button);
          } else {
            news_items.insertBefore(node, load_button.nextSibling);
          }
        }

        if(next_or_previous) {
          next_params = response_object.next_params;
          if(!next_params) {
            load_button.style.display = "none";
          }
        } else {
          previous_params = response_object.previous_params;
          if(!previous_params) {
            load_button.style.display = "none";
          }
        }
}

function requestFailedHandler(prev_or_next) {
        alert('Failed to load more news items');

        var load_button_id = next_or_previous ? 'load-next-button' : 'load-previous-button';
        var load_busy_id = next_or_previous ? 'load-next-busy' : 'load-previous-busy';
        var load_button = document.getElementById(load_button_id);
        var load_busy = document.getElementById(load_busy_id);
       
        
}
    