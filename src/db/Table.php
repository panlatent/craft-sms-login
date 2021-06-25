<?php

namespace panlatent\craft\smslogin\db;

/**
 * Class Table (Enum table names)
 *
 * @package panlatent\craft\smslogin\db
 */
abstract class Table
{
    const CAPTCHA = '{{%smslogin_captcha}}';
    const SENDERS = '{{%smslogin_senders}}';
    const SENDER_LOGS = '{{%smslogin_sender_logs}}';
}