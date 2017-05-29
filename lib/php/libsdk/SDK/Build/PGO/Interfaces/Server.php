<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Config;

interface Server
{
	public function __construct(Config $conf);
	public function init() : void;
	public function up() : void;
	public function down(bool $force = false) : void;
}

