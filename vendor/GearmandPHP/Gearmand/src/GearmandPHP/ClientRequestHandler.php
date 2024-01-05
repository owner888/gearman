<?php

namespace GearmandPHP;

use \GearmandPHP\Job;

class ClientRequestHandler
{
	// Request Types
	const SUBMIT_JOB = 7;
	const GET_STATUS = 15;
	const ECHO_REQ = 16;
	const SUBMIT_JOB_BG = 18;
	const SUBMIT_JOB_HIGH = 21;
	const OPTION_REQ = 26;
	const SUBMIT_JOB_HIGH_BG = 32;
	const SUBMIT_JOB_LOW = 33;
	const SUBMIT_JOB_LOW_BG = 34;
	const SUBMIT_JOB_SCHED = 35;
	const SUBMIT_JOB_EPOCH = 36;

	const SUBMIT_REDUCE_JOB = 37;
	const SUBMIT_REDUCE_JOB_BACKGROUND = 38;

	// Response Types
	const JOB_CREATED = 8;
	const WORK_STATUS= 12;
	const WORK_COMPLETE = 13;
	const WORK_FAIL = 14;
	const ECHO_RES = 17;
	const ERROR = 19;
	const STATUS_RES = 20;
	const WORK_EXCEPTION = 25;
	const OPTION_RES = 27;
	const WORK_DATA = 28;
	const WORK_WARNING = 29;

	private $bev;

	public function __construct($ident, $bev, $schivel){
		$this->ident = $ident;
		$this->bev = $bev;
		$this->schivel = $schivel;
	}

	public function relay($headers,$data){
		$type = $headers['type'];
		switch($type){
			// WORK_* requests are relayed from worker
			case self::WORK_DATA:
				$this->handleWorkData($data);
				break;
			case self::WORK_WARNING:
				$this->handleWorkWarning($data);
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
			default:
				//INVALID RELAY REQUEST TYPE
				break;
		}
	}

	public function handle($headers,$data){
		$type = $headers['type'];
		switch($type){
			case self::SUBMIT_JOB:
				$this->handleSubmitJob($data);
				break;
			case self::GET_STATUS:
				$this->handleGetStatus($data);
				break;
			case self::ECHO_REQ:
				$this->handleEchoReq($data);
				break;
			case self::SUBMIT_JOB_BG:
				$this->handleSubmitJobBg($data);
				break;
			case self::SUBMIT_JOB_HIGH:
				$this->handleSubmitJobHigh($data);
				break;
			case self::OPTION_REQ:
				$this->handleOptionReq($data);
				break;
			case self::SUBMIT_JOB_HIGH_BG:
				$this->handleSubmitJobHighBg($data);
				break;
			case self::SUBMIT_JOB_LOW:
				$this->handleSubmitJobLow($data);
				break;
			case self::SUBMIT_JOB_LOW_BG:
				$this->handleSubmitLowBg($data);
				break;
			case self::SUBMIT_JOB_SCHED:
				$this->handleSubmitJobSched($data);
				break;
			case self::SUBMIT_JOB_EPOCH:
				$this->handleSubmitJobEpoch($data);
				break;
			default:
				//INVALID CLIENT REQUEST TYPE
				break;
		}
	}

	// WORK_* requests to client are WORK_* requests
	//  received from worker
	private function handleWorkData($data){
		list(,$data) = explode("\0",$data,2);
		$this->sendResponse(self::WORK_DATA,$data);
	}

	private function handleWorkWarning($data){
		list(,$data) = explode("\0",$data,2);
		$this->sendResponse(self::WORK_WARNING,$data);
	}

	private function handleWorkStatus($data){
		list(,$data) = explode("\0",$data,2);
		$this->sendResponse(self::WORK_STATUS,$data);
	}

	private function handleWorkComplete($data){
		list(,$data) = explode("\0",$data,2);
		$this->sendResponse(self::WORK_COMPLETE,$data);
	}

	private function handleWorkFail($data){
		list(,$data) = explode("\0",$data,2);
		$this->sendResponse(self::WORK_FAIL,$data);
	}

	private function handleWorkException($data){
		list(,$data) = explode("\0",$data,2);
		$this->sendResponse(self::WORK_EXCEPTION,$data);
	}

