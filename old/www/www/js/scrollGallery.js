
// MooTools: the javascript framework.
// Load this file's selection again by visiting: http://mootools.net/more/777f20ee39b438de94ffb908da026aef 
// Or build this file again with packager using: packager build More/Fx.Scroll More/Scroller
/*
---
copyrights:
  - [MooTools](http://mootools.net)

licenses:
  - [MIT License](http://mootools.net/license.txt)
...
*/
MooTools.More={version:"1.3.2.1",build:"e586bcd2496e9b22acfde32e12f84d49ce09e59d"};(function(){Fx.Scroll=new Class({Extends:Fx,options:{offset:{x:0,y:0},wheelStops:true},initialize:function(c,b){this.element=this.subject=document.id(c);
this.parent(b);if(typeOf(this.element)!="element"){this.element=document.id(this.element.getDocument().body);}if(this.options.wheelStops){var d=this.element,e=this.cancel.pass(false,this);
this.addEvent("start",function(){d.addEvent("mousewheel",e);},true);this.addEvent("complete",function(){d.removeEvent("mousewheel",e);},true);}},set:function(){var b=Array.flatten(arguments);
if(Browser.firefox){b=[Math.round(b[0]),Math.round(b[1])];}this.element.scrollTo(b[0],b[1]);return this;},compute:function(d,c,b){return[0,1].map(function(e){return Fx.compute(d[e],c[e],b);
});},start:function(c,d){if(!this.check(c,d)){return this;}var b=this.element.getScroll();return this.parent([b.x,b.y],[c,d]);},calculateScroll:function(g,f){var d=this.element,b=d.getScrollSize(),h=d.getScroll(),j=d.getSize(),c=this.options.offset,i={x:g,y:f};
for(var e in i){if(!i[e]&&i[e]!==0){i[e]=h[e];}if(typeOf(i[e])!="number"){i[e]=b[e]-j[e];}i[e]+=c[e];}return[i.x,i.y];},toTop:function(){return this.start.apply(this,this.calculateScroll(false,0));
},toLeft:function(){return this.start.apply(this,this.calculateScroll(0,false));},toRight:function(){return this.start.apply(this,this.calculateScroll("right",false));
},toBottom:function(){return this.start.apply(this,this.calculateScroll(false,"bottom"));},toElement:function(d,e){e=e?Array.from(e):["x","y"];var c=a(this.element)?{x:0,y:0}:this.element.getScroll();
var b=Object.map(document.id(d).getPosition(this.element),function(g,f){return e.contains(f)?g+c[f]:false;});return this.start.apply(this,this.calculateScroll(b.x,b.y));
},toElementEdge:function(d,g,e){g=g?Array.from(g):["x","y"];d=document.id(d);var i={},f=d.getPosition(this.element),j=d.getSize(),h=this.element.getScroll(),b=this.element.getSize(),c={x:f.x+j.x,y:f.y+j.y};
["x","y"].each(function(k){if(g.contains(k)){if(c[k]>h[k]+b[k]){i[k]=c[k]-b[k];}if(f[k]<h[k]){i[k]=f[k];}}if(i[k]==null){i[k]=h[k];}if(e&&e[k]){i[k]=i[k]+e[k];
}},this);if(i.x!=h.x||i.y!=h.y){this.start(i.x,i.y);}return this;},toElementCenter:function(e,f,h){f=f?Array.from(f):["x","y"];e=document.id(e);var i={},c=e.getPosition(this.element),d=e.getSize(),b=this.element.getScroll(),g=this.element.getSize();
["x","y"].each(function(j){if(f.contains(j)){i[j]=c[j]-(g[j]-d[j])/2;}if(i[j]==null){i[j]=b[j];}if(h&&h[j]){i[j]=i[j]+h[j];}},this);if(i.x!=b.x||i.y!=b.y){this.start(i.x,i.y);
}return this;}});function a(b){return(/^(?:body|html)$/i).test(b.tagName);}})();var Scroller=new Class({Implements:[Events,Options],options:{area:20,velocity:1,onChange:function(a,b){this.element.scrollTo(a,b);
},fps:50},initialize:function(b,a){this.setOptions(a);this.element=document.id(b);this.docBody=document.id(this.element.getDocument().body);this.listener=(typeOf(this.element)!="element")?this.docBody:this.element;
this.timer=null;this.bound={attach:this.attach.bind(this),detach:this.detach.bind(this),getCoords:this.getCoords.bind(this)};},start:function(){this.listener.addEvents({mouseover:this.bound.attach,mouseleave:this.bound.detach});
return this;},stop:function(){this.listener.removeEvents({mouseover:this.bound.attach,mouseleave:this.bound.detach});this.detach();this.timer=clearInterval(this.timer);
return this;},attach:function(){this.listener.addEvent("mousemove",this.bound.getCoords);},detach:function(){this.listener.removeEvent("mousemove",this.bound.getCoords);
this.timer=clearInterval(this.timer);},getCoords:function(a){this.page=(this.listener.get("tag")=="body")?a.client:a.page;if(!this.timer){this.timer=this.scroll.periodical(Math.round(1000/this.options.fps),this);
}},scroll:function(){var c=this.element.getSize(),a=this.element.getScroll(),h=this.element!=this.docBody?this.element.getOffsets():{x:0,y:0},d=this.element.getScrollSize(),g={x:0,y:0},e=this.options.area.top||this.options.area,b=this.options.area.bottom||this.options.area;
for(var f in this.page){if(this.page[f]<(e+h[f])&&a[f]!=0){g[f]=(this.page[f]-e-h[f])*this.options.velocity;}else{if(this.page[f]+b>(c[f]+h[f])&&a[f]+c[f]!=d[f]){g[f]=(this.page[f]-c[f]+b-h[f])*this.options.velocity;
}}g[f]=g[f].round();}if(g.y||g.x){this.fireEvent("change",[a.x+g.x,a.y+g.y]);}}});

