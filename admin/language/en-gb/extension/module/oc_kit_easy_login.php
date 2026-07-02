<?php
// Heading
$_['heading_title']    = 'oc-kit.com — Easy Login';

// Text
$_['text_extension']   = 'Extensions';
$_['text_success']     = 'Settings saved successfully!';
$_['text_edit']        = 'Module settings';
$_['text_module_name'] = 'Easy Login';
$_['text_module_description'] = 'Fast authentication via Google, Telegram, Apple, Facebook, Email Magic Link and SMS OTP.';
$_['text_enabled']     = 'Enabled';
$_['text_disabled']    = 'Disabled';
$_['text_yes']         = 'Yes';
$_['text_no']          = 'No';
$_['text_log_empty']   = 'Log is empty';
$_['text_confirm_clear_log'] = 'Clear the entire log? This cannot be undone.';
$_['text_confirm_clear_old'] = 'Delete old records?';
$_['text_log_cleared'] = 'Log cleared.';
$_['text_old_cleared'] = 'Old records deleted: %d';
$_['text_records_total'] = 'Total records: %d';

// Tabs
$_['tab_general']      = 'General';
$_['tab_google']       = 'Google';
$_['tab_telegram']     = 'Telegram';
$_['tab_apple']        = 'Apple';
$_['tab_facebook']     = 'Facebook';
$_['tab_email_magic']  = 'Email Magic Link';
$_['tab_sms_otp']      = 'SMS OTP';
$_['tab_log']          = 'Log';
$_['tab_faq']          = 'FAQ';
$_['tab_license']      = 'License';

// General — section titles
$_['text_section_status']       = 'Module status';
$_['text_section_display']      = 'Where to show login buttons';
$_['text_section_policies']     = 'Security and registration policies';
$_['text_section_rate_limits']  = 'Rate limiting';
$_['text_section_log_settings'] = 'Log settings';

// General — fields
$_['entry_status']                       = 'Status';
$_['entry_display_in_popup']             = 'Login popup';
$_['help_display_in_popup']              = 'Inject buttons into the login popup (via OCMOD).';
$_['entry_display_on_login_page']        = '<code>/account/login</code> page';
$_['entry_display_on_register_page']     = '<code>/account/register</code> page';
$_['entry_display_on_account_page']      = 'Account page';
$_['help_display_on_account_page']       = 'Show the "Linked accounts" section with provider management.';
$_['entry_require_phone_after_oauth']    = 'Require phone after OAuth login';
$_['help_require_phone_after_oauth']     = 'If a new user signs in via OAuth without a phone, force phone entry before granting account access.';
$_['entry_default_redirect_route']       = 'Default redirect after login';
$_['help_default_redirect_route']        = 'OC route to redirect to after successful login. Leave empty for standard behavior (account/account). Examples: <code>common/home</code>, <code>account/account</code>, <code>information/contact</code>.';
$_['entry_log_retention_days']           = 'Keep log entries (days)';
$_['help_log_retention_days']            = 'Records older than this will be removed automatically by cron.';
$_['entry_rate_limit_per_ip_per_hour']   = 'Requests per IP per hour';
$_['entry_rate_limit_per_email_per_hour']= 'Requests per email/phone per hour';
$_['entry_trust_cf_ip']                  = 'Trust Cloudflare client IP';
$_['help_trust_cf_ip']                   = 'Enable when the site is behind Cloudflare so rate-limits key off the real client IP (CF-Connecting-IP) instead of the CF proxy IP. Leave off if not using CF.';

// Log — table headers
$_['column_provider']    = 'Provider';
$_['column_status']      = 'Status';
$_['column_email']       = 'Email';
$_['column_customer_id'] = 'Customer';
$_['column_ip']          = 'IP';
$_['column_user_agent']  = 'User Agent';
$_['column_error']       = 'Error';
$_['column_created_at']  = 'Date';

// Log — filters
$_['entry_filter_provider']  = 'Provider';
$_['entry_filter_status']    = 'Status';
$_['entry_filter_email']     = 'Email';
$_['entry_filter_ip']        = 'IP';
$_['entry_filter_date_from'] = 'Date from';
$_['entry_filter_date_to']   = 'Date to';
$_['button_filter']          = 'Filter';
$_['button_reset_filter']    = 'Reset';
$_['button_clear_log']       = 'Clear entire log';
$_['button_clear_old']       = 'Delete old';

