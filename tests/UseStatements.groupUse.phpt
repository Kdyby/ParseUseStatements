<?php

/**
 * @phpVersion 7
 */

use Kdyby\ParseUseStatements\UseStatements;
use Tester\Assert;

require_once __DIR__ . '/bootstrap.php';

require __DIR__ . '/files/groupUse.php';


Assert::same(
	['A' => 'A\B\A', 'C' => 'A\B\B\C', 'D' => 'A\B\C', 'E' => 'D\E'],
	UseStatements::getUseStatements(new ReflectionClass('GroupUseTest'))
);
