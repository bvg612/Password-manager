// extended object inheritance support for versions of ECMAScript < 5
if (typeof Object.create !== "function") {
  Object.create = function (proto, propertiesObject) {
    if (typeof proto !== 'object' && typeof proto !== 'function') {
      throw new TypeError('Object prototype may only be an Object: ' + proto);
    } else if (proto === null) {
      throw new Error("This browser's implementation of Object.create is a shim and doesn't support 'null' as the first argument.");
    }

    if (typeof propertiesObject != 'undefined') {
      throw new Error("This browser's implementation of Object.create is a shim and doesn't support a second argument.");
    }

    function F() {}
    F.prototype = proto;

    return new F();
  };
}

/**
 * @param obj
 * @returns {number}
 */
function ObjLength(obj) {
  if(Object.keys) {
    return Object.keys(obj).length;
  }

  var len = 0, key;
  for (key in obj) {
    if (obj.hasOwnProperty(key))
      ++len;
  }
  return len;
}


/**
 * @param str
 * @returns {string}
 */
function urlencode(str) {
  if(encodeURIComponent)
    return encodeURIComponent(str);
  if(escape)
    return escape(str);
  return str;
}

function urldecode(str) {
  if(decodeURIComponent)
    return decodeURIComponent(str);
  return str;
}

window.ns = {
  isReady : false,
  readyHandlers : [],
  cookieCache : [],
  _is_mobile : null,
  _is_tablet : null,
  loadedJsFiles : [],
  htmlSpecialChatsArray : [
    {search: "&", replace:"&amp;"},
    {search: "<", replace:"&lt;"},
    {search: ">", replace:"&gt;"},
    {search: "\"", replace:"&quot;"},
    {search: "'", replace:"&#039;"}
    ]

};

window.ns._isJsLoaded = function(jsUrl) {
  var i;
  for(i = 0; i<ns.loadedJsFiles.length; ++i) {
    if(ns.loadedJsFiles[i] == jsUrl)
      return true;
  }
  return false;
};


function loadScript(url, callback){

  var script = document.createElement("script");
  script.type = "text/javascript";

  if (script.readyState){  //IE
    script.onreadystatechange = function(){
      if (script.readyState == "loaded" ||
          script.readyState == "complete"){
        script.onreadystatechange = null;
        callback();
      }
    };
  } else {  //Others
    script.onload = function(){
      callback();
    };
  }

  script.src = url;
  document.getElementsByTagName("head")[0].appendChild(script);
}

window.ns.loadJs = function(jsUrl, callback){
  if(ns._isJsLoaded(jsUrl)) {
    callback();
    return;
  }
  // DOM: Create the script element
  var script = document.createElement("script");
  // set the type attribute
  script.type = "application/javascript";
  // make the script element load file
  script.src = jsUrl;

  if (script.readyState){  //IE
    script.onreadystatechange = function(){
      if (script.readyState == "loaded" ||
          script.readyState == "complete"){
        script.onreadystatechange = null;
        callback();
      }
    };
  } else {  //Others
    script.onload = function(){
      callback();
    };
  }

  // finally insert the element to the body element in order to load the script
  document.head.appendChild(script);
  ns.loadedJsFiles.push(jsUrl);
};

window.ns.isTouchDevice = function () {
  return 'ontouchstart' in window        // works on most browsers
      || navigator.maxTouchPoints;       // works on IE10/11 and Surface
};
window.ns.isMobile = function(){
  if(window.ns._is_mobile === null) {
    window.ns._is_mobile = window.mobilecheck()?true:false;
  }
  return window.ns._is_mobile;
};
window.ns.isTablet = function(){
  if(window.ns._is_tablet === null) {
    window.ns._is_tablet = window.mobileAndTabletcheck()?true:false;
    if(window.ns.isMobile())
      window.ns._is_tablet = false;
  }
  return window.ns._is_tablet;
};

window.ns.reload = function(forced){
  window.location.reload(forced);
};

