<?php
/**
 * Page to preview form
 */

$sib_form_id = isset($_GET['sib_form']) ? sanitize_text_field($_GET['sib_form']) : '';
$sib_preview = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

wp_head();

?>
<body style="background-color: #f5f5f5;">
    <div id="page" class="site" style="padding:16px;">
        <div id="sib-preview-form">
        <?php
        if($sib_preview == '') {
            $formData = SIB_Forms::getForm($sib_form_id);
        } else {
            $formData = get_option(SIB_Manager::PREVIEW_OPTION_NAME, array());
        }
        
        $selectCaptchaTypeVal = isset($formData['selectCaptchaType']) ? $formData['selectCaptchaType'] : "";
        $cCaptchaStyle = isset($formData['cCaptchaStyle']) ? $formData['cCaptchaStyle'] : "";
        
        if (isset($formData['gCaptcha']) && $formData['gCaptcha'] != 0) {
            if (isset($selectCaptchaTypeVal) && !in_array($selectCaptchaTypeVal, [1, 3])) {
                if( '1' == $formData['gCaptcha'] ) {   // For old forms.
                    $formData['html'] = preg_replace( '/([\s\S]*?)<div class="g-recaptcha"[\s\S]*?data-size="invisible"><\/div>/', '$1', $formData['html'] );
                }
                if ( '3' == $formData['gCaptcha'] ) {     // The case of using google recaptcha.
                    ?>
                    <script type="text/javascript">
                        var onloadSibCallback = function () {
                            grecaptcha.render('sib_captcha', {
                                'sitekey': '<?php echo esc_attr($formData["gCaptcha_site"]) ?>'
                            });
                        };
                    </script>
                <?php
                }
                else {                                  // The case of using google invisible recaptcha.
                ?>
                    <script type="text/javascript">
                        var onloadSibCallback = function() {
                            var element = document.getElementsByClassName('sib-default-btn');
                            grecaptcha.render(element[0],{
                                'sitekey' : '<?php echo esc_attr( $formData["gCaptcha_site"] ) ?>',
                                'callback' : sibVerifyCallback
                            });
                        };
                    </script>
                <?php
                }
                ?>
                <script src="https://www.google.com/recaptcha/api.js?onload=onloadSibCallback&render=explicit" async defer></script>
                <?php
                    $html = stripslashes_deep($formData['html']);
                    $css = stripslashes_deep($formData['css']);
                    // phpcs:ignore
                    echo wp_kses($html, SIB_Manager::wordpress_allowed_attributes());
                    ?>
                <?php
            } else if ($selectCaptchaTypeVal == 3) {     
                    $html = stripslashes_deep($formData['html']);
                    $css = stripslashes_deep($formData['css']);
                    // phpcs:ignore
                    echo wp_kses($html, SIB_Manager::wordpress_allowed_attributes());
                ?>
                <script type="text/javascript"  async defer>                
                    var siteKey = jQuery('.cf-turnstile').data("sitekey");
                    function _turnstileCb() {
                        turnstile.render('.cf-turnstile', {
                            sitekey: siteKey,
                            theme: <?php echo $cCaptchaStyle; ?>,
                            callback: function(token) {
                                console.log("Challenge Success");
                            },
                        });
                    }
                </script>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=_turnstileCb"></script>
            <?php }
        } else { 
                $html = stripslashes_deep($formData['html']);
                $css = stripslashes_deep($formData['css']);
                // phpcs:ignore
                echo wp_kses($html, SIB_Manager::wordpress_allowed_attributes());
            } ?>
        
        </div>
        <style>
            <?php
                if($formData['dependTheme'] != '1'){
                    $css = str_replace('[form]', '#sib-preview-form', $css);
                    echo sanitize_text_field( $css );
                }
            ?>
        </style>
    </div>
</body>
