<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Interfaces\Server;

interface TrainingCase
{
	public function __construct(Server\DB $srv_db, Server\HTTP $srv_http);
	/* Initialize the case, run only once on a new checkout. */
	public function init();

	/* Run training. */
	public function run();

	public function getDbServer() : ?Server\DB;
	public function getHttpServer() : ?Server\HTTP;
}

