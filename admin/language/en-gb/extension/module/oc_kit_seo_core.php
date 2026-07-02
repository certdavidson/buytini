<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

// Heading
$_['heading_title'] = 'oc-kit.com — SEO Core';

// Text
$_['text_extension']             = 'Extensions';
$_['text_home']                  = 'Home';
$_['text_success']               = 'Settings saved!';
$_['text_license_activated']     = 'License activated!';
$_['text_enabled']               = 'Enabled';
$_['text_disabled']              = 'Disabled';

// Tab labels
$_['tab_settings']               = 'Settings';
$_['tab_meta']                   = 'Meta Tags';
$_['tab_redirects']              = 'Redirects';
$_['tab_urls']                   = 'SEO URLs';
$_['tab_headers']                = 'Headers';
$_['tab_audit']                  = 'Audit';
$_['tab_robots']                 = 'robots.txt';
$_['tab_sitemap']                = 'Sitemap';
$_['tab_absurl']                 = 'Domain Replace';
$_['tab_dashboard']              = 'Dashboard';
$_['tab_faq']                    = 'FAQ';
$_['tab_masks']                  = 'URL masks';
$_['tab_canonical']              = 'Canonicals';
$_['tab_hreflang']                = 'Hreflang';
$_['tab_opengraph']               = 'Open Graph';
$_['tab_schema']                  = 'Schema.org';

$_['text_canonical_auto']              = 'Automatic rules';
$_['text_canonical_overrides']         = 'Manual overrides';
$_['text_canonical_test']              = 'Canonical test';
$_['label_canonical_pagination']       = 'Canonical for pagination';
$_['text_canonical_pagination_first']  = 'First page';
$_['text_canonical_pagination_self']   = 'Current page';
$_['label_canonical_filters']          = 'Canonical for filters';
$_['text_canonical_filters_base']      = 'Base category';
$_['text_canonical_filters_self']      = 'Current URL';
$_['text_canonical_filters_noindex']   = 'noindex robots';
$_['label_canonical_cross_domain']     = 'Cross-domain canonical';
$_['text_canonical_cross_domain_hint'] = 'For mirror sites: all canonicals will point to this domain. Leave empty to use the current domain.';
$_['button_test']                      = 'Test';

$_['text_hreflang_coming']  = 'Hreflang settings will be added in the next phase. HreflangBuilder library is already integrated and renders tags automatically for all active languages.';
$_['text_hreflang_about']   = '<strong>Hreflang</strong> tells search engines about alternate language versions of a page: <code>&lt;link rel="alternate" hreflang="uk" href="..."&gt;</code>. This avoids confusion between similar URLs and ensures correct regional versions in search results. Tags are generated automatically for all languages that have a SEO URL for the page.';
$_['text_hreflang_preview'] = 'Tag preview';
$_['label_hreflang_enabled']= 'Enable hreflang';
$_['label_hreflang_format'] = 'Code format';
$_['text_opengraph_coming'] = 'Open Graph panel with og:* and Twitter Card settings will be added in the next phase. Standard og tags are currently rendered automatically through OpenGraphRenderer.';
$_['text_og_about']            = '<strong>Open Graph</strong> (Facebook, LinkedIn, Telegram, etc.) and <strong>Twitter Card</strong> control how a page appears when shared on social networks: title, description, image. When templates are empty — values from Meta tags are used.';
$_['text_og_templates']        = 'Tag templates';
$_['text_og_templates_hint']   = 'Variables: <code>{name}</code>, <code>{description}</code>, <code>{price}</code>, <code>{manufacturer}</code>, <code>{store_name}</code>, <code>{year}</code>, etc.';
$_['label_og_enabled']         = 'Enable Open Graph';
$_['label_og_twitter_card']    = 'Twitter Card';
$_['label_og_twitter_handle']  = 'Twitter handle';
$_['label_og_image_fallback']  = 'Image URL';
$_['text_og_twitter_handle_hint']= 'Your Twitter/X username with @, e.g. @yoursite. Used in <code>twitter:site</code>.';
$_['text_og_image_fallback_hint']= 'Default image URL — used when an entity has no image of its own. 1200×630px recommended.';
$_['text_schema_coming']    = 'Schema.org panel with standard-type toggles and a custom JSON-LD editor will be added in the next phase.';
$_['text_schema_about']         = '<strong>Schema.org</strong> is structured JSON-LD markup for search engines. Enabling a type adds the corresponding block to each page <code>&lt;head&gt;</code>. Required for Rich Snippets in Google (rating, price, availability, breadcrumbs), Organization cards, etc.';
$_['text_schema_standard']      = 'Standard types';
$_['text_schema_organization']  = 'Organization';
$_['text_schema_custom']        = 'Custom rules (JSON-LD editor)';
$_['text_schema_custom_hint']   = 'Create your own JSON-LD blocks for custom pages. Templates support <code>{{var}}</code> context variables, loops <code>{{#each}}</code>, conditions <code>{{#if}}</code>.';
$_['label_schema_min_reviews']  = 'Min reviews for aggregateRating';
$_['text_schema_min_reviews_hint']= 'Below this count, aggregateRating is omitted from Product JSON-LD (to avoid misleading data).';
$_['label_schema_org_name']     = 'Organization name';
$_['label_schema_org_logo']     = 'Logo URL';
$_['label_schema_org_phone']    = 'Phone (E.164)';
$_['label_schema_org_email']    = 'Email';
$_['text_schema_modal_title']   = 'Custom Schema.org rule';
$_['label_schema_route']        = 'Route pattern';
$_['label_schema_preview']      = 'Preview';
$_['label_schema_template']     = 'JSON-LD template';
$_['label_schema_priority']     = 'Priority';
$_['label_schema_status']       = 'Status';
$_['button_validate']           = 'Validate';

$_['text_section_cache']  = 'SEO URL cache';
$_['text_cache_hint']     = 'A warm cache keeps all <code>keyword → query</code> maps in memory and eliminates the DB hit on every URL. Recommended after bulk URL generation or URL-depth change.';
$_['button_warm_cache']   = 'Generate cache';
$_['button_clear_cache']  = 'Clear cache';
$_['button_export_csv']   = 'Export CSV';
$_['button_delete_stale'] = 'Stale (0 hits)';
$_['text_redirects_stale_hint'] = 'Delete unused redirects (0 hits) older than N days';
$_['redirects_stale_days_prompt']= 'Delete 0-hit redirects older than N days:';
$_['confirm_delete_stale']= 'Delete all 0-hit redirects older than';
$_['days']                = 'days';
$_['deleted']             = 'Deleted';

