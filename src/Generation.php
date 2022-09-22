<?php

declare(strict_types=1);

namespace MysqlModels;

use PDO;
use PDOException;

class Generation
{
    /** @var PDO $connection */
    protected static $connection;

    /** @var string $targetFolder */
    protected static $targetFolder;

    /** @var string $stubsFolder */
    protected static $stubsFolder;

    public static function process(
        string $host,
        string $dbname,
        string $username,
        string $password,
        string $targetFolder,
        string $namespace
    ): ?string
    {
        $result = self::setTargetFolder($targetFolder);

        if ($result) {
            return $result;
        }

        if (! self::setStubsFolder()) {
            return 'Problem on setStubsFolder';
        }

        if (! self::connect($host, $dbname, $username, $password)) {
            return 'Problem connecting to the DB';
        }

        $result = self::generateFiles($host, $dbname, $username, $password, $namespace);

        if ($result) {
            return $result;
        }

        return self::generateClasses($namespace);
    }

    protected static function setTargetFolder(string $targetFolder): ?string
    {
        $result = shell_exec("rm -rf $targetFolder");

        if ($result) {
            return $result;
        }

        $result = shell_exec("mkdir -p $targetFolder");

        if ($result) {
            return $result;
        }

        if (substr($targetFolder, -1) != DIRECTORY_SEPARATOR) {
            $targetFolder .= DIRECTORY_SEPARATOR;
        }

        self::$targetFolder = $targetFolder;

        return null;
    }

    protected static function setStubsFolder(): bool
    {
        self::$stubsFolder = __DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR;

        return file_exists(self::$stubsFolder);
    }

    protected static function connect(string $host, string $dbname, string $username, string $password): bool
    {
        $result = true;

        try {
            self::$connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        } catch (PDOException $e) {
            $result = false;
        }

        return $result;
    }

    protected static function generateFiles(
        string $host,
        string $dbname,
        string $username,
        string $password,
        string $namespace
    ): ?string
    {
        $result = self::copyReplace(
            'Connection',
            ['{{namespace}}', '{{host}}', '{{dbname}}', '{{username}}', '{{password}}'],
            [$namespace, $host, $dbname, $username, $password]
        );

        if (! $result) {
            return 'Problem generating the connection file';
        }

        if (! self::copyReplace('Model', ['{{namespace}}'], [$namespace])) {
            return 'Problem generating the ModelBody file';
        }

        if (! self::copyReplace('SqlGenerator', ['{{namespace}}'], [$namespace])) {
            return 'Problem generating the SqlGenerator file';
        }

        return null;
    }

    /**
     * @param string $fileName model filename
     * @param array<string> $search keys to search
     * @param array<string> $replace words to replace
     */
    protected static function copyReplace(string $fileName, array $search, array $replace): bool
    {
        $content = file_get_contents(self::$stubsFolder . $fileName);

        if (!$content) {
            return false;
        }

        $content = str_replace($search, $replace, $content);

        $result = file_put_contents(self::$targetFolder . $fileName . '.php', $content);

        return (is_numeric($result));
    }

    protected static function generateClasses(string $namespace): ?string
    {
        $content = self::getClassmodelContent($namespace);

        if (! $content) {
            return 'Problem on generateClasses reading entity file';
        }

        $descs = self::getTablesInfo();

        if (! $descs) {
            return 'Problem getting tables';
        }

        /**
         * @param array<string, Desc> $descs
         */
        if (! self::generateModels($descs, $content)) {
            return 'Problem on generateModels, file can not be saved';
        }

        return null;
    }

    protected static function getClassmodelContent(string $namespace): ?string
    {
        $content = file_get_contents(self::$stubsFolder.'Class');

        if (! $content) {
            return null;
        }

        return str_replace('{{namespace}}', $namespace, $content);
    }

    /**
     * @return null|array<string, array<string>>
     */
    protected static function getTablesInfo(): ?array
    {
        /**
         * @var array<string, array<string>> $descs
         */
        $descs = [];
        $stmt = self::$connection->query('SHOW TABLES');
        $index = 0;
        $max = 0;
        $tableDescription = [];
        $tableName = '';

        if (! $stmt) {
            return null;
        }

        /**
         * @var array<string> $tables
         */
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tables)) {
            return null;
        }

        $max = count($tables);

        while ($index < $max) {
            $tableName = $tables[$index];
            $tableDescription = self::getTableInfo($tableName);

            if ($tableDescription) {
                $descs[$tableName] = $tableDescription;
                ++$index;
            } else {
                $descs = null;
                $index = $max;
            }
        }

        return $descs;
    }

    /**
     * @return null|array<string>
     */
    protected static function getTableInfo(string $tableName): ?array
    {
        $desc = [];
        $stmt = self::$connection->query("DESC $tableName");

        if (!$stmt) {
            return null;
        }

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        foreach($result as $fielDesc) {
            $desc[] = $fielDesc['Field'];
        }

        return $desc;
    }

    /**
     * @param array<string, array<string>> $descs description of tables
     */
    protected static function generateModels(array $descs, string $modelContent): bool
    {
        $tables = array_keys($descs);
        $max = count($tables);
        $result = false;
        $index = 0;

        while ($index < $max) {
            $table = $tables[$index];

            $fileName = self::toPascalCase($table);

            [$columns, $properties] = self::generateColumnsProperties($descs[$table]);/** @phpstan-ignore-line */

            $content = str_replace(
                ['{{class_name}}', '{{table_name}}', '{{columns}}', '{{properties}}'],
                [$fileName, $table, $columns, $properties],
                $modelContent
            );

            $result = boolval(file_put_contents(self::$targetFolder . $fileName . '.php', $content));

            $index = ($result) ? ++$index : $max;
        }

        return $result;
    }

    protected static function toPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }

    /**
     * @param array<string, string> $desc description of table
     * @return array<string, string>
     */
    protected static function generateColumnsProperties(array $desc): array
    {
        $columns = '';
        $properties = '';

        foreach ($desc as $colName) {
            $columns .= strlen($columns) ? ',' : '[';
            $columns .= "\n\t\t'$colName'";

            $properties .= sprintf("\tpublic $%s;\n", $colName);
        }

        $columns .= "\n\t]";

        return [$columns, $properties];/** @phpstan-ignore-line */
    }
}
