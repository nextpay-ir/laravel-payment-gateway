<?php
/**
 * Created by NextPay co.
 * Website: Nextpay.ir
 * Email: info@nextpay.ir
 * User: nextpay
 * Date: 5/15/17
 * Time: 1:20 PM
 */

Route::get('nextpay/request',
    'NextpayPayment\Gateway\NextpayPaymentController@request');
Route::post('nextpay/callback',
    'NextpayPayment\Gateway\NextpayPaymentController@callback');