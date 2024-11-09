/* eslint-disable */

/*
 * Progress Bar
 *
 * @description
 * Show a progress bar in the header on scroll of an article.
 *
 */
export default function() {
  window.progressArticle = document.querySelector('article') !== null;

  // Check to make sure an article is present.
  if (window.progressArticle) {
    let progressArticleIsIntersecting = false;
    const progressBar = document.querySelector('.js-progress-bar progress');

    const articleObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            progressArticle = entry.target;
            progressArticleIsIntersecting = true;
          }
        });
      },
      {
        // Fire when comes into view.
        threshold: 0,
      }
    );

    // OBSERVE ARTICLES
    [...document.querySelectorAll('article')].forEach(el => {
      articleObserver.observe(el);
    })

    // CATCH STACKS
    const body = document.querySelector('body');
    const mObserver = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.target != body) {
          [...mutation.target.querySelectorAll('article')].forEach(el => {
            articleObserver.observe(el);
          });
        }
      });
    });

    // Trigger observer with options.
    mObserver.observe(body, {
      childList: true,
      subtree: true,
    });

    // THROTTLING THIS LOOKS BAD
    document.addEventListener('scroll', function() {
      if (progressArticle) {
        let width = (1 - ((progressArticle.getBoundingClientRect().bottom - window.innerHeight) / progressArticle.offsetHeight)) * 100;
        progressBar.setAttribute('value', width);
      }
    });

    function throttle(callback, limit) {
      var wait = false;
      return function() {
        if (!wait) {
          callback.call();
          wait = true;
          setTimeout(function() {
            wait = false;
          }, limit);
        }
      }
    }
  }
}
