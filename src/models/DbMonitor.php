<?php
declare(strict_types = 1);

namespace pozitronik\core\models;

use pozitronik\sys_exceptions\models\SysExceptions;
use Throwable;
use Yii;

/**
 * Class DbMonitor
 * Прямой мониторинг и работа с базой.
 * Основная идея - видеть, кто и что крутит, с возможностью прибития
 * DbMonitor.php
 * @deprecated since 1.2.0
 * use pozitronik/yii2-dbmon instead
 */
class DbMonitor {
	/**
	 * @return ProcessListItem[]
	 * @throws Throwable
	 */
	public static function ProcessList():array {
		$result = [];
		try {
			$pList = Yii::$app->db->createCommand('SHOW FULL PROCESSLIST')->queryAll();
		} catch (Throwable $t) {
			if (class_exists(SysExceptions::class)) {
				SysExceptions::log($t);
			} else {
				throw $t;
			}
			$pList = [];
		}
		$debugInfo = new SqlDebugInfo();
		foreach ($pList as $process) {
			$debugInfo->getFromSql($process['Info']);
			$result[] = new ProcessListItem([
				'id' => $process['Id'],
				'db' => $process['db'],
				'command' => $process['Command'],
				'time' => $process['Time'],
				'state' => $process['State'],
				'query' => $process['Info'],
				'user_id' => $debugInfo->user_id,
				'operation' => $debugInfo->operation
			]);
		}
		return $result;
	}

	/**
	 * @param int $process_id
	 * @return null|int Affected rows count, null on error
	 */
	public static function kill(int $process_id):?int {
		try {
			return Yii::$app->db->createCommand("kill {$process_id}")->execute();
		} /** @noinspection BadExceptionsProcessingInspection */ catch (Throwable $t) {
			return null;
		}
	}
}