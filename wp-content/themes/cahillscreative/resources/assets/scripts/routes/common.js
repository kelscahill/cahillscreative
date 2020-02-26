/* eslint-disable */

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

    // check window width
    var getWidth = function() {
      var width;
      if (document.body && document.body.offsetWidth) {
        width = document.body.offsetWidth;
      }
      if (document.compatMode === 'CSS1Compat' &&
          document.documentElement &&
          document.documentElement.offsetWidth ) {
         width = document.documentElement.offsetWidth;
      }
      if (window.innerWidth) {
         width = window.innerWidth;
      }
      return width;
    };
    window.onload = function() {
      getWidth();
    };
    window.onresize = function() {
      getWidth();
    };

    $('.primary-nav--with-subnav.js-toggle > a').click(function(e) {
      e.preventDefault();
    });

    // Popup
    $('.popup__close').click(function(e) {
      e.preventDefault();
      $('#popup-container').addClass('popup--hide');
    });

    $('.popup .btn').click(function() {
      $('#popup-container').addClass('popup--hide');
    });

    // $(window).load(function(){
    //   function show_popup(){
    //     var visits = $.cookie('visits') || 0;
    //     visits++;
    //     $.cookie('visits', visits, { expires: 7, path: '/' });
    //
    //     if ($.cookie('visits') <= 1) {
    //       $('#popup-container').fadeIn();
    //       $('#popup-container').removeClass('popup--hide');
    //     }
    //   };
    //    window.setTimeout( show_popup, 2000 ); // 2 seconds
    // });

    // Smooth scrolling on anchor clicks
    $(function() {
      $('a[href*="#"]:not([href="#"])').click(function() {
        $('.nav__primary, .nav-toggler').removeClass('main-nav-is-active');
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
     * Share Tooltip
     */
    $(document).on('click', '.tooltip-toggle', function() {
      $(this).parent().addClass('is-active');
      $('.overlay').show();
    });

    $('.tooltip-close').click(function() {
      $(this).parent().parent().removeClass('is-active');
      $('.overlay').hide();
    });

    $('.overlay').click(function() {
      $(this).hide();
      $('.tooltip').removeClass('is-active');
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
