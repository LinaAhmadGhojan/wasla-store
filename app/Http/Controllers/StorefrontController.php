<?php

namespace App\Http\Controllers;

class StorefrontController extends Controller
{
    public function shop()
    {
        return view('storefront.shop');
    }

    public function compare()
    {
        return view('storefront.compare');
    }

    public function productShow($product)
    {
        return view('storefront.product-show', ['productId' => $product]);
    }

    public function cart()
    {
        return view('storefront.cart');
    }

    public function checkout()
    {
        return view('storefront.checkout');
    }

    public function orderConfirmation()
    {
        return view('storefront.order-confirmation');
    }

    public function myRequests()
    {
        return view('storefront.my-requests');
    }

    public function profile()
    {
        return view('storefront.profile');
    }

    public function addresses()
    {
        return view('storefront.addresses');
    }

    public function favorites()
    {
        return view('storefront.favorites');
    }

    public function storeShow($vendor)
    {
        return view('storefront.store', ['storeId' => $vendor]);
    }

    public function trackOrder($order)
    {
        return view('storefront.order-track', ['orderId' => $order]);
    }

    public function orderInvoice($order)
    {
        return view('storefront.order-invoice', ['orderId' => $order]);
    }

    public function login()
    {
        return view('storefront.login');
    }

    public function register()
    {
        return view('storefront.register');
    }

    public function forgotPassword()
    {
        return view('storefront.forgot-password');
    }

    public function resetPassword()
    {
        return view('storefront.reset-password');
    }

    public function buyFromAnywhere()
    {
        return view('storefront.buy-from-anywhere');
    }

    public function browseHub()
    {
        return view('storefront.browse-hub');
    }

    public function browsePlatform(string $platform)
    {
        if ($platform === 'shein') {
            return view('storefront.shein-webview-shell');
        }

        return view('storefront.browse-platform', ['platform' => $platform]);
    }

    public function browseProduct(string $platform, string $product)
    {
        return view('storefront.browse-product', [
            'platform' => $platform,
            'productId' => $product,
        ]);
    }
}
