<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception};

class MariaDB implements Server
{
	protected $conf;

	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
	}

	public function init()
	{
		echo "mariadb init";
	}

	public function up()
	{
		echo "mariadb up";
	}

	public function down()
	{
		echo "mariadb down";
	}
}