window.ns.createAjaxObject = function(){
  var xhr;
  try{
    xhr = typeof XMLHttpRequest != 'undefined'
      ? new XMLHttpRequest()
      : new ActiveXObject('Microsoft.XMLHTTP');
  }catch(e){
    try{
      xhr = new ActiveXObject('Msxml2.XMLHTTP');
    }catch(e){
      return false;
    }
  }
  return xhr;
};

window.ns.deleteCookie = function(cookieName) {
  document.cookie = cookieName+'=; Max-Age=-99999999;';
};
window.ns.setCookie = function(cookieName, value, expireDays, path){
  var exdate = new Date();
  exdate.setTime(exdate.getTime() + expireDays*24*3600*1000);
  if(!path){
    path="/"
  }
  path="; path="+path;

  var cookieValue = urlencode(value) + ((expireDays == null) ? "" : "; expires="+exdate.toUTCString())+path;
  document.cookie = cookieName + "=" + cookieValue;
};
window.ns.getCookie = function(c_name, defaultValue) {
  if(typeof(defaultValue) == "undefined")
    defaultValue = false;
  if(typeof(window.ns.cookieCache[c_name]) != "undefined")
    return window.ns.cookieCache[c_name];

  var i, x, y, ARRcookies = document.cookie.split(";");
  for (i = 0; i < ARRcookies.length; i += 1) {
    x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
    y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
    x = x.replace(/^\s+|\s+$/g, "");
    if (x === c_name) {
        window.ns.cookieCache[c_name] = unescape(y);
        return window.ns.cookieCache[c_name];
    }
  }
  return defaultValue;
};

window.ns.addReadyHandler = function(handler){
  if(typeof handler !== 'function') {
    if(handler) {
      console.log(handler.name + ' is not a function');
    }else {
      console.log('addReadyHandler() must have a function as a parameter');
    }
    console.trace();
    return;
  }

  if(window.ns.isReady){
    handler();
  }else{
    var len = window.ns.readyHandlers.length;
    window.ns.readyHandlers.push(handler);
  }
};

// Dean Edwards/Matthias Miller/John Resig

window.ns.documentReady = function () {
  // quit if this function has already been called
  if (arguments.callee.done) return;

  // flag this function so we don't do the same thing twice
  arguments.callee.done = true;

  // kill the timer
  if (_timer) clearInterval(_timer);

  var readyHandler;
  // do stuff
  window.ns.isReady = true;
  /*
  if(console){
    console.log("document ready event occured");
  }
  */
  var len = window.ns.readyHandlers.length;
  for(var i=0;i<len;++i){
    readyHandler = window.ns.readyHandlers[i];
    if(typeof readyHandler !== 'function') {
      console.trace();
    }else {
      readyHandler();
    }
  }
};


/* for Mozilla/Opera9 */
if (typeof(document.addEventListener) != 'undefined') {
  document.addEventListener("DOMContentLoaded", window.ns.documentReady, false);
}

/* for Internet Explorer */
/*@cc_on @*/
/*@if (@_win32)
  document.write("<script id=__ie_onload defer src=javascript:void(0)><\/script>");
  var script = document.getElementById("__ie_onload");
  script.onreadystatechange = function() {
    if (this.readyState == "complete") {
      window.ns.documentReady(); // call the onload handler
    }
  };
/*@end @*/

/* for Safari */
if (/WebKit/i.test(navigator.userAgent)) { // sniff
  var _timer = setInterval(function() {
    if (/loaded|complete/.test(document.readyState)) {
      window.ns.documentReady(); // call the onload handler
    }
  }, 10);
}

/* for other browsers */
window.onload = window.ns.documentReady;


/**
 *
 * @param {Object} obj
 * @param {array=} args Optional
 * @param {boolean|int=} appendArgs Default false
 * @returns {Function}
 */
