/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function drawLineChart(strCanvasID,fltLineWidth,hexLineColor,fltYMin,fltYMax,arrValues) {
    var cvs=document.getElementById(strCanvasID);
    var len=arrValues.length;
    if(cvs && cvs.getContext) {
        var context=cvs.getContext("2d");
        context.lineWidth=fltLineWidth;
        context.beginPath();
        context.lineTo((0-fltLineWidth),relY(cvs.height,fltYMin,fltYMax,arrValues[0]));	// Move to just off the left edge of the canvas
        for(var i=1;i<len;i++) {
            context.lineTo((cvs.width*(i/(len-1))),relY(cvs.height,fltYMin,fltYMax,arrValues[i]));
        }
        context.strokeStyle="#"+cutHex(hexLineColor);
        context.stroke();
    }
}

function drawAreaChart(strCanvasID,fltLineWidth,hexLineColor,hexFillColor,intFillOpacity,fltYMin,fltYMax,arrValues) {
    var cvs=document.getElementById(strCanvasID);
    var len=arrValues.length;
    if(cvs && cvs.getContext) {
        var context=cvs.getContext("2d");
        context.lineWidth=fltLineWidth;
        context.beginPath();
        context.lineTo((0-fltLineWidth),relY(cvs.height,fltYMin,fltYMax,arrValues[0]));	// Move to just off the left edge of the canvas
        for(var i=1;i<len;i++) {
            context.lineTo((cvs.width*(i/(len-1))),relY(cvs.height,fltYMin,fltYMax,arrValues[i]));
        }
        context.lineTo((cvs.width+fltLineWidth),(cvs.height+fltLineWidth));
        context.lineTo(0,cvs.height+fltLineWidth);
        context.strokeStyle="#"+cutHex(hexLineColor);
        context.stroke();
        context.fillStyle="rgba("+hexToR(hexFillColor)+","+hexToG(hexFillColor)+","+hexToB(hexFillColor)+","+(intFillOpacity/100)+")";
        context.fill();
    }
}

function drawCircle(strCanvasID,fltLineWidth,hexLineColor,hexFillColor,intFillOpacity,fltXMin,fltXMax,fltYMin,fltYMax,fltX,fltY,fltR) {
    var cvs=document.getElementById(strCanvasID);
    if(cvs && cvs.getContext) {
        var context=cvs.getContext("2d");
        context.lineWidth=fltLineWidth;
        context.beginPath();
        context.arc(relX(cvs.width,fltXMin,fltXMax,fltX),relY(cvs.height,fltYMin,fltYMax,fltY),fltR,0,Math.PI*2,true);
        context.closePath();
        context.strokeStyle="#"+cutHex(hexLineColor);
        context.stroke();
        context.fillStyle="rgba("+hexToR(hexFillColor)+","+hexToG(hexFillColor)+","+hexToB(hexFillColor)+","+(intFillOpacity/100)+")";
        context.fill();
    }
}

function drawText(strCanvasID,hexTextColor,fltXMin,fltXMax,fltYMin,fltYMax,fltX,fltY,fltSize,blnBold,blnItalic,txtFace,strText) {
    var cvs=document.getElementById(strCanvasID);
    var txtFont=fltSize+"px " + txtFace;
    if(blnItalic) { txtFont = "italic " + txtFont; }
    if(blnBold) { txtFont = "bold " + txtFont; }
    if(cvs && cvs.getContext) {
        var context=cvs.getContext("2d");
        context.font = txtFont;
        context.textBaseline = 'middle';
        context.textAlign = 'center';
        context.fillStyle=hexTextColor;
        context.fillText(strText,relX(cvs.width,fltXMin,fltXMax,fltX), relY(cvs.height,fltYMin,fltYMax,fltY));
    }
}

function drawCircleLabeled(strCanvasID,fltLineWidth,hexLineColor,hexFillColor,intFillOpacity,fltXMin,fltXMax,fltYMin,fltYMax,fltX,fltY,fltR,strText) {
    drawCircle(strCanvasID,fltLineWidth,hexLineColor,hexFillColor,intFillOpacity,fltXMin,fltXMax,fltYMin,fltYMax,fltX,fltY,fltR);
    drawText(strCanvasID,"#000000",fltXMin,fltXMax,fltYMin,fltYMax,fltX,fltY,(fltR*.75),1,0,"sans-serif",strText);
}


function relX(fltWidth,fltXMin,fltXMax,fltXVal) {
    return (fltWidth*((fltXMax-fltXVal)/(fltXMax-fltXMin)));
}

function relY(fltHeight,fltYMin,fltYMax,fltYVal) {
    return (fltHeight*((fltYMax-fltYVal)/(fltYMax-fltYMin)));
}

function setCanvasSize(strCanvasID) {
    var cvs=document.getElementById(strCanvasID);
    var multiplier=2; 	// Accommodates high-density displays - must adjust line weights and other scaled elements drawn onto canvas to compensate
    if(cvs) {
        cvs.width=(cvs.parentNode.offsetWidth)*multiplier;
        cvs.height=(cvs.parentNode.offsetHeight)*multiplier;
    }
}

function hexToR(h) {return parseInt((cutHex(h)).substring(0,2),16)}
function hexToG(h) {return parseInt((cutHex(h)).substring(2,4),16)}
function hexToB(h) {return parseInt((cutHex(h)).substring(4,6),16)}
function cutHex(h) {return (h.charAt(0)=="#") ? h.substring(1,7):h}
