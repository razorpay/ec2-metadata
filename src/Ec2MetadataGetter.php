<?php

namespace Razorpay\EC2Metadata;

/**
 * Ec2MetadataGetter uses file_get_contents to query the EC2 instance Metadata from within a running EC2 instance.
 *
 * see: http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AESDG-chapter-instancedata.html
 */
class Ec2MetadataGetter
{

    /**
     * read from http scheme
     *
     * @var string
     */
    protected $scheme = 'http';

    /**
     * to view instance metadata from within a running instance, use this
     *
     * @var string
     */
    protected $hostname = '169.254.169.254';

    /**
     * lookup table of command and meta-data destination
     *
     * @var array
     */
    private $commands = [
            'AmiId'              => 'ami-id',
            'AmiLaunchIndex'     => 'ami-launch-index',
            'AmiManifestPath'    => 'ami-manifest-path',
            'AncestorAmiIds'     => 'ancestor-ami-ids',
            'BlockDeviceMapping' => 'block-device-mapping',
            'Hostname'           => 'hostname',
            'InstanceAction'     => 'instance-action',
            'InstanceId'         => 'instance-id',
            'InstanceType'       => 'instance-type',
            'KernelId'           => 'kernel-id',
            'LocalHostname'      => 'local-hostname',
            'LocalIpv4'          => 'local-ipv4',
            'Mac'                => 'mac',
            'Metrics'            => 'metrics/vhostmd',
            'Network'            => 'network/interfaces/macs',
            'Placement'          => 'placement/availability-zone',
            'ProductCodes'       => 'product-codes',
            'Profile'            => 'profile',
            'PublicHostname'     => 'public-hostname',
            'PublicIpv4'         => 'public-ipv4',
            'PublicKeys'         => 'public-keys',
            'RamdiskId'          => 'ramdisk-id',
            'ReservationId'      => 'reservation-id',
            'SecurityGroups'     => 'security-groups',
            'Services'           => 'services/domain',
            'UserData'           => 'user-data'
    ];

    /**
     * http connections time out after 0.1 seconds
     *
     * @var float
     */
    const HTTP_TIMEOUT = 0.1;

    /**
     * be used when assembling metadata path
     *
     * @var string
     */
    const METADATA = 'meta-data';

    /**
     * when not available metadata, display this message.
     *
     * @var string
     */
    const NOT_AVAILABLE = 'not available';

    /**
     * Whether to return dummy data or not
     * @var boolean
     */
    private $dummy = false;

    public function __construct($cache_dir = "/tmp")
    {
        $this->cache_dir = $cache_dir;

        // Make sure that it is writeable
        if (is_writeable($this->cache_dir) === false)
        {
            throw new \Exception("Cache directory not writable");
        }
    }

    /**
     * Allows dummy data to be returned instead of raising an exception
     */
    public function allowDummy()
    {
        $this->dummy = true;
    }

    /**
     * Writes the response to the cache file
     * @param  array $attributes array of requested attributes
     * @param  array $response   combined response of the API
     * @return string $filename filename to which the response was cached to
     */
    private function writeCache($attributes, $response)
    {
        $filename = $this->getCacheFile($attributes);
        $data = json_encode($response);
        file_put_contents($filename, $data);

        return $filename;
    }

    /**
     * read the data from the cache and return it
     * @param  array  $attributes array of requested attributes
     * @return array|false return an array of response, or false if file was not found
     */
    private function readCache(array $attributes)
    {
        $filename = $this->getCacheFile($attributes);

        if (is_readable($filename))
        {
            return json_decode($filename);
        }

        return false;

    }

    /**
     * returns the filename for the cache file
     * @param  array  $attributes array of requested attributes
     * @return string $filename fully qualified path of the file
     */
    private function getCacheFile(array $attributes)
    {
        $uniqueRequestId = $this->uniqueRequestId($attributes);
        $filename = $this->cache_dir . DIRECTORY_SEPARATOR . $uniqueRequestId . ".json";

        return $filename;
    }

    /**
     * Returns a unique hash for a set of attributes
     * @param  array  $attributes list of attributes asked for
     * @return string unique hash to use as filename
     */
    private function uniqueRequestId(array $attributes)
    {
        // make array unique (order independent)
        // then serialize and hash it to generate a unique id
        sort($attributes, SORT_STRING);

        return sha1(serialize($attributes));
    }

    /**
     * e.g.
     * $blockDeviceMapping = [
     *          'ebs0' => 'sda',
     *          'ephemeral0' => 'sdb',
     *          'root' => '/dev/sda1'
     *  ];
     * @return array
     */
    public function getBlockDeviceMapping()
    {
        $output = [];

        foreach (explode(PHP_EOL, $this->get('BlockDeviceMapping')) as $map)
        {
            $output[$map] = $this->get('BlockDeviceMapping', $map);
        }

        return $output;
    }

    /**
     * e.g.
     * $publicKeys = [
     *         0 => [
     *                 'keyname' => 'my-public-key',
     *                 'index' => '0',
     *                 'format' => 'openssh-key',
     *                 'key' => 'ssh-rsa hogefuga my-public-key'
     *         ]
     * ];
     * @return array
     */
    public function getPublicKeys()
    {
        $keys = [];

        foreach (explode(PHP_EOL, $this->get('PublicKeys')) as $publicKey)
        {
            list($index, $keyname) = explode('=', $publicKey, 2);
            $format = $this->get('PublicKeys', $index);

            $keys[] = [
                    'keyname' => $keyname,
                    'index' => $index,
                    'format' => $format,
                    'key' => $this->get('PublicKeys', sprintf("%s/%s", $index, $format))
            ];
        }

        return $keys;
    }

