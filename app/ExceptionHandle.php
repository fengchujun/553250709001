<?php

namespace app;

use app\exception\ApiException;
use app\exception\BaseException;
use extend\exception\OrderException;
use extend\exception\StockException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\View;
use think\Response;
use think\template\exception\TemplateNotFoundException;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception) : void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     * @throws \Exception
     */
    public function render($request, Throwable $e) : Response
    {
        if ($e instanceof HttpException) {
            return view(app()->getRootPath() . 'public/error/error.html');
        } elseif ($e instanceof ApiException || $e instanceof OrderException) {
            //针对api异常处理
            $data = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'timestamp' => time()
            ];
            return Response::create($data, 'json', 200);
        } elseif ($e instanceof StockException) {
            //针对api异常处理
            $data = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'timestamp' => time()
            ];
            return Response::create($data, 'json', 200);
        } elseif (!env('app_debug') && ( $request->isPost() || $request->isAjax() ) && !( $e instanceof HttpResponseException )) {
            $data = [
                'code' => -1,
                'message' => "系统异常：" . $e->getMessage(),
                'timestamp' => time()
            ];
            return Response::create($data, 'json', 200);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
