## 构建微服务
* 安装composer 配置阿里镜像
```
php -r "copy('https://install.phpcomposer.com/installer', 'composer-setup.php');"

php composer-setup.php

mv composer.phar /usr/local/bin/composer

composer config -g repo.packagist composer https://mirrors.aliyun.com/composer 
```
* 安装hyperf
```
composer create-project hyperf/hyperf-skeleton
```

* Hello service服务
``` php
<?php
/** 
* Created by PhpStorm. 
* User: xiangdongdong 
* Date: 2019/12/12 
* Time: 16:11 
*/
namespace App\Service;use 
Hyperf\RpcServer\Annotation\RpcService;
/** 
* 注意，如希望通过服务中心来管理服务，需在注解内增加 publishTo 属性 
* @RpcService(name="HelloService", protocol="jsonrpc-http", 
server="jsonrpc-http", publishTo="consul") */
class HelloService{    
    // 实现一个加法方法，这里简单的认为参数都是 int 类型    
    public function hello(): string    {        
        // 这里是服务方法的具体实现        
        return "Hello";    
    }
}
```
* 配置说明
    * RpcService 服务类型
    * name 服务名称  用户服务发现和服务调用
    * protocol 服务写协议 定义服务协议
    * server 用于和autoconfig 中的server 绑定关系
    * publishTo 服务发现类型
* 服务提供方配置
```php
/**
* server 配置 autoload/server.php
*/
[    
    'name' => 'jsonrpc-http',    
    'type' => Server::SERVER_HTTP,    
    'host' => '0.0.0.0',    'port' => 9504,    
    'sock_type' => SWOOLE_SOCK_TCP,   
    'callbacks' => [ 
        SwooleEvent::ON_REQUEST => [\Hyperf\JsonRpc\HttpServer::class,'onRequest']
    ]
]
/**
* consul 配置 autoload/consul.php
*/
return [   
    'uri' => 'http://172.19.0.8:8500',
];
```
* WorldService 同上服务端口不一致即可
* 消费方配置
    * consul 保持一致
    * 消费方需要引入提供方的servic接口或者自定义消费接口
    ```php
        <?php
        /** * Created by PhpStorm. 
        * User: xiangdongdong 
        * Date: 2019/12/13 * Time: 11:05 
        */
        namespace App\Service;
        use Hyperf\RpcClient\AbstractServiceClient;
        class WorldServiceConsumer extends AbstractServiceClient{    
            /**     
            * 定义对应服务提供者的服务名称     
            * @var string     
            */    
            protected $serviceName = 'WorldService';    
            /**     * 定义对应服务提供者的服务协议     
            * @var string     
            */   
            protected $protocol = 'jsonrpc-http';    
            public function world(): string    {       
                return $this->__request(__FUNCTION__, []);    
            }
        }
    ```
    * 自定义接口需要绑定依赖注入类
    ```php
    /**
    * 依赖配置 配置 autoload/dependencies.php
    */
    return [    
        \App\Service\WorldService::class => App\Service\WorldServiceConsumer::class,
    ];
    ```
    * service 依赖配置
    ```php
    return [
        'consumers' => [
            [
                // name 需与服务提供者的 name 属性相同
                'name' => 'HelloService',
                // 服务提供者的服务协议，可选，默认值为 jsonrpc-http
                'protocol' => 'jsonrpc-http',
                // 负载均衡算法，可选，默认值为 random
                'load_balancer' => 'random',
                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => [
                    'protocol' => 'consul',
                    'address' => 'http://172.19.0.8:8500',
                ],
                // 如果没有指定上面的 registry 配置，即为直接对指定的节点进行消费，通过下面的 nodes 参数来配置服务提供者的节点信息
                'nodes' => [
                    ['host' => '127.0.0.1', 'port' => 9504],
                ],
            ],
            [
                // name 需与服务提供者的 name 属性相同
                'name' => 'WorldService',
                // 服务提供者的服务协议，可选，默认值为 jsonrpc-http
                'protocol' => 'jsonrpc-http',
                // 负载均衡算法，可选，默认值为 random
                'load_balancer' => 'random',
                // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
                'registry' => [
                    'protocol' => 'consul',
                    'address' => 'http://172.19.0.8:8500',
                ]
            ]
        ],
    ];
    ```
* 控制器中使用
```php
    public function hello()
    {
        $hello = ApplicationContext::getContainer()->get(HelloService::class);
        $world = ApplicationContext::getContainer()->get(WorldService::class);
        return [$hello->hello(), $world->world()];
    }
```
## 服务发现
* 按照上面的步骤就完成了多服务系统并且已经实现，依赖了consul 的服务发现和管理，通过consul 方便的管理服务，比如观察服务的状态，或者下线服务
* 观察服务可以使用consul 提供的后台
    * http://localhost:8500/ui/dc1/services/WorldService
* 切断某个服务可以提供的api
    * http://localhost:8500/v1/agent/service/deregister/HelloService-1
