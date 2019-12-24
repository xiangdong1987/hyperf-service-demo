<?php
/**
 * Created by PhpStorm.
 * User: xiangdongdong
 * Date: 2019/12/13
 * Time: 11:05
 */

namespace App\Service;

use Hyperf\RpcClient\AbstractServiceClient;



class WorldServiceConsumer extends AbstractServiceClient
{
    /**
     * 定义对应服务提供者的服务名称
     * @var string
     */
    protected $serviceName = 'WorldService';

    /**
     * 定义对应服务提供者的服务协议
     * @var string
     */
    protected $protocol = 'jsonrpc-http';

    public function world(): string
    {
        return $this->__request(__FUNCTION__, []);
    }

    public function circuitBreaker(): string
    {
        return $this->__request(__FUNCTION__, []);
    }

    public function circuitBreakerFallback(): string
    {
        return "circuit is success!";
    }

}
