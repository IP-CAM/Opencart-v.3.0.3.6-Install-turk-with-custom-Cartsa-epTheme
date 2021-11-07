<?php
// Heading
$_['heading_title']             = 'Merchant Center Feed (Google, Bing, Twenga...)';
$_['text_edit']                 = 'Edit Feed Settings';

// Text
$_['text_feed']                 = 'Product Feeds';
$_['text_success']              = 'Success: You have modified Merchant Center feed!';
$_['text_open']                 = 'Open Feed';
$_['text_copy']                 = 'Copy URL';
$_['text_copy_success']         = 'Feed URL copied successfully.';
$_['text_copy_fail']            = 'Copy unsupported by the browser, please copy the URL address directly.';

//options
$_['text_skip_products']        = 'Exclude from feed';
$_['text_in_stock']             = 'In stock';
$_['text_out_of_stock']         = 'Out of stock';
$_['text_preorder']             = 'Preorder';
$_['text_product_id']           = 'Product ID';
$_['text_model']                = 'Model';
$_['text_option_name']          = 'Option name';
$_['text_option_id']            = 'Option ID';
$_['text_direct_image']         = 'Use images directly (without cache)';
$_['text_minimal_image']        = ' (minimal image size)';
$_['text_attribute']            = ' (attribute)';
$_['text_option']               = ' (option)';
$_['text_no_carrier']           = 'Shipping set in the Merchant account (recommend)';

// Entry
$_['entry_status']              = 'Status:';
$_['entry_save_to_file']        = 'Save to file:';
$_['entry_feed_url']            = 'Feed URL:';
$_['entry_base_taxonomy']       = 'Main taxonomy categories:';
$_['entry_size_options']        = 'Select options/attributes with sizes:';
$_['entry_color_options']       = 'Select options/attributes with colors:';
$_['entry_pattern_options']     = 'Select options/attributes with patterns:';
$_['entry_material_options']    = 'Select options/attributes with materials:';
$_['entry_use_meta']            = 'Use meta title/description:';
$_['entry_clear_html']          = 'Remove HTML tags from the description:';
$_['entry_pid1']                = 'Product ID:';
$_['entry_option_ids']          = 'Option IDs:';
$_['entry_use_tax']             = 'Include taxes in the price:';
$_['entry_image_cache']         = 'Image format:';
$_['entry_disabled_products']   = 'Set disabled products as:';
$_['entry_sold_out_products']   = 'Set products with 0 quantity as:';
$_['entry_language']            = 'Language:';
$_['entry_currency']            = 'Currency:';
$_['entry_carrier']             = 'Shipping carrier:';
$_['entry_compression']         = 'GZIP compression:';
$_['entry_gtin_source']         = 'Preferred GTIN source:';

// Help
$_['help_status']               = 'Enables/Disables the feed.';
$_['help_save_to_file']         = 'Saves the feed to a XML file and loads it. If you have timeout errors, enable this setting.';
//other URL parameters: tax_rate additional_images redirect start limit
$_['help_feed_url']             = 'You can change the feed URL parameters to get feeds in different languages, currencies etc.:
<br />
<style>
.feed_url_info {
    width: 100%;
    display: block;
    overflow-x: auto;
}
.feed_url_info th, .feed_url_info td {
    border: 1px solid;
    padding: 2px 6px;
}
</style>
<table class="feed_url_info">
<tr>
<th>Languages (override):</th>
<th>Currencies (override):</th>
<th>Limit the range of products:</th>
<th>Use taxes (override):</th>
<th>Include/Exclude products/categories/manufacturers:</th>
<th>Include/Exclude by keywords:</th>
<th>Multistore:</th>
</tr>
<tr>
<td>&lang={language code}</td>
<td>&curr={currency code}</td>
<td>&range={from_product-to_product}</td>
<td>&tax={0 or 1}</td>
<td>&(in)exclude_product(category/manufacturer)_id={product/category/manufacturer ids separated by comma}</td>
<td>&(in)exclude_keyword={keywords separated by comma}</td>
<td>Replace the shop domain.</td>
</tr>
</table>
<br />
Example: .../index.php?route=extension/feed/google_merchant_center<wbr>&lang=us&curr=EUR&include_product_id=42,30,47&range=1-500&tax=0';
$_['help_base_taxonomy']        = 'Selecting your main category will reduce the amount of options available in the category setup Catalog->Categories[Edit](Data).';
$_['help_size_options']         = 'Select options/attributes which contain the size values. Options will affect your product IDs.';
$_['help_color_options']        = 'Select options/attributes which contain the color values. Options will affect your product IDs. You can also set the color in Catalog->Products[edit](Google Merchant Center).';
$_['help_pattern_options']      = 'Select options/attributes which contain the pattern values. Options will affect your product IDs.';
$_['help_material_options']     = 'Select options/attributes which contain the material values. Options will affect your product IDs.';
$_['help_use_meta']             = 'If meta is not available on a product the visible front page title/description will be used instead.';
$_['help_clear_html']           = 'Removes HTML tags like div, styles... from the title and descriptions. Recommend to enable.';
$_['help_pid1']                 = 'Product ID used in the feed. For dynamic ads, this must be identical to your remarketing scripts.';
$_['help_option_ids']           = 'Product option IDs used in the feed. For dynamic ads, this must be identical to your remarketing scripts.';
$_['help_use_tax']              = 'When enabled, prices will include taxes. In USA, Canada and India the prices must be without taxes!';
$_['help_image_cache']          = 'Select an image size format (larger is better). Disabling the image cache might speed up the feed creation, but the images will be used as they are, so without watermarks and without adjusting the image dimensions to the minimal feed requirements.';
$_['help_disabled_products']    = 'Defines how to mark disabled products in the feed.';
$_['help_sold_out_products']    = 'Defines how to mark zero stock products in the feed.';
$_['help_language']             = 'Select the language used in the feed.';
$_['help_currency']             = 'Select the currency used in the feed.';
$_['help_carrier']              = 'Select carrier for shipping. For more precise shipping information set the shipping in your merchant center account instead.';
$_['help_compression']          = 'Use GZIP compression (level 5) to reduce feed size and save server bandwidth.';
$_['help_gtin_source']          = 'If the preferred value is empty, it will use whatever is available.';

// Error
$_['error_permission']          = 'Warning: You do not have permission to modify the extension!';
?>
