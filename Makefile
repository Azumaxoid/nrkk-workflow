# Approval Workflow Makefile

.PHONY: help up down build logs shell test newrelic-up newrelic-logs newrelic-test

# Default environment
ENV ?= development

help: ## Show this help message
	@echo "Approval Workflow Commands"
	@echo "=========================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Standard Docker commands
up: ## Start the application (standard version)
	docker-compose up -d --build

down: ## Stop the application
	docker-compose down

build: ## Build the application containers
	docker-compose build

logs: ## Show application logs
	docker-compose logs -f

shell: ## Access application shell
	docker exec -it approval-workflow-app bash

# New Relic commands
newrelic-up: ## Start with New Relic monitoring
	@echo "Starting with New Relic monitoring..."
	@if [ ! -f .env ]; then \
		echo "Creating .env from .env.newrelic template..."; \
		cp .env.newrelic .env; \
		echo "⚠️  Please edit .env and add your NEWRELIC_LICENSE_KEY"; \
	fi
	COMMIT_SHA=$$(git rev-parse --short HEAD 2>/dev/null || echo "dev") docker-compose -f docker-compose.newrelic.yml up -d --build

newrelic-up-full: ## Start with full New Relic monitoring (including infrastructure)
	@echo "Starting with full New Relic monitoring..."
	@if [ ! -f .env ]; then \
		echo "Creating .env from .env.newrelic template..."; \
		cp .env.newrelic .env; \
		echo "⚠️  Please edit .env and add your NEWRELIC_LICENSE_KEY"; \
	fi
	COMMIT_SHA=$$(git rev-parse --short HEAD 2>/dev/null || echo "dev") docker-compose -f docker-compose.newrelic.yml --profile monitoring up -d --build

newrelic-down: ## Stop New Relic version
	docker-compose -f docker-compose.newrelic.yml --profile monitoring down

newrelic-logs: ## Show New Relic application logs
	docker-compose -f docker-compose.newrelic.yml logs -f app

newrelic-shell: ## Access New Relic application shell
	docker exec -it approval-workflow-app-newrelic bash

newrelic-test: ## Run bulk approval test with New Relic monitoring
	docker exec approval-workflow-app-newrelic php artisan test:bulk-approval

newrelic-agent-logs: ## Show New Relic agent logs
	@echo "=== PHP Agent Log ==="
	@docker exec approval-workflow-app-newrelic cat /var/log/newrelic/php_agent.log 2>/dev/null || echo "No PHP agent log found"
	@echo ""
	@echo "=== Daemon Log ==="
	@docker exec approval-workflow-app-newrelic cat /var/log/newrelic/newrelic-daemon.log 2>/dev/null || echo "No daemon log found"

# Testing commands
test: ## Run all tests
	docker exec approval-workflow-app php artisan test

test-bulk: ## Run bulk approval test
	docker exec approval-workflow-app php artisan test:bulk-approval

test-ui: ## Run UI tests (requires Python environment)
	@if [ -d "test_env" ]; then \
		source test_env/bin/activate && python3 test_multi_browser_approval.py; \
	else \
		echo "❌ Python test environment not found. Run: python3 -m venv test_env && source test_env/bin/activate && pip install selenium webdriver-manager"; \
	fi

# Database commands
db-reset: ## Reset database with fresh migrations and seeds
	docker exec approval-workflow-app php artisan migrate:fresh --seed

db-seed: ## Run database seeders
	docker exec approval-workflow-app php artisan db:seed

# Development commands
install: ## Install composer dependencies
	docker exec approval-workflow-app composer install

key-generate: ## Generate application key
	docker exec approval-workflow-app php artisan key:generate

cache-clear: ## Clear application cache
	docker exec approval-workflow-app php artisan cache:clear
	docker exec approval-workflow-app php artisan config:clear
	docker exec approval-workflow-app php artisan view:clear

# Status commands
status: ## Show application status
	@echo "=== Container Status ==="
	@docker-compose ps
	@echo ""
	@echo "=== Application Health ==="
	@curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:8080/ || echo "Application not accessible"

newrelic-status: ## Show New Relic version status
	@echo "=== New Relic Container Status ==="
	@docker-compose -f docker-compose.newrelic.yml ps
	@echo ""
	@echo "=== Application Health ==="
	@curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:8080/ || echo "Application not accessible"
	@echo ""
	@echo "=== New Relic Agent Status ==="
	@docker exec approval-workflow-app-newrelic php -m newrelic 2>/dev/null && echo "✅ New Relic extension loaded" || echo "❌ New Relic extension not loaded"

# Utility commands
clean: ## Clean up Docker resources
	docker system prune -f
	docker volume prune -f

env-setup: ## Setup environment file
	@if [ ! -f .env ]; then \
		echo "Creating .env from .env.example..."; \
		cp .env.example .env; \
		echo "✅ Created .env file. Please edit it with your configuration."; \
	else \
		echo "⚠️  .env file already exists"; \
	fi

newrelic-env-setup: ## Setup New Relic environment file
	@if [ ! -f .env ]; then \
		echo "Creating .env from .env.newrelic..."; \
		cp .env.newrelic .env; \
		echo "✅ Created .env file with New Relic template."; \
		echo "⚠️  Please edit .env and add your NEWRELIC_LICENSE_KEY"; \
	else \
		echo "⚠️  .env file already exists"; \
	fi