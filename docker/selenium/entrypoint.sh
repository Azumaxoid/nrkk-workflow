#!/bin/bash

# Start Xvfb for headless Chrome
echo "🖥️  Starting Xvfb for headless Chrome..."
Xvfb :99 -screen 0 1920x1080x24 -ac +extension GLX +render -noreset &
export DISPLAY=:99

# Wait for Xvfb to start
sleep 2

# Check if required environment variables are set
if [ -z "$APP_URL" ]; then
    echo "⚠️  APP_URL not set, using default: http://localhost:8080"
    export APP_URL="http://localhost:8080"
fi

echo "🧪 Starting Selenium tests..."
echo "📍 Target application: $APP_URL"
echo "🌐 Chrome version: $(google-chrome --version)"
echo "🔧 ChromeDriver version: $(chromedriver --version)"

# Run the command passed to the container
exec "$@"