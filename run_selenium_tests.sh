#!/bin/bash

echo "🚀 Selenium UI Test Runner for Approval Workflow"
echo "==============================================="

# Check if Python is available
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 is not installed. Please install Python3 to run tests."
    exit 1
fi

# Check if pip is available
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 is not installed. Please install pip3 to run tests."
    exit 1
fi

# Install required Python packages
echo "📦 Installing required Python packages..."
pip3 install selenium pytest --user --quiet

# Check if Chrome is available (optional for headless mode)
if command -v google-chrome &> /dev/null || command -v chrome &> /dev/null || command -v chromium-browser &> /dev/null; then
    echo "✅ Chrome browser detected"
else
    echo "⚠️  Chrome not detected - will try Selenium Grid"
fi

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Check if application is running
echo "🔍 Checking if application is running..."
if curl -s http://localhost:8080 > /dev/null; then
    echo "✅ Application is running at http://localhost:8080"
else
    echo "⚠️  Application may not be running at http://localhost:8080"
    echo "   Starting docker-compose services..."
    docker-compose up -d
    
    # Wait for services to start
    echo "⏳ Waiting for services to start (30 seconds)..."
    sleep 30
    
    if curl -s http://localhost:8080 > /dev/null; then
        echo "✅ Application is now running"
    else
        echo "❌ Could not start application. Please check docker-compose logs."
        exit 1
    fi
fi

# Check if Selenium Grid is running
echo "🔍 Checking Selenium Grid..."
if curl -s http://localhost:4444/wd/hub/status > /dev/null; then
    echo "✅ Selenium Grid is running"
else
    echo "⚠️  Selenium Grid not detected - tests will use local Chrome if available"
fi

echo ""
echo "🧪 Starting Selenium Tests..."
echo "=============================="

# Run the tests
cd "$(dirname "$0")"
python3 tests/selenium_tests.py

# Capture exit code
EXIT_CODE=$?

echo ""
echo "=============================="
if [ $EXIT_CODE -eq 0 ]; then
    echo "🎉 All tests completed successfully!"
else
    echo "❌ Some tests failed. Check the output above for details."
fi

echo ""
echo "📊 Test Environment Information:"
echo "   - Application URL: http://localhost:8080"
echo "   - Selenium Grid: http://localhost:4444"
echo "   - PHPMyAdmin: http://localhost:8081"

exit $EXIT_CODE