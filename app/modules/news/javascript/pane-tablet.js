var newsEllipsizer = null;

addEventListener('load', function() {
    addPaneResizeHandler(newsPaneResizeHandler);
    
}, true);

function newsPaneResizeHandler() {

  // set the size on the news stories
  var stories = document.getElementById('newsStories').childNodes;
  if (stories.length) {
    var pager = document.getElementById('newsPager');
    var storyClipHeight = getCSSHeight(document.getElementById('newsStories'))
      - pager.offsetHeight
      - parseFloat(getCSSValue(stories[0], 'border-top-width')) 
      - parseFloat(getCSSValue(stories[0], 'border-bottom-width'))
      - parseFloat(getCSSValue(stories[0], 'padding-top'))
      - parseFloat(getCSSValue(stories[0], 'padding-bottom'))
      - parseFloat(getCSSValue(stories[0], 'margin-top'))
      - parseFloat(getCSSValue(stories[0], 'margin-bottom'));
      
    for (var i = 0; i < stories.length; i++) {
      stories[i].style.height = storyClipHeight+'px';
    }
  }
  
  if (newsEllipsizer == null) {
    newsEllipsizer = new ellipsizer({refreshOnResize: false});
    newsEllipsizer.addElements(stories);
  } else {
    setTimeout(function () {
      newsEllipsizer.refresh();
    }, 1);
  }
}

function newsPaneSwitchStory(elem, direction) {
  if (elem.className.match(/disabled/)) { return false; }

  var stories = document.getElementById('newsStories').childNodes;
  
  var dots = document.getElementById('newsPagerDots').childNodes;
  var prev = document.getElementById('newsStoryPrev');
  var next = document.getElementById('newsStoryNext');
  
  for (var i = 0; i < stories.length; i++) {
    if (stories[i].className == 'current') {
      var j = direction == 'next' ? i+1 : i-1;
      
      if (j >= 0 || j < stories.length) {
        stories[i].className = '';
        stories[j].className = 'current';
        
        dots[i].className = '';
        dots[j].className = 'current';
        
        prev.className = (j == 0) ? 'disabled' : '';
        next.className = (j == (stories.length-1)) ? 'disabled' : '';
        
        newsEllipsizer.refresh();
      }
      
      break;
    }
  }
  
  return false;
}
