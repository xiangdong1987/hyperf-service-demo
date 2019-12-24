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

namespace App\Controller;

use App\Service\HelloService;
use App\Service\WorldService;
use Hyperf\Utils\ApplicationContext;
use Hyperf\CircuitBreaker\Annotation\CircuitBreaker;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Hyperf\Di\Annotation\Inject;

/**
 * @RateLimit(limitCallback={IndexController::class, "limitCallback"})
 */
class IndexController extends AbstractController
{
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

    public function hello()
    {
        $hello = ApplicationContext::getContainer()->get(HelloService::class);
        $world = ApplicationContext::getContainer()->get(WorldService::class);
        return [$hello->hello(), $world->world()];
    }

    /**
     * @CircuitBreaker(timeout=0.05, failCounter=1, successCounter=1, fallback="App\Service\WorldServiceConsumer::circuitBreakerFallback")
     */
    public function circuitBreaker()
    {
        $cb = ApplicationContext::getContainer()->get(WorldService::class);
        return [$cb->circuitBreaker()];
    }

}
