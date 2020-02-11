<?php
declare(strict_types = 1);

namespace pozitronik\core\traits;

use yii\base\Model;

/**
 * Trait ModelExtended
 * Yii model extensions
 * @package app\models\core\traits
 */
trait ModelExtended {

	/**
	 * something like ArrayHelper::getValue, but for models
	 * @param string $propertyName
	 * @param null $default
	 * @return mixed|null
	 */
	public function getPropertyValue(string $propertyName, $default = null) {
		/** @var Model $this */
		return $this->hasProperty($propertyName)?$this->$propertyName:$default;
	}
}