<?php

namespace GearmandPHP;

use \GearmandPHP\GearmandJob;

class JobStore implements JobStoreInterface
{
	public function __construct(array $config){
	}

	public function putJob(GearmandJob $job){
	}

	public function putJobs(array $jobs){
		foreach($jobs as $job){
			$this->putJob($job);
		}
	}

	public function getJobs(){
	}

	public function deleteJobs(){
	}

}
