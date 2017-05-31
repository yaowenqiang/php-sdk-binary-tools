<?php

namespace symfony_demo;

use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Config;
use SDK\{Config as SDKConfig, Exception, FileOps};
use SDK\Build\PGO\Tool;

class TrainingCaseHandler extends Abstracts\TrainingCase implements Interfaces\TrainingCase
{
	protected $conf;
	protected $base;
	protected $nginx;
	protected $php;

	public function __construct(Config $conf, ?Interfaces\Server $nginx, ?Interfaces\Server\DB $srv_db)
	{
		if (!$nginx) {
			throw new Exception("Invalid NGINX object");
		}

		$this->conf = $conf;
		$this->base = $this->conf->getCaseWorkDir($this->getName());
		$this->nginx = $nginx;
		$this->php = $nginx->getPhp();
	}

	public function getName() : string
	{
		return __NAMESPACE__;
	}

	public function getJobFilename() : string
	{
		return $this->conf->getJobDir() . DIRECTORY_SEPARATOR . $this->getName() . ".txt";
	}

	protected function getToolFn() : string
	{
		return $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "symfony";
	}

	protected function setupDist() : void
	{
		if (!file_exists($this->getToolFn())) {
			$url = "https://symfony.com/installer";

			echo "Fetching '$url'\n";
			$this->download($url, $this->getToolFn());
		}

		if (!is_dir($this->conf->getCaseWorkDir($this->getName()))) {
			echo "Setting up in '{$this->base}'\n";
			$php = new PHP\CLI($this->conf);
			$php->exec($this->getToolFn() . " demo " . $this->base);
		}

		$port = $this->conf->getSectionItem($this->getName(), "port");
		if (!$port) {
			$port = $this->conf->getNextPort();
			$this->conf->setSectionItem($this->getName(), "port", $port);
		}

		$vars = array(
			$this->conf->buildTplVarName($this->getName(), "docroot") => str_replace("\\", "/", $this->base . DIRECTORY_SEPARATOR . "web"),
		);
		$tpl_fn = $this->conf->getCasesTplDir($this->getName()) . DIRECTORY_SEPARATOR . "nginx.partial.conf";
		$this->nginx->addServer($tpl_fn, $vars);
	}

	public function setupUrls()
	{
		$this->nginx->up();
		$this->php->up();

		$url = "http://" . $this->conf->getSectionItem($this->getName(), "host") . ":" . $this->conf->getSectionItem($this->getName(), "port") . "/en/blog/";
		$s = file_get_contents($url);

		$this->nginx->down();
		$this->php->down();

		echo "Generating training urls.\n";

		$lst = array();
		if (preg_match_all(", href=\"([^\"]+)\",", $s, $m)) {
			foreach ($m[1] as $u) {
				if ("/" == $u[0] && !in_array(substr($u, -3), array("css", "xml", "ico"))) {
					$ur = "http://" . $this->conf->getSectionItem($this->getName(), "host") . ":" . $this->conf->getSectionItem($this->getName(), "port") . $u;
					$lst[] = $ur;
				}
			}
		}
		$lst = array_unique($lst);

		$fn = $this->getJobFilename();
		if (!file_put_contents($fn, implode("\n", $lst))) {
			throw new Exception("Couldn't write '$fn'.");
		}
	}

	public function init() : void
	{
		echo "\nInitializing " . $this->getName() . ".\n";

		$this->setupDist();
		$this->setupUrls();

		echo $this->getName() . " initialization done.\n";
	}
}


