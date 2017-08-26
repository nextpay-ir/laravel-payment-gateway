<?php
/**
 * Created by NextPay co.
 * Website: Nextpay.ir
 * Email: info@nextpay.ir
 * User: nextpay
 * Date: 5/15/17
 * Time: 2:35 PM
 */

namespace NextpayPayment\Gateway;

use SoapClient;
use NextpayPayment\Gateway\GatewayInterface;
use NextpayPayment\Gateway\GatewayAbstract;
use NextpayPayment\Gateway\NextPayException;
use NextpayPayment\Gateway\ConstGateway;

class NextPay extends GatewayAbstract implements GatewayInterface
{
	/**
	 * Variable NextPay payment gateway	 *
	 * @var string
	 */
    public $api_key = "";
    public $order_id = "";
    public $amount = 0;
    public $trans_id = "";
    public $params = array();
    public $default_verify = Type_Verify::SoapClient;
    public $callback_uri = '';
    protected $server_soap = "https://api.nextpay.org/gateway/token.wsdl";
    //protected $server_soap = "https://api.nextpay.org/gateway/token?wsdl";
    protected $server_http = "https://api.nextpay.org/gateway/token.http";
    protected $request_http = "https://api.nextpay.org/gateway/payment";
    protected $request_verify_soap = "https://api.nextpay.org/gateway/verify.wsdl";
    //protected $request_verify_soap = "https://api.nextpay.org/gateway/verify?wsdl";
    protected $request_verify_http = "https://api.nextpay.org/gateway/verify.http";
    private $keys_for_verify = array("api_key","order_id","amount","callback_uri");
    private $keys_for_check = array("api_key","order_id","amount","trans_id");

