<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 * @author oc-kit.com | https://oc-kit.com
 */

$_['heading_title']            = 'oc-kit.com — EasyCheckout';
$_['module_name']              = 'EasyCheckout';

$_['sidebar_assistant']        = 'Setup assistant';
$_['sidebar_general']          = 'General';
$_['sidebar_pages']            = 'Block layout';
$_['sidebar_page_checkout']    = 'Checkout';
$_['sidebar_page_general']     = 'General';
$_['sidebar_page_blocks']      = 'Block layout';
$_['sidebar_page_block_settings'] = 'Block settings';

$_['layout_heading']           = 'Checkout page block layout';
$_['layout_help']              = 'Drag blocks between steps and within a step. Save changes with the "Save layout" button.';
$_['layout_btn_save']          = 'Save layout';
$_['layout_btn_reset']         = 'Reset to defaults';
$_['layout_confirm_reset']     = 'Reset the layout to defaults? Unsaved changes will be lost (until you click "Save layout").';
$_['layout_reset_done']        = 'Layout reset to defaults. Click "Save layout" to apply.';
$_['layout_group_selector_label'] = 'Group:';
$_['layout_group_selector_help']  = 'Active editing group — its layout loads when you switch.';
$_['layout_columns_label']     = 'Columns:';
$_['layout_btn_add_step']      = 'Add step';
$_['layout_btn_add_row']       = 'Add row';
$_['layout_btn_remove_row']    = 'Remove row';
$_['layout_row_1_col']         = '1 column';
$_['layout_row_2_col']         = '2 columns';
$_['layout_row_3_col']         = '3 columns';
$_['layout_row_cols']          = 'Columns:';
$_['layout_stack_hint']        = 'On this viewport rows always stack to 1 column. Drag blocks to reorder them for this viewport only.';
$_['layout_custom_order_label']= 'Custom order';
$_['layout_reset_order']       = 'Reset to desktop order';
$_['layout_viewport_label']    = 'Preview:';
$_['layout_btn_add_block']     = 'Add block';
$_['layout_btn_remove_step']   = 'Remove step';
$_['layout_btn_remove_block']  = 'Remove block';
$_['layout_btn_settings']      = 'Block settings';
$_['layout_step_title']        = 'Step title';
$_['layout_step_placeholder']  = 'Step {n}';
$_['layout_no_more_blocks']    = 'All unique blocks are already used.';
$_['layout_saved']             = 'Layout saved';
$_['layout_block_settings_soon'] = 'Settings for this block — coming next update.';

$_['block_settings_visibility']        = 'Visibility';
$_['block_settings_visibility_help']   = 'Controls who and where sees this block. All toggles off = block always visible.';
$_['block_settings_audience']          = 'Audience';
$_['block_settings_hide_for_guests']   = 'Hide for guests';
$_['block_settings_hide_for_logged_in']= 'Hide for logged-in users';
$_['block_settings_viewports']         = 'Viewports';
$_['block_settings_viewports_help']    = 'Marked viewports — block is hidden there.';
$_['block_settings_text_content']      = 'Block text';
$_['block_settings_html_content']      = 'HTML content';
$_['block_settings_advanced']          = 'Advanced settings';
$_['block_settings_advanced_soon']     = 'Type-specific options (fields, field-sets, module filters) coming in next updates.';
$_['block_settings_options']           = 'Block options';
$_['block_settings_display']           = 'Display';

$_['block_settings_agreement_required']      = 'Consent required';
$_['block_settings_agreement_required_help'] = 'If enabled — user cannot place the order without ticking the box.';

$_['block_settings_registration_mode']  = 'Registration on checkout';
$_['registration_mode_optional']        = 'User choice';
$_['registration_mode_required']        = 'Required';
$_['registration_mode_disabled']        = 'Not needed';
$_['block_settings_show_login_link']    = 'Show login link';

$_['block_settings_show_image']             = 'Image';
$_['block_settings_show_model']             = 'SKU / model';
$_['block_settings_show_quantity_controls'] = 'Quantity +/- buttons';
$_['block_settings_show_remove_btn']        = 'Remove button';
$_['block_settings_show_cart_subtotal']     = 'Cart subtotal';

$_['block_settings_show_subtotal']      = 'Subtotal';
$_['block_settings_show_taxes']         = 'Taxes';
$_['block_settings_show_coupon_input']  = 'Coupon input';
$_['block_settings_show_voucher_input'] = 'Voucher input';
$_['block_settings_show_reward_input']  = 'Reward points input';

$_['block_settings_display_mode']      = 'Variants display';
$_['block_settings_display_radio']     = 'Radio buttons';
$_['block_settings_display_select']    = 'Dropdown';
$_['block_settings_auto_select_first'] = 'Auto-select first variant';
$_['block_settings_show_description']  = 'Show variant descriptions';

$_['block_settings_submit_text']             = '"Place order" button text';
$_['block_settings_submit_text_help']        = 'Leave empty to use the default text from language file.';
$_['block_settings_show_agreement_inline']   = 'Show consent checkbox next to button';
$_['block_settings_show_agreement_inline_help']= 'Useful when there\'s no separate "Agreement" block in layout.';
$_['block_settings_sticky_on_mobile']        = 'Sticky on mobile';
$_['block_settings_sticky_on_mobile_help']   = 'Button sticks to viewport bottom while scrolling (mobile only).';

$_['block_settings_show_company']            = 'Show "Company" field';
$_['block_settings_address_fieldset_hint']   = 'The actual address field-set (country/zone/city/NovaPoshta) is configured in "Fields" section.';

$_['block_settings_payment_form_hint']       = 'This block is rendered by the selected payment module — no extra options here.';

$_['block_settings_fields']         = 'Fields in this block';
$_['block_settings_fields_help']    = 'Pick fields from the registry to use in this block. Order = render order on the page.';
$_['block_settings_fields_empty']   = 'No fields yet. Add some from the registry using the button below.';
$_['block_settings_field_add']      = 'Add field';
$_['block_settings_field_no_more']  = 'No more matching fields in registry. Create new ones in "Fields".';
$_['block_settings_field_remove']   = 'Remove field';
$_['block_settings_field_up']       = 'Move up';
$_['block_settings_field_down']     = 'Move down';
$_['block_settings_field_reorder']  = 'Drag';
$_['block_settings_field_required'] = 'Required';
$_['block_settings_field_reload']   = 'Reload blocks on change';
$_['block_settings_field_vis_always']= 'Always';
$_['block_settings_field_vis_guests']= 'Guests only';
$_['block_settings_field_vis_logged']= 'Logged-in only';
$_['block_settings_field_width']     = 'Field width';
$_['block_settings_field_width_full']       = 'Full width';
$_['block_settings_field_width_two_thirds'] = '2/3';
$_['block_settings_field_width_half']       = '1/2';
$_['block_settings_field_width_third']      = '1/3';

// Native field labels (fallback for those missing in sale/order)
$_['entry_password']        = 'Password';
$_['entry_confirm']         = 'Confirm password';
$_['entry_newsletter']      = 'Newsletter subscription';
$_['text_account_register'] = 'Registration';
$_['text_agree']            = 'Terms agreement';

