<?php
if (!extension_loaded("pthreads")) {

	class Threaded implements ArrayAccess, Countable, IteratorAggregate {
		const NOTHING = (0);
		const STARTED = (1<<0);	
		const RUNNING = (1<<1);
		const JOINED  = (1<<2);
		const ERROR   = (1<<3);

		public function offsetSet($offset, $value) { 
			if ($offset === null) {
				$offset = count($this->data);
			}

			if (!$this instanceof Volatile) {
				if ($this->data[$offset] instanceof Threaded) {
					throw new \RuntimeException();
				}
			}

			if (is_array($value)) {
				$safety = 
					new Volatile();
				$safety->merge(
					$this->convertToVolatile($value));
				$value = $safety;
			}
			
			return $this->data[$offset] = $value;
		}

		public function offsetGet($offset) { 
			return $this->data[$offset]; 
		}

		public function offsetUnset($offset) {
			if (!$this instanceof Volatile) {
				if (isset($this->data[$offset]) && $this->data[$offset] instanceof Threaded) {
					throw new \RuntimeException();
				}
			}
			unset($this->data[$offset]); 
		}

		public function offsetExists($offset) { 
			return isset($this->data[$offset]); 
		}

		public function count() { 
			return count($this->data); 
		}

		public function getIterator() { 		
			return new ArrayIterator($this->data); 
		}

		public function __set($offset, $value) { 
			$this->offsetSet($offset, $value); 
		}

		public function __get($offset) 		 { 
			return $this->offsetGet($offset); 
		}

		public function __isset($offset)		 { 
			return $this->offsetExists($offset); 
		}

		public function __unset($offset)		 { 
			return $this->offsetUnset($offset); 
		}

		public function shift() { 
			return array_shift($this->data); 
		}

		public function chunk($size) {
			$chunk = [];
			while (count($chunk) < $size) {
				$chunk[] = $this->shift();
			}
			return $chunk;
		}

		public function pop() { 
			return array_pop($this->data); 
		}

		public function merge($merge) {
			foreach ($merge as $k => $v) {
				$this->data[$k] = $v;
			}
		}
		
		public function wait($timeout = 0) {
			return true;
		}

		public function notify() {
			return true;
		}

		public function synchronized(Closure $closure, ... $args) {
			$closure(...$args);
		}

		public function isRunning() { 
			return $this->state & THREAD::RUNNING; 
		}

		public function isTerminated() { 
			return $this->state & THREAD::ERROR; 
		}

		public static function extend($class) { return true; }

		public function addRef() {}
		public function delRef() {}
		public function getRefCount() {}

		public function lock() { return true; }
		public function unlock() { return true; }
		public function isWaiting() { return false; }

		public function run() {}

		private function convertToVolatile($value) {
			if (is_array($value)) {
				foreach ($value as $k => $v) {
					if (is_array($v)) {
						$value[$k] = 
							new Volatile();
						$value[$k]->merge($v);
					}
				}
			}
			return $value;
		}

		private $data;
		protected $state;
	}
}