$_['text_faq_security']     = 'Security Headers &amp; HSTS';
$_['text_faq_audit']        = 'SEO Audit';
$_['text_faq_schema']       = 'Schema.org (JSON-LD)';
$_['text_faq_sitemap']      = 'Sitemap';
$_['text_faq_hreflang']     = 'Hreflang';
$_['text_faq_troubleshoot'] = 'Troubleshooting';

// Settings
$_['label_status']               = 'Status';
$_['label_url_depth']            = 'Category URL depth';
$_['label_trailing_slash']       = 'Trailing slash';
$_['label_lang_prefixes']        = 'Language prefixes';
$_['label_custom_routes']        = 'Custom routes';
$_['label_pagination_mode']      = 'Pagination';
$_['label_noindex_all_pagination'] = 'Noindex all paginated pages';
$_['label_mask_product']         = 'URL mask — product';
$_['label_mask_category']        = 'URL mask — category';
$_['label_mask_manufacturer']    = 'URL mask — manufacturer';
$_['label_mask_information']     = 'URL mask — information';
$_['label_auto_generate_url']    = 'Auto-generate URL on visit';
$_['help_auto_generate_url']     = 'When enabled, a missing SEO URL for a product/category/manufacturer/information page is created on the fly the first time the page is visited — using the configured mask. No need to run manual regeneration.';

// URL depth options
$_['text_depth_flat']            = 'Flat (no nesting)';
$_['text_depth_1']               = '1 level';
$_['text_depth_2']               = '2 levels';
$_['text_depth_full']            = 'Full hierarchy';

// Pagination mode options
$_['text_pagination_off']        = 'Off';
$_['text_pagination_404']        = '404 for extra pages';
$_['text_pagination_redirect_last'] = '301 redirect to last valid page';
$_['text_pagination_robots']     = 'Noindex via X-Robots-Tag';

// Redirects
$_['label_from_url']             = 'From';
$_['label_to_url']               = 'To';
$_['label_redirect_code']        = 'Code';
$_['label_hits']                 = 'Hits';
$_['button_redirect_add']        = 'Add redirect';
$_['button_import_csv']          = 'Bulk paste';
$_['text_bulk_paste_title']      = 'Bulk paste redirects';
$_['text_bulk_paste_hint']       = 'One redirect per line. Format: <code>/from, /to, code</code>. Code is optional (defaults to 301). Supported codes: 301, 302, 303, 307, 308, 410.';
$_['text_code_301']              = '<span class="ok-badge ok-badge-success">301</span> Permanent';
$_['text_code_302']              = '<span class="ok-badge ok-badge-warning">302</span> Temporary';
$_['text_code_303']              = '<span class="ok-badge ok-badge-warning">303</span> See Other (POST → GET)';
$_['text_code_307']              = '<span class="ok-badge ok-badge-warning">307</span> Temporary (keeps method)';
$_['text_code_308']              = '<span class="ok-badge ok-badge-success">308</span> Permanent (keeps method)';
$_['text_code_410']              = '<span class="ok-badge ok-badge-error">410</span> Gone (page removed)';

$_['text_code_guide_title']      = 'Which code to choose?';
$_['text_code_301_use']          = 'Default choice for SEO. Page moved permanently (URL rename, structure change). Search engines pass link equity to the new URL.';
$_['text_code_302_use']          = 'Temporary redirect (promo, A/B test, maintenance). Search engines do NOT pass equity — the old URL stays in the index.';
$_['text_code_303_use']          = 'After a POST request — lets the browser GET the result so F5 does not resubmit the form. Rarely used for SEO.';
$_['text_code_307_use']          = 'Like 302 but guarantees the HTTP method is preserved (POST → POST). Use when method must not be downgraded.';
$_['text_code_308_use']          = 'Like 301 but preserves the HTTP method. Rare: permanent redirect for API endpoints accepting POST/PUT.';
$_['text_code_410_use']          = 'Page removed permanently, will not return. Search engines drop it from the index faster than a 404. Target URL not required.';
$_['placeholder_search_redirects'] = 'Search redirects...';

// License
$_['tab_license']                = 'License';
$_['label_license_key']          = 'License key';
$_['entry_license_key']          = 'License key';
$_['button_activate']            = 'Activate';
$_['text_license_version']       = 'Version';
$_['text_license_activating']    = 'Activating...';
$_['text_license_buy']           = 'Buy license';
$_['text_license_trial']         = 'Trial period: %d days left';
$_['text_license_expired']       = 'License expired';
$_['text_license_invalid']       = 'Invalid license';
$_['text_license_api_error']     = 'License server unavailable';
$_['text_license_not_validated'] = 'License not validated';
$_['text_license_status_active'] = 'Active';
$_['text_license_status_trial']  = 'Trial';
$_['text_license_status_expired'] = 'Expired';
$_['text_license_status_grace']  = 'Grace period';
$_['text_license_status_invalid'] = 'Invalid';
$_['text_license_status_not_validated'] = 'Not validated';

// Errors
$_['error_license_invalid_key']  = 'Invalid license key.';
$_['error_license_api_unreachable'] = 'License server unreachable. Try again later.';
$_['error_redirect_fields']      = 'Please fill in both From and To URLs.';
$_['error_redirect_loop']        = 'This redirect would create a chain or loop.';
$_['error_import_empty']         = 'CSV data is empty.';

// Settings sections
$_['label_route_pattern']        = 'Route pattern';
$_['label_entity_id']            = 'Query parameter';
$_['label_route']                = 'OpenCart route';
$_['text_skip_routes_hint']      = '<strong>What is this?</strong> OpenCart identifies every page internally by a "route" — for example <code>product/search</code>, <code>account/login</code>, <code>information/information</code>. Here you list the routes for which <em>no</em> SEO URL should be generated. Useful for search pages, account pages, and other system pages that do not need a pretty slug. Wildcards supported: <code>account/*</code>.';
$_['text_entity_routes_hint']    = '<strong>What is this?</strong> By default SEO Core only knows the standard OpenCart routes (product, category, manufacturer, information page). If you have a non-standard page from a third-party module — e.g. a vendor at <code>index.php?route=vendor/vendor/view&amp;vendor_id=4</code> — the module has no way to know that the <code>vendor_id</code> param belongs to the <code>vendor/vendor/view</code> route, so SEO URLs do not work for it.<br><br><strong>Fix:</strong> add the mapping here <strong>once</strong> — put <code>vendor_id</code> in the "Query parameter" field and <code>vendor/vendor/view</code> in the "OpenCart route" field. After saving, every <code>oc_seo_url</code> row with <code>query=vendor_id=N</code> works automatically: it resolves on the pretty URL and gets substituted in links across the site. No code editing needed.';

