<?php

session_start();
ob_start();
$_SESSION['TypeConsult'] = 
[
    'produccion',//0 
    'rehabilitacion_pozo',//1 
    'fugas',//2 
    'tomas_ilegales',//3 
    'reparaciones_brippas',//4
    'afectaciones',//5 
    'operatividad_abastecimiento',//6
    'pozo',//7
    'brippas',//8
    'sistemas'//9
];

$_SESSION['Meses'] = 
[
    ["ENERO",0],
    ["FEBRERO",0],
    ["MARZO",0],
    ["ABRIL",0],
    ["MAYO",0],
    ["JUNIO",0],
    ["JULIO",0],
    ["AGOSTO",0],
    ["SEPTIEMBRE",0],
    ["OCTUBRE",0],
    ["NOVIEMBRE",0],
    ["DICIEMBRE",0]
];


$_SESSION['Estados'] = [
    ["AMAZONAS",0],
    ["ANZOATEGUI",0],
    ["APURE",0],
    ["ARAGUA",0],
    ["BARINAS",0],
    ["BOLIVAR",0],
    ["CARABOBO",0],
    ["COJEDES",0],
    ["DELTA AMACURO",0],
    ["FALCON",0],
    ["GUARICO",0],
    ["LARA",0],
    ["MERIDA",0],
    ["MIRANDA",0],
    ["MONAGAS",0],
    ["NUEVA ESPARTA",0],
    ["PORTUGUESA",0],
    ["SUCRE",0],
    ["TACHIRA",0],
    ["TRUJILLO",0],
    ["VARGAS",0], 
    ["YARACUY",0],
    ["ZULIA",0],
    ["DISTRITO CAPITAL",0]
];