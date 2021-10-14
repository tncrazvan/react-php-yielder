<?php

/**
 * In this example we want to get the contents of 3 web pages,
 * but each request must wait for the previous one to finish.
 */

namespace RazshareExamples;
chdir(dirname(__FILE__));
require_once '../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Razshare\ReactPhp\Yielder\Yielder;
use React\EventLoop\Loop;
use React\Http\Browser;
use RingCentral\Psr7\Response;

/**
 * Using PromiseInterface.
 */
Loop::futureTick(function () {
	$client = new Browser();
	$client->get('http://www.google.com/')->then(function (Response $response) use ($client) {
		echo "Promise - Google: ".sha1($response->getBody()) . PHP_EOL;

		$client->get('http://www.youtube.com/')->then(function (Response $response) use ($client) {
			echo "Promise - Youtube: ".sha1($response->getBody()) . PHP_EOL;

			$client->get('http://www.twitter.com/')->then(function (Response $response) use ($client) {
				echo "Promise - Twitter: ".sha1($response->getBody()) . PHP_EOL;
			});
		});
	});

});
Loop::run();


/**
 * Using Yielder and generators.
 */
Yielder::run(function () {
	$client = new Browser();

	/** @var ResponseInterface $response */
	$response = yield $client->get('http://www.google.com/');
	echo "Generator - Google: ".sha1($response->getBody()) . PHP_EOL;

	/** @var ResponseInterface $response */
	$response = yield $client->get('http://www.youtube.com/');
	echo "Generator - Youtube: ".sha1($response->getBody()) . PHP_EOL;

	/** @var ResponseInterface $response */
	$response = yield $client->get('http://www.twitter.com/');
	echo "Generator - Twitter: ".sha1($response->getBody()) . PHP_EOL;
});