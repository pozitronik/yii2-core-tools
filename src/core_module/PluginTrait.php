<?php
declare(strict_types = 1);

namespace pozitronik\core\core_module;

use Throwable;
use yii\base\InvalidConfigException;

/**
 * Дополнительный функционал, добавляемый к моделям, находящимся внутри модулей
 * Trait PluginTrait
 * @package app\models\core\core_module
 *
 * @property-read CoreModule|null $plugin
 */
trait PluginTrait {

	/**
	 * Геттер для $plugin (в случае, если удобнее будет обратиться к свойству)
	 * @return CoreModule|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public function getPlugin():?CoreModule {
		return self::Plugin();
	}

	/**
	 * Вычисляет плагин, внутри которого находится модель
	 * @return CoreModule|null
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public static function Plugin():?CoreModule {
		foreach (PluginsSupport::ListPlugins() as $plugin) {
			$currentPluginNamespace = $plugin->namespace;
			if (0 === strncmp(static::class, $currentPluginNamespace, strlen($currentPluginNamespace))) {
				return $plugin;
			}
		}
		return null;
	}

	/**
	 * @param string $text
	 * @param null $url
	 * @param array $options
	 * @return string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function a(string $text, $url = null, array $options = []):string {
		/*Интересно, что случится, если призвать эту штуку вне модуля. Упадёт так-то.*/
		/*По логике, такое падение означает отсутствие модуля, на который происходит ссылка. Вместо этого можно выдавать заглушку, типа "доставьте", или делать ссылку неактивной*/
		return self::Plugin()::a($text, $url, $options);
	}

	/**
	 * @param $url
	 * @return string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @deprecated
	 */
	public static function to($url = ''):string {//Лучше ссылаться в модуль, а не в модель
		return self::Plugin()::to($url);
	}

}