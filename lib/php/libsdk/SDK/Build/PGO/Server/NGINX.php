<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};

class NGINX implements Server
{
	use FileOps;

	protected $conf;
	protected $base;

	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
		$this->base = $conf->getSrvDir("nginx");
	}

	protected function getDist() : void
	{
		$url = "http://nginx.org/download/nginx-1.13.0.zip";
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

	protected function setupDist() : void
	{
		$nginx_conf_in = $this->conf->getTplDir("nginx") . DIRECTORY_SEPARATOR . "nginx.conf";
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
				str_replace("\\", "/", $this->base . DIRECTORY_SEPARATOR . "html"),
				$this->conf->getSectionItem("php", "fcgi", "host"),
				$this->conf->getSectionItem("php", "fcgi", "port"),
			),
			$in
			
		);

		$conf_fn = $this->base . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
		if (!file_put_contents($conf_fn, $out)) {
			throw new Exception("Couldn't write '$conf_fn'.");
		}
	}

	public function init() : void
	{
		echo "Initializing NGINX.\n";
		if (!is_dir($this->base)) {
			$this->getDist();
		}
		$this->setupDist();

		$this->up();
		$this->down();


		echo "NGINX initialization done.\n";
	}

	public function up() : void
	{
		echo "Starting NGINX.\n";

		$cwd = getcwd();

		chdir($this->base);

		$h = popen("start /b .\\nginx.exe", "r");
		/* XXX error check*/
		pclose($h);

		chdir($cwd);
	}

	public function down(bool $force = false) : void
	{
		echo "Stopping NGINX.\n";

		$cwd = getcwd();

		chdir($this->base);

		exec(".\\nginx.exe -s quit");

		if ($force) {
			sleep(1);
			exec("taskkill /f /im nginx.exe >nul 2>&1");
		}

		chdir($cwd);
	}

	/* Use only for init phase! */
	public function addServer(string $part_tpl_fn, array $tpl_vars)
	{
		if (!file_exists($part_tpl_fn)) {
			throw new Exception("Template file '$part_tpl_fn' doesn't exist.");
		}

		/* We've already did a fresh (re)config, so use the work file now. */
		$nginx_conf_in = $this->base . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
		$cur_conf = file_get_contents($nginx_conf_in);

		$in = file_get_contents($part_tpl_fn);
		$out = str_replace($tpl_vars["names"], $tpl_vars["vals"], $in);

		$tpl = "    # PHP_SDK_PGO_NGINX_SERVERS_INC_TPL";
		$new_conf = str_replace($tpl, "$out\n$tpl", $cur_conf);

		$conf_fn = $this->base . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
		if (!file_put_contents($conf_fn, $new_conf)) {
			throw new Exception("Couldn't write '$conf_fn'.");
		}
	}
}

