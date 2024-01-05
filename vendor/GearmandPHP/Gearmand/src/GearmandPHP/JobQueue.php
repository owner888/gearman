<?php

namespace GearmandPHP;

class JobQueue extends \SplPriorityQueue
{
	private $priorities = array(
		'low'=>1,
		'normal'=>2,
		'high'=>3
	);

	public function compare(mixed $priority1, mixed $priority2): int {
		if(0 !== $c = $this->compareDelay($priority1, $priority2)){
			return $c;
		}
		if(0 !== $c = $this->comparePriority($priority1, $priority2)){
			return $c;
		}
		return $this->compareCreatedTime($priority1, $priority2);
	}

	private function compareDelay($priority1, $priority2){
		if($priority1->delay < $priority2->delay){
			return 1;
		}
		else if($priority1->delay > $priority2->delay){
			return -1;
		}
		return 0;
	}

	private function compareCreatedTime($priority1, $priority2){
		if($priority1->created > $priority2->created){
			return 1;
		}
		else if($priority1->created < $priority2->created){
			return -1;
		}
		return 0;
	}

	private function comparePriority($priority1, $priority2){
		$priority1 = $this->priorities[strtolower($priority1->priority)];
		$priority2 = $this->priorities[strtolower($priority2->priority)];
		if($priority1 > $priority2){
			return 1;
		}
		else if($priority1 < $priority2){
			return -1;
		}
		return 0;
	}
}
