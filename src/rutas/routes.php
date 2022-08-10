<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new \Slim\App;


require __DIR__ . '/../dotenv/dotenvRun.php';
require __DIR__ . '/../jwtMiddleware/tuupola.php';
require __DIR__ . '/../class/auth.php';
require __DIR__ . '/../funciones/funciones.php';
require __DIR__ . '/../class/classRegistros.php';
require __DIR__ . '/../class/classPaginador.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../variables/global_var.php';
require __DIR__ . '/../../vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\IOFactory;


$_SESSION['documento'] = IOFactory::load('../prueba3.xlsx');

$app->add(Tuupola());



//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//////////////////////////////* Usuario *////////////////////////////////////
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||

$app->get('/user/verification', function (Request $request, Response $response) {

    return "OK";
    
});


$app->group('/api/user/', function () use ($app) {
    
    $app->post('authenticate', function (Request $request, Response $response) {
        $body = json_decode($request->getBody());
    
        $sql = "SELECT `usuarios`.*
                FROM `usuarios`";
        $db = new DB();
        $resultado = $db->consultaAll('usuarios', $sql);
        
        
        $body=json_decode($body->body);
        
        foreach ($resultado as $key => $user) {
        if ($user['nick'] == $body->user && $user['pass'] == $body->pass) {
            $current_user = $user;
        }}
    
        if (!isset($current_user)) {
            echo json_encode("No user found");
        } else{
    
            $sql = "SELECT * FROM tokens
                 WHERE id_usuario_token  = ?";
    
            try {
                $db = new DB();
                $token_from_db = $db->consultaAll('usuarios', $sql, [$current_user['id_usuario']], 'objeto');
                
                $db = null;
                if ($token_from_db) {
                    return $response->withJson([
                    "Token" => $token_from_db->token,
                    "User_render" =>$current_user['id_rol'], 
                   // "Hidrologica" =>$current_user
                    ]);
                }    
                }catch (Exception $e) {
                $e->getMessage();
                }
    
            if (count($current_user) != 0 && !$token_from_db) {
    
    
                 $data = [
                    "user_login" => $current_user['nick'],
                    "user_id"    => $current_user['id_usuario'],
                    "user_estado"    => $current_user['id_estado'],
                    "user_municipio"=>$current_user['id_municipio'],
                    "user_parroquia"=>$current_user['id_parroquia'],
                    "user_rol"    => $current_user['id_rol']
                ];
    
                 try {
                    $token=Auth::SignIn($data);
                 } catch (Exception $e) {
                     echo json_encode($e);
                 }
    
                  $sql = "INSERT INTO tokens (id_usuario_token, token)
                      VALUES (?, ?)";
                  try {
                        $hoy = (date('Y-m-d', time()));
                        $db = new DB();
                        $db = $db->consultaAll('usuarios', $sql, [$current_user['id_usuario'], $token]);
                        
                        
                        return $response->withJson([
                        "Token" => $token,
                        "User_render" =>$current_user['id_rol']
                        ]);
     
                  } catch (PDOException $e) {
                      echo '{"error":{"text":' . $e->getMessage() . '}}';
                  }
             }
        }
    
    });

    $app->post('create', function (Request $request, Response $response) { 
        $scope=$request->getAttribute('jwt')["data"]->scope[0];
        if (userVerification($scope) !== false) {
            $datos = json_decode($request->getBody());
            $pass = generar_password_complejo(8);
                
            try {
                $sql = "INSERT INTO `usuarios`(`id_usuario`, `nick`, `email`, `pass`, `id_rol`, `id_acceso`, `id_estado`, id_municipio, id_parroquia) VALUES (null, 0, ?, ?, ?, ?, ?, ?, ?)";
                $db = new DB();
                $resultado = $db->consultaAll('usuarios', $sql, [$datos->email, $pass, $datos->id_rol, $datos->id_acceso, $datos->id_estado, $datos->id_municipio, $datos->id_parroquia]);
                if ($resultado) {
                    $sql = "UPDATE `usuarios` SET `nick`=? WHERE usuarios.id_usuario = ?";
                    $nick = $datos->nick."-".$resultado->insert_id;
                    $resultado2 = $db->consultaAll('usuarios', $sql, [$nick, $resultado->insert_id]);
                    $retorno = [
                        "id"=>$resultado->insert_id,
                        "nick"=>$nick,
                        "pass"=>$pass, 
                        "rol"=>$datos->id_rol
                    ];
                    return $response->withJson($retorno); 
                }
                //return $response->withJson($resultado);
                } 
            catch (MySQLDuplicateKeyException $e) {
                $e->getMessage();
            }
            catch (MySQLException $e) {
                $e->getMessage();
            }
            catch (Exception $e) {
                $e->getMessage();
            }
        } else {
            return $response->withStatus(401);
        }
    });

    $app->post('info/user', function (Request $request, Response $response) { 
        $body = json_decode($request->getBody());
        $nick = json_decode($body->body);
        
        try {
            $sql = "SELECT usuarios.id_usuario, usuarios.id_rol, usuarios.nick, usuarios.id_estado FROM usuarios WHERE usuarios.nick = ?";
            $db = new DB();
            $resultado = $db->consultaAll('usuarios', $sql, [$nick], 'objeto');
            $array = ["id" => $resultado->id_usuario, "scope" => $resultado->id_rol, "nick"=>$resultado->nick, "estado"=>$resultado->id_estado];
            return $response->withJson($array);          
            
            } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
    });



    
});


//||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//////////////////////////////* GET *///////////////////////////////////////||
//||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||



$app->get('/api/reportes/paginador[/{params:.*}]', function (Request $request, Response $response, $args) {
    /*
VALORES NECESARIOS 
0 - ID_ESTADO
1 - TIPO DE TABLA A VISUALIZAR
2 - PAGINA

*/

$TablaConsultar = $_SESSION['TypeConsult'];


$db = new DB();

if (!empty($args['params'])) {
    $params = EliminarBarrasURL($args['params']);
    
}
   $valorSQL = null;

   if (isset($params[0]) AND isset($params[1])) {
        if ($params[0] > 0 AND $params[0] < 25) {
            $sql1 = "SELECT COUNT(*) as Paginas
            FROM reporte 
            LEFT JOIN `tablas` ON `reporte`.`id_tabla` = `tablas`.`id` 
            LEFT JOIN `estados` ON `reporte`.`id_estado` = `estados`.`id_estado` 
            WHERE reporte.id_estado = ? AND reporte.id_tabla = ?";

            $datos = $db->consultaAll('mapa',$sql1, [$params[0], $params[1]]);

       }else {
            $sql1 = "SELECT COUNT(*) as Paginas
            FROM reporte 
            LEFT JOIN `tablas` ON `reporte`.`id_tabla` = `tablas`.`id` 
            LEFT JOIN `estados` ON `reporte`.`id_estado` = `estados`.`id_estado` 
            WHERE reporte.id_tabla = ?";

            $datos = $db->consultaAll('mapa',$sql1, [$params[1]]);
       }
   
}else {
    $sql1 = "SELECT COUNT(*) as Paginas
    FROM reporte 
    LEFT JOIN `tablas` ON `reporte`.`id_tabla` = `tablas`.`id` 
    LEFT JOIN `estados` ON `reporte`.`id_estado` = `estados`.`id_estado` ";

    $datos = $db->consultaAll('mapa',$sql1);
}
   
    $pagina = isset($params[2]) ?(int)$params[2] : 1;


    $regPagina = 12;
    $inicio = ($pagina > 1) ? (($pagina * $regPagina) - $regPagina) : 0 ;


    if (isset($params[0]) AND isset($params[1])){

        if ($params[0] > 0 AND $params[0] < 25) {
            $sql2 = "SELECT SQL_CALC_FOUND_ROWS  reporte.* , estados.estado, tablas.tipo_reporte 
            FROM reporte 
            LEFT JOIN `tablas` ON `reporte`.`id_tabla` = `tablas`.`id` 
            LEFT JOIN `estados` ON `reporte`.`id_estado` = `estados`.`id_estado` 
            WHERE reporte.id_estado = ? AND reporte.id_tabla = ?
            ORDER BY reporte.id DESC
            LIMIT $inicio , $regPagina ";
            $resultado = $db->consultaAll('mapa',$sql2, [$params[0], $params[1]]);
        }else{
            $sql2 = "SELECT SQL_CALC_FOUND_ROWS  reporte.* , estados.estado, tablas.tipo_reporte 
            FROM reporte 
            LEFT JOIN `tablas` ON `reporte`.`id_tabla` = `tablas`.`id` 
            LEFT JOIN `estados` ON `reporte`.`id_estado` = `estados`.`id_estado` 
            WHERE reporte.id_tabla = ?
            ORDER BY reporte.id DESC
            LIMIT $inicio , $regPagina ";
            $resultado = $db->consultaAll('mapa',$sql2, [$params[1]]);
        }
        


    }else {
        $sql2 = "SELECT SQL_CALC_FOUND_ROWS  reporte.* , estados.estado, tablas.tipo_reporte 
        FROM reporte 
        LEFT JOIN `tablas` ON `reporte`.`id_tabla` = `tablas`.`id` 
        LEFT JOIN `estados` ON `reporte`.`id_estado` = `estados`.`id_estado` 
        ORDER BY reporte.id DESC
        LIMIT $inicio , $regPagina";
        $resultado = $db->consultaAll('mapa',$sql2);
    }
 
    return   json_encode([
        "paginas"=>$pagina,
        "datos"=>$resultado
    ]);

  
   
});


         


//||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||PAGINADOR|||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||

