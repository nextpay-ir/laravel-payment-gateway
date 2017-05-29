<?php
/**
 * Created by NextPay co.
 * Website: Nextpay.ir
 * Email: info@nextpay.ir
 * User: nextpay
 * Date: 5/15/17
 * Time: 3:54 PM
 */

namespace NextpayPayment\Gateway;

use Illuminate\Support\ServiceProvider;

class NextpayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'nextpay');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';
        $this->app->make('NextpayPayment\Gateway\NextpayPaymentController');

    }
}
