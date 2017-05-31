<?php

namespace SDK\Build\PGO\Abstracts;

use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};
use SDK\Build\PGO\Tool;

class TrainingCase
{
	use FileOps;

	const TYPE_WEB = "web";
	const TYPE_CLI = "cli";

	public function getType() : string
	{
		$type = $this->conf->getSectionItem($this->getName(), "type");

		if (!$type) {
			$type = "web";
		}

		return $type;
	}

	public function run() : void
	{
		$t = new Tool\Training($this->conf, $this);
		$p = new Tool\PGO($this->conf);

		$t->run();
	}
}

