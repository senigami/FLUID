/*
	string	typeOf(item) 			// like typeof() but returns 'array' correctly. 				ex: typeOf([]) --> returns "array"
	string	String.trim() 			// removes white space from the string. 						ex: " x ".trim() --> returns "x"
	bool	isEmpty(string)			// checks to see if the string contains data					ex: isEmpty(' ') --> returns true
	void	Log(mixedData)			// sends the data to the console if debug mode is activated.	ex: debug=true; Log("debuging"); Log({a:'b'});
	string	$.toJSON(theObject)		// converts a json object into text markup. 	ex: $.toJSON({a:true}) --> returns "{a: true}"
	object	$.evalJSON(theString)	// converts json markup into a json object. 	ex: $.evalJSON("{a: true}") --> returns object where object.a is true
	date	date.format(string)		// Simulates PHP's date function				ex: myDate.format('M jS, Y') --> May 11th, 2006
*/
/****** VARIABLES *****/
// global variables
var debug = true;

/***** FUNCTIONS *****/
function getGuid(format){
	if(format == null) format ='xxxxxxxx-xxxx-xxxx-yxxx-xxxxxxxxxxxx';
  /*
		var S4 = function() {
       return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
    };
    return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
	*/
		// or more compact
		// x = 0 1 2 3 4 5 6 7 8 9 a b c d e f
		// y = 8 9 a b
		return format.replace(/[xy]/g, function(c) {
			var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
			return v.toString(16);
		});

}
function typeOf(o){	// true object type, not just array
	var type = typeof(o);
	if( type == 'object' )
    {
        var criterion = o.constructor.toString().match(/array/i);
        return (criterion != null)?'array':'object';
    }
    return type;
}
Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};
String.prototype.trim = function(){
	return this.replace(/^\s+|\s+$/g,"");
}
Number.prototype.format = function(format) {
/**
 * Formats the number according to the 'format' string; adherses to the american number standard where a comma is inserted after every 3 digits.
 *  note: there should be only 1 contiguous number in the format, where a number consists of digits, period, and commas
 *        any other characters can be wrapped around this number, including '$', '%', or text
 *        examples (123456.789):
 *          '0' - (123456) show only digits, no precision
 *          '0.00' - (123456.78) show only digits, 2 precision
 *          '0.0000' - (123456.7890) show only digits, 4 precision
 *          '0,000' - (123,456) show comma and digits, no precision
 *          '0,000.00' - (123,456.78) show comma and digits, 2 precision
 *          '0,0.00' - (123,456.78) shortcut method, show comma and digits, 2 precision
 *
 * @method format
 * @param format {string} the way you would like to format this text
 * @return {string} the formatted number
 * @public
 */
	if ('string' != typeof(format)) {return '';} // sanity check

	var hasComma = -1 != format.indexOf(','),
	psplit = format.replace(/[^-\d\.]/g, '').split('.'),
	that = this,
	part_whole, part_decimal, temp;

	// compute precision
	if (1 < psplit.length) {
		// fix number precision
		that = that.toFixed(psplit[1].length);
	}
	// error: too many periods
	else if (2 < psplit.length) {
		throw('NumberFormatException: invalid format, formats should have no more than 1 period: ' + format);
	}
	// remove precision
	else {
		that = that.toFixed(0);
	}
	var formatted_num = that + '';

	// format has comma, then compute commas
	if (hasComma) {
		// remove precision for computation
		psplit = formatted_num.split('.');
		part_whole = psplit[0];
		part_decimal = psplit[1] || '';

		do {
			temp = part_whole;
			part_whole = part_whole.replace(/^(-?\d+)(\d{3})+/g, '$1,$2');
		}
		while (temp != part_whole);

				// add the precision back in
		formatted_num = part_whole + (part_decimal ? '.' + part_decimal : part_decimal);
	}

	// replace the number portion of the format with formatted_num
	return formatted_num
};
function isEmpty(inputStr){
	try{
		switch(typeof(inputStr))
		{
			case 'number':
				return (inputStr==0);

			case 'string':
				return !(inputStr && inputStr.trim().length );

			case 'undefined':
				return true;

			case 'object':
			{
				var empty = true;
				$.each(inputStr,function(i,o){empty = false;});
				return empty;
			}

			case 'boolean':
			default:
				return false;
		}
	}catch(err)
	{
		return true;
	}
}
function Log(data){
	if( debug && (typeof(console)=='object') )
		console.log(data);
}
function $E(id){
	// returns the first element found in the selector if it exists
	// ex: $E('#click_btn') or $E('a')
	if( $(id)[0] )
		return $(id)[0];
	return 0;
}
function $F(id, val){
	var valIsSet = (typeof(val) != 'undefined');
	//returns the form field value if it exists and sets the value if passed
	// ex: $F('#checkbox', true) or $F('input_name', 'joe') or $F('input_name') <- returns 'joe'
	var e = null;
	if( typeof(id) == 'object' )
	{
		e = id[0];
		id = id.attr('id');
	}
	else
	{
		if( id.substr(0,1) == '#' ) e = $E(id);
		else e = $("[name='"+id+"']")[0];
	}
	if(!e) return;
	var flag = ((typeof(val)=='number')||(typeof(val)=='boolean'))?true:false;
	switch(e.type)
	{
		case 'textarea': // <textarea name="n" rows="4" cols="40"></textarea>
		case 'submit': // <input type="submit" name="n">
		case 'reset': // <input type="reset">
		case 'file': // <input type="file" name="n" size="16">
		case 'hidden': // <input type="hidden" name="n" value="v">
		case 'password': // <input type="password" name="n" size="24">
		case 'text': // <input type="text" name="n" size="24">
			if( valIsSet ) e.value = val;
			return e.value;

		case 'select-multiple': // <select name="n" size="4" multiple></select>
			if( !valIsSet ) {
				val = [];
				for( i=0; i < e.length; i++ )
					if( e[i].selected == true )
						val.push(e[i].value);
				return val;
			}
			if( valIsSet && typeOf(val)=='array' ) { // do multi, otherwise do single
				for( i=0; i < e.length; i++ )
					e[i].selected = val.indexOf(e[i].value)>-1;
				return "";
			}

		case 'select-one':
			var index = e.selectedIndex;
			if( index < 0 )
			{
				for( i=0; i < e.length; i++ )
					if( e[i].value == val ){ e[i].selected = true; return val; }
				return "";
			}
			if( valIsSet && (e[e.selectedIndex].value != val) )
				for( i=0; i < e.length; i++ )
					if( e[i].value == val ){ e[i].selected = true; break; }
			return e[e.selectedIndex].value;

		case 'radio': // <input type="radio" name="n" value="v">
			if( id.substr(0,1) == '#' ) // single item or group
			{
				if(flag) e.checked = val;
				else if(valIsSet) e.value = val;
				return e.checked;
			}
			else
			{
				var group = $("input[name='"+id+"']");
				if(valIsSet)
				{
					for(i=0;  i < group.length;  i++)
						if(group[i].value == val)
							group[i].checked = true;
				}
				for(i=0;  i < group.length;  i++)
					if(group[i].checked)
						return group[i].value;
				return;
			}

		case 'checkbox': // <input type="checkbox" name="n" value="v">
			if( flag ) e.checked = val;
			else if( valIsSet ) e.value = val;
			return e.checked;

		default:
		case 'button': // <button name="n" type="button"></button>
		case 'image': // <input type="image" src="url" alt="">
			break;
	}
}
var valid = {
	email: function(val){
		return ( /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(val) );
	},
	date: function(val){
		return ( /\d{4}\/\d{2}\/\d{2}/.test(val) ); // yyyy/mm/dd
	},
	phone: function(val){
		//var phone = /^(\+[1-9][0-9]*(\([0-9]*\)|-[0-9]*-))?[0]?[1-9][0-9\- ]*$/; // International
		return (/^[01]?[- .]?\(?[2-9]\d{2}\)?[- .]?\d{3}[- .]?\d{4}$/.test(val));
	},
	password: function(val){
		// 6 charactors min
		// 16 char max
		// At least 1 number
		// At least 1 alpha
		return ( /^.*(?=.{6,})(?=.*\d)(?=.*[a-zA-Z]).*$/.test(val) && (val.length <= 16) );
	},
	text: function(val){
		val = val.trim();
		return val.replace(/[^\w .@-]/g,'');
	},
	alpha: function(val){
		// don't have any non alphanums
		return !/\W/.test(val);
	},
	number: function(val,floating){
		if(floating == null) floating = false;
		if( floating )
			return /^[-+]?[0-9]*\.?[0-9]+$/.test(val);

		// don't have any non digits
		return !/\D/.test(val);
	},
	time: function(val){
		return /\d{1,2}:\d{2} [ap]m/.test(val);
	}
}
function compareText(a,b){
	// if a is not less than b (-1) and a is not greater (1) then it is equal (0)
	return ( (a<b)? -1 : (a>b?1:0) );
}
jQuery.fn.fade = function(timeToFade){
	return this.stop().fadeTo(0,1).fadeTo(timeToFade,0)
};
function sortObject(o) {
    var sorted = {},
    key, a = [];

    for (key in o) {
        if (o.hasOwnProperty(key)) {
                a.push(key);
        }
    }

    a.sort();

    for (key = 0; key < a.length; key++) {
        sorted[a[key]] = o[a[key]];
    }
    return sorted;
}
/****************************************************************************************************/

