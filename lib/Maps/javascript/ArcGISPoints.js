point = new esri.geometry.Point(___X___, ___Y___, spatialRef);
pointSymbol = new esri.symbol.___SYMBOL_TYPE___([___SYMBOL_ARGS___]);
var graphic___IDENTIFIER___ = new esri.Graphic(point, pointSymbol);

infoTemplate = new esri.InfoTemplate();
infoTemplate.setTitle("___TITLE___");
infoTemplate.setContent("___DESCRIPTION___");
graphic___IDENTIFIER___.setInfoTemplate(infoTemplate);

map.graphics.add(graphic___IDENTIFIER___);

dojo.connect(map, "onClick", function(e) {
    map.infoWindow.hide();
});
