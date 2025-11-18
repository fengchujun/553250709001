<?php

namespace extend\exception;

use RuntimeException;
use Throwable;

/**
 * 订单退款错误异常处理类
 */
class RefundException extends RuntimeException
{
    public function __construct($message = "", $code = -1, Throwable $previous = null)
    {

        parent::__construct($message, $code, $previous);
    }
}
