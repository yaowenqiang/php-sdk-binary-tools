<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server;

class NGINX implements Server\HTTP
{
	public function __construct(string $host, string $port)
	{

	}

	public function init()
	{
		echo "nginx init";
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