$_['groups_heading']           = 'Settings groups';
$_['groups_help']               = 'Alternative checkout configurations. Each group is its own "project" with own layout, blocks, fields. Activated via `/easycheckout?group=slug`.';
$_['groups_empty']              = 'No groups yet. Create the first one — e.g. "B2B" or "Wholesale".';
$_['groups_btn_add']            = 'Add group';
$_['groups_btn_clone']          = 'Clone';
$_['groups_btn_clone_create']   = 'Create clone';
$_['groups_col_id']             = 'ID';
$_['groups_col_name']           = 'Name';
$_['groups_col_slug']           = 'Slug';
$_['groups_col_default']        = 'Default';
$_['groups_col_sort']           = 'Sort';
$_['groups_col_url_example']    = 'URL';
$_['groups_col_actions']        = 'Actions';
$_['groups_is_default']         = 'Default';

$_['groups_modal_title_add']    = 'Create group';
$_['groups_modal_title_edit']   = 'Edit group';
$_['groups_modal_title_clone']  = 'Clone group';
$_['groups_clone_help']         = 'A new group will be created with a full copy of layout, settings and filters of the existing one.';

$_['entry_group_name']          = 'Name';
$_['entry_group_slug']          = 'Slug (for URL)';
$_['entry_group_is_default']    = 'Default';
$_['entry_group_sort_order']    = 'Sort order';
$_['help_group_name']           = 'Internal name, shown only in admin.';
$_['help_group_slug']           = 'Latin letters, digits, hyphen. Used in URL: /easycheckout?group={slug}.';
$_['help_group_is_default']     = 'Active group when URL has no `group` param. Only one group can be default.';

$_['text_group_saved']          = 'Group saved';
$_['text_group_deleted']        = 'Group deleted';
$_['text_group_cloned']         = 'Group cloned';
$_['text_group_validation_error']= 'Please check the data';
$_['text_confirm_delete_group']  = 'Delete group "{name}" along with all its settings?';
$_['error_group_required']           = 'Required field';
$_['error_group_invalid_format']     = 'Invalid format (latin letters, digits, hyphen)';
$_['error_group_duplicate']          = 'Slug already in use';
$_['error_group_too_long']           = 'Value too long';
$_['error_group_cannot_delete_default']= 'Cannot delete the default group. Make another default first.';
$_['error_group_not_found']          = 'Group not found';

$_['block_type_customer']         = 'Customer';
$_['block_type_cart']             = 'Cart';
$_['block_type_payment_address']  = 'Payment address';
$_['block_type_shipping_address'] = 'Shipping address';
$_['block_type_shipping']         = 'Shipping';
$_['block_type_payment']          = 'Payment';
$_['block_type_comment']          = 'Comment';
$_['block_type_agreement']        = 'Agreement / consent';
$_['block_type_help']             = 'Help';
$_['block_type_summary']          = 'Summary';
$_['block_type_payment_form']     = 'Payment module form';
$_['block_type_buttons']          = 'Buttons & checkboxes';
$_['block_type_custom_html']      = 'Custom HTML';
$_['sidebar_fields']           = 'Fields';
$_['sidebar_headings']         = 'Headings';
$_['sidebar_misc']             = 'Other';
$_['sidebar_misc_link_replace']  = 'Link replacement';
$_['sidebar_misc_error_display'] = 'Error display';
$_['sidebar_misc_theme']         = 'Theme integration';
$_['sidebar_misc_javascript']    = 'JavaScript';
$_['sidebar_misc_modules']       = 'Modules';
$_['sidebar_misc_address_format']= 'Address formats';
$_['sidebar_groups']           = 'Settings groups';
$_['sidebar_abandoned']        = 'Abandoned carts';
$_['sidebar_health']           = 'Status check';
$_['sidebar_presets']          = 'Presets';
$_['presets_heading']          = 'Starter presets';
$_['presets_help']             = 'Ready-made layout templates. Click Apply to overwrite the currently active group with the selected preset.';
$_['preset_applied']           = 'Preset applied';
$_['preset_apply_confirm']     = 'Replace current layout with the selected preset?';
$_['sidebar_address_formats']  = 'Address formats';
$_['sidebar_restrictions']     = 'Order restrictions';
$_['address_formats_heading']  = 'Address formats';
$_['address_formats_help']     = 'Address rendering templates for emails and order panel. Supported placeholders: {firstname}, {lastname}, {company}, {address_1}, {city}, {postcode}, {country}, {zone} + {custom.field_code}';
$_['address_formats_col_scope']    = 'Type';
$_['address_formats_col_scope_id'] = 'Value';
$_['address_formats_col_language'] = 'Language';
$_['address_formats_col_template'] = 'Template';
$_['address_formats_help_scope_id']= 'For shipping — module code (np, flat). For customer_group — group id.';
$_['address_formats_help_template']= 'Use {placeholder} for substitutions. Each line of the textarea = one address line.';
$_['address_formats_placeholders_label'] = 'Available placeholders:';
$_['address_formats_placeholders_insert'] = 'Insert into template';
$_['address_formats_empty']    = 'No formats yet. Add the first one.';
$_['restrictions_heading']     = 'Order restrictions';
$_['restrictions_help']        = 'Block order based on rules: total / quantity / weight. Confirm halts with error_text if any rule matches.';
$_['restrictions_col_groups']  = 'Customer groups';
$_['restrictions_col_total']   = 'Total (min/max)';
$_['restrictions_col_qty']     = 'Quantity (min/max)';
$_['restrictions_col_weight']  = 'Weight (min/max)';
$_['restrictions_col_error']   = 'Error text';
$_['restrictions_help_groups'] = 'Pick customer groups. Empty — applies to all.';
$_['restrictions_groups_placeholder'] = 'Click to choose';
$_['restrictions_help_error']  = 'Shown to customer when the place-order button is blocked.';
$_['restrictions_label_total']  = 'Order total';
$_['restrictions_label_qty']    = 'Item quantity';
$_['restrictions_label_weight'] = 'Weight';
$_['restrictions_label_sort']   = 'Sort order';
$_['address_formats_scope_customer_group'] = 'Customer group';
$_['address_formats_scope_shipping']       = 'Shipping method';
$_['address_formats_scope_id_ph_shipping'] = 'flat / np / cod';
$_['address_formats_scope_id_ph_groups']   = '1, 2, 3 (group ids)';
$_['restrictions_empty']       = 'No restrictions yet.';
$_['misc_heading']             = 'Misc';
$_['misc_help']                = 'Error display, theme integration, JavaScript injection.';
$_['misc_error_heading']       = 'Error display';
$_['entry_error_display_mode'] = 'Display mode:';
$_['error_mode_inline']        = 'Inline under field';
$_['error_mode_top']           = 'Top summary';
$_['error_mode_toast']         = 'Toast notification';
$_['help_error_display_mode']  = 'How to render validation errors.';
$_['entry_error_scroll_to_first']= 'Scroll to first error';
$_['help_error_scroll_to_first']= 'Auto-scroll to first invalid field on confirm.';
$_['misc_theme_heading']       = 'Theme integration';
$_['entry_theme_wrapper']      = 'Wrapper CSS selector:';
$_['help_theme_wrapper']       = 'Where to mount checkout page. Default: .main-container';
$_['entry_theme_remove_breadcrumbs']= 'Remove breadcrumbs';
$_['help_theme_remove_breadcrumbs']= 'Hide breadcrumbs on checkout page.';
$_['misc_js_heading']          = 'JavaScript';
$_['misc_js_help']             = 'Custom JS snippets at specific frontend lifecycle hooks. Useful for GA, Pixel, GTM.';
$_['entry_js_before_init']     = 'Before init:';
$_['entry_js_after_init']      = 'After init:';
$_['entry_js_before_confirm']  = 'Before confirm:';
$_['license_heading']          = 'License';
$_['license_help']              = 'Module license status and key activation.';
$_['license_status_active']    = 'License is active';
$_['license_status_invalid']   = 'License is invalid';
$_['license_label_plan']       = 'Plan:';
$_['license_label_domain']     = 'Domain:';
$_['license_label_updates']    = 'Updates until:';
$_['license_activate_heading'] = 'Activate key';
$_['license_label_key']        = 'License key:';
$_['license_key_help']         = 'Enter your key and click Activate to bind the module to your domain.';
$_['license_activated']        = 'License activated';
$_['license_activate_failed']  = 'Failed to activate license';
$_['button_activate']          = 'Activate';
$_['sidebar_modules']          = 'Payment / Shipping';
$_['modules_heading']          = 'Payment & Shipping modules';
$_['modules_help']             = 'Rename, change the icon or reorder shipping and payment methods — the way the customer sees them on the checkout page. The modules themselves are not changed, only their appearance in this checkout.';
$_['modules_payment_heading']  = 'Payment methods';
$_['modules_shipping_heading'] = 'Shipping methods';
$_['modules_col_status']       = 'Active';
$_['modules_col_override_title']= 'Custom title';
$_['modules_col_override_description'] = 'Description';
$_['modules_col_override_icon']        = 'Icon';
$_['modules_col_sort']         = 'Sort order';
$_['modules_col_hide']         = 'Hide';
$_['modules_empty']            = 'No installed extensions found.';
$_['sidebar_license']          = 'License';

