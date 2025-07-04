<?php
// Configuração do banco de dados
$serverName = "DESKTOP-JI9FDB4\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Reconhecimento",
    "Uid" => "teste",
    "PWD" => "teste",
    "CharacterSet" => "UTF-8"
);

// Conexão com o banco
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("Erro ao conectar ao banco de dados");
}

$mensagem = '';
$classeMensagem = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome_usuario = $_POST["usuario-cadastro"];
    $nome_completo = $_POST["nome"];
    $cpf = $_POST["cpf"];
    $senha = $_POST["senha"];
    $confirmar = $_POST["confirmar"];
    $facial_data = $_POST["facial_data"];

    // Validações básicas
    if (empty($nome_usuario) || empty($nome_completo) || empty($cpf) || empty($senha) || empty($confirmar) || empty($facial_data)) {
        $mensagem = "Todos os campos são obrigatórios";
        $classeMensagem = 'erro';
    } elseif ($senha !== $confirmar) {
        $mensagem = "As senhas não coincidem";
        $classeMensagem = 'erro';
    } elseif (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpf)) {
        $mensagem = "CPF inválido. Formato: 123.456.789-01";
        $classeMensagem = 'erro';
    } else {
        try {
            // Salvar imagem temporária
            $temp_file = tempnam(sys_get_temp_dir(), 'face_');
            $base64_data = str_replace('data:image/jpeg;base64,', '', $facial_data);
            $base64_data = str_replace(' ', '+', $base64_data);
            file_put_contents($temp_file, base64_decode($base64_data));

            // Executar script Python
            $python_script = __DIR__ . '\\processar_rosto.py';
            if (!file_exists($python_script)) {
                throw new Exception("Arquivo Python não encontrado");
            }

            $command = '"C:\\Users\\Loren\\AppData\\Local\\Programs\\Python\\Python313\\python.exe" "' . $python_script . '" "' . $temp_file . '" 2>&1';
            $output = shell_exec($command);

            if ($output === null) {
                throw new Exception("O Python não respondeu. Verifique o caminho e dependências.");
            }

            $resultado = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE || !$resultado || !isset($resultado['success'])) {
                throw new Exception("Erro no retorno do Python: " . substr($output, 0, 200));
            }

            if (!$resultado['success']) {
                throw new Exception("Reconhecimento facial falhou: " . $resultado['message']);
            }

            $vetor = $resultado['vetor_caracteristicas'];

            // Inserir usuário e capturar ID
            $sql_usuario = "INSERT INTO Usuarios (Nome_Usuario, Nome_completo, CPF, Senha, DataCadastro, Ativo)
                            OUTPUT INSERTED.UsuarioID
                            VALUES (?, ?, ?, ?, GETDATE(), 1)";
            $params = array($nome_usuario, $nome_completo, $cpf, password_hash($senha, PASSWORD_DEFAULT));
            $stmt = sqlsrv_query($conn, $sql_usuario, $params);

            if ($stmt && sqlsrv_fetch($stmt)) {
                $usuarioID = sqlsrv_get_field($stmt, 0);

                // Inserir vetor facial
                $sql_facial = "INSERT INTO DadosFaciais (UsuarioID, VetorCaracteristicas, DataCadastro)
                               VALUES (?, ?, GETDATE())";
                $params_facial = array($usuarioID, $vetor);
                sqlsrv_query($conn, $sql_facial, $params_facial);

                $mensagem = "Cadastro realizado com sucesso!";
                $classeMensagem = 'sucesso';
            } else {
                throw new Exception("Erro ao cadastrar usuário no banco de dados");
            }

        } catch (Exception $e) {
            $mensagem = "Erro: " . $e->getMessage();
            $classeMensagem = 'erro';
            error_log("ERRO CADASTRO: " . $e->getMessage());
        } finally {
            if (isset($temp_file) && file_exists($temp_file)) {
                @unlink($temp_file);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastrar-se</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 500px;
      margin: 0 auto;
      padding: 20px;
    }
    .mensagem {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      text-align: center;
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
    input, button {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    #video {
      width: 100%;
      max-width: 320px;
      background: #ddd;
      margin-bottom: 10px;
    }
    #canvas {
      display: none;
    }
    .login-link {
      text-align: center;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <h2 style="text-align: center;">Cadastrar-se</h2>
  
  <?php if ($mensagem): ?>
    <div class="mensagem <?php echo $classeMensagem; ?>">
      <?php echo $mensagem; ?>
    </div>
  <?php endif; ?>
  
  <form method="POST">
    <input type="text" name="usuario-cadastro" placeholder="Nome de Usuário" required>
    <input type="text" name="nome" placeholder="Nome completo" required>
    <input type="text" name="cpf" placeholder="CPF (000.000.000-00)" required 
           pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" title="Formato: 123.456.789-01">
    <input type="password" name="senha" placeholder="Senha" required minlength="6">
    <input type="password" name="confirmar" placeholder="Confirmar senha" required minlength="6">
    
    <div style="text-align: center;">
      <video id="video" width="320" height="240" autoplay playsinline></video>
      <canvas id="canvas" width="320" height="240"></canvas>
      <button type="button" id="capturar">Capturar Rosto</button>
      <input type="hidden" name="facial_data" id="facial_data" required>
    </div>
    
    <button type="submit">Cadastrar</button>
  </form>
  
  <div class="login-link">
    <p>Já tem conta? <a href="login.php">Faça login</a></p>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const video = document.getElementById('video');
      const canvas = document.getElementById('canvas');
      const capturarBtn = document.getElementById('capturar');
      const facialData = document.getElementById('facial_data');
      
      // Configurar câmera
      navigator.mediaDevices.getUserMedia({ 
        video: { 
          width: 320,
          height: 240,
          facingMode: 'user'
        } 
      })
      .then(stream => {
        video.srcObject = stream;
      })
      .catch(err => {
        console.error("Erro na câmera:", err);
        alert("Erro ao acessar a câmera: " + err.message);
      });
      
      // Capturar rosto
      capturarBtn.addEventListener('click', function() {
        // Configurar canvas
        canvas.width = 320;
        canvas.height = 240;
        const ctx = canvas.getContext('2d');
        
        // Capturar imagem
        ctx.drawImage(video, 0, 0, 320, 240);
        
        // Converter para JPEG (70% de qualidade)
        facialData.value = canvas.toDataURL('image/jpeg', 0.7);
        alert("Rosto capturado com sucesso!");
      });
    });
  </script>
</body>
</html>
