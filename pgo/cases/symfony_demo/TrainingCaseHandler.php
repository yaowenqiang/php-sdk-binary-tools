<?php

namespace symfony_demo;

use SDK\Build\PGO\Interfaces\TrainingCase;
use SDK\Build\PGO\Config;
use SDK\Build\PGO\Server\{PHP, NGINX};
use SDK\{Config as SDKConfig, Exception, FileOps};

class TrainingCaseHandler implements TrainingCase
{
	use FileOps;

	protected $conf;
	protected $base;

	public function __construct(Config $conf)
	{
		$this->conf = $conf;
		$this->base = $this->conf->getCaseWorkDir("symfony_demo");
	}

	protected function getToolFn() : string
	{
		return $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "symfony.phar";
	}

	protected function setupDist() : void
	{
		if (!file_exists($this->getToolFn())) {
			$url = "https://symfony.com/installer";

			echo "Fetching '$url'\n";
			$this->download($url, $this->getToolFn());
		}

		if (!is_dir($this->conf->getCaseWorkDir("symfony_demo"))) {
			$php = new PHP\CLI($this->conf);
			$php->exec($this->getToolFn() . " demo " . $this->base);
		}


		$nginx = new NGINX($this->conf);
		$tpl_fn = $this->conf->getCasesTplDir("symfony_demo") . DIRECTORY_SEPARATOR . "nginx.partial.conf";
		$nginx->addServer($tpl_fn,
			array(
				"names" => array(
					"PHP_SDK_PGO_SYMFONY_DEMO_PORT",
					"PHP_SDK_PGO_SYMFONY_DEMO_HOST",
					"PHP_SDK_PGO_SYMFONY_DEMO_DOCROOT",
					"PHP_SDK_PGO_FCGI_HOST",
					"PHP_SDK_PGO_FCGI_PORT",	
				),
				"vals" => array(
					$this->conf->getSectionItem("symfony_demo", "port"),
					$this->conf->getSectionItem("symfony_demo", "host"),
					str_replace("\\", "/", $this->base . DIRECTORY_SEPARATOR . "web"),
					$this->conf->getSectionItem("php", "fcgi", "host"),
					$this->conf->getSectionItem("php", "fcgi", "port"),
				),
			)
		);
	}

	public function init()
	{
		echo "Initializing " . __NAMESPACE__ . ".\n";

		$this->setupDist();
		//$this->gatherUrls();

		echo __NAMESPACE__ . " initialization done.\n";
	}
}