$app->get('reportes[/{params:.*}]', function (Request $request, Response $response, $args) {
$valorSQL = null;
    /*
        ruta para obtener los reportes de el sistema
        1) existen tres parametros opcionales en la ruta, params[1] es el numero de paginacion, params[2] es el tipo de busqueda que vas a hacer si es el caso, params[3] es el id que se utiliza para formular la busqueda
        
    */

    $idHidrologica=$request->getAttribute('jwt')["data"]->user_hidrologica;
    /*
    Obteniendo user_hidrologica del usuario para el primer render de la paginacion, se obtiene por el Token
    1) se optiene el token para asi poder verificar o dar la informacion en base a lo que presenta el usuario en su informacion personal
    2) en esta opcion se optione el user_hidrologica que es el id de la hidrologica del usuario
    3) es totalmente necesario para el funcionamiento de la ruta
    */

    if (!empty($args['params'])) { //validamos si la ruta tiene algun valor opcional en el url

        ;
        $params = EliminarBarrasURL($args['params']);
        
        $tipoConsulta = null;
        if (count($params)>3) {
            $params[4] = trim(rawurldecode($params[4]), ' ');
            //comprobamos que en el params[1] venga el valor de busqueda
            if ($params[3] === "busqueda") {
                $array = [];
                if ($params[4][0]==="#") {
                    //si es un id, que se identifica por tener como primer valor el #, entonces extraemos los parametros para la consulta mysql, se le pasa como segundo valor a la funcion, el tipo, que en este caso es id
                    $tipoConsulta = ExtraerConsultaParametro($params[3], 'id');         
                        $paramsSaneado = explode('-', $params[4]);
                        $params[4] = [$paramsSaneado[1]];
                }else {
                    // en caso contrario se pasa nada mas el primer parametro, y se crea un array con los valores duplicados para enviarlos a la consulta
                    $tipoConsulta = ExtraerConsultaParametro($params[3]);
                    for ($i=0; $i < count($tipoConsulta); $i++) {
                        $param = urldecode($params[4]);
                        $param = '%'.$params[4].'%';               
                        array_push($array, $param);
                    }
                    $params[4]=$array;
                    
                }
            }else {
                $tipoConsulta = ExtraerConsultaParametro($params[3]);
                $params[3]=[ucfirst($params[4])];
                
            }
        }
        if ($tipoConsulta !== null) {
            $where = CondicionalMYSQL($idHidrologica, $tipoConsulta, $params[4]);
        }else {
            $where = CondicionalMYSQL($idHidrologica);
        }

        

        $sql = "SELECT COUNT(*) as Paginas
                FROM $valorSQL
                LEFT JOIN reporte ON $valorSQL.id_reporte = reporte.id_reporte
                WHERE $valorSQL.id_orientacion_proyecto = ? AND $valorSQL.id_validacion = ?
                {$where}";


        
               

        if ($where !== "") {
            if ($tipoConsulta!==null) {
                if ($idHidrologica===20) {
                    $db = new DB(); 
                    $datos = array($db->consultaAll('mapa', $sql, [$params[0], $params[1] , ...$params[4]])[0]['Paginas'],$params[2]);
                    
                    
                }else{
                    $db = new DB();
                    $param= [$params[0],$params[1], $idHidrologica, ...$params[4]];
                    $datos = array($db->consultaAll('mapa', $sql,  $param)[0]['Paginas'], $params[2]);
                    
                }
            }else {
                $db = new DB();
                $datos = array($db->consultaAll('mapa', $sql, [$params[0], $params[1] , $idHidrologica])[0]['Paginas'],$params[2]);
                
            }
        }else {
            $db = new DB();
            $datos = array($db->consultaAll('mapa',$sql,[$params[0], $params[1]])[0]['Paginas'], $params[2]);
            
            
        }

        if ($params[2] < 1 || $params[2] > ceil($datos[0] / 20)){
            return "La pagina solicitada no existe";
        }else{
            $paginador = New paginadorIncidencia($tipoConsulta!==null?$params[4]:null, $idHidrologica, $tipoConsulta);
             return json_encode($paginador->paginadorIncidencias($datos, $params[0]));             
        }
    }else {
        $sql = "SELECT COUNT(*) as Paginas
        FROM reporte
        LEFT JOIN reporte ON reporte.id_tabla = tablas.id
        WHERE id_tabla = $valorSQL
        ";
         try {
            $db = new DB();
            $resultado = $db->consultaAll('mapa',$sql);  
            return $response->withJson($resultado);                  
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
    }
        
});

//////////////////////////////// DESPLEGABLES ///////////////////////////////////////////

//////////////////////////////////////ESTADOS
$app->get('/api/desplegables/estados[/{id}]', function (Request $request, Response $response) {
    $id = $request->getAttribute('id')+0;
    if ($id) {
        $sql = "SELECT hidrologicas.* FROM hidrologicas WHERE hidrologicas.id_hidrologica = ?";
        $db = new DB();
        $resultado = $db->consultaAll('mapa',$sql, [$id]);

        if ($resultado) {

            $sql = "SELECT estados.id_estado, estados.estado
                    FROM estados
                    WHERE estados.id_estado 
                    IN (? , ? , ?)";
            $resultado = $db->consultaAll('mapa',$sql, [$resultado[0]['id_estado'],$resultado[0]['id_estado2'],$resultado[0]['id_estado3']]);
            $val= count($resultado)-1;
            unset($resultado[$val]);
            return $response->withJson($resultado); 
        }
    }else{
        $sql = "SELECT `estados`.`id_estado`, `estados`.`estado` FROM `estados`";
        $db = new DB();
        $resultado = $db->consultaAll('mapa',$sql);
        $val= count($resultado)-1;
        unset($resultado[$val]);      
        return $response->withJson($resultado);                        
    }
    
        
});
 
               
//////////////////////////////////////MUNICIPIOS
        $app->get('/api/desplegables/municipios/{id_estado}', function (Request $request, Response $response) {
            $id = $request->getAttribute('id_estado');
            
            
            $sql = "SELECT municipios.id_municipio, municipios.municipio, estados.id_estado
                FROM municipios 
                LEFT JOIN estados ON municipios.id_estado = estados.id_estadO
                WHERE municipios.id_estado = ?";
                $db = New DB();
                $resultado = $db->consultaAll('mapa',$sql,[$id]);

                return validarDatosReturn($resultado, $response);
    
        });
        
        
        
/////////////////////////////////////PARROQUIAS
    $app->get('/api/desplegables/parroquias/{id_municipio}', function (Request $request, Response $response) {
        $id = $request->getAttribute('id_municipio');
        
        
        $sql = "SELECT parroquias.id_parroquia, parroquias.parroquia, municipios.id_municipio 
                FROM parroquias
                LEFT JOIN municipios ON parroquias.id_municipio = municipios.id_municipio 
                WHERE municipios.id_municipio = ?";
                    $db = New DB();
                    $resultado = $db->consultaAll('mapa',$sql,[$id]);

                    return validarDatosReturn($resultado, $response);
        
                    
                    
    });



    $app->get('/api/desplegables/pozos[/{id_estado}]', function (Request $request, Response $response) {
        
        if ($request->getAttribute('id_estado')) {
           
            $id = $request->getAttribute('id_estado');
        }
        
        $db = New DB();

        if (isset($id)) {
            $sql = "SELECT `pozo`.*, `estados`.`estado`
                    FROM `pozo` 
                    LEFT JOIN `estados` ON `pozo`.`id_estado` = `estados`.`id_estado`
            WHERE estados.id_estado = ?";
            $resultado = $db->consultaAll('mapa',$sql,[$id]);
        }else {
            $sql = "SELECT `pozo`.*, `estados`.`estado`
                    FROM `pozo` 
                    LEFT JOIN `estados` ON `pozo`.`id_estado` = `estados`.`id_estado`";
                $resultado = $db->consultaAll('mapa',$sql);

        }


        return validarDatosReturn($resultado, $response);       
                    
    });


    $app->get('/api/desplegables/brippas[/{id_estado}]', function (Request $request, Response $response) {
        
        if ($request->getAttribute('id_estado')) {
           
            $id = $request->getAttribute('id_estado');
        }
        
        $db = New DB();

        if (isset($id)) {
            $sql = "SELECT `brippas`.*, `estados`.`estado`,municipios.municipio, `parroquias`.`parroquia`
                    FROM `brippas` 
                    LEFT JOIN `estados` ON `brippas`.`id_estado` = `estados`.`id_estado`
                    LEFT JOIN `municipios` ON brippas.id_municipio = `municipios`.`id_municipio`
                    LEFT JOIN parroquias ON brippas.id_parroquia = `parroquias`.`id_parroquia`
                    WHERE `estados`.`id_estado` = ?";
            $resultado = $db->consultaAll('mapa',$sql,[$id]);
        }else {
            $sql = "SELECT `brippas`.*, `estados`.`estado`, `municipios`.`municipio`, `parroquias`.`parroquia`
                    FROM `brippas` 
                    LEFT JOIN `estados` ON `brippas`.`id_estado` = `estados`.`id_estado` 
                    LEFT JOIN `municipios` ON `brippas`.`id_municipio` = `municipios`.`id_municipio` 
                    LEFT JOIN `parroquias` ON `brippas`.`id_parroquia` = `parroquias`.`id_parroquia`";
            $resultado = $db->consultaAll('mapa',$sql);

        }
        return validarDatosReturn($resultado, $response);
        
                    
                    
    });


    $app->get('/api/desplegables/sistemas[/{id_estado}]', function (Request $request, Response $response) {
                
        if ($request->getAttribute('id_estado')) {
           
            $id = $request->getAttribute('id_estado');
        }
        $db = New DB();

        if (isset($id)) {
            $sql = "SELECT sistemas.*, `estados`.`estado`
            FROM `sistemas`
            LEFT JOIN `estados` ON `sistemas`.`id_estado` = `estados`.`id_estado` 
                    WHERE `estados`.`id_estado` = ?";
            $resultado = $db->consultaAll('mapa',$sql,[$id]);
        }else {
            $sql = "SELECT sistemas.*, `estados`.`estado`
            FROM `sistemas`
            LEFT JOIN `estados` ON `sistemas`.`id_estado` = `estados`.`id_estado` ";
            $resultado = $db->consultaAll('mapa',$sql);

        }
        return validarDatosReturn($resultado, $response);        
    });



    $app->get('/api/reportes/dia', function (Request $request, Response $response) {

        $estados_asociativos = [
            ["Amazonas" => []],
            ["Anzoátegui" => []],
            ["Apure" => []],
            ["Aragua" => []],
            ["Barinas" => []],
            ["Bolívar" => []],
            ["Carabobo" => []],
            ["Cojedes" => []],
            ["Delta Amacuro" => []],
            ["Falcón" => []],
            ["Guárico" => []],
            ["Lara" => []],
            ["Mérida" => []],
            ["Miranda" => []],
            ["Monagas" => []],
            ["Nueva Esparta" => []],
            ["Portuguesa" => []],
            ["Sucre" => []],
            ["Táchira" => []],
            ["Trujillo" => []],
            ["Vargas" => []], 
            ["Yaracuy" => []],
            ["Zulia" => []],
            ["Distrito Capital" => []]
        ];
        
        $sql = "SELECT reporte.*, tablas.tipo_reporte, estados.estado
        FROM `reporte`
        LEFT JOIN tablas ON reporte.id_tabla = tablas.id
        LEFT JOIN estados ON reporte.id_estado = estados.id_estado
        WHERE reporte.fecha = CURDATE()";
    
        $db = New DB();
        $ConsultResult = $db->consultaAll('mapa', $sql);
        if (count($ConsultResult) > 0) {
    
            for ($i=0; $i < count($ConsultResult) ; $i++) { 
                $state_id = $ConsultResult[$i]['id_estado'] -1;
                $name_state = $ConsultResult[$i]['estado'];
                $Type_report_set = $ConsultResult[$i]['tipo_reporte'];
    
                $estados_asociativos[$state_id][$name_state][$Type_report_set] = "REPORTADO";
            }      
            return validarDatosReturn($estados_asociativos, $response);
            
        }else {
            return validarDatosReturn($estados_asociativos, $response);
        }           
    });
          
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////      
$app->get('/api/pozo/{id_pozo}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id_pozo');

    $sql = "SELECT * FROM pozo WHERE pozo.id = ?";
    $db = New DB();
    $resultado = $db->consultaAll('mapa',$sql,[$id]);  

    return validarDatosReturn($resultado, $response);
                   
});


