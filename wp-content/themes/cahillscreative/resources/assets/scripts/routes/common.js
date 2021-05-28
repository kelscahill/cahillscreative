/* eslint-disable */
import inView from 'in-view';

export default {
  init() {
    // JavaScript to be fired on all pages

    // Add class if is mobile
    function isMobile() {
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        return true;
      }
      return false;
    }
    // Add class if is mobile
    if (isMobile()) {
      $('html').addClass(' touch');
    } else if (!isMobile()){
      $('html').addClass(' no-touch');
    }

    /**
    * Add inview class on scroll if has-animation class.
    */
    if (!isMobile()) {
      inView('.js-inview').on('enter', function() {
        $("*[data-animation]").each(function() {
          var animation = $(this).attr('data-animation');
          if (inView.is(this)) {
            $(this).addClass("is-inview");
            $(this).addClass(animation);
          }
        });
      });
    }

    /**
    * Remove Active Classes when clicking outside menus and modals
    */
    // $(document).click(function(event) {
    //   if (!$(event.target).closest(".c-nav-drawer").length) {
    //     $("html").find(".menu-is-active").removeClass("menu-is-active");
    //   }
    // });

    // Expires after one day
    var setCookie = function(name, value) {
      var date = new Date(),
          expires = 'expires=';
      date.setDate(date.getDate() + 1);
      expires += date.toGMTString();
      document.cookie = name + '=' + value + '; ' + expires + '; path=/; SameSite=Strict;';
    }

    var getCookie = function(name) {
      var allCookies = document.cookie.split(';'),
        cookieCounter = 0,
        currentCookie = '';
      for (cookieCounter = 0; cookieCounter < allCookies.length; cookieCounter++) {
        currentCookie = allCookies[cookieCounter];
        while (currentCookie.charAt(0) === ' ') {
          currentCookie = currentCookie.substring(1, currentCookie.length);
        }
        if (currentCookie.indexOf(name + '=') === 0) {
          return currentCookie.substring(name.length + 1, currentCookie.length);
        }
      }
      return false;
    }

    $('.js-alert-close').click(function(e) {
      e.preventDefault();
      $('.js-alert').addClass('is-hidden');
      setCookie('alert', 'true');
    });

    var showAlert = function() {
      $('.js-alert').fadeIn();
      $('.js-alert').removeClass('is-hidden');
    }

    var hideAlert = function() {
      $('.js-alert').fadeOut();
      $('.js-alert').addClass('is-hidden');
    }

    if (getCookie('alert')) {
      hideAlert();
    } else {
      showAlert();
    }

    // Smooth scrolling on anchor clicks
    $(function() {
      $('a[href*="#"]:not([href="#"])').click(function() {
        $('.c-primary-nav, body').removeClass('primary-nav-is-active');
        if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
          var target = $(this.hash);
          target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
          if (target.length) {
            $('html, body').animate({
              scrollTop: target.offset().top - 50
            }, 1000);
            return false;
          }
        }
      });
    });

    /**
     * Slick sliders
     */
    $('.slick').slick({
      prevArrow: '<span class="icon--arrow icon--arrow-prev"></span>',
      nextArrow: '<span class="icon--arrow icon--arrow-next"></span>',
      dots: false,
      autoplay: false,
      arrows: true,
      infinite: true,
      speed: 250,
      fade: true,
      cssEase: 'linear',
      adaptiveHeight: true,
    });

    $('.slick-gallery').slick({
      prevArrow: '<span class="icon--arrow icon--arrow-prev"></span>',
      nextArrow: '<span class="icon--arrow icon--arrow-next"></span>',
      dots: true,
      autoplay: false,
      arrows: true,
      infinite: true,
      speed: 250,
      fade: true,
      cssEase: 'linear',
      adaptiveHeight: true,
      slidesToShow: 1,
      slidesToScroll: 1,
    });

    $('.slick-favorites').slick({
      prevArrow: '<span class="icon--arrow icon--arrow-prev"></span>',
      nextArrow: '<span class="icon--arrow icon--arrow-next"></span>',
      dots: false,
      infinite: false,
      speed: 300,
      slidesToShow: 4,
      slidesToScroll: 4,
      responsive: [
        {
          breakpoint: 700,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 3,
          }
        },
        {
          breakpoint: 500,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 2,
          }
        },
        {
          breakpoint: 375,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1,
          }
        }
      ]
    });
    /**
     * Main class toggling function
     */
    var $toggled = '';
    var toggleClasses = function(element) {
      var $this = element,
          $togglePrefix = $this.data('prefix') || 'this';

      // If the element you need toggled is relative to the toggle, add the
      // .js-this class to the parent element and "this" to the data-toggled attr.
      if ($this.data('toggled') === "this") {
        $toggled = $this.parents('.js-this');
      }
      else {
        $toggled = $('.' + $this.data('toggled'));
      }

      $this.toggleClass($togglePrefix + '-is-active');
      $toggled.toggleClass($togglePrefix + '-is-active');

      // Remove a class on another element, if needed.
      if ($this.data('remove')) {
        $('.' + $this.data('remove')).removeClass($this.data('remove'));
      }
    };

    /*
     * Toggle Active Classes
     *
     * @description:
     *  toggle specific classes based on data-attr of clicked element
     *
     * @requires:
     *  'js-toggle' class and a data-attr with the element to be
     *  toggled's class name both applied to the clicked element
     *
     * @example usage:
     *  <span class="js-toggle" data-toggled="toggled-class">Toggler</span>
     *  <div class="toggled-class">This element's class will be toggled</div>
     *
     */
    $('.js-toggle').on('click', function(e) {
      e.stopPropagation();
      toggleClasses($(this));
    });

    // Toggle parent class
    $('.js-toggle-parent').on('click', function(e) {
      e.preventDefault();
      var $this = $(this);

      $this.parent().toggleClass('is-active');
    });

    // Toggle hovered classes
    $('.js-hover').on('mouseenter mouseleave', function(e) {
      e.preventDefault();
      toggleClasses($(this));
    });

    $('.js-hover-parent').on('mouseenter mouseleave', function(e) {
      e.preventDefault();
      toggleClasses($(this).parent());
    });

  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};