$_['tab_general']              = 'General';
$_['entry_status']             = 'Module status';
$_['entry_route']              = 'Page route';
$_['entry_default_group']      = 'Default group';
$_['entry_replace_checkout_links'] = 'Replace standard /checkout links';
$_['help_replace_checkout_links']  = 'If enabled, OCMOD will replace all /checkout/checkout links in the catalog with /easycheckout. Disable to coexist with the stock checkout.';

$_['entry_integration']         = 'Frontend integration';
$_['help_integration']           = 'Activates /easycheckout SEO URL and registers a redirect from standard /checkout. Without this you get 404 on /easycheckout.';
$_['integration_active']         = 'Active';
$_['integration_inactive']       = 'Not configured';
$_['integration_btn_setup']      = 'Activate';
$_['integration_btn_remove']     = 'Deactivate';
$_['integration_languages']      = 'languages';
$_['integration_event_active']   = 'Redirect /checkout → /easycheckout active';
$_['integration_event_inactive'] = 'Redirect /checkout → /easycheckout not registered';
$_['integration_activated']      = 'Integration activated.';
$_['integration_deactivated']    = 'Integration deactivated.';
$_['help_route']                   = 'Configured via OCMOD as an /easycheckout alias. Changing it requires refreshing modifications.';

$_['button_save']              = 'Save';
$_['button_cancel']            = 'Cancel';
$_['button_apply']             = 'Apply';
$_['button_add']               = 'Add';
$_['button_bulk_edit']         = 'Bulk edit';
$_['bulk_edit_modal_title']    = 'Bulk edit selected fields';
$_['bulk_edit_apply_to']       = 'Changes will apply to';
$_['bulk_edit_apply_to_suffix']= 'selected fields. Leave empty — no change.';
$_['fields_filter_usage']      = 'Usage';
$_['bulk_edit_no_change']      = 'no change';
$_['bulk_edit_yes']            = 'Yes';
$_['bulk_edit_no']             = 'No';
$_['button_close']             = 'Close';
$_['button_delete']            = 'Delete';
$_['button_edit']              = 'Edit';

$_['fields_heading']           = 'Fields';
$_['fields_help']              = 'Global fields registry. Fields are inserted into checkout blocks. Type, mask, default value, and validation rules are defined here.';
$_['fields_native_heading']    = 'OpenCart default fields';
$_['fields_native_help']       = 'Labels, placeholders and tooltips for OC default fields (name, phone, city, etc.). Leave blank to use the default OpenCart label.';
$_['fields_native_modal_title'] = 'Default field';
$_['fields_empty']             = 'No fields yet. Add the first one — for example phone, name, or comment.';
$_['fields_filter_search']     = 'Search by name or code';
$_['fields_filter_type']       = 'Type';
$_['fields_filter_belongs_to'] = 'Belongs to';
$_['fields_filter_all']        = 'All';
$_['fields_btn_add']           = 'Add field';
$_['fields_btn_delete_selected'] = 'Delete selected';
$_['fields_col_id']            = 'ID';
$_['fields_col_code']          = 'Code';
$_['fields_col_type']          = 'Type';
$_['fields_col_belongs_to']    = 'Belongs to';
$_['fields_col_name']          = 'Name';
$_['fields_col_modified']      = 'Modified';
$_['fields_col_actions']       = 'Actions';
$_['fields_modal_title_add']   = 'Create field';
$_['fields_modal_title_edit']  = 'Edit field';
$_['fields_section_text']      = 'Texts';
$_['fields_section_params']    = 'Parameters';
$_['fields_section_mask']      = 'Mask';
$_['fields_section_default']   = 'Default value';
$_['fields_section_validation']= 'Validation rules';
$_['fields_section_options']   = 'Options';
$_['entry_field_code']         = 'Field identifier';
$_['entry_field_type']         = 'Field type';
$_['entry_field_belongs_to']   = 'Belongs to';
$_['entry_field_name']         = 'Name';
$_['entry_field_tooltip']      = 'Tooltip';
$_['entry_field_placeholder']  = 'Placeholder';
$_['entry_field_use_mask']     = 'Use input mask';
$_['help_field_use_mask']      = 'Enable only when the field needs formatted input — phone, postal code, card number. For plain text, emails, names — leave off.';
$_['entry_field_use_default']  = 'Set a default value';
$_['help_field_use_default']   = 'When enabled, the field will be pre-filled with the specified value (or a value from the API method).';
$_['entry_field_mask_mode']    = 'Mask mode';
$_['entry_field_mask_value']   = 'Mask value';
$_['entry_field_default_mode'] = 'Default mode';
$_['entry_field_default_value']= 'Value';
$_['entry_field_save_to_comment'] = 'Save value to order comment';
$_['entry_field_options']      = 'Options list';
$_['help_field_code']          = 'Latin letters/digits/underscores, starts with a letter. Unique within the module.';
$_['help_field_save_to_comment'] = 'If enabled, the value will be appended to the order comment after checkout.';
$_['help_field_mask']          = 'IMask input pattern. Example: +38(999) 999-99-99 for phone. "9" means any digit. Other characters appear as-is.';
$_['help_field_default']       = 'Value that will be substituted into the field if not entered. Can be supplied via API method.';
$_['help_field_options']       = 'One option per line: value=Label. Labels can be set per language via tabs.';
$_['mode_manual']              = 'Set manually';
$_['mode_api']                 = 'Via module API (catalog/model/tool/easycheckoutapi.php)';
$_['entry_field_api_method']   = 'Method name';
$_['help_field_api_method']    = 'Public method of ModelToolEasycheckoutapi. Receives ($field_code, $context), returns a string.';
$_['belongs_to_order']         = 'Order';
$_['belongs_to_customer']      = 'Customer';
$_['belongs_to_address']       = 'Address';
$_['option_label']             = 'Label';
$_['option_value']             = 'Value';
$_['option_add']               = 'Add option';
$_['option_remove']            = 'Remove';

