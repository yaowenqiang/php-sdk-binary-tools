<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};

class MariaDB implements Server
{
	use FileOps;

	protected $conf;
	protected $base;

	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
		$this->base = $conf->getSrvDir("mariadb");
	}

	protected function getDist() : void
	{
		$url = "https://downloads.mariadb.com/MariaDB/mariadb-5.5.56/win32-packages/mariadb-5.5.56-win32.zip";
		$bn = basename($url);
		$dist = SDKConfig::getTmpDir() . DIRECTORY_SEPARATOR . $bn;

		echo "Fetching '$url'\n";
		$this->download($url, $dist);

		echo "Unpacking to '{$this->base}'\n";
		try {
			$this->unzip($dist, $this->conf->getSrvDir());
		} catch (Throwable $e) {
			unlink($dist);
			throw $e;
		}

		$src_fn = $this->conf->getSrvDir() . DIRECTORY_SEPARATOR . basename($bn, ".zip");
		if (!rename($src_fn, $this->base)) {
			unlink($dist);
			throw new Exception("Failed to rename '$src_fn' to '{$this->base}'");
		}

		unlink($dist);
	}

	protected function setupDist()
	{
		/*$nginx_conf_in = $this->conf->getTplDir("nginx") . DIRECTORY_SEPARATOR . "nginx.conf";
		$in = file_get_contents($nginx_conf_in);
		$out = str_replace(
			array(
				"PHP_SDK_PGO_NGINX_HOST",
				"PHP_SDK_PGO_NGINX_PORT",
				"PHP_SDK_PGO_NGINX_DOCROOT",
				"PHP_SDK_PGO_FCGI_HOST",
				"PHP_SDK_PGO_FCGI_PORT",
			),
			array(
				$this->conf->getSectionItem("nginx", "host"),
				$this->conf->getSectionItem("nginx", "port"),
				$this->base . DIRECTORY_SEPARATOR . "html",
				$this->conf->getSectionItem("php", "fcgi", "host"),
				$this->conf->getSectionItem("php", "fcgi", "port"),
			),
			$in
			
		);

		$conf_fn = $this->base . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
		if (!file_put_contents($conf_fn, $out)) {
			throw new Exception("Couldn't write '$conf_fn'.");
		}*/
	}

	public function init() : void
	{
		echo "Initializing MariaDB.\n";

		if (!is_dir($this->base)) {
			$this->getDist();
		}
		$this->setupDist();

		$this->up();
		$this->down();

		echo "MariaDB initialization done.\n";
	}

	public function up() : void
	{
		echo "Starting MariaDB.\n";

		$cwd = getcwd();

		chdir($this->base);

		$h = popen("start /b .\\bin\\mysqld.exe >nul 2>&1", "r");
		/* XXX error check*/
		pclose($h);

		chdir($cwd);
	}

	public function down() : void
	{
		echo "Stopping MariaDB.\n";

		$cwd = getcwd();

		chdir($this->base);

		$user = $this->conf->getSectionItem("mariadb", "user");
		$pass = $this->conf->getSectionItem("mariadb", "pass");

		$cmd = sprintf(".\\bin\\mysqladmin.exe -u $user %s--shutdown_timeout=0 shutdown", ($pass ? "-p$pass " : ""));
		exec($cmd);

		chdir($cwd);
	}
}

