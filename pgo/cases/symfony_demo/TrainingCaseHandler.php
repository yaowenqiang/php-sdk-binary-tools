<?php

namespace symfony_demo;

use SDK\Build\PGO\Interfaces\TrainingCase;
use SDK\Build\PGO\Config;

class TrainingCaseHandler implements TrainingCase
{
	public function __construct(Config $conf)
	{
		echo "construct here\n";
	}

	public function init()
	{
		echo "init here\n";
	}
}


