<?php
declare(strict_types = 1);

namespace pozitronik\core\models\core_controller;

use pozitronik\core\models\core_controller\CoreController;

/**
 * Class WigetableController
 * Расширенный класс контроллера с дополнительными опциями встройки в меню и навигацию
 *
 * @property-read false|string $menuIcon
 * @property-read false|string $menuCaption
 * @property-read boolean $menuDisabled
 * @property-read integer $orderWeight
 * @property-read string $defaultRoute
 */
class WigetableController extends CoreController {

	public $menuDisabled = false;//отключает пункт меню
	public $orderWeight = 0;

	/**
	 * Возвращает путь к иконке контроллера
	 * @return false|string
	 */
	public function getMenuIcon() {
		return false;
	}

	/**
	 * Возвращает строковое название пункта меню контроллера
	 * @return false|string
	 */
	public function getMenuCaption() {
		return false;
	}

	/**
	 * При необходимости здесь можно переопределить роут контроллера, обрабатываемый виджетом
	 * @return string
	 */
	public function getDefaultRoute():string {
		return $this->route;
	}

}