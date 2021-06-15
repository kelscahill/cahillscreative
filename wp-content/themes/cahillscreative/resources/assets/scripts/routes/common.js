/* eslint-disable */
import inView from 'in-view';
import header from '../util/header';
import progressBar from '../util/progress-bar';
import slick from '../util/slick.min.js';
import magnificPopup from '../util/magnific-popup.min.js';

export default {
  init() {
    // JavaScript to be fired on all pages

    header();

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

    // Copy link on button click.
    $('.js-copy-link').click(function(e) {
      e.preventDefault();
      var $temp = $("<input>");
      var $url = $(this).attr('href');
      $("body").append($temp);
      $temp.val($url).select();
      document.execCommand("copy");
      $temp.remove();
      $(this).text('Link Copied!');
    });

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
    $('.js-slick').slick({
      dots: true,
      autoplay: true,
      autoplaySpeed: 3000,
      arrows: false,
      infinite: true,
      speed: 300,
      fade: true,
      cssEase: 'linear',
      adaptiveHeight: false,
    });

    $('.js-slick-posts').slick({
      dots: false,
      infinite: false,
      speed: 300,
      slidesToShow: 4,
      slidesToScroll: 4,
      responsive: [
        {
          breakpoint: 720,
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
          breakpoint: 320,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1,
          }
        }
      ]
    });

    $('.js-slick-products').slick({
      prevArrow: '<span class="o-icon--arrow o-icon--arrow-prev"></span>',
      nextArrow: '<span class="o-icon--arrow o-icon--arrow-next"></span>',
      dots: false,
      infinite: false,
      speed: 300,
      slidesToShow: 4,
      slidesToScroll: 4,
      responsive: [
        {
          breakpoint: 720,
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
          breakpoint: 320,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1,
          }
        }
      ]
    });

    /**
    * Magnigic Popup
    */
    if ($('.js-gallery').length) {
      $('.js-gallery').each(function() {
       $(this).magnificPopup({
         delegate: 'a',
         type: 'image',
         gallery: {
           enabled: true
         }
       });
      });
    }

    /**
     * General helper function to support toggle functions.
     */
    var toggleClasses = function(element) {
      var $this = element,
          $togglePrefix = $this.data('prefix') || 'this';

      // If the element you need toggled is relative to the toggle, add the
      // .js-this class to the parent element and "this" to the data-toggled attr.
      if ($this.data('toggled') == "this") {
        var $toggled = $this.closest('.js-this');
      }
      else {
        var $toggled = $('.' + $this.data('toggled'));
      }
      if ($this.attr('aria-expanded', 'true')) {
        $this.attr('aria-expanded', 'true')
      }
      else {
        $this.attr('aria-expanded', 'false')
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
      e.preventDefault();
      e.stopPropagation();
      toggleClasses($(this));
    });

    // Toggle parent class
    $('.js-toggle-parent').on('click', function(e) {
      e.preventDefault();
      var $this = $(this);
      $this.toggleClass('this-is-active');
      $this.parent().toggleClass('this-is-active');
    });

    // Prevent bubbling to the body. Add this class to the element (or element
    // container) that should allow the click event.
    $('.js-stop-prop').on('click', function(e) {
      e.stopPropagation();
    });

    // Toggle hovered classes
    $('.js-hover').on('mouseenter mouseleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      toggleClasses($(this));
    });

    $('.js-hover-parent').on('mouseenter mouseleave', function(e) {
      e.preventDefault();
      var $this = $(this);
      $this.toggleClass('this-is-active');
      $this.parent().toggleClass('this-is-active');
    });
  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};
