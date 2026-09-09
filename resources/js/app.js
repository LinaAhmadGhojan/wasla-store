import './bootstrap';
import { createApp } from 'vue';
import WaslaHome from './components/WaslaHome.vue';
import AdminDashboard from './components/AdminDashboard.vue';
import ShopPage from './components/storefront/ShopPage.vue';
import ProductPage from './components/storefront/ProductPage.vue';
import CartPage from './components/storefront/CartPage.vue';
import CheckoutPage from './components/storefront/CheckoutPage.vue';
import OrderConfirmationPage from './components/storefront/OrderConfirmationPage.vue';
import LoginPage from './components/storefront/LoginPage.vue';
import RegisterPage from './components/storefront/RegisterPage.vue';
import ForgotPasswordPage from './components/storefront/ForgotPasswordPage.vue';
import ResetPasswordPage from './components/storefront/ResetPasswordPage.vue';
import BuyFromAnywherePage from './components/storefront/BuyFromAnywherePage.vue';
import BrowseHubPage from './components/storefront/BrowseHubPage.vue';
import BrowsePlatformPage from './components/storefront/BrowsePlatformPage.vue';
import BrowseProductPage from './components/storefront/BrowseProductPage.vue';
import MyRequestsPage from './components/storefront/MyRequestsPage.vue';
import ProfilePage from './components/storefront/ProfilePage.vue';
import AddressesPage from './components/storefront/AddressesPage.vue';
import FavoritesPage from './components/storefront/FavoritesPage.vue';
import SheinShopPage from './components/storefront/SheinShopPage.vue';
import StorePage from './components/storefront/StorePage.vue';
import ComparePage from './components/storefront/ComparePage.vue';
import OrderTrackPage from './components/storefront/OrderTrackPage.vue';
import OrderInvoicePage from './components/storefront/OrderInvoicePage.vue';

const homeElement = document.querySelector('wasla-home');
const adminElement = document.querySelector('admin-dashboard');
const shopElement = document.querySelector('wasla-shop');
const productElement = document.querySelector('wasla-product');
const cartElement = document.querySelector('wasla-cart');
const checkoutElement = document.querySelector('wasla-checkout');
const orderConfirmationElement = document.querySelector('wasla-order-confirmation');
const loginElement = document.querySelector('wasla-login');
const registerElement = document.querySelector('wasla-register');
const forgotPasswordElement = document.querySelector('wasla-forgot-password');
const resetPasswordElement = document.querySelector('wasla-reset-password');
const buyAnywhereElement = document.querySelector('wasla-buy-anywhere');
const browseHubElement = document.querySelector('wasla-browse-hub');
const browsePlatformElement = document.querySelector('wasla-browse-platform');
const browseProductElement = document.querySelector('wasla-browse-product');
const sheinShopElement = document.querySelector('wasla-shein-shop');
const myRequestsElement = document.querySelector('wasla-my-requests');
const profileElement = document.querySelector('wasla-profile');
const addressesElement = document.querySelector('wasla-addresses');
const favoritesElement = document.querySelector('wasla-favorites');
const orderTrackElement = document.querySelector('wasla-order-track');
const orderInvoiceElement = document.querySelector('wasla-order-invoice');
const compareElement = document.querySelector('wasla-compare');
const storeElement = document.querySelector('wasla-store');

if (homeElement) {
    createApp(WaslaHome).mount(homeElement);
}

if (adminElement) {
    createApp(AdminDashboard).mount(adminElement);
}

if (shopElement) {
    createApp(ShopPage).mount(shopElement);
}

if (productElement) {
    createApp(ProductPage, { productId: productElement.dataset.productId }).mount(productElement);
}

if (cartElement) {
    createApp(CartPage).mount(cartElement);
}

if (checkoutElement) {
    createApp(CheckoutPage).mount(checkoutElement);
}

if (orderConfirmationElement) {
    createApp(OrderConfirmationPage).mount(orderConfirmationElement);
}

if (loginElement) {
    createApp(LoginPage).mount(loginElement);
}

if (registerElement) {
    createApp(RegisterPage).mount(registerElement);
}

if (forgotPasswordElement) {
    createApp(ForgotPasswordPage).mount(forgotPasswordElement);
}

if (resetPasswordElement) {
    createApp(ResetPasswordPage).mount(resetPasswordElement);
}

if (buyAnywhereElement) {
    createApp(BuyFromAnywherePage).mount(buyAnywhereElement);
}

if (browseHubElement) {
    createApp(BrowseHubPage).mount(browseHubElement);
}

if (browsePlatformElement) {
    createApp(BrowsePlatformPage, {
        platform: browsePlatformElement.dataset.platform,
    }).mount(browsePlatformElement);
}

if (browseProductElement) {
    createApp(BrowseProductPage, {
        platform: browseProductElement.dataset.platform,
        productId: browseProductElement.dataset.productId,
    }).mount(browseProductElement);
}

if (sheinShopElement) {
    createApp(SheinShopPage).mount(sheinShopElement);
}

if (myRequestsElement) {
    createApp(MyRequestsPage).mount(myRequestsElement);
}

if (profileElement) {
    createApp(ProfilePage).mount(profileElement);
}

if (addressesElement) {
    createApp(AddressesPage).mount(addressesElement);
}

if (favoritesElement) {
    createApp(FavoritesPage).mount(favoritesElement);
}

if (orderTrackElement) {
    createApp(OrderTrackPage, {
        orderId: orderTrackElement.dataset.orderId,
    }).mount(orderTrackElement);
}

if (orderInvoiceElement) {
    createApp(OrderInvoicePage, {
        orderId: orderInvoiceElement.dataset.orderId,
    }).mount(orderInvoiceElement);
}

if (compareElement) {
    createApp(ComparePage).mount(compareElement);
}

if (storeElement) {
    createApp(StorePage, {
        storeId: storeElement.dataset.storeId,
    }).mount(storeElement);
}
