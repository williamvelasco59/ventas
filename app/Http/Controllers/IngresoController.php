<?php

namespace sisVentas\Http\Controllers;

use Illuminate\Http\Request;

use sisVentas\Http\Requests;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use sisVentas\Http\Requests\IngresoFormRequest;
use sisVentas\Ingreso;
use sisVentas\DetalleIngreso;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Response;
use Illuminate\Support\Collection;



class IngresoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(Request $request){
        if ($request) {
            $query=trim($request->get('searchText'));
            $ingresos=DB::table('ingreso as i')
                ->join('persona as p','i.idproveedor','=','p.idpersona')
                ->join('detalle_ingreso as di', 'i.idingreso','=','di.idingreso')
                ->select('i.idingreso','i.fecha_hora','p.nombre','i.tipo_comprobante','i.serie_comprobante','i.num_comprobante','i.impuesto','i.estado',DB::raw('sum(di.cantidad*precio_compra) as total'))
                ->where('i.num_comprobante','LIKE','%'.$query.'%')
                ->orderBy('i.idingreso','desc')
                ->groupBy('i.idingreso','i.fecha_hora','p.nombre','i.tipo_comprobante','i.serie_comprobante','i.num_comprobante','i.impuesto','i.estado')
                ->paginate(7);
                return view('compras.ingreso.index',["ingresos"=>$ingresos,"searchText"=>$query]);

        }
    }

    public function create(){
        $personas=DB::table('persona')->where('tipo_persona','=','Proveedor')->get();
        $articulos=DB::table('articulo as art')
            ->select(DB::raw('CONCAT(art.codigo, " ",art.nombre) AS articulo'),'art.idarticulo')
            ->where('art.estado','=','Activo')
            ->get();
        return view("compras.ingreso.create",["personas"=>$personas,"articulos"=>$articulos]);
    }

    public function store (IngresoFormRequest $request){
        try {
            DB::beginTransaction();
            $ingreso=new Ingreso;
            $ingreso->idproveedor=$request->get('idproveedor');
            $ingreso->tipo_comprobante=$request->get('tipo_comprobante');
            $ingreso->serie_comprobante=$request->get('serie_comprobante');
            $ingreso->num_comprobante=$request->get('num_comprobante');

            $mytime=Carbon::now('America/La_Paz');
            $ingreso->fecha_hora=$mytime->toDateTimeString();
            $ingreso->impuesto='18';
            $ingreso->estado='A';
            $ingreso->save();

            $idarticulo=$request->get('idarticulo');
            $cantidad=$request->get('cantidad');
            $precio_compra=$request->get('precio_compra');
            $precio_venta=$request->get('precio_venta');
            
            $count=0;
            while ($count < count($idarticulo)) {
                $detalle=new DetalleIngreso();
                $detalle->idingreso=$ingreso->idingreso;
                $detalle->idarticulo=$idarticulo[$count];
                $detalle->cantidad=$cantidad[$count];
                $detalle->precio_compra=$precio_compra[$count];
                $detalle->precio_venta=$precio_venta[$count];
                $detalle->save();
                $count=$count+1;


            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }

        return Redirect::to('compras/ingreso');
    }

    public function show($id){
        $ingreso=DB::table('ingreso as i')
            ->join('persona as p','i.idproveedor','=','p.idpersona')
            ->join('detalle_ingreso as di', 'i.idingreso','=','di.idingreso')
            ->select('i.idingreso','i.fecha_hora','p.nombre','i.tipo_comprobante','i.serie_comprobante','i.num_comprobante','i.impuesto','i.estado',DB::raw('sum(di.cantidad*precio_compra) as total'))
            ->where('i.idingreso','=',$id)
            ->first();

        $detalles=DB::table('detalle_ingreso as d')
            ->join('articulo as a', 'd.idarticulo','=','a.idarticulo')
            ->select('a.nombre as articulo','d.cantidad','d.precio_compra','d.precio_venta')
            ->where('d.idingreso','=',$id)
            ->get();

        return view("compras.ingreso.show",["ingreso"=>$ingreso,"detalles"=>$detalles]);

    }

    public function destroy($id){
        $ingreso=ingreso::findOrFail($id);
        $ingreso->estado='C';
        $ingreso->update();
        return Redirect::to('compras/ingreso');
    }
}