$app->get('/api/reportes/unico[/{params:.*}]', function (Request $request, Response $response, $args) {
    $params = EliminarBarrasURL($args['params']);  
    /*
    0 - Tipo de consulta ($Tabla a consultar)
    1 - id del reporte
    2 - 
    3 -      */ 
    $db = New DB();
  
    $TablaConsultar = $_SESSION['TypeConsult'];

   
    
    if (!empty($params[0]) && is_numeric($params[0])) {
        if (($params[0] >= 0) && ($params[0] <= 6)) {
            $params[0] = $params[0] + 0;
            $valorSQL = $TablaConsultar[$params[0]];
        }else {
            return 'TABLA A CONSULTAR NO VALIDA';
        }
    }else {
        return 'PARAMETROS DE BUSQUEDA NO VALIDOS';
    }
    

    if (count($params) === 2) {
        
        if (($params[0] === 1) || ($params[0] === 5) || ($params[0] === 4)) {
            switch ($params[0]) {
                case 1:
                    $valorSQL2 = $TablaConsultar[7];
                    
                    break;

                case 5:
                    $valorSQL2 = $TablaConsultar[9];
                    break;

                case 4:
                    $valorSQL2 = $TablaConsultar[8];
                    break;
                
                default:
                $valorSQL2 = null;
                    break;
            }
            $sql = "SELECT $valorSQL.*, `estados`.`estado`, $valorSQL2.*,reporte.fecha
                    FROM $valorSQL 
                        LEFT JOIN $valorSQL2 ON $valorSQL.id_$valorSQL2 = $valorSQL2.id
                        LEFT JOIN `estados` ON $valorSQL2.id_estado = `estados`.`id_estado`
	                    LEFT JOIN `reporte` ON $valorSQL.`id_reporte` = `reporte`.`id`

                        WHERE $valorSQL2.id = ?";
            
        }else {

            $sql = "SELECT $valorSQL.*, `estados`.`estado`,`reporte`.`fecha`
                    FROM $valorSQL 
                        LEFT JOIN `estados` ON $valorSQL.`id_estado` = `estados`.`id_estado`
	                    LEFT JOIN `reporte` ON $valorSQL.`id_reporte` = `reporte`.`id`

                        WHERE $valorSQL.id = ?";

        
        }
            $reporte= $db->consultaAll('mapa',$sql, [$params[1]]);
     
            return validarDatosReturn($reporte, $response);
    }elseif (count($params) === 3) {

        
    }elseif (count($params) === 4) {
        
    }else {
        return 'FALTAN O SOBRAN INSERTAR PARAMETROS PARA SOLICITAR EL REPORTE';
    }
    
            
});

$app->get('/api/reportes/estado[/{params:.*}]', function (Request $request, Response $response, $args) {
    $params = EliminarBarrasURL($args['params']);   
    /*
    0 - Tipo de formulario ($Tabla a consultar)
    1 - Estado
    2 - Municipio
    3 - Parroquia     */    
    $db = New DB();
    /**/
    $TablaConsultar = $_SESSION['TypeConsult'];
   
    
    if (!empty($params[0]) && is_numeric($params[0])) {
        if (($params[0] >= 0) && ($params[0] <= 6)) {
            $params[0] = $params[0] + 0;
            $valorSQL = $TablaConsultar[$params[0]];
        }else {
            return 'TABLA A CONSULTAR NO VALIDA';
        }
    }else {
        return 'PARAMETROS DE BUSQUEDA NO VALIDOS';
    }
    


    if (count($params) === 2) {
        
        if (($params[0] === 1) || ($params[0] === 5) || ($params[0] === 4)) {
            switch ($params[0]) {
                case 1:
                    $valorSQL2 = $TablaConsultar[7];
                    
                    break;

                case 5:
                    
                    $valorSQL2 = $TablaConsultar[9];
                    $valorSQL3 = 'sistema';
                    break;

                case 4:
                    $valorSQL2 = $TablaConsultar[8];
                    break;
                
                default:
                $valorSQL2 = null;
                    break;
            }
            if ($params[0] === 5) {
                $sql = "SELECT $valorSQL.*, `estados`.`estado`, $valorSQL2.*
                    FROM $valorSQL 
                        LEFT JOIN $valorSQL2 ON $valorSQL.id_$valorSQL3 = $valorSQL2.id
                        LEFT JOIN `estados` ON $valorSQL2.id_estado = `estados`.`id_estado`
                        WHERE $valorSQL2.id_estado = ?";
                
            }else {
                
            $sql = "SELECT $valorSQL.*, `estados`.`estado`, $valorSQL2.*
                    FROM $valorSQL 
                        LEFT JOIN $valorSQL2 ON $valorSQL.id_$valorSQL2 = $valorSQL2.id
                        LEFT JOIN `estados` ON $valorSQL2.id_estado = `estados`.`id_estado`
                        WHERE $valorSQL2.id_estado = ?";
            }


        }else {

            $sql = "SELECT $valorSQL.*, `estados`.`estado`
                    FROM $valorSQL 
                        LEFT JOIN `estados` ON $valorSQL.`id_estado` = `estados`.`id_estado`
                        WHERE $valorSQL.id_estado = ?";

           
        
        }
            $reporte= $db->consultaAll('mapa',$sql, [$params[1]]);

            return validarDatosReturn($reporte, $response);
     
        
    }elseif (count($params) === 3) {

        
    }elseif (count($params) === 4) {
        
    }else {
        return 'FALTAN O SOBRAN INSERTAR PARAMETROS PARA SOLICITAR EL REPORTE';
    }
    
        
});

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$app->get('/api/reportes/fecha[/{params:.*}]', function (Request $request, Response $response, $args) {
    $array = [];
    $params = EliminarBarrasURL($args['params']);
    /*params = 
    0 - Tipo de formulario
    1 - Fecha de Inicio
    2 - Fecha Final
        ///////////////
    3 - Si el reporte es por estados aca va el id del estado solicitado
    */
    $TablaConsultar = $_SESSION['TypeConsult'];
    
    $db = New DB();    

    if (isset($params[0]) && is_numeric($params[0])) {
        if (($params[0] >= 0) && ($params[0] <= 6)) {
            $params[0] = $params[0] + 0;
        }else {
            return 'TABLA A CONSULTAR NO VALIDA';
        }
    }else {
        return 'PARAMETROS DE BUSQUEDA NO VALIDOS';
    }

    if (count($params) === 3) {
        
        if ( ($params[0] === 1) || ($params[0] === 5) || ($params[0] === 4)) {
            $valorSQL = $TablaConsultar[$params[0]];
        
            switch ($params[0]) {
                case 1:
                    $valorSQL2 = $TablaConsultar[7];
                    break;

                case 5:
                    $valorSQL2 = $TablaConsultar[9];
                    $valorSQL3 = 'sistema';

                    break;

                case 4:
                    $valorSQL2 = $TablaConsultar[8];
                    break;
                
                default:
                $valorSQL2 = null;
                    break;
            }



            if ($params[0] === 5) {
                $sql = "SELECT $valorSQL.*, `reporte`.fecha , $valorSQL2.nombre, estados.estado
                FROM $valorSQL
                    LEFT JOIN `reporte` ON $valorSQL.id_reporte = `reporte`.`id`   
                    LEFT JOIN $valorSQL2 ON $valorSQL.id_$valorSQL3 = $valorSQL2.id
                    LEFT JOIN estados ON $valorSQL2.id_estado = estados.id_estado
                    WHERE reporte.fecha 
                    BETWEEN ? AND ?";
                
            }else {
                
            $sql = "SELECT $valorSQL.*, `reporte`.fecha , $valorSQL2.nombre, estados.estado
            FROM $valorSQL
                LEFT JOIN `reporte` ON $valorSQL.id_reporte = `reporte`.`id`   
                LEFT JOIN $valorSQL2 ON $valorSQL.id_$valorSQL2 = $valorSQL2.id
                LEFT JOIN estados ON $valorSQL2.id_estado = estados.id_estado
                WHERE reporte.fecha 
                BETWEEN ? AND ?";
            }



                $reporte= $db->consultaAll('mapa',$sql, [$params[1], 
                                                         $params[2]]);
                                                         
                return validarDatosReturn($reporte, $response);

        }else {

            $valorSQL = $TablaConsultar[$params[0]];

            $sql = "SELECT $valorSQL.*, `reporte`.fecha, estados.estado
            FROM $valorSQL
                LEFT JOIN `reporte` ON $valorSQL.id_reporte = `reporte`.`id`             
                LEFT JOIN `estados` ON $valorSQL.id_estado = estados.id_estado 
                WHERE reporte.fecha 
                BETWEEN ? AND ?";

                $reporte= $db->consultaAll('mapa',$sql, [$params[1], 
                                                         $params[2]]);
                                                         
            return validarDatosReturn($reporte, $response);
                                                         
        }
      

    }elseif (count($params) === 4) {
        
        if ( ($params[0] === 1) || ($params[0] === 5) || ($params[0] === 4)) {
            $valorSQL = $TablaConsultar[$params[0]];

            switch ($params[0]) {
                case 1:
                    $valorSQL2 = $TablaConsultar[7];
                    break;

                case 5:
                    $valorSQL2 = $TablaConsultar[9];
                    break;

                case 4:
                    $valorSQL2 = $TablaConsultar[8];
                    break;
                
                default:
                $valorSQL2 = null;
                    break;
            }

            if ($params[0] === 5) {
                $sql = "SELECT $valorSQL.*, `reporte`.fecha , $valorSQL2.nombre, estados.estado
            FROM $valorSQL
                LEFT JOIN `reporte` ON $valorSQL.id_reporte = `reporte`.`id`    
                LEFT JOIN $valorSQL2 ON $valorSQL.id_$valorSQL3 = $valorSQL2.id
                LEFT JOIN estados ON $valorSQL2.id_estado = estados.id_estado
                WHERE $valorSQL2.id_estado = ? AND reporte.fecha BETWEEN ? AND ?";
                
            }else {
                
            $sql = "SELECT $valorSQL.*, `reporte`.fecha , $valorSQL2.nombre, estados.estado
            FROM $valorSQL
                LEFT JOIN `reporte` ON $valorSQL.id_reporte = `reporte`.`id`   
                LEFT JOIN $valorSQL2 ON $valorSQL.id_$valorSQL2 = $valorSQL2.id
                LEFT JOIN estados ON $valorSQL2.id_estado = estados.id_estado
                WHERE reporte.fecha 
                BETWEEN ? AND ?";
            }


            $reporte= $db->consultaAll('mapa',$sql, [$params[3],
                                                     $params[1], 
                                                     $params[2]]);

             return validarDatosReturn($reporte, $response);
        

        }else{

        $valorSQL = $TablaConsultar[$params[0]];

        $sql = "SELECT $valorSQL.*, `reporte`.fecha, estados.estado
        FROM $valorSQL
            LEFT JOIN `reporte` ON $valorSQL.id_reporte = `reporte`.`id`                  
            LEFT JOIN `estados` ON $valorSQL.id_estado = estados.id_estado 
            WHERE $valorSQL.id_estado = ? AND reporte.fecha BETWEEN ? AND ?";

            $reporte= $db->consultaAll('mapa',$sql, [$params[3],
                                                     $params[1], 
                                                     $params[2]]);
            
             return validarDatosReturn($reporte, $response);
        }

    }else{
        return validarDatosReturn($array, $response);;
}     
});
////////////////////////////////////////////////////FIN/////////////////////////////////////////////////////// 

