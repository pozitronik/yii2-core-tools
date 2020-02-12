<?php
declare(strict_types = 1);

namespace pozitronik\core\interfaces\access;

/**
 * Class AccessMethods
 * @package app\models\user_rights
 */
abstract class AccessMethods {
	public const __default = self::any;

	public const any = null;
	public const view = 0;
	public const create = 1;
	public const update = 2;
	public const delete = 3;
	/*et cetera*/
//	const take_ownership = 300;
//	const prune = 100;

}