<?php
declare(strict_types = 1);

namespace pozitronik\core\models\core_controller;

use pozitronik\core\models\ajax_answer\AjaxAnswer;
use Yii;
use yii\filters\AccessControl;
use yii\filters\ContentNegotiator;
use yii\web\Response;

/**
 * Дефолтные методы аяксовых контроллеров (они у нас могут быть в разных модулях), для унификации API
 * Class AjaxController
 * @package app\models\core
 *
 * @property AjaxAnswer $answer
 */
class BaseAjaxController extends CoreController {
	private $_answer;

	public function init():void {
		parent::init();
		$this->enableCsrfValidation = false;
		$this->_answer = new AjaxAnswer();
	}

	/**
	 * {@inheritDoc}
	 */
	public function behaviors():array {
		$controllerActions = self::GetControllerActions($this);
		$actions = [];
		foreach ($controllerActions as $controllerAction) {
			$actions[] = self::GetActionRequestName($controllerAction);
		}
		return [
			[
				'class' => ContentNegotiator::class,
				'formats' => [
					'application/json' => Response::FORMAT_JSON,
//					'application/xml' => Response::FORMAT_XML,
//					'text/html' => Response::FORMAT_HTML
				]
			],
			'access' => [
				'class' => AccessControl::class,
				'denyCallback' => static function() {
					return null;
				},
				'rules' => [
					[
						'allow' => Yii::$app->user->identity,
						'actions' => $actions,
						'roles' => ['@', '?']
					]
				]
			]
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeAction($action):bool {
		return parent::beforeAction($action);
	}

	/**
	 * @return AjaxAnswer
	 */
	public function getAnswer():AjaxAnswer {
		return $this->_answer;
	}

	/**
	 * @param AjaxAnswer $answer
	 */
	public function setAnswer($answer):void {
		$this->_answer = $answer;
	}

}