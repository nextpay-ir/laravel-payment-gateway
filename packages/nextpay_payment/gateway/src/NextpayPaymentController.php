<?php
/**
 * Created by NextPay co.
 * Website: Nextpay.ir
 * Email: info@nextpay.ir
 * User: nextpay
 * Date: 5/15/17
 * Time: 1:14 PM
 */

namespace NextpayPayment\Gateway;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use NextPay\Gateway\NextPay;


class NextpayPaymentController extends Controller
{
    public function index($timezone = NULL)
    {
    }

    public function request()
    {
        $nextpay = new NextPay();
        $nextpay->setAmount(1200);
        $nextpay->token();
        $trans_id = $nextpay->getTransId();
        $request = $nextpay->getRequestURL();
        return view('nextpay::request', compact('trans_id', 'request'));
    }

    public function callback()
    {
        $nextpay = new NextPay();
        $trans_id = Input::get('trans_id');
        $order_id = Input::get('order_id');
        $nextpay->setTransId($trans_id);
        $nextpay->setOrderId($order_id);
        $trans = $nextpay->getTransaction($trans_id, $order_id);
        $nextpay->setAmount($trans->price);
        $nextpay->setApiKey(config('gateway.nextpay.api_key', 'None'));
        $status = $nextpay->verify_request();
        switch ($status) {
            case 0:
                $status = "موفق";
                break;
            case -1:
                $status = "در انتظار واریز";
                break;
            default:
                $status = "ناموفق";
                break;
        }
        $trans_id = $nextpay->getTransId();
        return view('nextpay::callback', compact('order_id', 'trans_id', 'status'));
    }

}