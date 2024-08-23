var $jQ = jQuery.noConflict();
var ajax_sib_object;
var CountryList = {
    213: "DZ",
    376: "AD",
    54: "AR",
    971: "AE",
    43: "AT",
    61: "AU",
    387: "BA",
    880: "BD",
    32: "BE",
    359: "BG",
    973: "BH",
    590: "BL",
    55: "BR",
    1: "CA",
    41: "CH",
    56: "CL",
    86: "CN",
    57: "CO",
    420: "CZ",
    49: "DE",
    45: "DK",
    593: "EC",
    372: "EE",
    20: "EG",
    34: "ES",
    358: "FI",
    33: "FR",
    44: "GB",
    995: "GE",
    30: "GR",
    852: "HK",
    385: "HR",
    509: "HT",
    36: "HU",
    62: "ID",
    353: "IE",
    972: "IL",
    91: "IN",
    98: "IR",
    39: "IT",
    1876: "JM",
    962: "JO",
    81: "JP",
    269: "KM",
    961: "LB",
    94: "LK",
    370: "LT",
    352: "LU",
    371: "LV",
    212: "MA",
    261: "MG",
    356: "MT",
    230: "MU",
    52: "MX",
    60: "MY",
    687: "NC",
    234: "NG",
    505: "NI",
    31: "NL",
    47: "NO",
    977: "NP",
    64: "NZ",
    507: "PA",
    51: "PE",
    689: "PF",
    675: "PG",
    63: "PH",
    92: "PK",
    48: "PL",
    508: "PM",
    1787: "PR",
    351: "PT",
    595: "PY",
    974: "QA",
    40: "RO",
    7: "RU",
    46: "SE",
    65: "SG",
    386: "SI",
    421: "SK",
    66: "TH",
    216: "TN",
    90: "TR",
    886: "TW",
    380: "UA",
    256: "UG",
    1: "US",
    598: "UY",
    58: "VE",
    84: "VN",
    681: "WF",
    262: "YT",
    27: "ZA",
};
$jQ(document).ready(function(){

    var bodyHeight = $jQ(document).height();
    var adminmenu_height = $jQ('#adminmenuwrap').height();
    if(bodyHeight > adminmenu_height){
        $jQ("#datamain").height(bodyHeight);
    }
    else
    {
        $jQ("#datamain").height(adminmenu_height);
    }


    var normal_attributes = [];

    var category_attributes = [];

    function isValidEmailAddress(emailAddress) {

        var pattern = new RegExp(/^[#&*\/=?^{!}~'_a-z0-9-\+]+([#&*\/=?^{!}~'_a-z0-9-\+]+)*(\.[#&*\/=?^{!}~'_a-z0-9-\+]+)*[.]?@[_a-z0-9-]+(\.[_a-z0-9-]+)*(\.[a-z0-9]{2,10})$/);
        return pattern.test(emailAddress);
    }

    function change_field_attr(){
        var attr_val = $jQ('#sib_sel_attribute').val();
        var attr_type, attr_name, attr_text;
        if (attr_val == 'email' || attr_val == 'submit') {
            // get all info of attr
            var hidden_attr = $jQ('#sib_hidden_' + attr_val);
            attr_type = hidden_attr.attr('data-type');
            attr_name = hidden_attr.attr('data-name');
            attr_text = hidden_attr.attr('data-text');
        }
        else {
            $jQ.each(normal_attributes, function(index, value) {
                if (value['name'] == attr_val) {
                    attr_type = value['type'];
                    attr_name = value['name'];
                    attr_text = attr_name;
                }
            });

            $jQ.each(category_attributes, function(index, value) {
                if (value['name'] == attr_val) {
                    attr_type = value['type'];
                    attr_name = value['name'];
                    attr_text = attr_name;
                }
            });
        }

        // generate attribute html
        generate_attribute_html(attr_type, attr_name, attr_text);
    }

    function change_attribute_tag(attr_type, attr_name, attr_text){
        $jQ('#sib_field_label').attr('value', attr_text);
        $jQ('#sib_field_placeholder').attr('value', '');
        $jQ('#sib_field_initial').attr('value', '');
        $jQ('#sib_field_button_text').attr('value', attr_text);
        $jQ('.sib-attr-other').hide();
        $jQ('.sib-attr-normal').hide();
        $jQ('.sib-attr-category').hide();
        $jQ('#sib_field_required').removeAttr('checked');
        var dateformat = $jQ('.sib-dateformat').val();
        switch(attr_type)
        {
            case 'email':
                $jQ('#sib_field_required').attr('checked', 'true');
                dateformat = '';
            case 'date':
                $jQ('#sib_field_placeholder').val(dateformat);
            case 'text':
            case 'float':
                $jQ('.sib-attr-normal').show();
                if(attr_name == 'SMS'){
                    $jQ('#sib_field_initial_area').hide();
                }
                break;
            case 'boolean':
                $jQ('.sib-attr-normal').show();
                break;
            case 'category':
                $jQ('.sib-attr-category').show();
                break;
            case 'submit':
                $jQ('.sib-attr-other').show();
                break;
        }
    }

    // generate attribute html
    function generate_attribute_html(attr_type, attr_name, attr_text){
        var field_label = $jQ('#sib_field_label').val();
        var field_placeholder = $jQ('#sib_field_placeholder').val();
        var field_initial = $jQ('#sib_field_initial').val();
        var field_buttontext = $jQ('#sib_field_button_text').val();
        //var field_wrap = $jQ('#sib_field_wrap').is(':checked');
        var field_required = $jQ('#sib_field_required').is(':checked');
        if(field_required == true) field_label += '*';
        var field_type = $jQ('input[name=sib_field_type]:checked').val();
        var dateformat = $jQ('.sib-dateformat').val();
        var field_html = '';

        if(attr_type != 'submit') {
            field_html += '<p class="sib-' + attr_name + '-area"> \n';
        }
        else {
            field_html += '<p> \n';
        }

        if ((field_label != '') && (attr_type == 'category')) {
            if (field_type == 'select') {
                field_html += '    <label class="sib-' + attr_name + '-area">' + field_label + '</label> \n';
            }
            else {
                field_html += '    <div style="display:block;"><label class="sib-' + attr_name + '-area">' + field_label + '</label></div> \n';
            }
        }
        else if((field_label != '') && (attr_type != 'submit')) {
            field_html += '    <label class="sib-' + attr_name + '-area">' + field_label + '</label> \n';
        }

        switch (attr_type)
        {
            case 'email':
                field_html += '    <input type="email" class="sib-' + attr_name + '-area" name="' + attr_name + '" ';
                field_html += 'placeholder="' + field_placeholder + '" ';
                field_html += 'value="' + field_initial + '" ';
                if(field_required == true) {
                    field_html += 'required="required" ';
                }
                field_html += '> \n';
                break;
            case 'date':
                field_html += '    <input type="text" class="sib-' + attr_name + '-area sib-date" name="' + attr_name + '" placeholder="' + dateformat + '" data-format="' + dateformat + '">';
                break;
            case 'boolean':
                field_html += '  <input type="hidden" name="' + attr_name + '" value="0"><input type="checkbox" value="1" class="sib-' + attr_name + '-area" name="' + attr_name + '" ';
                if(field_placeholder != '') {
                    field_html += 'placeholder="' + field_placeholder + '" ';
                }
                if(field_required == true) {
                    field_html += 'required="required" ';
                }
                field_html += '> \n';
                break;
            case 'text':
                if ( attr_name == "SMS" ) {
                    field_html += '<div class="sib-sms-field"><div class="sib-country-block">' +
                        '<div class="sib-toggle sib-country-flg"><div class="sib-cflags"></div> ' +
                        '<div class="sib-icon-arrow"></div></div> '+
                        '</div>' + '<ul class="sib-country-list" style="display: none;"></ul>' +
                        '<input type="hidden" name="sib_SMS_prefix" value="+33"><input type="text" name="SMS" class="sib-sms" value="+33" ';
                    if(field_placeholder != '') {
                        field_html += 'placeholder="' + field_placeholder + '" ';
                    }
                    if(field_required == true) {
                        field_html += 'required="required" ';
                    }
                    field_html += '></div>\n';
                }
                else {
                    field_html += '    <input type="text" class="sib-' + attr_name + '-area" name="' + attr_name + '" ';
                    if(field_placeholder != '') {
                        field_html += 'placeholder="' + field_placeholder + '" ';
                    }
                    if(field_initial != '') {
                        field_html += 'value="' + field_initial + '" ';
                    }
                    if(field_required == true) {
                        field_html += 'required="required" ';
                    }
                    field_html += '> \n';
                }
                break;
            case 'float':
                field_html += '    <input type="text" class="sib-' + attr_name + '-area" name="' + attr_name + '" ';
                if(field_placeholder != '') {
                    field_html += 'placeholder="' + field_placeholder + '" ';
                }
                if(field_initial != '') {
                    field_html += 'value="' + field_initial + '" ';
                }
                if(field_required == true) {
                    field_html += 'required="required" ';
                }
                field_html += 'pattern="[0-9]+([\\.|,][0-9]+)?" > \n';
                break;
            case 'submit':
                field_html += '    <input type="submit" class="sib-default-btn" name="' + attr_name + '" ';
                field_html += 'value="' + field_buttontext + '" ';
                field_html += '> \n';
                break;
            case 'category':
                var enumeration = [];
                $jQ.each(category_attributes, function(index, value) {
                    if (value['name'] == attr_name) {
                        enumeration = value['enumeration'];
                    }
                });

                if (field_type == 'select') {
                    field_html += '    <select class="sib-' + attr_name + '-area" name="' + attr_name + '" ';
                    if (field_required == true) {
                        field_html += 'required="required" ';
                    }
                    field_html += '> \n';
                }
                $jQ.each(enumeration, function(index, value) {
                    if (field_type == 'select') {
                        field_html += '      <option value="' + value['value'] + '">' + value['label'] + '</option> \n';
                    }
                    else {
                        field_html += '    <div style="display:block;"><input type="radio" class="sib-' + attr_name + '-area" name="' + attr_name + '" value="' + value['value'] + '" ';
                        if (field_required == true) {
                            field_html += 'required="required" ';
                        }
                        field_html += '>' + value['label'] + '</div> \n';
                    }
                });
                if (field_type == 'select') {
                    field_html += '    </select> \n';
                }
                break;
        }

        field_html += '</p>';
        $jQ('#sib_field_html').html(field_html);
    }

    function set_select_list() {
        var selected_list_id = $jQ('#sib_selected_list_id').val();

        var data = {
            frmid : $jQ('input[name=sib_form_id]').val(),
            action : 'sib_get_lists',
            security: ajax_sib_object.ajax_nonce
        };
        $jQ.post(ajax_sib_object.ajax_url, data, function(respond) {
            var select_html = '';
            var selected = respond.selected;

            $jQ.each(respond.lists, function(index, value) {
                if(value['name'] == 'Temp - DOUBLE OPTIN') return true;
                if ( selected.indexOf(value['id'].toString()) != '-1' ) {
                    select_html += '<option value="' + value['id'] + '" selected>' + value['name'] + '</option>';
                }
                else {
                    select_html += '<option value="' + value['id'] + '">' + value['name'] + '</option>';
                }
            });
            $jQ('#sib_select_list').html(select_html).trigger("chosen:updated");

            set_select_attributes();

        });
    }

    function set_select_template() {
        var selected_template_id = $jQ('#sib_selected_template_id').val();
        var selected_do_template_id = $jQ('#sib_selected_do_template_id').val();
        var selected_confirm_template_id = $jQ('#sib_selected_confirm_template_id').val();
        var default_template_name = $jQ('#sib_default_template_name').val();
        var data = {
            action : 'sib_get_templates',
            security: ajax_sib_object.ajax_nonce
        };
        $jQ.post(ajax_sib_object.ajax_url, data, function(respond) {
            var select_html = '<select id="sib_template_id" class="col-md-11" name="template_id">';
            if (selected_template_id == '-1') {
                select_html += '<option value="-1" selected>' + default_template_name + '</option>';
            }
            else {
                select_html += '<option value="-1">' + default_template_name + '</option>';
            }
            $jQ.each(respond.templates, function(index, value) {
                if (value['id'] == selected_template_id) {
                    select_html += '<option value="' + value['id'] + '" selected>' + value['name'] + '</option>';
                }
                else if (!value['is_dopt']) {
                    select_html += '<option value="' + value['id'] + '">' + value['name'] + '</option>';
                }
            });
            select_html += '</select>';
            $jQ('#sib_template_id_area').html(select_html);

            // For double optin.
            select_html = '<select class="col-md-11" name="doubleoptin_template_id" id="sib_doubleoptin_template_id">';
            if (selected_do_template_id == '-1') {
                select_html += '<option value="-1" selected>' + default_template_name + '</option>';
            }
            else {
                select_html += '<option value="-1">' + default_template_name + '</option>';
            }
            $jQ.each(respond.templates, function(index, value) {
                if (value['id'] == selected_do_template_id) {
                    select_html += '<option is_shortcode="' + value['is_dopt']  + '" value="' + value['id'] + '" selected>' + value['name'] + '</option>';
                }
                else if (value['is_dopt']) {
                    select_html += '<option is_shortcode="' + value['is_dopt']  + '" value="' + value['id'] + '">' + value['name'] + '</option>';
                }
            });
            select_html += '</select>';
            $jQ('#sib_doubleoptin_template_id_area').html(select_html);

            // For final confirmation emait template
            select_html = '<select id="sib_confirm_template_id" class="col-md-11" name="confirm_template_id">';
            if (selected_confirm_template_id == '-1') {
                select_html += '<option value="-1" selected>' + default_template_name + '</option>';
            }
            else {
                select_html += '<option value="-1">' + default_template_name + '</option>';
            }

            $jQ.each(respond.templates, function(index, value) {
                if (value['id'] == selected_confirm_template_id) {
                    select_html += '<option is_shortcode="' + value['is_dopt']  + '" value="' + value['id'] + '" selected>' + value['name'] + '</option>';
                }
                else if (!value['is_dopt']) {
                    select_html += '<option is_shortcode="' + value['is_dopt']  + '" value="' + value['id'] + '">' + value['name'] + '</option>';
                }
            });
            select_html += '</select>';
            $jQ('#sib_final_confirm_template_id_area').html(select_html);

            // double optin template id
            $jQ('#sib_doubleoptin_template_id').on('change', function() {
                var shortcode_exist = $jQ(this).find(':selected').attr('is_shortcode');
                if (shortcode_exist == 0 && $jQ(this).val() != -1) {
                    $jQ('#sib_form_alert_message').show();
                    $jQ('#sib_disclaim_smtp').hide();
                    $jQ('#sib_disclaim_confirm_template').hide();
                    $jQ('#sib_disclaim_do_template').show();
                    $jQ(this).val('-1');
                }
                else {
                    $jQ('#sib_form_alert_message').hide();
                }
            });

            // Final confirm template id
            $jQ('#sib_confirm_template_id').on('change', function() {
                var shortcode_exist = $jQ(this).find(':selected').attr('is_shortcode');
                if (shortcode_exist == 1 && $jQ(this).val() != -1) {
                    $jQ('#sib_form_alert_message').show();
                    $jQ('#sib_disclaim_smtp').hide();
                    $jQ('#sib_disclaim_confirm_template').show();
                    $jQ('#sib_disclaim_do_template').hide();
                    $jQ(this).val('-1');
                }
                else {
                    $jQ('#sib_form_alert_message').hide();
                }
            });

            $jQ('#sib_setting_signup_spin').addClass('hide');
           
        });
    }

    function set_select_attributes() {
        var data = {
            action : 'sib_get_attributes',
            security: ajax_sib_object.ajax_nonce
        };

        $jQ.post(ajax_sib_object.ajax_url, data, function(respond) {

            var iframWidth = $jQ('.form-field').width() - 48;
            $jQ('#sib-preview-form').width(iframWidth);

            normal_attributes = respond.attrs.attributes.normal_attributes;
            category_attributes = respond.attrs.attributes.category_attributes;
            var attr_email_name = $jQ('#sib_hidden_email').attr('data-text');
            var message_1 = $jQ('#sib_hidden_message_1').val();
            var message_2 = $jQ('#sib_hidden_message_2').val();
            var message_3 = $jQ('#sib_hidden_message_3').val();
            var message_4 = $jQ('#sib_hidden_message_4').val();
            var message_5 = $jQ('#sib_hidden_message_5').val();
            var select_html = '<select class="col-md-12" id="sib_sel_attribute">' +
                '<option value="-1" disabled selected>' + message_1 + '</option>' +
                '<optgroup label="' + message_2 + '">';
            select_html += '<option value="email">' + attr_email_name + '*</option>';
            $jQ.each(normal_attributes, function(index, value) {
                select_html += '<option value="' + value['name'] + '">' + value['name'] + '</option>';
            });
            select_html += '</optgroup>';
            select_html += '<optgroup label="' + message_3 + '">';
            $jQ.each(category_attributes, function(index, value) {
                if(value['name'] == 'DOUBLE_OPT-IN') return;
                select_html += '<option value="' + value['name'] + '">' + value['name'] + '</option>';
            });
            select_html += '</optgroup>';
            select_html += '<optgroup label="' + message_4 + '">';
            select_html += '<option value="submit">' + message_5 + '</option>';
            select_html += '</optgroup>';
            select_html += '</select>';

            $jQ('#sib_sel_attribute_area').html(select_html);
            $jQ('#sib_sel_attribute').on('change', function() {
                //
                $jQ('#sib-field-content').show();

                var attr_val = $jQ(this).val();
                var attr_type, attr_name, attr_text;
                if (attr_val == 'email' || attr_val == 'submit') {
                    // get all info of attr
                    var hidden_attr = $jQ('#sib_hidden_' + attr_val);
                    attr_type = hidden_attr.attr('data-type');
                    attr_name = hidden_attr.attr('data-name');
                    attr_text = hidden_attr.attr('data-text');
                }
                else {
                    $jQ.each(normal_attributes, function(index, value) {
                        if (value['name'] == attr_val) {
                            attr_type = value['type'];
                            attr_name = value['name'];
                            attr_text = attr_name;
                        }
                    });

                    $jQ.each(category_attributes, function(index, value) {
                        if (value['name'] == attr_val) {
                            attr_type = value['type'];
                            attr_name = value['name'];
                            attr_text = attr_name;
                        }
                    });
                }
                // change attribute tags
                change_attribute_tag(attr_type, attr_name, attr_text);

                // generate attribute html
                generate_attribute_html(attr_type, attr_name, attr_text);
            });
            $jQ('#sib_setting_form_spin').addClass('hide');
            set_select_template();
        });
    }

    function update_preview(){

        var selectCaptchaType = $jQ('.sib-captcha-select').val();
        var frmid = $jQ('#sib_form_id').val();
        var formHtml = $jQ('#sibformmarkup').val();
        var formCss = $jQ('#sibcssmarkup').val();
        var isDepend = $jQ('input[name=sib_css_type]:checked').val();
        if (selectCaptchaType == 3) {
            var cCaptcha = $jQ('input[name=sib_add_captcha]:checked').val();
            var cCaptchaSite = $jQ('#sib_captcha_site_turnstile').val();
            var data = {
                action:'sib_update_form_html',
                security: ajax_sib_object.ajax_nonce,
                frmid: frmid,
                frmData: formHtml,
                frmCss: formCss,
                isDepend: isDepend,
                selectCaptchaType: selectCaptchaType,
                gCaptcha: cCaptcha,
                cCaptchaSite: cCaptchaSite
            };
        } else if (selectCaptchaType != 3) {
            var gCaptcha = $jQ('input[name=sib_add_captcha]:checked').val();
            var gCaptchaType = $jQ('input[name=sib_recaptcha_type]:checked').val();
            var gCaptchaSite = $jQ('#sib_captcha_site').val();
            var data = {
                action:'sib_update_form_html',
                security: ajax_sib_object.ajax_nonce,
                frmid: frmid,
                frmData: formHtml,
                frmCss: formCss,
                isDepend: isDepend,
                gCaptcha: gCaptcha,
                gCaptchaType: gCaptchaType,
                gCaptchaSite: gCaptchaSite,
                selectCaptchaType: selectCaptchaType,
            };
        }
        $jQ.post(ajax_sib_object.ajax_url, data,function() {
            var preview_form = $jQ('#sib-preview-form');
            preview_form.attr('src', preview_form.attr('src') + '&action=update');
        });
        
    }
    // get cursor posistion of text area
    function get_cursor_position(node) {
        //node.focus();
        /* without node.focus() IE will returns -1 when focus is not on node */
        if(node.selectionStart) return node.selectionStart;
        else if(!document.selection) return 0;
        var c       = "\001";
        var sel = document.selection.createRange();
        var dul = sel.duplicate();
        dul.moveToElementText(node);
        sel.text    = c;
        var len     = (dul.text.indexOf(c));
        sel.moveStart('character',-1);
        sel.text    = "";
        return len;
    }
    // set cursor position at top of text area
    function setSelectionRange(input, selectionStart, selectionEnd) {
        if (input.setSelectionRange) {
            input.focus();
            input.setSelectionRange(selectionStart, selectionEnd);
        } else if (input.createTextRange) {
            var range = input.createTextRange();
            range.collapse(true);
            range.moveEnd('character', selectionEnd);
            range.moveStart('character', selectionStart);
            range.select();
        }
    }

    // generate html for multi subscription lists
    function generate_multi_list_html() {
        var field_label = $jQ('#sib_multi_field_label').val();
        var field_html = '<p class="sib-multi-lists-area">\n';
        var list_id = '';
        var list_name = '';
        var required = false;
        var required_label = '';
        var required_attr = '';
        if ( $jQ('#sib_multi_field_required').is(":checked"))
        {
            required = true;
            required_label = '*';
            required_attr = 'required';
        }
        if ( field_label != '' )
        {
            field_html += '<label>' + field_label + required_label + '</label>\n';
        }

        field_html += '<div class="sib-multi-lists" data-require="' + required_attr + '">\n';
        var selected_lists = $jQ('#sib_select_multi_list').find('option:selected', this);
        selected_lists.each(function(){
            list_id = $jQ(this).val();
            list_name = $jQ(this).data('list');
            field_html += '<div style="block"><input type="checkbox" class="sib-interesting-lists" value="' + list_id + '" name="listIDs[]">' + list_name + '</div>\n';
        });
        field_html += '</div></p>';
        $jQ('#sib_multi_field_html').html(field_html);
    }
    /////////////////////////////////
    /*       home settings         */
    /////////////////////////////////

    // var elements
    var sib_access_key = $jQ('#sib_access_key');
    var sib_validate_btn = $jQ('#sib_validate_btn');

    // validate button click process in welcome page
    sib_validate_btn.on('click', function(){

        var access_key = sib_access_key.val();

        // check validation
        var error_flag = 0;
        if(access_key == '') {
            sib_access_key.addClass('error');
            error_flag =1;
        }

        if(error_flag != 0) {
            return false;
        }

        // ajax process for validate
        var data = {
            action:'sib_validate_process',
            access_key: access_key,
            security: ajax_sib_object.ajax_nonce
        };

        $jQ('.sib_alert').hide();
        $jQ('.sib-spin').show();
        sib_access_key.removeClass('error');
        $jQ(this).attr('disabled', 'true');

        $jQ.post(ajax_sib_object.ajax_url, data, function(respond) {
            $jQ('.sib-spin').hide();
            sib_validate_btn.removeAttr('disabled');
            if(respond == 'success') {
                $jQ('#success-alert').show();
                /*var cur_url = $jQ('#cur_refer_url').val();
                window.location.href = cur_url;*/
                window.location.reload();
            }
            else if (respond == 'curl_no_installed') {
                sib_access_key.addClass('error');
                $jQ('#failure-alert').html($jQ('#curl_no_exist_error').val()).show();
            }
            else if (respond == 'curl_error') {
                sib_access_key.addClass('error');
                $jQ('#failure-alert').html($jQ('#curl_error').val()).show();
            }           
            else {
                sib_access_key.addClass('error');
                $jQ('#failure-alert').html(respond).show();
            }
        });
    });

    sib_access_key.on('keypress', function(){
        $jQ(this).removeClass('error');
    });

    // Transactional emails
    $jQ('input[name=activate_email]').on('click', function(){
        var option_val = $jQ(this).val();
        var data = {
            action: 'sib_activate_email_change',
            option_val: option_val,
            security: ajax_sib_object.ajax_nonce
        };
        $jQ.post(ajax_sib_object.ajax_url, data, function(respond) {
            if(respond == 'yes')
                $jQ('#email_send_field').show();
            else
                $jQ('#email_send_field').hide();
        });

        return true;
    });

    // change sender detail
    $jQ('#sender_list').on('change',function(){
        var data = {
            action: 'sib_sender_change',
            sender: $jQ(this).val(),
            security: ajax_sib_object.ajax_nonce
        };
        $jQ.post(ajax_sib_object.ajax_url, data, function() {
            $jQ(this).blur();
        });

        return true;
    });

    // validate MA
    $jQ('#validate_ma_btn').on('click',function(){
        var option_val = $jQ('input[name=activate_ma]:checked').val();
        var data = {
            action:'sib_validate_ma',
            option_val: option_val,
            security: ajax_sib_object.ajax_nonce
        };
        var uninstall = false;
        var uninstallMsg = $jQ('#sib-ma-unistall').val();
        if(option_val != 'yes'){
            uninstall = confirm(uninstallMsg);
        }
        if(option_val == 'yes' || uninstall) {
            $jQ(this).find('.sib-spin').show();
            $jQ('.sib-ma-alert').hide();
            $jQ(this).attr('disabled', 'true');
            $jQ.post(ajax_sib_object.ajax_url, data, function (respond) {
                $jQ('.sib-spin').hide();
                $jQ('#validate_ma_btn').removeAttr('disabled');
                if (respond == 'yes') {
                    $jQ('.sib-ma-active').show();
                } else if(respond == 'no') {
                    $jQ('.sib-ma-inactive').show();
                } else if(respond == 'disabled'){
                    $jQ('.sib-ma-disabled').show();
                    $jQ('#activate_ma_radio_no').prop('checked', true);
                }
                setTimeout(function(){
                    if(respond != 'disabled')
                        window.location.reload();
                },2000);

            });
        }
    });

    // send activate email button
    $jQ('#send_email_btn').on('click',function(){
        var activate_email = $jQ('#activate_email');
        var email = activate_email.val();
        if(email == '' || isValidEmailAddress(email) != true) {
            activate_email.removeClass('has-success').addClass('error');
            $jQ('#failure-alert').show();
            return false;
        }
        $jQ(this).attr('disabled', 'true');

        var data = {
            action:'sib_send_email',
            email:email,
            security: ajax_sib_object.ajax_nonce
        };

        $jQ('.sib_alert').hide();
        activate_email.removeClass('error');
        $jQ(this).find('.sib-spin').show();
        $jQ.post(ajax_sib_object.ajax_url, data,function(respond) {
            $jQ('.sib-spin').hide();
            $jQ('#send_email_btn').removeAttr('disabled');
            if(respond != 'success') {
                $jQ('#activate_email').removeClass('has-success').addClass('error');
                $jQ('#failure-alert').show();
            } else {
                $jQ('#success-alert').show();
            }
        });
    });

    ////////////////////////////////
    /*       manage forms         */
    ////////////////////////////////

    $jQ('#sib-field-content').hide();

    // check confirm email
    var is_send_confirm_email = $jQ("input[name=is_confirm_email]:checked").val();

    if(is_send_confirm_email == '1') {
        $jQ('#sib_confirm_template_area').show();
        $jQ('#sib_confirm_sender_area').show();
    } else {
        $jQ('#sib_confirm_template_area').hide();
        $jQ('#sib_confirm_sender_area').hide();
    }

    // check double optin
    var is_double_optin = $jQ("input[name=is_double_optin]:checked").val();

    if(is_double_optin == '1') {
        $jQ('#is_confirm_email_no').prop("checked", true);
        $jQ('#sib_confirm_template_area').hide();
        $jQ('#sib_confirm_sender_area').hide();
        $jQ('#sib_double_sender_area').show();
        $jQ('#sib_doubleoptin_template_area').show();

    } else {
        $jQ('#sib_double_sender_area').hide();
        $jQ('#sib_double_redirect_area').hide();
        $jQ('#sib_doubleoptin_template_area').hide();
        $jQ('#sib_final_confirm_template_area').hide();
    }

    if ($jQ('#sib_setting_signup_body').find('#sib_select_list_area').length > 0 ) {
        set_select_list();
        $jQ('#sib_select_list').chosen({width:"100%"});
    }

    // For multi lists subscription
    if ( $jQ('#sib_setting_form_body').find('#sib_sel_multi_list_area').length > 0 ) {
        var data = {
            frmid : $jQ('input[name=sib_form_id]').val(),
            action : 'sib_get_lists',
            security: ajax_sib_object.ajax_nonce
        };
        $jQ.post(ajax_sib_object.ajax_url, data, function(respond) {
            var select_html = '';
            $jQ.each(respond.lists, function(index, value) {
                if(value['name'] == 'Temp - DOUBLE OPTIN') return true;
                select_html += '<option value="' + value['id'] + '" data-list="' + value['name'] + '">' + value['name'] + '</option>';
            });
            $jQ('#sib_select_multi_list').html(select_html).trigger("chosen:updated");
            $jQ('#sib_select_multi_list').chosen({width:"100%"});
        });
    }

    $jQ('#sib_select_multi_list').on('change', function(){
       if ( $jQ(this).val() != null )
       {
           $jQ('#sib_multi_list_field').show();
           generate_multi_list_html();
       }
       else {
           $jQ('#sib_multi_list_field').hide();
       }
    });

    $jQ('#sib_multi_field_label').on('change', function () {
        generate_multi_list_html();
    });

    $jQ('#sib_multi_field_required').on('change', function () {
       generate_multi_list_html();
    });
    // keep change of fields
    $jQ('.sib_field_changes').on('change',function() {
        change_field_attr();
    });

    // click confirm email
    $jQ("input[name=is_confirm_email]").on('click',function() {
        var confirm_email = $jQ(this).val();
        var is_activated_smtp = parseInt($jQ("#is_smtp_activated").val());

        if(confirm_email == '1') {
            $jQ('#sib_doubleoptin_template_id').val('-1');
            $jQ('#sib_confirm_template_id').val('-1');
            $jQ('#is_double_optin_no').prop("checked", true);
            $jQ('#sib_double_sender_area').hide();
            $jQ('#sib_double_redirect_area').hide();
            $jQ('#sib_confirm_template_area').show();
            $jQ('#sib_confirm_sender_area').show();
            $jQ('#sib_doubleoptin_template_area').hide();
            $jQ('#sib_final_confirm_template_area').hide();
            $jQ('#sib_form_alert_message').hide();
            if (is_activated_smtp == 0) {
                $jQ('#sib_form_alert_message').show();
                $jQ('#sib_disclaim_smtp').show();
                $jQ('#sib_disclaim_do_template').hide();
                $jQ('#sib_disclaim_confirm_template').hide();
            }
        } else {
            $jQ('#sib_confirm_template_area').hide();
            $jQ('#sib_confirm_sender_area').hide();
            $jQ('#sib_form_alert_message').hide();
        }
    });

    // click double optin
    $jQ('input[name=is_double_optin]').on('click', function() {
        var double_optin = $jQ(this).val();
        var is_activated_smtp = parseInt($jQ("#is_smtp_activated").val());
        if(double_optin == '1') {
            $jQ('#sib_template_id').val('-1');
            $jQ('#is_confirm_email_no').prop("checked", true);
            $jQ('#sib_confirm_template_area').hide();
            $jQ('#sib_confirm_sender_area').hide();
            $jQ('#sib_double_sender_area').show();
            $jQ('#sib_double_redirect_area').show();
            $jQ('#sib_doubleoptin_template_area').show();
            $jQ('#sib_final_confirm_template_area').show();
            if (is_activated_smtp == 0) {
                $jQ('#sib_form_alert_message').show();
                $jQ('#sib_disclaim_smtp').show();
                $jQ('#sib_disclaim_do_template').hide();
                $jQ('#sib_disclaim_confirm_template').hide();
            }
        } else {
            $jQ('#sib_double_sender_area').hide();
            $jQ('#sib_double_redirect_area').hide();
            $jQ('#sib_doubleoptin_template_area').hide();
            $jQ('#sib_form_alert_message').hide();
            $jQ('#sib_final_confirm_template_area').hide();
        }
    });

    // click redirect url
    $jQ('#is_redirect_url_click_yes').on('click', function () {
        $jQ('#sib_subscrition_redirect_area').show();
    });
    $jQ('#is_redirect_url_click_no').on('click', function () {
        $jQ('#sib_subscrition_redirect_area').hide();
    });

    //// refresh iframe to preview form
    $jQ('#sib-preview-form-refresh').on('click',function(){
        // ajax to update form html
        update_preview();
    });

    //// display popup when delete form
    $jQ('.sib-form-delete').on('click', function(e) {
        return confirm('Are you sure you want to delete this form?');
    });

    //// custom or theme's css
    $jQ('input[name=sib_css_type]').on('change',function() {
        $jQ('#sibcssmarkup').toggle();
        update_preview();
    });

   // remove all transients
    $jQ(window).focus(function() {

        var data = {
            action: 'sib_remove_cache',
            security: ajax_sib_object.ajax_nonce
        };
        $jQ.post(ajax_sib_object.ajax_url, data,function(respond) {

            if(respond == 'success') {
                //
            }
        });
    });

    /* sync wordpress users to sendinblue contact list */
    // sync popup
    $jQ('#sib-sync-btn').on('click', function() {
        var syncModal = $jQ('.sib-sync-modal');
        syncModal.modal();
        $jQ('#sync-failure').hide();

        // add to multilist field
        var list = $jQ('#sib_select_list');
        list[0].selectedIndex = 0;
        list.chosen({width:"100%"});

        syncModal.on('hidden.bs.modal', function () {
            //window.location.reload();
        });
    });

    var attrFieldLine = $jQ('.sync-attr-line').html();
    var appenderLine = $jQ('.sync-attr-plus-col').html();
    // sync add attr line filed
    $jQ('.modal-body').on('click', '.sync-attr-plus', function(){
        $jQ(this).css('visibility', 'hidden');
        $jQ(this).addClass('pb-2');
        $jQ('.sync-attr-plus-col').append(appenderLine);
        $jQ('.sync-attr-line').append(attrFieldLine);
        $jQ('.sync-attr-dismiss').show();
    });
    // sync dismiss attr line filed
    $jQ('.modal-body').on('click', '.sync-attr-dismiss', function(){
        $jQ(this).closest('.sync-attr').remove();
        var attrCount = $jQ('.sync-attr').length;
        if(attrCount == 1) {
            $jQ('.sync-attr-dismiss').hide();
        } 
        $jQ(`.sync-attr-plus-col .sync-attr-plus:nth-child(${attrCount - 1})`).css('visibility', 'show');
        $jQ(`.sync-attr-plus-col .sync-attr-plus:nth-child(${attrCount})`).remove();
    });

    // set attribute matching
    $jQ('.modal-body').on('change', 'select', function () {
        if($jQ(this).attr("class") == 'sync-wp-attr'){
            $jQ(this).closest('.sync-attr').find('.sync-match').val($jQ(this).val());
        }else{
            $jQ(this).closest('.sync-attr').find('.sync-match').attr('name',$jQ(this).val());
        }
    });

    // sync users to sendinblue
    $jQ('#sib_sync_users_btn').on('click', function(){

        $jQ(this).attr('disabled', 'true');
        var postData = $jQ('#sib-sync-form').serializeObject();
        $jQ(this).closest('form').find('input[type=hidden]').each(function (index, value) {
            var attrName = $jQ(this).attr('name');
            if($jQ('input[name='+attrName+']').length > 1){
                // the attribute is duplicated !
                postData['errAttr'] = attrName;
            }
        });

        var data = {
            action:'sib_sync_users',
            data: postData,
            security: ajax_sib_object.ajax_nonce
        };

        $jQ('.sib_alert').hide();
        $jQ(this).find('.sib-spin').show();
        $jQ.post(ajax_sib_object.ajax_url, data,function(respond) {
            $jQ('.sib-spin').hide();
            $jQ('#sib_sync_users_btn').removeAttr('disabled');
            let messageBox = '';
            if(respond.code != 'success') {
                messageBox = 'sync-failure';
                $jQ('#sync-failure').show().html(respond.message);
            } else {
                // success to sync wp users
                location.reload();
                $jQ('.sib-sync-modal').modal('toggle');
                $jQ('#sib-message-body').html(respond.message);
                $jQ('#sib-message-box').show();
            }
        });

    });
    $jQ('.sib-add-captcha').on('click', function(){
        var add_captcha = $jQ(this).val();
        var selectCaptchaType = $jQ('.sib-captcha-select').val();
         if(add_captcha == '1')
         {
             $jQ('.sib-captcha-select').show('slow');

             if (selectCaptchaType == 2) {
                $jQ('.sib-captcha-key').show('slow');
             } else if (selectCaptchaType == 3) {
                $jQ('.sib-captcha-key-turnstile').show('slow');
             }
         }
         else
         {
             $jQ('.sib-captcha-select').hide('slow');
             $jQ('.sib-captcha-key').hide('slow');
             $jQ('.sib-captcha-key-turnstile').hide('slow');
         }
    });

    //Captcha select
    $jQ('.sib-captcha-select').on('change', function(){
       var add_captcha = $jQ(this).val();
        if(add_captcha == '1')
        {
            $jQ('.sib-captcha-key-turnstile').hide('slow');
            $jQ('.sib-captcha-key').hide('slow');
        }
        else if(add_captcha == '2')
        {
            $jQ('.sib-captcha-key-turnstile').hide('slow');
            $jQ('.sib-captcha-key').show('slow');
        }
        else if (add_captcha == '3')
        {
            $jQ('.sib-captcha-key').hide('slow');
            $jQ('.sib-captcha-key-turnstile').show('slow');
        }
    });

    $jQ('.popover-help-form').popover({
    });
    $jQ('.sib-spin').hide();
    $jQ('body').on('click', function(e) {
        if(!$jQ(e.target).hasClass('popover-help-form')) {
            $jQ('.popover-help-form').popover('hide');
        }
    });

    $jQ('.sib-add-terms').on('click', function(){
        var add_terms = $jQ(this).val();
        if(add_terms == '1')
        {
            $jQ('.sib-terms-url').show('slow');
        }
        else
        {
            $jQ('.sib-terms-url').hide('slow');
        }
    });

    $jQ('.sib-add-to-form').on('click', function(){
        var btn_id = $jQ(this).attr('id');
        var field_html = '';

        var formMarkup = $jQ("#sibformmarkup");
        var cursorPosition = get_cursor_position(formMarkup[0]);
        var html = formMarkup.val();
        var replacedHTML = "";

        if(btn_id == 'sib_add_to_form_btn')
        {
            let textToslice = $jQ("#sib_field_html").val();
            var position = textToslice.search("sib_SMS_prefix");
            let firstpart = position + 22;

            let lastPoint = firstpart + 7;
            let sliceValue = textToslice.slice(firstpart, lastPoint);
            var code = sliceValue.substring(sliceValue.indexOf("+") + 1, sliceValue.lastIndexOf('"'));

            var flagInicial = CountryList[code];
            if (typeof flagInicial !== "undefined") {
                var flagICo = jQuery('#getDomain').val() + flagInicial.toLowerCase() + ".png";

                field_html = textToslice.replace('class="sib-cflags"', `class="sib-cflags" style=background-image:url(${flagICo})`);
            } else {
                field_html = textToslice;
            }
        }
        else if(btn_id == 'sib_multi_lists_add_form_btn')
        {
            field_html = $jQ('#sib_multi_field_html').val();
            $jQ('#sib_multi_list_field').hide();
        }
        else if(btn_id == 'sib_add_captcha_btn')
        {
            var site_key = $jQ('#sib_captcha_site').val();
            var secret_key = $jQ('#sib_captcha_secret').val();
            var gCaptcha_type = $jQ('input[name=sib_recaptcha_type]:checked').val();

            if(gCaptcha_type == '0')
            {
                $jQ('.cf-turnstile').remove();
                field_html = '<div id="sib_captcha"></div>';
            }
            
            if(site_key == '')
            {
                $jQ('#sib_form_captcha .alert-danger').html('You should input <strong>Site Key</strong>').show(300);
                return false;
            }
            else if(secret_key == '')
            {
                $jQ('#sib_form_captcha .alert-danger').html('You should input <strong>Secrete Key</strong>').show(300);
                return false;
            }
        }
        else if(btn_id == 'sib_add_captcha_btn_turnstile')
        {
            var site_key = $jQ('#sib_captcha_site_turnstile').val();
            var secret_key = $jQ('#sib_captcha_secret_turnstile').val();
            var secret_key = $jQ('#sib_captcha_secret_turnstile').val();
            var cCaptchaStyle = $jQ('input[name=turnstile_captcha_theme]:checked').val();

            var if_site_key_exists = $jQ('#cf-turnstile').val();

            if ((if_site_key_exists == '') || (if_site_key_exists != site_key)) {
                field_html = '<div id="' + "cf-turnstile-"+site_key + '"' + ' class="cf-turnstile" data-error-callback="errorCallbackForTurnstileErrors" data-sitekey="'+site_key+'"   data-theme="'+cCaptchaStyle+'"></div>';
            } else {
                replacedHTML = html;
            }
            
            if(site_key == '')
            {
                $jQ('#sib_form_captcha .alert-danger').html('You should input <strong>Site Key</strong>').show(300);
                return false;
            }
            else if(secret_key == '')
            {
                $jQ('#sib_form_captcha .alert-danger').html('You should input <strong>Secrete Key</strong>').show(300);
                return false;
            }
        }
        else if(btn_id == 'sib_add_termsUrl_btn')
        {
            var terms_url = $jQ('#sib_terms_url').val();
            field_html = '<input type="checkbox" name="terms" required="required">I accept the <a href="' + terms_url + '">terms and conditions</a> ';
            if(terms_url == '')
            {
                $jQ('#sib_form_terms .alert-danger').html('You should input <strong>Terms URL</strong>').show(300);
                return false;
            }
        }
        else if(btn_id == 'sib_add_compliance_note')
        {
            var compliance_note = $jQ('#sib_gdpr_text').val();
            field_html = '<p>' + compliance_note + '</p>';
        }

        replacedHTML = html.replace(/<div id="cf-turnstile.*?>(.*?)<\/div>/, '$1');
        
        if(replacedHTML.charCodeAt(cursorPosition) == 10 || replacedHTML.charCodeAt(cursorPosition) == 13){ // 10 is value of new line
            field_html = "\n" + field_html;
        }else{
            field_html = field_html + "\n";
        }

        var formData = [replacedHTML.slice(0, cursorPosition), field_html, replacedHTML.slice(cursorPosition)].join('');

        formMarkup.val(formData);

        // hide field edit after add the field to form
        $jQ('#sib-field-content').hide();
        $jQ("#sib_sel_attribute").val('-1');

        /*/ refresh iframe form /*/
        // ajax to update form html
        update_preview();
        // set cursor position at top
        setSelectionRange(formMarkup[0], 0, 0);
        return false;
    });

    var redirect = '';
    $jQ('.sib-form-redirect').on('click', function(e){
        e.preventDefault();
        redirect = $jQ(this).attr('href');
        $jQ('#sib_modal').modal();
    });

    $jQ('#sib_form_lang').on('change', function(){
        $jQ('#sib_modal').modal();
    });

    $jQ('#sib_modal_cancel').on('click', function(){
        $jQ('#sib_modal').modal('hide');
        $jQ('#sib_form_lang').val("");
    });
    $jQ('#sib_modal_ok').on('click', function(){
        var url = (redirect != '')? redirect :$jQ('#sib_form_lang').val();
        window.location.href = url;
    });

    // duplicate content from origin form in translation
    $jQ('.sib-duplicate-btn').on('click', function(){
        $jQ('.sib-spin').show();
        var pid = $jQ('input[name="pid"]').val();
        var data = {
            action: 'sib_copy_origin_form',
            pid: pid,
            security: ajax_sib_object.ajax_nonce
        };
        $jQ.post(ajax_sib_object.ajax_url, data, function(respond) {
            $jQ('.sib-spin').hide();
            $jQ('#sibformmarkup').val(respond);
        });

    });

    $jQ('.sib-add-compliant-note').on('click', function () {
        var add_notes = $jQ(this).val();
        if(add_notes == '1')
        {
            $jQ('.sib-gdpr-block-area').show('slow');
            $jQ('.sib-gdpr-block-btn').show('slow');
        }
        else
        {
            $jQ('.sib-gdpr-block-area').hide('slow');
            $jQ('.sib-gdpr-block-btn').hide('slow');
        }
    });
    $jQ('#set_gdpr_default').on('click', function () {
        $jQ('#sib_gdpr_text').val(ajax_sib_object.compliance_note);
    })

    if($jQ("#sib-statistics-date").length) {
        $jQ("#sib-statistics-date").datepicker({
            dateFormat: 'yy-mm-dd',
            numberOfMonths: 1,
            onSelect: function( selectedDate ) {
                if(!$jQ(this).data().datepicker.first){
                    $jQ(this).data().datepicker.inline = true
                    $jQ(this).data().datepicker.first = selectedDate;
                }else{
                    if(selectedDate > $jQ(this).data().datepicker.first){
                        $jQ(this).val($jQ(this).data().datepicker.first+" - "+selectedDate);
                    }else{
                        $jQ(this).val(selectedDate+" - "+$jQ(this).data().datepicker.first);
                    }
                    $jQ(this).data().datepicker.inline = false;
                }
            },
            onClose:function(){
                delete $jQ(this).data().datepicker.first;
                $jQ(this).data().datepicker.inline = false;
            }
        })
    }
    $jQ('#sib-statistics-form').on('submit', function (data) {
        $jQ("#apply-date-range").attr("disabled", true);
        $jQ('.sib-spinner').addClass('is-active');
        return true;
    });

    $jQ('#sibformmarkup').on('change', function ($data) {
        var formHtml = $jQ('#sibformmarkup').val();
        formHtml = updateHtmlWithFlag(formHtml); 
        $jQ('#sibformmarkup').val(formHtml);
    });
});

// get serialized data form sync users form
$jQ.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $jQ.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

function updateHtmlWithFlag(htmlString) {
    var customEle = document.createElement( 'section' );
    customEle.innerHTML = htmlString;
    var codeEle = customEle.querySelector("[name='sib_SMS_prefix']");
    if(codeEle === null) {
        return htmlString;
    }
    let dialCode = codeEle.value;
    dialCode = dialCode.replace('+','');
    var flagInicial = CountryList[dialCode];
    if (typeof flagInicial !== "undefined") {
        let flagICo =  jQuery('#getDomain').val()+flagInicial.toLowerCase()+".png";
        customEle.querySelector("[class='sib-cflags']").removeAttribute("style");
        customEle.querySelector("[class='sib-cflags']").setAttribute("style","background-image:url("+flagICo+")");
    }
    var HtString = customEle.innerHTML;
    HtString = HtString.replace('</p><div class="sib-sms-field','<div class="sib-sms-field');
    HtString = HtString.replace('<p></p>','</p>');
    return HtString;
}
