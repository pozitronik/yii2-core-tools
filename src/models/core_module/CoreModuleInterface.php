<?php
declare(strict_types = 1);

namespace pozitronik\core\models\core_module;

use pozitronik\core\interfaces\access\UserRightInterface;

/**
 * Интерфейс системного модуля приложения
 * Interface CoreModule
 * @package app\models\core
 */
interface CoreModuleInterface {

	/**
	 * Возвращает название плагина
	 * @return string
	 */
	public function getName():string;

	/**
	 * Возвращает неймспейс загруженного модуля (для вычисления алиасных путей внутри модуля)
	 * @return string
	 */
	public function getNamespace():string;

	/**
	 * Возвращает зарегистрированный алиас модуля
	 * @return string
	 */
	public function getAlias():string;

	/**
	 * Возвращает массив прав, поддерживаемых модулем
	 * @param UserRightInterface[] $excludedRights Массив моделей, исключаемых из списка
	 * @return UserRightInterface[]
	 */
	public function getRightsList(array $excludedRights = []):array;

}