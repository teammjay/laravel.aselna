<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileSystem extends Model
{
    protected $table = 'file_systems';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'type_' , 'host', 'port', 'login_id', 'password', 'root_path', 'url_base_path'];
}
