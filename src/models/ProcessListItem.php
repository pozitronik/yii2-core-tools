<?php
declare(strict_types = 1);

namespace pozitronik\core\models;

use yii\base\Model;

/**
 * Class ProcessListItem
 * Элемент списка процессов + информация отладки
 * @property null|int $id
 * @property null|string $db
 * @property null|string $command
 * @property null|string $time
 * @property null|string $state
 * @property null|string $query
 * @property null|int $user_id
 * @property null|string $operation
 */
class ProcessListItem extends Model {
	public $id;
	public $db;
	public $command;
	public $time;
	public $state;
	public $query;
	public $user_id;
	public $operation;
}