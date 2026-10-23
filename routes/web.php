<?php

use App\Http\Controllers\Admin\PackingSlipController;
use App\Http\Controllers\Store\AbandonedCartController;
use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\Store\CatalogController;
use App\Http\Controllers\Store\CatalogFeedController;
use App\Http\Controllers\Store\FavoritesController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\NewsletterController;
use App\Http\Controllers\Store\OrderController;
use App\Http\Controllers\Store\OrderPaymentController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\RestockAlertController;
use App\Http\Controllers\Store\ReviewController;
use App\Http\Controllers\Store\ShippingQuoteController;
use App\Http\Controllers\Store\SitemapController;
use App\Filament\Resources\Products\ProductResource;
use App\Http\Middleware\RequireConfirmedTwoFactor;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response()->view('seo.robots')->header('Content-Type', 'text/plain; charset=UTF-8'))->name('robots');
Route::get('/admin/pedidos/{order}/hoja-empaque', PackingSlipController::class)
    ->middleware(['auth', 'verified', RequireConfirmedTwoFactor::class, 'can:orders.view'])
    ->where('order', 'AURA-[0-9]{4}-[0-9]{4}')
    ->name('admin.orders.packing-slip');
Route::get('/feeds/google.xml', [CatalogFeedController::class, 'google'])->name('feeds.google');
Route::get('/feeds/meta.csv', [CatalogFeedController::class, 'meta'])->name('feeds.meta');
Route::permanentRedirect('/catalogo.html', '/tienda')->name('catalog.legacy-redirect');
Route::get('/tienda', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/tienda/{category}', [CatalogController::class, 'index'])->name('catalog.category');
Route::get('/coleccion/{collection}', [CatalogController::class, 'collection'])->name('collections.show');
Route::get('/buscar', [CatalogController::class, 'search'])->name('search');
Route::get('/buscar/sugerencias', [CatalogController::class, 'suggestions'])->middleware('throttle:30,1')->name('search.suggestions');
Route::get('/pagina/{page}', [PageController::class, 'show'])->name('pages.show');
Route::get('/producto/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/pedido', [OrderController::class, 'create'])->name('orders.create');
Route::post('/pedido', [OrderController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
Route::post('/pedido/{order}/pago', [OrderPaymentController::class, 'checkout'])->middleware(['auth', 'verified'])->where('order', 'AURA-[0-9]{4}-[0-9]{4}')->name('orders.payment.checkout');
Route::post('/webhooks/pagos', [OrderPaymentController::class, 'webhook'])->name('payments.webhook');
Route::post('/cotizar-envio', ShippingQuoteController::class)->middleware('throttle:20,1')->name('shipping.quote');
Route::post('/bolsa/recordar', [AbandonedCartController::class, 'store'])->middleware('throttle:5,60')->name('abandoned-carts.store');
Route::get('/bolsa/recordar/{cart}/baja', [AbandonedCartController::class, 'unsubscribe'])->middleware('signed')->name('abandoned-carts.unsubscribe');
Route::post('/avisos-reabastecimiento', [RestockAlertController::class, 'store'])->middleware('throttle:5,1')->name('restock-alerts.store');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:3,1')->name('newsletter.store');
Route::get('/newsletter/confirmar/{subscriber}', [NewsletterController::class, 'confirm'])->middleware('signed')->name('newsletter.confirm');
Route::get('/newsletter/baja/{subscriber}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->name('newsletter.unsubscribe');
Route::get('/pedido/{order}', [OrderController::class, 'show'])->where('order', 'AURA-[0-9]{4}-[0-9]{4}')->name('orders.show');
Route::get('/favoritos', FavoritesController::class)->name('favorites.index');

Route::middleware(['auth', 'verified'])->prefix('cuenta')->name('account.')->group(function (): void {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::post('/direcciones', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/direcciones/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
    Route::post('/favoritos/{product}', [AccountController::class, 'addFavorite'])->name('favorites.store');
    Route::delete('/favoritos/{product}', [AccountController::class, 'removeFavorite'])->name('favorites.destroy');
    Route::post('/productos/{product}/resena', [ReviewController::class, 'store'])->name('reviews.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        if (ProductResource::canViewAny()) {
            return redirect()->to(ProductResource::getUrl('index', panel: 'admin'));
        }

        return app(AccountController::class)->index(request());
    })->name('dashboard');
});

require __DIR__.'/settings.php';
