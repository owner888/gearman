<?php

namespace GearmandPHP;

use \GearmandPHP\Gearmand;

class WorkerRequestHandler
{
	// Request Types
	const CAN_DO = 1;
	const CANT_DO = 2;
	const RESET_ABILITIES = 3;
	const PRE_SLEEP = 4;
	const GRAB_JOB = 9;
	const WORK_STATUS = 12;
	const WORK_COMPLETE = 13;
	const WORK_FAIL = 14;
	const WORK_EXCEPTION = 25;
	const WORK_DATA = 28;
	const WORK_WARNING = 29;

const CLIENT_WORK_DATA = 20;
const CLIENT_WORK_STATUS = 22;
const CLIENT_WORK_FAIL = 24;
const CLIENT_WORK_COMPLETE = 0;
const CLIENT_PAUSE = 38;
const CLIENT_IO_WAIT = 1;
const CLIENT_WORK_EXCEPTION = 23;
const CLIENT_WORK_WARNING = 21;

	const ECHO_REQ = 16;
	const SET_CLIENT_ID = 22;
	const CAN_DO_TIMEOUT = 23;
	const ALL_YOURS = 24;
	const OPTION_REQ = 26;
	const GRAB_JOB_UNIQ = 30;
	const GRAB_JOB_ALL = 39;

	// Response Types
	const NOOP = 6;
	const NO_JOB = 10;
	const JOB_ASSIGN = 11;
	const ECHO_RES = 17;
	const ERROR = 19;
	const OPTION_RES = 27;
	const JOB_ASSIGN_UNIQ = 31;
	const JOB_ASSIGN_ALL = 40;

	private $bev;

	public function __construct($ident,$bev,$schivel){
		$this->ident = $ident;
		$this->bev = $bev;
		$this->schivel = $schivel;
	}

	public function handle($headers,$data){
		$type = $headers['type'];
		switch($type){
			case self::CAN_DO:
				$this->handleCanDo($data);
				break;
			case self::CANT_DO:
				$this->handleCantDo($data);
				break;
			case self::RESET_ABILITIES:
				$this->handleResetAbilities();
				break;
			case self::PRE_SLEEP:
				$this->handlePreSleep($data);
				break;
			case self::GRAB_JOB:
				$this->handleGrabJob();
				break;
			case self::GRAB_JOB_ALL:
				$this->handleGrabJobAll();
				break;
			case self::ECHO_REQ:
				$this->handleEchoReq($data);
				break;
			case self::SET_CLIENT_ID:
				$this->handleSetClientID($data);
				break;
			case self::CAN_DO_TIMEOUT:
				$this->handleCanDoTimeout($data);
				break;
			case self::ALL_YOURS:
				$this->handleAllYours($data);
				break;
			case self::OPTION_REQ:
				$this->handleOptionReq($data);
				break;
			case self::GRAB_JOB_UNIQ:
				$this->handleGrabJobUniq();
				break;
			case self::WORK_STATUS:
				$this->handleWorkStatus($data);
				break;
			case self::WORK_COMPLETE:
				$this->handleWorkComplete($data);
				break;
			case self::WORK_FAIL:
				$this->handleWorkFail($data);
				break;
			case self::WORK_EXCEPTION:
				$this->handleWorkException($data);
				break;
			case self::WORK_DATA:
				$this->handleWorkData($data);
				break;
			case self::WORK_WARNING:
				$this->handleWorkWarning($data);
				break;
			default:
				//INVALID WORKER REQUEST TYPE
				break;
		}
	}



	public function handleGrabJob(){
		// server responds with "NO_JOB" or "JOB_ASSIGN"
		if($job = $this->getJobFromQueue()){
			$this->setJobState($job->uuid, 'worker', $this->ident);
			$this->sendResponse(self::JOB_ASSIGN, $job->uuid."\0".$job->function_name."\0".$job->payload);
		}
		else{
			$this->sendResponse(self::NO_JOB);
		}
	}

	public function handleGrabJobAll(){
		// server responds with "NO_JOB" or "JOB_ASSIGN"
		if($job = $this->getJobFromQueue()){
			$this->sendResponse(self::JOB_ASSIGN_UNIQ, $job->uuid."\0".$job->function_name."\0".$job->client_uuid."\0".$job->payload);
		}
		else{
			$this->sendResponse(self::NO_JOB);
		}
	}

	public function handleGrabJobUniq(){
		// server responds with "NO_JOB" or "JOB_ASSIGN_UNIQ"
		if($job = $this->getJobFromQueue()){
			$this->sendResponse(self::JOB_ASSIGN_UNIQ, $job->uuid."\0".$job->function_name."\0".$job->client_uuid."\0".$job->payload);
		}
		else{
			$this->sendResponse(self::NO_JOB);
		}
	}

