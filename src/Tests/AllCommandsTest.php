<?php

namespace Razorpay\EC2Metadata\Tests;

class AllCommandsTest extends \PHPUnit_Framework_TestCase
{

    private $ec2metadataGetter;

    public function setUp()
    {

        $this->ec2metadataGetter = new \Razorpay\EC2Metadata\Mock\VirtualEc2MetadataGetter(\Razorpay\EC2Metadata\Mock\DummyMetadata::$dummyMetadata);
    }

    /**
     * @test
     */
    public function getAmiIdTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getAmiId(), 'ami-12345678');
    }

    /**
     * @test
     */
    public function getAmiLaunchIndexTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getAmiLaunchIndex(), '0');
    }

    /**
     * @test
     */
    public function getAmiManifestPathTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getAmiManifestPath(), '(unknown)');
    }

    /**
     * @test
     * There is no mock for this test, and it acts as a test
     * against any missing resources as well.
     */
    public function getAncestorAmiIdsTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getAncestorAmiIds(), false);
    }

    /**
     * @test
     */
    public function getBlockDeviceMappingTest()
    {

        $blockDeviceMapping = $this->ec2metadataGetter->getBlockDeviceMapping();
        $this->assertEquals($blockDeviceMapping['ebs0'], 'sda');
        $this->assertEquals($blockDeviceMapping['ephemeral0'], 'sdb');
        $this->assertEquals($blockDeviceMapping['root'], '/dev/sda1');
    }

    /**
     * @test
     */
    public function getHostnameTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getHostname(), 'ip-10-123-123-123.ap-northeast-1.compute.internal');
    }

    /**
     * @test
     */
    public function getInstanceActionTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getInstanceAction(), 'none');
    }

    /**
     * @test
     */
    public function getInstanceIdTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getInstanceId(), 'i-87654321');
    }

    /**
     * @test
     */
    public function getInstanceTypeTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getInstanceType(), 't1.micro');
    }

    /**
     * @test
     */
    public function getKernelIdTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getKernelId(), 'aki-12345678');
    }

    /**
     * @test
     */
    public function getLocalHostnameTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getLocalHostname(), 'ip-10-123-123-123.ap-northeast-1.compute.internal');
    }

    /**
     * @test
     */
    public function getLocalIpv4Test()
    {

        $this->assertEquals($this->ec2metadataGetter->getLocalIpv4(), '10.123.123.123');
    }

    /**
     * @test
     */
    public function getMacTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getMac(), '11:22:33:44:55:66');
    }

    /**
     * @test
     */
    public function getMetricsTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getMetrics(), '<?xml version="1.0" encoding="UTF-8"?>');
    }

    /**
     * @test
     */
    public function getNetworkTest()
    {

        $network = $this->ec2metadataGetter->getNetwork();
        $interfaces = $network['11:22:33:44:55:66'];
        $this->assertEquals($interfaces['device-number'], '0');
        $this->assertEquals($interfaces['local-hostname'], 'ip-10-123-123-123.ap-northeast-1.compute.internal');
        $this->assertEquals($interfaces['local-ipv4s'], '10.123.123.123');
        $this->assertEquals($interfaces['mac'], '11:22:33:44:55:66');
        $this->assertEquals($interfaces['owner-id'], '123456789012');
        $this->assertEquals($interfaces['public-hostname'], 'ec2-12-34-56-78.ap-northeast-1.compute.amazonaws.com');
        $this->assertEquals($interfaces['public-ipv4s'], '12.34.56.78');
    }

    /**
     * @test
     */
    public function getPlacementTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getPlacement(), 'ap-northeast-1c');
    }

    /**
     * @test
     */
    public function getProductCodesTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getProductCodes(), 'abcdefghijklmnopqrstuvwxy');
    }

    /**
     * @test
     */
    public function getProfileTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getProfile(), 'default-paravirtual');
    }

    /**
     * @test
     */
    public function getPublicIpv4Test()
    {

        $this->assertEquals($this->ec2metadataGetter->getPublicIpv4(), '12.34.56.78');
    }

    /**
     * @test
     */
    public function getPublicKeysTest()
    {

        $publicKeys = $this->ec2metadataGetter->getPublicKeys();
        $publicKey = $publicKeys[0];
        $this->assertEquals($publicKey['keyname'], 'my-public-key');
        $this->assertEquals($publicKey['index'], '0');
        $this->assertEquals($publicKey['format'], 'openssh-key');
        $this->assertEquals($publicKey['key'], 'ssh-rsa hogefuga my-public-key');

        $publicKey = $publicKeys[1];
        $this->assertEquals($publicKey['keyname'], 'hoge-key');
        $this->assertEquals($publicKey['index'], '1');
        $this->assertEquals($publicKey['format'], 'openssh-key');
        $this->assertEquals($publicKey['key'], 'ssh-rsa hogefugahogefuga hoge-key');
    }

    /**
     * @test
     */
    public function getRamdiskIdTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getRamdiskId(), 'ari-abcdefgh');
    }

    /**
     * @test
     */
    public function getReservationIdTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getReservationId(), 'r-1234abcd');
    }

    /**
     * @test
     */
    public function getSecurityGroupsTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getSecurityGroups(), 'securitygroups');
    }

    /**
     * @test
     */
    public function getServicesTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getServices(), 'amazonaws.com');
    }

    /**
     * @test
     */
    public function getUserDataTest()
    {

        $this->assertEquals($this->ec2metadataGetter->getUserData(), 'this is userdata');
    }

}
