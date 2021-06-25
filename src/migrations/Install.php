<?php

namespace panlatent\craft\smslogin\migrations;

use craft\db\Migration;
use panlatent\craft\smslogin\db\Table;

/**
 * Class Install
 *
 * @package panlatent\craft\smslogin\migrations
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(Table::SENDERS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex(null, Table::SENDERS, 'name', true);
        $this->createIndex(null, Table::SENDERS, 'handle', true);

        $this->createTable(Table::SENDER_LOGS, [
            'id' => $this->primaryKey(),
            'senderId' => $this->integer()->notNull(),
            'phone' => $this->char(20),
            'code' => $this->char(10),
            'message' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex(null, Table::SENDER_LOGS, 'senderId');
        $this->addForeignKey(null, Table::SENDER_LOGS, 'senderId', Table::SENDERS, 'id', 'CASCADE');

        $this->createTable(Table::CAPTCHA, [
            'id' => $this->primaryKey(),
            'phone' => $this->string(20),
            'code' => $this->string(10),
            'token' => $this->char(32),
            'route' => $this->text(),
            'expireDate' => $this->dateTime()->notNull(),
            'postDate' => $this->dateTime(),
            'passedDate' => $this->dateTime(),
            'lastTestDate' => $this->dateTime(),
            'retryPost' => $this->integer()->defaultValue(0),
            'retryTest' => $this->integer()->defaultValue(0),
            'reason' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex(null, Table::CAPTCHA, 'phone');
    }

    public function safeDown()
    {
        $this->dropTable(Table::CAPTCHA);
        $this->dropTable(Table::SENDER_LOGS);
        $this->dropTable(Table::SENDERS);
    }
}