	private function getJobFromQueue(){
		$worker = Gearmand::$state['worker'][$this->ident];
		foreach(Gearmand::$priority_queue as $job){
			if(isset($worker['functions'][$job->function_name])){
				if(empty(Gearmand::getJobState($job->uuid,'worker'))){
					Gearmand::setJobState($job->uuid,'worker',$this->ident);
					return $job;
				}
			}
		}
		return false;
	}

	private function handleCanDo($data){
		Gearmand::workerAddFunction($this->ident, $data);
	}

	private function handleCantDo($data){
		Gearmand::workerRemoveFunction($this->ident, $data);
	}

	private function handleResetAbilities(){
		// $data is empty
		// RESET "abilities" to empty
		Gearmand::setWorkerState($this->ident, 'functions', array());
	}

	private function handleCanDoTimeout($data){
		list($function_name,$timeout) = explode("\0",$data);
		// same as "CAN_DO", but $timeout indicates how long the job can run
		// if the job takes longer than $timeout seconds, it will fail
		$timeout = max(0,(int)$timeout);
		Gearmand::workerAddFunction($this->ident, $data, $timeout);
	}

	private function handlePreSleep($data){
		// $data is empty
		// Set "status" to "sleeping"
		//   which means server needs to wake up worker with "NOOP"
		//   if a job comes in that the worker can do
		Gearmand::setWorkerState($this->ident, 'state', 'sleeping');
	}

	private function handleEchoReq($data){
		$this->sendResponse(self::ECHO_RES,$data);
	}

	private function handleSetClientID($data){
		$client_id = $data;
		// unique string to identify the worker instance
		Gearmand::setWorkerState($this->ident, 'alias', $client_id);
	}

	private function handleAllYours($data){
		// $data is empty
		// notify server that the worker is connected exclusively
		Gearmand::setWorkerState($this->ident, 'state', 'waiting');
	}

	private function handleWorkStatus($raw){
		list($handle,$numerator,$denominator) = explode("\0",$raw);
		// relay "percentage complete" to client, and update on server
		Gearmand::setJobState($handle, 'percent_done_numerator', $numerator);
		Gearmand::setJobState($handle, 'percent_done_denominator', $denominator);
		$client = $this->getJobClient($handle);
		if(!empty($client) && !empty($client['connection'])){
			$client['connection']->sendResponse(self::WORK_STATUS, $raw);
		}
	}

	// notify server / clients that the job completed successfully
	private function handleWorkComplete($raw){
		list($handle,$data) = explode("\0",$raw);
		$client = $this->getJobClient($handle);
		if(!empty($client) && !empty($client['connection'])){
			$client['connection']->sendResponse(self::WORK_COMPLETE, $raw);
		}
		$this->bev->free();
		unset(Gearmand::$state['worker'][$this->ident]);
	}

	// notify server / clients that job failed
	private function handleWorkFail($raw){
		$handle = $raw;
		$client = $this->getJobClient($handle);
		if(!empty($client) && !empty($client['connection'])){
			$client['connection']->sendResponse(self::WORK_FAIL,"");
		}
	}

	// notify server / clients that the job failed
	// $data is info about the exception
	private function handleWorkException($raw){
		list($handle,$data) = explode("\0",$raw);
		$client = $this->getJobClient($handle);
		if(!empty($client) && !empty($client['connection'])){
			$client['connection']->sendResponse(self::WORK_EXCEPTION, $raw);
		}
	}

	// supposed to relay progress info or job info to client
	private function handleWorkData($raw){
		list($handle,$data) = explode("\0",$raw);
		$client = $this->getJobClient($handle);
		if(!empty($client) && !empty($client['connection'])){
			$client['connection']->sendResponse(self::WORK_DATA, $raw);
		}
	}

	// relay "warning" data to the client
	private function handleWorkWarning($raw){
		list($handle,$data) = explode("\0",$raw);
		$client = $this->getJobClient($handle);
		if(!empty($client) && !empty($client['connection'])){
			$client['connection']->sendResponse(self::WORK_WARNING, $raw);
		}
	}

	private function getJobClient($job_handle){
		$job = Gearmand::$state['jobs'][$job_handle];
		if(!empty($job) && !empty(Gearmand::$state['client'][$job->client_uuid])){
			return Gearmand::$state['client'][$job->client_uuid];
		}
		return null;
	}

	private function handleOptionReq($data){
		$option = $data;
		// currently "exceptions" is only documented possibility here
		switch($option){
			case 'exceptions':
				Gearmand::workerAddOption($this->ident, $option);
				break;
			default:
				error_log('worker received unknown option request: '.$option);
				break;
		}
		$this->sendResponse(self::OPTION_RES,$option);
	}


	public function sendResponse($type, $message = ''){
		$response = pack('c4',"\0",ord('R'),ord('E'),ord('S'));
		$response.= pack('N',$type);
		$response.= pack('N',strlen($message));
		$response.= $message;

		$output = $this->bev->output;
		return $output->add($response);
	}

}
