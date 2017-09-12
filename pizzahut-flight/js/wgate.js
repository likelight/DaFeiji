 (function() {
  var getParameterByName = function (name, url) {
      if (!url) url = window.location.href;
      name = name.replace(/[\[\]]/g, "\\$&");
      var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
          results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, " "));
  }
      WgateJs = {};
      WgateJs.auto_auth=true;
      WgateJs.gate_options={force:1};
      WgateJs.ready=function(){
       window.wid=WgateJs.getWgateid();
        var type = getParameterByName('type');
        var channel = getParameterByName('channel');
       if ((type == '10' || type == '49' || type == 'free') && channel == 'tv') {
            window.channel = 'tv';
            checkType(type, 'tv');
            alert('test tv');
        } else if (type == 'lose') {
            showType('lose');
        }
       // alert(window.wid);
      }
      var u=(("https:" == document.location.protocol) ? "https" : "http") + "://st.weixingate.com/";
      u=u+'st/2227';
      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
      g.defer=true; g.async=true; g.src=u; s.parentNode.insertBefore(g,s);
    })();
