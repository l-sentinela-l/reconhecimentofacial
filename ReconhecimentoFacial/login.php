<?php
session_start();
include("conexao.php");

$mensagem = '';
$classeMensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];
    $facial_data = $_POST['facial_data'];

    if (empty($facial_data)) {
        $mensagem = "Por favor, capture seu rosto para login.";
        $classeMensagem = 'erro';
    } else {
        $sql = "SELECT UsuarioID, Senha, Nome_Usuario FROM Usuarios WHERE Nome_Usuario = ?";
        $params = array($usuario);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($senha === $row['Senha']) {
                // Executar o reconhecimento facial
                $pythonScript = "C:\\Python39\\python.exe reconhecimentofacial.py";
                $command = escapeshellcmd($pythonScript);
                $data = json_encode([
                    'usuario_id' => $row['UsuarioID'],
                    'imagem_base64' => $facial_data
                ]);
                
                $output = shell_exec("$command " . escapeshellarg($data));
                $result = json_decode($output, true);
                
                if ($result && $result['success']) {
                    // Registrar o acesso
                    $sql_acesso = "INSERT INTO RegistrosAcesso (UsuarioID, DataHora, Sucesso) 
                                  VALUES (?, GETDATE(), 1)";
                    $params_acesso = array($row['UsuarioID']);
                    sqlsrv_query($conn, $sql_acesso, $params_acesso);
                    
                    $_SESSION["usuario_id"] = $row['UsuarioID'];
                    $_SESSION["usuario_nome"] = $row['Nome_Usuario'];
                    header("Location: chamada.php");
                    exit;
                } else {
                    // Registrar tentativa falha
                    $sql_acesso = "INSERT INTO RegistrosAcesso (UsuarioID, DataHora, Sucesso) 
                                  VALUES (?, GETDATE(), 0)";
                    $params_acesso = array($row['UsuarioID']);
                    sqlsrv_query($conn, $sql_acesso, $params_acesso);
                    
                    $mensagem = $result['message'] ?? "Falha no reconhecimento facial.";
                    $classeMensagem = 'erro';
                }
            } else {
                $mensagem = "Senha incorreta.";
                $classeMensagem = 'erro';
            }
        } else {
            $mensagem = "Usuário não encontrado.";
            $classeMensagem = 'erro';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lista de Chamada</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .mensagem {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        #video {
            width: 100%;
            background: #ddd;
            margin-bottom: 10px;
        }
        #canvas {
            display: none;
        }
        #capturar {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo $classeMensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" id="formLogin">
            <input type="text" name="usuario" placeholder="Digite seu nome de usuário" required />
            <input type="password" name="senha" placeholder="Digite sua senha" required />
            
            <div id="camera-container">
                <video id="video" width="320" height="240" autoplay></video>
                <canvas id="canvas" width="320" height="240"></canvas>
                <button type="button" id="capturar">Capturar Rosto</button>
                <input type="hidden" name="facial_data" id="facial_data" required />
            </div>
            
            <button type="submit" id="button">Entrar</button>
        </form>

        <div class="registrar">
            <p>Ainda não se cadastrou? <a href="cadastrar.php">Cadastrar-se</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mensagem = document.querySelector('.mensagem');
            if (mensagem) {
                setTimeout(function() {
                    mensagem.style.display = 'none';
                }, 4000);
            }
            
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const capturarBtn = document.getElementById('capturar');
            const facialData = document.getElementById('facial_data');
            const form = document.getElementById('formLogin');
            
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function(stream) {
                        video.srcObject = stream;
                    })
                    .catch(function(error) {
                        console.error("Erro ao acessar a câmera: ", error);
                        alert("Não foi possível acessar a câmera. Por favor, permita o acesso.");
                    });
            }
            
            capturarBtn.addEventListener('click', function() {
                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = canvas.toDataURL('image/jpeg');
                facialData.value = imageData;
                alert("Rosto capturado com sucesso!");
            });
            
            form.addEventListener('submit', function(e) {
                if (!facialData.value) {
                    e.preventDefault();
                    alert("Por favor, capture uma imagem do seu rosto antes de enviar.");
                }
            });
        });
    </script>
</body>
</html>