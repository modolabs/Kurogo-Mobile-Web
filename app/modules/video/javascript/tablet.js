function moduleInit() {
    videoListScroller = new iScroll('videos');
    videoDetailScroller = new iScroll('videoDetailWrapper', {checkDOMChange: true} );
    
    var links = document.querySelectorAll("#videos .results a");
    var first = true;
    for (var i=0;i<links.length;i++) {
        if (links[i].href.match(/detail/)) {
            links[i].onclick = function(e) {
                videoDetailScroller.scrollTo(0,0);
                var selected = document.querySelectorAll("#videos .results .videoSelected");
                for (var j=0;j<selected.length;j++) {
                    selected[j].className=selected[j].oldclass;
                }
                this.oldclass = this.className;
                this.className += ' videoSelected';
                var httpRequest = new XMLHttpRequest();
                httpRequest.open("GET", this.href+'&ajax=1', true);
                httpRequest.onreadystatechange = function() {
                    if (httpRequest.readyState == 4 && httpRequest.status == 200) {
                        document.getElementById('videoDetail').innerHTML = httpRequest.responseText;
                        videoDetailScroller.refresh();
                        moduleHandleWindowResize();
                    }
                }
                httpRequest.send(null);
                if (e) {
                    e.preventDefault();
                }
            }
            if (first) {
                links[i].onclick();
                first = false;
            }
        }
    }
    
    moduleHandleWindowResize();
    
}

function moduleHandleWindowResize() {
    document.getElementById('videos').style.height = 'auto';
    document.getElementById('videoDetailWrapper').style.height = 'auto';
	var wrapperHeight = document.getElementById('wrapper').offsetHeight;
    var headerHeight = document.getElementById('videoHeader').offsetHeight;
    var contentHeight = wrapperHeight - headerHeight;
	document.getElementById('videos').style.height = contentHeight + 'px';
	document.getElementById('videoDetailWrapper').style.height = contentHeight + 'px';
}
