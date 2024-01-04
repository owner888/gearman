<?php

namespace Schivel\Traits;

trait SimpleQueries
{
	private $pagenum;
	private $limit;
	private $desc;
	private $async;

	public function fetchIdByKey($view, $key){
		$result = $this->simpleQuery([
			'view'=>$view,
			'filter'=>'id_only',
			'key'=>$key,
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchIdByRange($view, $startkey, $endkey){
		$result = $this->simpleQuery([
			'view'=>$view,
			'filter'=>'id_only',
			'startkey'=>$startkey,
			'endkey'=>$endkey,
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchIdByKeys($view, array $keys){
		$result = $this->simpleMultiQuery([
			'view'=>$view,
			'filter'=>'id_only',
			'keys'=>$keys,
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchDocByKey($view, $key){
		$result = $this->simpleQuery([
			'view'=>$view,
			'filter'=>'doc_only',
			'key'=>$key,
			'include_docs' => 'true',
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchDocByRange($view, $startkey, $endkey){
		$result = $this->simpleQuery([
			'view'=>$view,
			'filter'=>'doc_only',
			'startkey'=>$startkey,
			'endkey'=>$endkey,
			'include_docs' => 'true',
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchDocByKeys($view, array $keys){
		$result = $this->simpleMultiQuery([
			'view'=>$view,
			'filter'=>'doc_only',
			'keys'=>$keys,
			'include_docs' => 'true',
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchDocstateByKey($view, $key){
		$result = $this->simpleQuery([
			'view'         => $view,
			'filter'       => 'docstate_only',
			'key'          => $key,
			'include_docs' => 'true',
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchDocstateByRange($view, $startkey, $endkey){
		$result = $this->simpleQuery([
			'view'         => $view,
			'filter'       => 'docstate_only',
			'startkey'=>$startkey,
			'endkey'=>$endkey,
			'include_docs' => 'true',
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchDocstateByKeys($view, array $keys){
		$result = $this->simpleMultiQuery([
			'view'=>$view,
			'filter'=>'docstate_only',
			'include_docs' => 'true',
			'keys'=>$keys,
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchValueByKey($view, $key){
		$result = $this->simpleQuery([
			'view'=>$view,
			'filter'=>'value_only',
			'key'=>$key,
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchValueByRange($view, $startkey, $endkey){
		$result = $this->simpleQuery([
			'view'=>$view,
			'filter'=>'value_only',
			'startkey'=>$startkey,
			'endkey'=>$endkey,
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchValueByKeys($view, array $keys){
		$result = $this->simpleMultiQuery([
			'view'=>$view,
			'filter'=>'value_only',
			'keys'=>$keys,
			'format'=>'array'
		]);
		return $result;
	}

	public function fetchObjectByKey($view, $key){
		$result = $this->simpleQuery([
			'view'=>$view,
			'key'=>$key,
			'format'=>'thaw'
		]);
		return $result;
	}

	public function fetchObjectByRange($view, $startkey, $endkey){
		$result = $this->simpleQuery([
			'view'=>$view,
			'startkey'=>$startkey,
			'endkey'=>$endkey,
			'format'=>'thaw'
		]);
		return $result;
	}

	public function fetchObjectByKeys($view, array $keys){
		$result = $this->simpleMultiQuery([
			'view'=>$view,
			'keys'=>$keys,
			'format'=>'thaw'
		]);
		return $result;
	}

	public function fetchJsonByKey($view, $key){
		$result = $this->simpleQuery([
			'view'=>$view,
			'key'=>$key,
			'include_docs' => 'true',
			'format'=>'json'
		]);
		return $result;
	}

	public function fetchJsonByRange($view, $startkey, $endkey){
		$result = $this->simpleQuery([
			'view'=>$view,
			'startkey'=>$startkey,
			'endkey'=>$endkey,
			'include_docs' => 'true',
			'format'=>'json'
		]);
		return $result;
	}

	public function fetchJsonByKeys($view, array $keys){
		$result = $this->simpleMultiQuery([
			'view'=>$view,
			'keys'=>$keys,
			'include_docs' => 'true',
			'format'=>'json'
		]);
		return $result;
	}

	public function isDuplicate($view, $key){
		if(!empty($this->async)){
			throw new Exception('Invalid method "isDuplicate" while in async mode');
		}
		$result = $this->fetchIdByKey($view, $key);
		return count($result) > 0;
	}

	private function simpleQuery($params){
		$query = $this->setQuery(array(
			'key'=> json_encode(empty($params['key']) ? '' : $params['key'])
		), $params);
		if(isset($params['include_docs'])){
			$query['include_docs'] = $params['include_docs'];
		}
		if($this->async){
			$this->db->getViewService()->async($params['view'], array(
				'query'=>$query,
				'opts'=>$this->setOptions($params)
			));
			$this->cleanUp();
			return $this;
		}
		else{
			$result = $this->db->getViewService()->query($params['view'], array(
				'query'=>$query,
				'opts'=>$this->setOptions($params)
			));
		}
		$this->cleanUp();
		return is_string($result) ? $result : (isset($result['rows']) ? $result['rows'] : $result);
	}

	private function simpleMultiQuery($params){
		$query = $this->setQuery(array(
			'keys'=> json_encode(empty($params['keys']) ? '' : $params['keys'])
		),$params);
		if($this->async){
			$this->db->getViewService()->async($params['view'], array(
				'query'=>$query,
				'opts'=>$this->setOptions($params)
			));
			$this->cleanUp();
			return $this;
		}
		else{
			$result = $this->db->getViewService()->query($params['view'], array(
				'query'=>$query,
				'opts'=>$this->setOptions($params)
			));
		}
		$this->cleanUp();
		return is_string($result) ? $result : (isset($result['rows']) ? $result['rows'] : $result);
	}

	public function dispatchViews(callable $fn){
		$this->async = null;
		$this->db->getViewService()->dispatch($fn);
	}

	public function fetchViews(){
		$this->db->getViewService()->fetch();
		$this->async = null;
	}

	public function getBuffers(){
		return $this->db->getViewService()->getBuffers();
	}

	private function cleanUp(){
		$this->pagenum = null;
		$this->limit = null;
		$this->desc = null;
	}

	public function flush(){
		$this->db->getViewService()->flush();
	}

	private function setQuery($query, $params){
		if(isset($params['startkey'])){
			$query = array(
				'startkey'=>json_encode($params['startkey']),
				'endkey'=>json_encode($params['endkey'])
			);
		}
		if(isset($this->desc)){
			$query['descending'] = $this->desc;
		}
		if(isset($params['include_docs'])){
			$query['include_docs'] = $params['include_docs'];
		}
		if(!empty($this->pagenum) && !empty($this->limit)){
			$query['skip'] = abs(($this->pagenum - 1) * $this->limit);
			$query['limit'] = abs($this->limit);
		}
		return $query;
	}

	private function setOptions($params){
		$opts = array(
			'blacklist'=>array('__phreezer_hash')
		);
		switch($params['format']){
			case 'thaw':
				$opts['thaw'] = true;
				break;
			case 'json':
				$opts['json'] = true;
				break;
			default:
				$opts['filter'] = $params['filter'];
				break;
		}
		return $opts;
	}

	public function page($pagenum, $limit){
		$this->pagenum = (int)$pagenum;
		$this->limit = (int)$limit;
		return $this;
	}
	public function async(){
		$this->async = true;
		return $this;
	}
	public function desc(){
		$this->desc = 'true';
		return $this;
	}
}
