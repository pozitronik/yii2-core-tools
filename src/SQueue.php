<?php
declare(strict_types = 1);

namespace pozitronik\core;

use Yii;
use yii\base\Model;

/**
 * Class SQueue
 * Простая очередь сообщений, работающая через кеш (тупо для того, чтобы не завязываться на отдельную реализацию механик, которые уже есть в кеше).
 * Через это реализованы пользовательские уведомления (переделано с Flash, не совсем подходящего для такой задачи).
 * Если кеш отключён, работать, естественно, не будет.
 * @package app\models\core
 */
class SQueue extends Model {
	public static $queue_identifier = 'SQueue_';

	/**
	 * Добавляет сообщение в очередь для пользователя $user_id
	 * @param int $user_id
	 * @param mixed $message
	 */
	public static function push(int $user_id, $message):void {
		$current_messages = self::get($user_id);
		$current_messages[] = $message;
		Yii::$app->cache->set(self::$queue_identifier.$user_id, $current_messages);
	}

	/**
	 * Возвращает очередь сообщений для пользователя $user_id
	 * @param int $user_id
	 * @return array
	 */
	public static function get(?int $user_id):array {
		if (null === $user_id) return [];
		$messages = Yii::$app->cache->get(self::$queue_identifier.$user_id);
		if (false === $messages) return [];
		return $messages;
	}

	/**
	 * Очищает очередь сообщения для пользователя $user_id
	 * @param int $user_id
	 */
	public static function clear(int $user_id):void {
		Yii::$app->cache->delete(self::$queue_identifier.$user_id);
	}
}