    /**
     * Nextpay_Payment constructor.
     * @param array|bool $params
     * @param string|bool $api_key
     * @param string|bool $order_id
     * @param string|bool $url
     * @param int|bool $amount
     */
    public function __construct($params=false, $api_key=false, $order_id=false, $amount=false, $url=false)
    {
        $trust = true;
        $this->callback_uri = config('gateway.nextpay.callback_uri','None');
        $this->api_key = config('gateway.nextpay.apikey','None');
//        $api_key = app('config')->get('gateway.nextpay.apikey');
        $const_gateway = new ConstGateway();
        $this->setGatewayName($const_gateway::NEXTPAY);
        if(is_array($params))
        {
            foreach ($this->keys_for_verify as $key )
            {
                if(!array_key_exists($key,$params))
                {
                    $error = "<h2>آرایه ارسالی دارای مشکل میباشد.</h2>";
                    $error .= "<h4>نمونه مثال برای آرایه ارسالی.</h4>";
                    $error .= /** @lang text */
                        "<pre>                        
                        array(\"api_key\"=>\"شناسه api\",
                              \"order_id\"=>\"شماره فاکتور\",
                              \"amount\"=>\"مبلغ\",
                              \"callback_uri\"=>\"مسیر باگشت\")

                        </pre>";
                    $trust = false;
                    $this->show_error($error);
                    break;
                }
            }
            if($trust)
            {
                $this->params = $params;
                $this->api_key = $params['api_key'];
                $this->order_id = $params['order_id'];
                $this->amount = $params['amount'];
                $this->callback_uri = $params['callback_uri'];
            }
            else
            {
                $this->show_error("برای مقدارهی پارامتر ها باید بصورت آرایه اقدام نمایید");
                exit("End with Error!!!");
            }
        }
        else
        {
            if($api_key)
                $this->api_key = $api_key;

            if($order_id)
                $this->order_id = $order_id;

            if($amount)
                $this->amount = $amount;

            if($url)
                $this->callback_uri = $url;

            $this->params = array(
                "api_key"=>$this->api_key,
                "order_id"=>$this->order_id,
                "amount"=>$this->amount,
                "callback_uri"=>$this->callback_uri);
        }
    }

    /**
     * @return Object
     * @throws NextPayException
     * @throws \Exception
     * @throws \SoapFault
     */
    public function token()
    {
        $response = "";
        switch ($this->default_verify)
        {
            case Type_Verify::SoapClient:
                try
                {
                    $this->setOrderId($this->newTransaction());
                    $soap_client = new SoapClient($this->server_soap);
                    $response = $soap_client->TokenGenerator($this->params);
                    $response = $response->TokenGeneratorResult;

                    if ($response != "" && $response != NULL && is_object($response)) {
                        $code = intval($response->code);
                        if ($code == -1) {
                            $this->trans_id = $response->trans_id;
                            $this->setTransactionId($this->trans_id);
                            $this->transactionId = $this->trans_id;
                        } else {
                            $this->transactionFailed($code);
                            throw new NextPayException($code);
                        }
                    } else {
                        $this->transactionFailed();
                        throw new NextPayException("خطا در پاسخ دهی به درخواست با SoapClinet");
                    }
                }
                catch(\SoapFault $e){
                    $this->transactionFailed();
                    throw $e;
                }
                break;
            case Type_Verify::NuSoap:
                try
                {
                    include_once ("include/nusoap/nusoap.php");

                    $client = new nusoap_client($this->server_soap,'wsdl');

                    $error = $client->getError();

                    if ($error)
                        $this->show_error($error);

                    $res = $client->call('TokenGenerator',array($this->params));

                    if ($client->fault)
                    {
                        echo "<h2>Fault</h2><pre>";
                        print_r ($res);
                        echo "</pre>";
                        exit(0);
                    }
                    else
                    {
                        $error = $client->getError();

                        if ($error)
                            $this->show_error($error);

                        $res = $res['TokenGeneratorResult'];

                        if ($res != "" && $res != NULL && is_array($res)) {
                            if (intval($res['code']) == -1) {
                                $this->trans_id = $res['trans_id'];
                                $res = (object)$res;
                            }/*else
                                $this->code_error($res['code']);*/
                        }
                        else
                            $this->show_error("خطا در پاسخ دهی به درخواست با NuSoap_Client");
                    }
                }
                catch(Exception $e){
                    $this->show_error($e->getMessage());
                }
                break;
            case Type_Verify::Http:
                try
                {
                    if( !$this->cURLcheckBasicFunctions() ) $this->show_error("UNAVAILABLE: cURL Basic Functions");
                    $this->newTransaction();
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $this->server_http);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_POSTFIELDS,
                        "api_key=".$this->api_key."&order_id=".$this->order_id."&amount=".$this->amount."&callback_uri=".$this->callback_uri);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    /** @var int | string $server_output */
                    $response = json_decode(curl_exec ($curl));
                    curl_close ($curl);

                    if ($response != "" && $response != NULL && is_object($response)) {
                        $code = intval($response->code);
                        if (intval($response->code) == -1){
                            $this->trans_id = $response->trans_id;
                            $this->transactionSetRefId();
                        } else {
                            $this->transactionFailed($code);
                            throw new NextPayException("انجام تراکنش با خطا مواجه شده است. شماره خطا : " . $code);
                        }
                    }
                }
                catch (\Exception $e){
                    throw $e;
                }
                break;
            default:
                try
                {
                    $this->newTransaction();
                    $soap_client = new SoapClient($this->server_soap);
                    $response = $soap_client->TokenGenerator($this->params);
                    $response = $response->TokenGeneratorResult;

                    if ($response != "" && $response != NULL && is_object($response)) {
                        $code = intval($response->code);
                        if ($code == -1) {
                            $this->trans_id = $response->trans_id;
                            $this->setTransactionId($this->trans_id);
                            $this->transactionId = $this->trans_id;
                        } else {
                            $this->transactionFailed($code);
                            throw new NextPayException("انجام تراکنش با خطا مواجه شده است. شماره خطا : " . $code);
                        }
                    } else {
                        $this->transactionFailed();
                        throw new NextPayException("خطا در پاسخ دهی به درخواست با SoapClinet");
                    }
                }
                catch(\SoapFault $e){
                    $this->transactionFailed();
                    throw $e;
                }
                break;
        }
        return $response;
    }

    /**
     * @return object
     */
    public function send()
    {
        if(isset($trans_id)) {
            $trans = $trans_id;
        } elseif (isset($this->trans_id)) {
            $trans = $this->trans_id;
        } else {
            $this->show_error("empty trans_id param send");
            return;
        }

        $url_payment = $this->request_http;
        return view('gateway::nextpay-redirector')->with(compact('trans','url_payment'));
    }

    /**
     * @param array|bool $params
     * @param string|bool $api_key
     * @param string|bool $trans_id
     * @param int|bool $amount
     * @param string|bool $order_id
     * @return int|mixed
     */
    public function verify_request($params=false, $api_key=false, $order_id=false, $trans_id=false, $amount=false)
    {
        $code = -1;
        $trust = true;
        if(is_array($params))
        {
            foreach ($this->keys_for_check as $key )
            {
                if(!array_key_exists($key,$params))
                {
                    $error = "<h2>آرایه ارسالی دارای مشکل میباشد.</h2>";
                    $error .= "<h4>نمونه مثال برای آرایه ارسالی.</h4>";
                    $error .= /** @lang text */
                        "<pre>
                            array(\"api_key\"=>\"شناسه api\",
                                  \"order_id\"=>\"شماره فاکتور\",
                                  \"amount\"=>\"مبلغ\",
                                  \"trans_id\"=>\"شماره تراکنش\")

                        </pre>";
                    $trust = false;
                    $this->show_error($error);
                    break;
                }
            }
            if($trust)
            {
                $this->trans_id = $params['trans_id'];
                $this->api_key = $params['api_key'];
                $this->order_id = $params['order_id'];
                $this->amount = $params['amount'];
            }
            else
            {
                $this->show_error("برای مقدارهی پارامتر ها باید بصورت آرایه اقدام نمایید");
                exit("End with Error!!!");
            }
        }

        if($api_key){
            $this->api_key = $api_key;
            $this->params['api_key'] = $api_key;
        }elseif (isset($this->api_key)) {
            $this->params['api_key'] = $this->api_key;
        }

        if($order_id){
            $this->order_id = $order_id;
            $this->params['order_id'] = $order_id;
        }elseif (isset($this->order_id)){
            $this->params['order_id'] = $this->order_id;
        }

        if($amount){
            $this->amount = $amount;
            $this->params['amount'] = $amount;
        }elseif (isset($this->amount)){
            $this->params['amount'] = $this->amount;
        }

        if($trans_id){
            $this->trans_id = $trans_id;
            $this->params['trans_id'] = $trans_id;
        }elseif (isset($this->trans_id)){
            $this->params['trans_id'] = $this->trans_id;
        }


        switch ($this->default_verify)
        {
            case Type_Verify::SoapClient:
                try
                {
                    $soap_client = new SoapClient($this->request_verify_soap);
                    $response = $soap_client->PaymentVerification($this->params);
                    $response = $response->PaymentVerificationResult;

                    if ($response != "" && $response != NULL && is_object($response)) {
                        $code = intval($response->code);
                        if ($code == 0) {
                            $this->transactionSucceed();
                        } else {
                            $this->transactionFailed($code);
                            //throw new NextPayException("انجام تراکنش با خطا مواجه شده است. شماره خطا : " . $code);
                        }
                    } else {
                        $this->transactionFailed();
                        throw new NextPayException("خطا در پاسخ دهی به درخواست با SoapClinet");
                    }
                }
                catch (\SoapFault $e) {
                    $this->transactionFailed();
                    throw $e;
                }
                break;
            case Type_Verify::NuSoap:
                try
                {
                    include_once ("include/nusoap/nusoap.php");

                    $client = new nusoap_client($this->server_soap,'wsdl');

                    $error = $client->getError();

                    if ($error)
                        $this->show_error($error);

                    $response = $client->call('PaymentVerification',array($this->params));

                    if ($client->fault)
                    {
                        echo "<h2>Fault</h2><pre>";
                        print_r ($response);
                        echo "</pre>";
                        exit(0);
                    }
                    else
                    {
                        $error = $client->getError();

                        if ($error)
                            $this->show_error($error);

                        $response = $response['PaymentVerificationResult'];

                        if ($response != "" && $response != NULL && is_array($response)) {
                            $code = $response['code'];
                        }
                        else
                            $this->show_error("خطا در پاسخ دهی به درخواست با NuSoap_Client");
                    }
                }
                catch(Exception $e){
                    $this->show_error($e->getMessage());
                }
                break;
            case Type_Verify::Http:
                try
                {
                    if( !$this->cURLcheckBasicFunctions() ) $this->show_error("UNAVAILABLE: cURL Basic Functions");
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $this->request_verify_http);
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_POSTFIELDS,
                        "api_key=".$this->api_key."&order_id=".$this->order_id."&amount=".$this->amount."&trans_id=".$this->trans_id);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    /** @var int | string $server_output */
                    $response = json_decode(curl_exec ($curl));
                    curl_close ($curl);

                    if ($response != "" && $response != NULL && is_object($response)) {
                        $code = intval($response->code);
                        if ($code == 0) {
                            $this->transactionSucceed();
                        } else {
                            $this->transactionFailed($code);
                            throw new NextPayException("انجام تراکنش با خطا مواجه شده است. شماره خطا : " . $code);
                        }
                    }
                    else
                        throw new NextPayException("خطا در پاسخ دهی به درخواست با Curl");
                }
                catch (Exception $e){
                    $this->transactionFailed();
                    throw $e;
                }
                break;
            default:
                try
                {
                    $soap_client = new SoapClient($this->request_verify_soap);
                    $response = $soap_client->PaymentVerification($this->params);
                    $response = $response->PaymentVerificationResult;

                    if ($response != "" && $response != NULL && is_object($response)) {
                        $code = intval($response->code);
                        if ($code == 0) {
                            $this->transactionSucceed();
                        } else {
                            $this->transactionFailed($code);
                            //throw new NextPayException("انجام تراکنش با خطا مواجه شده است. شماره خطا : " . $code);
                        }
                    } else {
                        $this->transactionFailed();
                        throw new NextPayException("خطا در پاسخ دهی به درخواست با SoapClinet");
                    }
                }
                catch (\SoapFault $e) {
                    $this->transactionFailed();
                    throw $e;
                }
                break;
        }
        return $code;
    }

    /**
     * @param string | string $error
     */
    public function show_error($error)
    {
        echo "<h1>وقوع خطا !!!</h1>";
        echo "<h4>{$error}</h4>";
    }

    /**
     * @param int | string $error_code
     * @return object | String
     */
    public function code_error($error_code)
    {
        $error_code = intval($error_code);
        $error_array = array(
            0 => "Complete Transaction",
            -1 => "Default State",
            -2 => "Bank Failed or Canceled",
            -3 => "Bank Payment Pending",
            -4 => "Bank Canceled",
            -20 => "api key is not send",
            -21 => "empty trans_id param send",
            -22 => "amount in not send",
            -23 => "callback in not send",
            -24 => "amount incorrect",
            -25 => "trans_id resend and not allow to payment",
            -26 => "Token not send",
            -30 => "amount less of limit payment",
            -32 => "callback error",
            -33 => "api_key incorrect",
            -34 => "trans_id incorrect",
            -35 => "type of api_key incorrect",
            -36 => "order_id not send",
            -37 => "transaction not found",
            -38 => "token not found",
            -39 => "api_key not found",
            -40 => "api_key is blocked",
            -41 => "params from bank invalid",
            -42 => "payment system problem",
            -43 => "gateway not found",
            -44 => "response bank invalid",
            -45 => "payment system deactivated",
            -46 => "request incorrect",
            -48 => "commission rate not detect",
            -49 => "trans repeated",
            -50 => "account not found",
            -51 => "user not found"
        );

        if (array_key_exists($error_code, $error_array)) {
            return $error_array[$error_code];
        } else {
            return "error code : $error_code";
        }
    }

    /**
     * @return bool
     */
    public function cURLcheckBasicFunctions()
    {
        if( !function_exists("curl_init") &&
            !function_exists("curl_setopt") &&
            !function_exists("curl_exec") &&
            !function_exists("curl_close") ) return false;
        else return true;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return string
     */
    public function getCallbackUri()
    {
        return $this->callback_uri;
    }

    /**
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getRequestURL()
    {
        return $this->request_http;
    }

    /**
     * @param string $trans_id
     * @param int|null $order_id
     * @return string
     */
    public function getTransaction($trans_id,$order_id = NULL)
    {
        return parent::getTransaction($trans_id,$order_id);
    }

    /**
     * @param int|int $amount
     * @return Nextpay
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        $this->params['amount'] = $this->amount;
        return $this;
    }

    /**
     * @param bool|string $api_key
     * @return Nextpay
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
        $this->params['api_key'] = $this->api_key;
        return $this;
    }

    /**
     * @param bool|string $order_id
     * @return Nextpay
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
        $this->OrderId = $order_id;
        $this->params['order_id'] = $this->order_id;
        return $this;
    }

    /**
     * @param string $trans_id
     * @return Nextpay
     */
    public function setTransId($trans_id)
    {
        $this->trans_id = $trans_id;
        $this->params['trans_id'] = $this->trans_id;
        return $this;
    }

    /**
     * @param string|string $callback_uri
     * @return Nextpay
     */
    public function setCallbackUri($callback_uri)
    {
        $this->callback_uri = $callback_uri;
        $this->params['callback_uri'] = $this->callback_uri;
        return $this;
    }

    /**
     * @param array|array $params
     * @return Nextpay
     */
    public function setParams($params)
    {
        $trust = true;
        if(is_array($params))
        {
            if (isset($this->keys_for_verify))
            {
                foreach ($this->keys_for_verify as $key )
                {
                    if(!array_key_exists($key,$params))
                    {
                        $trust = false;
                        $error = "<h2>آرایه ارسالی دارای مشکل میباشد.</h2>";
                        $error .= "<h4>نمونه مثال برای آرایه ارسالی.</h4>";
                        $error .= /** @lang text */
                            "<pre>
                                array(\"api_key\"=>\"شناسه api\",
                                      \"order_id\"=>\"شماره فاکتور\",
                                      \"amount\"=>\"مبلغ\",
                                      \"callback_uri\"=>\"مسیر باگشت\")
    
                            </pre>";
                        $this->show_error($error);
                        break;
                    }
                }
            }
            else
                $this->show_error("برای مقدارهی پارامتر ها باید بصورت آرایه اقدام نمایید");
            if($trust)
            {
                $this->params = $params;
                $this->api_key = $params['api_key'];
                $this->order_id = $params['order_id'];
                $this->amount = $params['amount'];
                $this->callback_uri = $params['callback_uri'];
            }
            else
                $this->show_error("برای مقدارهی پارامتر ها باید بصورت آرایه اقدام نمایید");

        }
        else
            $this->show_error("برای مقدارهی پارامتر ها باید بصورت آرایه اقدام نمایید");

        return $this;
    }

    /**
     * @param int $default_verify
     * @return Nextpay
     */
    public function setDefaultVerify($default_verify)
    {
        switch ($default_verify){
            case 0:
            case Type_Verify::NuSoap:
                $this->default_verify = Type_Verify::NuSoap;
                break;
            case 1:
            case Type_Verify::SoapClient:
                $this->default_verify = Type_Verify::SoapClient;
                break;
            case 2:
            case Type_Verify::Http:
                $this->default_verify = Type_Verify::Http;
                break;
            default:
                $this->default_verify = Type_Verify::SoapClient;
        }

        return $this;
    }
}

class Type_Verify
{
    const NuSoap = 0;
    const SoapClient = 1;
    const Http = 2;
}
