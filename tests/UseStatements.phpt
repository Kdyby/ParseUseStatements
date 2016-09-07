<?php

use Kdyby\ParseUseStatements\UseStatements;
use Tester\Assert;

require_once __DIR__ . '/bootstrap.php';

require __DIR__ . '/files/noNamespace.php';
require __DIR__ . '/files/bracketedNamespace.php';
require __DIR__ . '/files/inNamespace.php';
require __DIR__ . '/files/twoBlocks.php';
require __DIR__ . '/files/groupUse.php';
require __DIR__ . '/files/nonClassUse.php';


$rcNoNamespace = new \ReflectionClass('NoNamespace');
$rcBTest = new \ReflectionClass('BTest');
$rcFoo = new \ReflectionClass('Test\Space\Foo');
$rcBar = new \ReflectionClass('Test\Space\Bar');

Assert::exception(function () use ($rcNoNamespace) {
	UseStatements::expandClassName('', $rcNoNamespace);
}, 'InvalidArgumentException', 'Class name must not be empty.');

Assert::same('A', UseStatements::expandClassName('A', $rcNoNamespace));
Assert::same('A\B', UseStatements::expandClassName('C', $rcNoNamespace));
Assert::same('BTest', UseStatements::expandClassName('BTest', $rcBTest));
Assert::same('Test\Space\Foo', UseStatements::expandClassName('self', $rcFoo));
Assert::same('Test\Space\Foo', UseStatements::expandClassName('Self', $rcFoo));
Assert::same('Test\Space\Foo', UseStatements::expandClassName('static', $rcFoo));
Assert::same('Test\Space\Foo', UseStatements::expandClassName('$this', $rcFoo));

foreach (['String', 'string', 'int', 'float', 'bool', 'array', 'callable'] as $type) {
	Assert::same(strtolower($type), UseStatements::expandClassName($type, $rcFoo));
}

/*
alias to expand => [
	FQN for $rcFoo,
	FQN for $rcBar
]
*/
$cases = [
	'\Absolute' => [
		'Absolute',
		'Absolute',
	],
	'\Absolute\Foo' => [
		'Absolute\Foo',
		'Absolute\Foo',
	],
	'AAA' => [
		'Test\Space\AAA',
		'AAA',
	],
	'AAA\Foo' => [
		'Test\Space\AAA\Foo',
		'AAA\Foo',
	],
	'B' => [
		'Test\Space\B',
		'BBB',
	],
	'B\Foo' => [
		'Test\Space\B\Foo',
		'BBB\Foo',
	],
	'DDD' => [
		'Test\Space\DDD',
		'CCC\DDD',
	],
	'DDD\Foo' => [
		'Test\Space\DDD\Foo',
		'CCC\DDD\Foo',
	],
	'F' => [
		'Test\Space\F',
		'EEE\FFF',
	],
	'F\Foo' => [
		'Test\Space\F\Foo',
		'EEE\FFF\Foo',
	],
	'HHH' => [
		'Test\Space\HHH',
		'Test\Space\HHH',
	],
	'Notdef' => [
		'Test\Space\Notdef',
		'Test\Space\Notdef',
	],
	'Notdef\Foo' => [
		'Test\Space\Notdef\Foo',
		'Test\Space\Notdef\Foo',
	],
	// trim leading backslash
	'G' => [
		'Test\Space\G',
		'GGG',
	],
	'G\Foo' => [
		'Test\Space\G\Foo',
		'GGG\Foo',
	],
];

foreach ($cases as $alias => $fqn) {
	Assert::same($fqn[0], UseStatements::expandClassName($alias, $rcFoo));
	Assert::same($fqn[1], UseStatements::expandClassName($alias, $rcBar));
}

Assert::same(
	['C' => 'A\B'],
	UseStatements::getUseStatements(new ReflectionClass('NoNamespace'))
);
Assert::same(
	[],
	UseStatements::getUseStatements(new ReflectionClass('Test\Space\Foo'))
);
Assert::same(
	['AAA' => 'AAA', 'B' => 'BBB', 'DDD' => 'CCC\DDD', 'F' => 'EEE\FFF', 'G' => 'GGG'],
	UseStatements::getUseStatements(new ReflectionClass('Test\Space\Bar'))
);
Assert::same(
	[],
	UseStatements::getUseStatements(new ReflectionClass('stdClass'))
);

Assert::same(
	['A' => 'A\B\A', 'C' => 'A\B\B\C', 'D' => 'A\B\C', 'E' => 'D\E'],
	UseStatements::getUseStatements(new ReflectionClass('GroupUseTest'))
);

Assert::same(
	[],
	UseStatements::getUseStatements(new ReflectionClass('NonClassUseTest'))
);
