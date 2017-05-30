<?php

namespace SDK\Build\PGO\Tool;

use SDK\{Config as SDKConfig, Exception};
use SDK\Build\PGO\Config as PGOConfig;
use SDK\Build\PGO\Interfaces\{TrainingCase, Server, Server\DB, PHP};

class Training
{
	protected $conf;
	protected $t_case;

	public function __construct(PGOConfig $conf, TrainingCase $t_case)
	{
		$this->conf = $conf;
		$this->t_case = $t_case;
		
		if (!in_array($type, array("web", "cli"))) {
			throw new Exception("Unknown training type '$type'.");
		}
		$this->type = $type;
	}

	protected function run()
	{
		
	}
}