// Meta templates
$_['text_section_meta_templates']  = 'Meta tag templates';
$_['label_meta_title_tpl']         = 'Title template';
$_['label_meta_desc_tpl']          = 'Description template';
$_['label_meta_h1_tpl']            = 'H1 template';
$_['text_meta_vars_product']       = 'Available variables: <code>{name}</code> — product name, <code>{sku}</code> — SKU/model, <code>{manufacturer}</code> — manufacturer, <code>{category}</code> — primary category, <code>{price}</code> — price, <code>{description}</code> — first 160 chars of description, <code>{store_name}</code> — store name, <code>{year}</code> — current year, <code>{page}</code> — page number (empty on page 1).<br>Conditional blocks: <code>{{#if page}} — page {page}{{/if}}</code> — rendered only when variable is non-empty.';
$_['text_meta_vars_category']      = 'Available variables: <code>{name}</code> — name, <code>{count}</code> — product count, <code>{store_name}</code> — store name, <code>{year}</code> — current year, <code>{page}</code> — page number (empty on page 1).<br>Conditional blocks: <code>{{#if page}} — page {page}{{/if}}</code> — rendered only when variable is non-empty.';
$_['text_meta_vars_manufacturer']  = 'Available variables: <code>{name}</code> — manufacturer name, <code>{store_name}</code> — store name, <code>{year}</code> — current year, <code>{page}</code> — page number (empty on page 1).<br>Conditional blocks: <code>{{#if page}} — page {page}{{/if}}</code> — rendered only when variable is non-empty.';
$_['text_meta_vars_information']   = 'Available variables: <code>{name}</code> — page title, <code>{description}</code> — first 160 chars of content, <code>{store_name}</code> — store name, <code>{year}</code> — current year, <code>{page}</code> — page number (empty on page 1).<br>Conditional blocks: <code>{{#if page}} — page {page}{{/if}}</code> — rendered only when variable is non-empty.';
$_['text_meta_tpl_hint']           = '<strong>How templates work:</strong> rendered live on the storefront — behaviour is controlled by the <em>"Template priority"</em> setting above.<br><strong>Priority order:</strong> Manual override (table below) → Template/OC field (depending on mode) → empty.<br><strong>"Bulk fill"</strong> below is a one-shot operation: it renders the template for selected entities and <em>writes the result into the override table</em> (not into the native OC fields!). After that the values are frozen to the entity, editable via "Manual overrides" below, and template changes no longer affect them.';

$_['text_section_general']       = 'General';
$_['text_section_url']           = 'URL';
$_['text_section_url_masks']     = 'URL Masks';
$_['text_section_pagination']    = 'Pagination';
$_['text_section_lang_prefixes'] = 'Language Prefixes';
$_['label_lang_default']         = 'Default';
$_['text_section_custom_routes'] = 'Custom Routes';
$_['text_lang_prefix_hint']      = 'Leave prefix empty for the default language (no prefix in URL). Other languages use their prefix, e.g. /ru/slug.';
$_['text_skip_routes']           = 'Skip routes (no SEO URL generation)';
$_['text_entity_routes']         = 'Custom routes';
$_['text_depth_hint']            = 'Flat: /slug · 1 level: /category/slug · Full: /cat/sub/slug';
$_['text_depth_flat_ex']         = 'site.com/slug';
$_['text_depth_1_ex']            = 'site.com/category/slug';
$_['text_depth_2_ex']            = 'site.com/cat/sub/slug';
$_['text_depth_full_ex']         = 'site.com/cat/sub/sub/slug';

$_['label_product_include_category'] = 'Category prefix in product URL';
$_['text_product_category_off']  = 'No — site.com/product-slug';
$_['text_product_category_on']   = 'Yes — site.com/category/product-slug';
$_['text_product_category_hint'] = 'Whether to prepend the category path before the product slug. Off — flat product URLs (recommended for most shops).';

$_['text_skip_routes_why']       = 'Service pages (search, account, cart) do not need pretty URLs and may break if you generate them. Such routes must be excluded from SEO URL generation.';
$_['text_entity_routes_why']     = 'Third-party module pages (vendors, blog, etc.) use non-standard routes. Map the query parameter to its route — SEO URLs then work for them with no code changes.';

$_['text_section_skip_routes']   = 'Skipped routes';
$_['text_section_entity_routes'] = 'Custom routes';

$_['label_noindex_from_page']    = 'Start noindex from page #';
$_['text_noindex_from_page_hint']= '1 — all pagination pages. 2 — first page stays indexable, ?page=2, 3… get noindex. Works together with the setting above.';
$_['text_pagination_mode_hint']  = 'How to handle extra / invalid pagination pages. Can be combined with noindex below.';
$_['text_pagination_intro']         = 'These two settings are independent and work together: one decides what happens to invalid pages, the other controls indexation of valid ones.';
$_['text_pagination_invalid_title'] = 'Invalid pages (out of range)';
$_['text_pagination_noindex_title'] = 'Noindex for valid pagination pages';
$_['label_noindex_delivery']        = 'Blocking method';
$_['text_noindex_delivery_meta']    = '<meta name="robots" content="noindex">';
$_['text_noindex_delivery_header']  = 'HTTP header X-Robots-Tag';
$_['text_noindex_delivery_both']    = 'Both (meta tag + HTTP header)';
$_['text_noindex_delivery_hint']    = 'Meta tag is read by bots during HTML parsing. X-Robots-Tag is a server-side header, effective for any resource (including non-HTML) and applies earlier — before the body is fetched.';

$_['label_mask_product_ex']      = '{name}-{product_id}';
$_['label_mask_category_ex']     = '{name}';
$_['text_mask_section_hint']     = 'Masks define the SEO URL format during auto-generation. Used for new records and when regenerating below.';

