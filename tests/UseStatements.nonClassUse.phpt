<?php

/**
 * @phpVersion 7
 */

use Kdyby\ParseUseStatements\UseStatements;
use Tester\Assert;

require_once __DIR__ . '/bootstrap.php';

require __DIR__ . '/files/nonClassUse.php';


Assert::same(
	[],
	UseStatements::getUseStatements(new ReflectionClass('NonClassUseTest'))
);
