import cv2
import face_recognition
import numpy as np
import base64
import json
import sys
import os

def processar_imagem(imagem_path):
    try:
        # 1. Verificar se o arquivo existe
        if not os.path.exists(imagem_path):
            return {"success": False, "message": "Arquivo de imagem não encontrado"}

        # 2. Ler o conteúdo do arquivo
        with open(imagem_path, 'rb') as f:
            img_data = f.read()

        # 3. Extrair dados da imagem (remover cabeçalho se existir)
        if ',' in img_data:
            img_base64 = img_data.split(',')[1]
        else:
            img_base64 = img_data

        # 4. Decodificar a imagem
        img_bytes = base64.b64decode(img_base64)
        img_np = np.frombuffer(img_bytes, dtype=np.uint8)
        img = cv2.imdecode(img_np, cv2.IMREAD_COLOR)

        if img is None:
            return {"success": False, "message": "Não foi possível decodificar a imagem"}

        # 5. Redimensionar (otimização)
        height, width = img.shape[:2]
        if width > 800 or height > 800:
            scale = 800 / max(width, height)
            img = cv2.resize(img, (0,0), fx=scale, fy=scale)

        # 6. Converter para RGB (a biblioteca usa RGB)
        rgb_img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)

        # 7. Detectar rostos (usando modelo mais rápido)
        face_locations = face_recognition.face_locations(
            rgb_img,
            number_of_times_to_upsample=1,
            model="hog"
        )

        if not face_locations:
            return {"success": False, "message": "Nenhum rosto detectado na imagem"}

        # 8. Extrair características faciais
        face_encoding = face_recognition.face_encodings(rgb_img, face_locations)[0]

        # 9. Converter para base64
        vetor_base64 = base64.b64encode(face_encoding.tobytes()).decode('utf-8')

        return {
            "success": True,
            "vetor_caracteristicas": vetor_base64,
            "message": "Rosto processado com sucesso",
            "detalhes": {
                "rostos_detectados": len(face_locations),
                "tamanho_imagem": f"{img.shape[1]}x{img.shape[0]}"
            }
        }

    except Exception as e:
        return {
            "success": False,
            "message": f"Erro durante o processamento: {str(e)}",
            "error_type": type(e).__name__
        }

if __name__ == "__main__":
    try:
        if len(sys.argv) < 2:
            print(json.dumps({
                "success": False,
                "message": "Uso: python processar_rosto.py <caminho_do_arquivo>"
            }))
            sys.exit(1)

        resultado = processar_imagem(sys.argv[1])
        print(json.dumps(resultado))

    except Exception as e:
        print(json.dumps({
            "success": False,
            "message": f"Erro fatal: {str(e)}"
        }))
        sys.exit(1)