$_['text_var_name']              = 'name';
$_['text_var_model']             = 'model';
$_['text_var_sku']               = 'SKU';
$_['text_var_product_id']        = 'product ID';
$_['text_var_category_id']       = 'category ID';
$_['text_var_manufacturer_id']   = 'manufacturer ID';
$_['text_var_information_id']    = 'page ID';
$_['text_trailing_slash_hint']   = 'On: /url/ · Off: /url';
$_['text_mask_hint']             = 'Example: {name} → nike-air-max';

// Regen
$_['label_regen_type']           = 'Type';
$_['label_regen_lang']           = 'Language';
$_['label_regen_mode']           = 'Mode';
$_['text_regen_empty']           = 'Empty only';
$_['text_regen_all']             = 'All (overwrite)';
$_['button_regen']               = 'Regenerate SEO URLs';
$_['text_regen_note']            = 'Save settings before regenerating';

// Shared type/mode options
$_['text_type_product']          = 'Product';
$_['text_type_category']         = 'Category';
$_['text_type_manufacturer']     = 'Manufacturer';
$_['text_type_information']      = 'Page';
$_['text_mode_empty']            = 'Empty only';
$_['text_mode_all']              = 'All (overwrite)';
$_['text_all_types']             = 'All types';
$_['text_all_langs']             = 'All languages';
$_['text_all_levels']            = 'All levels';
$_['text_loading']               = 'Loading...';

// Column labels
$_['column_from']                = 'From';
$_['column_to']                  = 'To';
$_['column_code']                = 'Code';
$_['column_hits']                = 'Hits';
$_['column_date']                = 'Date';
$_['column_type']                = 'Type';
$_['column_severity']            = 'Severity';
$_['column_entity']              = 'Entity';
$_['column_issue']               = 'Issue';
$_['column_detail']              = 'Detail';
$_['column_file']                = 'File';
$_['column_size']                = 'Size';
$_['column_field']               = 'Field';
$_['column_count']               = 'Found';
$_['column_status']              = 'Status';

$_['status_new']                 = 'New';
$_['status_in_progress']         = 'In progress';
$_['status_fixed']               = 'Fixed';
$_['status_ignored']             = 'Ignored';

$_['button_diff']                = 'Diff';
$_['button_close']               = 'Close';
$_['button_edit']                = 'Edit';
$_['text_diff_backup']           = 'from backup';
$_['text_diff_current']          = 'in current file';
$_['text_no_diff']               = 'Backup is identical to the current file — no differences.';

// Audit issue short labels
$_['issue_missing_title']          = 'meta_title';
$_['issue_missing_description']    = 'meta_description';
$_['issue_missing_seo_url']        = 'SEO URL';
$_['issue_title_too_short']        = 'meta_title too short';
$_['issue_title_too_long']         = 'meta_title too long';
$_['issue_title_equals_name']      = 'title = name';
$_['issue_description_too_short']  = 'meta_description too short';
$_['issue_description_too_long']   = 'meta_description too long';
$_['issue_duplicate_title']        = 'Duplicate title';
$_['issue_duplicate_description']  = 'Duplicate description';
$_['issue_no_image']               = 'no image';
$_['issue_no_brand']               = 'no brand';
$_['issue_no_body_description']    = 'no description';
$_['issue_body_too_short']         = 'short description';
$_['issue_short_content']          = 'little content';
$_['issue_images_no_alt']          = 'img without alt';
$_['issue_no_category']            = 'no category';
$_['issue_empty_category']         = 'empty category';
$_['issue_no_price']               = 'zero price';
$_['issue_no_model']               = 'no model';
$_['issue_orphan_keyword']         = 'orphan keyword';
$_['issue_duplicate_keyword']      = 'duplicate keyword';
$_['issue_keyword_too_long']       = 'URL too long';
$_['issue_keyword_too_short']      = 'URL too short';
$_['issue_uppercase_in_keyword']   = 'uppercase in URL';
$_['issue_special_chars_in_keyword'] = 'special chars in URL';

// Redirects page
$_['text_from_uri']              = 'From (URI)';
$_['text_to_url']                = 'To (URL or URI)';
$_['text_redirect_modal_title']  = 'Redirect';
$_['button_add']                 = 'Add';

// Meta page
$_['text_bulk_fill']             = 'Bulk Generate';
$_['text_meta_overrides']        = 'Manual Overrides';
$_['text_meta_overrides_hint']   = 'This table stores meta tags you manually set for specific products, categories, etc. Manual overrides have the highest priority — they always take precedence over templates and native OpenCart meta fields.';
$_['text_meta_entity_hint']      = 'Start typing a name — autocomplete will suggest an entity. ID is filled automatically.';
$_['label_entity_search']        = 'Entity';
$_['text_entity_search_placeholder'] = 'Start typing a name...';

$_['label_meta_tpl_mode']        = 'Template priority';
$_['text_meta_tpl_mode_override']= 'Override OC (strict)';
$_['text_meta_tpl_mode_fallback']= 'Fallback only';
$_['text_meta_tpl_mode_hint']    = '<strong>"Override OC"</strong> — the template is always injected into HTML, even when the product/category has its own meta_title/description filled in. Use this when you manage meta tags exclusively through this module.<br><strong>"Fallback only"</strong> — if the OC native field has a value, it wins; the template is used only for empty OC fields. Use this when you fill some pages manually in OC and want the template to cover the rest.<br>Manual overrides (table below) always have the highest priority.';
$_['text_meta_modal_title']      = 'Meta tag override';
$_['label_search_meta']          = 'Search by title...';
$_['button_bulk_start']          = 'Auto-fill';
$_['label_category']             = 'Category';
$_['text_all_categories']        = 'All categories';
$_['text_mask_hint_prefix']      = 'URL mask:';
$_['text_title_hint']            = 'Recommended up to 60 characters';
$_['text_desc_hint']             = 'Recommended up to 160 characters';

// Audit page
$_['text_audit_run']             = 'Run Audit';
$_['text_audit_results']         = 'Results';
$_['text_audit_empty']           = 'No issues found';
$_['text_selected']              = 'selected';
$_['text_analyzing']             = 'Analyzing database...';
$_['text_level_error']           = 'Errors';
$_['text_level_warning']         = 'Warnings';
$_['text_level_info']            = 'Info';
$_['button_audit_run']           = 'Run Audit';
$_['text_per_page']              = 'Per page';

// Robots page
$_['text_robots_editor']         = 'robots.txt Editor';
$_['text_robots_backups']        = 'Backups';
$_['text_no_backups']            = 'No backups found';
$_['button_restore']             = 'Restore';

