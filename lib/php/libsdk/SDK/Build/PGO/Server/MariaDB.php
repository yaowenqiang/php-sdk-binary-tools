<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server;

class MariaDB implements Server\DB
{
	public function __construct(string $host, string $port, string $user, string $pw)
	{

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

