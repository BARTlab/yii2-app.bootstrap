<?php

/**
 * New line
 */
defined('NL') or define('NL', "\n");
/**
 * Tabulation
 */
defined('TAB') or define('TAB', "\t");
/**
 * Directory separator
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

class AppBootstrap
{
    /**
     * Application type "web"
     */
    const APP_WEB = 'web';

    /**
     * Application type "console"
     */
    const APP_CONSOLE = 'console';

    /**
     * Application type "auto"
     */
    const APP_AUTO = 'auto';

    /**
     * Environment "dev"
     */
    const ENV_DEV = 'dev';

    /**
     * Environment "prod"
     */
    const ENV_PROD = 'prod';

    /**
     * Environment "auto"
     */
    const ENV_AUTO = 'auto';

    /**
     * Debug mode
     * @var bool
     */
    protected $debug = false;

    /**
     * Environment "dev", "prod" or "auto".
     * If set "auto" and app type is "web",
     * then set "dev" environment for connection from $allowedIPs list
     * @var string
     */
    protected $env = self::ENV_AUTO;

    /**
     * Application name
     * @var string
     */
    protected $name = 'app';

    /**
     * Base application directory
     * @var string
     */
    protected $baseDir = __DIR__;

    /**
     * Vendor directory
     * @var string
     */
    protected $vendorDir = __DIR__ . '/vendor';

    /**
     * Config for bootstrap class
     * @var string
     */
    protected $bootConfig = __DIR__ . '/config/bootstrap.php';

    /**
     * Type "web", "console" or "auto".
     * If set "auto", try detect by php_sapi_name
     * @var string
     */
    protected $type = self::APP_AUTO;

    /**
     * Compiled local config
     * @var array
     */
    protected $_local;

    /**
     * Compiled application config
     * @var array
     */
    protected $_config;

    /**
     * Aliases list for yii
     * @var array
     */
    protected $aliases = [];

    /**
     * Base files, that load on bootstrap class create
     * @var array
     */
    protected $baseFiles = [
        'yii' => 'vendor/yiisoft/yii2/Yii.php'
    ];

    /**
     * Config files for application.
     * May use tempalte in file name: {name}, {env}, {type}
     * @var array
     */
    protected $configFiles = [
        'config/common.php',
        'config/{name}.php',
    ];

    /**
     * Local config files
     * @var array
     */
    protected $localConfigFiles = [
        'config/local.php'
    ];

    /**
     * Class name for web application
     * @var string
     */
    protected $webClassName = 'yii\web\Application';

    /**
     * Class name for console application
     * @var string
     */
    protected $consoleClassName = 'yii\console\Application';

    /**
     * Callback for "init" event
     * @var null|callable
     */
    protected $onInit;

    /**
     * Callback for "before run" event
     * @var null|callable
     */
    protected $onBeforeRun;

    /**
     * Allowed IP's for detect application environment.
     * May use for debug bar config
     * @var array
     */
    protected $allowedIPs = [
        '127.0.0.1',
        '::1',
        '192.168.0.*',
        '192.168.1.*'
    ];

    /**
     * Bootstrap constructor.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($config = [])
    {
        foreach ($config as $name => $value) {
            $this->writePropperty($name, $value);
        }

        // vendors
        $autoload = $this->getFilename($this->vendorDir . '/autoload.php', false);
        if (!file_exists($autoload)) {
            die('Vendors not installed');
        }
        require $autoload;

        // bootstrap config
        $bcFilename = $this->getFilename($this->bootConfig, false);
        if (file_exists($bcFilename)) {
            $bootConfig = require $bcFilename;
            foreach ($bootConfig as $name => $value) {
                $this->writePropperty($name, $value);
            }
        }

        // bootstrap local config
        foreach ($this->getLocalConfig('bootstrap') as $name => $value) {
            $this->writePropperty($name, $value);
        }

        // set global constatnts
        $this->setDefine();

        // load base files
        foreach ($this->getBaseFiles() as $file) {
            $filename = $this->getFilename($file);
            if (file_exists($filename)) {
                require $filename;
            }
        }
        $this->init();
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
        $this->trigger('onInit', [$this]);
    }

    /**
     * Returns the value of an object property.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $object->property;`.
     * @param string $name the property name
     * @return mixed the property value
     * @throws Exception if the property is not defined
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            throw new \Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Run application
     * @return integer exit code (0 by default)
     */
    public function run()
    {
        // set yii aliases
        foreach ($this->getAliases() as $alias => $path) {
            Yii::setAlias($alias, $path);
        }

        // load app config
        $config = yii\helpers\ArrayHelper::merge(
            $this->getConfig(),
            $this->getLocalConfig('common'),
            $this->getLocalConfig($this->name)
        );

        // get application class name and call "before run" event
        $classname = $this->applicationClassName();
        $this->trigger('onBeforeRun', [$this, &$config, &$classname]);

        return (new $classname($config))->run();
    }

    /**
     * Triggers an event.
     * @param string $event event name
     * @param array $params event parameter
     */
    public function trigger($event, $params = [])
    {
        if (isset($this->$event) && is_callable($this->$event)) {
            call_user_func_array($this->$event, $params);
        }
    }

    /**
     * Get application name by current type
     * @return mixed
     */
    public function applicationClassName()
    {
        $prop = $this->getType() . 'ClassName';
        if (is_array($prop)) {
            return $prop[$this->name];
        }
        return $this->{$prop};
    }

    /**
     * Get file path with correct directory separator and base dir, if needed
     * @param string $path file path
     * @param bool $baseDir add base dir to file path
     * @return string
     */
    public function getFilename($path, $baseDir = true)
    {
        return str_replace('/', DS, ($baseDir ? $this->baseDir . '/' : '') . $path);
    }

    /**
     * Get application type
     * @return string
     */
    public function getType()
    {
        if ($this->type == self::APP_AUTO) {
            return (php_sapi_name() == "cli" ? self::APP_CONSOLE : self::APP_WEB);
        }

        return $this->type;
    }

    /**
     * Get application environment
     * @return string
     */
    public function getEnv()
    {
        if ($this->env == self::ENV_AUTO) {
            return $this->checkLocalHost() ? self::ENV_DEV : self::ENV_PROD;
        }

        return $this->env;
    }

    /**
     * Check allow connection by ip for web application
     * @return bool
     */
    protected function checkLocalHost()
    {
        // console application return false
        if ($this->getType() != self::APP_CONSOLE) {
            $ip = $_SERVER['REMOTE_ADDR'];
            foreach ($this->allowedIPs as $filter) {
                if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Set global constants
     */
    public function setDefine()
    {
        defined('YII_DEBUG') or define('YII_DEBUG', $this->debug);
        defined('YII_ENV') or define('YII_ENV', $this->getEnv());

        if ($this->type == self::APP_CONSOLE) {
            defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
            defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));
        }
    }

    /**
     * Get application config
     * @return array
     */
    public function getConfig()
    {
        if (!$this->_config) {
            $this->_config = $this->mergeFiles(
                $this->getConfigFiles(),
                [
                    'basePath' => $this->getFilename('apps/' . $this->name),
                    'vendorPath' => $this->vendorDir,
                    'id' => $this->name,
                    'name' => $this->name
                ]
            );
        }

        return $this->_config;
    }

    /**
     * Get param from local config
     * @param null|string $section param name. If not set, return full config
     * @param mixed $sectionDefault default value if param not found
     * @return array|mixed
     */
    public function getLocalConfig($section = null, $sectionDefault = [])
    {
        if (!$this->_local) {
            $this->_local = $this->mergeFiles($this->getLocalConfigFiles(), []);
        }

        return $section ? \yii\helpers\ArrayHelper::getValue($this->_local, $section, $sectionDefault) : $this->_local;
    }

    /**
     * Merge array from files
     * @param array $files files list
     * @param array $result base array for merge
     * @return array
     */
    public function mergeFiles($files, $result = [])
    {
        foreach ($files as $file) {
            $filename = $this->getFilename($file);
            if (file_exists($filename)) {
                $result = yii\helpers\ArrayHelper::merge($result, require($filename));
            }
        }

        return $result;
    }

    /**
     * Get base files
     * @return array
     */
    public function getBaseFiles()
    {
        return $this->readProperty('baseFiles');
    }

    /**
     * Get config files
     * @return array
     */
    public function getConfigFiles()
    {
        return $this->readProperty('configFiles');
    }

    /**
     * Get local config files
     * @return array|mixed|string
     */
    public function getLocalConfigFiles()
    {
        return $this->readProperty('localConfigFiles');
    }

    /**
     * Get aliases
     * @return array
     */
    public function getAliases()
    {
        return $this->strtr(
            array_merge(
                [
                    'root' => $this->baseDir
                ],
                $this->readProperty('aliases')
            )
        );
    }

    /**
     * Get property value. Propery may be callable
     * @param string $name property name
     * @param bool $strtr replace pairs for value
     * @return mixed
     */
    public function readProperty($name, $strtr = true)
    {
        $result = is_callable($this->{$name}) ?
            call_user_func($this->{$name}, $this) :
            $this->{$name};

        return $strtr ? $this->strtr($result) : $result;
    }

    /**
     * Set or merge property value
     * @param string $name property name
     * @param mixed $value property value
     * @param bool $merge merge value
     */
    public function writePropperty($name, $value, $merge = true)
    {
        if (is_array($value) && $merge) {
            $this->{$name} = array_merge((array)$this->{$name}, $value);
        } else {
            $this->{$name} = $value;
        }
    }

    /**
     * Run replace pairs for input var
     * @param mixed $var input var
     * @return array|string
     */
    public function strtr($var)
    {
        return is_array($var) ?
            array_map(function ($value) {
                return strtr($value, $this->replacePairs());
            }, $var) :
            strtr($var, $this->replacePairs());
    }

    /**
     * Get pairs
     * @return array
     */
    public function replacePairs()
    {
        return [
            '{type}' => $this->getType(),
            '{env}' => $this->getEnv(),
            '{name}' => $this->name,
        ];
    }

}