// Sitemap page
$_['text_sitemap_status_title']  = 'Sitemap Status';
$_['text_sitemap_actions']       = 'Actions';
$_['text_jetsitemap_installed']  = 'Sitemap Generator installed';
$_['text_jetsitemap_missing']    = 'OcKit Sitemap Generator module not found';
$_['text_no_sitemap_file']       = 'Sitemap files missing';
$_['text_sitemap_open_settings'] = 'Sitemap Generator Settings';
$_['button_sitemap_generate']    = 'Update sitemap now';

// AbsURL page
$_['text_absurl_about_title']    = 'About this tool';
$_['text_absurl_about']         = 'Product and category descriptions may contain absolute URLs like <code>&lt;img src="http://old-domain.com/..."&gt;</code> or <code>&lt;a href="http://old-domain.com/..."&gt;</code>. This happens after a domain change or migration from HTTP to HTTPS. This tool scans all descriptions for a specific domain and replaces old URLs with new ones — without touching other content.';
$_['text_absurl_scan_title']     = 'Scan Absolute URLs';
$_['text_absurl_replace_title']  = 'URL Replace';
$_['text_absurl_log_title']      = 'Change Log';
$_['label_search_domain']        = 'Search domain';
$_['label_old_domain']           = 'Old domain';
$_['label_new_domain']           = 'New domain';
$_['label_https_only']           = '<code>http</code> → <code>https</code> only';
$_['button_scan']                = 'Scan';
$_['button_replace_selected']    = 'Replace selected';

// Dashboard page
$_['text_stat_seo_urls']         = 'SEO URL records';
$_['text_stat_redirects']        = 'Active redirects';
$_['text_stat_audit_errors']     = 'SEO errors';
$_['text_stat_audit_warnings']   = 'Audit warnings';
$_['text_stat_redirect_hits']    = 'Redirect hits';
$_['text_stat_chains']           = 'Redirect chains';
$_['text_quick_actions']         = 'Quick Actions';
$_['text_audit_issues_top']      = 'Top SEO Issues';
$_['text_all_audit_results']     = 'All audit results';
$_['text_top_redirects']         = 'Top redirects by hits';
$_['text_chain_warning']         = 'Redirect chains detected';

// FAQ page
$_['text_faq_title']             = 'Frequently Asked Questions';

// Headers page
$_['text_headers_test']          = 'Test rule';
$_['text_headers_rules']         = 'Header rules';
$_['button_add_rule']            = 'Add rule';
$_['text_headers_about_title']   = 'About this tool';
$_['text_headers_about']         = 'Manage <code>X-Robots-Tag</code> headers and the <code>robots</code> meta tag for specific URLs without editing <code>robots.txt</code>. Use cases: block indexing of search, filter, or account pages; temporarily hide a section under development. For each URI pattern (wildcards <code>*</code> supported) set the <code>robots</code> value and choose whether to send the HTTP header, inject <code>&lt;meta name="robots"&gt;</code>, or both. Use the rule test below to see which rule matches a given URI.';
$_['text_no_headers_rules']      = 'No header rules yet. Click “Add rule” to create the first one.';
$_['label_hdr_uri']              = 'URI';
$_['label_hdr_robots']           = 'Robots';
$_['label_hdr_sort_order']       = 'Sort order';
$_['label_hdr_comment']          = 'Comment';
$_['label_hdr_status']           = 'Active';
$_['placeholder_hdr_uri']        = '/catalog/product/* or /admin/*';

// JS i18n
$_['js_saving']                  = 'Saving...';
$_['js_saved']                   = 'Saved!';
$_['js_error_save']              = 'Save error.';
$_['js_regen_done']              = 'Regenerated:';
$_['js_regen_inserted']          = 'created';
$_['js_regen_updated']           = 'updated';
$_['js_regen_skipped']           = 'skipped';
$_['js_regen_confirm_all']       = 'Overwrite ALL SEO URLs for this type?';
$_['js_confirm_delete_redirect'] = 'Delete this redirect?';
$_['js_import_success']          = 'Imported:';
$_['js_import_skipped']          = 'Skipped:';
$_['js_error_redirect_fields']   = 'Fill in both From and To fields.';
$_['js_confirm_delete_meta']     = 'Delete this override?';
$_['js_bulk_complete']           = 'Fill complete:';
$_['js_bulk_filled']             = 'filled';
$_['js_bulk_skipped']            = 'skipped';
$_['js_audit_running']           = 'Running audit...';
$_['js_audit_done']              = 'Audit complete';
$_['js_audit_errors']            = 'errors';
$_['js_audit_warnings']          = 'warnings';
$_['js_audit_info']              = 'info';
$_['js_confirm_restore_robots']  = 'Restore this backup? Current file will be replaced.';
$_['js_robots_saved']            = 'robots.txt saved';
$_['js_sm_generate_ok']          = 'Generation started in background';
$_['js_sm_generate_fail']        = 'Failed to start generation';
$_['js_sm_ping_ok']              = 'Google notified successfully';
$_['js_sm_ping_fail']            = 'Ping failed';
$_['js_absurl_scan_found']       = 'occurrences found';
$_['js_absurl_replaced']         = 'Rows replaced';
$_['js_confirm_replace_absurl']  = 'Replace URLs in selected records?';
$_['js_confirm_flatten']         = 'Fix all redirect chains automatically?';
$_['js_flatten_done']            = 'Chains fixed:';

// Buttons
$_['button_save']                = 'Save';
$_['button_cancel']              = 'Cancel';
$_['button_delete']              = 'Delete';
$_['button_flatten_chains']      = 'Fix chains';

