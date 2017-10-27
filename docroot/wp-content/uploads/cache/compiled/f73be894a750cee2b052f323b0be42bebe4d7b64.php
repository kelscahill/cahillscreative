<?php $__env->startSection('content'); ?>
  <?php while(have_posts()): ?> <?php (the_post()); ?>
    <?php echo $__env->make('partials.content-page', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <section class="section section__process padding--double-bottom">
      <div class="section--inner layout-container narrow narrow--m spacing--double">
        <div class="step">
          <div class="step-item sticky-parent">
            <div class="step-item__number sticky"><span class="font--primary--xs color--gray">Step</span></div>
            <div class="step-item__content spacing">
              <h2 class="font--primary--s">Discovery</h2>
              <p>We’ll talk, we’ll laugh, I’ll tell a knock-knock joke that makes us both uncomfortable and then we’ll get into the nitty-gritty. This phase of my process sets the foundation for your entire project. You’ll gather as much information about me as I do about you to allow both of us to make informed and cohesive decisions throughout the length of your project. After the initial discussions, I’ll present you with a proposal outlining costs as well as deliverables.</p>
            </div>
          </div>
          <div class="step-item sticky-parent">
            <div class="step-item__number sticky"><span class="font--primary--xs color--gray">Step</span></div>
            <div class="step-item__content spacing">
              <h2 class="font--primary--s">Planning</h2>
              <p>Once we take the plunge and decide to work together, we’ll dive head first into strategizing. Your project needs a plan that’s definitive, creative, innovative, all the –ives. Great design won’t produce the results you want without an underlying framework of logic and strategy.</p>
            </div>
          </div>
          <div class="step-item sticky-parent">
            <div class="step-item__number sticky"><span class="font--primary--xs color--gray">Step</span></div>
            <div class="step-item__content spacing">
              <h2 class="font--primary--s">Design</h2>
              <p>Ready. Set. Create! At this point, all my juices are flowing – creative, strategic, apple (have to stay hydrated) – to design the perfect piece or pieces for your project. Don’t worry, I keep you updated along the way so you never have to wonder how things are progressing.</p>
            </div>
          </div>
          <div class="step-item sticky-parent">
            <div class="step-item__number sticky"><span class="font--primary--xs color--gray">Step</span></div>
            <div class="step-item__content spacing">
              <h2 class="font--primary--s">Delivery</h2>
              <p>Once I make my official presentation of concepts, you will either approve a direction or suggest some changes. Either way, we keep moving forward until the piece moves from concept to reality. No matter the type of project, this is the stage when all the heavy-lifting occurs and, unlike my pizza guy, I actually deliver when I say I am going to.</p>
            </div>
          </div>
          <div class="step-item sticky-parent">
            <div class="step-item__number sticky"><span class="font--primary--xs color--gray">Step</span></div>
            <div class="step-item__content spacing">
              <h2 class="font--primary--s">Follow Up</h2>
              <p>Like that time your mother-in-law came to visit, I never truly leave. After providing you final files for your project, I still remain open for communication. Need a different logo file? Want to make some text or photo updates to your website? Looking for some marketing materials that match your new brand? No matter the project, I love helping clients with follow up work.</p>
            </div>
          </div>
        </div>
        <a href="/contact" class="btn btn--center">Start a Project</a>
      </div>
    </section>
    <section class="section section__faqs padding--double-top padding--double-bottom background-color--white">
      <div class="section--inner layout-container narrow narrow--l spacing--double">
        <div class="section__header text-align--center">
          <h3 class="font--primary--s">FAQ's</h3>
          <hr class="divider" />
          <h2 class="font--primary--xl">You have questions. I have answers.</h2>
        </div>
        <div class="accordion spacing">
          <div class="accordion--inner space--half-bottom">
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">How much do your projects cost?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>It’s difficult to put a price on a project before I know the parameters. I prefer to provide you with a custom quote based on your project’s individual goals. <a href="/contact" alt="Contact Me">Contact me</a> directly if you’d like to discuss pricing further.</p>
              </div>
            </div>
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">What are your typical turnaround times?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>Every project moves at different speeds, but is based on a tried-and-true method for completion. Check out <a href="http://cahillscreative.com/about#process" alt="My Process">my&nbsp;process</a> for more information on this topic.</p>
              </div>
            </div>
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">Do you help with printing?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>I sure do. I work with some great printers who always produce great work.</p>
              </div>
            </div>
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">What do you need from me to start the project?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>A signed agreement, a 50% deposit and any relevant files.</p>
              </div>
            </div>
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">Are your initial deposits refundable?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>All deposits are nonrefundable. In my experience, clients who move forward without some type of deposit oftentimes drop off before a project is finished, or are not as invested as those who do. Your deposit not only reserves your project’s spot ahead of other viable projects, but it also ensures that everyone involved is motivated to keep things moving forward.</p>
              </div>
            </div>
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">How do you accept payment?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>All projects require a 50% down payment prior to starting the job. This is payable by your choice of methods: bank transfer, PayPal, or check, in U.S. dollars. The remaining 50% is due upon completion of the project, prior to release of files. Depending on the duration of the project, I may allow for installment payments. All final details are included in our agreement.</p>
              </div>
            </div>
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">Do you provide hosting for the websites you design?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>Hosting requires a much larger server than is feasible for me to maintain, but I can certainly point you in the direction of a number of great hosting services and even help guide you through the process of selecting which package is best for you. That being said, Cahill’s Creative is not responsible for server downtime, software issues or any other compatibility issue that may arise after the launch of the site.</p>
              </div>
            </div>
            <div class="accordion-item">
              <div class="accordion-item__title js-toggle-parent">
                <h4 class="font--primary--s">Do you have office hours?</h4>
                <span class="accordion-item__toggle spacing--zero"></span>
              </div>
              <div class="accordion-item__body article__body spacing padding--zero">
                <p>I am available Monday – Friday from 9am – 5pm EST and can be reached via email, telephone, carrier pigeon, etc. I typically respond anywhere from 24 to 72 business hours after I receive your email or phone call, depending on my current work load. For more in-depth discussions I prefer to schedule calls ahead of time in order to keep from having to cut our conversation short (and so I can properly set my DVR, if need be).</p>
              </div>
            </div>
          </div>
        </div>
        <p class="text-align--center">Don't hesitate to reach out with any other questions or just to say hi.</p>
        <a href="/contact" class="btn btn--center">Contact</a>
      </div>
    </section>
  <?php endwhile; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>