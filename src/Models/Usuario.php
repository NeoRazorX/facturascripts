<?php
namespace FacturaScripts\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Usuario extends Eloquent {
	protected $table = "fs_users";
	protected $primaryKey = "nick";
	public $incrementing = false;
	public $timestamps = false;
}