$_['rules_help']               = 'Rules apply only when the field is shown on the page. "Required" is configured separately in the block field-set.';
$_['rules_empty']              = 'No rules yet. Add the first one — "Not empty" or "Regular expression".';
$_['rules_btn_add']            = 'Add rule';
$_['rules_error_text']         = 'Error message';
$_['rules_remove']             = 'Remove rule';
$_['rule_type_not_empty']      = 'Not empty';
$_['rule_type_length']         = 'Length range';
$_['rule_type_regex']          = 'Regular expression';
$_['rule_type_api']            = 'Module API method';
$_['rule_type_match']          = 'Match another field';
$_['rule_param_min']           = 'Min';
$_['rule_param_max']           = 'Max';
$_['rule_param_pattern']       = 'Pattern (PCRE)';
$_['rule_param_method']        = 'Method name in easycheckoutapi.php';
$_['rule_param_field_code']    = 'Field code to match';
$_['placeholder_rule_pattern'] = '^[^\s@]+@[^\s@]+\.[^\s@]+$';
$_['placeholder_rule_error']   = 'Invalid value';
$_['mask_preview_label']       = 'Test input';
$_['mask_preview_placeholder'] = 'Try typing to verify the mask...';

$_['fields_section_type_params'] = 'Type parameters';
$_['entry_consent_policy_url'] = 'Policy/agreement URL';
$_['entry_consent_version']    = 'Policy version';
$_['entry_consent_store_meta'] = 'Store consent metadata (IP, timestamp, version)';
$_['help_consent_version']     = 'Bump this when policy changes — old consents become invalid.';
$_['entry_tel_default_country']    = 'Default country (ISO2)';
$_['entry_tel_preferred_countries']= 'Preferred countries (comma-separated)';
$_['help_tel_preferred']           = 'ISO2 codes (UA, PL, US, ...) shown at the top of the picker.';
$_['entry_np_scope']           = 'Autocomplete scope';
$_['entry_np_api_key']         = 'Nova Poshta API key';
$_['help_np_api_key']          = 'Get one from your Nova Poshta account.';
$_['help_integration_global_keys'] = 'Nova Poshta API key is set globally in "General settings → Integrations" (coming next iteration).';
$_['np_scope_city']            = 'City';
$_['np_scope_warehouse']       = 'Warehouse';
$_['entry_computed_source']    = 'Value source';
$_['help_computed_source']     = 'Where to read the value from.';
$_['computed_source_utm_source']  = 'UTM Source';
$_['computed_source_utm_medium']  = 'UTM Medium';
$_['computed_source_utm_campaign']= 'UTM Campaign';
$_['computed_source_utm_content'] = 'UTM Content';
$_['computed_source_utm_term']    = 'UTM Term';
$_['computed_source_referrer']    = 'Referer (HTTP)';
$_['computed_source_cookie']      = 'Cookie (specify name)';
$_['computed_source_expression']  = 'JS expression (advanced)';
$_['entry_computed_extra']     = 'Source parameter';
$_['entry_group_columns']      = 'Columns';

$_['entry_date_disable_past']    = 'Disable past dates';
$_['entry_date_min_days_ahead']  = 'Minimum days from today';
$_['entry_date_max_days_ahead']  = 'Maximum days from today';
$_['help_date_min_days_ahead']   = 'E.g., 1 = pick from tomorrow. 0 = from today.';
$_['help_date_max_days_ahead']   = 'Leave empty for no limit. E.g., 14 = 2 weeks ahead.';
$_['entry_date_weekends']        = 'Weekends';
$_['help_date_weekends']         = 'Days of week that cannot be selected. Hold Ctrl/Cmd to multi-select.';

$_['entry_time_working_hours']   = 'Working hours';
$_['entry_time_working_from']    = 'From';
$_['entry_time_working_to']      = 'To';
$_['entry_time_slot_minutes']    = 'Slot interval';
$_['help_time_slot_minutes']     = 'Time is divided into slots of this length (default 30 min).';
$_['entry_time_min_hours_ahead'] = 'Minimum hours from now';
$_['help_time_min_hours_ahead']  = 'E.g., 2 = earliest slot today is 2 hours from now. Rule does not apply for following days.';
$_['entry_time_weekends']        = 'Weekends';

$_['weekday_0'] = 'Sunday';
$_['weekday_1'] = 'Monday';
$_['weekday_2'] = 'Tuesday';
$_['weekday_3'] = 'Wednesday';
$_['weekday_4'] = 'Thursday';
$_['weekday_5'] = 'Friday';
$_['weekday_6'] = 'Saturday';

$_['entry_consent_information_id']  = 'Information page';
$_['help_consent_information_id']   = 'Start typing the title — the system will find a page from Catalog → Information. Link text comes from the page title (or from custom label below).';
$_['entry_consent_custom_label']    = 'Custom label (optional)';
$_['help_consent_custom_label']     = 'If filled — used instead of page title. Multi-language.';
$_['placeholder_information_search']= 'Start typing the page title...';

$_['settings_section_integrations']      = 'Integrations';
$_['settings_section_country']           = 'Default country';
$_['settings_help_integrations']         = 'Global API keys and settings for external services. Used by autocomplete-type fields.';
$_['entry_default_country']              = 'Default country';
$_['help_default_country']               = 'Used when "Country" field is not on the form but zone/city are (zones depend on country).';
$_['entry_integration_np_api_key']       = 'Nova Poshta API key';
$_['help_integration_np_api_key']        = 'Get one from your Nova Poshta account.';
$_['entry_integration_ukrposhta_api_key']= 'Ukrposhta API key';
$_['help_integration_ukrposhta_api_key'] = 'Get one from your Ukrposhta account.';

