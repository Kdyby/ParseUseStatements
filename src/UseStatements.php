<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Kdyby\ParseUseStatements;



/**
 * @author David Grudl <david@grudl.com>
 * @copyright David Grudl (https://davidgrudl.com)
 * @see https://github.com/nette/di/blob/ed1b90255688b08b87ae641f2bf1dfcba586ae5b/src/DI/PhpReflection.php
 */
class UseStatements
{

	/** @var array */
	private static $cache = [];



	/**
	 * Expands class name into full name.
	 *
	 * @param  string
	 * @return string  full name
	 */
	public static function expandClassName($name, \ReflectionClass $rc)
	{
		$lower = strtolower($name);
		if (empty($name)) {
			throw new \InvalidArgumentException('Class name must not be empty.');
		} elseif (self::isBuiltinType($lower)) {
			return $lower;
		} elseif ($lower === 'self' || $lower === 'static' || $lower === '$this') {
			return $rc->getName();
		} elseif ($name[0] === '\\') { // fully qualified name
			return ltrim($name, '\\');
		}
		$uses = self::getUseStatements($rc);
		$parts = explode('\\', $name, 2);
		if (isset($uses[$parts[0]])) {
			$parts[0] = $uses[$parts[0]];
			return implode('\\', $parts);
		} elseif ($rc->inNamespace()) {
			return $rc->getNamespaceName() . '\\' . $name;
		} else {
			return $name;
		}
	}



	/**
	 * @return array of [alias => class]
	 */
	public static function getUseStatements(\ReflectionClass $class)
	{
		if (!isset(self::$cache[$name = $class->getName()])) {
			if ($class->isInternal()) {
				self::$cache[$name] = [];
			} else {
				$code = file_get_contents($class->getFileName());
				self::$cache = self::parseUseStatements($code, $name) + self::$cache;
			}
		}
		return self::$cache[$name];
	}



	/**
	 * @param string $type
	 * @return bool
	 */
	public static function isBuiltinType($type)
	{
		return in_array(strtolower($type), ['string', 'int', 'float', 'bool', 'array', 'callable'], TRUE);
	}



	/**
	 * Parses PHP code.
	 *
	 * @param  string
	 * @return array of [class => [alias => class, ...]]
	 */
	public static function parseUseStatements($code, $forClass = NULL)
	{
		$tokens = token_get_all($code);
		$namespace = $class = $classLevel = $level = NULL;
		$res = $uses = [];

		while ($token = current($tokens)) {
            		next($tokens);
			switch (is_array($token) ? $token[0] : $token) {
				case T_NAMESPACE:
					$namespace = ltrim(self::fetch($tokens, [T_STRING, T_NS_SEPARATOR]) . '\\', '\\');
					$uses = [];
					break;

				case T_CLASS:
				case T_INTERFACE:
				case T_TRAIT:
					if ($name = self::fetch($tokens, T_STRING)) {
						$class = $namespace . $name;
						$classLevel = $level + 1;
						$res[$class] = $uses;
						if ($class === $forClass) {
							return $res;
						}
					}
					break;

				case T_USE:
					while (!$class && ($name = self::fetch($tokens, [T_STRING, T_NS_SEPARATOR]))) {
						$name = ltrim($name, '\\');
						if (self::fetch($tokens, '{')) {
							while ($suffix = self::fetch($tokens, [T_STRING, T_NS_SEPARATOR])) {
								if (self::fetch($tokens, T_AS)) {
									$uses[self::fetch($tokens, T_STRING)] = $name . $suffix;
								} else {
									$tmp = explode('\\', $suffix);
									$uses[end($tmp)] = $name . $suffix;
								}
								if (!self::fetch($tokens, ',')) {
									break;
								}
							}

						} elseif (self::fetch($tokens, T_AS)) {
							$uses[self::fetch($tokens, T_STRING)] = $name;

						} else {
							$tmp = explode('\\', $name);
							$uses[end($tmp)] = $name;
						}
						if (!self::fetch($tokens, ',')) {
							break;
						}
					}
					break;

				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
				case '{':
					$level++;
					break;

				case '}':
					if ($level === $classLevel) {
						$class = $classLevel = NULL;
					}
					$level--;
			}
		}

		return $res;
	}



	private static function fetch(& $tokens, $take)
	{
		$res = NULL;
		while ($token = current($tokens)) {
			list($token, $s) = is_array($token) ? $token : [$token, $token];
			if (in_array($token, (array) $take, TRUE)) {
				$res .= $s;
			} elseif (!in_array($token, [T_DOC_COMMENT, T_WHITESPACE, T_COMMENT], TRUE)) {
				break;
			}
			next($tokens);
		}
		return $res;
	}

}