    /**
     * e.g.
     * $network = [
     *         '11:22:33:44:55:66' => [
     *                 'device-number' => '0',
     *                  'local-hostname' => 'ip-10-123-123-123.ap-northeast-1.compute.internal',
     *                  'local-ipv4s' => '10.123.123.123',
     *                  'mac' => '11:22:33:44:55:66',
     *                  'owner-id' => '123456789012',
     *                  'public-hostname' => 'ec2-12-34-56-78.ap-northeast-1.compute.amazonaws.com',
     *                  'public-ipv4s' => '12.34.56.78'
     *          ]
     *  ];
     * @return array
     */
    public function getNetwork()
    {
        $macList = explode(PHP_EOL, $this->get('Network'));
        $network = [];

        foreach ($macList as $mac)
        {
            $interfaces = [];

            foreach (explode(PHP_EOL, $this->get('Network', $mac)) as $key)
            {
                $interfaces[$key] = $this->get('Network', sprintf("%s/%s", $mac, $key));
            }

            $network[$mac] = $interfaces;
        }

        return $network;
    }

    /**
     * get all instance data using lookup table
     *
     * @return array
     */
    public function getAll()
    {
        $cacheData = $this->readCache(array_keys($this->commands));

        if ($cacheData)
        {
            return $cacheData;
        }

        $result = [];

        foreach (array_keys($this->commands) as $commandName)
        {
            $result[$commandName] = $this->{"get$commandName"}();
        }

        $this->writeCache($this->commands, $result);

        return $result;
    }

    /**
     * returns true if on EC2, otherwise throw RuntimeException
     *
     * @throws \RuntimeException
     * @return true
     */
    public function isRunningOnEc2()
    {
        /**
         * We may fake being on EC2
         */
        if ($this->dummy)
        {
            return true;
        }

        if (!@file_get_contents($this->getLatestInstanceDataPath(), false, $this->getStreamContext(), 1, 1))
        {
            throw new \RuntimeException(
                "[ERROR] Command not valid outside EC2 instance. " .
                "Please run this command within a running EC2 instance or call allowDummy()");
        }

        return true;
    }

    /**
     * read URL by combined commandName and args into an array.
     * return the read data or false on failure.
     * throw RuntimeException if it is not on the EC2.
     *
     * @param string $commandName
     * @param string $args
     * @throws \RuntimeException
     * @return array|false
     */
    public function get($commandName, $args = '')
    {
        $this->isRunningOnEc2();

        if ($this->dummy)
        {
            $command = $this->commands[$commandName];
            $dummy = new Mock\VirtualEc2MetadataGetter(Mock\DummyMetadata::$dummyMetadata);

            return $dummy->get($commandName, $args);
        }
        else
        {
            return @file_get_contents($this->getFullPath($commandName, $args), false, $this->getStreamContext());
        }
    }

    /**
     * read multiple commands and return an array
     * internally calls get
     * @param array $attributes array of attributes to read
     * @return array response
     */

    public function getMultiple(array $attributes)
    {
        $cacheData = $this->readCache(array_keys($attributes));

        if ($cacheData)
        {
            return $cacheData;
        }

        $response = [];

        foreach ($attributes as $attribute)
        {
            $response[$attribute] = $this->{"get$attribute"}();
        }

        $this->writeCache($attributes, $response);

        return $response;
    }

    /**
     * get full path by latest instance data path, commandName and args.
     *
     * @param string $commandName
     * @param string $args
     * @return string
     */
    private function getFullPath($commandName, $args)
    {
        if ($commandName === 'UserData')
        {
            return sprintf(
                "%s/%s",
                $this->getLatestInstanceDataPath(),
                $this->commands['UserData']);
        }

        return sprintf(
            "%s/%s/%s/%s",
            $this->getLatestInstanceDataPath(),
            self::METADATA,
            $this->commands[$commandName], $args);
    }

    /**
     * get latest instance data path combined scheme
     *
     * @return string
     */
    private function getLatestInstanceDataPath()
    {
        return sprintf("%s://%s/latest", $this->scheme, $this->hostname);
    }

    /**
     * get stream_context with setting timeout of http connection
     *
     * @return resource
     */
    private function getStreamContext()
    {
        $context = ['http' => ['timeout' => self::HTTP_TIMEOUT]];

        return stream_context_create($context);
    }

    /**
     * try to remove "get" at the beginning of a functionName in the first.
     * calling get function if there is a command in $this->commands.
     * otherwise throw LogicException.
     *
     * @param string $functionName
     * @param string $args
     * @throws \LogicException
     * @return array|false
     */
    public function __call($functionName, $args)
    {
        $command = preg_replace('/^get/', '', $functionName);

        if (array_key_exists($command, $this->commands) === false)
        {
            throw new \LogicException("Only get operations allowed.");
        }

        return $this->get($command, array_pop($args));
    }
}
