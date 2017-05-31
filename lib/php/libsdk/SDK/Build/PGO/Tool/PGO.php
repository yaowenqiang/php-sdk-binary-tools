<?php

namespace SDK\Build\PGO\Tool;

use SDK\{Config as SDKConfig, Exception};
use SDK\Build\PGO\Config as PGOConfig;
use SDK\Build\PGO\Interfaces;

class PGO
{
	protected $php;
	protected $conf;
	protected $idx = 0;

	public function __construct(PGOConfig $conf, Interfaces\PHP $php)
	{
		$this->conf = $conf;
		$this->php = $php;		
	}

	protected function getPgcName(string $fname) : string
	{
		$bn = basename($fname, substr($fname, -4, 4));
		$dn = dirname($fname);

		return $dn . DIRECTORY_SEPARATOR . $bn . "!" . $this->idx . ".pgc";
	}

	protected function getPgdName(string $fname) : string
	{
		$bn = basename($fname, substr($fname, -4, 4));
		$dn = dirname($fname);

		return $dn . DIRECTORY_SEPARATOR . $bn . ".pgd";
	}

	protected function getWorkItems() : array
	{
		$exe = glob($this->php->getRootDir() . DIRECTORY_SEPARATOR . "*.exe");
		$dll = glob($this->php->getRootDir() . DIRECTORY_SEPARATOR . "*.dll");
		$dll = array_merge($dll, glob($this->php->getExtRootDir() . DIRECTORY_SEPARATOR . "php*.dll"));

		/* find out next index */
		do {
			if (!file_exists($this->getPgcName($dll[0]))) {
				break;
			}
			$this->idx++;
		} while (true);

		return array_unique(array_merge($exe, $dll));
	}

	public function dump(bool $merge = true) : void
	{
		$its = $this->getWorkItems();	

		foreach ($its as $base) {
			$pgd = $this->getPgdName($base);
			$pgc = $this->getPgcName($base);

			`pgosweep $base $pgc`;

			if ($merge) {
				`pgomgr /merge:1000 $pgc $pgd`;
			}
		}
	}

	public function waste() : void
	{
		$this->dump(false);
	}
}