	private function handleSubmitJob($data){
		list($function_name,$unique_id,$data) = explode("\0",$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = 0;
		$job->created = time();
		$job->priority = 'normal';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = false;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function handleGetStatus($data){
		$handle = $data;
		// client is requesting status on a particular job
		$data = implode("\0", array(
			$handle,
			$this->knowJobStatus($handle) ? 1 : 0,
			$this->isRunning($handle) ? 1 : 0,
			$this->getStatusNumerator($handle),
			$this->getStatusDenominator($handle)
		));
		$this->sendResponse(self::STATUS_RES,$data);
	}

	private function knowJobStatus($handle){
		return !empty(Gearmand::getJobState($handle, 'state'));
	}

	private function isRunning($handle){
		return Gearmand::getJobState($handle, 'state') === 'processing';
	}

	private function getStatusNumerator($handle){
		return (int)Gearmand::getJobState($handle, 'percent_done_numerator');
	}

	private function getStatusDenominator($handle){
		return (int)Gearmand::getJobState($handle, 'percent_done_denominator');
	}

	private function handleEchoReq($data){
		$this->sendResponse(self::ECHO_RES,$data);
	}

	private function handleSubmitJobBg($data){
		list($function_name,$unique_id,$data) = explode("\0",$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = 0;
		$job->created = time();
		$job->priority = 'normal';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = true;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function handleSubmitJobHigh($data){
		list($function_name,$unique_id,$data) = explode("\0",$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = 0;
		$job->created = time();
		$job->priority = 'high';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = false;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function handleOptionReq($data){
		$option = $data;
		// currently only "exceptions" is a possibility here
		switch($option){
			case 'exceptions':
				// notify server it should forward "WORK_EXCEPTION" packets to client
				$this->sendResponse(self::OPTION_RES,$option);
				break;
			default:
				error_log('invalid option for client: '.$option);
				break;
		}
		Gearmand::clientAddOption($this->ident,$option);
	}

	private function handleSubmitJobHighBg($data){
		list($function_name,$unique_id,$data) = explode("\0",$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = 0;
		$job->created = time();
		$job->priority = 'high';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = true;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function handleSubmitJobLow($data){
		list($function_name,$unique_id,$data) = explode("\0",$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = 0;
		$job->created = time();
		$job->priority = 'low';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = false;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function handleSubmitJobLowBg($data){
		list($function_name,$unique_id,$data) = explode("\0",$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = 0;
		$job->created = time();
		$job->priority = 'low';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = true;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function handleSubmitJobSched($data){
		$data = explode("\0",$data);
		$function_name = $data[0];
		$unique_id = $data[1];
		$minute = $data[2];
		$hour = $data[3];
		$day_of_month = $data[4];
		$month = $data[5];
		$day_of_week = $data[6];
		$data = $data[7];
		// above data tells server at what time to run the job
		// $handle = $this->assignHandle();

		$today = date('Y-m-d-H-i');
		$sched = date('Y-m-d-H-i',strtotime(date('Y').'-'.$m.'-'.$d.'-'.$h.'-'.$i));

		if($sched < $today){
			list($y,$tail) = explode('-',$sched,2);
			$y += 1;
			$sched = $y.'-'.$tail;
		}

		$delay = strtotime($sched)-strtotime($today);

		// if delay is over 60 days, it's probably an error
		if($delay > (86400 * 60)){
			$delay = 0;
		}
		$delay = max(0,$delay);

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = $delay;
		$job->sched = $sched;
		$job->created = time();
		$job->priority = 'normal';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = false;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function handleSubmitJobEpoch($data){
		list($function_name,$unique_id,$epoch_time,$data) = explode("\0",$data);
		// like "SUBMIT_JOB_BG", but run job at $epoch_time instead of immediately
		// $handle = $this->assignHandle();

		$job = new Job();
		$job->client_uuid = $this->ident;
		$job->delay = $epoch_time - time();
		$job->created = time();
		$job->priority = 'normal';
		$job->function_name = $function_name;
		$job->uniq_id = $unique_id;
		$job->background = false;
		$job->payload = $data;

		$this->createJob($job);
	}

	private function createJob(Job $job){
		$cb = function($uuid) use($job) {
			Gearmand::registerJob($uuid, $job);
			$this->sendResponse(self::JOB_CREATED, $uuid);
		};
		$cb->bindTo($this);
		$this->schivel->store($job, $cb);
	}

	public function sendResponse($type, $message){
		$response = pack('c4',"\0",ord('R'),ord('E'),ord('S'));
		$response.= pack('N',$type);
		$response.= pack('N',strlen($message));
		$response.= $message;

		$output = $this->bev->output;
		return $output->add($response);
	}

}
