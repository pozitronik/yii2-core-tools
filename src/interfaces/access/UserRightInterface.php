<?php
declare(strict_types = 1);

namespace pozitronik\core\interfaces\access;

use yii\base\Model;
use yii\web\Controller;

/**
 * Interface UserRight
 * @package app\models\user_rights
 * Интерфейс права пользователя.
 * Каждое право определяет ту или иную возможность действия.
 * Набор прав объединяется под общим алиасом (привилегией), определённым в классе Privileges
 * @property-read string $id
 * @property-read string $name
 * @property-read string $description
 * @property-read bool $hidden
 * @property string $module
 */
interface UserRightInterface {
	/*Константы доступа*/
	public const ACCESS_DENY = false;
	public const ACCESS_ALLOW = true;
	public const ACCESS_UNDEFINED = null;

	/*Флаговые константы*/
	public const FLAG_SERVICE = 1;

	/**
	 * Магическое свойство, необходимое для сравнения классов, например
	 * Предполагается, что будет использоваться имя класса
	 * @return string
	 */
	public function __toString():string;

	/**
	 * Уникальный идентификатор (подразумевается имя класса)
	 * @return string
	 */
	public function getId():string;

	/**
	 * Вернуть true, если правило не должно быть доступно в выбиралке
	 * @return bool
	 */
	public function getHidden():bool;

	/**
	 * Имя права
	 * @return string
	 */
	public function getName():string;

	/**
	 * Подробное описание возможностей, предоставляемых правом
	 * @return string
	 */
	public function getDescription():string;

	/**
	 * @param Controller $controller Экземпляр класса контроллера
	 * @param string $action Имя экшена
	 * @param array $actionParameters Дополнительный массив параметров (обычно $_GET)
	 * @return bool|null Одна из констант доступа
	 */
	public function checkActionAccess(Controller $controller, string $action, array $actionParameters = []):?bool;

	/**
	 * @param Model $model Модель, к которой проверяется доступ
	 * @param int|null $method Метод доступа (см. AccessMethods)
	 * @param array $actionParameters Дополнительный массив параметров (обычно $_GET)
	 * @return bool|null
	 */
	public function checkMethodAccess(Model $model, ?int $method = AccessMethods::any, array $actionParameters = []):?bool;

	/**
	 * Набор действий, предоставляемых правом. Пока прототипирую
	 *
	 * @return array
	 */
	public function getActions():array;

	/**
	 * Для возможностей, которые можно и нужно включать только флагами + прототипирование
	 * @param int $flag
	 * @return null|bool
	 */
	public function getFlag(int $flag):?bool;
}