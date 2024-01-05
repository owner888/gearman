<?php

namespace SimpleHttpClient;

use \SplQueue;
use \Event;
use \EventUtil;
use \EventBufferEvent;

class Context
{
	use \SimpleHttpClient\Config;

	private $config;
	private $count;
	private $buffers;
	private $finished;
	protected $queue;

	public function __construct(array $config){
		$this->config = $config;
		$this->count = 0;
		$this->buffers = array();
		$this->finished = array();
		$this->queue = new SplQueue();
	}

	public function getBuffers($filter = null){
		$return = array();
		foreach($this->buffers as $key=>$value){
			if(is_string($filter)){
				$return[$key] = $this->commonFilters($filter, $value);
			}
			elseif(is_callable($filter)){
				$return[$key] = $filter($value);
			}
			else{
				return $this->buffers;
			}
		}
		return $return;
	}

	public function cleanUp(){
		$this->buffers = array();
	}

	public function getCount(){
		return $this->count;
	}

	public function isDone(){
		return count($this->finished) >= $this->getCount();
	}

	public function get($path){
		return $this->sendRequest('GET',$path);
	}

	public function put($path,$body){
		return $this->sendRequest('PUT',$path,$body);
	}

	public function post($path,$body){
		return $this->sendRequest('POST',$path,$body);
	}

	public function custom($method,$path,$body){
		return $this->sendRequest($method,$path,$body);
	}

	public function delete($path, $body){
		return $this->sendRequest('DELETE', $path, $body);
	}

	public function dispatch(){
		if(!empty($this->queue)){
			while(count($this->queue) > 0){
				$fn = $this->queue->dequeue();
				$fn(true);
			}
		}
	}

	public function fetch(){
		if(!empty($this->queue)){
			while(count($this->queue) > 0){
				$fn = $this->queue->dequeue();
				$fn();
			}
			$this->getBase()->dispatch();
		}
	}

	public function sendRequest($method, $url, $body = '') {
		$host = $this->getHost();
		$port = $this->getPort();
		$base = $this->getBase();
		$count = ++$this->count;
		$fn = function($callback = false) use($base, $host, $port, $method, $url, $body, $count) {

			$this->buffers[$count] = '';

			$readcb = function($bev, $count){
				//$bev->readBuffer($bev->input);
				$this->buffers[$count] .= $bev->input->read($bev->input->length);
			};

			$eventcb = function($bev, $events, $count) use($callback){
				if($events & (EventBufferEvent::ERROR | EventBufferEvent::EOF)){
					if($events & EventBufferEvent::ERROR){
						$this->errors[$count] = 'DNS error: '.$bev->getDnsErrorString().PHP_EOL;
					}
					if($events & EventBufferEvent::EOF){

						$this->buffers[$count] .= $bev->input->read($bev->input->length);

						$this->finished[$count] = true;
						if($this->isDone()){
							$processor = $this->getProcessor();
							if($callback && !empty($processor)){
								$cb = $this->getCallback();
								$processor($cb, $count, $this->buffers[$count]);
							}
							else if($callback){
								$cb = $this->getCallback();
								$cb();
							}
							else{
								$this->getBase()->stop();
							}
						}

					}
				}
			};

			$readcb->bindTo($this);
			$eventcb->bindTo($this);
			$bev = new EventBufferEvent($this->getBase(), NULL,
				EventBufferEvent::OPT_CLOSE_ON_FREE | EventBufferEvent::OPT_DEFER_CALLBACKS,
				$readcb, NULL, $eventcb, $count
			);
			$bev->setWatermark(Event::READ|Event::WRITE, 1, 0);

			$bev->enable(Event::READ | Event::WRITE);

			$output = $bev->output;

			if (!$output->add(
				"{$method} {$url} HTTP/1.0\r\n".
				"Host: {$this->getHost()}:{$this->getPort()}\r\n".
				(empty($this->getUser()) ? '' : 'Authorization: Basic '.base64_encode($this->getUser().':'.$this->getPassword())."\r\n").
				"Content-Type: {$this->getContentType()}\r\n".
				'Content-Length: ' . strlen($body) . "\r\n".
				"Connection: Close\r\n\r\n{$body}"
			)) {
				exit("Failed adding request to output buffer\n");
			}

			if (!$bev->connectHost($this->getDnsBase(), $this->getHost(), $this->getPort(), EventUtil::AF_UNSPEC)) {
				exit("Can't connect to host {$this->getHost()}\n");
			}

		};
		$fn->bindTo($this);
		$this->queue->enqueue($fn);
	}

    public function parseHeaders($raw_headers){

        // for headers that continue to next line
        $headers = preg_replace("/\r\n\s+?/"," ",$raw_headers);

        $headers = explode("\r\n",$headers);
        $head = array_shift($headers);

        list($protocol,$code,$status) = preg_split("/[\s]+/", trim($head),-1,PREG_SPLIT_NO_EMPTY);

        $parsed_headers = array(
            '__head__'=>array(
                'protocol'=>$protocol,
                'code'=>$code,
                'status'=>$status
            )
        );

        foreach($headers as $header){
            list($k,$v) = explode(':',$header,2);
            $parsed_headers[strtolower(trim($k))] = trim($v);
        }

        return $parsed_headers;
    }

    private function commonFilters($filterName, $value){
        switch($filterName){
            case 'raw_headers':
                list($header,$body) = explode("\r\n\r\n",$value,2);
                return $header;
                break;
            case 'body':
                list($header,$body) = explode("\r\n\r\n",$value,2);
                return $body;
                break;
            case 'parsed_headers':
                list($header) = explode("\r\n\r\n",$value,2);
                return $this->parseHeaders($header);
                break;
        }
    }

}
