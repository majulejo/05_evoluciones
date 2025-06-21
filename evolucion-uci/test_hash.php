require 'conexion.php';         // o como llames a tu conexión
$u = 'f.cornejo';
$stmt = $conn->prepare("SELECT usuario, clave FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $u);
$stmt->execute();
var_dump( $stmt->get_result()->fetch_assoc() );
