<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server\DB;
use SDK\Build\PGO\Abstracts\Server;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};
use SDK\Build\PGO\Tool\PackageWorkman;

class PostgreSQL extends Server implements DB
{
	use FileOps;

	protected $conf;
	protected $base;
	protected $data_dir;
	protected $name = "PostgreSQL";

	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
		$this->base = $conf->getSrvDir(strtolower($this->name));
		$this->data_dir = $this->base . DIRECTORY_SEPARATOR . "data";
	}

	protected function setupDist()
	{
		$user = $this->conf->getSectionItem($this->name, "user");
		$pass = $this->conf->getSectionItem($this->name, "pass");

		if (!is_dir($this->data_dir)) {
			/* TODO No user pass yet. */
			$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "initdb.exe --nosync --username $user --encoding=UTF-8 " . $this->data_dir;
			exec($cmd);
		}
	}

	public function prepareInit(PackageWorkman $pw, bool $force = false) : void
	{
		$url = $this->conf->getSectionItem($this->name, "pkg_url");
		$pw->fetchAndUnzip($url, "postgresql.zip", $this->conf->getSrvDir(), "postgresql", $force);
	}

	public function init() : void
	{
		echo "Initializing " . $this->name . ".\n";

		$this->setupDist();

		$this->up();
		$this->down(true);

		echo $this->name . " initialization done.\n";
	}

	public function up() : void
	{
		echo "Starting " . $this->name . ".\n";


		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "pg_ctl.exe start -D " . $this->data_dir . " -o \"-h $host -p $port\"";
		$h = popen($cmd, "r");
		/* XXX error check*/
		pclose($h);

		echo $this->name . " started.\n";
	}

	public function down(bool $force = false) : void
	{
		echo "Stopping " . $this->name . ".\n";


		$user = $this->conf->getSectionItem($this->name, "user");
		$pass = $this->conf->getSectionItem($this->name, "pass");


		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "pg_ctl.exe stop -D " . $this->data_dir . " -m fast";
		exec($cmd);

		if ($force) {
			//sleep(1);
			//exec("taskkill /f /im nginx.exe >nul 2>&1");
		}

		echo $this->name . " stopped.\n";
	}

	public function createDb(string $db_name) : void
	{
		$user = $this->conf->getSectionItem($this->name, "user");
		$pass = $this->conf->getSectionItem($this->name, "pass");
		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "createdb.exe -h $host -p $port -U $user $db_name";
		exec($cmd);
	}

	public function dropDb(string $db_name) : void
	{
		$user = $this->conf->getSectionItem($this->name, "user");
		$pass = $this->conf->getSectionItem($this->name, "pass");
		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "dropdb.exe --if-exists -h $host -p $port -U $user $db_name";
		exec($cmd);
	}

	public function query(string $s) : void
	{
		$ret = NULL;

		$user = $this->conf->getSectionItem($this->name, "user");
		$pass = $this->conf->getSectionItem($this->name, "pass");
		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$pass_arg = $pass ? "-p$pass " : "";
		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "psql.exe -h $host -p $port -U $user -d $db_name -c \"$s\"";
		$ret = shell_exec($cmd);
	}
}

