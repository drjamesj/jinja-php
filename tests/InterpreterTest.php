<?php

declare(strict_types=1);

use Codewithkyrian\Jinja\Core\Environment;
use Codewithkyrian\Jinja\Core\Interpreter;
use Codewithkyrian\Jinja\Core\Lexer;
use Codewithkyrian\Jinja\Core\Parser;

beforeEach(function () {
    $this->env = new Environment();
    $this->env->set('True', true);
});

test('should handle whitespace control', function ($template, $data, $lstripBlocks, $trimBlocks, $target) {
    foreach ($data as $key => $value) {
        $this->env->set($key, $value);
    }

    $tokens = Lexer::tokenize($template, $lstripBlocks, $trimBlocks);
    $parsed = Parser::make($tokens)->parse();

    $interpreter = new Interpreter($this->env);

    $result = $interpreter->run($parsed);

    expect($result->value)->toEqual($target);
})->with('interpreterTestData');

// Laravel (and other frameworks) turn PHP warnings into exceptions, so a
// missing attribute must not even warn: it renders as empty, as in Jinja2.
test('missing attributes render as empty without warnings', function ($data) {
    set_error_handler(function (int $errno, string $errstr) {
        throw new ErrorException($errstr, 0, $errno);
    });

    try {
        foreach ($data as $key => $value) {
            $this->env->set($key, $value);
        }

        $tokens = Lexer::tokenize("[{{ x.foo }}]{% if x.foo %}yes{% else %}no{% endif %}");
        $result = (new Interpreter($this->env))->run(Parser::make($tokens)->parse());

        expect($result->value)->toEqual('[]no');
    } finally {
        restore_error_handler();
    }
})->with([
    'unset variable' => [[]],
    'null' => [['x' => null]],
    'number' => [['x' => 5]],
    'boolean' => [['x' => true]],
    'list' => [['x' => [1, 2]]],
    'string' => [['x' => 'abc']],
    'object' => [['x' => ['bar' => 1]]],
]);
