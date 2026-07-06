var pms_payment_buttons
var $pms_auto_renew_field
var $pms_checked_subscription
var $pms_checked_paygate
var $pms_gateways_not_available
var pms_payment_button_loading_placeholder_text
var $pms_form
var is_pb_email_confirmation_on
var $pms_section_billing_details
var $pms_billing_toggle
jQuery(function($){if(window.history.replaceState){currentURL=window.location.href;currentURL=pms_remove_query_arg('pmsscscd',currentURL);currentURL=pms_remove_query_arg('pmsscsmsg',currentURL);currentURL=pms_remove_query_arg('pms_gateway_payment_action',currentURL);currentURL=pms_remove_query_arg('pms_gateway_payment_id',currentURL);currentURL=pms_remove_query_arg('subscription_plan_id',currentURL);currentURL=pms_remove_query_arg('pms_wppb_custom_success_message',currentURL);currentURL=pms_remove_query_arg('redirect_to',currentURL);if(currentURL!=window.location.href)
window.history.replaceState(null,null,currentURL);}
function pms_remove_query_arg(key,sourceURL){var rtn=sourceURL.split("?")[0],param,params_arr=[],queryString=(sourceURL.indexOf("?")!==-1)?sourceURL.split("?")[1]:"";if(queryString!==""){params_arr=queryString.split("&");for(var i=params_arr.length-1;i>=0;i-=1){param=params_arr[i].split("=")[0];if(param===key){params_arr.splice(i,1)}}
rtn=rtn+"?"+params_arr.join("&")}
if(rtn.split("?")[1]==""){rtn=rtn.split("?")[0]}
return rtn}
pms_payment_buttons='input[name=pms_register], '
pms_payment_buttons+='input[name=pms_new_subscription], '
pms_payment_buttons+='input[name=pms_change_subscription], '
pms_payment_buttons+='input[name=pms_upgrade_subscription], '
pms_payment_buttons+='input[name=pms_renew_subscription], '
pms_payment_buttons+='input[name=pms_confirm_retry_payment_subscription], '
pms_payment_buttons+='input[name=pms_update_payment_method], '
pms_payment_buttons+='#pms-paypal-express-confirmation-form input[type="submit"], '
pms_payment_buttons+='.wppb-register-user input[name=register]'
var subscription_plan_selector='input[name=subscription_plans]'
var paygate_selector='input.pms_pay_gate'
var settings_recurring=$('input[name="pms_default_recurring"]').val()
$pms_section_billing_details=$('.pms-section-billing-details')
$pms_billing_toggle=$('#pms_billing_toggle_checkbox')
is_pb_email_confirmation_on=$pms_section_billing_details.siblings('.pms-email-confirmation-payment-message').length>0?!0:!1
$pms_auto_renew_field=jQuery('.pms-subscription-plan-auto-renew')
$pms_checked_subscription=jQuery(subscription_plan_selector+'[type=radio]').length>0?jQuery(subscription_plan_selector+'[type=radio]:checked'):jQuery(subscription_plan_selector+'[type=hidden]')
$pms_checked_paygate=jQuery(paygate_selector+'[type=radio]').length>0?jQuery(paygate_selector+'[type=radio]:checked'):jQuery(paygate_selector+'[type=hidden]')
$pms_gateways_not_available=jQuery('#pms-gateways-not-available')
pms_payment_button_loading_placeholder_text=$('#pms-submit-button-loading-placeholder-text').text()
jQuery(document).ready(function(){$(document).on('click',paygate_selector,function(){if($(this).is(':checked'))
$pms_checked_paygate=$(this)
if($pms_checked_paygate.data('type')=='extra_fields'){$('.pms-paygate-extra-fields').hide()
$('.pms-paygate-extra-fields-'+$pms_checked_paygate.val()).show()}else $('.pms-paygate-extra-fields').hide()
handle_billing_fields_display()
handle_billing_cycles_display($pms_checked_paygate.val())})
$(document).on('click',subscription_plan_selector+'[type=radio], '+subscription_plan_selector+'[type="hidden"]',function(){if($(this).is(':checked'))
$pms_checked_subscription=$(this)
if(typeof $pms_form=='undefined')
$pms_form=$(this).closest('form')
handle_auto_renew_field_display()
handle_payment_gateways_display()
handle_billing_fields_display()})
$(document).on('change','.pms_pwyw_pricing',handle_billing_fields_display)
$(document).on('keyup','.pms_pwyw_pricing',handle_billing_fields_display)
function handle_auto_renew_field_display(){if($pms_checked_subscription.data('recurring')==1&&$pms_checked_paygate.data('recurring')!='undefined')
$pms_auto_renew_field.show()
else $pms_auto_renew_field.hide()
if($pms_checked_subscription.data('recurring')==0){if(settings_recurring==1)
$pms_auto_renew_field.show()}
if(($pms_checked_subscription.data('fixed_membership')=='on'&&$pms_checked_subscription.data('allow_renew')!='on')||$pms_checked_subscription.data('recurring')==2||$pms_checked_subscription.data('recurring')==3){$pms_auto_renew_field.hide()}
if(($pms_checked_subscription.data('fixed_membership')!='on'&&$pms_checked_subscription.data('duration')==0)||($pms_checked_subscription.data('price')==0&&!($pms_checked_subscription.data('sign_up_fee')>0))){if(typeof $pms_checked_subscription.data('discountedPrice')=='undefined')
$pms_auto_renew_field.hide()
else if(typeof $pms_checked_subscription.data('isFullDiscount')!='undefined'&&$pms_checked_subscription.data('isFullDiscount')==!0&&$pms_checked_subscription.data('discountRecurringPayments')==1)
$pms_auto_renew_field.hide()}
if($pms_checked_subscription.data('recurring')!='undefined'&&$pms_checked_subscription.data('recurring')!=3&&$pms_checked_subscription.data('recurring')!=2){if($pms_checked_subscription.data('fixed_membership')!='on'||($pms_checked_subscription.data('fixed_membership')=='on'&&$pms_checked_subscription.data('allow_renew')=='on')){if(typeof $pms_checked_subscription.data('prorated_discount')!='undefined'&&$pms_checked_subscription.data('prorated_discount')>0)
$pms_auto_renew_field.show()}}}
function handle_payment_gateways_display(){$('#pms-paygates-wrapper').show()
$(paygate_selector).removeAttr('disabled')
$(paygate_selector).closest('label').show()
if($.pms_plan_has_trial()){$(paygate_selector+':not([data-trial])').attr('disabled',!0);$(paygate_selector+':not([data-trial])').closest('label').hide()}
if($.pms_plan_has_signup_fee()){$(paygate_selector+':not([data-sign_up_fee])').attr('disabled',!0);$(paygate_selector+':not([data-sign_up_fee])').closest('label').hide()}
if($pms_checked_subscription.data('recurring')==2){$(paygate_selector+':not([data-recurring])').attr('disabled',!0);$(paygate_selector+':not([data-recurring])').closest('label').hide()}else if($pms_checked_subscription.data('recurring')==1){if($pms_auto_renew_field.find('input[type=checkbox]').is(':checked')){$(paygate_selector+':not([data-recurring])').attr('disabled',!0);$(paygate_selector+':not([data-recurring])').closest('label').hide()}}else if(!$pms_checked_subscription.data('recurring')){if(settings_recurring==1){if($pms_auto_renew_field.find('input[type=checkbox]').is(':checked')){$(paygate_selector+':not([data-recurring])').attr('disabled',!0);$(paygate_selector+':not([data-recurring])').closest('label').hide()}}else if(settings_recurring==2){$(paygate_selector+':not([data-recurring])').attr('disabled',!0);$(paygate_selector+':not([data-recurring])').closest('label').hide()}}
if($pms_checked_subscription.length>0&&$pms_checked_subscription.data('limit_payment_cycles')==='yes'){$(paygate_selector+':not([data-billing_cycles])').attr('disabled',!0);$(paygate_selector+':not([data-billing_cycles])').closest('label').hide()}
if($(paygate_selector+':not([disabled]):checked').length==0)
$(paygate_selector+':not([disabled])').first().trigger('click');if($(paygate_selector).length>0){if($(paygate_selector+':not([disabled])').length==0){$pms_gateways_not_available.show();$('.pms-paygate-extra-fields').hide()
if($pms_checked_subscription.data('price')!=0){if($pms_checked_subscription.length!=0)
$(pms_payment_buttons).attr('disabled',!0).addClass('pms-submit-disabled');}}else{$pms_gateways_not_available.hide();if($(paygate_selector+':not([disabled]):checked[data-type="extra_fields"]').length>0){$('.pms-paygate-extra-fields').hide()
$('.pms-paygate-extra-fields-'+$(paygate_selector+':not([disabled]):checked[data-type="extra_fields"]').val()).show()}else if($(paygate_selector+':not([disabled])[type="hidden"][data-type="extra_fields"]').length>0){$('.pms-paygate-extra-fields').hide()
$('.pms-paygate-extra-fields-'+$(paygate_selector+':not([disabled])[type="hidden"][data-type="extra_fields"]').val()).show()}
if($pms_checked_subscription.length!=0)
$(pms_payment_buttons).attr('disabled',!1).removeClass('pms-submit-disabled');}}
if($pms_checked_subscription.data('price')==0&&!$.pms_plan_has_signup_fee()){if($.pms_plan_is_prorated()){if($.pms_checkout_is_recurring()){if(typeof $pms_form!='undefined')
$.pms_show_payment_fields($pms_form)
return}}
$('#pms-paygates-wrapper').hide()
$(paygate_selector).attr('disabled',!0)
$(paygate_selector).closest('label').hide()
$('.pms-paygate-extra-fields').hide()
$('.pms-billing-details').hide()
$('.pms-section-billing-toggle').hide()}}
function handle_plan_recurring_duration_display(){if(!($('#pms-change-subscription-form').length>0))
return
$('input[name="subscription_plans"]').each(function(index,plan){if($(plan).data('recurring')==3||(typeof $(plan).data('prorated_discount')=='undefined'||$(plan).data('prorated_discount')==0))
return
if(($(plan).data('recurring')==2||settings_recurring==2||$('input[name="pms_recurring"]',$pms_auto_renew_field).prop('checked'))&&$('.pms-subscription-plan-price__recurring',$(plan).parent()))
$('.pms-subscription-plan-price__recurring',$(plan).parent()).show()
else $('.pms-subscription-plan-price__recurring',$(plan).parent()).hide()})}
function handle_billing_fields_display(){if(!($pms_section_billing_details.length>0)){$('.pms-section-billing-toggle').hide()
return}
if($pms_checked_subscription.length>0&&!is_pb_email_confirmation_on&&($pms_checked_subscription.data('price')!=0||$.pms_plan_has_signup_fee($pms_checked_subscription))){$('.pms-billing-details').attr('style','display: flex;');if($pms_checked_subscription.data('price')>0)
$('.pms-section-billing-toggle').show()
else $('.pms-section-billing-toggle').hide()}
let parentForm=$pms_section_billing_details.closest('form').attr('id');if(parentForm===undefined||(parentForm!=='pms_edit-profile-form'&&!$pms_checked_subscription.length)){$('.pms-section-billing-toggle').hide()
return}
if($pms_billing_toggle.length>0){if($pms_billing_toggle.is(':checked')){$('.pms-billing-details').attr('style','display: flex;')}else{$('.pms-billing-details').hide()}}}
function handle_billing_cycles_display(selected_paygate){let cyclesText=jQuery('.pms-subscription-plan-billing-cycles');let gateways=['manual','stripe_connect','paypal_connect','authorize_net'];if(gateways.includes(selected_paygate))
cyclesText.show();else cyclesText.hide()}
jQuery(document).on('submit','.pms-form',disable_form_submit_button)
if(jQuery('.wppb-register-user').length>0&&jQuery('.wppb-register-user .wppb-subscription-plans').length>0)
jQuery(document).on('submit','.wppb-register-user',disable_form_submit_button)
window.disable_form_submit_button=disable_form_submit_button;function disable_form_submit_button(e){if(jQuery(e.target).is('form')){var form=jQuery(e.target)}else{var form=jQuery(e)}
var target_button=jQuery('input[type="submit"], button[type="submit"]',form).not('#pms-apply-discount').not('input[name="pms_redirect_back"]')[0]
if($(target_button).hasClass('pms-submit-disabled'))
return!1
$(target_button).data('original-value',$(target_button).val())
if(pms_payment_button_loading_placeholder_text.length>0){$(target_button).addClass('pms-submit-disabled').val(pms_payment_button_loading_placeholder_text)
if($(target_button).is('button'))
$(target_button).text(pms_payment_button_loading_placeholder_text)}}
$pms_auto_renew_field.click(function(){handle_auto_renew_field_display()
handle_payment_gateways_display()
handle_plan_recurring_duration_display()});if($pms_billing_toggle.length>0){let allRequiredFilled=!0;$('.pms-billing-details .pms-field-required').each(function(){let $input=$(this).find('input, select');if($input.length>0&&!$input.val()){allRequiredFilled=!1;return!1}});if(!allRequiredFilled){$pms_billing_toggle.prop('checked',!0)}
$pms_billing_toggle.on('change',function(){handle_billing_fields_display()})}
handle_auto_renew_field_display()
handle_payment_gateways_display()
handle_plan_recurring_duration_display()
handle_billing_fields_display()
$('#pms-paygates-inner').css('visibility','visible');handle_billing_cycles_display($pms_checked_paygate.val())
jQuery(document).on('elementor/popup/show',function(){if($('.pms-form',$('.elementor-popup-modal')).length>0){$pms_checked_subscription=jQuery(subscription_plan_selector+'[type=radio]').length>0?jQuery(subscription_plan_selector+'[type=radio]:checked'):jQuery(subscription_plan_selector+'[type=hidden]')
handle_auto_renew_field_display()
handle_payment_gateways_display()
handle_plan_recurring_duration_display()
handle_billing_fields_display()
$('#pms-paygates-inner').css('visibility','visible')}})
if($('.wppb-register-user').length!=0&&$('.wppb-subscription-plans').length!=0){pmsHandleDefaultWPPBFormSelectedPlanOnLoad()
pmsHandleGatewaysDisplayRemove()
$(document).on("wppbRemoveRequiredAttributeEvent",pmsHandleGatewaysDisplayRemove)
$(document).on("wppbAddRequiredAttributeEvent",pmsHandleGatewaysDisplayShow)
$(document).on("wppb_msf_next_step",pmsHandleGatewaysDisplayRemove)
$(document).on("wppb_msf_next_step",pmsHandleGatewaysDisplayShow)
function pmsHandleGatewaysDisplayRemove(event=''){if($('#pms-paygates-wrapper').is(':hidden'))
return
if(event!=''){if(event.type&&event.type!='wppb_msf_next_step'){var element=event.target
if(typeof $(element).attr('conditional-name')=='undefined'||$(element).attr('conditional-name')!='subscription_plans')
return}}
var visible_plans=!1
$('.wppb-subscription-plans').each(function(index,item){var only_free_plans=!0
var $checked=$('.pms-subscription-plan input[type=radio]:checked, .pms-subscription-plan input[type=hidden]',$(item))
if($checked.attr('conditional-name')==='subscription_plans'){return!1}
if(($checked.data('price')&&$checked.data('price')>0)||$.pms_plan_has_signup_fee($checked)){only_free_plans=!1}
if(only_free_plans)
visible_plans=!1
else visible_plans=!0
return!1})
if(visible_plans===!1){$('#pms-paygates-wrapper').hide()
$(paygate_selector).attr('disabled',!0)
$(paygate_selector).closest('label').hide()
$('.pms-paygate-extra-fields').hide()
$('.pms-billing-details').hide()
$('.pms-section-billing-toggle').hide()
$('.pms-price-breakdown__holder').hide()
if(typeof element!='undefined'&&element.length>0){$('input[type="submit"], button[type="submit"]',$(element).closest('.pms-form, .wppb-register-user')).show()}}else{pmsHandleDefaultWPPBFormSelectedPlanOnLoad()}}
function pmsHandleGatewaysDisplayShow(event=''){if(event!=''){if(event.type&&event.type!='wppb_msf_next_step'){var element=event.target
if(typeof $(element).attr('conditional-name')=='undefined'||$(element).attr('conditional-name')!='subscription_plans')
return}}
var visible_plans=!1
$('.wppb-subscription-plans').each(function(index,item){var only_free_plans=!0
var $checked=$('.pms-subscription-plan input[type=radio]:checked, .pms-subscription-plan input[type=hidden]',$(item))
if($checked.attr('conditional-name')==='subscription_plans'){return!1}
if($checked.data('price')&&$checked.data('price')>0){only_free_plans=!1}
if(only_free_plans)
visible_plans=!1
else visible_plans=!0
return!1})
if(visible_plans===!1){$('#pms-paygates-wrapper').hide()
$(paygate_selector).attr('disabled',!0)
$(paygate_selector).closest('label').hide()
$('.pms-paygate-extra-fields').hide()
$('.pms-billing-details').hide()
$('.pms-section-billing-toggle').hide()
$('.pms-price-breakdown__holder').hide()
if(typeof element!='undefined'&&element.length>0){$('input[type="submit"], button[type="submit"]',$(element).closest('.pms-form, .wppb-register-user')).show()}}else{$('#pms-paygates-wrapper').show()
$(paygate_selector).removeAttr('disabled')
$(paygate_selector).closest('label').show()
$('.pms-paygate-extra-fields').show()
$('.pms-billing-details').attr('style','display: flex;');$('.pms-section-billing-toggle').show()
$('.pms-price-breakdown__holder').show()
if(($('input[type=radio][name=pay_gate]:checked').val()=='paypal_connect'||$('input[type=hidden][name=pay_gate]').val()=='paypal_connect')&&(!$('input[type=radio][name=pay_gate]:checked').is(':disabled')||!$('input[type=hidden][name=pay_gate]').is(':disabled'))){$('.pms-paygate-extra-fields-paypal_connect').show()
$('.wppb-register-user .form-submit input[type="submit"], .wppb-register-user.form-submit button[type="submit"]').last().hide()}}}
function pmsHandleDefaultWPPBFormSelectedPlanOnLoad(){if(!(jQuery('#wppb-register-user').length>0))
return
if(!(jQuery('.wppb-subscription-plans').length>1))
return
jQuery('.wppb-subscription-plans').each(function(){if(jQuery(this).is(':visible')){jQuery(this).find("input[name=\'subscription_plans\']").each(function(index,item){if(typeof jQuery(item).data("default-selected")!="undefined"&&jQuery(item).data("default-selected")==!0){jQuery(item).prop("checked","checked")
jQuery(item).trigger("click")}})
return}})}}
if($('#pms-change-subscription-form').length>0){if($pms_checked_subscription.closest('.pms-upgrade__group').hasClass('pms-upgrade__group--upgrade')){$('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_upgrade"]').val())
$('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="upgrade_subscription"]').val())}else if($pms_checked_subscription.closest('.pms-upgrade__group').hasClass('pms-upgrade__group--downgrade')){$('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_downgrade"]').val())
$('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="downgrade_subscription"]').val())}
$('#pms-change-subscription-form .pms-upgrade__group--upgrade .pms-subscription-plan input').on('click',function(){$('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_upgrade"]').val())
$('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="upgrade_subscription"]').val())})
$('#pms-change-subscription-form .pms-upgrade__group--downgrade .pms-subscription-plan input').on('click',function(){$('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_downgrade"]').val())
$('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="downgrade_subscription"]').val())})
$('#pms-change-subscription-form .pms-upgrade__group--change .pms-subscription-plan input').on('click',function(){$('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_change"]').val())
$('#pms-change-subscription-form input[name="form_action"]').val('')})}})
$.pms_add_field_error=function(error,field_name){if(error==''||error=='undefined'||field_name==''||field_name=='undefined')
return!1;$field=$('[name='+field_name+']');$field_wrapper=$field.closest('.pms-field');error='<p>'+error+'</p>';if($field_wrapper.find('.pms_field-errors-wrapper').length>0)
$field_wrapper.find('.pms_field-errors-wrapper').html(error);else $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">'+error+'</div>')}
$.pms_add_general_error=function(error){if(error==''||error=='undefined')
return!1
var target=$('.pms-form')
target.prepend('<div class="pms_field-errors-wrapper pms-is-js"><p>'+error+'</p></div>')}
$.pms_add_subscription_plans_error=function(error){if(error==''||error=='undefined')
return!1
$('<div class="pms_field-errors-wrapper pms-is-js"><p>'+error+'</p></div>').insertBefore('#pms-paygates-wrapper')}
$.pms_add_recaptcha_field_error=function(error,payment_button){$field_wrapper=$('#pms-recaptcha-register-wrapper',$(payment_button).closest('form'))
error='<p>'+error+'</p>'
if($field_wrapper.find('.pms_field-errors-wrapper').length>0)
$field_wrapper.find('.pms_field-errors-wrapper').html(error)
else $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">'+error+'</div>')}
$.pms_plan_has_trial=function(element=null){if(element==null)
element=$pms_checked_subscription
if(typeof element.data('trial')=='undefined'||element.data('trial')=='0')
return!1
return!0}
$.pms_plan_has_signup_fee=function(element=null){if(element==null)
element=$pms_checked_subscription
if(typeof element.data('sign_up_fee')=='undefined'||element.data('sign_up_fee')=='0')
return!1
return!0}
$.pms_plan_is_prorated=function(element=null){if(!($('#pms-change-subscription-form').length>0))
return!1
if(element==null)
element=$pms_checked_subscription
if(typeof element.data('prorated_discount')!='undefined'&&element.data('prorated_discount')>0)
return!0
return!1}
$.pms_checkout_is_recurring=function(element=null){if(element==null)
element=$pms_checked_subscription
if((settings_recurring=='2'||$('input[name="pms_recurring"]',$pms_auto_renew_field).prop('checked')||element.data('recurring')==2)&&element.data('recurring')!=3)
return!0
return!1}
$.pms_hide_payment_fields=function(form){if(typeof form=='undefined')
return
if(typeof form.pms_paygates_wrapper=='undefined')
form.pms_paygates_wrapper=form.find('#pms-paygates-wrapper').clone()
form.find('#pms-paygates-wrapper').replaceWith('<span id="pms-paygates-wrapper">')
form.find('.pms-paygate-extra-fields').hide()
if(form.find('.pms-paygate-extra-fields-paypal_connect').length>0){if(typeof $pms_checked_paygate!='undefined'&&$pms_checked_paygate.val()=='paypal_connect'){form.find('input[type="submit"], button[type="submit"]').show()}}
if(typeof PMS_ChosenStrings!=='undefined'&&$.fn.chosen!=undefined){form.find('#pms_billing_country').chosen('destroy')
form.find('#pms_billing_state').chosen('destroy')}
if(typeof form.pms_billing_details=='undefined'){form.pms_billing_details=form.find('.pms-billing-details').clone()}
form.find('.pms-billing-details').replaceWith('<span class="pms-billing-details">')
$('.pms-section-billing-toggle').hide()}
$.pms_show_payment_fields=function(form){if(typeof form=='undefined')
return
if(typeof form.pms_paygates_wrapper!='undefined')
form.find('#pms-paygates-wrapper').replaceWith(form.pms_paygates_wrapper)
if(typeof $pms_checked_paygate!='undefined'&&$pms_checked_paygate.data('type')=='extra_fields')
form.find('.pms-paygate-extra-fields-'+$pms_checked_paygate.val()).show()
if(form.find('.pms-paygate-extra-fields-paypal_connect').length>0){if(typeof $pms_checked_paygate!='undefined'&&$pms_checked_paygate.val()=='paypal_connect'){form.find('input[type="submit"]:not([name="pms_redirect_back"]):not([id="pms-apply-discount"]), button[type="submit"]').hide()}}
if(typeof form.pms_billing_details!='undefined'){form.find('.pms-billing-details').replaceWith(form.pms_billing_details)
$('.pms-section-billing-toggle').show();if(typeof PMS_ChosenStrings!=='undefined'&&$.fn.chosen!=undefined){form.find('#pms_billing_country').chosen(PMS_ChosenStrings)
if($('#pms_billing_state option').length>0)
form.find('#pms_billing_state').chosen(PMS_ChosenStrings)}}}
$.pms_checkout_is_setup_intents=function(){let selected_plan=$(subscription_plan_selector+'[type=radio]').length>0?$(subscription_plan_selector+'[type=radio]:checked'):$(subscription_plan_selector+'[type=hidden]')
if(typeof selected_plan.data('trial')!='undefined'&&selected_plan.data('trial')=='1'&&!$.pms_plan_has_signup_fee(selected_plan))
return!0
else if($('input[name="discount_code"]').length>0&&$('input[name="discount_code"]').val().length>0&&typeof selected_plan.data('price')!='undefined'&&selected_plan.data('price')=='0')
return!0
else if($.pms_plan_is_prorated(selected_plan)&&typeof selected_plan.data('price')!='undefined'&&selected_plan.data('price')=='0')
return!0
return!1}
$.pms_form_add_wppb_validation_errors=function(errors,current_button){let scroll=!1
jQuery.each(errors,function(key,value){let field=jQuery('#wppb-form-element-'+key)
field.addClass('wppb-field-error')
field.append(value)
scroll=!0})
if(scroll)
$.pms_form_scrollTo('.wppb-register-user',current_button)}
$.pms_stripe_add_credit_card_error=function(error){if(error==''||error=='undefined')
return!1
$field_wrapper=$('.pms-paygate-extra-fields-stripe_connect');error='<p>'+error+'</p>'
if($field_wrapper.find('.pms_field-errors-wrapper').length>0)
$field_wrapper.find('.pms_field-errors-wrapper').html(error)
else $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">'+error+'</div>')}
$.pms_form_add_validation_errors=function(errors,payment_button){var scrollLocation='';$.each(errors,function(index,value){if(value.target=='form_general'){$.pms_add_general_error(value.message)
scrollLocation='.pms-form'}else if(value.target=='subscription_plan'||value.target=='subscription_plans'||value.target=='payment_gateway'){$.pms_add_subscription_plans_error(value.message)
if(scrollLocation=='')
scrollLocation='.pms-field-subscriptions'}else if(value.target=='credit_card'){$.pms_stripe_add_credit_card_error(value.message)
if(scrollLocation=='')
scrollLocation='#pms-paygates-wrapper'}else if(value.target=='recaptcha-register'){$.pms_add_recaptcha_field_error(value.message,payment_button)}else{$.pms_add_field_error(value.message,value.target)
if(scrollLocation==''&&value.target.indexOf('pms_billing')!==-1)
scrollLocation='.pms-billing-details'
else if(scrollLocation==''&&value.target.indexOf('pms_gift_recipient_email')!==-1)
scrollLocation='.pms-gift-details'
else scrollLocation='.pms-form'}})
if($(payment_button).attr('name')=='pms_update_payment_method'&&scrollLocation=='#pms-paygates-wrapper')
scrollLocation='#pms-stripe-connect';$.pms_form_scrollTo(scrollLocation,payment_button)}
$.pms_form_reset_submit_button=function(target){if(!target.data||!target.data('original-value')||typeof target.data('original-value')==undefined){value=target.val()}else{value=target.data('original-value')}
setTimeout(function(){target.attr('disabled',!1).removeClass('pms-submit-disabled').val(value).blur()
if($(target).is('button'))
$(target).text(value)},1)}
$.pms_form_scrollTo=function(scrollLocation,payment_button){var form=$(scrollLocation)[0]
if(typeof form=='undefined'){$.pms_form_reset_submit_button(payment_button)
return}
var coord=form.getBoundingClientRect().top+window.scrollY
var offset=-170
window.scrollTo({top:coord+offset,behavior:'smooth'})
$.pms_form_reset_submit_button(payment_button)}
$.pms_form_remove_errors=function(){$('.pms_field-errors-wrapper').remove()
if($('.pms-stripe-error-message').length>0)
$('.pms-stripe-error-message').remove()
if($('.wppb-register-user').length>0){$('.wppb-form-error').remove()
$('.wppb-register-user .wppb-form-field').each(function(){$(this).removeClass('wppb-field-error')})}}
$.pms_form_get_data=async function(current_button,verify_captcha=!1){if(!current_button)
return!1
var form=$(current_button).closest('form')
var data=form.serializeArray().reduce(function(obj,item){obj[item.name]=item.value
return obj},{})
data.action='pms_process_checkout'
data.current_page=window.location.href
data.pms_nonce=$('#pms-process-checkout-nonce').val()
data.form_type=$('.wppb-register-user .wppb-subscription-plans').length>0?'wppb':$('.pms-ec-register-form').length>0?'pms_email_confirmation':'pms'
data[current_button.attr('name')]=!0
if($('input[name="form_action"]',form)&&$('input[name="form_action"]',form).length>0)
data.form_action=$('input[name="form_action"]',form).val()
if(data.form_type=='wppb'){data.wppb_fields=$.pms_form_get_wppb_fields(current_button)
if($('input[name="send_credentials_via_email"]',form).length>0&&$('input[name="send_credentials_via_email"]',form).is(':checked'))
data.send_credentials_via_email='sending'
else data.send_credentials_via_email=''}
if($('body').hasClass('logged-in'))
data.form_type=$('input[type="submit"], button[type="submit"]',form).not('#pms-apply-discount').not('input[name="pms_redirect_back"]').attr('name')
if($.pms_checkout_is_setup_intents())
data.setup_intent=!0
if(data.pms_current_subscription)
data.subscription_id=data.pms_current_subscription
if(verify_captcha&&typeof data['g-recaptcha-response']!='undefined'&&data['g-recaptcha-response']==''){if(data.form_type=='wppb')
$.pms_form_add_wppb_validation_errors({recaptcha:{field:'recaptcha',error:'<span class="wppb-form-error">This field is required</span>'}},current_button)
else $.pms_add_recaptcha_field_error('Please complete the reCaptcha.',current_button)
$.pms_form_reset_submit_button(current_button)
return!1}
return data}
$.pms_form_get_wppb_fields=function(current_button){var fields={}
jQuery('li.wppb-form-field',jQuery(current_button).closest('form')).each(function(){if(jQuery(this).attr('class').indexOf('heading')==-1&&jQuery(this).attr('class').indexOf('wppb_billing')==-1&&jQuery(this).attr('class').indexOf('wppb_shipping')==-1&&jQuery(this).attr('class').indexOf('wppb-shipping')==-1){var meta_name;if(jQuery(this).hasClass('wppb-repeater')||jQuery(this).parent().attr('data-wppb-rpf-set')=='template'||jQuery(this).hasClass('wppb-recaptcha')){return!0}
if(jQuery(this).hasClass('wppb-send-credentials-checkbox'))
return!0;if(jQuery(this).find('[conditional-value]').length!==0){return!0}
fields[jQuery(this).attr('id')]={};fields[jQuery(this).attr('id')]['class']=jQuery(this).attr('class');if(jQuery(this).hasClass('wppb-woocommerce-customer-billing-address')){meta_name='woocommerce-customer-billing-address'}else if(jQuery(this).hasClass('wppb-woocommerce-customer-shipping-address')){meta_name='woocommerce-customer-shipping-address';if(!jQuery('.wppb-woocommerce-customer-billing-address #woo_different_shipping_address',jQuery(current_button).closest('form')).is(':checked')){return!0}}else{meta_name=jQuery(this).find('label').attr('for');fields[jQuery(this).attr('id')].title=jQuery(this).find('label').first().text().trim()}
fields[jQuery(this).attr('id')]['meta-name']=meta_name;if(jQuery(this).parent().parent().attr('data-wppb-rpf-meta-name')){var repeater_group=jQuery(this).parent().parent();fields[jQuery(this).attr('id')].extra_groups_count=jQuery(repeater_group).find('#'+jQuery(repeater_group).attr('data-wppb-rpf-meta-name')+'_extra_groups_count').val()}
if(jQuery(this).hasClass('wppb-woocommerce-customer-billing-address')){var woo_billing_fields_fields={};jQuery('ul.wppb-woo-billing-fields li.wppb-form-field',jQuery(current_button).closest('form')).each(function(){if(!jQuery(this).hasClass('wppb_billing_heading')){woo_billing_fields_fields[jQuery(this).find('label').attr('for')]=jQuery(this).find('label').text()}});fields[jQuery(this).attr('id')].fields=woo_billing_fields_fields}
if(jQuery(this).hasClass('wppb-woocommerce-customer-shipping-address')){var woo_shipping_fields_fields={};jQuery('ul.wppb-woo-shipping-fields li.wppb-form-field',jQuery(current_button).closest('form')).each(function(){if(!jQuery(this).hasClass('wppb_shipping_heading')){woo_shipping_fields_fields[jQuery(this).find('label').attr('for')]=jQuery(this).find('label').text()}});fields[jQuery(this).attr('id')].fields=woo_shipping_fields_fields}}})
return fields}
jQuery("#pms-delete-account").on("click",function(e){e.preventDefault();if(typeof pmsGdpr==='undefined'||typeof pmsGdpr.delete_url==='undefined')
return;var pmsDeleteUser=prompt(pmsGdpr.delete_text);if(pmsDeleteUser==="DELETE"){window.location.replace(pmsGdpr.delete_url)}else{alert(pmsGdpr.delete_error_text)}})})
jQuery(function($){$(document).ready(function(){if(($('.pms-subscription-plan input[type=radio][data-price="0"]').is(':checked')||$('.pms-subscription-plan input[type=hidden]').attr('data-price')=='0'||$('.pms-subscription-plan input[type=radio]').prop('checked')==!1)&&!$.pms_plan_has_signup_fee()){$('.pms-email-confirmation-payment-message').hide()}
if($('.pms-subscription-plan input[type=radio]').length>0){var has_paid_subscription=!1
$('.pms-subscription-plan input[type=radio]').each(function(){if($(this).data('price')!=0||$.pms_plan_has_signup_fee($(this)))
has_paid_subscription=!0})
if(!has_paid_subscription)
$('.pms-email-confirmation-payment-message').hide()}
$('.pms-subscription-plan input[type=radio]').click(function(){if($('.pms-subscription-plan input[type=radio][data-price="0"]').is(':checked')&&!$.pms_plan_has_signup_fee($(this)))
$('.pms-email-confirmation-payment-message').hide()
else $('.pms-email-confirmation-payment-message').show()})
$('.wppb-edit-user input[required]').on('invalid',function(e){$.pms_reset_submit_button($('.wppb-edit-user .wppb-subscription-plans input[type="submit"]').first())})})})
jQuery(function($){$(document).ready(function(){if(typeof PMS_States=='undefined'||!PMS_States)
return
pms_handle_billing_state_field_display()
$(document).on('change','#pms_billing_country',function(){pms_handle_billing_state_field_display()})
if(typeof PMS_ChosenStrings!=='undefined'&&$.fn.chosen!=undefined){$('#pms_billing_country').chosen(PMS_ChosenStrings)
if($('#pms_billing_state option').length>0)
$('#pms_billing_state').chosen(PMS_ChosenStrings)}
$('input[name=pms_billing_email], input[name=pms_billing_first_name], input[name=pms_billing_last_name]').each(function(){if($(this).val()!='')
$(this).addClass('pms-has-value')})})
$(document).on('keyup','#pms_user_email, .wppb-form-field input[name=email]',function(){if($(this).closest('form').find('[name=pms_billing_email]').length==0)
return!1
if($(this).closest('form').find('[name=pms_billing_email]').hasClass('pms-has-value'))
return!1
$(this).closest('form').find('[name=pms_billing_email]').val($(this).val())})
$(document).on('keyup','#pms_first_name',function(){if($(this).closest('form').find('[name=pms_billing_first_name]').length==0)
return!1
if($(this).closest('form').find('[name=pms_billing_first_name]').hasClass('pms-has-value'))
return!1
$(this).closest('form').find('[name=pms_billing_first_name]').val($(this).val())})
$(document).on('keyup','#pms_last_name',function(){if($(this).closest('form').find('[name=pms_billing_last_name]').length==0)
return!1
if($(this).closest('form').find('[name=pms_billing_last_name]').hasClass('pms-has-value'))
return!1
$(this).closest('form').find('[name=pms_billing_last_name]').val($(this).val())})
function pms_handle_billing_state_field_display(){var country=$('#pms_billing_country').val()
if(PMS_States[country]){if(typeof PMS_ChosenStrings!=='undefined'&&$.fn.chosen!=undefined)
$('.pms-billing-state__select').chosen('destroy')
$('.pms-billing-state__select option').remove()
$('.pms-billing-state__select').append('<option value=""></option>');for(var key in PMS_States[country]){if(PMS_States[country].hasOwnProperty(key))
$('.pms-billing-state__select').append('<option value="'+key+'">'+PMS_States[country][key]+'</option>')}
var prevValue=$('.pms-billing-state__input').val()
if(prevValue!='')
$('.pms-billing-state__select').val(prevValue)
$('.pms-billing-state__input').removeAttr('name').removeAttr('id').hide()
$('.pms-billing-state__select').attr('name','pms_billing_state').attr('id','pms_billing_state').show()
if(typeof PMS_ChosenStrings!=='undefined'&&$.fn.chosen!=undefined)
$('.pms-billing-state__select').chosen(PMS_ChosenStrings)}else{if(typeof PMS_ChosenStrings!=='undefined'&&$.fn.chosen!=undefined)
$('.pms-billing-state__select').chosen('destroy')
$('.pms-billing-state__select').removeAttr('name').removeAttr('id').hide()
$('.pms-billing-state__input').attr('name','pms_billing_state').attr('id','pms_billing_state').show()}}
var $inviteCodeField=$(".pms-invite-code-field");if($inviteCodeField.length>0){toggleInviteCodeField();$(document).on("change","input[name='subscription_plans']",toggleInviteCodeField)}
function toggleInviteCodeField(){var $subscriptionPlans=$("input[name='subscription_plans']");if($subscriptionPlans.length==0){$inviteCodeField.hide();return}
var $selected;if($subscriptionPlans.length===1){$selected=$subscriptionPlans}else if($subscriptionPlans.length>1){$selected=$("input[name='subscription_plans']:checked")}
if(!$selected||!$selected.length){$inviteCodeField.hide();return}
var hasInviteCode=($selected.attr("data-has_invite_code")||"").toLowerCase();$inviteCodeField.toggle(hasInviteCode==="yes")}})
;