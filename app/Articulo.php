<?php

namespace sisVentas;

use Illuminate\Database\Eloquent\Model;

class Articulo extends Model
{
    protected $table = 'articulo';

    protected $primaryKey = 'idarticulo';

    public $timestamps = false;

    protected $fillable =[
        'idarticulo',
        'codigo',
        'nombre',
        'stock',
        'descripcion',
        'imagen',
        'estado'
    ];

    protected $guarded = [
        
    ];
}
