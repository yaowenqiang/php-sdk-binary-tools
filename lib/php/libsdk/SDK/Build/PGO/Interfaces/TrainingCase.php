<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Interfaces\Server\{DB, HTTP};

interface TrainingCase
{
	/* Initialize the case, run only once on a new checkout. */
	public function init();

	/* Run training. */
	public function run();

	public function getDbServer() : ?DB;
	public function getHttpServer() : ?HTTP;
}