// Log — status badges
$_['status_success']      = 'Success';
$_['status_failed']       = 'Failed';
$_['status_rate_limited'] = 'Rate limited';
$_['status_linked']       = 'Linked';
$_['status_registered']   = 'Registered';

// Log — stats
$_['text_stats_total']        = 'Total';
$_['text_stats_success']      = 'Success';
$_['text_stats_failed']       = 'Failed';
$_['text_stats_rate_limited'] = 'Rate limited';
$_['text_stats_linked']       = 'Linked';
$_['text_stats_registered']   = 'Registered';

// Google
$_['text_section_google_credentials'] = 'Credentials';
$_['text_section_google_appearance']  = 'Appearance';
$_['entry_google_enabled']            = 'Enable Google';
$_['entry_google_mode']               = 'Mode';
$_['entry_google_client_id']          = 'Client ID';
$_['entry_google_client_secret']      = 'Client Secret';
$_['entry_google_one_tap_position']   = 'One Tap position';
$_['entry_google_button_theme']       = 'Button theme';
$_['entry_google_button_text']        = 'Button text';
$_['help_google_callback_url']        = 'Copy this URL to Google Cloud Console → Authorized redirect URIs:';
$_['help_google_mode']                = 'Button — standard click-based OAuth login. One Tap — native Google popup in a screen corner. Both — button + One Tap simultaneously.';
$_['mode_button']                     = 'Button only';
$_['mode_one_tap']                    = 'One Tap only';
$_['mode_both']                       = 'Button + One Tap';
$_['pos_top_right']                   = 'Top right';
$_['pos_top_left']                    = 'Top left';
$_['pos_bottom_right']                = 'Bottom right';
$_['pos_bottom_left']                 = 'Bottom left';
$_['entry_one_tap_top_offset']        = 'Top/bottom offset (px)';
$_['entry_one_tap_side_offset']       = 'Side offset (px)';
$_['help_one_tap_offset']             = 'Distance of One Tap popup from screen edge. Useful when site header overlaps the popup. Default: top/bottom = 0, side = 20.';
$_['theme_outline']                   = 'Outline';
$_['theme_filled_blue']               = 'Filled (blue)';
$_['theme_filled_black']              = 'Filled (black)';
$_['btn_text_signin_with']            = 'Sign in with Google';
$_['btn_text_signup_with']            = 'Sign up with Google';
$_['btn_text_continue_with']          = 'Continue with Google';

// Telegram
$_['text_section_telegram_credentials'] = 'Credentials';
$_['text_section_telegram_appearance']  = 'Appearance';
$_['entry_telegram_enabled']            = 'Enable Telegram';
$_['entry_telegram_bot_token']          = 'Bot Token';
$_['entry_telegram_bot_username']       = 'Bot Username';
$_['entry_telegram_button_size']        = 'Button size';
$_['entry_telegram_request_phone']      = 'Request phone number';
$_['help_telegram_setup']               = 'Create a bot via @BotFather in Telegram and obtain a token.';
$_['help_telegram_domain']              = 'After creating the bot, run in @BotFather: /setdomain — and specify the site domain (without https://).';
$_['help_telegram_bot_username']        = 'Bot username without @ (e.g. MyShopBot).';
$_['help_telegram_request_phone']       = 'If enabled, the widget will ask the user to share their phone number.';
$_['btn_size_large']                    = 'Large';
$_['btn_size_medium']                   = 'Medium';
$_['btn_size_small']                    = 'Small';

// Apple
$_['text_section_apple_credentials'] = 'Credentials';
$_['text_section_apple_appearance']  = 'Appearance';
$_['entry_apple_enabled']            = 'Enable Apple';
$_['entry_apple_service_id']         = 'Service ID';
$_['entry_apple_team_id']            = 'Team ID';
$_['entry_apple_key_id']             = 'Key ID';
$_['entry_apple_private_key']        = 'Private Key (.p8)';
$_['entry_apple_button_theme']       = 'Button theme';
$_['help_apple_setup']                = 'Create a Service ID + Sign in with Apple key in Apple Developer Console. Requires a paid account ($99/year).';
$_['help_apple_private_key']          = 'Paste the entire .p8 file content including BEGIN/END PRIVATE KEY lines.';
$_['theme_black']                     = 'Black';
$_['theme_white']                     = 'White';
$_['theme_white_outline']             = 'White with outline';