/* jQuery JSON Plugin
	http://code.google.com/p/jquery-json/

	theString = $.toJSON(theObject);
	theObject = $.evalJSON(theString);

	var thing = {plugin: 'jquery-json', version: 2.2};
	var encoded = $.toJSON(thing);              //'{"plugin":"jquery-json","version":2.2}'
	var name = $.evalJSON(encoded).plugin;      //"jquery-json"
	var version = $.evalJSON(encoded).version;  // 2.2
 */

(function($){$.toJSON=function(o)
{if(typeof(JSON)=='object'&&JSON.stringify)
return JSON.stringify(o);var type=typeof(o);if(o===null)
return"null";if(type=="undefined")
return undefined;if(type=="number"||type=="boolean")
return o+"";if(type=="string")
return $.quoteString(o);if(type=='object')
{if(typeof o.toJSON=="function")
return $.toJSON(o.toJSON());if(o.constructor===Date)
{var month=o.getUTCMonth()+1;if(month<10)month='0'+month;var day=o.getUTCDate();if(day<10)day='0'+day;var year=o.getUTCFullYear();var hours=o.getUTCHours();if(hours<10)hours='0'+hours;var minutes=o.getUTCMinutes();if(minutes<10)minutes='0'+minutes;var seconds=o.getUTCSeconds();if(seconds<10)seconds='0'+seconds;var milli=o.getUTCMilliseconds();if(milli<100)milli='0'+milli;if(milli<10)milli='0'+milli;return'"'+year+'-'+month+'-'+day+'T'+
hours+':'+minutes+':'+seconds+'.'+milli+'Z"';}
if(o.constructor===Array)
{var ret=[];for(var i=0;i<o.length;i++)
ret.push($.toJSON(o[i])||"null");return"["+ret.join(",")+"]";}
var pairs=[];for(var k in o){var name;var type=typeof k;if(type=="number")
name='"'+k+'"';else if(type=="string")
name=$.quoteString(k);else
continue;if(typeof o[k]=="function")
continue;var val=$.toJSON(o[k]);pairs.push(name+":"+val);}
return"{"+pairs.join(", ")+"}";}};$.evalJSON=function(src)
{if(typeof(JSON)=='object'&&JSON.parse)
return JSON.parse(src);return eval("("+src+")");};$.secureEvalJSON=function(src)
{if(typeof(JSON)=='object'&&JSON.parse)
return JSON.parse(src);var filtered=src;filtered=filtered.replace(/\\["\\\/bfnrtu]/g,'@');filtered=filtered.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']');filtered=filtered.replace(/(?:^|:|,)(?:\s*\[)+/g,'');if(/^[\],:{}\s]*$/.test(filtered))
return eval("("+src+")");else
throw new SyntaxError("Error parsing JSON, source is not valid.");};$.quoteString=function(string)
{if(string.match(_escapeable))
{return'"'+string.replace(_escapeable,function(a)
{var c=_meta[a];if(typeof c==='string')return c;c=a.charCodeAt();return'\\u00'+Math.floor(c/16).toString(16)+(c%16).toString(16);})+'"';}
return'"'+string+'"';};var _escapeable=/["\\\x00-\x1f\x7f-\x9f]/g;var _meta={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'};})(jQuery);
/****************************************************************************************************/

/*	Simulates PHP's date function
	http://jacwright.com/projects/javascript/date_format

	var myDate = new Date();
	myDate.format('M jS, Y'); // May 11th, 2006
*/
Date.prototype.format = function(format) {
        var returnStr = '';
        var replace = Date.replaceChars;
        for (var i = 0; i < format.length; i++) {               var curChar = format.charAt(i);                 if (i - 1 >= 0 && format.charAt(i - 1) == "\\") {
                        returnStr += curChar;
                }
                else if (replace[curChar]) {
                        returnStr += replace[curChar].call(this);
                } else if (curChar != "\\"){
                        returnStr += curChar;
                }
        }
        return returnStr;
};

Date.replaceChars = {
        shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        longMonths: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        shortDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        longDays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],

        // Day
        d: function() { return (this.getDate() < 10 ? '0' : '') + this.getDate(); },
        D: function() { return Date.replaceChars.shortDays[this.getDay()]; },
        j: function() { return this.getDate(); },
        l: function() { return Date.replaceChars.longDays[this.getDay()]; },
        N: function() { return this.getDay() + 1; },
        S: function() { return (this.getDate() % 10 == 1 && this.getDate() != 11 ? 'st' : (this.getDate() % 10 == 2 && this.getDate() != 12 ? 'nd' : (this.getDate() % 10 == 3 && this.getDate() != 13 ? 'rd' : 'th'))); },
        w: function() { return this.getDay(); },
        z: function() { var d = new Date(this.getFullYear(),0,1); return Math.ceil((this - d) / 86400000); }, // Fixed now
        // Week
        W: function() { var d = new Date(this.getFullYear(), 0, 1); return Math.ceil((((this - d) / 86400000) + d.getDay() + 1) / 7); }, // Fixed now
        // Month
        F: function() { return Date.replaceChars.longMonths[this.getMonth()]; },
        m: function() { return (this.getMonth() < 9 ? '0' : '') + (this.getMonth() + 1); },
        M: function() { return Date.replaceChars.shortMonths[this.getMonth()]; },
        n: function() { return this.getMonth() + 1; },
        t: function() { var d = new Date(); return new Date(d.getFullYear(), d.getMonth(), 0).getDate() }, // Fixed now, gets #days of date
        // Year
        L: function() { var year = this.getFullYear(); return (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)); },       // Fixed now
        o: function() { var d  = new Date(this.valueOf());  d.setDate(d.getDate() - ((this.getDay() + 6) % 7) + 3); return d.getFullYear();}, //Fixed now
        Y: function() { return this.getFullYear(); },
        y: function() { return ('' + this.getFullYear()).substr(2); },
        // Time
        a: function() { return this.getHours() < 12 ? 'am' : 'pm'; },
        A: function() { return this.getHours() < 12 ? 'AM' : 'PM'; },
        B: function() { return Math.floor((((this.getUTCHours() + 1) % 24) + this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24); }, // Fixed now
        g: function() { return this.getHours() % 12 || 12; },
        G: function() { return this.getHours(); },
        h: function() { return ((this.getHours() % 12 || 12) < 10 ? '0' : '') + (this.getHours() % 12 || 12); },
        H: function() { return (this.getHours() < 10 ? '0' : '') + this.getHours(); },
        i: function() { return (this.getMinutes() < 10 ? '0' : '') + this.getMinutes(); },
        s: function() { return (this.getSeconds() < 10 ? '0' : '') + this.getSeconds(); },
        u: function() { var m = this.getMilliseconds(); return (m < 10 ? '00' : (m < 100 ?
'0' : '')) + m; },
        // Timezone
        e: function() { return "Not Yet Supported"; },
        I: function() { return "Not Yet Supported"; },
        O: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + '00'; },
        P: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + ':00'; }, // Fixed now
        T: function() { var m = this.getMonth(); this.setMonth(0); var result = this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, '$1'); this.setMonth(m); return result;},
        Z: function() { return -this.getTimezoneOffset() * 60; },
        // Full Date/Time
        c: function() { return this.format("Y-m-d\\TH:i:sP"); }, // Fixed now
        r: function() { return this.toString(); },
        U: function() { return this.getTime() / 1000; }
};
/****************************************************************************************************/

/* jquery cookie
# jquery.cookie [![Build Status](https://travis-ci.org/carhartl/jquery-cookie.png?branch=master)](https://travis-ci.org/carhartl/jquery-cookie)
	A simple, lightweight jQuery plugin for reading, writing and deleting cookies.

	Create session cookie:
		$.cookie('the_cookie', 'the_value');

	Create expiring cookie, 7 days from then:
		$.cookie('the_cookie', 'the_value', { expires: 7 });

	Create expiring cookie, valid across entire site:
		$.cookie('the_cookie', 'the_value', { expires: 7, path: '/' });

	Read cookie:
		$.cookie('the_cookie'); // => "the_value"
		$.cookie('not_existing'); // => null

	Read all available cookies:
		$.cookie(); // => { "the_cookie": "the_value", "...remaining": "cookies" }

	Delete cookie:
		// Returns true when cookie was found, false when no cookie was found...
		$.removeCookie('the_cookie');

		// Same path as when the cookie was written...
		$.removeCookie('the_cookie', { path: '/' });

	*Note: when deleting a cookie, you must pass the exact same path, domain and secure options that were used to set the cookie, unless you're relying on the default options that is.*

	## Configuration
	### raw
		By default the cookie value is encoded/decoded when writing/reading, using `encodeURIComponent`/`decodeURIComponent`. Bypass this by setting raw to true:
			$.cookie.raw = true;

	### json
		Turn on automatic storage of JSON objects passed as the cookie value. Assumes `JSON.stringify` and `JSON.parse`:
			$.cookie.json = true;

	## Cookie Options
	### expires
		Cookie attributes can be set globally by setting properties of the `$.cookie.defaults` object or individually for each call to `$.cookie()` by passing a plain object to the options argument. Per-call options override the default options.
			expires: 365
		Define lifetime of the cookie. Value can be a `Number` which will be interpreted as days from time of creation or a `Date` object. If omitted, the cookie becomes a session cookie.

	### path
			path: '/'
		Define the path where the cookie is valid. *By default the path of the cookie is the path of the page where the cookie was created (standard browser behavior).* If you want to make it available for instance across the entire domain use `path: '/'`. Default: path of page where the cookie was created.

	**Note regarding Internet Explorer:**
		> Due to an obscure bug in the underlying WinINET InternetGetCookie implementation, IE’s document.cookie will not return a cookie if it was set with a path attribute containing a filename.
		(From [Internet Explorer Cookie Internals (FAQ)](http://blogs.msdn.com/b/ieinternals/archive/2009/08/20/wininet-ie-cookie-internals-faq.aspx))
		This means one cannot set a path using `path: window.location.pathname` in case such pathname contains a filename like so: `/check.html` (or at least, such cookie cannot be read correctly).

	### domain
			domain: 'example.com'
		Define the domain where the cookie is valid. Default: domain of page where the cookie was created.

	### secure
			secure: true
		If true, the cookie transmission requires a secure protocol (https). Default: `false`.

 */
(function ($, document, undefined) {

	var pluses = /\+/g;

	function raw(s) {
		return s;
	}

	function decoded(s) {
		return unRfc2068(decodeURIComponent(s.replace(pluses, ' ')));
	}

	function unRfc2068(value) {
		if (value.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape
			value = value.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}
		return value;
	}

	function fromJSON(value) {
		return config.json ? JSON.parse(value) : value;
	}

	var config = $.cookie = function (key, value, options) {

		// write
		if (value !== undefined) {
			options = $.extend({}, config.defaults, options);

			if (value === null) {
				options.expires = -1;
			}

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setDate(t.getDate() + days);
			}

			value = config.json ? JSON.stringify(value) : String(value);

			return (document.cookie = [
				encodeURIComponent(key), '=', config.raw ? value : encodeURIComponent(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// read
		var decode = config.raw ? raw : decoded;
		var cookies = document.cookie.split('; ');
		var result = key ? null : {};
		for (var i = 0, l = cookies.length; i < l; i++) {
			var parts = cookies[i].split('=');
			var name = decode(parts.shift());
			var cookie = decode(parts.join('='));

			if (key && key === name) {
				result = fromJSON(cookie);
				break;
			}

			if (!key) {
				result[name] = fromJSON(cookie);
			}
		}

		return result;
	};

	config.defaults = {};

	$.removeCookie = function (key, options) {
		if ($.cookie(key) !== null) {
			$.cookie(key, null, options);
			return true;
		}
		return false;
	};

})(jQuery, document);
/****************************************************************************************************/

/**
 * jQuery.ScrollTo - Easy element scrolling using jQuery.
 * Copyright (c) 2007-2009 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 5/25/2009
 * @author Ariel Flesler
 * @version 1.4.2
 *
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html

	$('div.pane').scrollTo(...);//all divs w/class pane
 */
/*;(function(d){var k=d.scrollTo=function(a,i,e){d(window).scrollTo(a,i,e)};k.defaults={axis:'xy',duration:parseFloat(d.fn.jquery)>=1.3?0:1};k.window=function(a){return d(window)._scrollable()};d.fn._scrollable=function(){return this.map(function(){var a=this,i=!a.nodeName||d.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!i)return a;var e=(a.contentWindow||a).document||a.ownerDocument||a;return d.browser.safari||e.compatMode=='BackCompat'?e.body:e.documentElement})};d.fn.scrollTo=function(n,j,b){if(typeof j=='object'){b=j;j=0}if(typeof b=='function')b={onAfter:b};if(n=='max')n=9e9;b=d.extend({},k.defaults,b);j=j||b.speed||b.duration;b.queue=b.queue&&b.axis.length>1;if(b.queue)j/=2;b.offset=p(b.offset);b.over=p(b.over);return this._scrollable().each(function(){var q=this,r=d(q),f=n,s,g={},u=r.is('html,body');switch(typeof f){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(f)){f=p(f);break}f=d(f,this);case'object':if(f.is||f.style)s=(f=d(f)).offset()}d.each(b.axis.split(''),function(a,i){var e=i=='x'?'Left':'Top',h=e.toLowerCase(),c='scroll'+e,l=q[c],m=k.max(q,i);if(s){g[c]=s[h]+(u?0:l-r.offset()[h]);if(b.margin){g[c]-=parseInt(f.css('margin'+e))||0;g[c]-=parseInt(f.css('border'+e+'Width'))||0}g[c]+=b.offset[h]||0;if(b.over[h])g[c]+=f[i=='x'?'width':'height']()*b.over[h]}else{var o=f[h];g[c]=o.slice&&o.slice(-1)=='%'?parseFloat(o)/100*m:o}if(/^\d+$/.test(g[c]))g[c]=g[c]<=0?0:Math.min(g[c],m);if(!a&&b.queue){if(l!=g[c])t(b.onAfterFirst);delete g[c]}});t(b.onAfter);function t(a){r.animate(g,j,b.easing,a&&function(){a.call(this,n,b)})}}).end()};k.max=function(a,i){var e=i=='x'?'Width':'Height',h='scroll'+e;if(!d(a).is('html,body'))return a[h]-d(a)[e.toLowerCase()]();var c='client'+e,l=a.ownerDocument.documentElement,m=a.ownerDocument.body;return Math.max(l[h],m[h])-Math.min(l[c],m[c])};function p(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);
*/
/****************************************************************************************************/
/**
* hoverIntent r5 // 2007.03.27 // jQuery 1.1.2+
* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
*
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Brian Cherne <brian@cherne.net>
*/
(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY;};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev]);}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev]);};var handleHover=function(e){var p=(e.type=="mouseover"?e.fromElement:e.toElement)||e.relatedTarget;while(p&&p!=this){try{p=p.parentNode;}catch(e){p=this;}}if(p==this){return false;}var ev=jQuery.extend({},e);var ob=this;if(ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);}if(e.type=="mouseover"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if(ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}}else{$(ob).unbind("mousemove",track);if(ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob);},cfg.timeout);}}};return this.mouseover(handleHover).mouseout(handleHover);};})(jQuery);

