<?php
/**
 * Created by PhpStorm.
 * User: xiangdongdong
 * Date: 2019/12/12
 * Time: 16:11
 */

namespace App\Service;

use Hyperf\RpcServer\Annotation\RpcService;

/**
 * 注意，如希望通过服务中心来管理服务，需在注解内增加 publishTo 属性
 * @RpcService(name="WorldService", protocol="jsonrpc-http", server="jsonrpc-http", publishTo="consul")
 */
class WorldService
{
    // 实现一个加法方法，这里简单的认为参数都是 int 类型
    public function world(): string
    {
        // 这里是服务方法的具体实现
        return "world";
    }

    public function circuitBreaker(): string
    {
        sleep(1);
        return "我熔断了";
    }
}
