<?php
declare(strict_types = 1);

namespace pozitronik\core\models\sys_exceptions;

use pozitronik\core\traits\ARExtended;
use pozitronik\helpers\Utils;
use Yii;
use Throwable;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "sys_exceptions".
 *
 * @property int $id
 * @property string $timestamp
 * @property int $user_id
 * @property int $code
 * @property string $file
 * @property int $line
 * @property string $message
 * @property string $trace
 * @property string $get
 * @property string $post
 * @property bool $known
 */
class SysExceptions extends ActiveRecord {
	use ARExtended;

	/**
	 * @return LCQuery
	 */
	public static function find():LCQuery {
		return new LCQuery(static::class);
	}

	/**
	 * @inheritdoc
	 */
	public static function tableName():string {
		return 'sys_exceptions';
	}

	/**
	 * @inheritdoc
	 */
	public function rules():array {
		return [
			[['timestamp', 'get', 'post'], 'safe'],
			[['user_id', 'code', 'line'], 'integer'],
			[['message', 'trace'], 'string'],
			[['file'], 'string', 'max' => 255],
			['known', 'boolean']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'timestamp' => 'Время',
			'user_id' => 'Пользователь',
			'code' => 'Код',
			'file' => 'Файл',
			'line' => 'Строка',
			'message' => 'Сообщение',
			'trace' => 'Trace',
			'get' => '$_GET',
			'post' => '$_POST',
			'known' => 'Известная ошибка'
		];
	}

	/**
	 * В случае, если надо поставить отлов и логирование исключения
	 * @param Throwable $t
	 * @param bool $throw - Если передано исключение, оно выбросится в случае ненахождения модели
	 * @param bool $known_error - Пометить исключение, как известное. Сделано для пометки исключений, с которыми мы ничего сделать не можем (ошибка сторонних сервисов, например).
	 * @throws Throwable
	 */
	public static function log(Throwable $t, bool $throw = false, bool $known_error = false):void {
		$logger = new self;
		try {
			$logger->setAttributes([
				'user_id' => Yii::$app->request->isConsoleRequest?0:Yii::$app->user->id,
				'code' => $t->getCode(),
				'file' => $t->getFile(),
				'line' => $t->getLine(),
				'message' => $t->getMessage(),
				'trace' => $t->getTraceAsString(),
				'get' => json_encode($_GET),
				'post' => json_encode($_POST),
				'known' => $known_error
			]);
			if (!$logger->save()) Utils::fileLog($logger->attributes, 'exception catch', 'exception.log');
		} /** @noinspection BadExceptionsProcessingInspection */ catch (Throwable $t) {
			Utils::fileLog($logger->attributes, '!!!exception catch', 'exception.log');
		} finally {
			if ($throw) throw $t;
		}

	}

	/**
	 * Acknowledge record
	 * @param integer $id
	 * @throws Throwable
	 */
	public static function acknowledgeOne(int $id):void {
		if (null === $model = self::findModel($id, new NotFoundHttpException())) $model->updateAttributes(['known' => true]);
	}

	/**
	 * Помечаем все записи, как известные
	 */
	public static function acknowledgeAll():void {
		self::updateAll(['known' => true], ['known' => false]);
	}

	/**
	 * @return int
	 */
	public static function unknownCount():int {
		return self::find()->unknown()->count();
	}
}