$_['headings_heading']         = 'Headings';
$_['headings_help']            = 'Global text headings, can be inserted between fields inside blocks.';
$_['headings_empty']           = 'No headings yet. Add the first one — e.g. "Contacts" or "Shipping".';
$_['headings_filter_search']   = 'Search by code or text';
$_['headings_filter_tag']      = 'Tag';
$_['headings_btn_add']         = 'Add heading';
$_['headings_btn_delete_selected'] = 'Delete selected';
$_['headings_col_id']          = 'ID';
$_['headings_col_code']        = 'Code';
$_['headings_col_tag']         = 'Tag';
$_['headings_col_text']        = 'Text';
$_['headings_col_modified']    = 'Modified';
$_['headings_col_actions']     = 'Actions';
$_['headings_modal_title_add'] = 'Create heading';
$_['headings_modal_title_edit']= 'Edit heading';
$_['entry_heading_code']       = 'Identifier';
$_['entry_heading_tag']        = 'Tag';
$_['entry_heading_text']       = 'Text';
$_['heading_tag_none']         = 'No tag';
$_['heading_tag_h1']           = 'H1';
$_['heading_tag_h2']           = 'H2';
$_['heading_tag_h3']           = 'H3';
$_['heading_tag_h4']           = 'H4';
$_['heading_tag_h5']           = 'H5';
$_['heading_tag_p']            = 'p';
$_['heading_tag_legend']       = 'Legend';
$_['text_heading_saved']       = 'Heading saved';
$_['text_heading_deleted']     = 'Heading deleted';
$_['text_heading_validation_error'] = 'Please check the data';
$_['text_confirm_delete_heading']   = 'Delete this heading?';
$_['text_confirm_delete_headings']  = 'Delete selected headings?';
$_['error_heading_text_required']   = 'Text is required in at least one language';
$_['text_field_saved']         = 'Field saved';
$_['text_field_deleted']       = 'Field deleted';
$_['text_field_validation_error']= 'Please check the data';
$_['text_confirm_delete_field']  = 'Delete this field?';
$_['text_confirm_delete_fields'] = 'Delete selected fields?';
$_['error_field_code_required']  = 'Identifier is required';
$_['error_field_code_format']    = 'Identifier format: latin letter, then letters/digits/underscores (up to 64 chars)';
$_['error_field_code_duplicate'] = 'Identifier is already in use';
$_['error_field_code_reserved']  = 'This identifier is reserved by a native checkout field (email, city, address_1, etc.). Choose another.';
$_['error_field_type_invalid']   = 'Unknown field type';
$_['error_field_name_required']  = 'Name is required in at least one language';

$_['fields_group_basic']    = 'Basic';
$_['fields_group_datetime'] = 'Date and time';
$_['fields_group_hidden']   = 'Hidden / technical';
$_['fields_group_address']  = 'Address';
$_['fields_group_special']  = 'Special';
$_['fields_group_struct']   = 'Structure';

$_['field_type_text']                  = 'Text';
$_['field_type_textarea']              = 'Textarea';
$_['field_type_select']                = 'Select';
$_['field_type_radio']                 = 'Radio buttons';
$_['field_type_checkbox']              = 'Checkbox';
$_['field_type_date']                  = 'Date';
$_['field_type_hidden']                = 'Hidden';
$_['field_type_html']                  = 'HTML';
$_['field_type_segmented']             = 'Button group';
$_['field_type_consent']               = 'Document consent';
$_['field_type_tel_intl']              = 'International phone';
$_['field_type_autocomplete_np']       = 'Nova Poshta autocomplete';
$_['field_type_autocomplete_ukrposhta']= 'Ukrposhta autocomplete';
$_['field_type_country']               = 'Country';
$_['field_type_zone']                  = 'Zone / region';
$_['field_type_city']                  = 'City / locality';
$_['field_type_time']                  = 'Time';
$_['field_type_computed_hidden']       = 'Auto-parameter';
$_['field_type_group']                 = 'Field group';
$_['field_type_address_select']        = 'Address book select';
$_['field_type_file']                  = 'File upload';

$_['text_enabled']             = 'Enabled';
$_['text_disabled']            = 'Disabled';
$_['text_yes']                 = 'Yes';
$_['text_no']                  = 'No';
$_['text_success']             = 'Settings saved!';
$_['text_extension']           = 'Extensions';
$_['text_module_brand']        = 'oc-kit.com';
$_['text_version']             = 'Version';
$_['text_dev_stage']           = 'Module is under development. Sections will become available step by step.';
$_['text_coming_soon']         = 'Section under development';

$_['tab_license']              = 'License';
$_['entry_license_key']        = 'License key';
$_['text_extensions']          = 'Extensions';
$_['text_license_active']      = 'License is active';
$_['text_license_invalid']     = 'Invalid key';
$_['text_license_expired']     = 'License expired';
$_['text_license_trial']       = 'Trial: %d days remaining';
$_['text_license_not_validated']= 'No key entered';
$_['text_license_version']     = 'Version';
$_['text_license_domain']      = 'Domain';
$_['text_license_buy']         = 'Buy license';
$_['text_license_api_error']   = 'API unavailable, try again later';

$_['error_permission']         = 'You do not have permission to modify this module!';
$_['error_install']            = 'Module installation error.';

$_['js_saving']                = 'Saving...';
$_['js_saved']                 = 'Saved';
$_['js_error']                 = 'Error';
$_['js_network_error']         = 'Network error. Please try again.';
$_['js_confirm']               = 'Are you sure?';

// Order info admin tab
$_['text_order_tab_col_field']  = 'Field';
$_['text_order_tab_col_value']  = 'Value';
$_['text_order_tab_col_type']   = 'Type';

// Abandoned section
$_['abandoned_heading']        = 'Abandoned checkouts';
$_['abandoned_help']           = 'Users started checkout but did not complete. Copy a recovery URL to send back to the customer.';
$_['cron_last_run_label']      = 'Last cron run:';
$_['cron_never_ran']           = 'Cron job never ran';
$_['cron_never_ran_help']      = 'Configure a cron job to run crons/cron_easycheckout_reminder.php — otherwise reminders will not be sent.';
$_['abandoned_empty']          = 'No abandoned checkouts.';
$_['abandoned_col_name']       = 'Name';
$_['abandoned_col_customer']  = 'Customer';
$_['abandoned_col_phone']      = 'Phone';
$_['abandoned_col_total']      = 'Total';
$_['abandoned_col_products']   = 'Items';
$_['abandoned_col_modified']   = 'Updated';
$_['text_copy_recovery_url']   = 'Copy recovery URL';
$_['text_copied']              = 'Copied';

// Abandoned reminder
$_['entry_reminder']         = 'Abandoned-checkout email reminder';
$_['help_reminder']          = 'Automatically emails customers a cart-recovery link when they do not finish checkout.';
$_['entry_reminder_enabled'] = 'Enable reminder';
$_['entry_reminder_delay']   = 'Delay (minutes)';
$_['help_reminder_delay']    = 'How many minutes to wait after last activity before sending the reminder.';

// Layout preview
$_['layout_btn_preview']     = 'Preview';
$_['layout_preview_title']   = 'Layout preview';

// Reminder template
$_['entry_reminder_template'] = 'Reminder email template';
$_['help_reminder_template']  = 'Substitutions: <code>{firstname}</code>, <code>{lastname}</code>, <code>{email}</code>, <code>{store_name}</code>, <code>{recovery_url}</code>, <code>{total}</code>, <code>{currency}</code>.';
$_['entry_reminder_subject']  = 'Subject';
$_['entry_reminder_body']     = 'Body (HTML)';

