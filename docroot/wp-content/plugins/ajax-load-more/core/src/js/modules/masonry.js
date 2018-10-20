/*
	almMasonry
	Function to trigger built-in Ajax Load More Masonry

   @param container        object
   @param items            object
   @param selector         string
   @param columnWidth      string
   @param animation        string
   @param horizontalOrder  string
   @param speed            int
   @param masonry_init     boolean
   @param init             boolean
   @param filtering        boolean   
   
   @since 3.1
   @updated 3.3.2
*/


let almMasonry = (container, items, selector, columnWidth, animation, horizontalOrder, speed, masonry_init, init, filtering) => {	
   
   let duration = (speed+100)/1000 +'s'; // Add 100 for some delay
   let hidden = 'scale(0.5)';
   let visible = 'scale(1)';
   
   if(animation === 'zoom-out'){
      hidden = 'translateY(-20px) scale(1.25)'; 
      visible = 'translateY(0) scale(1)';
   }
   
   if(animation === 'slide-up'){
      hidden = 'translateY(50px)';
      visible = 'translateY(0)';
   } 
   
   if(animation === 'slide-down'){
      hidden = 'translateY(-50px)';
      visible = 'translateY(0)';
   }  
    
   if(animation === 'none'){
      hidden = 'translateY(0)';  
      visible = 'translateY(0)';
   }
   
   // columnWidth
   if(columnWidth){
	   if(!isNaN(columnWidth)){// Check if number
		   columnWidth = parseInt(columnWidth);
		}
   } else { // No columnWidth, use the selector
	   columnWidth = selector;
   }
   
   // horizontalOrder
   horizontalOrder = (horizontalOrder === 'true') ? true : false;
   
	if(!filtering){
   	
		// First Run
		if(masonry_init && init){
			container.imagesLoaded( () => {
				
				let defaults = {
					itemSelector: selector,
					transitionDuration: duration,
					columnWidth: columnWidth,
					horizontalOrder: horizontalOrder,
               hiddenStyle: {
                  transform: hidden,
                  opacity: 0
               },
               visibleStyle: {
                  transform: visible,
                  opacity: 1
               } 
            }
            
            // Get custom Masonry options (https://masonry.desandro.com/options.html)
            let alm_masonry_vars = alm_masonry_vars;
            if(alm_masonry_vars){
		         Object.keys(alm_masonry_vars).forEach(function(key) {	// Loop object	to create key:prop			
						defaults[key] = alm_masonry_vars[key];					
					});
				}				
            
            // Trigger Masonry()		
				container.masonry(defaults);
				
				// Fade in
				almMasonryFadeIn(container[0].parentNode, speed); 
				
			});
		}
		
		// Standard
		else{
			items.imagesLoaded( () => {
				container.append(items).masonry( 'appended', items );
			});
		}

	} else{
		// Filtering Reset
		container.masonry('destroy'); // destroy masonry
		container[0].parentNode.style.opacity = 0;
		container.append( items );
		almMasonry(container, items, selector, columnWidth, animation, horizontalOrder, speed, true, true, false);
	}

};


// Fade in masonry on initial page load
let almMasonryFadeIn = (element, speed) => {
	speed = speed/10;
	let op = parseInt(element.style.opacity);  // initial opacity
	let timer = setInterval(function () { 
		if (op > 0.9){
			element.style.opacity = 1;
			clearInterval(timer);
		}
		element.style.opacity = op;
		op += 0.1;
	}, speed);
}
