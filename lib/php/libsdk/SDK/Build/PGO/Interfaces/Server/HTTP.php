<?php

namespace SDK\Build\PGO\Interfaces\Server;

interface HTTP extends Server
{
	public function __construct(string $host, string $port);
}
