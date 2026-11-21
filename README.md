**English** | [Українська](README.uk.md)

# CamsBoard — Smart Video Surveillance System with AI Detection

**CamsBoard** is an open-source, self-hosted video surveillance, recording, and intelligent real-time event analytics system.

The system is built on a high-performance two-stage architecture: cameras push **RTMP** streams to the server, **FFmpeg** analyzes them in real time for scene changes, and only upon detecting motion does it pass frames to a Python neural network service based on **YOLOv8** (via Unix Domain Socket) for accurate human detection.

---

## Key Features

- **Two-Stage AI Analytics (FFmpeg + YOLOv8):**
  - Significant CPU resource savings by triggering the neural network only upon actual scene changes.
  - Flexible sensitivity settings per camera: FPS, scene change threshold (`sceneThreshold`), and keyframe-only processing (`onlyKeyFrames`).
  - Automatic bounding-box highlighting of detected people on event frames and saving recognition metadata to the database.

- **Smart Video Archive Management:**
  - Automatic preservation of video clips around the trigger moment (including a pre-motion buffer).
  - Multi-tier automated disk cleanup (`Cleaner`): temporary segments are purged after a few minutes, events without human detection after 24 hours, and confirmed human-detection events are retained for up to 30 days.

- **Full Telegram & Viber Bot Integration:**
  - Instant alerts: annotated snapshots with bounding boxes in Telegram and real-time text notifications in Viber containing the camera name, address, and event details.
  - Easy user account linking via a personal token and notification language selection directly inside the messenger chat.

- **Server Automation & Self-Healing:**
  - Managing cameras via the web UI automatically updates `mediamtx.yml` and restarts the corresponding `systemd` services (`camsboard_device-@.service`).
  - Built-in stream activity monitoring (`heartbeat` via `/tmp/alive-*`) to ensure continuous uptime for every camera.

- **Modern Security & Localization:**
  - Passwordless login with two-factor authentication (2FA) via one-time codes delivered through **Telegram, Viber, or Email**.
  - Login form protection powered by **Google reCAPTCHA Enterprise** with dynamic risk scoring.
  - Full multi-language support with dynamic dictionary loading and detailed system event logging.

---

## System Architecture

```text
[RTMP Cameras] (Push streams to server)
      │
      ▼
[MediaMTX] (RTMP Server & HLS Streamer)
      │                     │
      │                     ▼
      │            (MP4 video segments) ──► [Disk Storage / Video Archive]
      ▼
[FFmpeg] (Scene change filter + frame segmentation)
  │   │
  │   ▼
  │  [PHP Worker (FFmpegScene.php)]
  │   │
  ▼   ▼
[PHP Worker (FFmpegStdout.php)]
            │ (on scene change)
            ▼
  [Python YOLOv8 Daemon] (Unix Socket /tmp/yolo.sock)
            │ (human detected)
            ▼
  [MySQL DB + Telegram / Viber Notifications]
```

---

## Technology Stack

- **Backend:** PHP 8.3+ (modular architecture, custom ORM `DataProvider`, REST/JSON API), Python 3.10+ (`ultralytics` YOLOv8, OpenCV).
- **Frontend:** Vanilla JavaScript (ES6+), HTML5, CSS3, HLS.js (browser video playback without external heavy frameworks).
- **Video & Streaming:** MediaMTX (RTMP/HLS), FFmpeg.
- **Database:** MySQL 8.0 / MariaDB.
- **Infrastructure:** Ubuntu / Apache, `systemd`.

---

## Documentation

- **[Installation & Deployment Guide](docs/INSTALL.md)** — step-by-step instructions for setting up the server, database, Python environment, MediaMTX, and background services.

---

## Project Structure

```text
├── config/             # Environment configuration templates (.env.example) and systemd units
├── deploy/             # Database schema and initial seed data (schema.sql)
├── docs/               # Installation and administration documentation
├── js/                 # Public web directory (entry points, JS, CSS, temporary files)
├── CSS/
├── python/             # Python YOLOv8 background detection service
├── src/                # Core PHP backend logic (API Gateways, Workers, Messenger Bots)
└── storage/            # Video archive storage, event snapshots, and logs (gitignored)
```

---

## Security

This repository contains no sensitive data. All API keys, database passwords, messenger bot tokens, and reCAPTCHA keys are configured via the `.env` file and database settings panel after installation.

## License

This project is distributed under the **MIT License**. See the [LICENSE](LICENSE) file for details.
