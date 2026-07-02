<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// Heading
$_['heading_title']             = 'oc-kit.com — Auto Related Products';

// Breadcrumb
$_['text_home']                 = 'Home';
$_['text_extension']            = 'Extensions';
$_['text_settings']             = 'Settings';
$_['text_success']              = 'Settings saved!';

// Buttons
$_['button_save']               = 'Save';
$_['button_cancel']             = 'Cancel';

// Tabs
$_['tab_general']               = 'General';
$_['tab_weights']               = 'Similarity Signals';
$_['tab_performance']           = 'Performance';
$_['tab_generate']              = 'Generate';
$_['tab_stats']                 = 'Statistics';
$_['tab_rules']                 = 'Rule Blocks';

// General
$_['entry_status']              = 'Status';
$_['entry_related_limit']       = 'Number of Related Products';
$_['entry_overwrite']           = 'Overwrite Existing';
$_['entry_on_visit']            = 'Generate on Product Visit';
$_['entry_visit_mode']          = 'Generation Mode';
$_['entry_visit_mode_async']    = 'Async (JS fetch, does not block render)';
$_['entry_visit_mode_sync']     = 'Sync (related products in HTML immediately)';
$_['entry_exclude_oos']         = 'Exclude Out-of-Stock Products';
$_['entry_exclude_disabled']    = 'Exclude Disabled Products';
$_['entry_cache']               = 'Caching';
$_['entry_cache_ttl']           = 'Cache Lifetime (hours)';

// Weights
$_['entry_weight_category']     = 'Category';
$_['entry_weight_name']         = 'Name';
$_['entry_weight_neighbor_id']  = 'Neighbor IDs';
$_['entry_weight_fields']       = 'Fields (MPN/SKU/…)';
$_['entry_weight_manufacturer'] = 'Manufacturer';
$_['entry_weight_attributes']   = 'Attributes';
$_['entry_weight_coorders']     = 'Frequently Ordered Together';
$_['entry_weight_price_range']  = 'Price Range';

$_['entry_neighbor_enabled']    = 'Enable Neighbor IDs Signal';
$_['entry_neighbor_range']      = 'Neighbor ID Range (±N)';
$_['entry_field_list']          = 'Fields to Compare';
$_['entry_field_separator']     = 'Field Value Separator';
$_['entry_attribute_ids']       = 'Attributes to Compare';
$_['entry_attribute_min_match'] = 'Min. Attribute Matches';
$_['entry_coorders_days']       = 'Orders Within N Days';
$_['entry_coorders_min']        = 'Min. Shared Orders';
$_['entry_coorders_statuses']   = 'Order Statuses';

// Price range signal
$_['entry_price_range_pct']     = 'Max. Price Deviation (%)';
$_['text_price_range_pct_help'] = 'Products with a price difference greater than this % of the source product price get a score of 0. E.g. 20 means ±20%.';

// Result sort & only_special (global)
$_['entry_result_sort']         = 'Display Order';
$_['entry_result_sort_score']   = 'By Score (best match first)';
$_['entry_result_sort_random']  = 'Random';
$_['entry_result_sort_price_asc']  = 'Price: Low to High';
$_['entry_result_sort_price_desc'] = 'Price: High to Low';
$_['entry_result_sort_new']     = 'Newest First';
$_['entry_result_sort_name']    = 'Alphabetical';
$_['entry_only_special']        = 'Only Products with Discount';
$_['text_only_special_help']    = 'When enabled, only products with an active special/sale price are shown as related products.';

// Brand priority & blacklist
$_['entry_brand_priority']      = 'Same Brand First';
$_['text_brand_priority_help']  = 'When enabled, products from the same manufacturer are moved to the top of the related list (after scoring).';
$_['entry_blacklist_products']  = 'Exclude Products';
$_['entry_blacklist_categories']= 'Exclude Categories';
$_['text_blacklist_help']       = 'Products matching these criteria will never appear in the related list.';

