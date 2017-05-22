<?php

namespace SDK\Build\PGO\Interfaces\Server;

interface DB extends Server
{
	public function __construct(string $host, string $port, string $user, string $pw);
}

