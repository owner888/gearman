<?php

namespace GearmandPHP;

class AdminRequestHandler
{
	private $bev;

	public function __construct($bev){
		$this->bev = $bev;
	}

	public function parseCommand($command){
		$parts = explode(' ',$command);
		if($parts[0] === 'maxqueue'){
			$command = array_shift($parts);
			$args = implode(' ', $parts);
		}
		else{
			$args = array_pop($parts);
			$command = implode(' ',$parts);
		}
		return array(trim($command), trim($args));
	}

	public function handle($command, $arg = null){
		switch($command){
			case 'show unique jobs':
				$this->handleShowUniqueJobs();
				break;
			case 'verbose':
				$this->handleVerbose();
				break;
			case 'show jobs':
				$this->handleShowJobs();
				break;
			case 'getpid':
				$this->handleGetPid();
				break;
			case 'status':
				$this->handleStatus();
				break;
			case 'version':
				$this->handleVersion();
				break;
			case 'workers':
				$this->handleWorkers();
				break;
			case 'shutdown':
				// optional argument "graceful"
				$this->handleShutdown($arg);
				break;
			case 'cancel job':
				$this->handleCancelJob($arg);
				break;
			case 'drop function':
				$this->handleDropFunction($arg);
				break;
			case 'create function':
				$this->handleCreateFunction($arg);
				break;
			case 'maxqueue':
				$this->handleMaxQueue($arg);
				break;
			default:
				// TODO:  Possible error conditions
				//   ERR INVALID_ARGUMENTS An+incomplete+set+of+arguments+was+sent+to+this+command+%.*s
				//   ERR UNKNOWN_SHOW_ARGUMENTS
				//   ERR UNKNOWN_JOB
				if(is_string($arg) || ($arg==="")){
					// error, was already here
					return;
				}
				if(empty($command)){
					echo 'No Command Sent'.PHP_EOL;
					return;
				}
				list($command, $arg) = $this->parseCommand($command);
				$this->handle($command, $arg);
				break;
		}
	}

	private function handleShowUniqueJobs(){
		// can be any arbitrary data
		// No info in SPEC
		// TODO: per source code...
		//   data.vec_append_printf("%.*s\n", int(server_job->unique_length), server_job->unique);
		$this->sendResponse('20 127.0.0.1 blah : a b c',true);
	}

	private function handleVerbose(){
		// can be any arbitrary data
		// No info in SPEC
		// TODO: Find out what existing gearmand provides
		$this->sendResponse('20 127.0.0.1 blah : a b c',true);
	}

	private function handleShowJobs(){
		// can be any arbitrary data
		// No info in SPEC
		// TODO: per source code...
		//   data.vec_append_printf("%s\t%u\t%u\t%u\n", server_job->job_handle, uint32_t(server_job->retries),
		//                       uint32_t(server_job->ignore_job), uint32_t(server_job->job_queued));
		$this->sendResponse('20 127.0.0.1 blah : a b c',true);
	}

	private function handleGetPid(){
		$this->sendResponse('OK '.getmypid());
	}

	private function handleStatus(){
		// can be any arbitrary data
		// PER SPEC:
		// The format is: FUNCTION\tTOTAL\tRUNNING\tAVAILABLE_WORKERS
		$this->sendResponse("blah\t5\t2\t3",true);
	}

	private function handleVersion(){
		$this->sendResponse('OK Version 0.1');
	}

	private function handleWorkers(){
		// can be any arbitrary data
		// PER SPEC:
		// The format is: FD IP-ADDRESS CLIENT-ID : FUNCTION ...
		$response = "20 127.0.0.1 blah : a b c";
		$this->sendResponse($response, true);
	}

	private function handleShutdown($arg = null){
		$this->sendResponse('OK');
		//create new event that will shutdown
		switch($arg){
			case 'graceful':
				break;
			default:
				break;
		}
	}

	private function handleCancelJob(){
		// TODO: POSSIBLE ERROR CASES:
		//   ERR this job does not exist
		$this->sendResponse('OK');
	}

	private function handleDropFunction(){
		// TODO: POSSIBLE ERROR CASES...
		//   ERR there are still connected workers or executing clients
		//   ERR function not found
		$this->sendResponse('OK');
	}

	private function handleCreateFunction($arg){
		// TODO: POSSIBLE ERROR CASES...
		//   ERR CREATE_FUNCTION [function_name]
		$this->sendResponse('OK');
	}

	private function handleMaxQueue($arg){
		$this->sendResponse('OK');
	}

	public function sendResponse($response,$terminate = false){
		$response = trim($response);
		if($terminate){
			$response.= "\n".'.';
		}
		$output = $this->bev->output;
		$output->add($response."\n");
	}

}
