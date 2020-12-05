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
	 * @return string
	 * @throws Throwable
	 */
	private static function getFirstAttributeName():string {
		/** @var ActiveRecord $link */
		$link = new self();
		return ArrayHelper::getValue($link->rules(), '0.0.0', new Exception('Не удалось получить атрибут для связи'));
	}

	/**
	 * @return string
	 * @throws Throwable
	 */
	private static function getSecondAttributeName():string {
		/** @var ActiveRecord $link */
		$link = new self();
		return ArrayHelper::getValue($link->rules(), '0.0.1', new Exception('Не удалось получить атрибут для связи'));
	}

	/**
	 * Находит и возвращает существующую связь к базовой модели
	 * @param ActiveRecord|int|string $master
	 * @return self[]
	 * @throws Throwable
	 */
	public static function currentLink($master):array {
		if (empty($master)) return [];
		return static::findAll([self::getFirstAttributeName() => self::extractKeyValue($master)]);
	}

	/**
	 * Возвращает все связи к базовой модели
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @return self[]
	 * @throws Throwable
	 */
	public static function currentLinks($master):array {
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

		$first_name = self::getFirstAttributeName();
		$second_name = self::getSecondAttributeName();

		$link->$first_name = self::extractKeyValue($master);
		$link->$second_name = self::extractKeyValue($slave);

		$link->save();//save or update, whatever
	}

	/**
	 * Линкует в этом релейшене две модели. Модели могут быть заданы как через айдишники, так и напрямую, в виде массивов или так.
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $slave
	 * @throws Throwable
	 */
	public static function linkModels($master, $slave):void {
		if (empty($master)) return;
		/*Удалим разницу (она может быть полной при очистке)*/
		self::dropDiffered($master, $slave);

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
	 * Вычисляет разницу между текущими и задаваемыми связями, удаляя те элементы, которые есть в текущей связи, но отсутствуют в устанавливаемой
	 * @param $master
	 * @param $slave
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private static function dropDiffered($master, $slave):void {
		$currentItems = self::currentLinks($master);
		$slaveItemsKeys = [];
		$second_name = self::getSecondAttributeName();
		if (is_array($slave)) {//вычисляем ключи линкованных моделей
			foreach ($slave as $value) $slaveItemsKeys[] = self::extractKeyValue($value);
		} else {
			$slaveItemsKeys[] = self::extractKeyValue($slave);
		}
		foreach ($currentItems as $item) {//все
			if (!in_array($item->$second_name, $slaveItemsKeys)) {
				$item::unlinkModel($master, $item->$second_name);
			}
		}
	}

	/**
	 * Удаляет единичную связь в этом релейшене
	 * @param ActiveRecord|int|string $master
	 * @param ActiveRecord|int|string $slave
	 * @throws Throwable
	 */
	public static function unlinkModel($master, $slave):void {
		if (empty($master) || empty($slave)) return;

		if (null !== $model = static::findOne([self::getFirstAttributeName() => self::extractKeyValue($master), self::getSecondAttributeName() => self::extractKeyValue($slave)])) {
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

		if (is_array($master)) {
			foreach ($master as $item) self::clearLinks($item);
		}

		foreach (static::findAll([self::getFirstAttributeName() => self::extractKeyValue($master)]) as $model) {
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