// Layout store selector
$_['layout_store_label']    = 'Store:';
$_['layout_store_help']     = 'Layout is stored per store. If no record for a store — default layout is used.';
$_['layout_copy_from_label']= 'Copy layout from store:';
$_['layout_copy_from_btn']  = 'Copy';
$_['layout_copy_from_help'] = 'Loads layout from the selected store into the current one as unsaved state. Click Save to apply.';
$_['layout_copy_from_confirm'] = 'Replace current layout with copy from selected store?';
$_['layout_copied']         = 'Layout copied — review and Save';
$_['layout_warnings_heading'] = 'Layout warnings';
$_['layout_warn_loc_step']    = 'step';
$_['layout_warn_loc_row']     = 'row';
$_['layout_warn_loc_cell']    = 'cell';
$_['layout_warn_loc_multiple']= 'multiple blocks';
$_['layout_warn_empty_cell']            = 'Empty cell — it has no blocks';
$_['layout_warn_empty_row']             = 'Empty row — it has no cells';
$_['layout_warn_empty_step']            = 'Empty step — it has no rows';
$_['layout_warn_block_condition_broken']= 'Block visibility condition references a deleted field: %source_code%';
$_['layout_warn_field_missing']         = 'Block references a deleted field (ID %field_id%)';
$_['layout_warn_field_condition_broken']= 'Field condition references a deleted field: %source_code%';
$_['layout_warn_heading_missing']       = 'Block references a deleted heading (ID %heading_id%)';
$_['layout_warn_field_duplicate']       = 'Field (ID %field_id%) is used in %count% blocks — likely a duplicate';

// Abandoned stats
$_['abandoned_stats_days']      = 'Period (days):';
$_['abandoned_stats_total']     = 'Started checkouts';
$_['abandoned_stats_recovered'] = 'Completed';
$_['abandoned_stats_lost']      = 'Lost amount';
$_['abandoned_stats_reminder']  = 'Reminder conversions';

// CSV export
$_['button_export_csv'] = 'Export CSV';

// Reminder test
$_['button_reminder_test']     = 'Send test email';
$_['button_reminder_preview']  = 'Preview template';
$_['entry_reminder_delays']    = 'Reminder stages (minutes, comma-separated):';
$_['help_reminder_delays']     = 'Delays for multi-cadence reminders. Example: "60, 1440, 4320" = 1 hour, 1 day, 3 days. Leave empty to use single delay above.';
$_['health_heading']           = 'Health check';
$_['health_help']              = 'Module configuration checks. Fix all red items for proper operation.';
$_['health_check_module_status']= 'Module enabled';
$_['health_check_cron_recent']  = 'Automatic reminders';
$_['health_check_mail_engine']  = 'Email sending';
$_['health_check_db_tables']    = 'Module data in database';
$_['health_check_ocmod_active'] = 'Theme integration';
$_['health_check_default_country']= 'Default country';
$_['health_check_layout_valid'] = 'Checkout layout';
$_['health_status_ok']          = 'OK';
$_['health_status_warn']        = 'Warning';
$_['health_status_fail']        = 'Fail';
$_['entry_check']               = 'Check';
$_['entry_status_label']        = 'Result';

// Health-check — detailed state descriptions
$_['health_msg_generic_ok']             = 'All good';
$_['health_msg_generic_warn']           = 'Needs attention';
$_['health_msg_generic_fail']           = 'Needs fixing';
$_['health_msg_module_status_fail']     = 'Module is disabled — enable it in the "General settings" section.';
$_['health_msg_cron_recent_warn']       = 'Cron has not run for over a day — reminders may not be sent. Check the cron job.';
$_['health_msg_cron_recent_fail']       = 'The cron job has never run. Set it up on the server — otherwise abandoned-cart reminders will not work.';
$_['health_msg_mail_engine_warn']       = 'Email sending (SMTP) is not configured. Reminder emails may not reach customers.';
$_['health_msg_db_tables_fail']         = 'The database is missing module tables. Reinstall the module from the extensions list.';
$_['health_msg_ocmod_active_warn']      = 'Theme modifications are not active. Go to Extensions → Modifications and click "Refresh".';
$_['health_msg_default_country_warn']   = 'No default country selected — the address form will not prefill the country. Pick it in "General settings".';
$_['health_msg_layout_valid_warn']      = 'The checkout layout has warnings (empty cells or suboptimal settings). Review the "Block layout" section.';
$_['health_msg_layout_valid_fail']      = 'The checkout layout has broken references to deleted fields or headings. Open "Block layout" and fix them.';
$_['button_add_format']         = 'Add format';
$_['button_add_restriction']    = 'Add restriction';
$_['sidebar_js']                = 'JavaScript';
$_['js_heading']                = 'JavaScript integrations';
$_['js_help']                   = 'Custom JS snippets + pub/sub Events API documentation. Useful for GA4, Pixel, GTM, custom integrations.';
$_['help_js_before_init']       = 'Runs before OkEasyCheckout init.';
$_['help_js_after_init']        = 'Runs after init. window.OkEasyCheckout is available.';
$_['help_js_before_confirm']    = 'Runs before order submit — can be cancelled.';
$_['js_api_heading']            = 'Events API';
$_['js_api_help']               = 'Module exposes window.OkEasyCheckout — pub/sub event bus + state methods.';
$_['js_api_events_heading']     = 'Available events';
$_['js_api_methods_heading']    = 'API methods';
$_['js_api_when_heading']       = 'When it fires';
$_['js_event_ready']            = 'Page initialized';
$_['js_event_field_change']     = 'Any field change';
$_['js_event_field_focus']      = 'Field focus';
$_['js_event_field_blur']       = 'Field blur';
$_['js_event_payment_select']   = 'Payment method selected';
$_['js_event_shipping_select']  = 'Shipping method selected';
$_['js_event_before_reload']    = 'Before AJAX block reload';
$_['js_event_after_reload']     = 'After AJAX reload';
$_['js_event_abandoned_saved']  = 'Abandoned cart saved';
$_['js_event_before_confirm']   = 'Before submit — can be cancelled';
$_['js_event_order_confirmed']  = 'Order created';

