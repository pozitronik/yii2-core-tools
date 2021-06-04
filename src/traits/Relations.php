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
		return self::getLinks(self::getFirstAttributeName(), $master);
	}

	/**
	 * Находит и возвращает существующую связь от базовой модели
	 * @param ActiveRecord|int|string $slave
	 * @return self[]
	 * @throws Throwable
	 */
	public static function currentBackLink($slave):array {
		return self::getLinks(self::getSecondAttributeName(), $slave);
	}

	/**
	 * Возвращает все связи к базовой модели
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @return self[]
	 * @throws Throwable
	 */
	public static function currentLinks($master):array {
		return self::currentLink($master);
	}

	/**
	 * Возвращает все связи от базовой модели
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $slave
	 * @return self[]
	 * @throws Throwable
	 */
	public static function currentBackLinks($slave):array {
		return self::currentBackLink($slave);
	}

	/**
	 * Линкует в этом релейшене две модели. Модели могут быть заданы как через айдишники, так и моделью, и ещё тупо строкой
	 * @param ActiveRecord|int|string $master
	 * @param ActiveRecord|int|string $slave
	 * @throws Throwable
	 */
	public static function linkModel($master, $slave):void {
		if (empty($master) || empty($slave)) return;
		/*Пришёл запрос на связывание ActiveRecord-модели, ещё не имеющей primary key*/
		if (is_subclass_of($master, ActiveRecord::class, false) && $master->isNewRecord) {
			$master->on(ActiveRecord::EVENT_AFTER_INSERT, function($event) {//отложим связывание после сохранения
				self::linkModel($event->data[0], $event->data[1]);
			}, [$master, $slave]);
			return;
		}

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
	 * @param bool $backLink Если связь задана в "обратную сторону", т.е. основная модель присоединяется к вторичной.
	 * @throws Throwable
	 * @noinspection NotOptimalIfConditionsInspection
	 */
	public static function linkModels($master, $slave, bool $backLink = false):void {
		if (($backLink && empty($slave)) || (!$backLink && empty($master))) return;
		/*Удалим разницу (она может быть полной при очистке)*/
		self::dropDiffered($master, $slave, $backLink);

		if (empty($slave)) return;
		if (is_array($master)) {
			foreach ($master as $master_item) {
				if (is_array($slave)) {
					foreach ($slave as $slave_item) {
						self::linkModel($master_item, $slave_item);
					}
				} else self::linkModel($master_item, $slave);
			}
		} elseif (is_array($slave)) {
			foreach ($slave as $slave_item) {
				self::linkModel($master, $slave_item);
			}
		} else self::linkModel($master, $slave);
	}

	/**
	 * Вычисляет разницу между текущими и задаваемыми связями, удаляя те элементы, которые есть в текущей связи, но отсутствуют в устанавливаемой
	 * @param $master
	 * @param $slave
	 * @param bool $backLink Если связь задана в "обратную сторону", т.е. основная модель присоединяется к вторичной.
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection TypeUnsafeArraySearchInspection
	 */
	private static function dropDiffered($master, $slave, bool $backLink = false):void {
		if ($backLink) {
			$currentItems = self::currentBackLinks($slave);
			$masterItemsKeys = self::extractKeysValues($master);
			$first_name = self::getFirstAttributeName();
			foreach ($currentItems as $item) {//все
				$unlinks = [];
				if (!in_array($item->$first_name, $masterItemsKeys)) {
					$unlinks[] = $item->$first_name;
				}
				if ([] !== $unlinks) {
					$item::unlinkModel($unlinks, $slave);
				}
			}

		} else {
			$currentItems = self::currentLinks($master);
			$slaveItemsKeys = self::extractKeysValues($slave);
			$second_name = self::getSecondAttributeName();
			foreach ($currentItems as $item) {//все
				$unlinks = [];
				if (!in_array($item->$second_name, $slaveItemsKeys)) {
					$unlinks[] = $item->$second_name;
				}
				if ([] !== $unlinks) {
					$item::unlinkModel($master, $unlinks);
				}
			}
		}

	}

	/**
	 * Удаляет единичную связь в этом релейшене
	 * @param mixed $master
	 * @param mixed $slave
	 * @throws Throwable
	 */
	public static function unlinkModel($master, $slave):void {
		if (empty($master) || empty($slave)) return;

		$attr1 = self::getFirstAttributeName();
		$attr2 = self::getSecondAttributeName();
		if (is_array($master) && is_array($slave)) {
			$where = ['OR'];
			foreach ($master as $masterItem) {
				$where[] = [$attr1 => self::extractKeyValue($masterItem), $attr2 => self::extractKeysValues($slave)];
			}
		} else {
			$where = [$attr1 => self::extractKeysValues($master), $attr2 => self::extractKeysValues($slave)];
		}

		static::deleteAll($where);
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
	 * @noinspection NotOptimalIfConditionsInspection
	 */
	public static function unlinkModels($master, $slave):void {
		self::unlinkModel($master, $slave);
	}

	/**
	 * Удаляет все связи от модели в этом релейшене
	 * @param int|int[]|string|string[]|ActiveRecord|ActiveRecord[] $master
	 * @throws Throwable
	 */
	public static function clearLinks($master):void {
		if (empty($master)) return;

		static::deleteAll([self::getFirstAttributeName() => self::extractKeysValues($master)]);
	}

	/**
	 * @param string $attribute
	 * @param mixed $item
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private static function getLinks(string $attribute, $item):array {
		return empty($item)?[]:static::findAll([$attribute => self::extractKeysValues($item)]);
	}

	/**
	 * @param mixed $item
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private static function extractKeysValues($item):array {
		return is_array($item)?array_map('self::extractKeyValue', $item):[self::extractKeyValue($item)];
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