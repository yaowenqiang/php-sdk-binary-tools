<?php

namespace SDK\Build\PGO;

use SDK\{Config as SDKConfig, Exception};
use SDK\Build\PGO\Config as PGOConfig;
use SDK\Build\PGO\Server\{MariaDB, NGINX};
use SDK\Build\PGO\PHP;
use SDK\Build\PGO\Interfaces\TrainingCase;
use SDK\Build\PGO\TrainingCaseIterator;

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

	protected function vitalizeSrv()
	{
		$php_fcgi_tcp = new PHP\FCGI($this->conf, true);
		$this->conf->addSrv(new NGINX($this->conf, $php_fcgi_tcp));

		$this->conf->addSrv(new MariaDB($this->conf));

		return $this->conf->getSrv("all");
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

	protected function initWorkDirs() : void
	{
		$dirs = array(
			$this->conf->getWorkDir(),
			$this->conf->getSrvDir(),
			$this->conf->getToolsDir(),
			$this->conf->getHtdocs(),
			$this->conf->getJobDir(),
		);

		foreach ($dirs as $dir) {
			if (!is_dir($dir)) {
				if (!mkdir($dir)) {
					throw new Exception("Failed to create '$dir'.");
				}
			}
		}
	}

	public function init(bool $force = false)
	{
		echo "\nInitializing PGO training environment.\n";

		$this->initWorkDirs();

		foreach ($this->vitalizeSrv() as $srv) {
			$srv->init($this->conf);
		}

		foreach (new TrainingCaseIterator($this->conf) as $handler) {
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

		foreach (new TrainingCaseIterator($this->conf) as $handler) {
			//$handler->init();
		}

		$this->down();
		echo "PGO training finished.\n";
	}

	public function up()
	{

		if (!$this->isInitialized()) {
			throw new Exception("PGO training environment is not initialized.");
		}
		echo "Starting up PGO environment.\n";

		foreach ($this->vitalizeSrv("all") as $srv) {
			$srv->up();
		}

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

		foreach ($this->vitalizeSrv("all") as $srv) {
			$srv->down($force);
		}

		sleep(1);

		echo "The PGO environment has been shut down.\n";
	}
}