// ── Integrations marketplace ────────────────────────────────────────
$_['sidebar_integrations']      = 'Integrations';
$_['integrations_heading']      = 'Integrations';
$_['integrations_help']         = 'Add-ons for specific shipping/payment/country combinations. Enable only what you need — others won\'t load.';
$_['integrations_empty']        = 'No integrations found.';
$_['integrations_marketplace_hint'] = 'Marketplace for additional integrations (KazPost, Meest, Apple Pay, Google Pay) — coming soon.';
$_['integration_status_active']    = 'Active';
$_['integration_status_inactive']  = 'Inactive';
$_['integration_test_connection']  = 'Test connection';
$_['integration_refresh_warehouses']= 'Refresh cache';
$_['integration_purge_data']       = 'Purge data';
$_['integration_purge_confirm']    = 'Delete all locally cached data for this integration? Action is irreversible, but the cache can be refilled later with «Refresh cache».';
$_['integration_refresh_running']  = 'Cache refresh started — it may take a few minutes.';
$_['integration_version']          = 'version';
$_['integration_install_fields']   = 'Create fields';
$_['integration_install_fields_help'] = 'Creates fields from integration preset-blocks in the "Fields" section. Then you can drag them into the layout.';
$_['marketplace_heading']          = 'Integrations marketplace';
$_['marketplace_help']              = 'Buy and install additional integrations in one click. Downloaded from oc-kit.com.';
$_['marketplace_install']           = 'Install';
$_['marketplace_uninstall']         = 'Uninstall';
$_['marketplace_installed']         = 'Installed';
$_['marketplace_install_confirm']   = 'Download and install this integration? Files will be extracted into integrations/.';
$_['marketplace_uninstall_confirm'] = 'Remove the integration with all files and DB tables?';
$_['button_back']                  = 'Back';
$_['integration_section_general_fallback'] = 'General';
$_['integration_section_health']    = 'State & cache';
$_['integration_health_last_refresh'] = 'Last refresh';
$_['integration_health_records']    = 'Records cached';
$_['integration_health_status']     = 'Status';
$_['integration_health_ok']         = 'OK';
$_['integration_health_stale']      = 'Stale';
$_['marketplace_search_placeholder']= 'Search integration...';
$_['marketplace_filter_all_countries']= 'All countries';
$_['marketplace_filter_all_categories']= 'All categories';
$_['marketplace_update']            = 'Update';
$_['integration_add_to_layout']     = 'Add to layout';
$_['button_settings']           = 'Settings';
$_['entry_detail']              = 'Detail';
$_['button_refresh']            = 'Refresh';
$_['presets_empty']             = 'No presets found. Check <span class="ok-badge ok-badge-danger-soft">system/library/ockit/easycheckout/presets/*.json</span>.';
$_['entry_reminder_test_email']= 'Test email address';
$_['text_reminder_test_sent']  = 'Test email sent to %s';

$_['block_settings_same_as_shipping_toggle'] = '"Billing same as shipping" toggle';
$_['help_same_as_shipping_toggle']           = 'When ON — customer sees a "Same as shipping" checkbox and payment-address fields are hidden when checked. When OFF — separate fields render with billing_ prefix.';

$_['block_settings_field_condition']      = 'Show condition';
$_['block_settings_condition_show_if']    = 'Show if';
$_['block_settings_condition_op_not_empty']= 'not empty';
$_['block_settings_condition_op_empty']   = 'empty';
$_['block_settings_condition_op_in']      = 'one of list';
$_['block_settings_condition_op_eq']      = 'equals';
$_['block_settings_condition_op_neq']     = 'not equals';
$_['block_settings_condition_match']      = 'Show when';
$_['block_settings_condition_match_all']  = 'all conditions met';
$_['block_settings_condition_match_any']  = 'any condition met';
$_['block_settings_condition_add_rule']   = 'Add condition';
$_['block_settings_condition_remove_rule']= 'Remove condition';
$_['block_settings_condition_value_ph']   = 'Value (for == / != / in)';

$_['abandoned_search_ph']      = 'Search: email, phone, name';
$_['abandoned_filter_pending'] = 'Pending';
$_['abandoned_filter_notified']= 'Reminder sent';
$_['abandoned_filter_recovered']= 'Completed';
$_['abandoned_filter_all']     = 'All';
$_['button_delete_selected']   = 'Delete selected';
$_['text_selected']            = 'selected';
$_['text_total']               = 'Total';

$_['fields_btn_presets']      = 'Presets';
$_['text_apply_preset_confirm']= 'Create fields from this preset? Existing (by code) will be skipped.';
$_['text_preset_applied']     = 'Created: %d, skipped: %d (already exist)';

$_['option_bulk_import']         = 'Bulk import';
$_['option_bulk_import_help']    = 'One option per line: value, label_{order}. Wrap commas in quotes.';
$_['option_bulk_import_ph']      = "red,Червоний,Красный,Red\nblue,Синій,Синий,Blue";
$_['option_bulk_import_replace'] = 'Replace current options (otherwise — append)';

$_['text_field_in_use']     = 'Field used in %d layout blocks. Force delete (will disappear from those blocks)?';
$_['text_fields_in_use']    = '%d fields are used in layouts. Force delete all?';

$_['abandoned_show_products']  = 'Products in cart';

$_['abandoned_col_note']     = 'Note';
$_['abandoned_note_ph']      = 'Sales-team comment';

$_['entry_abandoned_retention'] = 'Keep abandoned carts (days)';
$_['help_abandoned_retention']  = 'Delete recovered/notified records older than N days. Pending — untouched. Cleanup runs alongside reminder cron.';

$_['fields_col_usage']         = 'In orders';
$_['fields_col_usage_tooltip'] = 'Times this field is referenced in completed orders';
$_['fields_col_langs']         = 'Langs';
$_['fields_col_langs_tooltip'] = 'Languages filled / total configured';

$_['block_settings_block_condition']        = 'Block show condition';
$_['block_settings_block_condition_enable'] = 'Conditional show';
$_['block_settings_block_condition_help']   = 'The block is shown only when the selected field value matches the condition. E.g. show "Comment" only when "Shipping type" = "Pickup".';
$_['block_settings_block_condition_source_ph']= 'field code (e.g. register, country_id)';

$_['fields_filter_used']    = 'Used';
$_['fields_filter_unused']  = 'Unused';

$_['button_clone']         = 'Clone';
$_['text_field_cloned']    = 'Field cloned';

$_['layout_btn_clone_block']      = 'Clone block';
$_['layout_block_cloned']         = 'Block cloned';
$_['layout_block_unique_no_clone']= 'This block is unique — only one allowed';
$_['abandoned_view_order']     = 'View order';
$_['abandoned_send_reminder_now']    = 'Send reminder now';
$_['abandoned_send_reminder_confirm']= 'Send a reminder email to the customer now?';
$_['abandoned_reminder_sent']        = 'Reminder sent';
$_['abandoned_no_email_or_token']    = 'Record has no email or recovery token';
$_['abandoned_already_recovered']    = 'Order already recovered';
$_['abandoned_notified_at_tooltip']  = 'Reminder sent:';
$_['text_heading_cloned']  = 'Heading cloned';
$_['button_print']         = 'Print';

$_['button_reminder_reset']      = 'Reset templates';
$_['text_reminder_reset_confirm']= 'Clear the reminder email templates and revert to defaults?';
$_['text_reminder_reset_done']   = 'Templates cleared. Click "Save" to apply.';
$_['button_field_code_regen']    = 'Generate from name';
$_['text_field_name_empty']      = 'Enter the field name first';
$_['text_field_name_unsupported']= 'Could not derive code from this name';

$_['fields_col_usage_orders_short']    = 'ord.';
$_['fields_col_usage_orders_tooltip']  = 'How many times the field appears in completed orders';
$_['fields_col_usage_layouts_short']   = 'blk.';
$_['fields_col_usage_layouts_tooltip'] = 'How many layout blocks reference this field';

$_['fields_filter_layouts'] = 'In layouts (no orders)';
$_['text_heading_in_use']   = 'Heading used in %d blocks. Force delete?';