/****************************************************************************************************/

/**
sprintf() for JavaScript 0.7-beta1
http://www.diveintojavascript.com/projects/javascript-sprintf

Copyright (c) Alexandru Marasteanu <alexaholic [at) gmail (dot] com>
All rights reserved.

string sprintf(string format , [mixed arg1 [, mixed arg2 [ ,...]]]);
The placeholders in the format string are marked by "%" and are followed by one or more of these elements, in this order:

    An optional "+" sign that forces to preceed the result with a plus or minus sign on numeric values. By default, only the "-" sign is used on negative numbers.
    An optional padding specifier that says what character to use for padding (if specified). Possible values are 0 or any other character precedeed by a '. The default is to pad with spaces.
    An optional "-" sign, that causes sprintf to left-align the result of this placeholder. The default is to right-align the result.
    An optional number, that says how many characters the result should have. If the value to be returned is shorter than this number, the result will be padded.
    An optional precision modifier, consisting of a "." (dot) followed by a number, that says how many digits should be displayed for floating point numbers. When used on a string, it causes the result to be truncated.
    A type specifier that can be any of:
        % — print a literal "%" character
        b — print an integer as a binary number
        c — print an integer as the character with that ASCII value
        d — print an integer as a signed decimal number
        e — print a float as scientific notation
        u — print an integer as an unsigned decimal number
        f — print a float as is
        o — print an integer as an octal number
        s — print a string as is
        x — print an integer as a hexadecimal number (lower-case)
        X — print an integer as a hexadecimal number (upper-case)
**/
var sprintf = (function() {
	function get_type(variable) {
		return Object.prototype.toString.call(variable).slice(8, -1).toLowerCase();
	}
	function str_repeat(input, multiplier) {
		for (var output = []; multiplier > 0; output[--multiplier] = input) {/* do nothing */}
		return output.join('');
	}

	var str_format = function() {
		if (!str_format.cache.hasOwnProperty(arguments[0])) {
			str_format.cache[arguments[0]] = str_format.parse(arguments[0]);
		}
		return str_format.format.call(null, str_format.cache[arguments[0]], arguments);
	};

	str_format.format = function(parse_tree, argv) {
		var cursor = 1, tree_length = parse_tree.length, node_type = '', arg, output = [], i, k, match, pad, pad_character, pad_length;
		for (i = 0; i < tree_length; i++) {
			node_type = get_type(parse_tree[i]);
			if (node_type === 'string') {
				output.push(parse_tree[i]);
			}
			else if (node_type === 'array') {
				match = parse_tree[i]; // convenience purposes only
				if (match[2]) { // keyword argument
					arg = argv[cursor];
					for (k = 0; k < match[2].length; k++) {
						if (!arg.hasOwnProperty(match[2][k])) {
							throw(sprintf('[sprintf] property "%s" does not exist', match[2][k]));
						}
						arg = arg[match[2][k]];
					}
				}
				else if (match[1]) { // positional argument (explicit)
					arg = argv[match[1]];
				}
				else { // positional argument (implicit)
					arg = argv[cursor++];
				}

				if (/[^s]/.test(match[8]) && (get_type(arg) != 'number')) {
					throw(sprintf('[sprintf] expecting number but found %s', get_type(arg)));
				}
				switch (match[8]) {
					case 'b': arg = arg.toString(2); break;
					case 'c': arg = String.fromCharCode(arg); break;
					case 'd': arg = parseInt(arg, 10); break;
					case 'e': arg = match[7] ? arg.toExponential(match[7]) : arg.toExponential(); break;
					case 'f': arg = match[7] ? parseFloat(arg).toFixed(match[7]) : parseFloat(arg); break;
					case 'o': arg = arg.toString(8); break;
					case 's': arg = ((arg = String(arg)) && match[7] ? arg.substring(0, match[7]) : arg); break;
					case 'u': arg = Math.abs(arg); break;
					case 'x': arg = arg.toString(16); break;
					case 'X': arg = arg.toString(16).toUpperCase(); break;
				}
				arg = (/[def]/.test(match[8]) && match[3] && arg >= 0 ? '+'+ arg : arg);
				pad_character = match[4] ? match[4] == '0' ? '0' : match[4].charAt(1) : ' ';
				pad_length = match[6] - String(arg).length;
				pad = match[6] ? str_repeat(pad_character, pad_length) : '';
				output.push(match[5] ? arg + pad : pad + arg);
			}
		}
		return output.join('');
	};

	str_format.cache = {};

	str_format.parse = function(fmt) {
		var _fmt = fmt, match = [], parse_tree = [], arg_names = 0;
		while (_fmt) {
			if ((match = /^[^\x25]+/.exec(_fmt)) !== null) {
				parse_tree.push(match[0]);
			}
			else if ((match = /^\x25{2}/.exec(_fmt)) !== null) {
				parse_tree.push('%');
			}
			else if ((match = /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(_fmt)) !== null) {
				if (match[2]) {
					arg_names |= 1;
					var field_list = [], replacement_field = match[2], field_match = [];
					if ((field_match = /^([a-z_][a-z_\d]*)/i.exec(replacement_field)) !== null) {
						field_list.push(field_match[1]);
						while ((replacement_field = replacement_field.substring(field_match[0].length)) !== '') {
							if ((field_match = /^\.([a-z_][a-z_\d]*)/i.exec(replacement_field)) !== null) {
								field_list.push(field_match[1]);
							}
							else if ((field_match = /^\[(\d+)\]/.exec(replacement_field)) !== null) {
								field_list.push(field_match[1]);
							}
							else {
								throw('[sprintf] huh?');
							}
						}
					}
					else {
						throw('[sprintf] huh?');
					}
					match[2] = field_list;
				}
				else {
					arg_names |= 2;
				}
				if (arg_names === 3) {
					throw('[sprintf] mixing positional and named placeholders is not (yet) supported');
				}
				parse_tree.push(match);
			}
			else {
				throw('[sprintf] huh?');
			}
			_fmt = _fmt.substring(match[0].length);
		}
		return parse_tree;
	};

	return str_format;
})();

