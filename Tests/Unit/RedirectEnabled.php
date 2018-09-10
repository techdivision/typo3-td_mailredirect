<?php

namespace TechDivision\TdMailredirect\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 TechDivision GmbH <info@techdivision.com>
 *
 *  All rights reserved
 *
 ***************************************************************/
class RedirectEnabled extends \Tx_Phpunit_TestCase
{
    /**
     * @test
     * @dataProvider ipProvider
     * @param string $remote
     * @param string $valid
     * @param bool $expected
     */
    public function ipConditionTest(string $remote, string $valid, bool $expected)
    {
        $config = [];
        $emConfiguration = new \TechDivision\TdMailredirect\Domain\Model\Dto\Configuration($config);
        $this->assertEquals($expected, $emConfiguration->isRequestFromAllowedIp($remote, $valid));
    }


    public function ipProvider()
    {
        return [
            ['127.0.0.1', '*', true],
            ['127.0.0.1', '127.0.0.*', true],
            ['127.0.0.1', '127.0.*.*', true],
            ['127.0.0.1', '192.168.1.1', false],
            ['127.0.0.1', '127.0.0.1,192.168.1.1', true],
        ];
    }

    /**
     * @test
     * @dataProvider userAgentProvider
     * @param string $remote
     * @param string $valid
     * @param bool $expected
     */
    public function userAgentTest(string $remote, string $valid, bool $expected)
    {
        $emConfiguration = new \TechDivision\TdMailredirect\Domain\Model\Dto\Configuration([]);
        $this->assertEquals($expected, $emConfiguration->isRequestFromAllowedUserAgent($remote, $valid));
    }

    public function userAgentProvider()
    {
        return [
            ['foo', '*', true],
            ['foo', 'bar', false]
        ];
    }

    /**
     * @param string $email
     * @param array $whitelisted
     * @param bool $expected
     * @test
     * @dataProvider emailAddressProvider
     */
    public function whitelistedEmailTest(string $email, array $whitelisted, bool $expected)
    {
        $emConfiguration = new \TechDivision\TdMailredirect\Domain\Model\Dto\Configuration([]);
        $this->assertEquals($expected, $emConfiguration->isEmailAddressWhitelisted($email, $whitelisted));
    }

    public function emailAddressProvider()
    {
        return [
            ['foo@example.org', ['foo@example.org'], true],
            ['foo@example.org', ['foo@example.org', 'bar@example.org'], true],
            ['foo@example.org', ['*@example.org'], true],
            ['foo@example.org', ['foo@*'], true],
            ['foo@example.org', ['bar@example.org'], false],
        ];
    }

    /**
     * @param string $email
     * @param string $result
     * @test
     * @dataProvider overrideEmailProvider
     */
    public function overrideEmailTest(string $email, string $result)
    {
        $config = [
            'redirectRule' => 'mailtester+{local}-{domain}-{tld}@example.org'
        ];
        $emConfiguration = new \TechDivision\TdMailredirect\Domain\Model\Dto\Configuration($config);
        $this->assertEquals($result, $emConfiguration->getOverrideAddress($email));
    }

    public function overrideEmailProvider()
    {
        return [
            ['foo@bar.baz', 'mailtester+foo-bar-baz@example.org'],
            ['foo@foo.bar.bar.baz', 'mailtester+foo-foo.bar.bar-baz@example.org'],
        ];
    }
}