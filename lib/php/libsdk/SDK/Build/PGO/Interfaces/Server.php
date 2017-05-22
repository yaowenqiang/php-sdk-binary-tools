<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Config;

interface Server
{
	public function __construct(Config $conf);
	public function init();
	public function up();
	public function down();
}