//////////////////////////////////////////////////////DASH/////////////////////////////////////////

    
    $app->get('/api/dashboard/ultimos_reportes', function (Request $request, Response $response) {
        
        $hoja_actual= $_SESSION['documento']->getSheet(0);
        $filas = $hoja_actual->getHighestDataRow();
        $letra =$hoja_actual->getHighestColumn();
        $array = [1,2];
        
        for ($i=0; $i < $filas; $i++) { 
            $valor = $hoja_actual->getCellByColumnAndRow(2,$i);
            //array_push($array, $valor);
            var_dump($valor);
        }
        
    //var_dump($array);





        /*        
        $sql = "SELECT `reporte`.*, tablas.tipo_reporte
        FROM `reporte`
        LEFT JOIN tablas ON reporte.id_tabla = tablas.id
        ";
        $db = New DB();

        $ultimos_reportes = $db->consultaAll('mapa',$sql);
        $values = array_slice($ultimos_reportes,-5);

        return validarDatosReturn($values, $response);*/
    });


    $app->get('/api/dashboard/lps_recuperados[/{params:.*}]', function (Request $request, Response $response, $args) {
//PARAMETROS NECESARIOS       
       
        //FECHA INICIAL
        //FECHA FINAL
        //ID_ESTADO
        
        if (!empty($args['params'])) {
            $params = EliminarBarrasURL($args['params']);
            
        }else {
            $array = [];
            return validarDatosReturn($array, $response);
        }

        
        if (isset($params[2])) {
            $id = $params[2];
        }

        if (!isset($params[1]) OR !isset($params[0])) {
           return  [
            "cod" => "error",
            "cont" => "FALTAN PARAMETROS DE FECHA" 
            ];
        }

        if (isset($id)) {
            $sql = "SELECT `reporte`.`fecha`, SUM(`rehabilitacion_pozo`.`lps`) AS total, `pozo`.`id_estado`, `estados`.`estado`
                FROM `reporte` 
                    LEFT JOIN `rehabilitacion_pozo` ON `rehabilitacion_pozo`.`id_reporte` = `reporte`.`id` 
                    LEFT JOIN `pozo` ON `rehabilitacion_pozo`.`id_pozo` = `pozo`.`id` 
                    LEFT JOIN `estados` ON `pozo`.`id_estado` = `estados`.`id_estado`
                    WHERE pozo.id_estado = ? AND reporte.fecha BETWEEN ? AND ?
                    GROUP BY (reporte.fecha)";
                $db = New DB();
                $resultado = $db->consultaAll('mapa',$sql, [$id,$params[0],$params[1]]);
        }else {
         $sql = "SELECT `reporte`.`fecha`, SUM(`rehabilitacion_pozo`.`lps`) AS total, `pozo`.`id_estado`, `estados`.`estado`
            FROM `reporte` 
            LEFT JOIN `rehabilitacion_pozo` ON `rehabilitacion_pozo`.`id_reporte` = `reporte`.`id` 
            LEFT JOIN `pozo` ON `rehabilitacion_pozo`.`id_pozo` = `pozo`.`id` 
            LEFT JOIN `estados` ON `pozo`.`id_estado` = `estados`.`id_estado`
            AND reporte.fecha BETWEEN ? AND ?
            GROUP BY (reporte.fecha)";
        $db = New DB();
        $resultado = $db->consultaAll('mapa',$sql,[$params[0],$params[1]]);
        }
        
        $array= $_SESSION['Meses'];
 
        for ($i=0; $i < count($resultado) ; $i++) { 
            $fecha = strtotime($resultado[$i]["fecha"]);
            $mes = date("m", $fecha);
            switch ($mes) {
                case '10':
                    break;
                
                case '11':
                    
                    break;
                
                case '12':
                    
                    break;
                
                default:
                $mes = substr($mes,-1);
                    break;
            }
            $array[$mes -1][1] = $array[$mes -1][1] + $resultado[$i]["total"];
    
        }  
        return validarDatosReturn($array, $response);   
    });




    $app->get('/api/dashboard/tomas_ilegales[/{params:.*}]', function (Request $request, Response $response, $args) {
//PARAMETROS NECESARIOS       
       
        //FECHA INICIAL
        //FECHA FINAL
        //ID_ESTADO
        
        if (!empty($args['params'])) {
            $params = EliminarBarrasURL($args['params']);
            
        }else {
            $array = [];
            return validarDatosReturn($array, $response);
        }

        
        if (isset($params[2])) {
            $id = $params[2];
        }

        if (!isset($params[1]) OR !isset($params[0])) {
           return  [
            "cod" => "error",
            "cont" => "FALTAN PARAMETROS DE FECHA" 
            ];
        }
    
            if (isset($id)) {
                $sql = "SELECT `reporte`.`fecha`, SUM(`tomas_ilegales`.`cantidad_tomas_eliminadas`)AS total, `estados`.`estado`
                FROM `reporte` 
                    LEFT JOIN `tomas_ilegales` ON `tomas_ilegales`.`id_reporte` = `reporte`.`id` 
                    LEFT JOIN `estados` ON `tomas_ilegales`.`id_estado` = `estados`.`id_estado`
                    WHERE tomas_ilegales.id_estado = ? AND reporte.fecha BETWEEN ? AND ?
                    GROUP BY (reporte.fecha)";
                    $db = New DB();
                    $resultado = $db->consultaAll('mapa',$sql, [$id,$params[0],$params[1]]);
            }else {
                $sql = "SELECT `reporte`.`fecha`, SUM(`tomas_ilegales`.`cantidad_tomas_eliminadas`)AS total, `estados`.`estado`
                FROM `reporte` 
                    LEFT JOIN `tomas_ilegales` ON `tomas_ilegales`.`id_reporte` = `reporte`.`id` 
                    LEFT JOIN `estados` ON `tomas_ilegales`.`id_estado` = `estados`.`id_estado`
                    AND reporte.fecha BETWEEN ? AND ?
                    GROUP BY (reporte.fecha)";
                     $db = New DB();
                     $resultado = $db->consultaAll('mapa',$sql,[$params[0],$params[1]]);
            }
            
              $array=$_SESSION['Meses'];
     
            for ($i=0; $i < count($resultado) ; $i++) { 
                $fecha = strtotime($resultado[$i]["fecha"]);
                $mes = date("m", $fecha);
                switch ($mes) {
                    case '10':
                        break;
                    
                    case '11':
                        
                        break;
                    
                    case '12':
                        
                        break;
                    
                    default:
                    $mes = substr($mes,-1);
                        break;
                }
                $array[$mes -1][1] = $array[$mes -1][1] + $resultado[$i]["total"];
        
            }  
            return validarDatosReturn($array, $response);
});
     



    $app->get('/api/dashboard/fugas[/{params:.*}]', function (Request $request, Response $response, $args) {
//PARAMETROS NECESARIOS       
       
        //FECHA INICIAL
        //FECHA FINAL
        //ID_ESTADO
        
        if (!empty($args['params'])) {
            $params = EliminarBarrasURL($args['params']);
            
        }else {
            $array = [];
            return validarDatosReturn($array, $response);
        }

        
        if (isset($params[2])) {
            $id = $params[2];
        }

        if (!isset($params[1]) OR !isset($params[0])) {
           return  [
            "cod" => "error",
            "cont" => "FALTAN PARAMETROS DE FECHA" 
            ];
        }


        if (isset($id)) {
            $sql = "SELECT `reporte`.`fecha`, SUM(`fugas`.`cantidad_fugas_reparadas`)AS total, `estados`.`estado`
            FROM `reporte` 
            LEFT JOIN `fugas` ON `fugas`.`id_reporte` = `reporte`.`id` 
            LEFT JOIN `estados` ON `fugas`.`id_estado` = `estados`.`id_estado`
            WHERE fugas.id_estado = ? AND reporte.fecha BETWEEN ? AND ?
            GROUP BY (reporte.fecha)";
                $db = New DB();
                $resultado = $db->consultaAll('mapa',$sql, [$id,$params[0],$params[1]]);

        }else {
            $sql = "SELECT `reporte`.`fecha`, SUM(`fugas`.`cantidad_fugas_reparadas`)AS total, `estados`.`estado`
            FROM `reporte` 
            LEFT JOIN `fugas` ON `fugas`.`id_reporte` = `reporte`.`id` 
            LEFT JOIN `estados` ON `fugas`.`id_estado` = `estados`.`id_estado`
            WHERE reporte.fecha BETWEEN ? AND ?
            GROUP BY (reporte.fecha)";
                 $db = New DB();
                 $resultado = $db->consultaAll('mapa',$sql,[$params[0],$params[1]] );

        }
        
          $array = $_SESSION['Meses'];

        for ($i=0; $i < count($resultado) ; $i++) { 
            $fecha = strtotime($resultado[$i]["fecha"]);
            $mes = date("m", $fecha);
            switch ($mes) {
                case '10':
                    break;
                
                case '11':
                    
                    break;
                
                case '12':
                    
                    break;
                
                default:
                $mes = substr($mes,-1);
                    break;
            }
            $array[$mes -1][1] = $array[$mes -1][1] + $resultado[$i]["total"];
    
        }  
        return validarDatosReturn($array, $response);
    });



    $app->get('/api/dashboard/pozos_operativos[/{id_estado}]', function (Request $request, Response $response) {      
        
        if ($request->getAttribute('id_estado')) {            
            $id = $request->getAttribute('id_estado'); 
        }   

        $db = New DB();

        if (isset($id)) {
            $sql = "SELECT COUNT(`pozo`.`id`) as total,pozo.operatividad, `estados`.`estado`
        FROM `pozo` 
            LEFT JOIN `estados` ON `pozo`.`id_estado` = `estados`.`id_estado`
                WHERE pozo.id_estado = ? AND pozo.operatividad = ?";

        $array = [
            "operativos" => $db->consultaAll('mapa',$sql, [$id,1]),
            "inoperativos" => $db->consultaAll('mapa',$sql, [$id,0])
        ];
            
        }else {
            $sql = "SELECT COUNT(`pozo`.`id`) as total,pozo.operatividad, `estados`.`estado`
            FROM `pozo` 
                LEFT JOIN `estados` ON `pozo`.`id_estado` = `estados`.`id_estado`
                    WHERE pozo.operatividad = ?";
    
            $array = [
                "operativos" => $db->consultaAll('mapa',$sql, [1]),
                "inoperativos" => $db->consultaAll('mapa',$sql, [0])
            ];
        }
        
        return validarDatosReturn($array, $response);                 

    });

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    $app->get('/api/reporte/mapa[/{params:.*}]', function (Request $request, Response $response, $args){
     //ENVIAR OBLIGATORIAMENTE 
     //FECHA INICIAL  
     //FECHA FINAL 
     //EL TIPO DE CONSULTA => 1 , 2 , 3 , 6 
     $array= $_SESSION['EstadosMapa'];
     
    if (!empty($args['params'])) {
        $params = EliminarBarrasURL($args['params']);
        
    }else {
        $array = [];
        return validarDatosReturn($array, $response);
    }

    if (isset($params[0]) AND isset($params[1])) {
        
        if (isset($params[2])) {
            if (($params[2] != 2) && ($params[2] != 1) && ($params[2] != 3) && ($params[2] != 6)) {
            $array = [];
            return validarDatosReturn($array, $response);
            }
        }

        $db = New DB();


    switch ($_SESSION["TypeConsult"][$params[2]]) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        case 'rehabilitacion_pozo':
            $sql = "SELECT COUNT(rehabilitacion_pozo.id) as total,reporte.fecha, reporte.id_estado, estados.estado
            FROM rehabilitacion_pozo 
                LEFT JOIN `reporte` ON `rehabilitacion_pozo`.`id_reporte` = reporte.id
                LEFT JOIN `estados` ON `reporte`.`id_estado` = estados.id_estado 
                WHERE reporte.fecha
                BETWEEN ? AND ?            
                    GROUP BY (reporte.id_estado)";        
            $PozosRehabilitados = $db->consultaAll('mapa',$sql, [$params[0],$params[1]]);
        
        if (count($PozosRehabilitados) > 0) {
            for ($i=0; $i < count($PozosRehabilitados) ; $i++) { 
                $estado = $PozosRehabilitados[$i]["id_estado"] - 1;
                $array[$estado][1] = $PozosRehabilitados[$i]["total"];
            }
        }

        $array= [["State", "Latitude"], ...$array];
        return validarDatosReturn($array, $response);                 

        break;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        case 'operatividad_abastecimiento':

        $sql = "SELECT AVG(operatividad_abastecimiento.porcentaje_operatividad) as total_Operatividad,AVG(operatividad_abastecimiento.porcentaje_abastecimiento) as total_Abastecimiento,reporte.fecha, reporte.id_estado, estados.estado
        FROM operatividad_abastecimiento 
            LEFT JOIN `reporte` ON operatividad_abastecimiento.`id_reporte` = reporte.id
            LEFT JOIN `estados` ON `reporte`.`id_estado` = estados.id_estado
            WHERE reporte.fecha
                BETWEEN ? AND ? 
                GROUP BY (reporte.id_estado)";

        $operatividad_abastecimiento = $db->consultaAll('mapa',$sql, [$params[0],$params[1]]);
                
        if (count($operatividad_abastecimiento) > 0) {
            for ($i=0; $i < count($operatividad_abastecimiento) ; $i++) { 
                $estado = $operatividad_abastecimiento[$i]["id_estado"] - 1;
                $array[$estado][1] = $operatividad_abastecimiento[$i]["total_Abastecimiento"];
                $array[$estado][2] = $operatividad_abastecimiento[$i]["total_Operatividad"];
            }
        }

        $array= [["State", "Latitude"], ...$array];
        return validarDatosReturn($array, $response);                 

        break;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        case 'tomas_ilegales':

            $sql = "SELECT SUM(tomas_ilegales.cantidad_tomas_eliminadas) AS total,reporte.fecha, reporte.id_estado, estados.estado
            FROM tomas_ilegales 
                LEFT JOIN `reporte` ON tomas_ilegales.`id_reporte` = reporte.id
                LEFT JOIN `estados` ON `reporte`.`id_estado` = estados.id_estado 
                WHERE reporte.fecha
                BETWEEN ? AND ?
                    GROUP BY (reporte.id_estado)";

            $tomas_eliminadas = $db->consultaAll('mapa',$sql, [$params[0],$params[1]]);

        if (count($tomas_eliminadas) > 0) {
            for ($i=0; $i < count($tomas_eliminadas) ; $i++) { 
                $estado = $tomas_eliminadas[$i]["id_estado"] - 1;
                $array[$estado][1] = $tomas_eliminadas[$i]["total"];
            }
        }

        $array= [["State", "Latitude"], ...$array];
        return validarDatosReturn($array, $response); 

        break;        
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        case 'fugas':

            $sql = "SELECT SUM(fugas.cantidad_fugas_reparadas) AS total,reporte.fecha, reporte.id_estado, estados.estado
            FROM fugas 
                LEFT JOIN `reporte` ON fugas.`id_reporte` = reporte.id
                LEFT JOIN `estados` ON `reporte`.`id_estado` = estados.id_estado 
                WHERE reporte.fecha
                BETWEEN ? AND ?
                    GROUP BY (reporte.id_estado)";
                            
            $fugas_reparadas = $db->consultaAll('mapa',$sql, [$params[0],$params[1]]);

        if (count($fugas_reparadas) > 0) {
            for ($i=0; $i < count($fugas_reparadas) ; $i++) { 
                $estado = $fugas_reparadas[$i]["id_estado"] - 1;
                $array[$estado][1] = $fugas_reparadas[$i]["total"];
            }
        }

        $array= [["State", "Latitude"], ...$array];
        return validarDatosReturn($array, $response);                 

        break;        

        default:

        $array= [["State", "Latitude"], ...$array];
        return validarDatosReturn($array, $response);                 
        
            break;
    }
   
}
});