// Facebook
$_['text_section_facebook_credentials'] = 'Credentials';
$_['text_section_facebook_appearance']  = 'Appearance';
$_['entry_facebook_enabled']            = 'Enable Facebook';
$_['entry_facebook_app_id']             = 'App ID';
$_['entry_facebook_app_secret']         = 'App Secret';
$_['entry_facebook_button_size']        = 'Button size';
$_['help_facebook_setup']               = 'Create an app at Meta for Developers, configure Facebook Login → Valid OAuth Redirect URIs.';

// Email Magic
$_['text_email_magic_description']      = '<strong>Email Magic Link</strong> — passwordless sign-in. The user enters their email, receives a one-time link by mail, and clicking that link signs them into the account. The link expires after a configurable time (below) and works only once.';
$_['text_section_email_magic_settings'] = 'Settings';
$_['text_section_email_magic_template'] = 'Email template';
$_['entry_email_magic_enabled']         = 'Enable Email Magic Link';
$_['entry_email_magic_token_ttl_minutes'] = 'Link TTL (minutes)';
$_['entry_email_magic_from_name']       = 'Sender name';
$_['entry_email_magic_subject']         = 'Subject';
$_['entry_email_magic_template']        = 'HTML template';
$_['help_email_magic_template']         = 'Available placeholders: {magic_url}, {ttl_minutes}, {store_name}.';

// SMS OTP
$_['text_section_sms_otp_settings']  = 'Settings';
$_['text_section_sms_otp_text']      = 'SMS text';
$_['entry_sms_otp_enabled']          = 'Enable SMS OTP';
$_['entry_sms_otp_token']            = 'TurboSMS Token';
$_['entry_sms_otp_sender']           = 'Alpha-name (sender)';
$_['entry_sms_otp_code_length']      = 'Code length';
$_['entry_sms_otp_ttl_minutes']      = 'Code TTL (minutes)';
$_['entry_sms_otp_max_attempts']     = 'Max input attempts';
$_['entry_sms_otp_message']          = 'SMS text';
$_['help_sms_otp_message']           = 'Placeholder: {code} — will be replaced with the generated code.';

// FAQ
$_['text_faq_intro']    = 'Coming soon. Future updates will include instructions for obtaining credentials for each provider.';

// Buttons
$_['button_save']      = 'Save';
$_['button_cancel']    = 'Cancel';
$_['button_back']      = 'Back to settings';

// Account-linked (frontend section)
$_['heading_linked']     = 'Linked accounts';
$_['text_no_identities'] = 'No linked accounts.';
$_['text_link_more']     = 'Add another account:';
$_['text_confirm_unlink']= 'Unlink this account?';
$_['button_unlink']      = 'Unlink';

// Errors
$_['error_permission'] = 'You do not have permission to modify this module!';
$_['error_network']    = 'Network error. Please try again.';
$_['js_error_license_key_required'] = 'Enter license key';
$_['js_error_no_activate_url']      = 'Activation URL is missing';
$_['text_https_required_title']     = 'HTTPS required';
$_['text_https_required_body']      = 'Easy Login only works on HTTPS — Google/Apple/Facebook refuse http callbacks and Apple\'s state cookie requires Secure;SameSite=None. Configure SSL before using the module.';

// License
$_['text_license_title']         = 'License activation';
$_['text_license_subtitle']      = 'Enter your oc-kit.com license key to activate the module';
$_['text_license_status_active']  = 'Active';
$_['text_license_status_invalid'] = 'Invalid key';
$_['text_license_status_grace']   = 'Temporary access (API unreachable)';
$_['text_license_status_trial']   = 'Trial';
$_['text_license_status_expired'] = 'Expired';
$_['text_license_status_not_validated'] = 'Not activated';
$_['text_license_active']        = 'License activated! Redirecting…';
$_['text_license_invalid']       = 'Invalid key. Please check your input.';
$_['text_license_api_error']     = 'API unreachable. Please try again later.';
$_['text_license_domain']        = 'Domain';
$_['text_license_version']       = 'Version';
$_['text_license_get_key']       = 'Buy a license at oc-kit.com';
$_['entry_license_key']          = 'License key';
$_['button_activate']            = 'Activate';
