<?php
declare(strict_types = 1);

namespace pozitronik\core\models;

use pozitronik\helpers\ArrayHelper;
use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * Class SqlDebugInfo
 * @property null|string $operation Описание отслеживаемого процесса
 * @property null|int $user_id ID пользователя, вызвавшего процесс
 */
class SqlDebugInfo extends Model {
	public $operation;
	public $user_id;

	/**
	 * @return string
	 */
	public function __toString() {
		return '/*debug=>'.json_encode([
				'operation' => $this->operation,
				'user_id' => $this->user_id
			]).'<=debug*/';
	}

	/**
	 * extract debug information from sql if exists
	 * @param null|string $sql
	 * @return bool
	 */
	public function getFromSql(?string $sql):bool {
		$this->user_id = null;
		$this->operation = null;
		if (null === $sql) return false;
		$matches = [];
		$r = preg_match('/\/\*debug=>(.*?)<=debug\*\//', $sql, $matches);
		if (false !== $r) {
			$this->setAttributes(json_decode(ArrayHelper::getValue($matches, '1', '{}'), true), false);
			return true;
		}

		return false;
	}

	public static function addDebugInfo(ActiveQuery $query, ?string $operation = null, ?int $user_id = null):ActiveQuery {
		$sqlDebugInfo = new self([
			'operation' => $operation,
			'user_id' => $user_id
		]);
		return $query->andWhere("1 = 1{$sqlDebugInfo}");
	}

}
