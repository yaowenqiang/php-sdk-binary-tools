<?php

namespace SDK\Build\PGO;

use SDK\{Config as SDKConfig, Exception};
use SDK\Build\PGO\Config as PGOConfig;
use SDK\Build\PGO\Server\{MariaDB, NGINX};
use SDK\Build\PGO\Server\PHP;

/* TODO add bench action */

class Controller
{
	protected $cmd;
	protected $scenario;
	protected $conf;

	public function __construct(string $cmd, ?string $scenario)
	{
		$this->cmd = $cmd;

		if (NULL == $scenario) {
			$scenario = "default";
		}
		$this->scenario = $scenario;
	}

	public function handle($force)
	{
		$mode = (int)("init" !== $this->cmd);
		$mode = (PGOConfig::MODE_INIT == $mode && $force) ? PGOConfig::MODE_REINIT : $mode;
		$this->conf = new PGOConfig("init" !== $this->cmd);
		$this->conf->setScenario($this->scenario);

		switch ($this->cmd) {
		default:
			throw new Exception("Unknown action '{$this->cmd}'.");
			break;
		case "init":
			$this->init($force);
			break;
		case "train":
			$this->train();
			break;
		case "up":
			$this->up();
			break;

		case "down":
			$this->down($force);
			break;
		}
	}

	public function init(bool $force = false)
	{
		echo "\nInitializing PGO training environment.\n";

		$work_dir = $this->conf->getWorkDir();
		if (!is_dir($work_dir)) {
			if (!mkdir($work_dir)) {
				throw new Exception("Failed to create work dir '$work_dir'.");
			}
		}

		$srv_dir = $this->conf->getSrvDir();
		if (!is_dir($srv_dir)) {
			if (!mkdir($srv_dir)) {
				throw new Exception("Failed to create '$srv_dir'.");
			}
		}

		$tool_dir = $this->conf->getToolsDir();
		if (!is_dir($tool_dir)) {
			if (!mkdir($tool_dir)) {
				throw new Exception("Failed to create '$tool_dir'.");
			}
		}

		$htdocs = $this->conf->getHtdocs();
		if (!is_dir($htdocs)) {
			if (!mkdir($htdocs)) {
				throw new Exception("Failed to create '$htdocs'.");
			}
		}

		$job_dir = $this->conf->getJobDir();
		if (!is_dir($job_dir)) {
			if (!mkdir($job_dir)) {
				throw new Exception("Failed to create job dir '$job_dir'.");
			}
		}

		$nginx = new NGINX($this->conf);
		$nginx->init();

		$maria = new MariaDB($this->conf);
		$maria->init();

		$php_fcgi_tcp = new PHP\FCGI($this->conf, true, $maria, $nginx);
		$php_fcgi_tcp->init();

		/* Setup training cases. */
		foreach (glob($this->conf->getCasesTplDir() . DIRECTORY_SEPARATOR . "*") as $base) {
			if(!is_dir($base)) {
				continue;
			}

			$handler_file = $base . DIRECTORY_SEPARATOR . "TrainingCaseHandler.php";
			if (!file_exists($handler_file)) {
				echo "Test case handler isn't present in '$base'.\n";
				continue;
			}

			$ns = basename($base);
			$this->conf->importSectionFromDir($ns, $base);

			require $handler_file;

			$class = "$ns\\TrainingCaseHandler";

			$srv_http_name = $this->conf->getSectionItem($ns, "srv_http");
			$srv_db_name = $this->conf->getSectionItem($ns, "srv_db");
			$php_name = $this->conf->getSectionItem($ns, "php");

			$srv_http = NULL;
			switch($srv_http_name) {
				case "nginx":
					$srv_http = $nginx;
					break;
			}

			$srv_db = NULL;
			switch($srv_db_name) {
				case "nginx":
					$srv_db = $maria;
					break;
			}

			$t_php = NULL;
			switch($php_name) {
				case "fcgi":
					$t_php = $php_fcgi_tcp;
					break;
			}

			$handler = new $class($this->conf, $srv_http, $srv_db, $t_php);

			$handler->init();
		}

		echo "Initialization complete.\n";
	}

	public function isInitialized()
	{
		$base = getenv("PHP_SDK_ROOT_PATH");

		/* XXX Could be some better check. */
		return is_dir($base . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "work");
	}

	public function train()
	{
		if (!$this->isInitialized()) {
			throw new Exception("PGO training environment is not initialized.");
		}

		echo "Starting PGO training.\n";
		$this->up();

		/* do work here */

		$this->down();
		echo "PGO training finished.\n";
	}

	public function up()
	{

		if (!$this->isInitialized()) {
			throw new Exception("PGO training environment is not initialized.");
		}
		echo "Starting up PGO environment.\n";

		$nginx = new NGINX($this->conf);
		$nginx->up();

		$maria = new MariaDB($this->conf);
		$maria->up();

		$php_fcgi_tcp = new PHP\FCGI($this->conf, true, $maria, $nginx);
		$php_fcgi_tcp->up();

		sleep(1);

		echo "The PGO environment is up.\n";
	}

	public function down(bool $force = false)
	{
		if (!$this->isInitialized()) {
			throw new Exception("PGO training environment is not initialized.");
		}
		/* XXX check it was started of course. */
		echo "Shutting down PGO environment.\n";

		$nginx = new NGINX($this->conf);
		$nginx->down($force);

		$maria = new MariaDB($this->conf);
		$maria->down($force);

		$php_fcgi_tcp = new PHP\FCGI($this->conf, true, $maria, $nginx);
		$php_fcgi_tcp->down($force);

		sleep(1);

		echo "The PGO environment has been shut down.\n";
	}
}
