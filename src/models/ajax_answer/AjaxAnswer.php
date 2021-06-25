<?php
declare(strict_types = 1);

namespace pozitronik\core\models\ajax_answer;

use yii\base\Model;

/**
 * Модель ответа ajax-обработчика
 * Class AjaxAnswer
 * @package app\models\core\ajax
 *
 * @property int $resultCode Числовой код результата операции
 * @property null|int $count Количество возвращаемых результатов (если не установлено, то не используется)
 * @property null|array $items Массив результатов (если не установлен, то не используется
 * @property null|string $content Возвращаемый результат рендеринга (если необходим)
 *
 * @property-read array $answer Массив с ответом
 * @deprecated since 1.2.0
 */
class AjaxAnswer extends Model {
	public const RESULT_OK = 0;/*Отработано*/
	public const RESULT_ERROR = 1;/*Ошибка*/
	public const RESULT_POSTPONED = 2;/*На будущее*/

	private $_resultCode = self::RESULT_OK;
	private $_count;
	private $_items;
	private $_content;

	/**
	 * @inheritdoc
	 */
	public function rules():array {
		return [
			[['resultCode', 'items'], 'integer'],
			[['items'], 'safe']
		];
	}

	/**
	 * @return int
	 */
	public function getResultCode():int {
		return $this->_resultCode;
	}

	/**
	 * @param int $resultCode
	 */
	public function setResultCode(int $resultCode):void {
		$this->_resultCode = $resultCode;
	}

	/**
	 * @return int
	 */
	public function getCount():?int {
		return $this->_count;
	}

	/**
	 * @param int|null $count
	 */
	public function setCount(?int $count):void {
		$this->_count = $count;
	}

	/**
	 * @return array
	 */
	public function getItems():?array {
		return $this->_items;
	}

	/**
	 * @param array|null $items
	 */
	public function setItems(?array $items):void {
		$this->_items = $items;
	}

	/**
	 * @return null|string
	 */
	public function getContent():?string {
		return $this->_content;
	}

	/**
	 * @param null|string $content
	 */
	public function setContent(?string $content):void {
		$this->_content = $content;
	}

	/**
	 * Добавляет ошибку и возвращает ответ (для случая, когда ajax-контроллер должен ответить при обнаружении ошибки)
	 * @param string $attribute
	 * @param string $error
	 * @return array
	 */
	public function addError($attribute, $error = ''):array {
		parent::addError($attribute, $error);
		$this->resultCode = self::RESULT_ERROR;
		return $this->answer;
	}

	/**
	 * Добавляет массив ошибок и возвращает ответ (для случая, когда ajax-контроллер должен ответить при обнаружении ошибки)
	 * @param array $items
	 * @return array
	 */
	public function addErrors(array $items):array {
		parent::addErrors($items);
		$this->resultCode = self::RESULT_ERROR;
		return $this->answer;
	}

	/**
	 * Возврат ответа
	 * @return array
	 */
	public function getAnswer():array {
		return [
			'result' => $this->resultCode,
			'errors' => ([] === $this->errors)?null:$this->errors,
			'count' => $this->count,
			'items' => $this->items,
			'content' => $this->content
		];
	}

}