<?php
declare(strict_types = 1);

namespace pozitronik\core;

use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * Class SqlDebugInfo
 * @package app\models\core
 */
class SqlDebugInfo extends Model {
	public $operation;
	public $userid;

	/**
	 * @return string
	 */
	public function __toString() {
		return "/*debug=>".json_encode([
				'operation' => $this->operation,
				'userid' => $this->userid
			])."<=debug*/";
	}

	/**
	 * extract debug information from sql if exists
	 * @param string $sql
	 * @return boolean
	 */
	public function getFromSql(string $sql):bool {
		$this->userid = null;
		$this->operation = null;
		$matches = [];
		$r = preg_match('//\*debug=>(.*?)<=debug\*//', $sql, $matches);
		if ($r) {
			$this->setAttributes(json_decode($matches[1], true), false);
			return true;
		}
		return false;
	}

	/**
	 * adds debug information to any AQ
	 * @param ActiveQuery $query
	 */
	public function addDebugInfo(ActiveQuery $query):void {
		$query->andWhere("1 = 1{$this}");
	}
}