/////////////////////////////////////////////////////////FIN DASHBOARD///////////////////////////////////////////////////////////////







//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
///////////////////////////////* POST *//////////////////////////////////////
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||

$app->post('/api/formularios/reportes', function (Request $request, Response $response) {
    $body = json_decode($request->getBody());
    $body= $body->body;
    $TablaConsultar = $_SESSION['TypeConsult'];
    $tablasInsertar=[
    ['`metros_cubicos`', '`id_estado`', '`id_reporte`'],
    ['`lps`', '`id_pozo`', '`id_reporte`'],
    ['`nombre_aduccion`', '`id_estado`', '`id_municipio`', '`id_parroquia`', '`sector`' , '`cantidad_fugas_reparadas`','`id_reporte`', '`lps_recuperados`'],
    ['`nombre_aduccion`', '`id_estado`', '`id_municipio`', '`id_parroquia`', '`sector`' , '`cantidad_tomas_eliminadas`', '`lps`', '`id_reporte`', '`lps_recuperados`'],
    ['`averias_levantadas_ap`', '`averias_levantadas_ap`', '`averias_levantadas_as`', '`averias_corregidas_as`', '`id_brippas`' , '`id_reporte`', '`lps_recuperados`'],
    ['`id_estado`', '`cantidad`', '`horas_sin_servicio`', '`equipos_danados`', '`id_infraestructura`' , '`id_sistema`', '`id_reporte`'],
    ['`id_estado`', '`porcentaje_operatividad`', '`porcentaje_abastecimiento`', '`observacion`', '`id_reporte`'],
    ['`nombre`', '`operatividad`', '`lps`', '`id_estado`', '`id_municipio`', '`id_parroquia`', '`sector`', '`poblacion`'],
    ['`nombre`', '`id_estado`', '`id_municipio`', '`id_parroquia`', '`sector`', '`cantidad_integrantes`', '`dotacion`', '`formacion`'],
    ['`nombre`', '`cantidad_pp`', '`cantidad_eb`', '`cantidad_pozo`', '`id_estado`','`cantidad_fuentes`']

    ];

    $tipoDatos = [//Tipo de datos a ingresar en cada formulario
        ["integer", "integer"],                                                                 //produccion
        ["integer", "integer"],                                                                 //rehabilitacion_pozo
        ["string", "integer", "integer", "integer", "string" , "integer", "integer"],           //fugas
        ["string", "integer", "integer", "integer", "string" , "integer", "integer", "integer"],//tomas_ilegales
        ["integer", "integer", "integer", "integer", "integer", "integer"],                      //reparaciones_brippas
        ["integer", "integer", "integer", "integer", "integer" , "integer"],                    //afectaciones
        ["integer", "integer", "integer", "string"],                                            //operatividad_abastecimiento
        ["string", "integer", "integer", "integer", "integer", "integer", "string", "integer"], //pozo
        ["string", "integer", "integer", "integer", "string", "integer", "integer", "integer"], //brippas
        ["string", "integer", "integer", "integer", "integer", "integer"]                                             //sistemas
    ];

    
    if (!empty($body->{'valores_insertar'})) {
        if (end($body->{'valores_insertar'}) === "reporte") {
            if (count($tablasInsertar[$body->{'tipo_formulario'}]) !== (count($body->{'valores_insertar'}) - 2 )) {
                return 'LOS VALORES NO COINCIDEN CON EL TIPO DE FORMULARIO1';                
            }
            
        }else{
            if (count($tablasInsertar[$body->{'tipo_formulario'}]) !== (count($body->{'valores_insertar'}))) {
                return 'LOS VALORES NO COINCIDEN CON EL TIPO DE FORMULARIO';
            }
        }        
    }else{
        return 'NO HAY VALORES PARA INSERTAR';
    }

    
    if (isset($body->{'tipo_formulario'})) {
        
        
        if (($body->{'tipo_formulario'} >= 0) AND ($body->{'tipo_formulario'} <=6)) {
            $sqlreporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, id_tabla, id_estado, id_revision) VALUES (NULL, ?, ?,?,?,?)";

            $sqlFormulario = generarSqlRegistro($tablasInsertar[$body->{'tipo_formulario'}], $body->{'tipo_formulario'}, $TablaConsultar[$body->{'tipo_formulario'}],);

            $values = array_slice($body->{'valores_insertar'},2,-1);
            $validacion = validarDatosFormulario($values,$body->{'tipo_formulario'},$tipoDatos[$body->{'tipo_formulario'}]);


            if ($validacion !== 'OK') {
                return  $validacion;
            }
            
            if (validarReporteDia( $body->{'id_estado'}, $body->{'tipo_formulario'}, $body->{'valores_insertar'}[1]) !== 'OK') {
                return enviarCods(200, 'warning', 'YA SE HA HECHO UN REPORTE EL DIA DE HOY', [] , $response);
            }
    
            $db = new DB();

            $stmt = $db->consultaAll('mapa', $sqlreporte, [$body->{'valores_insertar'}[0], $body->{'valores_insertar'}[1],$body->{'tipo_formulario'}, $body->{'id_estado'},1]);
            
            if ($stmt) {
                array_push($values,$stmt->{'insert_id'});
                $stmt2 = $db->consultaAll('mapa', $sqlFormulario, $values);
                if ($stmt2) {
                $db = null;
                return validarDatosReturn(["id"=>$stmt->{'insert_id'}], $response);
            } else {
                return 'ERROR EN EL REGISTRO DEL REPORTE';
            }
            }

        }elseif(($body->{'tipo_formulario'} >= 7) AND ($body->{'tipo_formulario'} <=9)){
            $sqlFormulario = generarSqlRegistro($tablasInsertar[$body->{'tipo_formulario'}], $body->{'tipo_formulario'}, $TablaConsultar[$body->{'tipo_formulario'}]);
            
            
            $validacion = validarDatosFormulario($body->{'valores_insertar'},$body->{'tipo_formulario'},$tipoDatos[$body->{'tipo_formulario'}]);


            if ($validacion !== 'OK') {
                return  $validacion;
            }
            
            $db = new DB();
            $stmt = $db->consultaAll('mapa', $sqlFormulario, $body->{'valores_insertar'});
            if ($stmt) {
                return 'REGISTRO EXITOSO, '.strtoupper($TablaConsultar[$body->{'tipo_formulario'}]).' NUMERO: '.$stmt->{'insert_id'};
            }else {
                return 'ERROR EN EL REGISTRO DEL REPORTE';
            }

            
            
        }else {
            return 'EL VALOR DEL TIPO DE FORMULARIO NO ES VALIDO';
        }
    }else {

        return 'TIPO DE FORMULARIO NO ENVIADO';
    }
   

});


