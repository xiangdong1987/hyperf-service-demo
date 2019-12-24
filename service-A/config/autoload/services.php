<?php
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
