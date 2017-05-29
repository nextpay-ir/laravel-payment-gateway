<?php
/**
 * Created by NextPay co.
 * Website: Nextpay.ir
 * Email: info@nextpay.ir
 * User: nextpay
 * Date: 5/15/17
 * Time: 1:40 PM
 */
namespace NextPay\Gateway;

use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

abstract class PortAbstract
{
	/**
	 * Transaction id
	 *
	 * @var null|int
	 */
	protected $transactionId = null;

    /**
	 * Id
	 *
	 * @var null|int
	 */
	protected $Id = null;

    protected  $db = null;

	/**
	 * Transaction row in database
	 */
	protected $transaction = null;

	/**
	 * Customer card number
	 *
	 * @var string
	 */
	protected $cardNumber = '';

	/**
	 * @var
	 */
	protected $config;

	/**
	 * Port id
	 *
	 * @var int
	 */
	protected $GatewayName;

	/**
	 * Reference id
	 *
	 * @var string
	 */
	protected $OrderId;

	/**
	 * Amount in Toman
	 *
	 * @var int
	 */
	protected $amount;

	/**
	 * callback URL
	 *
	 * @var String
	 */
	protected $callbackUrl;

	/**
	 * Initialize of class
	 *
	 * @param Config $config
	 * @param DataBaseManager $db
	 * @param int $port
	 */
	function __construct()
	{
		$this->db = app('DB');
	}

	/** bootstraper */
	function boot(){

	}

	function setConfig($config)
	{
		$this->config = $config;
	}

	/**
	 * @return mixed
	 */
	function getTable()
	{
		//return $this->db->table('nextpay_gateway_transactions');//config('gateway.table')
        return DB::table('nextpay_gateway_transactions');
	}

	/**
	 * Get Gateway id, $this->GatewayName
	 *
	 * @return int
	 */
	function getGatewayName()
	{
		return $this->GatewayName;
	}

	/**
	 * Set Gateway id, $this->GatewayName
     * @param String
	 *
	 */
	function setGatewayName($name)
	{
		$this->GatewayName = $name;
	}

	/**
	 * Return card number
	 *
	 * @return string
	 */
	function cardNumber()
	{
		return $this->cardNumber;
	}


	/**
	 * Get transaction id
	 *
	 * @return int|null
	 */
	function getTransactionId()
	{
		return $this->transactionId;
	}

	/**
	 * Return order id
	 */
	function getOrderId()
	{
		return $this->OrderId;
	}

	/**
	 * Sets amount
	 * @return int
	 */
	function getAmount()
	{
		return $this->amount;
	}

	/**
	 * Gets amount
	 * @param int
	 */
	function SetAmount($amount)
	{
		$this->amount = $amount;
	}

	/**
	 * Return result of payment
	 * If result is done, return true, otherwise throws an related exception
	 *
	 * This method must be implements in child class
	 *
	 * @param object $transaction row of transaction in database
	 *
	 * @return $this
	 */
	function verify($transaction)
	{
		$this->transaction = $transaction;
		$this->transactionId = $transaction->id;
		$this->amount = intval($transaction->price);
		$this->refId = $transaction->ref_id;
	}

	function getTimeId()
	{
		$genuid = function(){
			return substr(str_pad(str_replace('.','', microtime(true)),12,0),0,12);
		};
		$uid=$genuid();
		while ($this->getTable()->whereId($uid)->first())
			$uid = $genuid();
		return $uid;
	}

	/**
	 * Insert new transaction to poolport_transactions table
	 *
     * @param string
	 * @return int last inserted id
	 */
	protected function newTransaction($trans_id="00000000-0000-0000-0000-000000000000")
	{
		//$uid = $this->getTimeId();
		$this->Id = $this->getTable()->insertGetId([
			'trans_id' => $trans_id,
			'gateway' => $this->getGatewayName(),
			'price' => $this->amount,
			'status' => ConstGateway::TRANSACTION_PENDING,
			'ip' => Request::getClientIp(),
			'payment_date' => Carbon::now(),
			'id_commodity' => '1',
		]);
        $this->OrderId = $this->Id;
		return $this->Id;
	}

	/**
	 * Commit transaction
	 * Set status field to success status
	 *
	 * @return bool
	 */
	protected function transactionSucceed()
	{
		return $this->getTable()->whereId($this->OrderId)->update([
			'status' => ConstGateway::TRANSACTION_SUCCEED,
			'card_number' => $this->cardNumber,
		]);
	}

    /**
     * Failed transaction
     * Set status field to error status
     *
     * @param mixed
     * @return bool
     */
    protected function transactionFailed($code=ConstGateway::TRANSACTION_PENDING)
    {
        return $this->getTable()->whereId($this->OrderId)->update([
            'status' => $code,
        ]);
    }

	/**
	 * Commit transaction
	 * Set trans id
	 *
     * @param string
	 * @return bool
	 */
	protected function setTransactionId($trans_id)
	{
	    var_dump($trans_id);
	    var_dump($this->OrderId);
		return $this->getTable()->whereId($this->OrderId)->update([
			'trans_id' => $trans_id,
		]);
	}

    /**
     * Commit transaction
     * Set trans id
     *
     * @param string $trans_id
     * @param null|int $order_id
     * @return bool
     */
	protected function getTransaction($trans_id,$order_id = NULL)
	{
	    if ($order_id == NULL)
	        $order_id = $this->OrderId;
		return $this->getTable()->where('id', $order_id)
                                ->orWhere('trans_id', $trans_id)
                                ->first();
	}

	/**
	 * Add query string to a url
	 *
	 * @param string $url
	 * @param array $query
	 * @return string
	 */
	protected function makeCallback($url, array $query)
	{
		return $this->url_modify(array_merge($query, ['_token' => csrf_token()]), url($url));
	}

	/**
	 * manipulate the Current/Given URL with the given parameters
	 * @param $changes
	 * @param  $url
	 * @return string
	 */
	protected function url_modify($changes, $url)
	{
		// Parse the url into pieces
		$url_array = parse_url($url);

		// The original URL had a query string, modify it.
		if (!empty($url_array['query'])) {
			parse_str($url_array['query'], $query_array);
			$query_array = array_merge($query_array, $changes);
		} // The original URL didn't have a query string, add it.
		else {
			$query_array = $changes;
		}

		return (!empty($url_array['scheme']) ? $url_array['scheme'] . '://' : null) .
		(!empty($url_array['host']) ? $url_array['host'] : null) .
		$url_array['path'] . '?' . http_build_query($query_array);
	}
}
