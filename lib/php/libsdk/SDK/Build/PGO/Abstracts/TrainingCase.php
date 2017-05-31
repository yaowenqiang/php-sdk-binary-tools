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

	protected $stat = array();

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
		$training = new Tool\Training($this->conf, $this);
		$pgo = new Tool\PGO($this->conf, $this->php);

		echo "Running " . $this->getName() . " training.\n";

		$max_runs = $this->max_runs ?? 1;
		$max_runs = (int)$max_runs > 0 ? $max_runs : 1;
		$training->run($max_runs, $stat);

		if ($this->getType() == "web") {
			$ok = true;
			echo "HTTP responses:\n";
			foreach ($stat["http_code"] as $code => $num) {
				printf("    %d received %d times\n", $code, $num);
				/* TODO extend list. */
				if (200 != $code) {
					$ok = false;
				}
			}
			if (!$ok) {
				printf("\033[31m WARNING: Not all HTTP responses have indicated success, the PGO data might be unsuitable!\033[0m\n");
			}
		}

		echo $this->getName() . " training complete.\n";

		echo "Dumping PGO data for " . $this->getName() . ".\n";
		$pgo->dump();
		echo "Finished dumping training data for " . $this->getName() . ".\n";
	}
}