// Google Search Console (Google tab)
$_['tab_google']                  = 'Google';
$_['text_section_gsc']            = 'Google Search Console';
$_['text_section_gsc_stats']      = 'Search analytics';
$_['text_section_gsc_sitemaps']   = 'Sitemaps in GSC';
$_['text_gsc_about']              = 'Search Console + Indexing API integration via OAuth2. View search analytics (queries, clicks, CTR, position), manage sitemaps, inspect URL indexing and instantly notify Google about page updates/removals (Indexing API).';
$_['text_gsc_redirect_hint']      = 'Copy this URL into Google Cloud Console → Credentials → OAuth client → Authorized redirect URIs.';
$_['text_gsc_site_property_hint'] = 'Exact value from GSC → Settings → Property. For URL-prefix include the trailing slash; for Domain property use the <code>sc-domain:example.com</code> form.';
$_['text_gsc_connect_hint']       = 'First save Client ID/Secret with the «Save» button, then click «Connect Google».';
$_['text_gsc_not_loaded']         = 'Click «Load» to fetch the last 28 days.';
$_['text_gsc_connected']          = 'Connected';
$_['text_gsc_disconnected']       = 'Not connected';
$_['text_gsc_confirm_disconnect'] = 'Disconnect Google and remove the stored token?';
$_['text_gsc_submitted']          = 'URL submitted to Google';
$_['text_no_data']                = 'No data';
$_['text_confirm_delete']         = 'Delete?';
$_['label_gsc_redirect']          = 'Redirect URI';
$_['label_gsc_site_property']     = 'Site property';
$_['label_gsc_status']            = 'Connection';
$_['button_gsc_connect']          = 'Connect Google';
$_['button_gsc_disconnect']       = 'Disconnect';
$_['button_load']                 = 'Load';
$_['button_submit']               = 'Submit';
$_['gsc_col_key']                 = 'Query / page';
$_['gsc_col_clicks']              = 'Clicks';
$_['gsc_col_impressions']         = 'Impressions';
$_['gsc_col_ctr']                 = 'CTR';
$_['gsc_col_position']            = 'Position';
$_['gsc_col_path']                = 'Path';
$_['gsc_col_last_submitted']      = 'Last submitted';
$_['gsc_col_errors']              = 'Errors';
$_['gsc_col_warnings']            = 'Warnings';
$_['gsc_dim_query']               = 'Query';
$_['gsc_dim_page']                = 'Page';
$_['gsc_dim_country']             = 'Country';
$_['gsc_dim_device']              = 'Device';


// Preview / Apply (bulk operations) + cache labels
$_['button_preview']             = 'Preview';
$_['button_apply']                = 'Apply';
$_['js_running_preview']         = 'Searching…';
$_['js_running_apply']           = 'Applying…';
$_['js_matched_preview']         = 'Matched: ';
$_['js_matched_apply']           = 'Updated: ';
$_['js_confirm_bulk_apply']      = 'Apply replacements? This cannot be undone without a DB backup (history records are created automatically).';
$_['cache_warm']          = 'generated';
$_['cache_cold']                 = 'cold';
$_['cache_entries']              = 'entries';
$_['cache_kb']                   = 'KB';

// Schema Data Providers — explainer
$_['text_schema_providers_about']      = 'A Data Provider is your own PHP class that <b>fetches extra data</b> for Schema.org markup (e.g. stock levels, wholesale prices, third-party ratings) and exposes it inside Schema rule templates via <code>{{var.path}}</code> substitutions. If the built-in fields (Product, Offer, Organization, etc.) are enough — providers are optional.';
$_['text_schema_providers_howto_title']= 'How it works — quick example';
$_['text_schema_providers_step_1']     = '<b>1.</b> Create a PHP class that implements <code>OcKit\\SeoCore\\Libs\\SchemaDataProviderInterface</code>. The class must be autoloadable (e.g. shipped in a separate OCMOD module with its own <code>$autoload</code>). The <code>getData()</code> method returns an array that becomes available in the Schema template:';
$_['text_schema_providers_step_2']     = '<b>2.</b> Register the class FQCN in the field below (comma-, semicolon- or newline-separated). Now any Schema rule template (Schema → Custom Rules) can use these variables:';
$_['text_schema_providers_hint']       = 'List full FQCNs (with namespace), comma- or newline-separated. Leave empty if you do not need custom code — built-in Schema types (Product, BreadcrumbList, Organization, WebSite, Article) work without providers.';

// GSC how-to
$_['text_gsc_howto_title'] = 'How to get Client ID / Client Secret';
$_['text_gsc_howto_1']     = 'Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> and create a new project (or pick an existing one).';
$_['text_gsc_howto_2']     = 'Left menu: <b>APIs &amp; Services → Library</b>. Enable two APIs: <code>Google Search Console API</code> and <code>Web Search Indexing API</code> (click Enable on each).';
$_['text_gsc_howto_3']     = '<b>APIs &amp; Services → OAuth consent screen</b>. User type <i>External</i>, fill app name, support email, developer email. On the Scopes step — skip. On Test users — add the Google account that has access to your GSC property. Save.';
$_['text_gsc_howto_4']     = '<b>APIs &amp; Services → Credentials → Create Credentials → OAuth client ID</b>. Type <i>Web application</i>. In <b>Authorized redirect URIs</b> paste the URL shown in the <b>Redirect URI</b> field below — exact copy.';
$_['text_gsc_howto_5']     = 'Click Create — Google shows <b>Client ID</b> and <b>Client Secret</b>. Copy both into the fields below.';
$_['text_gsc_howto_6']     = 'In <b>Site property</b> paste the exact property value from GSC (URL-prefix — with trailing slash; Domain property — <code>sc-domain:example.com</code>). Click <b>Save</b>, then <b>Connect Google</b>.';

// Sitemap ping (modernized)
$_['text_sm_ping_google_hint']        = 'Submit sitemap to Google Search Console via API (requires OAuth connection in the Google tab).';
$_['text_sm_ping_bing_hint']          = 'Bing sitemap ping was retired in 2022. Use Bing Webmaster Tools or IndexNow.';
$_['text_sm_ping_google_ok']          = 'Sitemap submitted to Google Search Console.';
$_['text_sm_ping_google_need_oauth']  = 'Connect Google in the «Google» tab first (OAuth). Google retired the legacy /ping endpoint in 2023.';
$_['text_sm_ping_bing_deprecated']    = 'Bing /ping endpoint was retired in 2022. Use Bing Webmaster Tools or the IndexNow protocol.';