Function.prototype.createDelegate = function(obj, args, appendArgs) {
  var method = this;
  return function() {
    var callArgs = args || arguments;
    if(appendArgs === true){
      callArgs = Array.prototype.slice.call(arguments, 0);
      callArgs = callArgs.concat(args);
    }else if(typeof appendArgs == "number"){
      callArgs = Array.prototype.slice.call(arguments, 0);
// copy arguments first
      var applyArgs = [appendArgs, 0].concat(args);
// create method call params
      Array.prototype.splice.apply(callArgs, applyArgs);
// splice them in
    }
    return method.apply(obj || window, callArgs);
  };
};

Function.prototype.defer = function(millis, obj, args){
  var fn = this.createDelegate(obj, args);
  if(millis > 0){
    return setTimeout(fn, millis);
  }
  fn();
  return null;
};


function t(varName) {
  if (typeof langvar[varName] == 'undefined')
    return '<'+varName+' is undefined>';
  return langvar[varName];
}

// function encodeParams(params) {
//   query = [];
//   for(var paramName in params){
//     if(!params.hasOwnProperty(paramName))
//       continue;
//     query.push(urlencode(paramName)+'='+urlencode(params[paramName]));
//   }
//   return query.join('&');
// }


/**
 * @param {object} params
 */
function url(params) {
  this.url = window.location.href;
  if (typeof params != 'undefined') {
    for (var name in params) {
      if(!params.hasOwnProperty(name))
        continue;
      this.url += '&' + name + '=' + params[name];
    }
  }
  this.addParam = function(key, val) {
    var re = new RegExp('([?&])' + key + '=[a-z0-9-]+');
    if (this.url.match(re)) {
      this.url = this.url.replace(re, '$1' + key + '=' + val);
    } else {
      this.url += '&' + key + '=' + val;
    }
  };
  this.go = function() {
    window.location.href = this.url;
  }
}

function findParentNodeByTagName(element, parentName){
  var tag = element;
  parentName = parentName.toUpperCase();
  while(tag.tagName.toUpperCase() != parentName){
    if (typeof(tag.parentNode) == 'undefined')
      return false;
    if(tag.tagName == 'BODY')
      return false;
    tag = tag.parentNode;
  }
  return tag;
}

/**
 *
 * @param element
 * @param childName Upper case (will be auto up-cased)
 * @return {*}
 */
function findChildNodeByTagName(element, childName){
  var children = element.childNodes;
  childName = childName.toUpperCase();
  for (var i in children){
    if (children[i].nodeType != 1) //ELEMENT_NODE
      continue;
    if(children[i].tagName.toUpperCase() == childName)
      return children[i];
  }
  return false;
}

function toggleCheckbox(obj, index) {
  if (typeof index == 'undefined')
    index = 0;
  var box = obj.getElementsByTagName('input')[index];
  box.checked = !box.checked;
  changeBgColor(obj, box.checked);
}

function toggleCheckboxes(obj, parentLvl, index) {
  if (typeof index == 'undefined')
    index = 0;
  var box;
  // find a parent node which has more than one input element
  while (obj.getElementsByTagName('input').length < 2) {
    obj = obj.parentNode;
  }
  var boxes = obj.getElementsByTagName('input');
  boxes[0].checked = !boxes[0].checked;
  var mainBoxChecked = boxes[0].checked;
  var boxesLen = boxes.length;
  for (var i = 1; i < boxesLen; i++) {
    box = boxes[i];
    for (var j = 0; j < parentLvl; j++) {
      box = box.parentNode;
    }
    if (mainBoxChecked) {
      boxCheck(box, index);
    } else {
      boxUncheck(box, index);
    }
  }
}

function changeBgColor(obj, boxChecked) {
  var clName = 'rowSelected';
  if (boxChecked) {
    addClass(obj, clName);
  } else {
    removeClass(obj, clName);
  }
}

function boxCheck(obj, index) {
  obj.getElementsByTagName('input')[index].checked = true;
  addClass(obj, 'rowSelected');
}

