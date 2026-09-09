@echo off
cd /d "%~dp0"
echo Starting Wasla CLIP image-search on http://127.0.0.1:3010
.\venv\Scripts\uvicorn.exe main:app --host 127.0.0.1 --port 3010