## 熔断
* 为了防止服务中某些服务产生的阻塞，引起雪崩效应，需要对服务进行熔断处理
```php
composer require hyperf/circuit-breaker
//控制器中配置熔断器
/** 
* @CircuitBreaker(timeout=0.05, failCounter=1, successCounter=1, 
fallback="App\Service\WorldServiceConsumer::circuitBreakerFallback") 
*/
public function circuitBreaker(){    
    $cb = ApplicationContext::getContainer()->get(WorldService::class);    
    return [$cb->circuitBreaker()];
}

//熔断回调函数
public function circuitBreaker(): string
{
    return $this->__request(__FUNCTION__, []);
}

public function circuitBreakerFallback(): string
{
    return "circuit is success!";
}
```
## 限流
* 为了防止流量太大打垮服务器，我们可以采用限流的方式，将一部分流量挡住，再hyperf 中使用限流器进行限制
```php
<?php

namespace App\Controller;

use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\RateLimit\Annotation\RateLimit;

/**
 * @Controller(prefix="rate-limit")
 * @RateLimit(limitCallback={RateLimitController::class, "limitCallback"})
 */
class RateLimitController
{
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
}
```
> 限流器的令牌桶是用redis实现的所以需要redis支持
## 配置中心
* 配置中心的个人偏向于etcd方式实现，搭建和使用较为简单没有额外负担docker搭建本地环境
```php
//etcd 客户端
composer require hyperf/etcd
//etcd http 客户端
composer require start-point/etcd-php
//etcd 配置中心代码 （主要是配置变更监听和更新逻辑，通过实践监听实现）
composer require hyperf/config-etcd
```
* etcd 配置  etcd.php
```php

<?phpreturn [
    'uri' => 'http://192.168.1.200:2379',
    'version' => 'v3beta',
    'options' => [
        'timeout' => 10,
    ],];
```
* etcd 中心配置  config_etcd.php
```php
<?php
return [
    'enable' => true,
    'namespaces' => [
        '/test',
    ],
    'mapping' => [
        '/test/test' => 'etcd.test.test',
    ],
    'interval' => 5,
];

```
> 主要注意几个问题，配置文件都在autoload 文件夹内，只有在配置中心map中配置的key并且enable是true才会同步，同时只用用composer引入的客户端存入ETC的配置才能被识别，因为底层是存储的json格式的value，如果直接通过etcdctl put 进入的是无法被识别的，会再解包的时候产生null注意。

## 调用链
* 一般使用场景较小，并且会影响性能，使用起来也比较麻烦，对于小的服务化是有点没那么有必要，如果人多或者项目较大，可以安排人力负责相关内容。
## 服务监控
* 相对于调用链，服务监控更为重要，系统的数据对一个项目来说是最重要的参考，方便的上报数据和查看数据是重要的需求。
```php
//引入组件
composer require hyperf/metric
//引入配置
php bin/hyperf.php vendor:publish hyperf/metric
```
* 配置 metric
```php
<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use Hyperf\Metric\Adapter\Prometheus\Constants;

return [
    'default' => env('METRIC_DRIVER', 'prometheus'),
    'use_standalone_process' => env('METRIC_USE_STANDALONE_PROCESS', true),
    'enable_default_metric' => env('METRIC_ENABLE_DEFAULT_METRIC', true),
    'default_metric_interval' => env('DEFAULT_METRIC_INTERVAL', 5),
    'metric' => [
        'prometheus' => [
            'driver' => Hyperf\Metric\Adapter\Prometheus\MetricFactory::class,
            'mode' => Constants::SCRAPE_MODE,
            'namespace' => env('APP_NAME', 'skeleton'),
            'scrape_host' => env('PROMETHEUS_SCRAPE_HOST', '0.0.0.0'),
            'scrape_port' => env('PROMETHEUS_SCRAPE_PORT', '9505'),
            'scrape_path' => env('PROMETHEUS_SCRAPE_PATH', '/metrics'),
            'push_host' => env('PROMETHEUS_PUSH_HOST', '0.0.0.0'),
            'push_port' => env('PROMETHEUS_PUSH_PORT', '9091'),
            'push_interval' => env('PROMETHEUS_PUSH_INTERVAL', 5),
        ],
    ],
];
```
* 配置中间件
```php
<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

return [
    'http' => [
        \Hyperf\Metric\Middleware\MetricMiddleware::class,
    ],
];

```
* 在控制器中使用，通过注入的方式来使用MetricFactoryInterface实现了三种基本统计类型
```php
    /**
     * @Inject
     * @var MetricFactoryInterface
     */
    private $metricFactory;

    public function index()
    {
        $count = $this->metricFactory->makeCounter('index_views');
        $count->add(1);
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
```
## 总结
    总体感觉hyperf实现服务化是完全可行，并且便利的。主要精力应该是三方开源的方面需要花一些精力，但是总体上还是蛮方便的。我的选择三方开源尽量是Go语言开发，因为Go也更接近于php，也方便与后期需要自定义功能时，也能hold的住，总体感觉下来还是觉得需要一点灵活性，不能被绑死，毕竟代码是死的人是活的，php最重要的就是灵活。总的下来也加深了自己对服务化的认识，方便自己更好的写业务。所有的环境全程使用docker，用了docker后只有两个字真香。
