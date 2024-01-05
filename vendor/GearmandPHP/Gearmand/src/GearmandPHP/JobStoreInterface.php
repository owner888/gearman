<?php

/*

 NOTES:
 =========

on boot:
---------
1) look for last used database (stored based on name of database [format: gearmandphp_YYYYMMDDHHIISS_(jobs|done)])
2) if it exists, query all "job" documents on that database
3) iterate through all of them and match "request" with "response" to find any that are not already paired with a "completed" response or applicable pairing
4) create a new database
5) insert all "unfinished" jobs into the new database
6) initialize changes feed on the new database
7) callback for received changes feeds
8) for any jobs where a worker is not available

job store management:
----------------------
1) on new job requests, insert into "jobstore_jobs" database
2) on notice of completion, insert into "jobstore_done" database

** need view to show total number of pending jobs grouped by "function name", which apparently is the way a "job" is represented by name.

=============
- list databases
- create database
- delete database
- find previous databases (list of jobs, list of completed jobs)
- get all documents in database(s)
- find incomplete jobs
- insert job into database
- insert "completed" status into database
- on changes feed, must also "retrieve document"

*/


namespace GearmandPHP;

use \GearmandPHP\GearmandJob;

interface JobStoreInterface
{
	public function putJob(GearmandJob $job);

	public function getJobs();

	public function deleteJobs();
}