$app->post('/api/reportes/eliminar', function (Request $request, Response $response) { 
    $db = new DB();
    $body = json_decode($request->getBody());

// DATOS = TIPO DE INFORME/CONSULTA
//ID DEL INFORME A ELIMINAR
    $TablaConsultar = $_SESSION['TypeConsult'];

    

    
    if (isset($body->{'id_informe'}) && is_numeric($body->{'id_informe'})) {
        if (($body->{'id_informe'} >= 0) && ($body->{'id_informe'} <= 6)) {
            $valorSQL = $TablaConsultar[$body->{'id_formulario'}];
            $sql1 = "SELECT $valorSQL.* FROM $valorSQL";
            $consulta = $db->consultaAll('mapa', $sql1);
        }else {
            return 'TABLA A CONSULTAR NO VALIDA';
        }
    }else {
        return 'PARAMETROS DE BUSQUEDA NO VALIDOS';
    }

    if ($consulta) {
        

        try {
            $sql = "DELETE FROM $valorSQL WHERE $valorSQL.`id` = ?";
            $stmt = $db->consultaAll('mapa', $sql, [$body->{'id_informe'}]);


            if ($stmt) {

                $sql = "DELETE FROM reporte WHERE reporte.id = ?";
                $stmt = $db->consultaAll('mapa', $sql, [$consulta[0]["id_reporte"]]);
                if ($stmt) {
                    return 'INFORME ELIMINADO';
                }else {
                    return 'HUBO UN ERROR EN LA ELIMINACION DEL INFORME';
                }        
            }else {
                return 'HUBO UN ERROR EN LA ELIMINACION DEL INFORME';
            }
            
            } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
    }
    
    
    
        
        
     
});



$app->put('/api/actualizacion/pozo', function (Request $request, Response $response) { 
    $body = json_decode($request->getBody());
    /*
    DATOS
    OPERATIVIDAD (1-0)
    ID DEL POZO A ACTUALIZAR

    */
    try {
        $sql = "UPDATE `pozo` SET `operatividad` = ? WHERE `pozo`.`id` = ?";
        $db = new DB();
        $stmt = $db->consultaAll('mapa', $sql, [$body->{'operatividad'}, $body->{'id_pozo'}]);
        if ($stmt) {
            return 'POZO ACTUALIZADO';          
        }else {
            return 'HUBO UN ERROR EN LA ACTUALIZACION';
        }
        
        } 
    catch (MySQLDuplicateKeyException $e) {
        $e->getMessage();
    }
    catch (MySQLException $e) {
        $e->getMessage();
    }
    catch (Exception $e) {
        $e->getMessage();
    }
});



$app->put('/api/actualizacion/revision', function (Request $request, Response $response) { 
    $body = json_decode($request->getBody());
    /*
    DATOS
    id_revision (1 - 2)
    id del reporte a actualizar

    */
    try {
        $sql = "UPDATE `reporte` SET `id_revision` = ? WHERE `reporte`.`id` = ?";
        $db = new DB();
        $stmt = $db->consultaAll('mapa', $sql, [$body->{'id_revision'}, $body->{'id_reporte'}]);
        if ($stmt) {
            return 'REPORTE REVISADO';          
        }else {
            return 'HUBO UN ERROR EN LA LECTURA DEL REPORTE';
        }
        
        } 
    catch (MySQLDuplicateKeyException $e) {
        $e->getMessage();
    }
    catch (MySQLException $e) {
        $e->getMessage();
    }
    catch (Exception $e) {
        $e->getMessage();
    }
});




$app->get('/api/pruebas/jwt', function (Request $request, Response $response) {
    $token=$request->getAttribute('jwt')['data'];
    return json_encode($token);
    

});

//////////////////////////////////////////////////////MIGRACION/////////////////////////////////////////

$app->get('/api/ecxelBrippas', function (Request $request, Response $response) {
             
    //POZOS
    $hoja_actual= $_SESSION['brippas']->getSheet(0);
    $filas = $hoja_actual->getHighestDataRow();
    $letra =$hoja_actual->getHighestColumn();
    $cont = null;
    //var_dump($hoja_actual);

    $db = New DB();
    $sql_nombre_brippas = "SELECT * FROM brippas";
    $nombre_brippas = $db->consultaAll('mapa',$sql_nombre_brippas);

    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        $Columna_estado = $hoja_actual->getCellByColumnAndRow(1,$i);
        $Columna_municipios = strval($hoja_actual->getCellByColumnAndRow(2,$i));
        $Columna_parroquia = strval($hoja_actual->getCellByColumnAndRow(3,$i));
        $Nombre_brippas = strval($hoja_actual->getCellByColumnAndRow(4,$i));
        $Integrantes =$hoja_actual->getCellByColumnAndRow(5,$i);
        $dotacion = $hoja_actual->getCellByColumnAndRow(6,$i);
        $formacion = $hoja_actual->getCellByColumnAndRow(7,$i);
        //fecha
        $celda = ('A'.$i);
        $value_fechas = $hoja_actual->getCell('A'.$i)->getValue();
        $objeto_fechas = PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value_fechas)->format('Y-m-d');
        $fecha = $objeto_fechas;
        //fecha
        
        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];
        $valor_borrar = null;
//NOMBRE BRIPPAS
        for ($y=0; $y < count($nombre_brippas); $y++) { //aca valido que no exista el pozo
        
            
            if (strtoupper($nombre_brippas[$y]['nombre']) == $Nombre_brippas) {
                $valor_borrar=$i;                                       
            }                                            
        }   
        if ($valor_borrar === $i) {
            $valor_nombre = "BORRAR";
            $NombrePozo =  $valor_nombre;                             

        }else {                   
            $valor_nombre = strval($Nombre_brippas);
            $NombrePozo =  $valor_nombre;                             
        }        
//NOMBRE BRIPPAS

//ID MUNICIPIO                   
        $sql_municipios = "SELECT * FROM municipios WHERE municipios.id_estado = $Columna_estado";
        $municipios = $db->consultaAll('mapa',$sql_municipios);

for ($l=0; $l < count($municipios); $l++) { //aca recorro la consulta para asignarle id a los nombre que necesito en el municipio

            if (strtoupper($municipios[$l]['municipio']) == $Columna_municipios) {                 
               $Municipio = $municipios[$l]['id_municipio'];
            } 
        }
        if (!isset($Municipio)) {
            return "error en el Reporte (municipio)".$i;
        }
//ID MUNICIPIO 

