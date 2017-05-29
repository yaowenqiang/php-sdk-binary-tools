<?php

namespace SDK\Build\PGO\Server\PHP;

use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};

class FCGI extends Abstracts\PHP implements Interfaces\PHP
{
	protected $conf;
	protected $srv_db;
	protected $srv_http;
	protected $is_tcp;
	protected $scenario;

	public function __construct(PGOConfig $conf, bool $is_tcp, Interfaces\Server $srv_db, Interfaces\Server $srv_http, string $scenario)
	{
		if (!$is_tcp) {
			throw new Exception("FCGI training other than through TCP is not implemented yet.");
		}

		$this->conf = $conf;
		$this->srv_db = $srv_db;
		$this->srv_http = $srv_http;
		$this->is_tcp = $is_tcp;
		$this->scenario = $scenario;

		$this->setupPaths();
	}

	public function getExeFilename() : string
	{
		$exe = $this->getRootDir() . DIRECTORY_SEPARATOR . "php-cgi.exe";

		if (!file_exists($exe)) {
			throw new Exception("Path '$exe' doesn't exist.");
		}

		return $exe;
	}

	protected function createEnv() : array
	{
		$env = parent::createEnv();

		$fcgi_env = (array)$this->conf->getSectionItem("php", "fcgi:env");

		foreach ($fcgi_env as $k => $v) {
			$env[$k] = $v;
		}

		return $env;
	}

	public function init()
	{
		echo "Using PHP from the object dir.\n";
	}

	public function up()
	{
		echo "Starting PHP FCGI.\n";

		$exe  = $this->getExeFilename();
		$ini  = $this->getIniFilename();
		$host = $this->conf->getSectionItem("php", "fcgi", "host");
		$port = $this->conf->getSectionItem("php", "fcgi", "port");

		$cmd = "start /b $exe -n -c $ini -b $host:$port";

		/* XXX Log something, etc. */
		$p = proc_open($cmd, $dummy = array(), $dummy, $this->getRootDir(), $this->createEnv());
		proc_close($p);
	}

	public function down()
	{
		echo "Stopping PHP FCGI.\n";

		exec("taskkill /f /im php-cgi.exe >nul 2>&1");
	}
}

