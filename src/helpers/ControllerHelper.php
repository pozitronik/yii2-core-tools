<?php
declare(strict_types = 1);

namespace pozitronik\core\helpers;

use pozitronik\core\traits\ControllerTrait;
use pozitronik\helpers\ReflectionHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class ControllerHelper
 */
class ControllerHelper {
	use ControllerTrait;

	/**
	 * Загружает динамически класс веб-контроллера Yii2 по его пути
	 * @param string $fileName
	 * @param string|null $moduleId
	 * @param string[]|null $parentClassFilter Фильтр по родительскому классу (загружаемый контролер должен от него наследоваться)
	 * @return self|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function LoadControllerClassFromFile(string $fileName, ?string $moduleId, ?array $parentClassFilter = null):?object {
		$className = ReflectionHelper::GetClassNameFromFile($fileName);
		$class = ReflectionHelper::New($className);
		/** @noinspection NullPointerExceptionInspection */
		if (ReflectionHelper::IsInSubclassOf($class, $parentClassFilter)) {
			if (null === $moduleId) {
				$module = Yii::$app;
			} else {
				$module = ModuleHelper::GetModuleById($moduleId);
				if (null === $module) throw new InvalidConfigException("Module $moduleId not found or module not configured properly.");
			}
			return new $className(self::ExtractControllerId($className), $module);
		}

		return null;
	}

	/**
	 * Загружает динамически класс веб-контроллера Yii2 по его id и модулю
	 * @param string $controllerId
	 * @param string|null $moduleId
	 * @return self|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetControllerByControllerId(string $controllerId, ?string $moduleId):?object {
		if (null === $module = ModuleHelper::GetModuleById($moduleId)) throw new InvalidConfigException("Module $moduleId not found or module not configured properly.");
		$controllerId = implode('', array_map('ucfirst', preg_split('/-/', $controllerId, -1, PREG_SPLIT_NO_EMPTY)));
		return self::LoadControllerClassFromFile("{$module->controllerPath}/{$controllerId}Controller.php", $moduleId);

	}

	/**
	 * Выгружает список контроллеров в указанном неймспейсе
	 * @param string $path
	 * @param string|null $moduleId
	 * @param string[]|null $parentClassFilter Фильтр по классу родителя
	 * @return self[]
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetControllersList(string $path, ?string $moduleId = null, ?array $parentClassFilter = null):array {
		$result = [];

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Yii::getAlias($path)), RecursiveIteratorIterator::SELF_FIRST);
		/** @var RecursiveDirectoryIterator $file */
		foreach ($files as $file) {
			if ($file->isFile() && 'php' === $file->getExtension() && null !== $controller = self::LoadControllerClassFromFile($file->getRealPath(), $moduleId, $parentClassFilter)) {
				$result[] = $controller;
			}
		}
		return $result;
	}
}