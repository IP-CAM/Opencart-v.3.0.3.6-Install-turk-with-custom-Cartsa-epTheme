<?php
// Heading
$_['heading_title']             = 'Customizable Feeds';
$_['text_edit']                 = 'Edit Feed Settings';

// Text
$_['text_feed']                 = 'Product Feeds';
$_['text_success']              = 'Success: You have modified the Customizable Feeds!';
$_['text_open']                 = 'Open Feed';
$_['text_copy']                 = 'Copy URL';
$_['text_copy_success']         = 'Feed URL copied successfully.';
$_['text_copy_fail']            = 'Copy unsupported by the browser, please right click on the feed name and copy the address.';

//options
$_['text_skip_products']        = 'Exclude from feed';
$_['text_direct_image']         = 'Use images directly (without cache)';
$_['text_minimal_image']        = ' (minimal image size)';

$_['no_custom_templates']       = 'Please upload feed templates to the ../feed_templates folder.';
$_['no_custom_directory']       = 'Please create/upload the feed template folder "feed_templates" into the opencart root folder.';

// Entry
$_['entry_status']              = 'Status:';
$_['entry_save_to_file']        = 'Save to file:';
$_['entry_feed_url']            = 'Feeds:';
$_['entry_template_name']       = 'Feed Templates:';
$_['entry_template_download']   = 'Download Free Templates';
$_['entry_options']             = 'Select used options:';
$_['entry_use_meta']            = 'Use meta title/description:';
$_['entry_clear_html']          = 'Remove HTML tags from the description:';
$_['entry_image_cache']         = 'Image format:';
$_['entry_in_stock_products']   = 'Set in stock products as:';
$_['entry_disabled_products']   = 'Set disabled products as:';
$_['entry_sold_out_products']   = 'Set products with 0 quantity as:';
$_['entry_language']            = 'Language:';
$_['entry_currency']            = 'Currency:';
$_['entry_carriers']            = 'Carriers:';
$_['entry_shop_address']        = 'Use only shop address for carriers:';
$_['entry_template_location']   = 'Feed templates folder:';
$_['entry_compression']         = 'GZIP compression:';

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
<th>Include/Exclude products/categories/manufacturers:</th>
<th>Include/Exclude by meta keywords:</th>
<th>Multistore:</th>
</tr>
<tr>
<td>&lang={language code}</td>
<td>&curr={currency code}</td>
<td>&range={from_product-to_product}</td>
<td>&(in)exclude_product(category/manufacturer)_id={product/category/manufacturer ids separated by comma}</td>
<td>&(in)exclude_keyword={keywords separated by comma}</td>
<td>Replace the shop domain.</td>
</tr>
</table>
<br />
Example: .../index.php?route=extension/feed/custom<wbr>&lang=us&curr=EUR&include_product_id=42,30,47&range=1-500';
$_['help_options']              = 'Select options, it will affect your product IDs and available options.';
$_['help_use_meta']             = 'If meta is not available on a product the visible front page title/description will be used instead.';
$_['help_clear_html']           = 'Removes HTML tags like div, styles... from the title and descriptions. Recommend to enable.';
$_['help_image_cache']          = 'Select an image size format (larger is better). Disabling the image cache might speed up the feed creation, but the images will be used as they are, so without watermarks and without adjusting the image dimensions to the minimal feed requirements.';
$_['help_in_stock_products']    = 'Defines how to mark in stock products in the feed. Use \'exclude!\' to exclude the products from the feed.';
$_['help_disabled_products']    = 'Defines how to mark disabled products in the feed. Use \'exclude!\' to exclude the products from the feed.';
$_['help_sold_out_products']    = 'Defines how to mark zero stock products in the feed. Use \'exclude!\' to exclude the products from the feed.';
$_['help_language']             = 'Select the language used in the feed.';
$_['help_currency']             = 'Select the currency used in the feed.';
$_['help_carriers']             = 'Select the available shipping carriers. Set if the feed contains shipping info.';
$_['help_shop_address']         = 'If enabled only the shop address will be used in the carrier prices. If disabled, all countries and zones will be used.';
$_['help_template_location']    = 'Set the folder containing your custom feed templates (within opencart root).';
$_['help_compression']          = 'Use GZIP compression (level 5) to reduce feed size and save server bandwidth.';

// Error
$_['error_permission']          = 'Warning: You do not have permission to modify the extension!';
?>