function boxUncheck(obj, index) {
  obj.getElementsByTagName('input')[index].checked = false;
  removeClass(obj, 'rowSelected');
}


/**
 *
 * @param obj
 * @return {string[]}
 */
function splitClasses(obj){

  var s = obj.getAttribute('class');
  if(!s)
    return [];
  var re = new RegExp('\\s+');
  return s.split(re);

}

function addClass(obj, clName) {
  if (hasClass(obj, clName))
    return;

  if (obj.className == '')
    obj.className = obj.className + ' ';
  obj.setAttribute('class', obj.className + ' ' + clName);
}

function removeClass(obj, clName) {
  if (!hasClass(obj, clName))
    return;

  var classes = splitClasses(obj);
  var newClasses = '';
  var n = 0;

  for (var i=0;i<classes.length;++i) {
    if (classes[i] == clName)
      continue;
    if (n > 0)
      newClasses = newClasses + ' ';
    newClasses = newClasses + classes[i];
    n++;
  }
  obj.setAttribute('class', newClasses);
}

function hasClass(obj, clName) {
  var classes = splitClasses(obj);

  for (var i=0 ; i<classes.length; ++i) {
    if (classes[i] == clName)
      return true;
  }
  return false;
}

function getElementsByClassName(clName, node, tagName)  {
  if (typeof tagName == 'undefined')
    tagName = '*';
  if ((typeof node == 'undefined') || (!node))
    node = document.getElementsByTagName("body")[0];
  var a = [];
  var els = node.getElementsByTagName(tagName);
  var elsLen = els.length;
  for (var i = 0; i < elsLen; i++) {
    if (hasClass(els[i], clName))
      a.push(els[i]);
  }
  return a;
}

/**
 * Adds class invalid field to empty form elements that have class required
 *
 * @param formElem
 * @returns {boolean}
 */
function verifyForm(formElem) {
  var validForm = true;
  var fields = getElementsByClassName('required', formElem);
  var field;
  for (var i in fields) {
    if(!fields.hasOwnProperty(i))
      continue;
    field = fields[i];
    if(
        !(field.tagName.toLowerCase() == 'textarea') //not textarea
        && !(                                        // and not text input
             (field.tagName.toLowerCase() == 'input')
             && (field.getAttribute('type') == 'text')
            )
      )
      continue;

    var invalidField = field.parentNode.parentNode.getElementsByTagName('label')[0];
    if (field.value == '') {
      validForm = false;
      addClass(invalidField, 'invalidField');
    } else {
      removeClass(invalidField, 'invalidField');
    }
  }

  if (!validForm) {
    alert(t('required_field_error'));
  }
  return validForm;
}

window.Event = {
  add: function (obj, type, fn, useCapture) {
    var fname = fn;
    if(!useCapture)
      useCapture = false;

    if(typeof(fn) == "function")
      fname = fn.name;

    if (obj.attachEvent) {
      obj['e' + type + fname] = fn;
      obj[type + fname] = function () {
        obj['e' + type + fname](window.event);
      };
      obj.attachEvent('on' + type, obj[type + fname]);
    } else if (obj.addEventListener) {
      obj.addEventListener(type, fn, useCapture);
    } else {
      obj['on' + type] = fn;
    }
  },
  remove: function (obj, type, fn) {
    var fname = fn;

    if(typeof(fn) == "function")
      fname = fn.name;

    if (obj.detachEvent) {
      obj.detachEvent('on' + type, obj[type + fname]);
      obj[type + fname] = null;
    } else if (obj.addEventListener) {
      obj.removeEventListener(type, fn, false);
    } else {
      obj['on' + type] = null;
    }
  },
  getTarget : function (e) {
    var targ;
    if (e.target) { // W3C
      targ = e.target;
    } else if (e.srcElement) { // IE6-8
      targ = e.srcElement;
    }
    if (targ.nodeType == 3) { // Safari
      targ = targ.parentNode;
    }
    return targ;
  },
  stopPropagation: function (e) {
    if(e.stopPropagation()) {
      e.stopPropagation();
    }

  },

  isShiftDown : function (ev) {
    var isShift;
    if (window.event) {
      isShift = !!window.event.shiftKey; // typecast to boolean
    } else {
      isShift = ev.shiftKey;
    }
    // if ( isShift ) {
    //   switch (key) {
    //     case 16: // ignore shift key
    //       break;
    //     default:
    //       // alert(key);
    //       // do stuff here?
    //       break;
    //   }
    // }
    return !!isShift;
  }
};


