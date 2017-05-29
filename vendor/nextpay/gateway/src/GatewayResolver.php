<?php

namespace NextPay\Gateway;

use NextPay\Gateway\NextPay;
use NextPay\Gateway\Exceptions\RetryException;
use NextPay\Gateway\Exceptions\PortNotFoundException;
use NextPay\Gateway\Exceptions\InvalidRequestException;
use NextPay\Gateway\Exceptions\NotFoundTransactionException;
use Illuminate\Support\Facades\DB;

class GatewayResolver
{

	protected $request;

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Keep current Gateway driver
	 *
	 * @var NextPay
	 */
	protected $gateway;

	/**
	 * Gateway constructor.
	 * @param null $config
	 * @param null $gateway
	 */
	public function __construct($config = null, $gateway = null)
	{
		$this->config = app('config');
		$this->request = app('request');

		if ($this->config->has('gateway.timezone'))
			date_default_timezone_set($this->config->get('gateway.timezone'));

		if (!is_null($gateway)) $this->make($gateway);
	}

	/**
	 * Get supported ports
	 *
	 * @return array
	 */
	public function getSupportedPorts()
	{
		return [ConstGateway::NEXTPAY];
	}

	/**
	 * Call methods of current driver
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{

		// calling by this way ( Gateway::mellat()->.. , Gateway::parsian()->.. )
		if(in_array(strtoupper($name),$this->getSupportedPorts())){
			return $this->make($name);
		}

		return call_user_func_array([$this->gateway, $name], $arguments);
	}

	/**
	 * Gets query builder from you transactions table
	 * @return mixed
	 */
	function getTable()
	{
		return DB::table($this->config->get('gateway.table'));
	}

	/**
	 * Callback
	 *
	 * @return $this->gateway
	 *
	 * @throws InvalidRequestException
	 * @throws NotFoundTransactionException
	 * @throws PortNotFoundException
	 * @throws RetryException
	 */
	public function verify()
	{
		if (!$this->request->has('transaction_id') && !$this->request->has('iN'))
			throw new InvalidRequestException;
		if ($this->request->has('transaction_id')) {
			$id = $this->request->get('transaction_id');
		}else {
			$id = $this->request->get('iN');
		}

		$transaction = $this->getTable()->whereId($id)->first();

		if (!$transaction)
			throw new NotFoundTransactionException;

		if (in_array($transaction->status, [ConstGateway::TRANSACTION_SUCCEED, ConstGateway::TRANSACTION_FAILED]))
			throw new RetryException;

		$this->make($transaction->port);

		return $this->gateway->verify($transaction);
	}


	/**
	 * Create new object from port class
	 *
	 * @param int $gateway
	 * @throws PortNotFoundException
	 */
	function make($gateway)
	{
		if ($gateway InstanceOf NextPay) {
			$name = ConstGateway::NEXTPAY;
		} elseif(in_array(strtoupper($gateway),$this->getSupportedPorts())){
			$gateway = ucfirst(strtolower($gateway));
			$name = strtoupper($gateway);
			$class = __NAMESPACE__.'\\'.$gateway.'\\'.$gateway;
			$gateway = new $class;
		} else
			throw new PortNotFoundException;

		$this->gateway = $gateway;
		$this->gateway->setConfig($this->config); // injects config
		$this->gateway->setPortName($name); // injects config
		$this->gateway->boot();

		return $this;
	}
}
