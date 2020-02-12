<?php
declare(strict_types = 1);

namespace pozitronik\core\models\core_controller;

use pozitronik\core\models\core_module\PluginsSupport;
use pozitronik\helpers\ArrayHelper;
use app\models\core\traits\ModelExtended;
use app\modules\privileges\models\UserAccess;
use pozitronik\helpers\ReflectionHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\filters\AccessControl;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Базовая модель веб-контроллера приложения
 * Class CoreController
 * @package app\models\core
 */
class CoreController extends Controller {
	use ModelExtended;

	/**
	 * {@inheritDoc}
	 */
	public function behaviors():array {
		return [
			[
				'class' => ContentNegotiator::class,
				'formats' => [
					'application/json' => Response::FORMAT_JSON,
					'application/xml' => Response::FORMAT_XML,
					'text/html' => Response::FORMAT_HTML
				]
			],
			'access' => [
				'class' => AccessControl::class,
				'rules' => UserAccess::getUserAccessRules($this)
			]
		];
	}

	/**
	 * @return array
	 */
	public function actions():array {
		return [
			'error' => [
				'class' => ErrorAction::class
			]
		];
	}

	/**
	 * Возвращает все экшены контроллера
	 * @param Controller $controllerClass
	 * @return string[]
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 */
	public static function GetControllerActions(Controller $controllerClass):array {
		$names = ArrayHelper::getColumn(ReflectionHelper::GetMethods($controllerClass), 'name');
		return preg_filter('/^action([A-Z])(\w+?)/', '$1$2', $names);
	}

	/**
	 * Переводит вид имени экшена к виду запроса, который этот экшен дёргает.
	 * @param string $action
	 * @return string
	 * @example actionSomeActionName => some-action-name
	 * @example OtherActionName => other-action-name
	 */
	public static function GetActionRequestName(string $action):string {
		$lines = preg_split('/(?=[A-Z])/', $action, -1, PREG_SPLIT_NO_EMPTY);
		if ('action' === $lines[0]) unset($lines[0]);
		return mb_strtolower(implode('-', $lines));
	}

	/**
	 * Вытаскивает из имени класса контроллера его id
	 * app/shit/BlaBlaBlaController => bla-bla-bla
	 * @param string $className
	 * @return string
	 */
	private static function ExtractControllerId(string $className):string {
		$controllerName = preg_replace('/(^.+)(\\\)([A-Z].+)(Controller$)/', '$3', $className);//app/shit/BlaBlaBlaController => BlaBlaBla
		return mb_strtolower(implode('-', preg_split('/([[:upper:]][[:lower:]]+)/', $controllerName, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)));
	}

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
				$module = PluginsSupport::GetPluginById($moduleId);
				if (null === $module) throw new InvalidConfigException("Module $moduleId not found or plugin not configured properly.");
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
		if (null === $plugin = PluginsSupport::GetPluginById($moduleId)) throw new InvalidConfigException("Module $moduleId not found or plugin not configured properly.");
		$controllerId = implode('', array_map('ucfirst', preg_split('/-/', $controllerId, -1, PREG_SPLIT_NO_EMPTY)));
		return self::LoadControllerClassFromFile("{$plugin->controllerPath}/{$controllerId}Controller.php", $moduleId);

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