function isMobile() {
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        return true;
    }
    return false;
}

if (isMobile()) {
	$('body').addClass('notouch');
}

// Stellar - Parallax Scrolling Backgrounds
$(window).stellar();

$('#like').click(function() {
	$('#output').html(function(i, val) { return val*1+1 });
});


// Animations - inview
$('.inviewable').one('inview', function(event, isInView, visiblePartX, visiblePartY) {
	var now = new Date();
	var el = $(event.target);
	  if(isInView) {
	  if(!el.hasClass('inview'))
	  {
		el.addClass("inview");
		el.data('debounce', now.getTime() );        
	  }
	} else {

	  if(now.getTime() - el.data('debounce') > 1000)
	{
	  el.removeClass("inview");
	}
  }
});

// Smooth Scroll
$('a[href*=#]:not([href=#])').click(function() {
	if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') 
		|| location.hostname == this.hostname) {

		var target = $(this.hash);
		target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
		   if (target.length) {
			 $('html,body').animate({
				 scrollTop: target.offset().top
			}, 1000);
			return false;
		}
	}
});

// Row Height
// jQuery(function(jQuery) {
//   jQuery('.blog-content').responsiveEqualHeightGrid();  
// });   

// Hover on Work Grid
$(".work-grid a").hover(function() {
	$(this).children(".overlay").stop( true, true ).fadeIn("fast");
}).mouseleave(function() {
	$(this).children(".overlay").stop( true, true ).fadeOut("fast");
});


// Add Active Class to Navigation
$(function() {
  $('nav a[href^="/' + location.pathname.split("/")[1] + '"]').addClass('active');
});

// Mobile Menu Trigger
$(".trigger").click(function() {
	$(".overlay-scale").addClass("open");
});

$(".overlay-close").click(function() {
	$(".overlay-scale").removeClass("open");
});

///////////////
//// FORMS ////
///////////////
$.validator.addMethod("email", function(value, element) {
	return this.optional(element) || /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/.test(value);
}, "Please enter a valid email address");

$(document).ready(function() {   
	$("#contact-form").validate({
		rules: {
			name: {
				required:true
			},
			phone: {
				required:true
			},
			email: {
				required:true,
				email:true
			},
			budget: {
				required:true
			}
		},
		errorPlacement: function (error, element) {
			$(".error-container").show();
			error.insertAfter(element);
		},
		submitHandler: function(form) {
			$(".error-container").hide();
			form.submit();
		}
	});
});

// $("#contact-form").validate({
//   success: "valid",
//   submitHandler: function() { alert("Thank you! We'll be in touch soon.") }
// });