var vsprintf = function(fmt, argv) {
	argv.unshift(fmt);
	return sprintf.apply(null, argv);
};

//ELLEN'S STUFF...

/**********************************************************************************************************
=Functions=
ucFirst(string)         returns string        first letter upper-case
dedupArray(array)       returns array         removes duplicate array entries
createCurrentDateTime   returns Date object   retrieves current date and time in YYYY-MM-DD HH:MM:SS
isNumericInput          returns boolean       checks if input is number or not

=Prototypes=
Number.prototype.padLeft                      pads number with 0s on the left
Array.prototype.diff	returns array         returns array of differences between two arrays
**********************************************************************************************************/

var utilities = {
	ucFirst: function(string){
		return string.charAt(0).toUpperCase() + string.slice(1);
	}
	,dedupArray: function(array){
		var a = array.concat();
	    for(var i=0; i<a.length; ++i) {
	        for(var j=i+1; j<a.length; ++j) {
	            if(a[i] === a[j])
	                a.splice(j--, 1);
	        }
	    }
	    return a;
	}
	,createCurrentDateTime: function(){
		// should use the date format option:
		// return (new Date()).format('Y-m-d H:i:s');

		var d = new Date,
    dformat = [	d.getFullYear(),
								(d.getMonth()+1).padLeft(),
	              d.getDate().padLeft()
						  ].join('-')+' '+
              [	d.getHours().padLeft(),
	              d.getMinutes().padLeft(),
	              d.getSeconds().padLeft()
							].join(':');
		return dformat;
	}
	,isNumericInput: function(event){
		return event.charCode >= 48 && event.charCode <= 57 || event.charCode == 0 || event.charCode == 45; //numbers, backspace, and -
	}
};

Number.prototype.padLeft = function(base,chr){
	// note you should be using sprintf for this:
	// return sprintf("%'"+chr+"10s",base); // this will pad base to 10 places using the chr
    var  len = (String(base || 10).length - String(this).length)+1;
    return len > 0? new Array(len).join(chr || '0')+this : this;
}

Array.prototype.diff = function(a) {
    return this.filter(function(i) {return a.indexOf(i) < 0;});
};