<?php
declare(strict_types = 1);

namespace pozitronik\core\interfaces\access;

use ReflectionException;
use Throwable;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\web\Controller;

/**
 * Interface UserAccessInterface
 * @package app\modules\privileges\models
 */
interface UserAccessInterface {

	/**
	 * Формирует массив правил доступа к контроллерам и экшенам (с учётом параметров), применимый в правилах AccessControl
	 * @param Controller $controller
	 * @param array|null $actionParameters
	 * @param bool $defaultAllow
	 * @return array
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	public static function getUserAccessRules(Controller $controller, ?array $actionParameters = null, bool $defaultAllow = false):array;

	/**
	 * Вычисляет, имется ли у текущего пользователя доступ к выполнению метода у модели
	 * @param Model $model
	 * @param null|int $method
	 * @param array|null $actionParameters
	 * @param bool $defaultAllow
	 * @return bool
	 * @throws Throwable
	 */
	public static function canAccess(Model $model, ?int $method = AccessMethods::any, ?array $actionParameters = null, bool $defaultAllow = false):bool;

	/**
	 * Возвращает скоуп групп, доступных пользователю
	 * @return ActiveQuery
	 */
	public static function GetGroupsScope():ActiveQuery;

	/**
	 * @param int $flag
	 * @param bool $defaultAllow
	 * @return bool
	 * @throws Throwable
	 */
	public static function GetFlag(int $flag, bool $defaultAllow = false):bool;

}