/* eslint-disable */

export default {
  init() {
    // JavaScript to be fired on all pages

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
