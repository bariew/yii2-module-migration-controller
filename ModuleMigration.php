<?php
/**
 * ModuleMigration class file
 * @copyright Copyright (c) 2014 Galament
 * @license http://www.yiiframework.com/license/
 */

namespace bariew\moduleMigration;

use yii\console\controllers\MigrateController;
use Yii;
use yii\console\Exception;

/**
 * Runs migrations from module /migrations folder.
 * 
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class ModuleMigration extends MigrateController
{
    /**
     * @var array module base paths
     */
    public $allMigrationPaths = [];
    /**
     * @var array paths to migrations like [path => migrationName]
     */
    public $migrationFiles = [];

    /**
     * @var string dump migration file template.
     */
    public $dumpTemplateFile = '@bariew/moduleMigration/dumpTemplate.php';

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $cache = \Yii::$app->db->schemaCache;
        if (is_object($cache)) {
            $cache->flush();
        }
        $this->allMigrationPaths['app'] = $this->migrationPath;
        $this->attachModuleMigrations();
        $this->setMigrationFiles();
        return true;
    }
    
    /**
     * @inheritdoc
     */
    protected function getNewMigrations()
    {
        $result = [];
        foreach ($this->allMigrationPaths as $path) {
            $this->migrationPath = $path;
            if (!file_exists($path)) {
                continue;
            }
            $result = array_merge($result, parent::getNewMigrations());
        }
        $this->migrationPath = $this->allMigrationPaths['app'];
        return $result;
    }

    /**
     * gets path to migration file
     * @param string $name migration name
     * @param bool|string $path module migrations base path
     * @return string path to migration file
     */
    protected function getMigrationFile($name, $path = false)
    {
        $path = $path ? $path : $this->migrationPath;
        return $path . DIRECTORY_SEPARATOR . $name . '.php';
    }
    
    /**
     * @inheritdoc
     */
    protected function createMigration($class)
    {
        if (!$file = array_search($class, $this->migrationFiles)) {
            return false;
        }
        require_once($file);
        return new $class(['db' => $this->db]);
    }
    /**
     * creates $allMigrationPaths attribute from module base paths
     */
    protected function attachModuleMigrations()
    {
        $s = DIRECTORY_SEPARATOR;
        foreach (\Yii::$app->modules as $name => $config) {
            switch (gettype($config)) {
                case 'object'   : 
                    $basePath = $config->basePath;
                    break;
                case 'array'    : 
                    if (isset($config['basePath'])) {
                        $basePath = $config['basePath'];
                        break;
                    }
                    $config = $config['class'];
                default         : 
                   // $basePath = \Yii::$app->basePath . "{$s}modules{$s}{$name}";
                    $basePath = str_replace('\\', '/', preg_replace('/^(.*)\\\(\w+)$/', '@$1', $config));
                    $basePath = \Yii::getAlias($basePath);
            }
            $path = $basePath . $s . 'migrations';
            if (file_exists($path) && !is_file($path)) {
                $this->allMigrationPaths[$name] = $path;
            }
        }
    }
    
    /**
     * Creates $migrationFiles array
     * @return array list of migrations like [path=>migrationName]
     */
    protected function setMigrationFiles()
    {
        $result = [];
        foreach ($this->allMigrationPaths as $path) {
            if (!file_exists($path) || is_file($path)) {
                continue;
            }
            $handle = opendir($path);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($filePath)) {
                    $result[$filePath] = $matches[1];
                }
            }
            closedir($handle);
        }
        return $this->migrationFiles = $result;
    }

    /**
     * Migrates current module up.
     * @param string $module module name.
     * @param string|integer $limit migrations limit.
     */
    public function actionModuleUp($module, $limit = 'all')
    {
        $this->setModuleMigrationPaths($module);
        parent::actionUp($limit);
    }

    /**
     * Migrates current module down.
     * @param string $module module name.
     * @param string|integer $limit migrations limit.
     */
    public function actionModuleDown($module, $limit = 'all')
    {
        $this->setModuleMigrationPaths($module);
        parent::actionDown($limit);
    }

    /**
     * Creates table data migration.
     * @param string $table db table name.
     * @param int $remove whether to remove previous data or not (1|0)
     * @throws \yii\console\Exception "No data found"
     */
    public function actionDataDump($table, $remove = 1)
    {
        $className = 'm' . gmdate('ymd_His') . '_' . $table . '_dump';
        if (!$data = Yii::$app->db->createCommand("SELECT * FROM {{{$table}}}")->queryAll()) {
            throw new Exception("No data found");
        }
        $columns = DbHelper::dataColumns($data);
        $sql = DbHelper::insertUpdate($table, $columns, $data)->getSql();
        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $className . '.php';
        $content = $this->renderFile(Yii::getAlias($this->dumpTemplateFile), compact(
            'className', 'remove', 'sql', 'table'
        ));
        file_put_contents($file, $content);
        echo "New migration created successfully.\n";
    }

    /**
     * Sets modules array - leaves only module migrations.
     * @param string $module module name.
     */
    protected function setModuleMigrationPaths($module)
    {
        $paths = ['app' => \Yii::getAlias('@app/runtime/tmp')];
        if (isset($this->allMigrationPaths[$module])) {
            $paths[$module] = $this->allMigrationPaths[$module];
        }
        $this->allMigrationPaths = $paths;
        $this->setMigrationFiles();
    }


    /**
     * @inheritdoc
     */
    protected function getMigrationHistory($limit)
    {
        $history = parent::getMigrationHistory($limit);
        foreach ($history as $name => $time) {
            if (!$this->migrationExists($name)) {
                unset($history[$name]);
            }
        }
        return $history;
    }

    /**
     * Checks whether migration file exists.
     * @param string $name migration name.
     * @return bool whether migration exists.
     */
    protected function migrationExists($name)
    {
        return in_array($name, $this->migrationFiles);
    }
}