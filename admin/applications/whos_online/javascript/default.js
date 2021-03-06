var refreshTime = null;
var refreshTimer = null;

function refreshGrid(){
	$('.refreshTime').html(date('g:i:s a'));
	showAjaxLoader($('.gridTableHolder'), 'xlarge');
	$.ajax({
		cache: false,
		dataType: 'json',
		url: js_app_link('app=whos_online&appPage=default&action=refreshGrid'),
		success: function (data){
			$('.gridTableHolder').html(data.grid);
			$('.sessionCount').html(data.sessions);
			$('.duplicateCount').html(data.duplicates);
			$('.botCount').html(data.bots);
			$('.adminCount').html(data.admin);
			$('.customerCount').html(data.customers);
			
			removeAjaxLoader($('.gridTableHolder'));
		}
	});
	
	if (refreshTime){
		var nextTime = mktime(
			date('H'),
			date('i'),
			parseFloat(date('s')) + parseFloat(refreshTime)
		);
		$('.nextRefreshTime').html(date('g:i:s a', nextTime));
		refreshTimer = setTimeout(function (){
			refreshGrid();
		}, refreshTime * 1000);
	}
}

$(document).ready(function (){
	$('.refreshTime').html(date('g:i:s a'));
	
	$('.refreshLink').click(function (){
		if ($(this).attr('data-seconds') == 'null'){
			clearTimeout(refreshTimer);
			refreshTime = null;
			refreshTimer = null;
			$('.nextRefreshTime').html('N/A');
		}else{
			refreshTime = $(this).attr('data-seconds');
		}
		refreshGrid();
		return false;
	});
	
	$('.refreshButton').live('click', function (){
		refreshGrid();
	});
	
	refreshGrid();
});
/*
 * @TODO: These two functions need to be put into the main.js file or something else that's common
 * currently they are used in one extension and here.
 */
  function mktime() {
      // http://kevin.vanzonneveld.net
      // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
      // +   improved by: baris ozdil
      // +      input by: gabriel paderni
      // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
      // +   improved by: FGFEmperor
      // +      input by: Yannoo
      // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
      // +      input by: jakes
      // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
      // +   bugfixed by: Marc Palau
      // +   improved by: Brett Zamir (http://brett-zamir.me)
 
      var no=0, i = 0, ma=0, mb=0, d = new Date(), dn = new Date(), argv = arguments, argc = argv.length;
 
      var dateManip = {
          0: function(tt){ return d.setHours(tt); },
          1: function(tt){ return d.setMinutes(tt); },
          2: function(tt){ var set = d.setSeconds(tt); mb = d.getDate() - dn.getDate(); return set;},
          3: function(tt){ var set = d.setMonth(parseInt(tt, 10)-1); ma = d.getFullYear() - dn.getFullYear(); return set;},
          4: function(tt){ return d.setDate(tt+mb);},
          5: function(tt){
              if (tt >= 0 && tt <= 69) {
                  tt += 2000;
              }
              else if (tt >= 70 && tt <= 100) {
                  tt += 1900;
              }
              return d.setFullYear(tt+ma);
          }
          // 7th argument (for DST) is deprecated
      };
 
      for( i = 0; i < argc; i++ ){
          no = parseInt(argv[i]*1, 10);
          if (isNaN(no)) {
              return false;
          } else {
              // arg is number, let's manipulate date object
              if(!dateManip[i](no)){
                  // failed
                  return false;
              }
          }
      }
      for (i = argc; i < 6; i++) {
          switch(i) {
              case 0:
                  no = dn.getHours();
                  break;
              case 1:
                  no = dn.getMinutes();
                  break;
              case 2:
                  no = dn.getSeconds();
                  break;
              case 3:
                  no = dn.getMonth()+1;
                  break;
              case 4:
                  no = dn.getDate();
                  break;
              case 5:
                  no = dn.getFullYear();
                  break;
          }
          dateManip[i](no);
      }
 
      return Math.floor(d.getTime()/1000);
  }
  
  function date ( format, timestamp ) {
      // http://kevin.vanzonneveld.net
      // +   original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
      // +      parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
      // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
      // +   improved by: MeEtc (http://yass.meetcweb.com)
      // +   improved by: Brad Touesnard
      // +   improved by: Tim Wiel
      // +   improved by: Bryan Elliott
      // +   improved by: Brett Zamir (http://brett-zamir.me)
      // +   improved by: David Randall
      // +      input by: Brett Zamir (http://brett-zamir.me)
      // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
      // +   improved by: Brett Zamir (http://brett-zamir.me)
      // +   improved by: Brett Zamir (http://brett-zamir.me)
      // +   derived from: gettimeofday
      // %        note 1: Uses global: php_js to store the default timezone
 
      var jsdate=(
          (typeof(timestamp) == 'undefined') ? new Date() : // Not provided
          (typeof(timestamp) == 'number') ? new Date(timestamp*1000) : // UNIX timestamp
          new Date(timestamp) // Javascript Date()
      ); // , tal=[]
      var pad = function(n, c){
          if( (n = n + "").length < c ) {
              return new Array(++c - n.length).join("0") + n;
          } else {
              return n;
          }
      };
      var _dst = function (t) {
          // Calculate Daylight Saving Time (derived from gettimeofday() code)
          var dst=0;
          var jan1 = new Date(t.getFullYear(), 0, 1, 0, 0, 0, 0);  // jan 1st
          var june1 = new Date(t.getFullYear(), 6, 1, 0, 0, 0, 0); // june 1st
          var temp = jan1.toUTCString();
          var jan2 = new Date(temp.slice(0, temp.lastIndexOf(' ')-1));
          temp = june1.toUTCString();
          var june2 = new Date(temp.slice(0, temp.lastIndexOf(' ')-1));
          var std_time_offset = (jan1 - jan2) / (1000 * 60 * 60);
          var daylight_time_offset = (june1 - june2) / (1000 * 60 * 60);
 
          if (std_time_offset === daylight_time_offset) {
              dst = 0; // daylight savings time is NOT observed
          }
          else {
              // positive is southern, negative is northern hemisphere
              var hemisphere = std_time_offset - daylight_time_offset;
              if (hemisphere >= 0) {
                  std_time_offset = daylight_time_offset;
              }
              dst = 1; // daylight savings time is observed
          }
          return dst;
      };
      var ret = '';
      var txt_weekdays = ["Sunday","Monday","Tuesday","Wednesday",
          "Thursday","Friday","Saturday"];
      var txt_ordin = {1:"st",2:"nd",3:"rd",21:"st",22:"nd",23:"rd",31:"st"};
      var txt_months =  ["", "January", "February", "March", "April",
          "May", "June", "July", "August", "September", "October", "November",
          "December"];
 
      var f = {
          // Day
              d: function(){
                  return pad(f.j(), 2);
              },
              D: function(){
                  var t = f.l();
                  return t.substr(0,3);
              },
              j: function(){
                  return jsdate.getDate();
              },
              l: function(){
                  return txt_weekdays[f.w()];
              },
              N: function(){
                  return f.w() + 1;
              },
              S: function(){
                  return txt_ordin[f.j()] ? txt_ordin[f.j()] : 'th';
              },
              w: function(){
                  return jsdate.getDay();
              },
              z: function(){
                  return (jsdate - new Date(jsdate.getFullYear() + "/1/1")) / 864e5 >> 0;
              },
 
          // Week
              W: function(){
                  var a = f.z(), b = 364 + f.L() - a;
                  var nd2, nd = (new Date(jsdate.getFullYear() + "/1/1").getDay() || 7) - 1;
  
                  if(b <= 2 && ((jsdate.getDay() || 7) - 1) <= 2 - b){
                      return 1;
                  } 
                  if(a <= 2 && nd >= 4 && a >= (6 - nd)){
                      nd2 = new Date(jsdate.getFullYear() - 1 + "/12/31");
                      return date("W", Math.round(nd2.getTime()/1000));
                  }
                  return (1 + (nd <= 3 ? ((a + nd) / 7) : (a - (7 - nd)) / 7) >> 0);
              },
 
          // Month
              F: function(){
                  return txt_months[f.n()];
              },
              m: function(){
                  return pad(f.n(), 2);
              },
              M: function(){
                  var t = f.F();
                  return t.substr(0,3);
              },
              n: function(){
                  return jsdate.getMonth() + 1;
              },
              t: function(){
                  var n;
                  if( (n = jsdate.getMonth() + 1) == 2 ){
                      return 28 + f.L();
                  }
                  if( n & 1 && n < 8 || !(n & 1) && n > 7 ){
                      return 31;
                  }
                  return 30;
              },
 
          // Year
              L: function(){
                  var y = f.Y();
                  return (!(y & 3) && (y % 1e2 || !(y % 4e2))) ? 1 : 0;
              },
              o: function(){
                  if (f.n() === 12 && f.W() === 1) {
                      return jsdate.getFullYear()+1;
                  }
                  if (f.n() === 1 && f.W() >= 52) {
                      return jsdate.getFullYear()-1;
                  }
                  return jsdate.getFullYear();
              },
              Y: function(){
                  return jsdate.getFullYear();
              },
              y: function(){
                  return (jsdate.getFullYear() + "").slice(2);
              },
 
          // Time
              a: function(){
                  return jsdate.getHours() > 11 ? "pm" : "am";
              },
              A: function(){
                  return f.a().toUpperCase();
              },
              B: function(){
                  // peter paul koch:
                  var off = (jsdate.getTimezoneOffset() + 60)*60;
                  var theSeconds = (jsdate.getHours() * 3600) +
                                   (jsdate.getMinutes() * 60) +
                                    jsdate.getSeconds() + off;
                  var beat = Math.floor(theSeconds/86.4);
                  if (beat > 1000) {
                      beat -= 1000;
                  }
                  if (beat < 0) {
                      beat += 1000;
                  }
                  if ((String(beat)).length == 1) {
                      beat = "00"+beat;
                  }
                  if ((String(beat)).length == 2) {
                      beat = "0"+beat;
                  }
                  return beat;
              },
              g: function(){
                  return jsdate.getHours() % 12 || 12;
              },
              G: function(){
                  return jsdate.getHours();
              },
              h: function(){
                  return pad(f.g(), 2);
              },
              H: function(){
                  return pad(jsdate.getHours(), 2);
              },
              i: function(){
                  return pad(jsdate.getMinutes(), 2);
              },
              s: function(){
                  return pad(jsdate.getSeconds(), 2);
              },
              u: function(){
                  return pad(jsdate.getMilliseconds()*1000, 6);
              },
 
          // Timezone
              e: function () {
                  return 'UTC';
              },
              I: function(){
                  return _dst(jsdate);
              },
              O: function(){
                 var t = pad(Math.abs(jsdate.getTimezoneOffset()/60*100), 4);
                 t = (jsdate.getTimezoneOffset() > 0) ? "-"+t : "+"+t;
                 return t;
              },
              P: function(){
                  var O = f.O();
                  return (O.substr(0, 3) + ":" + O.substr(3, 2));
              },
              T: function () {
                  return 'UTC';
              },
              Z: function(){
                 return -jsdate.getTimezoneOffset()*60;
              },
 
          // Full Date/Time
              c: function(){
                  return f.Y() + "-" + f.m() + "-" + f.d() + "T" + f.h() + ":" + f.i() + ":" + f.s() + f.P();
              },
              r: function(){
                  return f.D()+', '+f.d()+' '+f.M()+' '+f.Y()+' '+f.H()+':'+f.i()+':'+f.s()+' '+f.O();
              },
              U: function(){
                  return Math.round(jsdate.getTime()/1000);
              }
      };
 
      return format.replace(/[\\]?([a-zA-Z])/g, function(t, s){
          if( t!=s ){
              // escaped
              ret = s;
          } else if( f[s] ){
              // a date function exists
              ret = f[s]();
          } else{
              // nothing special
              ret = s;
          }
          return ret;
      });
  }