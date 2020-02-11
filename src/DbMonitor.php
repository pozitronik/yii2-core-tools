<?php
declare(strict_types = 1);

namespace pozitronik\core;

use Throwable;
use Yii;
use yii\base\Model;
use yii\db\Exception;

/**
 * Class DbMonitor
 * @package app\models\core
 * Прямой мониторинг и работа с базой.
 * Основная идея - видеть, кто и что крутит, с возможностью прибития
 */
class DbMonitor {

	/**
	 * @return ProcessListItem[]
	 * @throws Exception
	 */
	public function getProcessList():array {
		$result = [];
		$p_list = Yii::$app->db->createCommand('SHOW FULL PROCESSLIST')
			->queryAll();
		$debugInfo = new SqlDebugInfo();
		foreach ($p_list as $process) {
			$debugInfo->getFromSql($process['Info']);
			$result[] = new ProcessListItem([
				'id' => $process['Id'],
				'db' => $process['db'],
				'command' => $process['Command'],
				'time' => $process['Time'],
				'state' => $process['State'],
				'query' => $process['Info'],
				'user_id' => $debugInfo->userid,
				'operation' => $debugInfo->operation
			]);

		}
		return $result;
	}

	/**
	 * Die mf die
	 * @param int $id
	 * @return string;
	 */
	public function kill(int $id):string {
		try {
			$affected = Yii::$app->db->createCommand("kill {$id}")
				->execute();
		} catch (Throwable $t) {
			return "Error: {$t->getMessage()}";
		}
		return "Kill process {$id}: {$affected} row affected";
	}

}

/**
 * Class ProcessListItem
 * @package app\models\core
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