<?php
declare(strict_types = 1);

namespace pozitronik\core\traits;

use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Trait Relations
 * Функции, общеприменимые ко всем таблицам связей.
 * @package app\ActiveRecords\relations
 */
trait Relations {

	/**
	 * Преобразует переданный параметр к единому виду
	 * @param int|string|ActiveRecord $storage
	 * @return int|string
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	private static function extractKeyValue($storage) {
		if (is_numeric($storage)) return (int)$storage;
		if (is_object($storage)) return ArrayHelper::getValue($storage, 'primaryKey', new Exception("Класс {$storage->formName()} не имеет атрибута primaryKey"));
		return (string)$storage; //suppose it string field name
	}

	/**
	 * Находит и возвращает существующую связь к базовой модели
	 * @param ActiveRecord|int|string $master
	 * @return self[]
	 * @throws Throwable
	 */
	public static function currentLink($master):array {
		if (empty($master)) return [];

		/** @var ActiveRecord $link */
		$link = new self();

		$first_name = ArrayHelper::getValue($link->rules(), '0.0.0', new Exception('Не удалось получить атрибут для связи'));
		$masterValue = self::extractKeyValue($master);

		return static::findAll([$first_name => $masterValue]);
	}

	/**
	 * Возвращает все связи к базовой модели
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @return self[]
	 */
	public static function currentLinks($master) {
		$links = [[]];
		if (is_array($master)) {
			foreach ($master as $master_item) {
				$links[] = self::currentLink($master_item);
			}
		} else $links[] = self::currentLink($master);

		return array_merge(...$links);
	}

	/**
	 * Линкует в этом релейшене две модели. Модели могут быть заданы как через айдишники, так и моделью, и ещё тупо строкой
	 * @param ActiveRecord|int|string $master
	 * @param ActiveRecord|int|string $slave
	 * @throws Throwable
	 */
	public static function linkModel($master, $slave):void {
		if (empty($master) || empty($slave)) return;

		/** @var ActiveRecord $link */
		$link = new self();

		$first_name = ArrayHelper::getValue($link->rules(), '0.0.0', new Exception('Не удалось получить атрибут для связи'));
		$second_name = ArrayHelper::getValue($link->rules(), '0.0.1', new Exception('Не удалось получить атрибут для связи'));

		$link->$first_name = self::extractKeyValue($master);
		$link->$second_name = self::extractKeyValue($slave);

		$link->save();//save or update, whatever
	}

	/**
	 * Линкует в этом релейшене две модели. Модели могут быть заданы как через айдишники, так и напрямую, в виде массивов или так.
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $slave
	 * @param bool $relink связи будут установлены заново
	 * @throws Throwable
	 */
	public static function linkModels($master, $slave, bool $relink = false):void {
		if (empty($master)) return;
		if ($relink) self::clearLinks($master);
		if (empty($slave)) return;
		if (is_array($master)) {
			foreach ($master as $master_item) {
				if (is_array($slave)) {
					foreach ($slave as $slave_item) {
						self::linkModel($master_item, $slave_item);
					}
				} else self::linkModel($master_item, $slave);
			}
		} else if (is_array($slave)) {
			foreach ($slave as $slave_item) {
				self::linkModel($master, $slave_item);
			}
		} else self::linkModel($master, $slave);
	}

	/**
	 * Удаляет единичную связь в этом релейшене
	 * @param ActiveRecord|int|string $master
	 * @param ActiveRecord|int|string $slave
	 * @throws Throwable
	 */
	public static function unlinkModel($master, $slave):void {
		if (empty($master) || empty($slave)) return;
		/** @var ActiveRecord $link */
		$link = new self();
		$first_name = ArrayHelper::getValue($link->rules(), '0.0.0', new Exception('Не удалось получить атрибут для связи'));
		$second_name = ArrayHelper::getValue($link->rules(), '0.0.1', new Exception('Не удалось получить атрибут для связи'));

		$masterValue = self::extractKeyValue($master);
		$slaveValue = self::extractKeyValue($slave);

		if (null !== $model = static::findOne([$first_name => $masterValue, $second_name => $slaveValue])) {
			/** @var ActiveRecord $model */
			$model->delete();
		}
	}

	/**
	 * Удаляет связь между моделями в этом релейшене
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $slave
	 * @throws Throwable
	 *
	 * Функция не будет работать с объектами, не имеющими атрибута/ключа id (даже если в качестве primaryKey указан другой атрибут).
	 * Такое поведение оставлено специально во избежание ошибок проектирования
	 * @see Privileges::setDropUserRights
	 *
	 * Передавать массивы строк/идентификаторов нельзя (только массив моделей)
	 */
	public static function unlinkModels($master, $slave):void {
		if (empty($master) || empty($slave)) return;
		if (is_array($master)) {
			foreach ($master as $master_item) {
				if (is_array($slave)) {
					foreach ($slave as $slave_item) {
						self::unlinkModel($master_item, $slave_item);
					}
				} else self::unlinkModel($master_item, $slave);
			}
		} else if (is_array($slave)) {
			foreach ($slave as $slave_item) {
				self::unlinkModel($master, $slave_item);
			}
		} else self::unlinkModel($master, $slave);
	}

	/**
	 * Удаляет все связи от модели в этом релейшене
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @throws Throwable
	 */
	public static function clearLinks($master):void {
		if (empty($master)) return;
		/** @var ActiveRecord $link */
		$link = new self();
		$first_name = ArrayHelper::getValue($link->rules(), '0.0.0', new Exception('Не удалось получить атрибут для связи'));

		if (is_array($master)) {
			foreach ($master as $item) self::clearLinks($item);
		}

		$masterValue = self::extractKeyValue($master);

		if (null !== $model = static::findOne([$first_name => $masterValue])) {
			/** @var ActiveRecord $model */
			$model->delete();
		}
	}

	/**
	 * @param mixed $condition
	 * @return static|null
	 * @see ActiveRecord::findOne()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	abstract public static function findOne($condition);

	/**
	 * @param mixed $condition
	 * @return static[]
	 * @see ActiveRecord::findAll()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	abstract public static function findAll($condition);
}