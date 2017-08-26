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
use Illuminate\Support\Facades\File;

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

        $config = __DIR__ . '/../config/gateway.php';
        $migrations = __DIR__ . '/../migrations/';
        $views = __DIR__ . '/../views/';

        //php artisan vendor:publish --provider=NextPay\Gateway\NextPayServiceProvider --tag=config
        $this->publishes([
            $config => config_path('gateway.php'),
        ], 'config');

        // php artisan vendor:publish --provider=NextPay\Gateway\NextPayServiceProvider --tag=migrations
        $this->publishes([
            $migrations => base_path('database/migrations')
        ], 'migrations');



        if (
            File::glob(base_path('/database/migrations/create_gateway_transactions_table\.php'))
            && !File::exists(base_path('/database/migrations/alter_id_in_transactions_table.php'))
        ) {
            @File::copy($migrations.'/alter_id_in_transactions_table.php',base_path('database/migrations/alter_id_in_transactions_table.php'));
            @File::copy($migrations.'/create_gateway_transactions_table.php',base_path('database/migrations/create_gateway_transactions_table.php'));
        }


        $this->loadViewsFrom($views, 'gateway');

        // php artisan vendor:publish --provider=NextPay\Gateway\GatewayServiceProvider --tag=views
        $this->publishes([
            $views => base_path('resources/views/vendor/gateway'),
        ], 'views');

        //$this->mergeConfigFrom( $config,'gateway')


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
