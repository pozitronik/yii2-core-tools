<?php
declare(strict_types = 1);

namespace pozitronik\core\models\core_module;

use pozitronik\core\interfaces\access\UserRightInterface;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ReflectionHelper;
use pozitronik\helpers\Utils;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module as BaseModule;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Class CoreModule
 *
 * @property-read string $name
 * @property-read string $namespace
 * @property-read string $alias
 */
class CoreModule extends BaseModule implements CoreModuleInterface {
	protected $_namespace;
	protected $_alias;

	/**
	 * {@inheritDoc}
	 */
	public function __construct(string $id, $parent = null, array $config = []) {
		parent::__construct($id, $parent, $config);
	}

	/**
	 * Возвращает название плагина
	 * @return string
	 */
	public function getName():string {
		return $this->id;
	}

	/**
	 * Возвращает неймспейс загруженного модуля (для вычисления алиасных путей внутри модуля)
	 * @return string
	 */
	public function getNamespace():string {
		if (null === $this->_namespace) {
			$class = get_class($this);
			if (false !== ($pos = strrpos($class, '\\'))) {
				$this->_namespace = substr($class, 0, $pos);
			}
		}
		return $this->_namespace;
	}

	/**
	 * Возвращает зарегистрированный алиас модуля
	 * @return string
	 */
	public function getAlias():string {
		if (null === $this->_alias) {
			/*Регистрируем алиас плагина*/
			$this->_alias = "@{$this->id}";
			Yii::setAlias($this->_alias, $this->basePath);
		}

		return $this->_alias;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRightsList(array $excludedRights = []):array {
		$result = [];
		$rightsDir = Yii::getAlias($this->alias."/models/rights/");
		if (file_exists($rightsDir)) {

			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rightsDir), RecursiveIteratorIterator::SELF_FIRST);
			$excludedIds = ArrayHelper::getColumn($excludedRights, 'id');
			/** @var RecursiveDirectoryIterator $file */
			foreach ($files as $file) {
				if (($file->isFile() && 'php' === $file->getExtension() && null !== $model = ReflectionHelper::LoadClassFromFile($file->getRealPath(), [UserRightInterface::class])) && (!$model->hidden) && (!in_array($model->id, $excludedIds))) {
					$model->module = $this->name;
					$result[] = $model;
				}
			}
		}
		return $result;
	}

	/**
	 * Функция генерирует пункт меню навигации внутри модуля
	 * @param string $label
	 * @param string|array $uroute
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function breadcrumbItem(string $label, $uroute = ''):array {
		if ((null === $module = static::getInstance()) && null === $module = PluginsSupport::GetPluginByClassName(static::class)) {
			$module = Yii::$app->controller->module;
		}
		return ['label' => $label, 'url' => $module::to($uroute)];
	}

	/**
	 * Возвращает путь внутри модуля. Путь всегда будет абсолютный, от корня
	 * @param string|array $route -- контроллер и экшен + параметры
	 * @return string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @example SalaryModule::to(['salary/index','id' => 10]) => /salary/salary/index?id=10
	 * @example UsersModule::to('users/index') => /users/users/index
	 */
	public static function to($route = ''):string {
		if ((null === $module = static::getInstance()) && null === $module = PluginsSupport::GetPluginByClassName(static::class)) {
			throw new InvalidConfigException("Модуль ".static::class." не подключён");
		}
		if ('' === $route) {
			$route = Utils::setAbsoluteUrl($module->defaultRoute);
		} elseif (is_array($route)) {/* ['controller{/action}', 'actionParam' => $paramValue */
			ArrayHelper::setValue($route, 0, Utils::setAbsoluteUrl($module->id.Utils::setAbsoluteUrl(ArrayHelper::getValue($route, 0))));
		} else {/* 'controller{/action}' */
			$route = Utils::setAbsoluteUrl($module->id.Utils::setAbsoluteUrl($route));
		}
		return Url::to($route);
	}


	/**
	 * Генерация html-ссылки внутри модуля (аналог Html::a(), но с автоматическим учётом путей модуля).
	 * @param string $text
	 * @param array|string|null $url
	 * @param array $options
	 * @return string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function a(string $text, $url = null, array $options = []):string {
		$url = static::to($url);
		return Html::a($text, $url, $options);
	}
}