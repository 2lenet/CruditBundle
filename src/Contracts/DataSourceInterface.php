<?php


interface DataSourceInterface
{
    public function get($id);
    public function cget();
    public function delete($id);
    public function put($id, $data);
    public function patch($id, $data);
}
