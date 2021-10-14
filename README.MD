This library provides a fluent api to work with promisses and generators inside the [ReactPhp](https://reactphp.org/) event loop.

```php
\Razshare\ReactPhp\Yielder\Yielder::run(function(){
    $result = yield new \React\Promise\Promise(function($r){
        $r("hello world");        
    });
    
    echo $result.PHP_EOL;
});
```

This is just a piece of code that simply prints "hello world" from a promise.

It might not seem like much of a difference, but when you take into 
consideration the number of async operations you're required to run in order
to keep your application running smoothly, this simple syntax improvement can
become a refreshing and more convenient way to handle promises.


## Getting Started
```bash
composer require razshare/react-php-yielder
```
