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

	protected function getDist()
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

	protected function setupDist()
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
				$this->base . DIRECTORY_SEPARATOR . "html",
				$this->conf->getSectionItem("php", "fcgi", "host"),
				$this->conf->getSectionItem("php", "fcgi", "port"),
			),
			$in
			
		);
		var_dump($out);
	}

	public function init()
	{
		echo "Initializing NGINX.\n";
		if (!is_dir($this->base)) {
			$this->getDist();
		}
		$this->setupDist();
		echo "NGINX initialization done.\n";
	}

	public function up()
	{
		echo "nginx up";
	}

	public function down()
	{
		echo "nginx down";
	}
}

