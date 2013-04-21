/**
 * Rocker client
 * ----------------------
 * This is a javascript client that can be used to communicate with
 * a Rocker server (https://github.com/victorjonsson/PHP-Rocker).
 * This script works both in a browser and as a nodejs module
 *
 * version: 1.0
 * author: Victor Jonsson (http://victorjonsson.se)
 * license: MIT
 */
var Rocker = (function(win) {

    'use strict';

    var IS_BROWSER = typeof win == 'object' && ('XMLHttpRequest' in win || typeof ActiveXObject != 'undefined'),
        concurrent = 0,
        parseJSON = function(data) {

            try {
                if( !IS_BROWSER || (typeof JSON != 'undefined' && typeof JSON.parse == 'function') ) {
                    return JSON.parse(data);
                }

                data = data.replace(/^\s\s*/, '').replace(/\s\s*$/, ''); // For ie's sake...

                // JSON RegExp
                var rvalidchars = /^[\],:{}\s]*$/,
                    rvalidbraces = /(?:^|:|,)(?:\s*\[)+/g,
                    rvalidescape = /\\(?:["\\\/bfnrt]|u[\da-fA-F]{4})/g,
                    rvalidtokens = /"[^"\\\r\n]*"|true|false|null|-?(?:\d+\.|)\d+(?:[eE][+-]?\d+|)/g;

                if ( rvalidchars.test( data.replace( rvalidescape, "@" )
                    .replace( rvalidtokens, "]" )
                    .replace( rvalidbraces, "")) ) {

                    return ( new Function( "return " + data ) )();
                }
            } catch(e) {}

            // we shouldn't have come this far
            throw new Error('Unable to parse JSON');
        };

    /**
     * Rocker REST server client
     * @param {String} baseURI
     * @param {Number} [maxConcurrentRequests]
     */
    function Rocker(baseURI, maxConcurrentRequests) {
        this.baseURI = baseURI;
        this.maxConcurrentRequests = maxConcurrentRequests ? maxConcurrentRequests:(IS_BROWSER ? 5:50);
        this.auth = false;
        this.user = false;
        this.secret = false;
        if( !IS_BROWSER ) {
            this.baseURI = require('url').parse(this.baseURI);
        }
    }

    /**
     * @param {String} s
     */
    Rocker.prototype.setSecret = function(s) {
        this.secret = s;
    };

    /**
     * @param {String} user
     * @param {String} pass
     */
    Rocker.prototype.setUser = function(user, pass) {
        var mechanism = 'basic ';
        this.auth = user+':'+pass;
        if( this.secret ) {
            this.auth = RC4Cipcher.encrypt(this.secret, this.auth);
            mechanism = 'RC4 ';
        }
        this.user = user;
        this.auth = mechanism + Base64.encode(this.auth);
    };

    /**
     * The object can contain the following props
     *  path - May include query string as well (required)
     *  data - String containing query parameters sent with POST requests
     *  method - GET, PUT, POST, DELETE (default is GET)
     *  auth - Whether or not send authorization header, authentication credentials is set using rocker.setUser()
     *  onComplete - function that gets executed when response is finished
     *
     * @param {Object} requestObj
     */
    Rocker.prototype.request = function(requestObj) {
        var _rocker = this;
        if( concurrent > this.maxConcurrentRequests ) {
            setTimeout(function() {
                _rocker.request(requestObj);
            }, 300);
            return;
        }

        concurrent++;

        var onFinished = function(status, content, http) {
            concurrent--;
            if( typeof requestObj.onComplete == 'function' ) {
                requestObj.onComplete(status, status == 204 ? {}:parseJSON(content), http);
            }
        };

        if( !requestObj.method )
            requestObj.method = 'GET';

        if( IS_BROWSER ) {

            var http = 'XMLHttpRequest' in win ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
            http.onreadystatechange = function() {
                if( http.readyState == 4 ) {
                    onFinished(http.status, http.responseText, http);
                }
            };
            http.open(requestObj.method, this.baseURI + requestObj.path, true);
            http.setRequestHeader('X-Requested-With', 'xmlhttprequest');
            if( requestObj.auth && this.auth ) {
                http.setRequestHeader('Authorization', this.auth);
            }
            if( requestObj.method == 'POST' ) {
                http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                var post = '';

                for(var x in requestObj.data ) {
                    if( typeof requestObj.data[x] == 'object' && requestObj.data.hasOwnProperty(x) ) {
                        for(var y in requestObj.data[x]) {
                            if(requestObj.data[x].hasOwnProperty(y) && (typeof requestObj.data[x][y] == 'string' || typeof requestObj.data[x][y] == 'number') ) {
                                post += x+'['+y+']='+encodeURIComponent(requestObj.data[x][y])+'&';
                            }
                        }
                    }
                    else if( (typeof requestObj.data[x] == 'string' || typeof requestObj.data[x] == 'number') && requestObj.data.hasOwnProperty(x) ) {
                        post += x+'='+encodeURIComponent(requestObj.data[x])+'&';
                    }
                }
                post = post.substr(0, post.length -1);
                http.send(post);
            } else {
                http.send();
            }

        } else {

            if( typeof requestObj.headers == 'undefined')
                requestObj.headers = {};

            if( requestObj.auth && this.auth )
                requestObj.headers['Authorization'] = this.auth;

            if( requestObj.data && typeof requestObj.data != 'string') {
                requestObj.data = require('querystring').stringify(requestObj.data);
                requestObj.headers['Content-Length'] = Buffer.byteLength(requestObj.data, 'utf8');
                requestObj.headers['Content-type'] = 'application/x-www-form-urlencoded';
            }
            if( !requestObj.data ) {
                requestObj.data = '';
            }
            requestObj.port = this.baseURI.port;
            requestObj.host = this.baseURI.host;
            requestObj.protocol = this.baseURI.protocol;
            requestObj.path = this.baseURI.path + requestObj.path;

            var req = require('http').request(requestObj, function(response) {
                var collectedBody = '';
                response.setEncoding('utf-8');
                response.on('data', function (body) {
                    collectedBody += body;
                });
                response.on('end', function() {
                    onFinished(response.statusCode, collectedBody);
                });
            });

            req.on('error', function(e) {
                throw new Error(e);
            });

            req.write(requestObj.data);
            req.end();

        }
    };

    /**
     * @param {Function} callback
     */
    Rocker.prototype.getServerVersion = function(callback) {
        this.request({
            path : 'system/version',
            onComplete : function(status, json) {
                callback(json.version);
            }
        });
    };

    /**
     * @param {Function} callback
     */
    Rocker.prototype.getAvailableOperations = function(callback) {
        this.request({
            path : 'operations',
            onComplete : function(status, json) {
                callback(json);
            }
        });
    };

    /**
     * @param callback
     */
    Rocker.prototype.me = function(callback) {
        this.request({
            path : 'me',
            auth : true,
            onComplete : function(status, json) {
                callback(status == 200 ? json:false);
            }
        });
    };

    /**
     * @param {String} email
     * @param {String} nick
     * @param {String} pass
     * @param {Array} meta
     * @param {Function} callback
     */
    Rocker.prototype.createUser = function(email, nick, pass, meta, callback) {
        var _rocker = this;
        this.request({
            path: 'user',
            method: 'POST',
            onComplete : function(status, json) {
                if( status == 201 ) {
                    _rocker.setUser(email, pass);
                }
                callback(status, json);
            },
            data : {
                email : email,
                nick : nick,
                password : pass,
                meta : meta
            }
        });
    };

    /*
     * - Utf8 encode/decode
     * - RC4 encrypt/decrypt
     * - Base64 encode/decode
     */
    var Utf8={};Utf8.encode=function(strUni){var strUtf=strUni.replace(/[\u0080-\u07ff]/g,function(c){var cc=c.charCodeAt(0);return String.fromCharCode(192|cc>>6,128|cc&63)});strUtf=strUtf.replace(/[\u0800-\uffff]/g,function(c){var cc=c.charCodeAt(0);return String.fromCharCode(224|cc>>12,128|cc>>6&63,128|cc&63)});return strUtf};Utf8.decode=function(strUtf){var strUni=strUtf.replace(/[\u00e0-\u00ef][\u0080-\u00bf][\u0080-\u00bf]/g,function(c){var cc=(c.charCodeAt(0)&15)<<12|(c.charCodeAt(1)&63)<<6|c.charCodeAt(2)&63;return String.fromCharCode(cc)});strUni=strUni.replace(/[\u00c0-\u00df][\u0080-\u00bf]/g,function(c){var cc=(c.charCodeAt(0)&31)<<6|c.charCodeAt(1)&63;return String.fromCharCode(cc)});return strUni};var RC4Cipcher={encrypt:function(key,pt){var s=new Array;for(var i=0;i<256;i++){s[i]=i}var j=0,x;for(i=0;i<256;i++){j=(j+s[i]+key.charCodeAt(i%key.length))%256;x=s[i];s[i]=s[j];s[j]=x}i=0;j=0;var ct="";for(var y=0;y<pt.length;y++){i=(i+1)%256;j=(j+s[i])%256;x=s[i];s[i]=s[j];s[j]=x;ct+=String.fromCharCode(pt.charCodeAt(y)^s[(s[i]+s[j])%256])}return ct},decrypt:function(key,ct){return this.encrypt(key,ct)}};var Base64={};Base64.code="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";Base64.encode=function(str,utf8encode){utf8encode=typeof utf8encode=="undefined"?false:utf8encode;var o1,o2,o3,bits,h1,h2,h3,h4,e=[],pad="",c,plain,coded;var b64=Base64.code;plain=utf8encode?str.encodeUTF8():str;c=plain.length%3;if(c>0){while(c++<3){pad+="=";plain+="\0"}}for(c=0;c<plain.length;c+=3){o1=plain.charCodeAt(c);o2=plain.charCodeAt(c+1);o3=plain.charCodeAt(c+2);bits=o1<<16|o2<<8|o3;h1=bits>>18&63;h2=bits>>12&63;h3=bits>>6&63;h4=bits&63;e[c/3]=b64.charAt(h1)+b64.charAt(h2)+b64.charAt(h3)+b64.charAt(h4)}coded=e.join("");coded=coded.slice(0,coded.length-pad.length)+pad;return coded};Base64.decode=function(str,utf8decode){utf8decode=typeof utf8decode=="undefined"?false:utf8decode;var o1,o2,o3,h1,h2,h3,h4,bits,d=[],plain,coded;var b64=Base64.code;coded=utf8decode?str.decodeUTF8():str;for(var c=0;c<coded.length;c+=4){h1=b64.indexOf(coded.charAt(c));h2=b64.indexOf(coded.charAt(c+1));h3=b64.indexOf(coded.charAt(c+2));h4=b64.indexOf(coded.charAt(c+3));bits=h1<<18|h2<<12|h3<<6|h4;o1=bits>>>16&255;o2=bits>>>8&255;o3=bits&255;d[c/4]=String.fromCharCode(o1,o2,o3);if(h4==64)d[c/4]=String.fromCharCode(o1,o2);if(h3==64)d[c/4]=String.fromCharCode(o1)}plain=d.join("");return utf8decode?plain.decodeUTF8():plain};

    return Rocker;

})( typeof window != 'undefined' ? window:undefined);


if( typeof module != 'undefined' ) {
    module.exports = Rocker;
}