<?php /** @noinspection UndetectableTableInspection */
declare(strict_types = 1);

namespace pozitronik\core;

use app\models\core\traits\ARExtended;
use app\modules\history\models\ActiveRecordHistory;
use app\modules\privileges\models\AccessMethods;
use app\modules\privileges\models\UserAccess;
use app\widgets\alert\AlertModel;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;

/**
 * Class ActiveRecordExtended
 * @package app\models\core
 */
class ActiveRecordExtended extends ActiveRecordHistory {
	use ARExtended;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		throw new InvalidConfigException('"'.static::class.'" нельзя вызывать напрямую.');
	}

	/**
	 * @return LCQuery
	 */
	public static function find():LCQuery {
		return new LCQuery(static::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeSave($insert):bool {
		if (!UserAccess::canAccess($this, $insert?AccessMethods::create:AccessMethods::update)) {
			$this->refresh();
			$this->addError('id', 'Вам не разрешено производить данное действие.');
			AlertModel::AccessNotify();
			return false;
		}

		return parent::beforeSave($insert);
	}

	/**
	 * @return bool
	 * @throws Throwable
	 */
	public function beforeDelete():bool {
		if (!UserAccess::canAccess($this, AccessMethods::delete)) {
			$this->addError('id', 'Вам не разрешено производить данное действие.');
			AlertModel::AccessNotify();
			return false;
		}
		return parent::beforeDelete();
	}

	/**
	 * Удаляет набор моделей по набору первичных ключей
	 * @param array $primaryKeys
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
	public static function deleteByKeys(array $primaryKeys):void {
		foreach ($primaryKeys as $primaryKey) {
			if (null !== $model = self::findModel($primaryKey)) {
				$model->delete();
			}
		}
	}

	/**
	 * Отличия от базового deleteAll(): проверка доступов и вызов родительского метода, всё.
	 * @param null|mixed $condition
	 * @return int|null
	 * @throws Throwable
	 */
	public static function deleteAllEx($condition = null):?int {
		$self_class_name = static::class;
		/** @var static $self_class */
		$self_class = new $self_class_name();
		if (!UserAccess::canAccess($self_class, AccessMethods::delete)) {
			$self_class->addError('id', 'Вам не разрешено производить данное действие.');
			AlertModel::AccessNotify();
			return null;
		}
		return parent::deleteAllEx($condition);
	}

}