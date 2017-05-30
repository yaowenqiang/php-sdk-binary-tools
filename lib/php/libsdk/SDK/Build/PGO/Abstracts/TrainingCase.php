<?php

namespace SDK\Build\PGO\Abstracts;

use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};

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

}

