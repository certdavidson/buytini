<?php
// Account-linked (frontend section)
$_['heading_linked']     = 'Login methods';
$_['text_no_identities'] = 'No linked accounts.';
$_['text_link_more']     = 'Add another account:';
$_['text_confirm_unlink']= 'Remove this account from linked list?';
$_['button_unlink']      = 'Remove';

// Provider labels
$_['provider_google']      = 'Google';
$_['provider_facebook']    = 'Facebook';
$_['provider_apple']       = 'Apple';
$_['provider_telegram']    = 'Telegram';
$_['provider_email_magic'] = 'Email';
$_['provider_sms_otp']     = 'Phone number';

// Buttons block
$_['text_or']                  = 'or';
$_['button_continue_google']   = 'Continue with Google';
$_['button_google_signin_with']   = 'Sign in with Google';
$_['button_google_signup_with']   = 'Sign up with Google';
$_['button_google_continue_with'] = 'Continue with Google';
$_['button_continue_facebook'] = 'Continue with Facebook';
$_['button_continue_apple']    = 'Continue with Apple';
$_['button_send_magic']        = 'Send magic link';
$_['button_send_sms_code']     = 'Send SMS code';
$_['button_verify']            = 'Submit';

// JS messages
$_['js_error_email_required'] = 'Please enter email';
$_['js_success_magic_sent']   = 'Check your inbox.';
$_['js_error_phone_required'] = 'Enter phone';
$_['js_success_code_sent']    = 'Code sent';
$_['js_error_send_failed']    = 'Failed to send';
$_['js_error_code_required']  = 'Enter code';
$_['js_error_invalid_code']   = 'Invalid code';
$_['js_error_network']        = 'Network error';
$_['js_error_login_conflict'] = 'This profile is already linked to another account.';
$_['js_error_login_needs_confirmation'] = 'An account with this address already exists. Sign in the usual way and link the provider from your account settings.';

// ProviderException → user-facing messages
$_['err_invalid_phone']       = 'Invalid phone number';
$_['err_no_active_code']      = 'No active code. Request a new one.';
$_['err_too_many_attempts']   = 'Too many attempts. Request a new code.';
$_['err_code_used']           = 'Code already used. Request a new one.';
$_['err_provider_disabled']   = 'This sign-in method is temporarily unavailable';
$_['err_sms_failed']          = 'Could not send SMS. Please try again later.';
$_['err_link_expired']        = 'Link is invalid or expired';
$_['err_link_used']           = 'Link already used';
$_['err_too_many_requests']   = 'Too many requests. Try again in a minute.';
$_['err_generic']             = 'Authentication error';

$_['placeholder_email']        = 'email@example.com';
$_['placeholder_phone']        = '+380 __ ___ __ __';
$_['entry_phone']              = 'Phone number';
