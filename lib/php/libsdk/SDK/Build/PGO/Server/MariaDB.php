<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server\DB;
use SDK\Build\PGO\Abstracts\Server;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};

class MariaDB extends Server implements DB
{
	use FileOps;

	protected $conf;
	protected $base;
	protected $name = "MariaDB";

	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
		$this->base = $conf->getSrvDir(strtolower($this->name));
	}

	protected function getDist() : void
	{
		$url = $this->conf->getSectionItem($this->name, "pkg_url");
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
		echo "Initializing " . $this->name . ".\n";

		if (!is_dir($this->base)) {
			$this->getDist();
		}
		$this->setupDist();

		$this->up();
		$this->down(true);

		echo $this->name . " initialization done.\n";
	}

	public function up() : void
	{
		echo "Starting " . $this->name . ".\n";

		$cwd = getcwd();

		chdir($this->base);

		$h = popen("start /b .\\bin\\mysqld.exe >nul 2>&1", "r");
		/* XXX error check*/
		pclose($h);

		chdir($cwd);

		echo $this->name . " started.\n";
	}

	public function down(bool $force = false) : void
	{
		echo "Stopping " . $this->name . ".\n";

		$cwd = getcwd();

		chdir($this->base);

		$user = $this->conf->getSectionItem($this->name, "user");
		$pass = $this->conf->getSectionItem($this->name, "pass");

		$cmd = sprintf(".\\bin\\mysqladmin.exe -u $user %s--shutdown_timeout=0 shutdown", ($pass ? "-p$pass " : ""));
		exec($cmd);

		if ($force) {
			sleep(1);
			exec("taskkill /f /im nginx.exe >nul 2>&1");
		}

		chdir($cwd);

		echo $this->name . " stopped.\n";
	}

	public function query(string $s)
	{
		$ret = NULL;

		$cwd = getcwd();

		chdir($this->base);

		$user = $this->conf->getSectionItem($this->name, "user");
		$pass = $this->conf->getSectionItem($this->name, "pass");
		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$pass_arg = $pass ? "-p$pass " : "";
		$ret = shell_exec(".\bin\mysql.exe -u $user $pass_arg -h $host -P $port -e \"$s\"");

		chdir($cwd);
	}
}

