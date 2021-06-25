<?php

namespace panlatent\craft\smslogin\records;

use craft\db\ActiveRecord;
use panlatent\craft\smslogin\db\Table;

/**
 * Class Sender
 *
 * @package panlatent\craft\smslogin\records
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string $type
 * @property string $settings
 */
class Sender extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Table::SENDERS;
    }
}