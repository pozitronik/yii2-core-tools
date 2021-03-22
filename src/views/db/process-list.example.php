<?php
declare(strict_types = 1);
/**
 * @var View $this
 * @var ArrayDataProvider $dataProvider
 * @var string|false $message
 */

use pozitronik\core\models\ProcessListItem;
use yii\data\ArrayDataProvider;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

$this->title = 'Мониторинг процессов БД';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row">
	<div class="col-xs-12">
		<?php if ($message): ?>
			<div class="alert alert-info">
				<?= $message ?>
			</div>
		<?php endif; ?>
		<?= GridView::widget([
			'dataProvider' => $dataProvider,
			'columns' => [
				'id',
				'db',
				'command',
				'time',
				'state',
				[
					'attribute' => 'user_id',
					'label' => 'user',
					'format' => 'raw'
				],
				'operation',
				'query',
				[
					'class' => ActionColumn::class,
					'template' => '{kill}',
					'buttons' => [
						'kill' => static function(string $url, ProcessListItem $model, int $key) {
							return Html::a('', ['kill', 'process_id' => $model->id], [
								'class' => 'glyphicon glyphicon-remove',
								'title' => 'Kill process',
								'data' => [
									'confirm' => "Kill process {$model->id}?"
								]
							]);
						}
					]
				],
			]
		]); ?>
	</div>
</div>