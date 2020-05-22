<?php
declare(strict_types = 1);

namespace pozitronik\core\traits;

use pozitronik\helpers\DateHelper;
use yii\db\ActiveQuery;
use Yii;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRecord;
use yii\db\Command;
use yii\db\Connection;
use yii\db\ExpressionInterface;
use yii\db\QueryTrait;

/**
 * Обёртка над ActiveQuery с полезными и общеупотребительными функциями
 * http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#customizing-query-classes
 */
trait ActiveQueryExtended {
	use ActiveQueryTrait;
	use QueryTrait;

	/**
	 * Глобальная замена findWorkOnly
	 * Возвращает записи, не помеченные, как удалённые
	 * @param bool $deleted
	 * @return $this
	 */
	public function active(bool $deleted = false):self {
		/** @var ActiveRecord $class */
		$class = new $this->modelClass;//Хак для определения вызывающего трейт класса (для определения имени связанной таблицы)
		$tableName = $class::tableName();
		return $class->hasAttribute('deleted')?$this->andOnCondition([$tableName.'.deleted' => $deleted]):$this;
	}

	/**
	 * В некоторых поисковых моделях часто используется такое условие: если в POST передана дата, то искать все записи за неё, иначе игнорировать
	 * @param string|array $field
	 * @param string|null $value
	 * @param boolean $formatted_already - true: принять дату как уже форматированную в Y-m-d (для тех случаев, где Женька сделал так)
	 * @return ActiveQuery|self
	 * @throws Throwable
	 */
	public function andFilterDateBetween($field, ?string $value, bool $formatted_already = false):ActiveQuery {
		if (null === $value) return $this;

		$date = explode(' ', $value);
		$start = ArrayHelper::getValue($date, 0);
		$stop = ArrayHelper::getValue($date, 2);//$date[1] is delimiter

		if (DateHelper::isValidDate($start, $formatted_already?'Y-m-d':'d.m.Y') && DateHelper::isValidDate($stop, $formatted_already?'Y-m-d':'d.m.Y')) {/*Проверяем даты на валидность*/
			if (is_array($field)) {
				return $this->andFilterWhere([
					$field[0] => self::extractDate($start, $formatted_already),
					$field[1] => self::extractDate($stop, $formatted_already)
				]);
			}

			return $this->andFilterWhere([
				'between', $field, self::extractDate($start, $formatted_already).' 00:00:00',
				self::extractDate($stop, $formatted_already).' 23:59:00'
			]);
		}

		return $this;
	}

	/**
	 * @param string $date_string
	 * @param bool $formatted_already
	 * @return string
	 */
	private static function extractDate(string $date_string, bool $formatted_already):string {
		return $formatted_already?$date_string:date('Y-m-d', strtotime($date_string));
	}

	/**
	 * Держим долго считаемый count для запроса в кеше
	 * @param int $duration
	 * @return integer
	 */
	public function countFromCache(int $duration = DateHelper::SECONDS_IN_HOUR):int {
		$countQuery = clone $this;
		$countQuery->distinct()
			->limit(false)
			->offset(false);//нелимитированный запрос для использования его в качестве ключа
		return Yii::$app->cache->getOrSet($this->createCommand()->rawSql, static function() use ($countQuery) {
			return (int)$countQuery->count();
		}, $duration);
	}

	/**
	 * @param string $name
	 * @param bool $checkVars
	 * @param bool $checkBehaviors
	 * @return bool
	 * @see Model::hasProperty()
	 */
	abstract public function hasProperty(string $name, bool $checkVars = true, bool $checkBehaviors = true):bool;

	/**
	 * @param string|array $condition
	 * @param array $params
	 * @return self
	 * @see ActiveQuery::andOnCondition()
	 */
	abstract public function andOnCondition($condition, array $params = []):self;

	/**
	 * @param bool $value
	 * @return self
	 * @see ActiveQuery::distinct()
	 */
	abstract public function distinct(bool $value = true):self;

	/**
	 * @param Connection|null $db
	 * @return Command
	 * @see ActiveQuery::createCommand()
	 */
	abstract public function createCommand(?Connection $db = null):Command;

	/**
	 * @param string $q
	 * @param Connection|null $db
	 * @return int|string
	 * @see ActiveQuery::count()
	 */
	abstract public function count(string $q = '*', ?Connection $db = null);

	/**
	 * @param int|ExpressionInterface|null $limit
	 * @return self
	 * @see ActiveQuery::limit()
	 */
	abstract public function limit($limit):self;

	/**
	 * @param int|ExpressionInterface|null $offset
	 * @return self
	 * @see ActiveQuery::offset()
	 */
	abstract public function offset($offset):self;

}