<?php


use Phinx\Migration\AbstractMigration;

class MailTemplateLogging extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
	    $table = $this->table('stem_mail_template_log', ['id' => false, 'primary_key' => 'id', 'engine' => 'innodb', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci']);
	    $table
		    ->addColumn('id', 'biginteger', ['signed' => false, 'null' => false, 'identity' => true])
		    ->addColumn('template', 'string', ['length' => 255, 'null' => false, 'collation' => 'utf8mb4_unicode_ci'])
		    ->addColumn('email_to', 'text', ['collation' => 'utf8mb4_unicode_ci'])
		    ->addColumn('email_bcc', 'text', ['collation' => 'utf8mb4_unicode_ci'])
		    ->addColumn('status', 'integer', ['signed' => true, 'null' => false, 'default' => (int)0])
		    ->addColumn('data', 'text', ['collation' => 'utf8mb4_unicode_ci'])
		    ->addColumn('error', 'text', ['collation' => 'utf8mb4_unicode_ci'])
		    ->addColumn('responseId', 'string', ['length' => 255, 'collation' => 'utf8mb4_unicode_ci'])
		    ->addColumn('response', 'text', ['collation' => 'utf8mb4_unicode_ci'])
		    ->addTimestamps()
		    ->create();
    }
}
