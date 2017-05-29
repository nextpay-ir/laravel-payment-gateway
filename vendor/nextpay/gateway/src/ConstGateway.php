<?php

namespace NextPay\Gateway;

class ConstGateway
{
    /**
     * NextPay Payment Gateway Const
     */
	const NEXTPAY = 'نکست پی';

	/**
	 * Status code for status field in  Gateway transactions table
	 */
	const TRANSACTION_PENDING = -1;
	const TRANSACTION_PENDING_TEXT = 'تراکنش ایجاد شد.';

    const TRANSACTION_SUCCEED = 0;
	const TRANSACTION_SUCCEED_TEXT = 'پرداخت با موفقیت انجام شد.';

    const TRANSACTION_FAILED = -2;
	const TRANSACTION_FAILED_TEXT = 'عملیات پرداخت با خطا مواجه شد.';

}
