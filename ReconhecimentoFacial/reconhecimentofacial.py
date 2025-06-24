import cv2
import face_recognition
import numpy as np
import base64
import json
import sys
import pyodbc

class ReconhecimentoFacial:
    def __init__(self, connection_string):
        self.conn = pyodbc.connect(connection_string)
        
    def carregar_dados_usuario(self, usuario_id):
        cursor = self.conn.cursor()
        cursor.execute("""
            SELECT VetorCaracteristicas 
            FROM DadosFaciais 
            WHERE UsuarioID = ?
        """, (usuario_id,))
        
        row = cursor.fetchone()
        if not row:
            return None
            
        vetor_bytes = base64.b64decode(row[0])
        return np.frombuffer(vetor_bytes, dtype=np.float64)
        
    def verificar_rosto(self, usuario_id, imagem_base64):
        # Carregar vetor armazenado
        stored_encoding = self.carregar_dados_usuario(usuario_id)
        if stored_encoding is None:
            return {"success": False, "message": "Dados faciais não encontrados para este usuário."}
        
        # Processar imagem recebida
        try:
            img_bytes = base64.b64decode(imagem_base64.split(',')[1])
            img_array = np.frombuffer(img_bytes, dtype=np.uint8)
            img = cv2.imdecode(img_array, cv2.IMREAD_COLOR)
            rgb_img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
            
            face_locations = face_recognition.face_locations(rgb_img)
            if not face_locations:
                return {"success": False, "message": "Nenhum rosto detectado na imagem enviada."}
                
            face_encodings = face_recognition.face_encodings(rgb_img, face_locations)
            if not face_encodings:
                return {"success": False, "message": "Não foi possível extrair características da imagem enviada."}
                
            # Comparar rostos
            matches = face_recognition.compare_faces([stored_encoding], face_encodings[0], tolerance=0.6)
            
            if matches[0]:
                return {"success": True, "message": "Autenticação facial bem-sucedida."}
            else:
                return {"success": False, "message": "Autenticação facial falhou. Rostos não correspondem."}
                
        except Exception as e:
            return {"success": False, "message": f"Erro ao processar imagem: {str(e)}"}

if __name__ == "__main__":
    if len(sys.argv) > 1:
        data = json.loads(sys.argv[1])
        usuario_id = data['usuario_id']
        imagem_base64 = data['imagem_base64']
        
        connection_string = (
            "Driver={SQL Server};"
            "Server=DESKTOP-JI9FDB3\SQLEXPRESS;"
            "Database=Reconhecimento;"
            "UID=teste;"
            "PWD=teste;"
        )
        
        rf = ReconhecimentoFacial(connection_string)
        resultado = rf.verificar_rosto(usuario_id, imagem_base64)
        print(json.dumps(resultado))
    else:
        print(json.dumps({"success": False, "message": "Argumentos insuficientes."}))