/*
--- 
description: scrollGallery

authors: 
- Benedikt Morschheuser (http://software.bmo-design.de)

license:
- MIT-style license

requires:
- core/1.3.1: '*'
- more/1.3.1: 'Fx.Scroll, Scroller'

provides: [scrollGallery]

...
*/
var scrollGallery = null;

(function($){
    
	scrollGallery = new Class({
		
		Implements: [Events, Options],
	  
		options: {
			'start': 1,
			'area': 200,
			'thumbarea': 'thumbarea',
			'imagearea': 'imagearea',
			'speed': 0.1,
			'clickable': true,
			'autoScroll': false,
			'diashowDelay': null,
			'toElementClass': null,
			'enableSwipeMode': false,
			'thumbOpacity': 100
			/* Events...*/
		},
	  
		initialize: function(options){
			this.setOptions(options);		
			Scroller.implement(new Events, new Options);
			
			this.tumbObjs=null;
			this.imgObjs=null;
			
			this.activeImgNum=0;
			
			//Workaround
			if((Browser.safari||Browser.chrome)&&this.safariWorkaround!=true){
				this.safariWorkaround=true;
				
				window.addEvent('load', function() {
				  this.initialize.delay(10, this, options);
				}.bind(this));
			}
			//FX
			if($(this.options.imagearea)){
				this.scrollimageareaFx = new Fx.Scroll(this.options.imagearea,{wheelStops: false});
			}
			//AutoScroll
			//init Thumb-Images
			if($(this.options.thumbarea)){
				// init tumbObjs
				this.tumbObjs = $(this.options.thumbarea).getElements('img');
				if($(this.options.imagearea)){
					Array.each(this.tumbObjs, function(imgObjekt, index){
						imgObjekt.addEvent('click', function(index){
							this.scrollToImg(index);
						}.bind(this).pass(index));
					
						if(index==this.options.start){
							imgObjekt.fireEvent('click',this,10);//delay for safari
						}
					}.bind(this));
				}
				//thumbOpacity
				if(this.options.thumbOpacity.toInt()<100){
					if(this.options.thumbOpacity.toInt()<=0) 
						this.options.thumbOpacity=1;
					if(this.options.thumbOpacity.toInt()<10)
						this.options.thumbOpacity='0'+this.options.thumbOpacity.toInt().toString();
					Array.each(this.tumbObjs, function(thumbObjekt, index){
						if(this.activeImgNum==index){
							thumbObjekt.set('opacity','1');
						}else{
							thumbObjekt.set('opacity','.' + this.options.thumbOpacity);
						}
					}.bind(this));
				}
				//scrollEvents
				if($(this.options.thumbarea).getScrollSize().x>$(this.options.thumbarea).getSize().x){//check if scrollable
					if(this.options.autoScroll==false){
						if(this.options.enableSwipeMode&&Browser.Features.Touch){//mobile version
							//do nothing - user can swipe
						}else{
							this.scrollthumbareaFx = new Scroller($(this.options.thumbarea), {area: this.options.area, velocity: this.options.speed, direction: "x"});
							$(this.options.thumbarea).setStyle('overflow-x', 'hidden');
							// Thumb Events
							$(this.options.thumbarea).addEvent('mouseenter', this.scrollthumbareaFx.start.bind(this.scrollthumbareaFx));
							$(this.options.thumbarea).addEvent('mouseleave', this.scrollthumbareaFx.stop.bind(this.scrollthumbareaFx));
						}
					}else{
						$(this.options.thumbarea).setStyle('overflow-x', 'hidden');
						var scrollSize = $(this.options.thumbarea).getScrollSize();
						var scrollTo = scrollSize.x;
						var cloneContainer =  this.tumbObjs[0].getParent("div");
						if($(this.options.thumbarea).getElements('img').length==this.tumbObjs.length){/*if not already cloned*/
							Array.each(this.tumbObjs, function(imgObjekt, index){
								if(imgObjekt.getParent().tagName!="DIV"&&imgObjekt.getParent().tagName!="div"){
									var theClone=imgObjekt.getParent().clone();
									cloneContainer.adopt(theClone);
									theClone.getElement('img').cloneEvents(imgObjekt);
								}else{
									cloneContainer.adopt(imgObjekt.clone().cloneEvents(imgObjekt));
								}
							}.bind(this));
						
							this.scrollthumbareaFx = new Fx.Scroll(this.options.thumbarea,{
								'duration': 300*this.options.speed*scrollSize.x/2,
								'transition': Fx.Transitions.linear, 
								'link': 'ignore',
								onComplete: function(){
									this.scrollthumbareaFx.set(0,0);
									this.scrollthumbareaFx.start(scrollTo,'x');
								}.bind(this),
								onCancel: function(){
									var scrollTo = $(this.options.thumbarea).getScrollSize().x;
									this.scrollthumbareaFx.start(scrollTo,'x');
								}.bind(this),
								'wheelStops': false
							});
							this.scrollthumbareaFx.set(0,0);
							this.scrollthumbareaFx.start(scrollTo,'x');
							$(this.options.thumbarea).addEvent('mouseenter', function(){//little backup fix
								var scrollTo = $(this.options.thumbarea).getScrollSize().x;
								this.scrollthumbareaFx.start(scrollTo,'x');
							}.bind(this));	
						}
					}
				}else{
					this.options.autoScroll=false;
				}
			}else{
				console.error('Missing thumbarea');
			}
			//init Images
			if($(this.options.imagearea)){
				$(this.options.imagearea).setStyle('overflow', 'hidden');
				$(this.options.imagearea).setStyle('overflow-x', 'hidden');
				// init imgObjs
				if(this.options.toElementClass!=null){
					this.imgObjs=$(this.options.imagearea).getElements(this.options.toElementClass.toString());
				}else{
					this.imgObjs=$(this.options.imagearea).getElements('img');
				}
				if(this.options.clickable){
					$(this.options.imagearea).getElement('div').setStyle('cursor', 'pointer');
					Array.each(this.imgObjs, function(imgObjekt, index){
						imgObjekt.addEvent('click', function(index){
							this.scrollToImg(index+1);
						}.bind(this).pass(index));
					}.bind(this));
				}
				if(this.options.enableSwipeMode&&Browser.Features.Touch){//mobile version optional
					$(this.options.imagearea).removeEvents('swipe');
					$(this.options.imagearea).addEvent('swipe', function(event){
						if(event.direction=='right'){
							this.prev();
						}else{
							this.next();
						}
					}.bind(this));
				}
				this.initDiashow();
			}else{
				console.error('Missing imagearea');
			}
	
			//check
			if(this.imgObjs&&this.tumbObjs)
				if(this.imgObjs.length!=this.tumbObjs.length) 
					console.error("Error: The number of images does not match!");
		},
		initDiashow: function(){
			if(this.options.diashowDelay!=null&&this.options.diashowDelay>=1){
				clearInterval(this.diashow);
				this.diashow = this.next.periodical(this.options.diashowDelay*1000,this);
			}
		},
		scrollToImg: function(index){
			if(index>=this.imgObjs.length)
				index=0;						
			if(this.options.thumbOpacity.toInt()<100&&$(this.options.thumbarea)){
				this.tumbObjs[index].set('opacity','1');
				if(index!=this.activeImgNum)
					this.tumbObjs[this.activeImgNum].set('opacity','.' + this.options.thumbOpacity);
				if(this.options.autoScroll!=false){
					var allclonedtumbObjs = $(this.options.thumbarea).getElements('img');
					if(allclonedtumbObjs.length!=this.tumbObjs.length){
						allclonedtumbObjs[(this.tumbObjs.length+index)].set('opacity','1');
						if(index!=this.activeImgNum)
							allclonedtumbObjs[(this.tumbObjs.length+this.activeImgNum)].set('opacity','.' + this.options.thumbOpacity);
					}
				}
			}
			this.scrollimageareaFx.toElement(this.imgObjs[index]);
			this.activeImgNum=index;
			this.initDiashow();	//reinit to reset time
		},
		next: function(){
			this.scrollToImg(this.activeImgNum+1);
		},
		prev: function(){
			this.scrollToImg(this.activeImgNum-1);
		}
	});
})(document.id);