// Preview
$_['tab_preview']               = 'Preview';
$_['text_preview_product']      = 'Type product name to search…';
$_['button_preview']            = 'Preview';
$_['text_preview_results']      = 'Scoring results (dry-run, nothing saved)';
$_['column_preview_score']      = 'Score';
$_['text_preview_empty']        = 'No candidates found';
$_['text_no_results']           = 'No results';

// Inline help texts (used in twig)
$_['text_weights_help']         = 'Weights do not need to sum to 100 — they are normalized automatically. 0 = signal ignored.';
$_['text_field_separator_help'] = 'E.g. comma (,) or semicolon (;). Leave blank for exact match.';
$_['text_coorders_statuses_help']= 'Empty = consider all order statuses.';

// Field names
$_['field_sku']                 = 'SKU';
$_['field_mpn']                 = 'MPN';
$_['field_ean']                 = 'EAN';
$_['field_jan']                 = 'JAN';
$_['field_isbn']                = 'ISBN';
$_['field_upc']                 = 'UPC';

// Performance
$_['entry_candidate_limit']     = 'Max. Candidates for Scoring';
$_['text_candidate_limit_help'] = 'The pre-filter pool size before full scoring. Lower values are faster; higher values improve accuracy on large catalogs. Recommended: 500–2000.';

// Generate
$_['entry_id_from']             = 'ID From';
$_['entry_id_to']               = 'ID To';
$_['entry_gen_categories']      = 'Categories';
$_['entry_gen_manufacturers']   = 'Manufacturers';
$_['entry_gen_overwrite']       = 'Overwrite Existing';
$_['button_generate']           = 'Generate';
$_['button_stop']               = 'Stop';
$_['text_processed']            = 'Processed';
$_['text_of']                   = 'of';
$_['text_generating']           = 'Generating…';
$_['text_done']                 = 'Done!';

// Stats
$_['text_total_products']       = 'Total Products';
$_['text_with_related']         = 'With Related';
$_['text_coverage']             = 'Coverage';
$_['text_without_related']      = 'Without Related';
$_['text_recent_generated']     = 'Recently Generated';
$_['column_product']            = 'Product';
$_['column_generated_at']       = 'Date';
$_['column_source']             = 'Source';
$_['column_count']              = 'Count';
$_['source_cron']               = 'Cron';
$_['source_visit']              = 'Visit';
$_['source_manual']             = 'Manual';

// Cron
$_['text_cron']                 = 'Cron Job';
$_['text_cron_command']         = 'Command';
$_['text_cron_schedule']        = 'Schedule';
$_['text_cron_daily_2']         = 'Daily at 02:00';
$_['text_cron_daily_3']         = 'Daily at 03:00';
$_['text_cron_daily_4']         = 'Daily at 04:00';
$_['text_cron_every_6h']        = 'Every 6 hours';
$_['text_cron_every_1h']        = 'Every hour';
$_['text_cron_all']             = 'all';
$_['text_cron_param_limit']     = 'Products per run';
$_['text_cron_param_force']     = 'Force regenerate';
$_['text_cron_param_category']  = 'Categories';
$_['text_cron_param_mf']        = 'Manufacturers';

// Preset scenarios
$_['text_presets']              = 'Presets';
$_['text_preset_balanced']      = 'Balanced';
$_['text_preset_coorders']      = 'Co-orders Focused';
$_['text_preset_category']      = 'Same Category';
$_['text_preset_variants']      = 'Product Variants';
$_['text_preset_help']          = 'Click a preset to fill weight sliders. You can then adjust and save.';