$_['title_store_selector']        = 'Store (for multi-store installs)';
$_['placeholder_optional']        = '(optional)';
$_['js_error']                    = 'Error';
$_['text_org_type_organization']  = 'Organization (generic)';
$_['text_org_type_online_store']  = 'OnlineStore (e-commerce)';
$_['text_org_type_store']         = 'Store (retail)';
$_['text_org_type_local_business']= 'LocalBusiness (local)';
$_['text_org_type_restaurant']    = 'Restaurant';
$_['ph_address_street']           = '1 Main St';
$_['ph_address_city']             = 'New York';
$_['ph_address_region']           = 'NY';
$_['ph_price_range']              = '$$ or 100-500 USD';
$_['ph_founding_date']            = '2015 or 2015-03-01';
$_['ph_founders']                 = "John Smith\nJane Doe";
$_['text_schema_placeholders_hint']= 'Placeholders: <code>{{product.name}}</code>, <code>{{category.name}}</code>, <code>{{get.param}}</code>, <code>{{config.store_name}}</code>, <code>{{page.url}}</code>, <code>{{page.title}}</code>. Blocks: <code>{{#each items}}…{{/each}}</code>, <code>{{#if cond}}…{{/if}}</code>.';
$_['text_meta_mode_override']     = 'template → HTML (always)';
$_['text_meta_mode_fallback']     = 'OC field → HTML, template → fallback';
$_['text_canonical_pagination_first_ex']= 'canonical → /sukni';
$_['text_canonical_pagination_self_ex'] = 'canonical → /sukni?page=2';
$_['text_canonical_filters_base_ex']    = 'canonical → base';
$_['text_canonical_filters_self_ex']    = 'canonical → self';

$_['text_image_alt_about']        = '<strong>What it does:</strong> finds <code>&lt;img&gt;</code> tags <strong>inside the HTML product description</strong> (<code>oc_product_description.description</code> — the WYSIWYG field) and adds <code>alt="product name"</code> to images that lack one. Existing alt attributes are NOT overwritten.<br><br><strong>What it does NOT do:</strong> ignores the gallery (thumb/oc_product_image) — that is controlled by the theme template. Ignores categories / blog / manufacturers descriptions.<br><br><strong>Preview</strong>: simulates — returns how many rows and <code>&lt;img&gt;</code> tags would be touched, with NO DB write.<br><strong>Apply</strong>: actually UPDATEs <code>product_description.description</code>.';

$_['js_url_not_set']         = 'URL is not set';
$_['js_running']             = 'Running…';
$_['js_preview_label']       = 'Preview: ';
$_['js_done_label']          = 'Done: ';
$_['js_products_label']      = 'products ';
$_['js_alts_added']          = ', alts added ';
$_['js_confirm_img_alt']     = 'Run bulk alt fill for all products? This cannot be undone without a DB backup.';
$_['js_broken_none']         = 'No broken links found';
$_['js_loading']             = 'Loading…';
$_['js_records_label']       = 'Records: ';

// Missing keys after |default strip — restored
$_['tab_home']                       = 'Home page';
$_['label_home_redirect_index']      = 'Remove <code>/index.php?route=common/home</code>';
$_['label_schema_org_contact_languages']= 'Support languages';
$_['label_schema_org_country']       = 'Country (ISO code)';
$_['label_schema_org_founders']      = 'Founders';
$_['label_schema_org_founding_date'] = 'Founding date';
$_['label_schema_org_geo']           = 'Latitude / Longitude';
$_['label_schema_org_locality']      = 'City';
$_['label_schema_org_opening_hours'] = 'Opening hours';
$_['label_schema_org_postal_code']   = 'Postal code';
$_['label_schema_org_price_range']   = 'Price range (priceRange)';
$_['label_schema_org_region']        = 'Region / state';
$_['label_schema_org_same_as']       = 'sameAs (social profiles)';
$_['label_schema_org_street']        = 'Street, building';
$_['label_schema_org_type']          = 'Organization type';
$_['label_schema_org_vat_id']        = 'vatID / Tax ID';
$_['label_schema_providers']         = 'Provider classes';
$_['label_strip_query_params']       = 'Strip query parameters from canonical';
$_['label_trailing_slash_all']       = 'All URLs';
$_['label_trailing_slash_categories']= 'Categories only';
$_['label_trailing_slash_off']       = 'No slash';
$_['label_trailing_slash_products']  = 'Products only';
$_['label_webhook_secret']           = 'Shared secret';
$_['label_webhook_url']              = 'Endpoint URL';
$_['text_ab_test_enabled']           = 'Enabled';
$_['text_ab_test_enabled_hint']      = 'Enable title-variant rotation on the storefront';
$_['text_ab_test_help']              = 'Create two title variants for an entity — the module shows A or B at random (stable per visitor via cookie). Impression counters help pick the winner based on CTR from Search Console.';
$_['text_ab_test_new']               = 'New A/B title test';
$_['text_ab_test_title']             = 'A/B title test';
$_['text_broken_links']              = 'Broken links';
$_['text_broken_links_about']        = 'Scans <code>&lt;a href&gt;</code> in product/category/manufacturer/information descriptions and HEAD-pings each unique URL. Results are stored in DB; re-running overwrites.';
$_['text_hints_examples']            = 'Examples: rel=dns-prefetch href=//fonts.gstatic.com | rel=preconnect href=https://cdn.example.com | rel=preload href=/font.woff2 as=font type=font/woff2 crossorigin=anonymous';
$_['text_home_about']                = 'Meta tags for the home page. Fill in for each language — empty fields fall back to store defaults.';
$_['text_home_redirect_index_hint']  = 'When enabled: all internal links to home are rendered as <code>/</code> or <code>/{prefix}/</code> without <code>/index.php?route=common/home</code>';
$_['text_image_alt_tools']           = 'Image alt — bulk fill';
$_['text_redirect_expires']          = 'Expires at';
$_['text_redirect_expires_hint']     = 'Optional: after this date the redirect stops applying (cron-job can purge it).';
$_['text_resource_hints_about']      = 'Adds &lt;link rel="preload"&gt; / &lt;link rel="dns-prefetch"&gt; / &lt;link rel="preconnect"&gt; into the &lt;head&gt; of every page. Useful for speeding up critical resource loads (fonts, key scripts) and DNS resolution of external domains.';
$_['text_robots_quick_block_index']  = 'Block ?route=*';
$_['text_robots_quick_default']      = 'Default template';
$_['text_robots_quick_sitemap']      = 'Add Sitemap URL';
$_['text_schema_org_address']        = 'Address (PostalAddress)';
$_['text_schema_org_contact_languages_hint']= 'Languages your customer support speaks. Comma- or newline-separated.';
$_['text_schema_org_country_hint']   = 'Two-letter ISO 3166-1 alpha-2 code: UA, PL, US, etc.';
$_['text_schema_org_founders_hint']  = 'Founder names, one per line.';
$_['text_schema_org_founding_date_hint']= 'Year or full ISO 8601 date (YYYY-MM-DD).';
$_['text_schema_org_geo']            = 'Geo coordinates + hours';
$_['text_schema_org_geo_hint']       = 'Coordinates are needed for LocalBusiness/Restaurant. You can copy from Google Maps (right-click → Coordinates).';
$_['text_schema_org_meta']           = 'Profiles, registration, founders';
$_['text_schema_org_opening_hours_hint']= 'One line per period. Days: Mo, Tu, We, Th, Fr, Sa, Su (range Mo-Fr allowed). Time in HH:MM-HH:MM. Use <code>closed</code> or just skip the line for weekends.';
$_['text_schema_org_price_range_hint']= 'For LocalBusiness/Store/Restaurant/OnlineStore only. Allowed: $-$$$$ or a currency range.';
$_['text_schema_org_same_as_hint']   = 'URLs of your official profiles (one per line). Google uses them to consolidate brand knowledge.';
$_['text_schema_org_type_hint']      = 'For LocalBusiness/Store/Restaurant Google requires address and opening hours. For an online shop choose OnlineStore or Organization.';
$_['text_schema_providers']          = 'Data Providers';
$_['text_section_resource_hints']    = 'Preload / DNS prefetch';
$_['text_section_webhook']           = 'Webhook';
$_['text_stat_seo_score']            = 'SEO Score';
$_['text_stat_seo_score_hint']       = 'Composite 0–100 score from the last audit: 100 - (errors*30 + warnings*10 + info*2) / N entities';
$_['text_strip_query_params_hint']   = 'List of params to strip from the canonical URL on 301 redirect (tracking marks). Comma- or newline-separated. Wildcards supported via trailing <code>*</code>: <code>utm_*</code> covers <code>utm_source</code>, <code>utm_medium</code>, etc. Other GET params (page, ajax, sort, filter…) are preserved automatically.';
$_['text_url_bulk_find']             = 'Find';
$_['text_url_bulk_regex']            = 'PCRE mode';
$_['text_url_bulk_replace']          = 'Bulk search/replace in keyword';
$_['text_url_bulk_replace_about']    = 'Find a substring (or PCRE pattern) and replace it across all SEO URL keywords. Preview — no write. Apply — updates the DB, adds history records and an auto-redirect 301 for the changed slug.';
$_['text_url_bulk_replace_with']     = 'Replace with';
$_['text_url_history']               = 'URL change history';
$_['text_webhook_about']             = 'HTTP POST to an external URL on SEO URL/redirect changes. Useful for CDN purge, Slack notifications, ETL pipelines. Payload: { event, timestamp, payload }. Signature in the X-SCF-Signature: sha256=&lt;HMAC&gt; header.';
$_['text_webhook_secret_hint']       = 'When set — every POST gets the X-SCF-Signature: sha256=HMAC(body, secret) header for receiver-side verification.';

