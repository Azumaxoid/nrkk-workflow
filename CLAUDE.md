
# New Relic Application Analysis Guide

## Application Metadata
AppName : Approval Workflow Production
Important attributes:

## Initial Analysis Steps

### 1. Custom Attributes Collection
When asked to analyze an application, gather information in the following order:

1. **Codebase Review**
   - Search for New Relic custom attribute additions in the project
   - Common patterns to look for:
     - `newrelic.addCustomAttribute`
     - `newrelic.addCustomParameter`
     - `@Trace` annotations
     - Custom event sending code

2. **New Relic Query Execution**
   ```sql
   FROM Transaction
   SELECT keyset()
   WHERE appName = '[APPLICATION_NAME]'
   SINCE 1 week ago
   ```
   This query retrieves all custom attributes for the application, serving as domain knowledge metadata

### 2. Analysis Priority Order

#### Phase 1: Transaction Event Analysis
```sql
-- Performance Overview
FROM Transaction
SELECT
  average(duration) as 'Avg Response Time',
  percentile(duration, 95) as 'P95',
  count(*) as 'Request Count',
  percentage(count(*), WHERE error = true) as 'Error Rate'
WHERE appName = '[APPLICATION_NAME]'
SINCE 1 week ago
FACET name

-- Error Analysis
FROM Transaction
SELECT count(*)
WHERE appName = '[APPLICATION_NAME]'
  AND error = true
SINCE 1 week ago
FACET `error.class`, `error.message`

-- Throughput Analysis
FROM Transaction
SELECT rate(count(*), 1 minute) as 'RPM'
WHERE appName = '[APPLICATION_NAME]'
SINCE 1 week ago
TIMESERIES
```

#### Phase 2: Log Analysis (if necessary)
```sql
-- Error Log Review
FROM Log
SELECT message, level, timestamp
WHERE entity.name = '[APPLICATION_NAME]'
  AND level IN ('ERROR', 'FATAL')
SINCE 1 week ago
LIMIT 100

-- Transaction-specific Logs
FROM Log
SELECT *
WHERE trace.id = '[TRACE_ID]'
SINCE 1 week ago
```

#### Phase 3: Span Analysis (Distributed Tracing)
```sql
-- Service Dependencies and Latency
FROM Span
SELECT average(duration) as 'avg_duration'
WHERE service.name = '[SERVICE_NAME]'
SINCE 1 week ago
FACET span.kind, name

-- External Service Call Analysis
FROM Span
SELECT count(*), average(duration)
WHERE service.name = '[SERVICE_NAME]'
  AND span.kind = 'client'
SINCE 1 week ago
FACET db.system, http.url
```

#### Phase 4: Infrastructure Information (if necessary)
```sql
-- CPU/Memory Usage
FROM SystemSample
SELECT average(cpuPercent), average(memoryUsedPercent)
WHERE entityName = '[HOST_NAME]'
SINCE 1 week ago
TIMESERIES

-- Container Metrics (if applicable)
FROM ContainerSample
SELECT average(cpuPercent), average(memoryUsedBytes)
WHERE containerImage LIKE '%[APPLICATION_NAME]%'
SINCE 1 week ago
```

## Analysis Perspectives

### Performance Analysis Checklist
- [ ] Response time trends (improving/degrading)
- [ ] P95/P99 latency
- [ ] Throughput variations
- [ ] Database query N+1 problems
- [ ] External API call bottlenecks
- [ ] Memory leak indicators
- [ ] CPU usage anomalies

### Error Analysis Checklist
- [ ] Error rate trends
- [ ] Error types and frequency
- [ ] Specific endpoints generating errors
- [ ] User impact scope
- [ ] Error-deployment correlation

### Custom Attribute-Driven Analysis
Use collected custom attributes for business logic-specific analysis:
- Performance by user segment
- Feature usage patterns
- Resource usage by tenant
- Business transaction success rates

## Problem-Solving Approach

### 1. Problem Identification
- Clarify symptoms (when started, under what conditions)
- Assess impact scope (number of users, features, services)
- Formulate root cause hypotheses

### 2. Solution Proposals
When problems are identified, present solutions in the following format:

#### Immediate Response (Short-term Solutions)
- Cache clearing
- Service restart
- Load balancing adjustment
- Temporary scale-out

#### Root Cause Resolution (Long-term Improvements)
- Code Optimization
  - Query improvements
  - Algorithm optimization
  - Asynchronous processing introduction
- Architecture Improvements
  - Caching strategies
  - Database index additions
  - Microservice decomposition
- Monitoring Enhancement
  - Alert additions/adjustments
  - Custom dashboard creation
  - SLI/SLO definitions

### 3. Implementation Priority Recommendations
- Impact Level (High/Medium/Low)
- Implementation Difficulty (Easy/Medium/Hard)
- Estimated Effort
- Risk Assessment

## New Relic Query Best Practices

### Efficient Query Creation
```sql
-- Time Window Usage
-- Short-term analysis: SINCE 1 hour ago
-- Daily analysis: SINCE 1 day ago
-- Weekly analysis: SINCE 1 week ago
-- Comparative analysis: SINCE 1 week ago COMPARE WITH 1 week ago

-- Sampling for Large Datasets
FROM Transaction
SELECT *
WHERE appName = '[APPLICATION_NAME]'
SINCE 1 week ago
LIMIT 1000
```

### Dashboard Query Templates
```sql
-- Golden Signals Monitoring
-- 1. Latency
FROM Transaction SELECT percentile(duration, 50, 95, 99)

-- 2. Traffic
FROM Transaction SELECT rate(count(*), 1 minute)

-- 3. Errors
FROM Transaction SELECT percentage(count(*), WHERE error = true)

-- 4. Saturation
FROM SystemSample SELECT average(cpuPercent), average(memoryUsedPercent)
```

## Reporting Format

Present analysis results in the following structure:

1. **Executive Summary**
   - Current situation overview (1-2 sentences)
   - Key findings (3-5 bullet points)
   - Prioritized recommendations

2. **Detailed Analysis**
   - Data-driven current state analysis
   - Problem root causes
   - Impact scope and severity

3. **Solutions and Next Steps**
   - Immediate actions required
   - Medium to long-term improvement proposals
   - Monitoring enhancement suggestions

## Important Notes

- Custom attributes may contain sensitive information; handle with care
- Execute queries with minimum necessary time ranges to minimize performance impact
- Production environment change proposals must assume testing environment validation
- Consider business context; evaluate business impact alongside technical solutions