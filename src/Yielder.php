<?php

namespace Razshare\ReactPhp\Yielder;

use Closure;
use Generator;
use React\EventLoop\Loop;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Yielder {

	/**
	 * This method handles which objects should be "awaited" and which objects should be sent
	 * to the original <b>Generator</b> as a result.<br/>
	 * <hr/>
	 * This will resolve <b>Generators</b> ( and <b>PromiseInterfaces</b> and <b>Closures</b> )
	 * within the given Generator recursively.
	 * @param mixed $result the object that we're trying to wait for.
	 * Sometimes it's a Generator itself, sometimes it's a PromiseInterface,
	 * sometimes times it's a Closure and other times it's just an unknown value,
	 * meaning we've reached a "return" statement.
	 * @param Generator $value the original Generator.
	 * @param Closure $r
	 */
	private static function next(
		mixed     $result,
		Generator $value,
		Closure   $r
	): void {
		if ($result instanceof PromiseInterface) {
			static::await($value, $result, $r);
		} else if ($result instanceof Generator) {
			static::await($value, static::toPromise($result), $r);
		} else if ($result instanceof Closure) {
			$result = $result();
			static::next($result, $value, $r);
		} else {
			/**
			 * When this part is reached, it means the generator finished executing.
			 * At this point $result will contain the value of the return statement
			 * or null if there's no return.
			 */
			$value->send($result);
			static::execute($value, $r);
		}
	}

	/**
	 * @param Generator $value
	 * @param PromiseInterface $promise
	 * @param Closure $r
	 */
	private static function await(Generator $value, PromiseInterface $promise, Closure $r): void {
		$promise->then(
			fn($result) => Loop::futureTick(    //resolve
				fn() => static::next(
					$result, $value, $r
				)
			),
			fn($result) => Loop::futureTick(    //reject
				fn() => static::next(
					$result, $value, $r
				)
			)
		);
	}

	/**
	 * This is the actual logic that will execute the Generator until it ends and complete the Promise.
	 * @param Generator $value the generator to resolve.
	 * @param Closure $r this is the callback that will resolve the promise.
	 */
	private static function execute(Generator $value, Closure $r): void {
		Loop::futureTick(function () use (&$value, &$r) {
			if ($value->valid()) { //cycle all generators until end of callback is reached
				$result = $value->current();
				static::next($result, $value, $r);
			} else {  //end of callback is reached
				$return = $value->getReturn();
				$r($return); //final result here
			}
		});
	}

	/**
	 * Convert a \Generator to a ractphp PromiseInterface.
	 * @param Generator $value the generator to convert.
	 * @return Promise a promise that will be resolved whenever the generator is consumed by the reactphp loop.
	 */
	public static function toPromise(Generator $value): PromiseInterface {
		return new Promise(function ($r) use (&$value) {
			static::execute($value, $r);
		});
	}

	/**
	 * Run a callback inside the event loop.
	 * @param Generator|Closure $callback
	 * @param bool $runLoop
	 * @return Generator
	 */
	public static function run(Generator|Closure $callback, bool $runLoop = true): void {

		if ($callback instanceof Generator)
			Loop::futureTick(fn() => static::toPromise($callback));
		else
			Loop::futureTick(
				fn() => static::toPromise(
					( fn () => yield from [$callback()] )()
				)
			);


		if ($runLoop) Loop::run();
	}
}