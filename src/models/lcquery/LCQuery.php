<?php
declare(strict_types = 1);

namespace pozitronik\core\models\lcquery;

use pozitronik\helpers\DateHelper;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use Yii;
use pozitronik\helpers\ArrayHelper;
use Throwable;

/** @noinspection MissingActiveRecordInActiveQueryInspection */

/**
 * Обёртка над ActiveQuery с полезными и общеупотребительными функциями
 * http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#customizing-query-classes
 * Class LCQuery (назван так в честь LightCab, в котором такое решение впервые появилось)
 * @package app\models\LCQuery
 */
class LCQuery extends ActiveQuery {

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
	 * @return ActiveQuery
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
	 * Применяется для SysExceptions - там флагом known помечаются известные (малокритичные) ошибки
	 * @return $this
	 */
	public function unknown():self {
		return $this->andOnCondition(['known' => false]);
	}
}