if(typeof(console) == 'undefined'){
  console = {
    log: function(text){
      //null function to avoid errors when console object is not available
    }
  }
}

String.prototype.replaceAll = function(str1, str2, ignore){
  return this.replace(new RegExp(str1.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g,"\\$&"),(ignore?"gi":"g")),(typeof(str2)=="string")?str2.replace(/\$/g,"$$$$"):str2);
};

function htmlSpecialChars(unsafe){

  return unsafe.
    replace(/&/g, "&amp;").
    replace(/</g, "&lt;").
    replace(/>/g, "&gt;").
    replace(/"/g, "&quot;").
    replace(/'/g, "&#039;")
}


String.prototype.htmlSpecialChars = function (){
  return this.
    replace(/&/g, "&amp;").
    replace(/</g, "&lt;").
    replace(/>/g, "&gt;").
    replace(/"/g, "&quot;").
    replace(/'/g, "&#039;")
};

window.mobilecheck = function() {
  var check = false;
  (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
  return check;
};

window.mobileAndTabletcheck = function() {
  var check = false;
  (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
  return check;
};

function getOffsetOf(el) {
  var rect = el.getBoundingClientRect(),
      scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
      scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  return { top: rect.top + scrollTop, left: rect.left + scrollLeft }
}

/*
if(!NodeList.prototype.forEach) {
  NodeList.prototype.forEach = function(callback) {
    var i;
    for(i=0;i<this.length; ++i) {
      callback(this.item(i));
    }
  }
}
*/

function clearChildren(elem) {
  while (elem.firstChild) {
    elem.removeChild(elem.firstChild);
  }
}

/**
 *
 * @param {string} url
 * @param {string} [target=_blank]
 * @param {int} [w]
 * @param {int} [h]
 * @param {Object} [features]
 */
function popupCenter(url, target, w, h, features) {
  if (!target)
    target = '_blank';
  if (!features)
    features = {width:800,height:700,resizable:'yes',scrollbars:'yes'};
  // Fixes dual-screen position                         Most browsers      Firefox
  var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
  var dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;

  var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
  var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
  width = screen.width;
  height = screen.height;

  if (w)
    features.width = w;
  if (h)
    features.width = h;
  var systemZoom = width / window.screen.availWidth;
  var left = (width - features.width) / 2.0 / systemZoom + dualScreenLeft;
  var top = (height - features.height) / 2.0 / systemZoom + dualScreenTop;
  // Puts focus on the newWindow


  features.width /= systemZoom;
  features.height /= systemZoom;
  features.top = top;
  features.left = left;
  let farr = [];
  for (let i in features) {
    if (!features.hasOwnProperty(i))
      continue;
    farr.push(i + '=' +features[i]);
  }

  features = farr.join(',');
  var newWindow = window.open(url, target, features);
  if (window.focus) newWindow.focus();
  return newWindow;
}

String.prototype.zeroPad = function(padToLength){
  var result = '';
  for (var i = 0; i < (padToLength - this.length); i++) {
    result += '0';
  }
  return (result + this);
};

function formatDate(date) {
  var day = (''+date.getDate()).zeroPad(2);
  var month = (''+(date.getMonth()+1)).zeroPad(2);
  var year = date.getFullYear();

  return day + '.' + month + '.' + year;
}

