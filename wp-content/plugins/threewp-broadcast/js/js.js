/*! Magnific Popup - v1.1.0 - 2016-02-20
* http://dimsemenov.com/plugins/magnific-popup/
* Copyright (c) 2016 Dmitry Semenov; */
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):window.jQuery||window.Zepto)}(function(a){var b,c,d,e,f,g,h="Close",i="BeforeClose",j="AfterClose",k="BeforeAppend",l="MarkupParse",m="Open",n="Change",o="mfp",p="."+o,q="mfp-ready",r="mfp-removing",s="mfp-prevent-close",t=function(){},u=!!window.jQuery,v=a(window),w=function(a,c){b.ev.on(o+a+p,c)},x=function(b,c,d,e){var f=document.createElement("div");return f.className="mfp-"+b,d&&(f.innerHTML=d),e?c&&c.appendChild(f):(f=a(f),c&&f.appendTo(c)),f},y=function(c,d){b.ev.triggerHandler(o+c,d),b.st.callbacks&&(c=c.charAt(0).toLowerCase()+c.slice(1),b.st.callbacks[c]&&b.st.callbacks[c].apply(b,a.isArray(d)?d:[d]))},z=function(c){return c===g&&b.currTemplate.closeBtn||(b.currTemplate.closeBtn=a(b.st.closeMarkup.replace("%title%",b.st.tClose)),g=c),b.currTemplate.closeBtn},A=function(){a.magnificPopup.instance||(b=new t,b.init(),a.magnificPopup.instance=b)},B=function(){var a=document.createElement("p").style,b=["ms","O","Moz","Webkit"];if(void 0!==a.transition)return!0;for(;b.length;)if(b.pop()+"Transition"in a)return!0;return!1};t.prototype={constructor:t,init:function(){var c=navigator.appVersion;b.isLowIE=b.isIE8=document.all&&!document.addEventListener,b.isAndroid=/android/gi.test(c),b.isIOS=/iphone|ipad|ipod/gi.test(c),b.supportsTransition=B(),b.probablyMobile=b.isAndroid||b.isIOS||/(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent),d=a(document),b.popupsCache={}},open:function(c){var e;if(c.isObj===!1){b.items=c.items.toArray(),b.index=0;var g,h=c.items;for(e=0;e<h.length;e++)if(g=h[e],g.parsed&&(g=g.el[0]),g===c.el[0]){b.index=e;break}}else b.items=a.isArray(c.items)?c.items:[c.items],b.index=c.index||0;if(b.isOpen)return void b.updateItemHTML();b.types=[],f="",c.mainEl&&c.mainEl.length?b.ev=c.mainEl.eq(0):b.ev=d,c.key?(b.popupsCache[c.key]||(b.popupsCache[c.key]={}),b.currTemplate=b.popupsCache[c.key]):b.currTemplate={},b.st=a.extend(!0,{},a.magnificPopup.defaults,c),b.fixedContentPos="auto"===b.st.fixedContentPos?!b.probablyMobile:b.st.fixedContentPos,b.st.modal&&(b.st.closeOnContentClick=!1,b.st.closeOnBgClick=!1,b.st.showCloseBtn=!1,b.st.enableEscapeKey=!1),b.bgOverlay||(b.bgOverlay=x("bg").on("click"+p,function(){b.close()}),b.wrap=x("wrap").attr("tabindex",-1).on("click"+p,function(a){b._checkIfClose(a.target)&&b.close()}),b.container=x("container",b.wrap)),b.contentContainer=x("content"),b.st.preloader&&(b.preloader=x("preloader",b.container,b.st.tLoading));var i=a.magnificPopup.modules;for(e=0;e<i.length;e++){var j=i[e];j=j.charAt(0).toUpperCase()+j.slice(1),b["init"+j].call(b)}y("BeforeOpen"),b.st.showCloseBtn&&(b.st.closeBtnInside?(w(l,function(a,b,c,d){c.close_replaceWith=z(d.type)}),f+=" mfp-close-btn-in"):b.wrap.append(z())),b.st.alignTop&&(f+=" mfp-align-top"),b.fixedContentPos?b.wrap.css({overflow:b.st.overflowY,overflowX:"hidden",overflowY:b.st.overflowY}):b.wrap.css({top:v.scrollTop(),position:"absolute"}),(b.st.fixedBgPos===!1||"auto"===b.st.fixedBgPos&&!b.fixedContentPos)&&b.bgOverlay.css({height:d.height(),position:"absolute"}),b.st.enableEscapeKey&&d.on("keyup"+p,function(a){27===a.keyCode&&b.close()}),v.on("resize"+p,function(){b.updateSize()}),b.st.closeOnContentClick||(f+=" mfp-auto-cursor"),f&&b.wrap.addClass(f);var k=b.wH=v.height(),n={};if(b.fixedContentPos&&b._hasScrollBar(k)){var o=b._getScrollbarSize();o&&(n.marginRight=o)}b.fixedContentPos&&(b.isIE7?a("body, html").css("overflow","hidden"):n.overflow="hidden");var r=b.st.mainClass;return b.isIE7&&(r+=" mfp-ie7"),r&&b._addClassToMFP(r),b.updateItemHTML(),y("BuildControls"),a("html").css(n),b.bgOverlay.add(b.wrap).prependTo(b.st.prependTo||a(document.body)),b._lastFocusedEl=document.activeElement,setTimeout(function(){b.content?(b._addClassToMFP(q),b._setFocus()):b.bgOverlay.addClass(q),d.on("focusin"+p,b._onFocusIn)},16),b.isOpen=!0,b.updateSize(k),y(m),c},close:function(){b.isOpen&&(y(i),b.isOpen=!1,b.st.removalDelay&&!b.isLowIE&&b.supportsTransition?(b._addClassToMFP(r),setTimeout(function(){b._close()},b.st.removalDelay)):b._close())},_close:function(){y(h);var c=r+" "+q+" ";if(b.bgOverlay.detach(),b.wrap.detach(),b.container.empty(),b.st.mainClass&&(c+=b.st.mainClass+" "),b._removeClassFromMFP(c),b.fixedContentPos){var e={marginRight:""};b.isIE7?a("body, html").css("overflow",""):e.overflow="",a("html").css(e)}d.off("keyup"+p+" focusin"+p),b.ev.off(p),b.wrap.attr("class","mfp-wrap").removeAttr("style"),b.bgOverlay.attr("class","mfp-bg"),b.container.attr("class","mfp-container"),!b.st.showCloseBtn||b.st.closeBtnInside&&b.currTemplate[b.currItem.type]!==!0||b.currTemplate.closeBtn&&b.currTemplate.closeBtn.detach(),b.st.autoFocusLast&&b._lastFocusedEl&&a(b._lastFocusedEl).focus(),b.currItem=null,b.content=null,b.currTemplate=null,b.prevHeight=0,y(j)},updateSize:function(a){if(b.isIOS){var c=document.documentElement.clientWidth/window.innerWidth,d=window.innerHeight*c;b.wrap.css("height",d),b.wH=d}else b.wH=a||v.height();b.fixedContentPos||b.wrap.css("height",b.wH),y("Resize")},updateItemHTML:function(){var c=b.items[b.index];b.contentContainer.detach(),b.content&&b.content.detach(),c.parsed||(c=b.parseEl(b.index));var d=c.type;if(y("BeforeChange",[b.currItem?b.currItem.type:"",d]),b.currItem=c,!b.currTemplate[d]){var f=b.st[d]?b.st[d].markup:!1;y("FirstMarkupParse",f),f?b.currTemplate[d]=a(f):b.currTemplate[d]=!0}e&&e!==c.type&&b.container.removeClass("mfp-"+e+"-holder");var g=b["get"+d.charAt(0).toUpperCase()+d.slice(1)](c,b.currTemplate[d]);b.appendContent(g,d),c.preloaded=!0,y(n,c),e=c.type,b.container.prepend(b.contentContainer),y("AfterChange")},appendContent:function(a,c){b.content=a,a?b.st.showCloseBtn&&b.st.closeBtnInside&&b.currTemplate[c]===!0?b.content.find(".mfp-close").length||b.content.append(z()):b.content=a:b.content="",y(k),b.container.addClass("mfp-"+c+"-holder"),b.contentContainer.append(b.content)},parseEl:function(c){var d,e=b.items[c];if(e.tagName?e={el:a(e)}:(d=e.type,e={data:e,src:e.src}),e.el){for(var f=b.types,g=0;g<f.length;g++)if(e.el.hasClass("mfp-"+f[g])){d=f[g];break}e.src=e.el.attr("data-mfp-src"),e.src||(e.src=e.el.attr("href"))}return e.type=d||b.st.type||"inline",e.index=c,e.parsed=!0,b.items[c]=e,y("ElementParse",e),b.items[c]},addGroup:function(a,c){var d=function(d){d.mfpEl=this,b._openClick(d,a,c)};c||(c={});var e="click.magnificPopup";c.mainEl=a,c.items?(c.isObj=!0,a.off(e).on(e,d)):(c.isObj=!1,c.delegate?a.off(e).on(e,c.delegate,d):(c.items=a,a.off(e).on(e,d)))},_openClick:function(c,d,e){var f=void 0!==e.midClick?e.midClick:a.magnificPopup.defaults.midClick;if(f||!(2===c.which||c.ctrlKey||c.metaKey||c.altKey||c.shiftKey)){var g=void 0!==e.disableOn?e.disableOn:a.magnificPopup.defaults.disableOn;if(g)if(a.isFunction(g)){if(!g.call(b))return!0}else if(v.width()<g)return!0;c.type&&(c.preventDefault(),b.isOpen&&c.stopPropagation()),e.el=a(c.mfpEl),e.delegate&&(e.items=d.find(e.delegate)),b.open(e)}},updateStatus:function(a,d){if(b.preloader){c!==a&&b.container.removeClass("mfp-s-"+c),d||"loading"!==a||(d=b.st.tLoading);var e={status:a,text:d};y("UpdateStatus",e),a=e.status,d=e.text,b.preloader.html(d),b.preloader.find("a").on("click",function(a){a.stopImmediatePropagation()}),b.container.addClass("mfp-s-"+a),c=a}},_checkIfClose:function(c){if(!a(c).hasClass(s)){var d=b.st.closeOnContentClick,e=b.st.closeOnBgClick;if(d&&e)return!0;if(!b.content||a(c).hasClass("mfp-close")||b.preloader&&c===b.preloader[0])return!0;if(c===b.content[0]||a.contains(b.content[0],c)){if(d)return!0}else if(e&&a.contains(document,c))return!0;return!1}},_addClassToMFP:function(a){b.bgOverlay.addClass(a),b.wrap.addClass(a)},_removeClassFromMFP:function(a){this.bgOverlay.removeClass(a),b.wrap.removeClass(a)},_hasScrollBar:function(a){return(b.isIE7?d.height():document.body.scrollHeight)>(a||v.height())},_setFocus:function(){(b.st.focus?b.content.find(b.st.focus).eq(0):b.wrap).focus()},_onFocusIn:function(c){return c.target===b.wrap[0]||a.contains(b.wrap[0],c.target)?void 0:(b._setFocus(),!1)},_parseMarkup:function(b,c,d){var e;d.data&&(c=a.extend(d.data,c)),y(l,[b,c,d]),a.each(c,function(c,d){if(void 0===d||d===!1)return!0;if(e=c.split("_"),e.length>1){var f=b.find(p+"-"+e[0]);if(f.length>0){var g=e[1];"replaceWith"===g?f[0]!==d[0]&&f.replaceWith(d):"img"===g?f.is("img")?f.attr("src",d):f.replaceWith(a("<img>").attr("src",d).attr("class",f.attr("class"))):f.attr(e[1],d)}}else b.find(p+"-"+c).html(d)})},_getScrollbarSize:function(){if(void 0===b.scrollbarSize){var a=document.createElement("div");a.style.cssText="width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;",document.body.appendChild(a),b.scrollbarSize=a.offsetWidth-a.clientWidth,document.body.removeChild(a)}return b.scrollbarSize}},a.magnificPopup={instance:null,proto:t.prototype,modules:[],open:function(b,c){return A(),b=b?a.extend(!0,{},b):{},b.isObj=!0,b.index=c||0,this.instance.open(b)},close:function(){return a.magnificPopup.instance&&a.magnificPopup.instance.close()},registerModule:function(b,c){c.options&&(a.magnificPopup.defaults[b]=c.options),a.extend(this.proto,c.proto),this.modules.push(b)},defaults:{disableOn:0,key:null,midClick:!1,mainClass:"",preloader:!0,focus:"",closeOnContentClick:!1,closeOnBgClick:!0,closeBtnInside:!0,showCloseBtn:!0,enableEscapeKey:!0,modal:!1,alignTop:!1,removalDelay:0,prependTo:null,fixedContentPos:"auto",fixedBgPos:"auto",overflowY:"auto",closeMarkup:'<button title="%title%" type="button" class="mfp-close">&#215;</button>',tClose:"Close (Esc)",tLoading:"Loading...",autoFocusLast:!0}},a.fn.magnificPopup=function(c){A();var d=a(this);if("string"==typeof c)if("open"===c){var e,f=u?d.data("magnificPopup"):d[0].magnificPopup,g=parseInt(arguments[1],10)||0;f.items?e=f.items[g]:(e=d,f.delegate&&(e=e.find(f.delegate)),e=e.eq(g)),b._openClick({mfpEl:e},d,f)}else b.isOpen&&b[c].apply(b,Array.prototype.slice.call(arguments,1));else c=a.extend(!0,{},c),u?d.data("magnificPopup",c):d[0].magnificPopup=c,b.addGroup(d,c);return d};var C,D,E,F="inline",G=function(){E&&(D.after(E.addClass(C)).detach(),E=null)};a.magnificPopup.registerModule(F,{options:{hiddenClass:"hide",markup:"",tNotFound:"Content not found"},proto:{initInline:function(){b.types.push(F),w(h+"."+F,function(){G()})},getInline:function(c,d){if(G(),c.src){var e=b.st.inline,f=a(c.src);if(f.length){var g=f[0].parentNode;g&&g.tagName&&(D||(C=e.hiddenClass,D=x(C),C="mfp-"+C),E=f.after(D).detach().removeClass(C)),b.updateStatus("ready")}else b.updateStatus("error",e.tNotFound),f=a("<div>");return c.inlineElement=f,f}return b.updateStatus("ready"),b._parseMarkup(d,{},c),d}}});var H,I="ajax",J=function(){H&&a(document.body).removeClass(H)},K=function(){J(),b.req&&b.req.abort()};a.magnificPopup.registerModule(I,{options:{settings:null,cursor:"mfp-ajax-cur",tError:'<a href="%url%">The content</a> could not be loaded.'},proto:{initAjax:function(){b.types.push(I),H=b.st.ajax.cursor,w(h+"."+I,K),w("BeforeChange."+I,K)},getAjax:function(c){H&&a(document.body).addClass(H),b.updateStatus("loading");var d=a.extend({url:c.src,success:function(d,e,f){var g={data:d,xhr:f};y("ParseAjax",g),b.appendContent(a(g.data),I),c.finished=!0,J(),b._setFocus(),setTimeout(function(){b.wrap.addClass(q)},16),b.updateStatus("ready"),y("AjaxContentAdded")},error:function(){J(),c.finished=c.loadError=!0,b.updateStatus("error",b.st.ajax.tError.replace("%url%",c.src))}},b.st.ajax.settings);return b.req=a.ajax(d),""}}});var L,M=function(c){if(c.data&&void 0!==c.data.title)return c.data.title;var d=b.st.image.titleSrc;if(d){if(a.isFunction(d))return d.call(b,c);if(c.el)return c.el.attr(d)||""}return""};a.magnificPopup.registerModule("image",{options:{markup:'<div class="mfp-figure"><div class="mfp-close"></div><figure><div class="mfp-img"></div><figcaption><div class="mfp-bottom-bar"><div class="mfp-title"></div><div class="mfp-counter"></div></div></figcaption></figure></div>',cursor:"mfp-zoom-out-cur",titleSrc:"title",verticalFit:!0,tError:'<a href="%url%">The image</a> could not be loaded.'},proto:{initImage:function(){var c=b.st.image,d=".image";b.types.push("image"),w(m+d,function(){"image"===b.currItem.type&&c.cursor&&a(document.body).addClass(c.cursor)}),w(h+d,function(){c.cursor&&a(document.body).removeClass(c.cursor),v.off("resize"+p)}),w("Resize"+d,b.resizeImage),b.isLowIE&&w("AfterChange",b.resizeImage)},resizeImage:function(){var a=b.currItem;if(a&&a.img&&b.st.image.verticalFit){var c=0;b.isLowIE&&(c=parseInt(a.img.css("padding-top"),10)+parseInt(a.img.css("padding-bottom"),10)),a.img.css("max-height",b.wH-c)}},_onImageHasSize:function(a){a.img&&(a.hasSize=!0,L&&clearInterval(L),a.isCheckingImgSize=!1,y("ImageHasSize",a),a.imgHidden&&(b.content&&b.content.removeClass("mfp-loading"),a.imgHidden=!1))},findImageSize:function(a){var c=0,d=a.img[0],e=function(f){L&&clearInterval(L),L=setInterval(function(){return d.naturalWidth>0?void b._onImageHasSize(a):(c>200&&clearInterval(L),c++,void(3===c?e(10):40===c?e(50):100===c&&e(500)))},f)};e(1)},getImage:function(c,d){var e=0,f=function(){c&&(c.img[0].complete?(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("ready")),c.hasSize=!0,c.loaded=!0,y("ImageLoadComplete")):(e++,200>e?setTimeout(f,100):g()))},g=function(){c&&(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("error",h.tError.replace("%url%",c.src))),c.hasSize=!0,c.loaded=!0,c.loadError=!0)},h=b.st.image,i=d.find(".mfp-img");if(i.length){var j=document.createElement("img");j.className="mfp-img",c.el&&c.el.find("img").length&&(j.alt=c.el.find("img").attr("alt")),c.img=a(j).on("load.mfploader",f).on("error.mfploader",g),j.src=c.src,i.is("img")&&(c.img=c.img.clone()),j=c.img[0],j.naturalWidth>0?c.hasSize=!0:j.width||(c.hasSize=!1)}return b._parseMarkup(d,{title:M(c),img_replaceWith:c.img},c),b.resizeImage(),c.hasSize?(L&&clearInterval(L),c.loadError?(d.addClass("mfp-loading"),b.updateStatus("error",h.tError.replace("%url%",c.src))):(d.removeClass("mfp-loading"),b.updateStatus("ready")),d):(b.updateStatus("loading"),c.loading=!0,c.hasSize||(c.imgHidden=!0,d.addClass("mfp-loading"),b.findImageSize(c)),d)}}});var N,O=function(){return void 0===N&&(N=void 0!==document.createElement("p").style.MozTransform),N};a.magnificPopup.registerModule("zoom",{options:{enabled:!1,easing:"ease-in-out",duration:300,opener:function(a){return a.is("img")?a:a.find("img")}},proto:{initZoom:function(){var a,c=b.st.zoom,d=".zoom";if(c.enabled&&b.supportsTransition){var e,f,g=c.duration,j=function(a){var b=a.clone().removeAttr("style").removeAttr("class").addClass("mfp-animated-image"),d="all "+c.duration/1e3+"s "+c.easing,e={position:"fixed",zIndex:9999,left:0,top:0,"-webkit-backface-visibility":"hidden"},f="transition";return e["-webkit-"+f]=e["-moz-"+f]=e["-o-"+f]=e[f]=d,b.css(e),b},k=function(){b.content.css("visibility","visible")};w("BuildControls"+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.content.css("visibility","hidden"),a=b._getItemToZoom(),!a)return void k();f=j(a),f.css(b._getOffset()),b.wrap.append(f),e=setTimeout(function(){f.css(b._getOffset(!0)),e=setTimeout(function(){k(),setTimeout(function(){f.remove(),a=f=null,y("ZoomAnimationEnded")},16)},g)},16)}}),w(i+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.st.removalDelay=g,!a){if(a=b._getItemToZoom(),!a)return;f=j(a)}f.css(b._getOffset(!0)),b.wrap.append(f),b.content.css("visibility","hidden"),setTimeout(function(){f.css(b._getOffset())},16)}}),w(h+d,function(){b._allowZoom()&&(k(),f&&f.remove(),a=null)})}},_allowZoom:function(){return"image"===b.currItem.type},_getItemToZoom:function(){return b.currItem.hasSize?b.currItem.img:!1},_getOffset:function(c){var d;d=c?b.currItem.img:b.st.zoom.opener(b.currItem.el||b.currItem);var e=d.offset(),f=parseInt(d.css("padding-top"),10),g=parseInt(d.css("padding-bottom"),10);e.top-=a(window).scrollTop()-f;var h={width:d.width(),height:(u?d.innerHeight():d[0].offsetHeight)-g-f};return O()?h["-moz-transform"]=h.transform="translate("+e.left+"px,"+e.top+"px)":(h.left=e.left,h.top=e.top),h}}});var P="iframe",Q="//about:blank",R=function(a){if(b.currTemplate[P]){var c=b.currTemplate[P].find("iframe");c.length&&(a||(c[0].src=Q),b.isIE8&&c.css("display",a?"block":"none"))}};a.magnificPopup.registerModule(P,{options:{markup:'<div class="mfp-iframe-scaler"><div class="mfp-close"></div><iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowfullscreen></iframe></div>',srcAction:"iframe_src",patterns:{youtube:{index:"youtube.com",id:"v=",src:"//www.youtube.com/embed/%id%?autoplay=1"},vimeo:{index:"vimeo.com/",id:"/",src:"//player.vimeo.com/video/%id%?autoplay=1"},gmaps:{index:"//maps.google.",src:"%id%&output=embed"}}},proto:{initIframe:function(){b.types.push(P),w("BeforeChange",function(a,b,c){b!==c&&(b===P?R():c===P&&R(!0))}),w(h+"."+P,function(){R()})},getIframe:function(c,d){var e=c.src,f=b.st.iframe;a.each(f.patterns,function(){return e.indexOf(this.index)>-1?(this.id&&(e="string"==typeof this.id?e.substr(e.lastIndexOf(this.id)+this.id.length,e.length):this.id.call(this,e)),e=this.src.replace("%id%",e),!1):void 0});var g={};return f.srcAction&&(g[f.srcAction]=e),b._parseMarkup(d,g,c),b.updateStatus("ready"),d}}});var S=function(a){var c=b.items.length;return a>c-1?a-c:0>a?c+a:a},T=function(a,b,c){return a.replace(/%curr%/gi,b+1).replace(/%total%/gi,c)};a.magnificPopup.registerModule("gallery",{options:{enabled:!1,arrowMarkup:'<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',preload:[0,2],navigateByImgClick:!0,arrows:!0,tPrev:"Previous (Left arrow key)",tNext:"Next (Right arrow key)",tCounter:"%curr% of %total%"},proto:{initGallery:function(){var c=b.st.gallery,e=".mfp-gallery";return b.direction=!0,c&&c.enabled?(f+=" mfp-gallery",w(m+e,function(){c.navigateByImgClick&&b.wrap.on("click"+e,".mfp-img",function(){return b.items.length>1?(b.next(),!1):void 0}),d.on("keydown"+e,function(a){37===a.keyCode?b.prev():39===a.keyCode&&b.next()})}),w("UpdateStatus"+e,function(a,c){c.text&&(c.text=T(c.text,b.currItem.index,b.items.length))}),w(l+e,function(a,d,e,f){var g=b.items.length;e.counter=g>1?T(c.tCounter,f.index,g):""}),w("BuildControls"+e,function(){if(b.items.length>1&&c.arrows&&!b.arrowLeft){var d=c.arrowMarkup,e=b.arrowLeft=a(d.replace(/%title%/gi,c.tPrev).replace(/%dir%/gi,"left")).addClass(s),f=b.arrowRight=a(d.replace(/%title%/gi,c.tNext).replace(/%dir%/gi,"right")).addClass(s);e.click(function(){b.prev()}),f.click(function(){b.next()}),b.container.append(e.add(f))}}),w(n+e,function(){b._preloadTimeout&&clearTimeout(b._preloadTimeout),b._preloadTimeout=setTimeout(function(){b.preloadNearbyImages(),b._preloadTimeout=null},16)}),void w(h+e,function(){d.off(e),b.wrap.off("click"+e),b.arrowRight=b.arrowLeft=null})):!1},next:function(){b.direction=!0,b.index=S(b.index+1),b.updateItemHTML()},prev:function(){b.direction=!1,b.index=S(b.index-1),b.updateItemHTML()},goTo:function(a){b.direction=a>=b.index,b.index=a,b.updateItemHTML()},preloadNearbyImages:function(){var a,c=b.st.gallery.preload,d=Math.min(c[0],b.items.length),e=Math.min(c[1],b.items.length);for(a=1;a<=(b.direction?e:d);a++)b._preloadItem(b.index+a);for(a=1;a<=(b.direction?d:e);a++)b._preloadItem(b.index-a)},_preloadItem:function(c){if(c=S(c),!b.items[c].preloaded){var d=b.items[c];d.parsed||(d=b.parseEl(c)),y("LazyLoad",d),"image"===d.type&&(d.img=a('<img class="mfp-img" />').on("load.mfploader",function(){d.hasSize=!0}).on("error.mfploader",function(){d.hasSize=!0,d.loadError=!0,y("LazyLoadError",d)}).attr("src",d.src)),d.preloaded=!0}}}});var U="retina";a.magnificPopup.registerModule(U,{options:{replaceSrc:function(a){return a.src.replace(/\.\w+$/,function(a){return"@2x"+a})},ratio:1},proto:{initRetina:function(){if(window.devicePixelRatio>1){var a=b.st.retina,c=a.ratio;c=isNaN(c)?c():c,c>1&&(w("ImageHasSize."+U,function(a,b){b.img.css({"max-width":b.img[0].naturalWidth/c,width:"100%"})}),w("ElementParse."+U,function(b,d){d.src=a.replaceSrc(d,c)}))}}}}),A()});
;
/**
	@brief		Offer a popup SDK, based on Magnific.
	@since		2014-11-02 10:25:38
**/
broadcast_popup = function( options )
{
	$ = jQuery;

	this.$popup = undefined;
	this.$content = $( '<div>' );
	this.$title = $( '<h1>' );
	this.options = options;

	/**
		@brief		Clear the content.
		@since		2015-07-07 21:33:35
	**/
	this.clear_content = function()
	{
		this.$content.empty();
	}

	/**
		@brief		Close the popup.
		@since		2014-11-02 11:06:07
	**/
	this.close = function()
	{
		$.magnificPopup.instance.close();
		return this;
	}

	/**
		@brief		Create the div.
		@since		2014-11-02 11:03:43
	**/
	this.create_div = function()
	{
		$( '.broadcast_popup' ).remove();
		this.$popup = $( '<div>' )
			.addClass( 'mfp-hide broadcast_popup' )
			.appendTo( $( 'body' ) );
		this.$title.appendTo( this.$popup );
		this.$content.appendTo( this.$popup );
		return this;
	}

	/**
		@brief		Open the popup.
		@since		2014-11-02 11:03:33
	**/
	this.open = function()
	{
		options = $.extend( this.options,
		{
			'items' :
			{
				'overflowY' : 'scroll',
				'src' : this.$popup,
				'type' : 'inline'
			}
		}
		);

		$.magnificPopup.open( options );
		return this;
	}

	/**
		@brief		Convenience function to set the popup's HTML content.
		@since		2014-11-02 11:10:15
	**/
	this.set_content = function( html )
	{
		this.$content.html( html );
		return this;
	}

	/**
		@brief		Obsolete: Sets the popup's HTML content.
		@since		2015-07-09 14:33:40
	**/
	this.set_html = function( html )
	{
		this.$content.html( html );
		return this;
	}


	/**
		@brief		Set a header 1 for the popup.
		@since		2014-11-02 14:52:37
	**/
	this.set_title = function( title )
	{
		this.$title.html( title );
		return this;
	}

	this.create_div();
	return this;
}
;
/**
	@brief		Convert the form fieldsets in a form2 table to ajaxy tabs.
	@since		2015-07-11 19:47:46
**/
;(function( $ )
{
    $.fn.extend(
    {
        plainview_form_auto_tabs : function()
        {
            return this.each( function()
            {
                var $this = $(this);

                if ( $this.hasClass( 'auto_tabbed' ) )
                	return;

                $this.addClass( 'auto_tabbed' );

				var $fieldsets = $( 'div.fieldset', $this );
				// At least two fieldsets for this to make sense.
				if ( $fieldsets.length < 2 )
					return;

				$this.prepend( '<div style="clear: both"></div>' );
				// Create the "tabs", which are normal Wordpress tabs.
				var $subsubsub = $( '<ul class="subsubsub">' )
					.prependTo( $this );

				$.each( $fieldsets, function( index, item )
				{
					var $item = $(item);
					var $h3 = $( 'h3.title', $item );
					var $a = $( '<a href="#">' ).html( $h3.html() );
					$h3.remove();
					var $li = $( '<li>' );
					$a.appendTo( $li );
					$li.appendTo( $subsubsub );

					// We add a separator if we are not the last li.
					if ( index < $fieldsets.length - 1 )
						$li.append( '<span class="sep">&emsp;|&emsp;</span>' );

					// When clicking on a tab, show it
					$a.on( 'click', function()
					{
						$( 'li a', $subsubsub ).removeClass( 'current' );
						$(this).addClass( 'current' );
						$fieldsets.hide();
						$item.show();
					} );

				} );

				$( 'li a', $subsubsub ).first().trigger( 'click' );
            } ); // return this.each( function()
        } // plugin: function()
    } ); // $.fn.extend({
} )( jQuery );
;
/**
	@brief		Subclass for handling of post bulk actions.
	@since		2014-10-31 23:15:10
**/
;(function( $ )
{
    $.fn.extend(
    {
        broadcast_post_actions: function()
        {
            return this.each( function()
            {
                var $this = $( this );

				// Don't add bulk post options several times.
				if( $this.data( 'broadcast_post_actions' ) !== undefined )
					return;
				$this.data( 'broadcast_post_actions', true )

                $this.submitted = false;

                $this.off( 'click' );

                $this.on( 'click', function()
                {
                	// Get the post ID.
                	$tr = $this.parentsUntil( 'tbody#the-list' ).last();
                	var id = $tr.prop( 'id' ).replace( 'post-', '' );

                	$this.$popup = broadcast_popup({
                			'callbacks' : {
                				'close' : function()
                				{
                					if ( ! $this.submitted )
                						return;
                					// Reload the page by submitting the filter.
									$( '#post-query-submit' ).trigger( 'click' );
                				}
                			},
                		})
						.set_title( broadcast_strings.post_actions )
						.open();

					$this.fetch_form( {
						'action' : 'broadcast_post_action_form',
						'nonce' : $this.data( 'nonce' ),
						'post_id' : id,
					} );
                } );

                $this.display_form = function( json )
                {
					$this.$popup.set_content( json.html );

					// Take over the submit button.
					var $form = $( '#broadcast_post_action_form' );
					$( 'input.submit', $form ).on( 'click', function()
 					{
 						$this.submitted = true;
						// Assemble the form.
						$this.fetch_form( $form.serialize() + '&submit=submit' );
						return false;
					} );
                }

                /**
                	@brief		Fetch the form via ajax.
                	@since		2014-11-02 22:24:07
                **/
                $this.fetch_form = function( data )
                {
					$this.$popup.set_content( 'Loading...' );

                	// Fetch the post link editor.
                	$.ajax( {
                		'data' : data,
                		"dataType" : "json",
                		'type' : 'post',
                		'url' : ajaxurl,
                	} )
                	.done( function( data )
                	{
                		$this.display_form( data );
                	} )
					.fail( function( jqXHR )
					{
						$this.$popup
							.set_content( jqXHR.responseText )
							.set_title( 'Ajax error' );
					} );
                }
            }); // return this.each( function()
        } // plugin: function()
    }); // $.fn.extend({
} )( jQuery );
;
/**
	@brief		Handles the postbox (meta box).
	@since		2014-11-02 09:54:16
**/
;(function( $ )
{
    $.fn.extend(
    {
        broadcast_postbox: function()
        {
            return this.each( function()
            {
                var $this = $(this);

				var $blogs_container;
				var $blog_inputs;
				var $invert_selection;
				var $select_all;
				var $selection_change_container;
				var $show_hide;

				/**
					Hides all the blogs ... except those that have been selected.
				**/
				$this.hide_blogs = function()
				{
					$this.$blogs_container.removeClass( 'opened' ).addClass( 'closed' );
					$this.$show_hide.html( broadcast_strings.show_all );

					// Hide all those blogs that aren't checked
					$this.$blog_inputs.each( function( index, item )
					{
						var $input = $( this );
						var checked = $input.prop( 'checked' );
						// Ignore inputs that are supposed to be hidden.
						if ( $input.prop( 'hidden' ) === true )
							return;
						if ( ! checked )
							$input.parent().parent().hide();
					} );
				},

				/**
					Reshows all the hidden blogs.
				**/
				$this.show_blogs = function()
				{
					this.$blogs_container.removeClass( 'closed' ).addClass( 'opened' );
					this.$show_hide.html( broadcast_strings.hide_all );
					$.each( $this.$blog_inputs, function( index, item )
					{
						var $input = $( this );
						if ( $input.prop( 'hidden' ) === true )
							return;
						$input.parent().parent().show();
					} );
				}

				// If the box doesn't contain any input information, do nothing.
				if ( $( 'input', $this ).length < 1 )
					return;

				$this.$blogs_container = $( '.blogs.html_section', $this );

				// If there is no blogs selector, then there is nothing to do here.
				if ( $this.$blogs_container.length < 1 )
					return;

				$this.$blog_inputs = $( 'input.checkbox', $this.$blogs_container );

				// Container for selection change.
				$this.$selection_change_container = $( '<div />' )
					.addClass( 'clear selection_change_container howto' );

				switch( broadcast_blog_selector_position )
				{
					case 'top':
						$this.$selection_change_container.prependTo( $this.$blogs_container );
					break;
					default:
						$this.$selection_change_container.appendTo( $this.$blogs_container );
				}

				// Append "Select all / none" text.
				$this.$select_all = $( '<span />' )
					.addClass( 'selection_change select_deselect_all' )
					.on( 'click', function()
					{
						var checkedStatus = ! $this.$blog_inputs.first().prop( 'checked' );
						$this.$blog_inputs.each( function(index, item)
						{
							var $item = $( item );
							// Only change the status of the blogs that aren't disabled.
							if ( $item.prop( 'disabled' ) != true )
								$item.prop( 'checked', checkedStatus );
						} );
					})
					.html( broadcast_strings.select_deselect_all )
					.appendTo( $this.$selection_change_container );

				$this.$selection_change_container.append( '&emsp;' );

				$this.$invert_selection = $( '<span />' )
					.on( 'click', function()
					{
						$this.$blog_inputs.each( function(index, item)
						{
							var $item = $( item );
							var checked = $item.prop( 'checked' );
							$item.prop( 'checked', ! checked );
						} );
					})
					.addClass( 'selection_change invert_selection' )
					.text( broadcast_strings.invert_selection )
					.appendTo( $this.$selection_change_container );

				// Need to hide the blog list?
				try
				{
					if ( broadcast_blogs_to_hide )
						true;
				}
				catch( e )
				{
					broadcast_blogs_to_hide = 5;
				}

				if ( $this.$blog_inputs.length > broadcast_blogs_to_hide )
				{
					$this.$show_hide = $( '<div />' )
						.addClass( 'show_hide howto' )
						.prependTo( $this.$blogs_container )
						.on( 'click', function()
						{
							if ( $this.$blogs_container.hasClass( 'opened' ) )
								$this.hide_blogs();
							else
								$this.show_blogs();
						} );

					$this.hide_blogs();
				}

				// GROUP functionality: Allow blogs to be mass selected, unselected.
				$( ".blog_groups select", $this ).on( 'change', function()
				{
					var $groups = $( this );
					var blogs = $groups.val().split(' ');
					for ( var counter=0; counter < blogs.length; counter++)
					{
						var $blog = $( "#plainview_sdk_broadcast_form2_inputs_checkboxes_blogs_" + blogs[counter], $this.$blogs_container );
						$blog.trigger( 'click' );
					}

					// If the blog list is closed, then expand and then close again to show the newly selected blogs.
					if ( $this.$blogs_container.hasClass( 'closed' ) )
						$this.$show_hide.trigger( 'click' ).trigger( 'click' );
				} ).change();

				// Unchecked child blogs
				var $unchecked_child_blogs_div = $( ".form_item_plainview_sdk_broadcast_form2_inputs_select_unchecked_child_blogs", $this ).hide();
				var $unchecked_child_blogs = $( "select", $unchecked_child_blogs );

				$( ".blogs.checkboxes .linked input", $this ).on( 'change',  function()
				{
					var $this = $( this );
					var checked = $this.is( ':checked' );

					// Show the uncheck select.
					if ( ! checked )
					{
						$unchecked_child_blogs_div.show();
					}
					else
					{
						// We can only hide it if all linked blogs are checked.
						var unchecked = $( ".blogs.checkboxes .linked input:not(:checked)", $this ).length == 0;
						if ( unchecked )
							$unchecked_child_blogs_div.hide();
					}
				} );


            } ); // return this.each( function()
        } // plugin: function()
    } ); // $.fn.extend({
} )( jQuery );
;
/**
	@brief		Subclass for handling of post bulk actions.
	@since		2014-10-31 23:15:10
**/
;(function( $ )
{
    $.fn.extend(
    {
        broadcast_post_bulk_actions: function()
        {
            return this.each( function()
            {
                var $this = $( this );

                /**
					@brief		Mark the bulkactions section as busy.
					@since		2014-11-01 23:43:52
				**/
				$this.busy = function( busy )
				{
					if ( busy )
						$( '.bulkactions' ).fadeTo( 250, 0.5 );
					else
						$( '.bulkactions' ).fadeTo( 250, 1 );
				}

				/**
					@brief		Return a string with all of the selected post IDs.
					@since		2014-10-31 23:15:48
				**/
				$this.get_ids = function()
				{
					var post_ids = [];
					// Get all selected rows
					var $inputs = $( '#posts-filter tbody#the-list th.check-column input:checked' );
					$.each( $inputs, function( index, item )
					{
						var $item = $( item );
						var $row = $( item ).parentsUntil( 'tr' ).parent();
						// Add it
						var id = $row.prop( 'id' ).replace( 'post-', '' );
						post_ids.push( id );
					} );
					return post_ids.join( ',' );
				}

				if ( typeof broadcast_bulk_post_actions === "undefined" )
					return;

				// Don't add bulk post options several times.
				if( $this.data( 'broadcast_post_bulk_actions' ) !== undefined )
					return;
				$this.data( 'broadcast_post_bulk_actions', true )

				// Begin by adding the broadcast optgroup.
				var $select = $( '.bulkactions select' );
				var $optgroup = $( '<optgroup>' );

				$.each( broadcast_bulk_post_actions, function( index, item )
				{
					var $option = $( '<option>' );
					$option.html( item.name );
					$option.prop( 'value', index );
					$option.addClass( 'broadcast' );
					$option.appendTo( $optgroup );
				} );

				// We appendTo here because otherwise it is only put in one place.
				$optgroup.prop( 'label', broadcast_strings.broadcast );
				$optgroup.appendTo( $select );

				// We use this to prevent the popup from activating twice on ACF forms.
				$this.data( 'popup_active', false );

				// Take over the apply buttons
				$( '.button.action' )
				.on( 'click', function()
				{
					// What is the current selection?
					var $container = $( this ).parent();
					var $select = $( 'select', $container );

					var $selected = $( 'option:selected', $select );

					// Not a broadcast bulk post action = allow the button to work normally.
					if ( ! $selected.hasClass( 'broadcast' ) )
						return true;

					// Has the user selected any posts?
					var post_ids = $this.get_ids();
					if ( post_ids == '' )
					{
						broadcast_popup()
							.set_title( 'No posts selected' )
							.set_content( 'Please select at least one post to use the Broadcast bulk actions.' )
							.open();
						return false;
					}

					if ( $this.data( 'popup_active' ) == true )
						return false;

					$this.data( 'popup_active', true );

					// Retrieve the action.
					var value = $selected.prop( 'value' );
					var action = broadcast_bulk_post_actions[ value ];
					// Use the callback.
					$this.busy( true );
					action.callback( $this );

					setTimeout( function()
					{
						$this.data( 'popup_active', false );
					}, 1000 );

					return false;
				} );

            }); // return this.each( function()
        } // plugin: function()
    }); // $.fn.extend({
} )( jQuery );
;
jQuery(document).ready( function( $ )
{
	$( '#threewp_broadcast.postbox' ).broadcast_postbox();
	$( '#posts-filter' ).broadcast_post_bulk_actions();
	$( '#posts-filter td.3wp_broadcast a.broadcast.post' ).broadcast_post_actions();
	$( '.plainview_form_auto_tabs' ).plainview_form_auto_tabs();
} );
;
