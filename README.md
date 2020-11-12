# LoewenstarkRedirect
Shopware 6 redirect module. In case of 404 try to find a redirect url for products and later for categories.

## Product URLs
Put a CSV with the old urls and skus in the following folder: var/loewenstark/redirect/url_export.csv

To export the URLs from Magento the following tool can be used
_exportTools/magento/products/url_export.php

The format of the csv is:
id;sku;url

e.g.
123;12-345;https://example.com/old-product-url

## Category URLs
Not yet implemented.