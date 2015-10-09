<?php
if (!extension_loaded("pthreads")) {

	class Pool {

		public function __construct($size, $class = \Worker::class, $ctor = []) {
			$this->size = $size;
			$this->clazz = $class;
			$this->ctor = $ctor;
		}

		public function submit(Collectable $collectable) {
			if ($this->last > $this->size) {
				$this->last = 0;
			}

			if (!$collectable instanceof Threaded) {
				throw new \RuntimeException();
			}

			if (!isset($this->workers[$this->last])) {
				$this->workers[$this->last] = 
					new $this->clazz(...$this->ctor);
				$this->workers[$this->last]->start();
			}

			$this->workers[$this->last++]->stack($collectable);		
		}

		public function submitTo($worker, Collectable $collectable) {
			if (!$collectable instanceof Threaded) {
				throw new \RuntimeException();
			}

			if (isset($this->workers[$worker])) {
				$this->workers[$worker]->stack($collectable);
			}
		}

		public function collect(Closure $collector) {
			$total = 0;
			foreach ($this->workers as $worker)
				$total += $worker->collect($collector);
			return $total;
		}

		public function resize($size) {
			if ($size < $this->size) {
				while ($this->size > $size) {
					if (isset($this->workers[$this->size-1]))
						$this->workers[$this->size-1]->shutdown();
					unset($this->workers[$this->size-1]);
					$this->size--;
				}
			}
		}

		public function shutdown() {
			unset($this->workers);
		}

		protected $workers;
		protected $size;
		protected $last;
		protected $clazz;
		protected $ctor;
	}
}


