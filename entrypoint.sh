#!/bin/bash
cd /var/www/html

# Set New Relic configuration from environment variables
if [ -n "$NEW_RELIC_LICENSE_KEY" ]; then
    echo "newrelic.license = \"$NEW_RELIC_LICENSE_KEY\"" >> /usr/local/etc/php/conf.d/newrelic.ini
fi

if [ -n "$NEW_RELIC_APP_NAME" ]; then
    echo "newrelic.appname = \"$NEW_RELIC_APP_NAME\"" >> /usr/local/etc/php/conf.d/newrelic.ini
fi

echo "newrelic.attributes.include = \"request.parameters.*\"" >> /usr/local/etc/php/conf.d/newrelic.ini
echo "newrelic.error_collector.attributes.include = \"request.parameters.*\"" >> /usr/local/etc/php/conf.d/newrelic.ini
echo "newrelic.transaction_events.attributes.include = \"request.parameters.*\"" >> /usr/local/etc/php/conf.d/newrelic.ini
echo "newrelic.transaction_tracer.attributes.include = \"request.parameters.*\"" >> /usr/local/etc/php/conf.d/newrelic.ini
echo "newrelic.attributes.exclude = \"request.parameters.wstoken\"" >> /usr/local/etc/php/conf.d/newrelic.ini
echo "newrelic.error_collector.attributes.exclude = \"request.parameters.wstoken\"" >> /usr/local/etc/php/conf.d/newrelic.ini
echo "newrelic.transaction_events.attributes.exclude = \"request.parameters.wstoken\"" >> /usr/local/etc/php/conf.d/newrelic.ini
echo "newrelic.transaction_tracer.attributes.exclude = \"request.parameters.wstoken\"" >> /usr/local/etc/php/conf.d/newrelic.ini

# Execute the main command
exec "$@"
