<?php

namespace SDK\Build\PGO;

use SDK\{Config as SDKConfig, Exception};

class Config
{
	const MODE_INIT = 0;
	const MODE_RUN = 1;

	protected $mode;
	protected $last_port = 8083;
	protected $sections = array();

	public function __construct(int $mode = MODE_RUN)
	{
		$this->mode = $mode;


		$base = getenv("PHP_SDK_ROOT_PATH");

		if (self::MODE_INIT == $mode) {

			$this->sections["nginx"] = parse_ini_file(implode("\\", array($base, "pgo", "conf", "nginx", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
			$this->sections["mariadb"] = parse_ini_file(implode("\\", array($base, "pgo", "conf", "mariadb", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
			$this->sections["php"] = parse_ini_file(implode("\\", array($base, "pgo", "conf", "php", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
		} else if (self::MODE_INIT == $mode) {
			$this->sections = parse_ini_file(implode("\\", array($base, "pgo", "work", "phpsdk_pgo.ini")), true, INI_SCANNER_TYPED);
		} else {
			throw new Exception("Unknown config mode '$mode'.");
		}
	}

	public function dump()
	{

	}
}
