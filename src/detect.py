import os
import json
import socket
from ultralytics import YOLO

SOCKET_PATH = '/tmp/yolo.sock'


model = YOLO('/var/www/camsboard/src/yolov8n.onnx', task='detect')


if os.path.exists(SOCKET_PATH):
    os.remove(SOCKET_PATH)

server = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
server.bind(SOCKET_PATH)
os.chmod(SOCKET_PATH, 0o666)  # Дозволяємо PHP підключатися до сокета
server.listen(5)


while True:
    conn, _ = server.accept()
    try:
        image_path = conn.recv(1024).decode('utf-8').strip()
        output = {"has_person": False, "detections": []}

        if image_path:
            results = model(image_path, classes=[0], verbose=False)
            for r in results:
                for box in r.boxes:
                    if int(box.cls) == 0:
                        output["has_person"] = True
                        coords = [round(x) for x in box.xyxy[0].tolist()]
                        confidence = round(float(box.conf) * 100, 1)
                        output["detections"].append({
                            "box": coords,
                            "confidence": confidence
                        })

        conn.sendall(json.dumps(output).encode('utf-8'))
    except Exception as e:
        conn.sendall(json.dumps({"has_person": False, "detections": [], "error": str(e)}).encode('utf-8'))
    finally:
        conn.close()
