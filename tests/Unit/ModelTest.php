<?php declare(strict_types=1);

namespace MysqlModels\Tests\Unit;

use PHPUnit\Framework\TestCase;
use MysqlModels\Tests\Unit\Models\City;
use MysqlModels\Tests\Unit\Models\Connection;

class ModelTest extends TestCase
{
    public const COUNTRY_CODE = 'ESP';
    public const CITY_NAME = 'Málaga';

    public static function setUpBeforeClass(): void
    {
        //GenerationTest::generateDefault();
    }

    public function testGet(): void
    {
        $model = new City();

        $model->where('CountryCode', '=', self::COUNTRY_CODE);

        $cities = $model->get();

        $this->assertIsArray($cities);

        $this->assertInstanceOf(City::class, $cities[0]);
    }

    public function testGetOne(): void
    {
        $model = new City();

        $model->where('Name', '=', self::CITY_NAME);

        $city = $model->getOne();

        $this->assertNotNull($city);

        $this->assertInstanceOf(City::class, $city);

        $this->assertSame(self::CITY_NAME, $city->Name);
    }

    public function testFirst(): void
    {
        $city = City::first(['Name' => self::CITY_NAME]);

        $this->assertNotNull($city);

        $this->assertInstanceOf(City::class, $city);
    }

    public function testSelect(): void
    {
        $model = new City();

        $model->select(['CountryCode', 'Population']);
        $model->where('Name', '=', self::CITY_NAME);

        $city = $model->getOne();

        $this->assertNotEmpty($city->CountryCode);
        $this->assertNotEmpty($city->Population);
    }

    public function testCreate(): City
    {
        $city = City::first(['Name' => self::CITY_NAME]);

        $this->assertNotNull($city);

        $this->assertInstanceOf(City::class, $city);

        unset($city->ID);

        $city->Name .= microtime();

        $city->save('ID');

        $this->assertNotEmpty($city->ID);

        return $city;
    }

    public function testUpdate(): void
    {
        Connection::getInstance()->executeSql('DELETE FROM city WHERE Name = :Name', ['Name' => __FUNCTION__]);

        $city = $this->testCreate();

        $city->Name = __FUNCTION__;

        $city->save('ID');

        $city2 = City::first(['Name' => __FUNCTION__]);

        $this->assertNotEmpty($city2);

        $this->assertSame($city->ID, $city2->ID);
    }

    public function testDelete(): void
    {
        Connection::getInstance()->executeSql('DELETE FROM city WHERE Name = :Name', ['Name' => __FUNCTION__]);

        $city = $this->testCreate();

        $city->Name = __FUNCTION__;

        $city->save('ID');

        $city->delete('ID');

        $city = City::first(['Name' => __FUNCTION__]);

        $this->assertEmpty($city);
    }

    public function testToJson(): void
    {
        $city = $this->testCreate();
        $city->Name = __FUNCTION__;

        $json = json_encode($city);
        $array = json_decode($json, true);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('Name', $array);
        $this->assertSame(__FUNCTION__, $array['Name']);
    }
}
