<?php
declare(strict_types = 1);

namespace pozitronik\core\core_module;

use pozitronik\helpers\ArrayHelper;
use app\modules\privileges\models\UserRightInterface;
use app\modules\references\models\Reference;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class PluginsSupport
 * @package app\models\core\core_module
 *
 * Ядро поддержки расширений. Обходит все подключённые модули, выбирая из них те, что имеют интерфейс CoreModuleInterface.
 * Найденные модули трактуются, как плагины: меж ними проверяются зависимости, выбираются данные/регистрируются функции.
 * Как обычно, прототипируем по мере написания.
 */
class PluginsSupport {

	/**
	 * @param string $name - id плагина из web.php
	 * @param null|array $pluginConfigurationArray - конфиг плагина из web.php вида
	 * [
	 *        'class' => Module::class,
	 *        ...
	 * ]
	 * null - подтянуть конфиг автоматически
	 *
	 * @return null|CoreModule - загруженный экземпляр модуля
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private static function LoadPlugin(string $name, ?array $pluginConfigurationArray = null):?CoreModule {
		$pluginConfigurationArray = $pluginConfigurationArray??ArrayHelper::getValue(Yii::$app->modules, $name, []);
		$module = Yii::createObject($pluginConfigurationArray, [$name]);
		if ($module instanceof CoreModule) return $module;
		return null;
	}

	/**
	 * Возвращает список подключённых плагинов. Список можно задать в конфигурации, либо же вернутся все подходящие модули, подключённые в Web.php
	 * @return CoreModule[] Массив подключённых плагинов
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function ListPlugins():array {
		if (null === $plugins = ArrayHelper::getValue(Yii::$app->params, 'plugins')) {
			$plugins = [];
			foreach (Yii::$app->modules as $name => $module) {
				if (is_object($module)) {
					if ($module instanceof CoreModule) $plugins[$name] = $module;
				} else if (null !== $loadedModule = self::LoadPlugin($name, $module)) {
					$plugins[$name] = $loadedModule;
				}
			}
		}
		return $plugins;
	}

	/**
	 * Возвращает плагин по его id
	 * @param string $pluginId
	 * @return CoreModule|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetPluginById(string $pluginId):?CoreModule {
		return ArrayHelper::getValue(self::ListPlugins(), $pluginId);
	}

	/**
	 * Возвращает плагин по его имени класса
	 * @param string $className
	 * @return CoreModule|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetPluginByClassName(string $className):?CoreModule {
		$config = array_filter(Yii::$app->modules, static function($element) use ($className) {
			return is_array($element) && $className === ArrayHelper::getValue($element, 'class');
		});
		if (null === $pluginName = ArrayHelper::key($config)) return null;

		return self::LoadPlugin($pluginName, $config[$pluginName]);
	}

	/**
	 * Возвращает имя плагина по id
	 * @param string $pluginId
	 * @return string|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetName(string $pluginId):?string {
		/** @var CoreModule $plugin */
		if (null !== $plugin = self::GetPluginById($pluginId)) return $plugin->name;
		return null;
	}

	/**
	 * Возвращает массив путей к контроллерам плагинов, дальше WigetableController по ним построит навигацию
	 * @return string[]
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetAllControllersPaths():array {
		$result = [];
		foreach (self::ListPlugins() as $plugin) {
			$result[$plugin->id] = $plugin->controllerPath;
		}
		return $result;
	}

	/**
	 * Возвращает массив моделей справочников, подключаемых в конфигурации плагина, либо одну модель (при задании $referenceClassName)
	 * @param string $pluginId id плагина
	 * @param null|string Имя класса загружаемого справочника
	 * @return Reference[]|Reference|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetReferences(string $pluginId, ?string $referenceClassName = null) {
		/** @var array $references */
		if ((null !== $plugin = self::GetPluginById($pluginId)) && null !== $references = ArrayHelper::getValue($plugin->params, 'references')) {
			if (null === $referenceClassName) {//вернуть массив со всеми справочниками
				$result = [];

				foreach ($references as $reference) {
					$referenceObject = Yii::createObject($reference);
					$referenceObject->pluginId = $plugin->id;
					$result[] = $referenceObject;
				}
				return $result;
			}

			foreach ($references as $reference) {
				/** @var Reference $referenceObject */
				$referenceObject = Yii::createObject($reference);
				if ($referenceClassName === $referenceObject->formName()) {
					$referenceObject->pluginId = $plugin->id;
					return $referenceObject;
				}
			}
		}
		return null;
	}

	/**
	 * Возвращает массив справочников, подключаемых в конфигурациях плагинов
	 * @return Reference[]
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetAllReferences():array {
		$result = [];
		foreach (self::ListPlugins() as $plugin) {
			/** @var array $references */
			if (null !== $references = ArrayHelper::getValue($plugin->params, 'references')) {
				foreach ($references as $reference) {
					$referenceObject = Yii::createObject($reference);
					$referenceObject->pluginId = $plugin->id;
					$result[$referenceObject->formName()] = $referenceObject;
				}
			}
		}
		return $result;
	}

	/**
	 * Возвращает массив всех возможных прав из всех модулей
	 * @param UserRightInterface[] $excludedRights Массив моделей, исключённых из общего списка
	 * @return UserRightInterface[]
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public static function GetAllRights(array $excludedRights = []):array {
		$result = [[]];
		foreach (self::ListPlugins() as $plugin) {
			$result[] = $plugin->getRightsList($excludedRights);
		}
		$result = array_merge(...$result);

		return $result;
	}

}