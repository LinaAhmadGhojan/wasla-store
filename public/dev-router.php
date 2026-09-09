<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// Product detail URLs must hit Laravel even when public/products/{id}/ exists for images.
if (preg_match('#^/products/(\d+)/?$#', $uri) || preg_match('#^/product/(\d+)/?$#', $uri)) {
    require __DIR__.'/index.php';

    return true;
}

if ($uri !== '/' && file_exists(__DIR__.$uri)) {
    return false;
}

require __DIR__.'/index.php';

return true;
