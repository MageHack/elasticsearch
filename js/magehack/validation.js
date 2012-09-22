// json_parse.js
// 2011-03-06
var json_parse=function(){"use strict";var a,b,c={'"':'"',"\\":"\\","/":"/",b:"\b",f:"\f",n:"\n",r:"\r",t:"\t"},d,e=function(b){throw{name:"SyntaxError",message:b,at:a,text:d}},f=function(c){if(c&&c!==b){e("Expected '"+c+"' instead of '"+b+"'")}b=d.charAt(a);a+=1;return b},g=function(){var a,c="";if(b==="-"){c="-";f("-")}while(b>="0"&&b<="9"){c+=b;f()}if(b==="."){c+=".";while(f()&&b>="0"&&b<="9"){c+=b}}if(b==="e"||b==="E"){c+=b;f();if(b==="-"||b==="+"){c+=b;f()}while(b>="0"&&b<="9"){c+=b;f()}}a=+c;if(!isFinite(a)){e("Bad number")}else{return a}},h=function(){var a,d,g="",h;if(b==='"'){while(f()){if(b==='"'){f();return g}else if(b==="\\"){f();if(b==="u"){h=0;for(d=0;d<4;d+=1){a=parseInt(f(),16);if(!isFinite(a)){break}h=h*16+a}g+=String.fromCharCode(h)}else if(typeof c[b]==="string"){g+=c[b]}else{break}}else{g+=b}}}e("Bad string")},i=function(){while(b&&b<=" "){f()}},j=function(){switch(b){case"t":f("t");f("r");f("u");f("e");return true;case"f":f("f");f("a");f("l");f("s");f("e");return false;case"n":f("n");f("u");f("l");f("l");return null}e("Unexpected '"+b+"'")},k,l=function(){var a=[];if(b==="["){f("[");i();if(b==="]"){f("]");return a}while(b){a.push(k());i();if(b==="]"){f("]");return a}f(",");i()}}e("Bad array")},m=function(){var a,c={};if(b==="{"){f("{");i();if(b==="}"){f("}");return c}while(b){a=h();i();f(":");if(Object.hasOwnProperty.call(c,a)){e('Duplicate key "'+a+'"')}c[a]=k();i();if(b==="}"){f("}");return c}f(",");i()}}e("Bad object")};k=function(){i();switch(b){case"{":return m();case"[":return l();case'"':return h();case"-":return g();default:return b>="0"&&b<="9"?g():j()}};return function(c,f){var g;d=c;a=0;b=" ";g=k();i();if(b){e("Syntax error")}return typeof f==="function"?function h(a,b){var c,d,e=a[b];if(e&&typeof e==="object"){for(c in e){if(Object.prototype.hasOwnProperty.call(e,c)){d=h(e,c);if(d!==undefined){e[c]=d}else{delete e[c]}}}}return f.call(a,b,e)}({"":g},""):g}}()
								
Validation.add('validate-json', 'Invalid JSON string', function(v) {
	if(v.length){
		try{
			var json = json_parse(v);
		}catch(e){
			return false;
		}
	}
    return true;
});

Validation.add('validate-cron', 'Invalid CRON syntax', function(v) {
	if(v.length){
		/**
		* @author Jordi Salvat i Alabart - Modified by GPMD
		* @stackoverfow 235504
		*/
		return v.match(/^\s*($|#|\w+\s*=|(\*(\/\d+)?|([0-5]?\d)(-([0-5]?\d)(\/\d+)?)?(,([0-5]?\d)(-([0-5]?\d)(\/\d+)?)?)*)\s+(\*(\/\d+)?|([01]?\d|2[0-3])(-([01]?\d|2[0-3])(\/\d+)?)?(,([01]?\d|2[0-3])(-([01]?\d|2[0-3])(\/\d+)?)?)*)\s+(\*(\/\d+)?|(0?[1-9]|[12]\d|3[01])(-(0?[1-9]|[12]\d|3[01])(\/\d+)?)?(,(0?[1-9]|[12]\d|3[01])(-(0?[1-9]|[12]\d|3[01])(\/\d+)?)?)*)\s+(\*(\/\d+)?|([1-9]|1[012])(-([1-9]|1[012])(\/\d+)?)?(,([1-9]|1[012])(-([1-9]|1[012])(\/\d+)?)?)*|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s+(\*(\/\d+)?|([0-7])(-([0-7])(\/\d+)?)?(,([0-7])(-([0-7])(\/\d+)?)?)*|mon|tue|wed|thu|fri|sat|sun)|(@reboot|@yearly|@annually|@monthly|@weekly|@daily|@midnight|@hourly))/);
	}
    return true;
});