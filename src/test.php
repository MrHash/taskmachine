<?php

require 'vendor/autoload.php';

use Auryn\Injector;
use TaskFlux\CustomHandler;
use TaskFlux\Environment;
use TaskFlux\TaskFlux;
use Workflux\Param\InputInterface;

// Bootstrap
$injector = new Injector;
$environment = new Environment(['processed' => 'processed', 'cleanedup' => 'cleaned up', 'custom' => 'finished']);
$injector->share($environment);

// Define tasks
$tf = new TaskFlux($injector);

$tf->task('start', function() {
    echo 'started'.PHP_EOL;
    return ['incoming' => 'outgoing'];
});

$tf->task('process', function(InputInterface $input, Environment $env) {
    echo $input->get('incoming').PHP_EOL;
    echo $env->get('processed').PHP_EOL;
    return ['log' => 0];
});

$tf->task('cleanup', function(Environment $env) {
    echo $env->get('cleanedup').PHP_EOL;
});

$tf->task('logging', function() {
    echo 'logged'.PHP_EOL;
});

$tf->task('finish', CustomHandler::class);

$tf->machine('transcoder')
    ->task('start')
        ->initial()
        ->then('process')
    ->task('process')
        ->expects([ 'bool' => 'incoming' ])
        ->then('cleanup')
    ->task('cleanup')
        ->when('input.get("log")', 'logging')
        ->when('!input.get("log")', 'finish')
        ->continue()
    ->task('logging')->final()
    ->task('finish')->final();

$output = $tf->run('transcoder');
print_r($output->toArray(), true);