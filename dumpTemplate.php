<?php
/**
 * This view is used by console/controllers/MigrateController.php
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name */

echo "<?php\n";
?>

use yii\db\Schema;
use yii\db\Migration;
use bariew\moduleMigration\DbHelper;

class <?= $className ?> extends Migration
{
    public function up()
    {
        $sql = <<<SQL
<?= str_replace('$', '<$-sign>', $sql); ?>;
SQL;
        DbHelper::foreignKeysOff();
        if(<?= $remove; ?>) {
            \Yii::$app->db->createCommand()->truncateTable('<?= $table; ?>')->execute();
        }
        \Yii::$app->db->createCommand(str_replace('<$-sign>', '$', $sql))->execute();
        DbHelper::foreignKeysOn();
    }

    public function down()
    {
        DbHelper::foreignKeysOff();
        if(<?= $remove; ?>) {
            \Yii::$app->db->createCommand()->truncateTable('<?= $table; ?>')->execute();
        }
        DbHelper::foreignKeysOn();
        return true;
    }
}
