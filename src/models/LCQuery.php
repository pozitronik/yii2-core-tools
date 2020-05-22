<?php
declare(strict_types = 1);

namespace pozitronik\core\models;

use pozitronik\core\traits\ActiveQueryExtended;
use yii\db\ActiveQuery;

/**
 * Class LCQuery (назван так в честь LightCab, в котором такое решение впервые появилось)
 */
class LCQuery extends ActiveQuery {
	use ActiveQueryExtended;
}