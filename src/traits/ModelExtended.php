<?php
declare(strict_types = 1);

namespace pozitronik\core\traits;


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
		return $this->hasProperty($propertyName)?$this->$propertyName:$default;
	}

	/**
	 * @param string $name
	 * @param bool $checkVars
	 * @param bool $checkBehaviors
	 * @return bool
	 * @see Model::hasProperty()
	 */
	abstract public function hasProperty(string $name, bool $checkVars = true, bool $checkBehaviors = true):bool;
}