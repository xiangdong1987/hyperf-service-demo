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

use App\Service\WorldService;
use Hyperf\Utils\ApplicationContext;

class IndexController extends AbstractController
{
    /**
     * @var WorldService
     */
    private $helloService;

    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    public function hello()
    {
        $client = ApplicationContext::getContainer()->get(WorldService::class);
        return [$client->hello()];
    }
}
