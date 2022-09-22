<?php declare(strict_types=1);

namespace MysqlModels\Tests\Unit;

use PHPUnit\Framework\TestCase;
use MysqlModels\Generation;
use PhpEnv\EnvManager;

class GenerationTest extends TestCase
{
    public const TARGET_FOLDER = '/projects/workspace/tests/Unit/Models';
    public const NAMESPACE = 'MysqlModels\Tests\Unit\Models';

    public static function generateDefault(): ?string
    {
        $filename = '/projects/workspace/.env';
        
        $envs = EnvManager::parse($filename);

        return Generation::process($envs['MYSQL_HOST'], $envs['MYSQL_DATABASE'], $envs['MYSQL_USER'], $envs['MYSQL_PASSWORD'], self::TARGET_FOLDER, self::NAMESPACE);
    }

    public function testGenerateOk(): void
    {
        $result = self::generateDefault();

        $this->assertNull($result, is_null($result) ? '' : $result);

        $testFile = self::TARGET_FOLDER.'/Connection.php';

        $this->assertFileExists($testFile);

        $this->assertStringContainsString(self::NAMESPACE, file_get_contents($testFile));
    }
}