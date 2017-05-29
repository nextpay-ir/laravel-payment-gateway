<?php

namespace NextPay\Gateway\Exceptions;
/**
 * This exception when throws, user try to submit a payment request who submitted before
 */
class BankException extends \Exception
{
	protected $code=-1;
	protected $message = 'حالت پیش فرض و در انتظار واریز';
}