// Rule Builder
$_['tab_rules']                      = 'Rule Blocks';
$_['text_rules_intro']               = 'Rule-based blocks are displayed alongside standard related products. Each rule is a constructor: define WHERE to show the block (source conditions) and WHAT to show (target conditions).';
$_['button_add_rule']                = 'Add Rule';
$_['button_edit_rule']               = 'Edit';
$_['button_delete_rule']             = 'Delete';
$_['button_save_rule']               = 'Save Rule';
$_['button_cancel_rule']             = 'Cancel';
$_['column_rule_name']               = 'Name';
$_['column_rule_source']             = 'Where to show';
$_['column_rule_target']             = 'What to show';
$_['column_rule_sort']               = 'Sort';
$_['column_rule_status']             = 'Status';
$_['column_rule_actions']            = 'Actions';
$_['entry_rule_name']                = 'Rule Name';
$_['entry_rule_status']              = 'Status';
$_['entry_rule_sort_order']          = 'Sort Order';
$_['entry_rule_block_title']         = 'Block Title';
$_['entry_rule_result_limit']        = 'Products to Show';
$_['entry_rule_result_sort']         = 'Sort Order';
$_['entry_result_sort_bestseller']   = 'Bestsellers';

// Rule constructor — source conditions (WHERE to show)
$_['text_source_conditions']         = 'Where to Show';
$_['text_source_conditions_help']    = 'Block appears on pages that match ALL listed conditions. No conditions = show on all product pages.';
$_['button_add_source_cond']         = '+ Add condition';
$_['cond_src_category']              = 'Category';
$_['cond_src_manufacturer']          = 'Brand';
$_['cond_src_attribute']             = 'Attribute value';
$_['cond_src_name_contains']         = 'Name contains';

// Rule constructor — target conditions (WHAT to show)
$_['text_target_conditions']         = 'What to Show';
$_['text_target_conditions_help']    = 'Products must match ALL listed conditions. Combine freely.';
$_['button_add_target_cond']         = '+ Add condition';
$_['cond_tgt_same_category']         = 'Same category as product';
$_['cond_tgt_same_manufacturer']     = 'Same brand as product';
$_['cond_tgt_category']              = 'Specific categories';
$_['cond_tgt_manufacturer']          = 'Specific brands';
$_['cond_tgt_attribute']             = 'Attribute = value';
$_['cond_tgt_dynamic_attribute']     = 'Same attribute as product';
$_['cond_tgt_name_contains']         = 'Name contains';
$_['cond_tgt_price_range']           = 'Price range ±%';
$_['cond_tgt_only_special']          = 'On sale only';
$_['cond_tgt_exclude_oos']           = 'In stock only';
$_['cond_tgt_brand_priority']        = 'Same brand first';

// Condition field labels / hints
$_['entry_cond_attribute_id']        = 'Attribute';
$_['entry_cond_attribute_value']     = 'Value';
$_['entry_cond_price_pct']           = '±%';
$_['entry_cond_name_text']           = 'Text';
$_['entry_cond_ids_placeholder']     = 'Search…';
$_['text_cond_same_cat_help']        = 'Products from the same category as the current product';
$_['text_cond_same_mf_help']         = 'Products from the same brand as the current product';
$_['text_cond_dyn_attr_help']        = 'Matches products that have the same value for this attribute as the current product (e.g. socket type E27)';
$_['text_cond_brand_priority_help']  = 'Same-brand products appear first in this block';
$_['text_cond_only_special_help']    = 'Only products with an active special / sale price';
$_['text_cond_exclude_oos_help']     = 'Exclude out-of-stock products';

$_['text_no_rules']                  = 'No rules yet. Click "Add Rule" to create one.';
$_['confirm_delete_rule']            = 'Delete this rule?';
$_['text_rule_saved']                = 'Rule saved.';
$_['text_rule_deleted']              = 'Rule deleted.';

// Errors
$_['error_permission']          = 'Warning: You do not have permission to modify settings!';

// License
$_['tab_license']               = 'License';
$_['entry_license_key']         = 'License Key';
$_['button_activate']           = 'Activate';
$_['text_license_not_validated']= 'No key entered';
$_['text_license_invalid']      = 'Key is invalid';
$_['text_license_active']       = 'License is active';
$_['text_license_expired']      = 'Trial period has expired';
$_['text_license_trial']        = 'Trial period: %d days remaining';
$_['text_license_grace']        = 'Grace period (API unavailable)';
$_['text_license_api_error']    = 'License validation error';
$_['text_license_version']      = 'Version';
$_['text_license_buy']          = 'Purchase License';
