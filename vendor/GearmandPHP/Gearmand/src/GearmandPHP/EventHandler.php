<?php

namespace GearmandPHP;

use \WindowSeat\EventHandlerInterface;
use \WindowSeat\EventInterface;
use \GearmandPHP\Event as GEvent;
use \GearmandPHP\Job;

class EventHandler implements EventHandlerInterface
{
	public function handle(EventInterface $event){
		$job = $event->getEvent();
		if(empty($job)){
			return;
		}
		Gearmand::$priority_queue->insert($job,$job);
		foreach(Gearmand::$state['worker'] as $ident=>$worker){
			if($this->workerIsAvailable($worker) && $this->workerCanDoJob($worker,$job)){
				Gearmand::$state['worker'][$ident]['connection']->handler->handleGrabJobAll();
			}
		}
		foreach(Gearmand::$state['worker'] as $ident=>$worker){
			if($this->workerIsSleeping($worker) && $this->workerCanDoJob($worker,$job)){
				Gearmand::$state['worker'][$ident]['connection']->sendResponse(WorkerRequestHandler::NOOP, '');
			}
		}
	}

	private function workerCanDoJob($worker, $job){
		return isset($worker['functions'][$job->function_name]);
	}

	private function workerIsAvailable($worker){
		return empty($worker['state']) || (!in_array($worker['state'],array('sleeping','busy')));
	}

	private function workerIsSleeping($worker){
		return !empty($worker['state']) && ($worker['state'] === 'sleeping');
	}

	public function createEvent($event_id, $job = null){
		if(!empty($job)){
			$job->uuid = $event_id;
		}
		return new GEvent($job);
	}
}
