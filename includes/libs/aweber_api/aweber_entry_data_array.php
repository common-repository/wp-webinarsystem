<?php

class WSAWeberEntryDataArray implements ArrayAccess, Countable, Iterator  {
    private $counter = 0;

    protected $data;
    protected $keys;
    protected $name;
    protected $parent;

    public function __construct($data, $name, $parent) {
        $this->data = $data;
        $this->keys = array_keys($data);
        $this->name = $name;
        $this->parent = $parent;
    }

    public function count(): int {
        return sizeOf($this->data);
    }

    public function offsetExists($offset): bool {
        return (isset($this->data[$offset]));
    }

    public function offsetGet($offset): mixed {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value): void {
        $this->data[$offset] = $value;
        $this->parent->{$this->name} = $this->data;
        //return $value;
    }

    public function offsetUnset($offset): void {
        unset($this->data[$offset]);
    }

    public function rewind(): void {
        $this->counter = 0;
    }

    public function current(): mixed {
        return $this->data[$this->key()];
    }

    public function key(): mixed {
        return $this->keys[$this->counter];
    }

    public function next(): void {
        $this->counter++;
    }

    public function valid(): bool {
        if ($this->counter >= sizeOf($this->data)) {
            return false;
        }
        return true;
    }


}



?>