//ID PARROQUIA                   
        $sql_parroquias = "SELECT * FROM parroquias WHERE parroquias.id_municipio = $Municipio";
        $parroquias = $db->consultaAll('mapa',$sql_parroquias);

        for ($j=0; $j < count($parroquias); $j++) { //aca recorro la consulta para asignarle id a los nombre que necesito en la parroquia

            if (strtoupper($parroquias[$j]['parroquia']) == $Columna_parroquia) {
                $Parroquia = $parroquias[$j]['id_parroquia']; 
            } 
        }
        if (!isset($Parroquia)) {
            return "error en el Reporte (parroquia)".$i;
        }           
//ID PARROQUIA     

//INYECCION DE DATOS
        $insertar_brippas = "INSERT INTO `brippas` (`id`, `nombre`, `id_estado`, `id_municipio`, `id_parroquia`, `sector`, `cantidad_integrantes`, `dotacion`, `formacion`) 
                             VALUES (NULL, '$Nombre_brippas', $Columna_estado, $Municipio, $Parroquia, '$estados', $Integrantes, $dotacion, $formacion);";
            
        $stmt_brippas = $db->consultaAll('mapa',$insertar_brippas);

        if ($stmt_brippas) {      
            
            var_dump($i);
        }else {
            return "error inyeccion Pozo".$i;
        }

    }

});




///OPERATIVIDAD Y ABASTECIENTO
$app->get('/api/ecxelOpAb', function (Request $request, Response $response) {
             
    $hoja_actual= $_SESSION['operatividad_abastecimiento']->getSheet(0);
    $filas = $hoja_actual->getHighestDataRow();
    $letra =$hoja_actual->getHighestColumn();
    $db = New DB();

    //FOR DE ENERO
    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        
        $Columna_estado = $hoja_actual->getCellByColumnAndRow(1,$i);
        $Columna_mes_datos_abastecimiento = strval($hoja_actual->getCellByColumnAndRow(2,$i));
        $Columna_mes_datos_operatividad = strval($hoja_actual->getCellByColumnAndRow(9,$i));

        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];
        $Columna_mes_datos_abastecimiento = round(($Columna_mes_datos_abastecimiento +0)*100);
        $Columna_mes_datos_operatividad = round(($Columna_mes_datos_operatividad +0)*100);


        //INYECCION DE DATOS
        $sql_insertar_reporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
        VALUES (NULL, '$estados', '2022-01-30', 3, $Columna_estado , 1)";
        $stmt = $db->consultaAll('mapa',$sql_insertar_reporte);
        $id_reporte = $stmt->{'insert_id'};

        if ($stmt) {
            $sql_insertar_operatividad = "INSERT INTO `operatividad_abastecimiento` (`id`, `id_estado`, `porcentaje_operatividad`, `porcentaje_abastecimiento`, `observacion`, `id_reporte`) 
            VALUES (NULL, $Columna_estado , $Columna_mes_datos_operatividad, $Columna_mes_datos_abastecimiento , 'N/A', $id_reporte)";
            $stmt2 = $db->consultaAll('mapa',$sql_insertar_reporte);

            if ($stmt2) {
                var_dump($i." ENERO");
            }
        }
        //INYECCION DE DATOS
    }

    //FOR DE MARZO
    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        
        $Columna_estado = $hoja_actual->getCellByColumnAndRow(1,$i);
        $Columna_mes_datos_abastecimiento = strval($hoja_actual->getCellByColumnAndRow(4,$i));
        $Columna_mes_datos_operatividad = strval($hoja_actual->getCellByColumnAndRow(11,$i));

        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];
        $Columna_mes_datos_abastecimiento = round(($Columna_mes_datos_abastecimiento +0)*100);
        $Columna_mes_datos_operatividad = round(($Columna_mes_datos_operatividad +0)*100);


        //INYECCION DE DATOS
        $sql_insertar_reporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
        VALUES (NULL, '$estados', '2022-03-30', 3, $Columna_estado , 1)";
        $stmt = $db->consultaAll('mapa',$sql_insertar_reporte);
        $id_reporte = $stmt->{'insert_id'};

        if ($stmt) {
            $sql_insertar_operatividad = "INSERT INTO `operatividad_abastecimiento` (`id`, `id_estado`, `porcentaje_operatividad`, `porcentaje_abastecimiento`, `observacion`, `id_reporte`) 
            VALUES (NULL, $Columna_estado , $Columna_mes_datos_operatividad, $Columna_mes_datos_abastecimiento , 'N/A', $id_reporte)";
            $stmt2 = $db->consultaAll('mapa',$sql_insertar_reporte);

            if ($stmt2) {
                var_dump($i." MARZO");
            }
        }
        //INYECCION DE DATOS
    }

    //FOR DE ABRIL
    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        
        $Columna_estado = $hoja_actual->getCellByColumnAndRow(1,$i);
        $Columna_mes_datos_abastecimiento = strval($hoja_actual->getCellByColumnAndRow(5,$i));
        $Columna_mes_datos_operatividad = strval($hoja_actual->getCellByColumnAndRow(12,$i));

        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];
        $Columna_mes_datos_abastecimiento = round(($Columna_mes_datos_abastecimiento +0)*100);
        $Columna_mes_datos_operatividad = round(($Columna_mes_datos_operatividad +0)*100);


        //INYECCION DE DATOS
        $sql_insertar_reporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
        VALUES (NULL, '$estados', '2022-04-30', 3, $Columna_estado , 1)";
        $stmt = $db->consultaAll('mapa',$sql_insertar_reporte);
        $id_reporte = $stmt->{'insert_id'};

        if ($stmt) {
            $sql_insertar_operatividad = "INSERT INTO `operatividad_abastecimiento` (`id`, `id_estado`, `porcentaje_operatividad`, `porcentaje_abastecimiento`, `observacion`, `id_reporte`) 
            VALUES (NULL, $Columna_estado , $Columna_mes_datos_operatividad, $Columna_mes_datos_abastecimiento , 'N/A', $id_reporte)";
            $stmt2 = $db->consultaAll('mapa',$sql_insertar_reporte);

            if ($stmt2) {
                var_dump($i." ABRIL");
            }
        }
        //INYECCION DE DATOS
    }

    //FOR DE MAYO
    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        
        $Columna_estado = $hoja_actual->getCellByColumnAndRow(1,$i);
        $Columna_mes_datos_abastecimiento = strval($hoja_actual->getCellByColumnAndRow(6,$i));
        $Columna_mes_datos_operatividad = strval($hoja_actual->getCellByColumnAndRow(13,$i));

        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];
        $Columna_mes_datos_abastecimiento = round(($Columna_mes_datos_abastecimiento +0)*100);
        $Columna_mes_datos_operatividad = round(($Columna_mes_datos_operatividad +0)*100);


        //INYECCION DE DATOS
        $sql_insertar_reporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
        VALUES (NULL, '$estados', '2022-05-30', 3, $Columna_estado , 1)";
        $stmt = $db->consultaAll('mapa',$sql_insertar_reporte);
        $id_reporte = $stmt->{'insert_id'};

        if ($stmt) {
            $sql_insertar_operatividad = "INSERT INTO `operatividad_abastecimiento` (`id`, `id_estado`, `porcentaje_operatividad`, `porcentaje_abastecimiento`, `observacion`, `id_reporte`) 
            VALUES (NULL, $Columna_estado , $Columna_mes_datos_operatividad, $Columna_mes_datos_abastecimiento , 'N/A', $id_reporte)";
            $stmt2 = $db->consultaAll('mapa',$sql_insertar_reporte);

            if ($stmt2) {
                var_dump($i." MAYO");
            }
        }
        //INYECCION DE DATOS
    }

    //FOR DE JUNIO
    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        
        $Columna_estado = $hoja_actual->getCellByColumnAndRow(1,$i);
        $Columna_mes_datos_abastecimiento = strval($hoja_actual->getCellByColumnAndRow(7,$i));
        $Columna_mes_datos_operatividad = strval($hoja_actual->getCellByColumnAndRow(14,$i));

        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];
        $Columna_mes_datos_abastecimiento = round(($Columna_mes_datos_abastecimiento +0)*100);
        $Columna_mes_datos_operatividad = round(($Columna_mes_datos_operatividad +0)*100);


        //INYECCION DE DATOS
        $sql_insertar_reporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
        VALUES (NULL, '$estados', '2022-06-30', 3, $Columna_estado , 1)";
        $stmt = $db->consultaAll('mapa',$sql_insertar_reporte);
        $id_reporte = $stmt->{'insert_id'};

        if ($stmt) {
            $sql_insertar_operatividad = "INSERT INTO `operatividad_abastecimiento` (`id`, `id_estado`, `porcentaje_operatividad`, `porcentaje_abastecimiento`, `observacion`, `id_reporte`) 
            VALUES (NULL, $Columna_estado , $Columna_mes_datos_operatividad, $Columna_mes_datos_abastecimiento , 'N/A', $id_reporte)";
            $stmt2 = $db->consultaAll('mapa',$sql_insertar_reporte);

            if ($stmt2) {
                var_dump($i." JUNIO");
            }
        }
        //INYECCION DE DATOS
    }
});