$_['text_org_group_general']  = 'General';
$_['text_org_group_local']    = 'Stores (LocalBusiness)';
$_['text_org_group_food']     = 'Food & drink';
$_['text_org_group_services'] = 'Services';
$_['text_org_group_auto_home']= 'Auto & home services';
$_['text_org_group_lodging']  = 'Lodging & rental';
$_['text_org_group_other']    = 'Education, sports, other';
$_['button_send']           = 'Send';

$_['label_allow_duplicate_keywords'] = 'Allow same SEO URL across languages';
$_['text_allow_duplicate_keywords_hint'] = 'By default OpenCart 3 blocks the same <code>keyword</code> across different languages of one product/category/manufacturer/information (raises a "must be unique" error). When ON — this cross-language check is bypassed so you can use the same slug in every language field. The cross-entity check (one slug for different products) still applies.';
$_['cache_warmed']        = 'Cache generated:';

$_['text_type_article']         = 'Blog post';
$_['text_type_blog_category']   = 'Blog category';
$_['text_meta_vars_article']    = 'Available variables: <code>{name}</code> — article title, <code>{description}</code> — first 160 chars, <code>{store_name}</code> — store name, <code>{year}</code> — current year, <code>{page}</code> — page number (empty on page 1).<br>Conditional blocks: <code>{{#if page}} — page {page}{{/if}}</code>.';
$_['text_meta_vars_blog_category'] = 'Available variables: <code>{name}</code> — name, <code>{description}</code> — first 160 chars, <code>{count}</code> — post count, <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code>.';

$_['text_section_route_meta']    = 'Custom route meta';
$_['text_route_meta_about']      = '<strong>What is this?</strong> Pages without an entity (manufacturer list <code>product/manufacturer</code>, blog index <code>blog/menu</code>, third-party modules <code>vendor/vendor</code>) have no entity-meta path. Set title/description/keywords <strong>per route</strong>, per language. Wildcard masks supported (<code>vendor/*</code>), variables <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code>, conditional blocks <code>{{#if page}}…{{/if}}</code>.';
$_['text_route_meta_route_hint'] = 'Exact route (<code>product/manufacturer</code>) or wildcard mask (<code>vendor/*</code>). If entity-meta already handles the page (product/category) — route-meta does NOT override it, only fills empty fields.';
$_['text_route_meta_vars_hint']  = 'Variables: <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code> (empty on page 1). Conditional blocks: <code>{{#if page}} — p. {page}{{/if}}</code>.';
$_['text_route_meta_modal_title']= 'Route meta';
$_['button_add_manufacturer_list']= '+ Manufacturer list';
$_['button_add_blog_index']       = '+ Blog';

$_['text_section_image_masks']    = 'Product image alt/title masks';
$_['text_image_masks_about']      = '<strong>What is this?</strong> Title and alt for all product gallery images are generated automatically from a mask — per language. Great for SEO and accessibility, no manual entry needed per product.';
$_['text_image_masks_vars']       = 'Variables: <code>{name}</code> — product name, <code>{category}</code> — main category, <code>{price}</code> — price, <code>{special}</code> — special price (empty if none), <code>{sort_order}</code> — index (1, 2, 3…), <code>{sku}</code>, <code>{model}</code>, <code>{manufacturer}</code> / <code>{brand}</code>, <code>{store_name}</code>, <code>{year}</code>.<br>Conditional blocks: <code>{{#if special}} sale {special}{{/if}}</code> — rendered only when variable is non-empty. <strong>Storefront theme</strong> must use <code>image.alt</code> / <code>image.title</code> in the <code>&lt;img&gt;</code> tag (most themes do).';
$_['label_image_alt_tpl']         = 'alt template';
$_['label_image_title_tpl']       = 'title template';
