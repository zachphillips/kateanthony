/**
 * jscolor, JavaScript Color Picker
 *
 * @version 1.3.1
 * @license GNU Lesser General Public License, http://www.gnu.org/copyleft/lesser.html
 * @author  Jan Odvarko, http://odvarko.cz
 * @created 2008-06-15
 * @updated 2010-01-23
 * @link    http://jscolor.com
 */

/*
 GNU Lesser General Public License, http://www.gnu.org/copyleft/lesser.html
 @author  Jan Odvarko, http://odvarko.cz
 @created 2008-06-15
 @updated 2010-01-23
 @link    http://jscolor.com
*/
var jscolor={dir:EE.PATH_CP_GBL_IMG,bindClass:"color",binding:!0,preloading:!0,install:function(){jscolor.addEvent(window,"load",jscolor.init)},init:function(){jscolor.binding&&jscolor.bind();jscolor.preloading&&jscolor.preload()},getDir:function(){if(!jscolor.dir){var a=jscolor.detectDir();jscolor.dir=a!==!1?a:"jscolor/"}return jscolor.dir},detectDir:function(){for(var a=location.href,e=document.getElementsByTagName("base"),b=0;b<e.length;b+=1)if(e[b].href)a=e[b].href;e=document.getElementsByTagName("script");
for(b=0;b<e.length;b+=1)if(e[b].src&&/(^|\/)jscolor\.js([?#].*)?$/i.test(e[b].src))return a=(new jscolor.URI(e[b].src)).toAbsolute(a),a.path=a.path.replace(/[^\/]+$/,""),a.query=null,a.fragment=null,a.toString();return!1},bind:function(){for(var a=RegExp("(^|\\s)("+jscolor.bindClass+")\\s*(\\{[^}]*\\})?","i"),e=document.getElementsByTagName("input"),b=0;b<e.length;b+=1){var l;if(!e[b].color&&e[b].className&&(l=e[b].className.match(a))){if(l[3])try{eval("prop="+l[3])}catch(u){}e[b].color=new jscolor.color(e[b],
{})}}},preload:function(){for(var a in jscolor.imgRequire)jscolor.imgRequire.hasOwnProperty(a)&&jscolor.loadImage(a)},images:{pad:[181,101],sld:[16,101],cross:[15,15],arrow:[7,11]},imgRequire:{},imgLoaded:{},requireImage:function(a){jscolor.imgRequire[a]=!0},loadImage:function(a){if(!jscolor.imgLoaded[a])jscolor.imgLoaded[a]=new Image,jscolor.imgLoaded[a].src=jscolor.getDir()+a},fetchElement:function(a){return typeof a==="string"?document.getElementById(a):a},addEvent:function(a,e,b){a.addEventListener?
a.addEventListener(e,b,!1):a.attachEvent&&a.attachEvent("on"+e,b)},fireEvent:function(a,e){if(a)if(document.createEventObject){var b=document.createEventObject();a.fireEvent("on"+e,b)}else if(document.createEvent)b=document.createEvent("HTMLEvents"),b.initEvent(e,!0,!0),a.dispatchEvent(b);else if(a["on"+e])a["on"+e]()},getElementPos:function(a){var e=a,b=0,l=0;if(e.offsetParent){do b+=e.offsetLeft,l+=e.offsetTop;while(e=e.offsetParent)}for(;(a=a.parentNode)&&a.nodeName.toUpperCase()!=="BODY";)b-=
a.scrollLeft,l-=a.scrollTop;return[b,l]},getElementSize:function(a){return[a.offsetWidth,a.offsetHeight]},getMousePos:function(a){if(!a)a=window.event;if(typeof a.pageX==="number")return[a.pageX,a.pageY];else if(typeof a.clientX==="number")return[a.clientX+document.body.scrollLeft+document.documentElement.scrollLeft,a.clientY+document.body.scrollTop+document.documentElement.scrollTop]},getViewPos:function(){return typeof window.pageYOffset==="number"?[window.pageXOffset,window.pageYOffset]:document.body&&
(document.body.scrollLeft||document.body.scrollTop)?[document.body.scrollLeft,document.body.scrollTop]:document.documentElement&&(document.documentElement.scrollLeft||document.documentElement.scrollTop)?[document.documentElement.scrollLeft,document.documentElement.scrollTop]:[0,0]},getViewSize:function(){return typeof window.innerWidth==="number"?[window.innerWidth,window.innerHeight]:document.body&&(document.body.clientWidth||document.body.clientHeight)?[document.body.clientWidth,document.body.clientHeight]:
document.documentElement&&(document.documentElement.clientWidth||document.documentElement.clientHeight)?[document.documentElement.clientWidth,document.documentElement.clientHeight]:[0,0]},URI:function(a){function e(b){for(var a="";b;)if(b.substr(0,3)==="../"||b.substr(0,2)==="./")b=b.replace(/^\.+/,"").substr(1);else if(b.substr(0,3)==="/./"||b==="/.")b="/"+b.substr(3);else if(b.substr(0,4)==="/../"||b==="/..")b="/"+b.substr(4),a=a.replace(/\/?[^\/]*$/,"");else if(b==="."||b==="..")b="";else{var e=
b.match(/^\/?[^\/]*/)[0],b=b.substr(e.length);a+=e}return a}this.authority=this.scheme=null;this.path="";this.fragment=this.query=null;this.parse=function(b){b=b.match(/^(([A-Za-z][0-9A-Za-z+.-]*)(:))?((\/\/)([^\/?#]*))?([^?#]*)((\?)([^#]*))?((#)(.*))?/);this.scheme=b[3]?b[2]:null;this.authority=b[5]?b[6]:null;this.path=b[7];this.query=b[9]?b[10]:null;this.fragment=b[12]?b[13]:null;return this};this.toString=function(){var b="";this.scheme!==null&&(b=b+this.scheme+":");this.authority!==null&&(b=b+
"//"+this.authority);this.path!==null&&(b+=this.path);this.query!==null&&(b=b+"?"+this.query);this.fragment!==null&&(b=b+"#"+this.fragment);return b};this.toAbsolute=function(b){var b=new jscolor.URI(b),a=new jscolor.URI;if(b.scheme===null)return!1;if(this.scheme!==null&&this.scheme.toLowerCase()===b.scheme.toLowerCase())this.scheme=null;this.scheme!==null?(a.scheme=this.scheme,a.authority=this.authority,a.path=e(this.path),a.query=this.query):(this.authority!==null?(a.authority=this.authority,a.path=
e(this.path),a.query=this.query):(this.path===""?(a.path=b.path,a.query=this.query!==null?this.query:b.query):(this.path.substr(0,1)==="/"?a.path=e(this.path):(a.path=b.authority!==null&&b.path===""?"/"+this.path:b.path.replace(/[^\/]+$/,"")+this.path,a.path=e(a.path)),a.query=this.query),a.authority=b.authority),a.scheme=b.scheme);a.fragment=this.fragment;return a};a&&this.parse(a)},color:function(a,e){function b(f,a,c){if(f===null)return[c,c,c];var b=Math.floor(f),d=c*(1-a),f=c*(1-a*(b%2?f-b:1-
(f-b)));switch(b){case 6:case 0:return[c,f,d];case 1:return[f,c,d];case 2:return[d,c,f];case 3:return[d,f,c];case 4:return[f,d,c];case 5:return[c,d,f]}}function l(f,b){if(!jscolor.picker){jscolor.picker={box:document.createElement("div"),boxB:document.createElement("div"),pad:document.createElement("div"),padB:document.createElement("div"),padM:document.createElement("div"),sld:document.createElement("div"),sldB:document.createElement("div"),sldM:document.createElement("div")};for(var c=0;c<jscolor.images.sld[1];c+=
4){var e=document.createElement("div");e.style.height="4px";e.style.fontSize="1px";e.style.lineHeight="0";jscolor.picker.sld.appendChild(e)}jscolor.picker.sldB.appendChild(jscolor.picker.sld);jscolor.picker.box.appendChild(jscolor.picker.sldB);jscolor.picker.box.appendChild(jscolor.picker.sldM);jscolor.picker.padB.appendChild(jscolor.picker.pad);jscolor.picker.box.appendChild(jscolor.picker.padB);jscolor.picker.box.appendChild(jscolor.picker.padM);jscolor.picker.boxB.appendChild(jscolor.picker.box)}c=
jscolor.picker;q=[f+d.pickerBorder+d.pickerFace+d.pickerInset,b+d.pickerBorder+d.pickerFace+d.pickerInset];c.box.onmouseup=c.box.onmouseout=function(){a.focus()};c.box.onmousedown=function(){o=!0};c.box.onmousemove=function(c){r&&y(c);s&&z(c)};c.padM.onmouseup=c.padM.onmouseout=function(){r&&(r=!1,jscolor.fireEvent(i,"change"))};c.padM.onmousedown=function(c){r=!0;y(c)};c.sldM.onmouseup=c.sldM.onmouseout=function(){s&&(s=!1,jscolor.fireEvent(i,"change"))};c.sldM.onmousedown=function(c){s=!0;z(c)};
c.box.style.width=4*d.pickerInset+2*d.pickerFace+jscolor.images.pad[0]+2*jscolor.images.arrow[0]+jscolor.images.sld[0]+"px";c.box.style.height=2*d.pickerInset+2*d.pickerFace+jscolor.images.pad[1]+"px";c.boxB.style.position="absolute";c.boxB.style.clear="both";c.boxB.style.left=f+"px";c.boxB.style.top=b+"px";c.boxB.style.zIndex=d.pickerZIndex;c.boxB.style.border=d.pickerBorder+"px solid";c.boxB.style.borderColor=d.pickerBorderColor;c.boxB.style.background=d.pickerFaceColor;c.pad.style.width=jscolor.images.pad[0]+
"px";c.pad.style.height=jscolor.images.pad[1]+"px";c.padB.style.position="absolute";c.padB.style.left=d.pickerFace+"px";c.padB.style.top=d.pickerFace+"px";c.padB.style.border=d.pickerInset+"px solid";c.padB.style.borderColor=d.pickerInsetColor;c.padM.style.position="absolute";c.padM.style.left="0";c.padM.style.top="0";c.padM.style.width=d.pickerFace+2*d.pickerInset+jscolor.images.pad[0]+jscolor.images.arrow[0]+"px";c.padM.style.height=c.box.style.height;c.padM.style.cursor="crosshair";c.sld.style.overflow=
"hidden";c.sld.style.width=jscolor.images.sld[0]+"px";c.sld.style.height=jscolor.images.sld[1]+"px";c.sldB.style.position="absolute";c.sldB.style.right=d.pickerFace+"px";c.sldB.style.top=d.pickerFace+"px";c.sldB.style.border=d.pickerInset+"px solid";c.sldB.style.borderColor=d.pickerInsetColor;c.sldM.style.position="absolute";c.sldM.style.right="0";c.sldM.style.top="0";c.sldM.style.width=jscolor.images.sld[0]+jscolor.images.arrow[0]+d.pickerFace+2*d.pickerInset+"px";c.sldM.style.height=c.box.style.height;
try{c.sldM.style.cursor="pointer"}catch(k){c.sldM.style.cursor="hand"}switch(m){case 0:var j="hs.png";break;case 1:j="hv.png"}c.padM.style.background="url('"+jscolor.getDir()+"cross.gif') no-repeat";c.sldM.style.background="url('"+jscolor.getDir()+"arrow.gif') no-repeat";c.pad.style.background="url('"+jscolor.getDir()+j+"') 0 0 no-repeat";u();A();jscolor.picker.owner=d;document.getElementsByTagName("body")[0].appendChild(c.boxB)}function u(){switch(m){case 0:var f=1;break;case 1:f=2}var a=Math.round(d.hsv[0]/
6*(jscolor.images.pad[0]-1)),f=Math.round((1-d.hsv[f])*(jscolor.images.pad[1]-1));jscolor.picker.padM.style.backgroundPosition=d.pickerFace+d.pickerInset+a-Math.floor(jscolor.images.cross[0]/2)+"px "+(d.pickerFace+d.pickerInset+f-Math.floor(jscolor.images.cross[1]/2))+"px";a=jscolor.picker.sld.childNodes;switch(m){case 0:for(var c=b(d.hsv[0],d.hsv[1],1),f=0;f<a.length;f+=1)a[f].style.backgroundColor="rgb("+c[0]*(1-f/a.length)*100+"%,"+c[1]*(1-f/a.length)*100+"%,"+c[2]*(1-f/a.length)*100+"%)";break;
case 1:var e,k=[d.hsv[2],0,0],f=Math.floor(d.hsv[0]),j=f%2?d.hsv[0]-f:1-(d.hsv[0]-f);switch(f){case 6:case 0:c=[0,1,2];break;case 1:c=[1,0,2];break;case 2:c=[2,0,1];break;case 3:c=[2,1,0];break;case 4:c=[1,2,0];break;case 5:c=[0,2,1]}for(f=0;f<a.length;f+=1)e=1-1/(a.length-1)*f,k[1]=k[0]*(1-e*j),k[2]=k[0]*(1-e),a[f].style.backgroundColor="rgb("+k[c[0]]*100+"%,"+k[c[1]]*100+"%,"+k[c[2]]*100+"%)"}}function A(){switch(m){case 0:var a=2;break;case 1:a=1}a=Math.round((1-d.hsv[a])*(jscolor.images.sld[1]-
1));jscolor.picker.sldM.style.backgroundPosition="0 "+(d.pickerFace+d.pickerInset+a-Math.floor(jscolor.images.arrow[1]/2))+"px"}function t(){return jscolor.picker&&jscolor.picker.owner===d}function B(){i!==a&&d.importColor()}function y(a){var b=jscolor.getMousePos(a),a=b[0]-q[0],b=b[1]-q[1];switch(m){case 0:d.fromHSV(a*(6/(jscolor.images.pad[0]-1)),1-b/(jscolor.images.pad[1]-1),null,v);break;case 1:d.fromHSV(a*(6/(jscolor.images.pad[0]-1)),null,1-b/(jscolor.images.pad[1]-1),v)}}function z(a){a=jscolor.getMousePos(a)[1]-
q[1];switch(m){case 0:d.fromHSV(null,null,1-a/(jscolor.images.sld[1]-1),w);break;case 1:d.fromHSV(null,1-a/(jscolor.images.sld[1]-1),null,w)}}this.adjust=this.required=!0;this.hash=!1;this.caps=!0;this.styleElement=this.valueElement=a;this.hsv=[0,0,1];this.rgb=[1,1,1];this.pickerOnfocus=!0;this.pickerMode="HSV";this.pickerPosition="bottom";this.pickerFace=10;this.pickerFaceColor="#f4f6f6";this.pickerBorder=1;this.pickerBorderColor="#d0d7df";this.pickerInset=1;this.pickerInsetColor="#d0d7df";this.pickerZIndex=
1E4;for(var n in e)e.hasOwnProperty(n)&&(this[n]=e[n]);this.hidePicker=function(){t()&&(delete jscolor.picker.owner,document.getElementsByTagName("body")[0].removeChild(jscolor.picker.boxB))};this.showPicker=function(){if(!t()){var b=jscolor.getElementPos(a),d=jscolor.getElementSize(a),c=jscolor.getViewPos(),e=jscolor.getViewSize(),k=[2*this.pickerBorder+4*this.pickerInset+2*this.pickerFace+jscolor.images.pad[0]+2*jscolor.images.arrow[0]+jscolor.images.sld[0],2*this.pickerBorder+2*this.pickerInset+
2*this.pickerFace+jscolor.images.pad[1]],j,g,i;switch(this.pickerPosition.toLowerCase()){case "left":j=1;g=0;i=-1;break;case "right":j=1;g=0;i=1;break;case "top":j=0;g=1;i=-1;break;default:j=0,i=g=1}var h=(d[g]+k[g])/2,b=[-c[j]+b[j]+k[j]>e[j]?-c[j]+b[j]+d[j]/2>e[j]/2&&b[j]+d[j]-k[j]>=0?b[j]+d[j]-k[j]:b[j]:b[j],-c[g]+b[g]+d[g]+k[g]-h+h*i>e[g]?-c[g]+b[g]+d[g]/2>e[g]/2&&b[g]+d[g]-h-h*i>=0?b[g]+d[g]-h-h*i:b[g]+d[g]-h+h*i:b[g]+d[g]-h+h*i>=0?b[g]+d[g]-h+h*i:b[g]+d[g]-h-h*i];l(b[j],b[g])}};this.importColor=
function(){if(i)if(this.adjust)!this.required&&/^\s*$/.test(i.value)?(i.value="",h.style.backgroundColor=h.jscStyle.backgroundColor,h.style.color=h.jscStyle.color,this.exportColor(p|x)):this.fromString(i.value)||this.exportColor();else{if(!this.fromString(i.value,p))h.style.backgroundColor=h.jscStyle.backgroundColor,h.style.color=h.jscStyle.color,this.exportColor(p|x)}else this.exportColor()};this.exportColor=function(a){if(!(a&p)&&i){var b=this.toString();this.caps&&(b=b.toUpperCase());this.hash&&
(b="#"+b);i.value=b}if(!(a&x)&&h)h.style.backgroundColor="#"+this.toString(),h.style.color=0.213*this.rgb[0]+0.715*this.rgb[1]+0.072*this.rgb[2]<0.5?"#FFF":"#000";!(a&w)&&t()&&u();!(a&v)&&t()&&A()};this.fromHSV=function(a,d,c,e){a<0&&(a=0)||a>6&&(a=6);d<0&&(d=0)||d>1&&(d=1);c<0&&(c=0)||c>1&&(c=1);this.rgb=b(a===null?this.hsv[0]:this.hsv[0]=a,d===null?this.hsv[1]:this.hsv[1]=d,c===null?this.hsv[2]:this.hsv[2]=c);this.exportColor(e)};this.fromRGB=function(a,b,c,d){a<0&&(a=0)||a>1&&(a=1);b<0&&(b=0)||
b>1&&(b=1);c<0&&(c=0)||c>1&&(c=1);var a=a===null?this.rgb[0]:this.rgb[0]=a,b=b===null?this.rgb[1]:this.rgb[1]=b,e=c===null?this.rgb[2]:this.rgb[2]=c,h=Math.min(Math.min(a,b),e),c=Math.max(Math.max(a,b),e),g=c-h;g===0?a=[null,0,c]:(a=a===h?3+(e-b)/g:b===h?5+(a-e)/g:1+(b-a)/g,a=[a===6?0:a,g/c,c]);a[0]!==null&&(this.hsv[0]=a[0]);a[2]!==0&&(this.hsv[1]=a[1]);this.hsv[2]=a[2];this.exportColor(d)};this.fromString=function(a,b){var c=a.match(/^\W*([0-9A-F]{3}([0-9A-F]{3})?)\W*$/i);return c?(c[1].length===
6?this.fromRGB(parseInt(c[1].substr(0,2),16)/255,parseInt(c[1].substr(2,2),16)/255,parseInt(c[1].substr(4,2),16)/255,b):this.fromRGB(parseInt(c[1].charAt(0)+c[1].charAt(0),16)/255,parseInt(c[1].charAt(1)+c[1].charAt(1),16)/255,parseInt(c[1].charAt(2)+c[1].charAt(2),16)/255,b),!0):!1};this.toString=function(){return(256|Math.round(255*this.rgb[0])).toString(16).substr(1)+(256|Math.round(255*this.rgb[1])).toString(16).substr(1)+(256|Math.round(255*this.rgb[2])).toString(16).substr(1)};var d=this,m=
this.pickerMode.toLowerCase()==="hvs"?1:0,o=!1,i=jscolor.fetchElement(this.valueElement),h=jscolor.fetchElement(this.styleElement),r=!1,s=!1,q,p=1,x=2,w=4,v=8;jscolor.addEvent(a,"focus",function(){d.pickerOnfocus&&d.showPicker()});jscolor.addEvent(a,"blur",function(){o?o=!1:window.setTimeout(function(){o||(i===a&&d.importColor(),d.pickerOnfocus&&d.hidePicker());o=!1},0)});i&&(n=function(){d.fromString(i.value,p)},jscolor.addEvent(i,"keyup",n),jscolor.addEvent(i,"input",n),jscolor.addEvent(i,"blur",
B),i.setAttribute("autocomplete","off"));if(h)h.jscStyle={backgroundColor:h.style.backgroundColor,color:h.style.color};switch(m){case 0:jscolor.requireImage("hs.png");break;case 1:jscolor.requireImage("hv.png")}jscolor.requireImage("cross.gif");jscolor.requireImage("arrow.gif");this.importColor()}};jscolor.install();