///TOMAS ILEGALES
$app->get('/api/ecxelTomasIlegales', function (Request $request, Response $response) {
             
   
    $hoja_actual= $_SESSION['documento_tomas_ilegales']->getSheet(0);
    $filas = $hoja_actual->getHighestDataRow();
    $letra =$hoja_actual->getHighestColumn();
    $array = [];
    $cont = null;
    //var_dump($hoja_actual);
    $valor = [];
    $db = New DB();
    
    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        $celda = ('A'.$i);
        $value_fechas = $hoja_actual->getCell('A'.$i)->getValue();
        $objeto_fechas = PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value_fechas)->format('Y-m-d');
        $fecha = strval($objeto_fechas);

        $Columna_estado = $hoja_actual->getCellByColumnAndRow(2,$i);
        $Columna_municipios = strval($hoja_actual->getCellByColumnAndRow(3,$i));
        $Columna_parroquia = $hoja_actual->getCellByColumnAndRow(4,$i);
        $NombreAduccion = $hoja_actual->getCellByColumnAndRow(5,$i);
        $cant_tomas = $hoja_actual->getCellByColumnAndRow(6,$i);
        $lps_recuperados = $hoja_actual->getCellByColumnAndRow(7,$i);

        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];

        
        $valor_borrar = null;

        //ID MUNICIPIO                   
        $sql_municipios = "SELECT * FROM municipios WHERE municipios.id_estado = $Columna_estado";
        $municipios = $db->consultaAll('mapa',$sql_municipios);
        
        for ($l=0; $l < count($municipios); $l++) { //aca recorro la consulta para asignarle id a los nombre que necesito en el municipio
            
            if (strtoupper($municipios[$l]['municipio']) == $Columna_municipios) {                 
                $Municipio = $municipios[$l]['id_municipio'];
            } 
        }

        if (!isset($Municipio)) {
            return "error en el Reporte".$i;
        }
        //ID MUNICIPIO                   
        

        //ID PARROQUIA
        $sql_parroquias = "SELECT * FROM parroquias WHERE parroquias.id_municipio = $Municipio";
            $parroquias = $db->consultaAll('mapa',$sql_parroquias);
            for ($j=0; $j < count($parroquias); $j++) { //aca recorro la consulta para asignarle id a los nombre que necesito en la parroquia

                if (strtoupper($parroquias[$j]['parroquia']) == $Columna_parroquia) {
                    $Parroquia = $parroquias[$j]['id_parroquia']; 
                } 
            }
            if (!isset($Parroquia)) {
                return "error en el Reporte".$i;
            } 
        //ID PARROQUIA

        //INYECCION DE DATOS
        $sql_insertar_reporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
                                VALUES (NULL, '$estados', '$fecha', 3, $Columna_estado , 1)";
        $stmt = $db->consultaAll('mapa',$sql_insertar_reporte);
        $id_reporte = $stmt->{'insert_id'};

        if ($stmt) {
            $sql_insertar_tomas = "INSERT INTO `tomas_ilegales` (`id`, `nombre_aduccion`, `id_estado`, `id_municipio`, `id_parroquia`, `sector`, `cantidad_tomas_eliminadas`, `lps`, `id_reporte`, `lps_recuperados`) 
                                VALUES (NULL, '$NombreAduccion', $Columna_estado, $Municipio, $Parroquia, 'N/A', $cant_tomas, $lps_recuperados, $id_reporte, $lps_recuperados)";

            
            $stmt2 = $db->consultaAll('mapa',$sql_insertar_tomas);

            if (!$stmt2) {
            return "error en el reporte ".$i;
            }

        }else {
            return "error en el reporte ".$i;
        }
        
        //INYECCION DE DATOS
        var_dump($i);

    }

});

///FUGAS 
$app->get('/api/ecxelFugas', function (Request $request, Response $response) {
             
   
    $hoja_actual= $_SESSION['documento_fugas']->getSheet(0);
    $filas = $hoja_actual->getHighestDataRow();
    $letra =$hoja_actual->getHighestColumn();
    $array = [];
    $cont = null;
    //var_dump($hoja_actual);
    $valor = [];
    $db = New DB();
    
    for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
        $celda = ('A'.$i);
        $value_fechas = $hoja_actual->getCell('A'.$i)->getValue();
        $objeto_fechas = PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value_fechas)->format('Y-m-d');
        $fecha = strval($objeto_fechas);

        $Columna_estado = $hoja_actual->getCellByColumnAndRow(2,$i);
        $Columna_municipios = strval($hoja_actual->getCellByColumnAndRow(3,$i));
        $Nombre_aduccion = strval($hoja_actual->getCellByColumnAndRow(4,$i));
        $Cant_fugas = $hoja_actual->getCellByColumnAndRow(5,$i);
        $lps_recuperados = $hoja_actual->getCellByColumnAndRow(6,$i);

        $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
        $estados = $db->consultaAll('mapa',$sql_estados);
        $estados = $estados[0]['estado'];

        
        $valor_borrar = null;

        //ID MUNICIPIO                   
        $sql_municipios = "SELECT * FROM municipios WHERE municipios.id_estado = $Columna_estado";
        $municipios = $db->consultaAll('mapa',$sql_municipios);
        
        for ($l=0; $l < count($municipios); $l++) { //aca recorro la consulta para asignarle id a los nombre que necesito en el municipio
            
            if (strtoupper($municipios[$l]['municipio']) == $Columna_municipios) {                 
                $Municipio = $municipios[$l]['id_municipio'];
            } 
        }

        if (!isset($Municipio)) {
            return "error en el Reporte".$i;
        }
        
        //INYECCION DE DATOS
            $sql_insertar_reporte = "INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
                                    VALUES (NULL, '$estados', '$fecha', 2, $Columna_estado , 1)";
            $stmt = $db->consultaAll('mapa',$sql_insertar_reporte);
            $id_reporte = $stmt->{'insert_id'};
        
        if ($stmt) {
            $sql_insertar_fuga = "INSERT INTO `fugas` (`id`, `nombre_aduccion`, `id_estado`, `id_municipio`, `id_parroquia`, `sector`, `cantidad_fugas_reparadas`, `id_reporte`, `lps_recuperados`) 
                                    VALUES (NULL, '$Nombre_aduccion', $Columna_estado, $Municipio, 0, 'N/A', $Cant_fugas, $id_reporte , $lps_recuperados);";
            $stmt = $db->consultaAll('mapa',$sql_insertar_fuga);
         }else{
            return "error en el reporte".$i;
         }
        var_dump($i);

    }

});

///POZOS 
    $app->get('/api/ecxelPozos', function (Request $request, Response $response) {
             
        //POZOS
        $hoja_actual= $_SESSION['documento']->getSheet(0);
        $filas = $hoja_actual->getHighestDataRow();
        $letra =$hoja_actual->getHighestColumn();
        $cont = null;
        //var_dump($hoja_actual);

        $db = New DB();
        $sql_nombre_pozos = "SELECT * FROM pozo";
        $nombre_pozos = $db->consultaAll('mapa',$sql_nombre_pozos);
  
        for ($i=1; $i <= $filas; $i++) { //aca obtengo cada dato de cada columna que necesite
            $Columna_estado = $hoja_actual->getCellByColumnAndRow(2,$i);
            $Columna_municipios = $hoja_actual->getCellByColumnAndRow(3,$i);
            $Columna_parroquia = $hoja_actual->getCellByColumnAndRow(4,$i);
            $Sector = $hoja_actual->getCellByColumnAndRow(5,$i);
            $Nombre = $hoja_actual->getCellByColumnAndRow(6,$i);
            $Lps = $hoja_actual->getCellByColumnAndRow(7,$i);
            $Poblacion = $hoja_actual->getCellByColumnAndRow(8,$i);
            //fecha
            $celda = ('A'.$i);
            $value_fechas = $hoja_actual->getCell('A'.$i)->getValue();
            $objeto_fechas = PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value_fechas)->format('Y-m-d');
            $fecha = $objeto_fechas;
            //fecha
            
            $sql_estados = "SELECT * FROM estados WHERE id_estado = $Columna_estado";       
            $estados = $db->consultaAll('mapa',$sql_estados);
            $estados = $estados[0]['estado'];

            $valor_borrar = null;


//NOMBRE POZO
            for ($y=0; $y < count($nombre_pozos); $y++) { //aca valido que no exista el pozo
            
                
                if (strtoupper($nombre_pozos[$y]['nombre']) == $Nombre) {
                    $valor_borrar=$i;                                       
                }                                            
            }   
            if ($valor_borrar === $i) {
                $valor_nombre = "BORRAR";
                $NombrePozo =  $valor_nombre;                             

            }else {                   
                $valor_nombre = strval($Nombre);
                $NombrePozo =  $valor_nombre;                             
            }        
//NOMBRE POZO

//ID MUNICIPIO                   
            $sql_municipios = "SELECT * FROM municipios WHERE municipios.id_estado = $Columna_estado";
            $municipios = $db->consultaAll('mapa',$sql_municipios);
            for ($l=0; $l < count($municipios); $l++) { //aca recorro la consulta para asignarle id a los nombre que necesito en el municipio

                if (strtoupper($municipios[$l]['municipio']) == $Columna_municipios) {                 
                   $Municipio = $municipios[$l]['id_municipio'];
                } 
            }
            if (!isset($Municipio)) {
                return "error en el Reporte".$i;
            }
//ID MUNICIPIO 

//ID PARROQUIA                   
            $sql_parroquias = "SELECT * FROM parroquias WHERE parroquias.id_municipio = $Municipio";
            $parroquias = $db->consultaAll('mapa',$sql_parroquias);
            for ($j=0; $j < count($parroquias); $j++) { //aca recorro la consulta para asignarle id a los nombre que necesito en la parroquia

                if (strtoupper($parroquias[$j]['parroquia']) == $Columna_parroquia) {
                    $Parroquia = $parroquias[$j]['id_parroquia']; 
                } 
            }
            if (!isset($Parroquia)) {
                return "error en el Reporte".$i;
            }           
//ID PARROQUIA     

//INYECCION DE DATOS
            $insertar_pozo = "  INSERT INTO `pozo` (`id`, `nombre`, `operatividad`, `lps`, `id_estado`, `id_municipio`, `id_parroquia`, `sector`, `poblacion`) 
            VALUES (NULL, '$NombrePozo', 1 , $Lps , $Columna_estado , $Municipio , $Parroquia , '$Sector' , $Poblacion)";
                
            $stmt_pozo = $db->consultaAll('mapa',$insertar_pozo);

            if ($stmt_pozo) {
                $id_pozo = $stmt_pozo->{'insert_id'};
                $insertar_reporte ="INSERT INTO `reporte` (`id`, `ubicacion_reporte`, `fecha`, `id_tabla`, `id_estado`, `id_revision`) 
                                    VALUES (NULL, '$estados', '$fecha', 1, $Columna_estado, 1)";
                $stmt_reporte = $db->consultaAll('mapa',$insertar_reporte);
                
                if ($stmt_reporte) {
                $id_reporte = $stmt_reporte->{'insert_id'};
                $insertar_rehabilitacion = "INSERT INTO `rehabilitacion_pozo` (`id`, `lps`, `id_pozo`, `id_reporte`) 
                                            VALUES (NULL, $Lps , $id_pozo , $id_reporte)";
                $stmt_rehab = $db->consultaAll('mapa',$insertar_rehabilitacion);
                
                
                if (!$stmt_rehab) {
                return "error inyeccion Rehabilitacion".$i;
                }
                }else {
                    return "error inyeccion Reporte".$i;
                }
                
            }else {
                return "error inyeccion Pozo".$i;
            }
            var_dump($i);

        }

    });

//////////////////////////////////////////////////////FIN MIGRACION/////////////////////////////////////////
