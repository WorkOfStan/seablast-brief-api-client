<?php

namespace Seablast\BriefApiClient\Test;

use Seablast\BriefApiClient\BriefApiClient;

class BriefApiClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var BriefApiClient */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        error_reporting(E_ALL); // incl E_NOTICE
        $apiUrl = 'https://raw.githubusercontent.com/WorkOfStan/seablast-brief-api-client/develop/Test/test.json';
        $this->object = new BriefApiClient($apiUrl);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        // no action
    }

    /**
     * @covers Seablast\BriefApiClient\BriefApiClient::getArrayArray
     * TODO create service, that would actually reply
     *
     * @return void
     */
    public function testGetArrayArray()
    {
        $expected = array('test' => 'Lorem');
        $this->assertEquals($expected, $this->object->getArrayArray($expected));
    }
}
