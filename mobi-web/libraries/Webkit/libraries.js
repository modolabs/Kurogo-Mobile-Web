function parseform(objForm) {
	var strQuery = document.getElementById("query").value;
	var searchURL = "http://mobileworldcat.org/?location=02139&os=MYG&on=MIT&sort=relevance&q=" + strQuery;
	var results = window.open(searchURL);
}
