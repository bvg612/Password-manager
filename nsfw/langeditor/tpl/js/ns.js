
window.ns = {
  isReady : false,
  readyHandlers : [],
  cookieCache : [],
  htmlSpecialChatsArray : [
    {search: "&", replace:"&amp;"},
    {search: "<", replace:"&lt;"},
    {search: ">", replace:"&gt;"},
    {search: "\"", replace:"&quot;"},
    {search: "'", replace:"&#039;"}
    ]

};

window.ns.addReadyHandler = function(handler){
  if(window.ns.isReady){
    handler();
  }else{
    var len = window.ns.readyHandlers.length
    window.ns.readyHandlers.push(handler);
  }
};

window.ns.reload = function(){
  window.location.reload();
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

window.ns.setCookie = function(cookieName, value, expireDays, path){
  var exdate = new Date();
  exdate.setTime(exdate.getTime() + expireDays*24*3600*1000);
  if(!path){
    path="/"
  }
  path="; path="+path;
  var cookieValue = escape(value) + ((expireDays == null) ? "" : "; expires="+exdate.toUTCString())+path;
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
    readyHandler();
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
 * @param obj
 * @param args Optional
 * @param appendArgs
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


function url(params) {
  this.url = window.location.href;
  if (typeof params != 'undefined') {
    for (i in params) {
      this.url += '&' + i + '=' + params[i];
    }
  }
  this.addParam = function(key, val) {
    var re = new RegExp('([?&])' + key + '=[a-z0-9-]+');
    if (this.url.match(re)) {
      this.url = this.url.replace(re, '$1' + key + '=' + val);
    } else {
      this.url += '&' + key + '=' + val;
    }
  }
  this.go = function() {
    window.location.href = this.url;
  }
}

function findParentNodeByTagName(element, parentName){
  var tag = element;
  while(tag.tagName.toUpperCase() != parentName){
    if (typeof(tag.parentNode) == 'undefined')
      return false;
    if(tag.tagName == 'BODY')
      return false;
    tag = tag.parentNode;
  }
  return tag;
}

function findChildNodeByTagName(element, childName){
  var children = element.childNodes;
  for (i in children){
    if (children[i].nodeType != 1) //ELEMENT_NODE
      continue;
    if(children[i].tagName == childName)
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

function uqConfirm(text) {
  text = text.replace(/&amp;/, '&');
  text = text.replace(/&quot;/, '"');
  return confirm(text);
}

function splitClasses(obj){
  var s = obj.className;
  re = new RegExp('\\s+');
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

  for (i in classes) {
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

  for (i in classes) {
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

function verifyForm(formElem) {
  var validForm = true;
  var fields = getElementsByClassName('required', formElem);
  var field;
  for (i in fields) {
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

var Event = {
  add: function (obj, type, fn) {
    if (obj.attachEvent) {
      obj['e' + type + fn] = fn;
      obj[type + fn] = function () {
        obj['e' + type + fn](window.event);
      }
      obj.attachEvent('on' + type, obj[type + fn]);
    } else if (obj.addEventListener) {
      obj.addEventListener(type, fn, false);
    } else {
      obj['on' + type] = fn;
    }
  },
  remove: function (obj, type, fn) {
    if (obj.detachEvent) {
      obj.detachEvent('on' + type, obj[type + fn]);
      obj[type + fn] = null;
    } else if (obj.addEventListener) {
      obj.removeEventListener(type, fn, false);
    } else {
      obj['on' + type] = null;
    }
  }
}


if(typeof(console) == 'undefined'){
  console = {
    log: function(text){
      //null function to avoid errors when console object is not available
    }
  }
}

String.prototype.replaceAll = function(str1, str2, ignore){
  return this.replace(new RegExp(str1.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g,"\\$&"),(ignore?"gi":"g")),(typeof(str2)=="string")?str2.replace(/\$/g,"$$$$"):str2);
}

function htmlSpecialChars(unsafe){

  return unsafe.
    replace(/&/g, "&amp;").
    replace(/</g, "&lt;").
    replace(/>/g, "&gt;").
    replace(/"/g, "&quot;").
    replace(/'/g, "&#039;")
}

function addEmoticons(str, emoticons){
  var len = emoticons.length;
  for(var i=0;i<len; ++i){
    str = str.replace(
      new RegExp(emoticons[i].text, "g"),
      emoticons[i].icon
    );
  }
  return str;
}

String.prototype.htmlSpecialChars = function (){
  return this.
    replace(/&/g, "&amp;").
    replace(/</g, "&lt;").
    replace(/>/g, "&gt;").
    replace(/"/g, "&quot;").
    replace(/'/g, "&#039;")
};
String.prototype.addEmoticons = function (emoticons){
  var len = emoticons.length;
  var str = this;
  for(var i=0;i<len; ++i){
    str = str.replace(
      new RegExp(emoticons[i].text, "g"),
      "<img src=\"/img"+emoticons[i].icon+"\" />"
    );
  }
  return str;
};
