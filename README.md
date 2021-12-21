
![test](https://github.com/operation-hardcode/php-rpc-server/workflows/test/badge.svg?event=push)
[![Codecov](https://codecov.io/gh/operation-hardcode/php-rpc-server/branch/master/graph/badge.svg)](https://codecov.io/gh/operation-hardcode/php-rpc-server)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/operation-hardcode/php-rpc-server.svg?style=flat-square)](https://packagist.org/packages/operation-hardcode/php-rpc-server)
[![Quality Score](https://img.shields.io/scrutinizer/g/operation-hardcode/php-rpc-server.svg?style=flat-square)](https://scrutinizer-ci.com/g/operation-hardcode/php-rpc-server)

# JSON RPC Server implementation for PHP.

The json-rpc is a very simple protocol. You can see this by reading the [protocol specification](https://www.jsonrpc.org/specification).

This library implements json-rpc server specification. It is easy to use and well typed.

### Contents

- [Installation](#installation)
- [Usage](#usage)
- [Examples](#examples)
- [Testing](#testing)
- [Stat Analysis](#stat-analysis)
- [License](#license)

## Installation

```bash
composer require operation-hardcode/php-rpc-server
```

## Usage

Since the json-rpc server has only one endpoint, you need a handler that knows how to determine from the name of the method the handler that you will handle the request.
You can write it yourself, if the `OperationHardcode\PhpRpcServer\RpcHandler` interface is implemented. Or use the ready-made one that comes with the library, the `OperationHardcode\PhpRpcServer\InvokableRpcHandler`.

To start to use the rpc server, instantiate the `RpcServer`:

```php

use OperationHardcode\PhpRpcServer\RpcServer;
use OperationHardcode\PhpRpcServer\RpcRequest;
use OperationHardcode\PhpRpcServer\RpcResponse;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateRequest;

$server = RpcServer::new([
    'users.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
        return $response?->addResult([10, 11]);
    }
])

$server->process('{"jsonrpc": "2.0", "method": "users.get", "id": 1}');
```

The rpc-server does not fetch the json, it just processes it according to the specification. This gives you the freedom to use the server,
because you can pass the json fetched any way you know: via http, web sockets or even via tcp.

As you may have noticed, handlers may not have an RpcResponse object. If so, it is a notification, not a request, so no response to the client will follow.
You can simply return this object (because it is null) or null directly.

The rpc-server uses validators to validate json against the specification.
If you want to add your own validators, you must implement the `OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateRequest` interface. In the `validate` method you will get the raw payload to ensure it satisfy your extended protocol rules.

```php

use OperationHardcode\PhpRpcServer\RpcServer;
use OperationHardcode\PhpRpcServer\RpcRequest;
use OperationHardcode\PhpRpcServer\RpcResponse;
use OperationHardcode\PhpRpcServer\Protocol\Validation\ValidateRequest;
use Psr\Log\NullLogger;

final class CustomValidator implements ValidateRequest
{
    /** {@inheritdoc} */
    public function validate(array $payload): bool
    {
        return false;
    }
}

$server = RpcServer::new([
    'users.get' => function (RpcRequest $request, ?RpcResponse $response = null): ?RpcResponse {
        return $response?->addResult([10, 11]);
    }
], new NullLogger(), [new CustomValidator()])

$server->process('{"jsonrpc": "2.0", "method": "users.get", "id": 1}');
```

If your validator fails, the error `INVALID_REQUEST` will be returned to the client.

If you are not satisfied with the standard request handler, you can write your own.

```php

use OperationHardcode\PhpRpcServer\RpcHandler;
use OperationHardcode\PhpRpcServer\RpcRequest;
use OperationHardcode\PhpRpcServer\RpcResponse;
use OperationHardcode\PhpRpcServer\RpcServer;

final class YourOwnRpcHandler implements RpcHandler
{
    public function handle(RpcRequest $request, ?RpcResponse $response = null) : ?RpcResponse
    {
        //
    }
}

$server = new RpcServer(new YourOwnRpcHandler());

$server->process('{"jsonrpc": "2.0", "method": "users.get", "id": 1}');
```

## Examples

More extensive code examples reside in the [`examples`](examples) directory.

## Testing

``` bash
$ composer test
```  

## Stat Analysis

``` bash
$ composer lint
```  

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.