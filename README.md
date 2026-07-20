# HTTP Traffic Entropy-Based DDoS Detection

A lightweight PHP-based HTTP traffic monitoring and anomaly detection system that detects potential HTTP Flood attacks using Shannon Entropy, dynamic threshold calculation, and IP re-evaluation.

The system continuously analyzes incoming HTTP requests within a sliding time window and classifies traffic into normal or suspicious states. When suspicious traffic is detected, an additional IP re-evaluation stage is performed before determining the final decision.

---

## Features

- HTTP request logging
- Sliding window traffic analysis
- Shannon Entropy calculation
- Normalized Entropy calculation
- Dynamic K parameter
- Adaptive threshold calculation
- Two-stage traffic classification
- Suspicious IP identification
- IP entropy re-evaluation
- Telegram attack notification
- Performance logging

---

## Project Structure

```
.
├── config.php
├── logger.php
└── core
    ├── main-function.php
    ├── sliding-window.php
    ├── save-performance-log.php
    └── send-telegram.php
```

---

## Workflow

```
Incoming HTTP Request
          │
          ▼
logger.php
(Store request information)
          │
          ▼
raw_traffic
          │
          ▼
Sliding Window
(last 30 seconds)
          │
          ▼
Entropy Calculation
          │
          ▼
Dynamic K
          │
          ▼
Threshold Calculation
          │
          ▼
Initial Classification
     │           │
 NORMAL       SUS
                 │
                 ▼
      Suspicious IP Analysis
                 │
                 ▼
        IP Entropy Re-evaluation
                 │
                 ▼
      Final Classification
                 │
                 ▼
 Telegram Notification (optional)
```

---

## Components

### logger.php

Captures every incoming HTTP request and stores traffic metadata into the database.

Recorded information includes:

- Timestamp
- Source IP
- Endpoint
- HTTP Method
- User Agent
- Payload Size

---

### sliding-window.php

Retrieves traffic statistics within a configurable sliding time window.

Current implementation:

- Window size: **30 seconds**
- Groups requests by source IP
- Calculates:
    - Total requests
    - Number of unique IPs
    - Request count per IP

---

### main-function.php

Core detection engine.

Responsibilities include:

1. Retrieve traffic from sliding window
2. Calculate Shannon Entropy
3. Calculate Normalized Entropy
4. Calculate Dynamic K
5. Build adaptive threshold
6. Perform initial traffic classification
7. Identify suspicious IPs
8. Perform entropy-based IP re-evaluation
9. Produce final detection result
10. Store every calculation into the database

---

### save-performance-log.php

Measures execution time for each detection cycle and stores it into the performance log table.

---

### send-telegram.php

Sends Telegram notifications whenever an attack is confirmed.

The notification contains:

- Detection timestamp
- Total requests
- Entropy values
- Threshold values
- Suspicious IP list

---

## Detection Process

### Stage 1

Traffic is analyzed using Shannon Entropy.

The system computes:

- Entropy
- Normalized Entropy
- Dynamic K
- Dynamic Threshold

Traffic is classified as:

- NORMAL
- SUS (Suspicious)

---

### Stage 2

Only suspicious traffic proceeds to IP re-evaluation.

The system:

- Filters IPs with above-average request counts
- Computes entropy of suspicious IPs
- Calculates a second threshold
- Produces the final classification

Possible results:

- NORMAL
- ATTACK

---

## Database

The system stores detection data into multiple tables.

Main tables include:

| Table | Purpose |
|--------|---------|
| raw_traffic | Raw HTTP request log |
| window_log | Sliding window information |
| entropy_log | Entropy calculation results |
| dynamic_k | Dynamic K values |
| threshold | Threshold calculation |
| classification | Initial and final classification |
| suspicious_ip | Suspicious IP records |
| reevaluation_log | IP re-evaluation results |
| performance_log | Detection execution time |

---

## Requirements

- PHP 8+
- MySQL
- Apache/Nginx
- Telegram Bot (optional)

---

## Configuration

Database connection is configured in:

```
config.php
```

Example:

Telegram configuration is located in:

```
core/send-telegram.php
```

Configure:

- Bot Token
- Chat ID

---

## Running the System

1. Configure database connection.
2. Create the required database tables.
3. Integrate `logger.php` into the target web application to record incoming HTTP requests.
4. Execute `core/main-function.php` periodically (e.g., using cron) to perform traffic analysis.
5. Monitor the generated logs and database records.

---

## Output

For each execution cycle, the system produces:

- Entropy
- Normalized Entropy
- Dynamic K
- Threshold
- Initial classification
- Suspicious IP list (if applicable)
- Re-evaluation result
- Final decision
- Execution time

---

## License

This project is provided for educational and research purposes.
