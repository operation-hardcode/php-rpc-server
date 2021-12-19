<?php

declare(strict_types=1);

use OperationHardcode\PhpRpcServer\InvokableRpcHandler;
use OperationHardcode\PhpRpcServer\RpcRequest;
use OperationHardcode\PhpRpcServer\RpcResponse;
use OperationHardcode\PhpRpcServer\RpcServer;

require_once __DIR__.'/../vendor/autoload.php';

final class MessageHandler
{
    public function __invoke(RpcRequest $request, ?RpcResponse $response): ?RpcResponse
    {
        return $response;
    }
}

$handler = new InvokableRpcHandler([
    'posts.add' => function (RpcRequest $request, RpcResponse $response): RpcResponse {
        return $response;
    },
    'messages.get' => new MessageHandler(),
]);

$handler->add('posts.get', function (RpcRequest $request, ?RpcResponse $response): ?RpcResponse {
    return $response;
});

$server = new RpcServer($handler);

$response = $server->process('[{"jsonrpc": "2.0", "method": "messages.get", "id": 1},{"jsonrpc": "2.0", "method": "messages.get1", "id": 2}]');

echo json_encode($response?->toArray(), JSON_UNESCAPED_SLASHES) . \PHP_EOL;
