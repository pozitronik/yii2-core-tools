<?php
declare(strict_types = 1);

namespace pozitronik\core\traits;

use Yii;
use app\helpers\PathHelper;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Trait Upload
 * @package app\models\core\traits
 * @property UploadedFile $uploadFileInstance
 */
trait Upload {
	public $uploadFileInstance;

	/**
	 * Загружает файл в соответствующий модели каталог, возвращает полный путь или null в случае ошибки
	 * @param string|null $saveDirAlias Параметр для переопределения пути загрузки
	 * @param string|null $newFileName Параметр для переименования загруженного файла (без расширения)
	 * @param string|null $newFileExtension Параметр для изменения расширения загруженного файла
	 * @param string $instanceName Параметр для переопределения имени инпута при необходимости
	 * @param int|null $returnPart Возвращаемый элемент имени (как в pathinfo)
	 * @return string|null
	 * @throws InvalidConfigException
	 */
	public function uploadFile(?string $saveDirAlias = null, ?string $newFileName = null, ?string $newFileExtension = null, string $instanceName = 'uploadFileInstance', ?int $returnPart = null):?string {
		/** @var Model $this */
		$saveDir = Yii::getAlias($saveDirAlias??"@app/web/uploads/{$this->formName()}");
		/** @var Model $this */
		if ((null !== $uploadFileInstance = UploadedFile::getInstance($this, $instanceName)) && PathHelper::CreateDirIfNotExisted($saveDir)) {
			$fileName = $uploadFileInstance->name;
			$fileName = (null === $newFileName)?$fileName:PathHelper::ChangeFileName($fileName, $newFileName);
			$fileName = (null === $newFileExtension)?$fileName:PathHelper::ChangeFileExtension($fileName, $newFileExtension);
			$fileName = $saveDir.DIRECTORY_SEPARATOR.$fileName;
			$uploadFileInstance->saveAs($fileName);
			return null === $returnPart?$fileName:pathinfo($fileName, $returnPart);
		}
		return null;
	}

}