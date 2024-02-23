<?php
// PARA CORRER EN LA CONSOLA ES CON   php .\load_data.php .\prueba.csv
// Cargar el script de configuración
require_once 'config.php';

// Validar que los campos obligatorios no estén vacíos
function validarRegistroPorLinea($data)
{
    // Lista de campos obligatorios
    $camposrequeridos = ['nombre', 'edad', 'email'];

    $resultado = array(
        'resultado' => true,
        'mensaje' => "Los datos son correctos",
    );

    // Verificar cada campo obligatorio
    foreach ($camposrequeridos as $campo) {
        if (!isset($data[$campo]) || empty($data[$campo])) {
            $resultado = array(
                'resultado' => false,
                'mensaje' => "Todos los campos (nombre, edad, email) son obligatorios.\n",
            );
        }
    }

    // Verificar si la edad es un valor numérico
    if (!is_numeric($data['edad'])) {
        $resultado = array(
            'resultado' => false,
            'mensaje' => "Validar que la edad sea tipo numerico",
        );
    }

    // Validar el formato del correo electrónico
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $resultado = array(
            'resultado' => false,
            'mensaje' => "Validar que el email sea correcto",
        );
    }

    // Validar que el nombre no contenga caracteres especiales o números
    if (!preg_match("/^[a-zA-Z ]*$/", $data['nombre'])) {
        $resultado = array(
            'resultado' => false,
            'mensaje' => "Validar que el nombre sea correcto",
        );
    }

    return $resultado; // Si todos los campos obligatorios están presentes, la validación pasa
}

// Verificar que se haya proporcionado un archivo CSV como argumento
if ($argc < 2) {
    echo "Por favor, proporcione la ruta del archivo CSV como argumento.\n";
    exit(1);
}

// Obtener la ruta del archivo CSV del primer argumento
$archivoCsv = $argv[1];

// Verificar que el archivo CSV exista
if (!file_exists($archivoCsv)) {
    echo "El archivo CSV especificado no existe.\n";
    exit(1);
}

// Abrir el archivo CSV para lectura
$archivo = fopen($archivoCsv, 'r');
if (!$archivo) {
    echo "Error al abrir el archivo CSV.\n";
    exit(1);
}

// Conexión a la base de datos
try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName}";
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error de conexión a la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// Contador para el número de filas insertadas
$contadorFilasInsertadas = 0;
$contadorFila = 0;
$resultadoFila = [];
$registrosValidos = [];

// Primer ciclo para validar los registros
while (($data = fgetcsv($archivo)) !== false) {
    $contadorFila++;
    $validarRegistro = validarRegistroPorLinea(array_combine(['nombre', 'edad', 'email'], $data));
    if (!$validarRegistro['resultado']) {
        $resultadoFila[] = $validarRegistro['mensaje'] . " en la línea " . $contadorFila;
    } else {
        $registrosValidos[] = $data;
    }
}

if (count($resultadoFila) === 0) {
    
    // Segundo ciclo para insertar los registros válidos en la base de datos
    foreach ($registrosValidos as $data) {
        // Construir la consulta SQL para la inserción de datos
        $sql = "INSERT INTO {$tabla} (nombre, edad, email) VALUES (?,?,?)";
    
        // Preparar la consulta
        $stmt = $pdo->prepare($sql);
    
        // Ejecutar la consulta con los datos de la fila actual
        if ($stmt->execute($data)) {
            $contadorFilasInsertadas++;
        } else {
            echo "Error al insertar fila: " . implode(', ', $data) . "\n";
        }
    }
}

// Imprimir mensajes de error, si los hay
if (count($resultadoFila) > 0) {
    foreach ($resultadoFila as $error) {
        echo $error . "\n";
    }
}

// Cerrar el archivo CSV y la conexión a la base de datos
fclose($archivo);
$pdo = null;

// Informar el número de filas insertadas
echo "Se insertaron {$contadorFilasInsertadas} filas en la tabla.\n";
