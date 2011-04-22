var containerScroller = null;
var navScroller = null;

function onDOMChange() {
  if (containerScroller) {
    setContainerWrapperHeight();
    containerScroller.refresh();
  }
}

// Update the nav slide indicators
function updateNavSlider() {
  var current = Math.abs(navScroller.x);
  var max = Math.abs(navScroller.maxScrollX);

  var canScrollLeft = (current > 0);
  var canScrollRight = (current < max-1);
  
  document.getElementById('slideleft').style.display  = canScrollLeft  ? 'block' : 'none';
  document.getElementById('slideright').style.display = canScrollRight ? 'block' : 'none';
}

// Change wrapper height based on device orientation.
function setContainerWrapperHeight() {
  document.getElementById('container').style.height = 'auto';

	var navbarHeight = document.getElementById('navbar').offsetHeight;
  var footerNavHeight = document.getElementById('footernav').offsetHeight;
	var wrapperHeight = window.innerHeight - navbarHeight - footerNavHeight;
	var containerHeight = document.getElementById('container').offsetHeight;
	
	document.getElementById('wrapper').style.height = wrapperHeight + 'px';
	
	if (containerHeight < wrapperHeight) {
	  document.getElementById('container').style.height = wrapperHeight + 'px';
	}
	
	// when this exists, make it fill the screen
	var fillscreen = document.getElementById('fillscreen');
	if (fillscreen) {
	  fillscreen.style.height = wrapperHeight + 'px';
	}
}

function handleWindowResize(e) { 
  setContainerWrapperHeight();
  
  setTimeout(updateNavSlider, 0);
  
  if (typeof moduleHandleWindowResize != 'undefined') {
    moduleHandleWindowResize(e);
  }
} 

function tabletInit() {
    if(!document.getElementById('navbar')) {
        // page has no footer so do not attempt
        // to use fancy tablet container
        return;
    }

  setContainerWrapperHeight();
  
  // Adjust wrapper height on orientation change or resize
  var resizeEvent = 'onorientationchange' in window ? 'orientationchange' : 'resize';
  window.addEventListener(resizeEvent, handleWindowResize, false);

  document.addEventListener('touchmove', function(e) { e.preventDefault(); });
  
  containerScroller = new iScroll('wrapper', { 
    checkDOMChanges: false, 
    hScrollbar: false,
    desktopCompatibility: true,
    bounce: false,
    bounceLock: true
  });


  navScroller = new iScroll('navsliderwrapper', { 
    checkDOMChanges: false, 
    hScrollbar: false,
    vScrollbar: false,
    desktopCompatibility: true,
    bounce: false,
    bounceLock: true,
    onScrollStart: updateNavSlider,
    onScrollEnd: updateNavSlider
  });

    handleWindowResize();
    updateNavSlider();

  //run module init if present
  if (typeof moduleInit != 'undefined') {
    moduleInit();
  }
}

function scrollToTop() {
  if (containerScroller) {
  	containerScroller.scrollTo(0,0,0); 
  }
}
