<?php

namespace SDK\Build\PGO\Server\PHP;

use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};

class CLI extends Abstracts\PHP implements Interfaces\PHP
{
	protected $conf;
	protected $scenario;

	public function __construct(PGOConfig $conf, string $scenario)
	{
		$this->conf = $conf;
		$this->scenario = $scenario;

		$this->setupPaths();
	}

	public function getExeFilename() : string
	{
		$exe = $this->getRootDir() . DIRECTORY_SEPARATOR . "php.exe";

		if (!file_exists($exe)) {
			throw new Exception("Path '$exe' doesn't exist.");
		}

		return $exe;
	}
}

