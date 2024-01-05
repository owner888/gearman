<?php

namespace GearmandPHP;

class JobQueue extends \SplPriorityQueue
{
	private $priorities = array(
		'low'=>1,
		'normal'=>2,
		'high'=>3
	);

	public function compare($obj1, $obj2){
		if(0 !== $c = $this->compareDelay($obj1,$obj2)){
			return $c;
		}
		if(0 !== $c = $this->comparePriority($obj1,$obj2)){
			return $c;
		}
		return $this->compareCreatedTime($obj1,$obj2);
	}

	private function compareDelay($obj1, $obj2){
		if($obj1->delay < $obj2->delay){
			return 1;
		}
		else if($obj1->delay > $obj2->delay){
			return -1;
		}
		return 0;
	}

	private function compareCreatedTime($obj1, $obj2){
		if($obj1->created > $obj2->created){
			return 1;
		}
		else if($obj1->created < $obj2->created){
			return -1;
		}
		return 0;
	}

	private function comparePriority($obj1, $obj2){
		$priority1 = $this->priorities[strtolower($obj1->priority)];
		$priority2 = $this->priorities[strtolower($obj2->priority)];
		if($priority1 > $priority2){
			return 1;
		}
		else if($priority1 < $priority2){
			return -1;
		}
		return 0;
	}
}
