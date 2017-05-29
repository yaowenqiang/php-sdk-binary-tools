<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Config;

interface PHP
{
/*	public function init() : void;
	public function up() : void;
public function down() : void;*/
	public function getVersion(bool $short = false) : string;
	public function getExeFilename() : string;
}

