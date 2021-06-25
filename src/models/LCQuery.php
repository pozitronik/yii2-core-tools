<?php
declare(strict_types = 1);

namespace pozitronik\core\models;

use pozitronik\core\traits\ActiveQueryExtended;
use yii\db\ActiveQuery;

/**
 * Class LCQuery (назван так в честь LightCab, в котором такое решение впервые появилось)
 * @deprecated since 1.2.0
 */
class LCQuery extends ActiveQuery {
	use ActiveQueryExtended;
}