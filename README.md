# Camsboard

Інтелектуальна система мережевого відеоспостереження (NVR) та відеоаналітики з відкритим вихідним кодом, побудована на **PHP, JavaScript, MediaMTX, FFmpeg** та **Python (YOLOv8)**.

Система створена для ефективної роботи навіть на серверах без потужних відеокарт (GPU). Завдяки двоетапній обробці відеопотоків вона мінімізує навантаження на процесор: спочатку легковаговий фільтр FFmpeg фіксує зміни в кадрі в оперативній пам'яті, і лише після цього нейромережа YOLOv8 перевіряє наявність людей та надсилає тривожне сповіщення.

An open-source intelligent Network Video Recorder (NVR) and video analytics platform built on **PHP, JavaScript, MediaMTX, FFmpeg**, and **Python (YOLOv8)**.

Designed to run efficiently even on CPU-only servers without dedicated GPUs, the system uses a two-stage video processing pipeline to minimize resource usage: a lightweight FFmpeg filter first detects scene changes in memory, and only then does the YOLOv8 neural network verify the presence of people and trigger instant alerts.

---

## Архітектура та принцип роботи / Architecture & How It Works

1. **Прийом та ретрансляція потоків (MediaMTX):** Система підключається до IP-камер (RTSP/RTMP), забезпечує перегляд у реальному часі з мінімальною затримкою та паралельно нарізає відео у тимчасовий буфер (хвилинні сегменти `fMP4`).
2. **Детекція руху та кільцевий буфер у RAM (FFmpeg):** Фоновий процес безперервно читає потік, утримуючи останні кадри в оперативній пам'яті (`FFmpegStdout`). Фільтр `lavfi.scene_score` відстежує зміну сцени (`FFmpegStderr`). Як тільки фіксується рух, серія кадрів зберігається на диск і створюється подія.
3. **Розпізнавання об'єктів ШІ (Python + YOLOv8):** Постійний фоновий демон (`detect.py`) приймає запити через швидкий Unix-сокет (`/tmp/yolo.sock`), аналізує збережені кадри моделлю **YOLOv8n (ONNX)**, позначає знайдених людей червоною рамкою (`bounding box`) і фіксує рівень впевненості.
4. **Архівація та сповіщення:** Скрипт копіювання (`Copier`) об'єднує відеосегменти до та після моменту тривоги в постійний архів, а система миттєво надсилає фотофіксацію події у Telegram.

<!-- -->

1. **Stream Ingestion & Restreaming (MediaMTX):** Connects to IP cameras (RTSP/RTMP), provides low-latency live viewing, and simultaneously records video into a temporary rolling buffer (1-minute `fMP4` segments).
2. **Motion Detection & RAM Ring Buffer (FFmpeg):** A background worker continuously reads the stream, keeping recent frames in RAM (`FFmpegStdout`). The `lavfi.scene_score` filter monitors scene changes (`FFmpegStderr`). Once motion is detected, a burst of JPEG frames is flushed to disk and an event is logged.
3. **AI Object Recognition (Python + YOLOv8):** A persistent background daemon (`detect.py`) listens on a fast Unix socket (`/tmp/yolo.sock`), analyzes event frames using **YOLOv8n (ONNX)**, draws red bounding boxes around detected people, and records the confidence score.
4. **Archiving & Alerting:** The archiving worker (`Copier`) preserves video segments before and after the trigger into permanent storage, while the notification engine instantly dispatches an annotated snapshot to Telegram.

---

## Основні можливості / Key Features

- **Двоетапна ШІ-аналітика (FFmpeg + YOLOv8) / Two-Stage AI Analytics:**
  - Суттєва економія ресурсів CPU завдяки запуску нейромережі лише при фактичній зміні сцени.
  - Гнучке налаштування чутливості для кожної камери: FPS, поріг зміни сцени (`sceneThreshold`), обробка лише ключових кадрів (`onlyKeyFrames`).
  - Автоматичне виділення людей на кадрах подій та збереження метаданих розпізнавання в БД.

- **Розумне керування відеоархівом / Smart Video Archive Management:**
  - Автоматичне збереження відеофрагментів навколо моменту тривоги (з урахуванням буфера до початку руху).
  - Багаторівневе автоматичне очищення диска (`Cleaner`): тимчасові сегменти видаляються через кілька хвилин, події без виявлення людини — через 24 години, а підтверджені події з людьми зберігаються до 30 днів.

- **Повноцінна інтеграція з Telegram та Viber / Full Telegram & Viber Bot Integration:**
  - Миттєві тривожні сповіщення з фотографією, обведеною людиною, назвою камери, адресою та посиланням на подію.
  - Проста прив'язка акаунта користувача до бота за персональним токеном та вибір мови сповіщень прямо в чаті месенджера.

- **Автоматизація конфігурації та самовідновлення / Server Automation & Self-Healing:**
  - Керування камерами через вебінтерфейс автоматично оновлює конфігурацію `mediamtx.yml` та перезапускає відповідні служби `systemd` (`camsboard_device-@.service`).
  - Вбудований моніторинг активності потоків (`heartbeat` через `/tmp/alive-*`) для контролю стабільності роботи кожної камери.

- **Сучасна безпека та багатомовність / Modern Security & Localization:**
  - Безпарольний вхід із двофакторною автентифікацією (2FA) через одноразові коди у **Telegram, Viber або Email**.
  - Захист форм входу через **Google reCAPTCHA Enterprise** з динамічною оцінкою ризику.
  - Повна підтримка багатомовності з динамічним завантаженням словників та детальним системним логуванням.

---

## Системні вимоги / System Requirements (Ubuntu 24.04+)

- **Apache** 2.4+ (`mod_rewrite`)
- **PHP** 8.3+ та **Composer** / **PHP** 8.3+ and **Composer**
- **MySQL** 8.0+
- **Python** 3.12+ (`python3-venv`, `python3-pip`)
- Системні бібліотеки для OpenCV / System libraries for OpenCV (`libgl1`, `libglib2.0-0`)

---

## Встановлення / Installation

Покрокову інструкцію з розгортання сервера дивіться у файлі **[INSTALL.md](docs/INSTALL.md)**.
For a step-by-step server deployment guide, see **[INSTALL.md](docs/INSTALL.md)**.