$_['entry_reminder_blacklist'] = 'Abandoned-cart blacklist';
$_['help_reminder_blacklist']  = 'Emails/domains excluded from reminders. One entry per line.';
$_['text_headings_in_use']  = '%d headings are used in layouts. Force delete all?';

$_['button_fields_export_tip'] = 'Download JSON dump of all fields registry';
$_['button_fields_import_tip'] = 'Import fields from JSON file (existing by code skipped)';
$_['text_fields_imported']     = 'Imported: %d, skipped: %s';

$_['layout_btn_export_tip']     = 'Download current layout as JSON';
$_['layout_btn_import_tip']     = 'Import layout from JSON (replaces current)';
$_['layout_btn_import_confirm'] = 'Import? Current layout will be replaced.';

$_['abandoned_filter_min_total'] = 'Min total';
$_['abandoned_filter_max_total'] = 'Max total';

$_['layout_btn_collapse_all']     = 'Collapse all';
$_['layout_btn_expand_all']       = 'Expand all';
$_['layout_btn_collapse_all_tip'] = 'Hide block details — keep only titles';
$_['groups_inline_rename_hint'] = 'Double-click to rename';
$_['groups_drag_hint']     = 'Drag to reorder';

// Backup (export/import all settings)
$_['settings_backup_heading']  = 'Settings backup';
$_['settings_backup_help']     = 'Export all module settings to a file (fields, headings, groups, block layout, address formats, restrictions, settings). License and abandoned-cart data are not included. Upload a file to restore — current settings will be replaced.';
$_['settings_export_btn']      = 'Export settings';
$_['settings_import_btn']      = 'Import settings';
$_['settings_import_confirm']  = 'Import will replace ALL current module settings. Continue?';
$_['settings_import_done']     = 'Settings imported';
$_['settings_import_no_file']  = 'No file received';
$_['settings_import_invalid']  = 'Invalid backup file';

// Custom methods (shipping/payment)
$_['cm_heading']               = 'Custom shipping & payment methods';
$_['cm_help']                  = 'Create your own shipping and payment variants shown in checkout alongside installed modules.';
$_['cm_add_variant']           = 'Add variant';
$_['cm_add_group']             = 'Add group';
$_['cm_select_hint']           = 'Select a variant on the left or create a new one to edit.';
$_['cm_field_name']            = 'Name';
$_['cm_field_description']     = 'Description';
$_['cm_cost_type']             = 'Shipping cost type';
$_['cm_cost_fixed']            = 'Fixed cost';
$_['cm_cost_weight']           = 'Depends on order weight';
$_['cm_cost_sum']              = 'Depends on order sum';
$_['cm_cost_sum_totals']       = 'Depends on sum incl. totals';
$_['cm_cost_api']              = 'Calculated via API';
$_['cm_cost_value_ph']         = 'e.g. 60.00';
$_['cm_cost_rules_hint']       = 'From-to → cost rules editor — coming next update; use fixed cost for now.';
$_['cm_cost_api_hint']         = 'Cost computed in catalog/model/extension/easycheckout/cm_api.php (next update).';
$_['cm_currency']              = 'Cost currency';
$_['cm_currency_default']      = 'Store default';
$_['cm_tax_class']             = 'Tax class';
$_['cm_tax_none']              = 'No tax';
$_['cm_zero_cost_text']        = 'Zero-cost text';
$_['cm_order_status']          = 'Order status on selection';
$_['cm_payment_form_heading']  = 'Payment form heading';
$_['cm_payment_info_form']     = 'Payment info (form)';
$_['cm_payment_info_hint']     = 'HTML allowed. Substitutions: <code>{total}</code>, <code>{subtotal}</code>, <code>{shipping}</code>, <code>{tax}</code>.';
$_['cm_payment_info_mail']     = 'Payment info (email)';
$_['cm_conditions']            = 'Display conditions';
$_['cm_cond_source_ph']        = 'field code (e.g. country_id, shipping_method)';
$_['cm_placeholder']           = 'Variant placeholder';
$_['cm_placeholder_always']    = 'Always show as placeholder';
$_['cm_placeholder_unavailable'] = 'Show placeholder when unavailable';
$_['cm_confirm_delete_group']  = 'Delete group? Variants inside will be detached, not deleted.';
$_['cm_confirm_delete_method'] = 'Delete this variant?';

// Custom methods — subtotal rows
$_['cm_subtotals_heading']     = 'Order totals (discounts/fees)';
$_['cm_subtotals_help']        = 'Extra total lines applied when a specific shipping or payment method is selected. E.g. prepayment discount or cash-on-delivery fee.';
$_['cm_sub_applies']           = 'Applies to';
$_['cm_sub_any']               = 'Any method';
$_['cm_sub_amount_type']       = 'Amount type';
$_['cm_sub_fixed']             = 'Fixed';
$_['cm_sub_percent']           = 'Percent of subtotal';
$_['cm_sub_amount']            = 'Amount (− discount)';
$_['cm_sub_methods']           = 'For methods:';
$_['cm_sub_add']               = 'Add total line';
$_['cm_sub_value']             = 'Value';
$_['cm_sub_value_hint']        = 'Fixed amount (e.g. -50) or percent of order total (e.g. -1.3%). Negative = discount.';
$_['cm_sub_round']             = 'Round to integers';
$_['cm_confirm_delete_subtotal'] = 'Delete this total line?';

// Condition types (custom methods)
$_['cm_cond_group_customer']   = 'Customer';
$_['cm_cond_group_cart']       = 'Cart / total';
$_['cm_cond_group_address']    = 'Address';
$_['cm_cond_group_context']    = 'Context';
$_['cm_cond_group_methods']    = 'Methods';
$_['cm_cond_logged_in']        = 'Customer logged in';
$_['cm_cond_customer_group']   = 'Customer group';
$_['cm_cond_has_orders']       = 'Customer has account';
$_['cm_cond_total']            = 'Order total';
$_['cm_cond_total_no_shipping']= 'Total without shipping';
$_['cm_cond_total_quantity']   = 'Total quantity';
$_['cm_cond_total_weight']     = 'Total weight (kg)';
$_['cm_cond_max_weight_single']= 'Max single-product weight (kg)';
$_['cm_cond_coupon_used']      = 'Coupon used';
$_['cm_cond_reward_used']      = 'Reward points used';
$_['cm_cond_voucher_used']     = 'Gift voucher used';
$_['cm_cond_products_no_shipping'] = 'Products require no shipping';
$_['cm_cond_country']          = 'Country';
$_['cm_cond_zone']             = 'Zone / region';
$_['cm_cond_city']             = 'City';
$_['cm_cond_postcode']         = 'Postcode';
$_['cm_cond_language']         = 'Language';
$_['cm_cond_currency']         = 'Currency';
$_['cm_cond_store']            = 'Store';
$_['cm_cond_ip']               = 'IP address';
$_['cm_cond_day']              = 'Day of week (0=Sun)';
$_['cm_cond_time']             = 'Time (HH:MM)';
$_['cm_cond_date']             = 'Date (YYYY-MM-DD)';
$_['cm_cond_payment_variant']  = 'Payment variant';
$_['cm_cond_shipping_variant'] = 'Shipping variant';
