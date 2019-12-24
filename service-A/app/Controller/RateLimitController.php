<?php

namespace App\Controller;

use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Etcd\KVInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Contract\ConfigInterface;

/**
 * @Controller(prefix="rate-limit")
 * @RateLimit(limitCallback={RateLimitController::class, "limitCallback"})
 */
class RateLimitController
{

    /**
     * @var \Hyperf\Contract\ConfigInterface
     */
    private $config;


    /**
     * @RequestMapping(path="test")
     * @RateLimit(create=1, capacity=1)
     */
    public function test()
    {
        return ["QPS 1, 峰值3"];
    }

    public static function limitCallback(float $seconds, ProceedingJoinPoint $proceedingJoinPoint)
    {
        // $seconds 下次生成Token 的间隔, 单位为秒
        // $proceedingJoinPoint 此次请求执行的切入点
        // 可以通过调用 `$proceedingJoinPoint->process()` 继续执行或者自行处理
        var_dump("Now is limit");
        return $proceedingJoinPoint->process();
    }

    /**
     * @RequestMapping(path="getConfig")
     */
    public function getConfig()
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $result = $config->get('etcd.test.test', "");
        return [$result];
    }

    /**
     * @RequestMapping(path="setConfig")
     */
    public function setConfig()
    {
        $config = ApplicationContext::getContainer()->get(KVInterface::class);
        $result = $config->put("/test/test", json_encode(['word' => 'hello world'],JSON_FORCE_OBJECT));
        return $result;
    }

}
