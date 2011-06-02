var videoEllipsizer = null;

addEventListener('load', function() {
    addPaneResizeHandler(videoPaneResizeHandler);
    
}, true);

function videoPaneResizeHandler() {
  // set the size on the videos
  var videos = document.getElementById('videos').childNodes;
  if (videos.length) {
    var pager = document.getElementById('videoPager');
    var videoClipHeight = getCSSHeight(document.getElementById('videos'))
      - pager.offsetHeight
      - parseFloat(getCSSValue(videos[0], 'border-top-width')) 
      - parseFloat(getCSSValue(videos[0], 'border-bottom-width'))
      - parseFloat(getCSSValue(videos[0], 'padding-top'))
      - parseFloat(getCSSValue(videos[0], 'padding-bottom'))
      - parseFloat(getCSSValue(videos[0], 'margin-top'))
      - parseFloat(getCSSValue(videos[0], 'margin-bottom'));
      
    for (var i = 0; i < videos.length; i++) {
      videos[i].style.height = videoClipHeight+'px';
    }
  }
  
  if (videoEllipsizer == null) {
    videoEllipsizer = new ellipsizer({refreshOnResize: false});
    videoEllipsizer.addElements(videos);
  } else {
    setTimeout(function () {
      videoEllipsizer.refresh();
    }, 1);
  }
}

function videoPaneSwitchVideo(elem, direction) {
  if (elem.className.match(/disabled/)) { return false; }

  var videos = document.getElementById('videos').childNodes;
  
  var dots = document.getElementById('videoPagerDots').childNodes;
  var prev = document.getElementById('videoPrev');
  var next = document.getElementById('videoNext');
  
  for (var i = 0; i < videos.length; i++) {
    if (videos[i].className == 'current') {
      var j = direction == 'next' ? i+1 : i-1;
      
      if (j >= 0 || j < videos.length) {
        videos[i].className = '';
        videos[j].className = 'current';
        
        dots[i].className = '';
        dots[j].className = 'current';
        
        prev.className = (j == 0) ? 'disabled' : '';
        next.className = (j == (videos.length-1)) ? 'disabled' : '';

        videoEllipsizer.refresh();
        
      }
      
      break;
    }
  }
  
  return false;
}
